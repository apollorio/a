<?php

/**
 * Apollo Events — same-origin proxy for CDN iframe assets.
 *
 * WHY THIS EXISTS (root-cause fix, 2026-08-01)
 * ─────────────────────────────────────────────────────────────────────────────
 * The portal hero's empty state embeds
 *   https://assets.apollo.rio.br/iframe/highlighted-fallback_portal-events.html
 * and the browser refused it with:
 *
 *   Refused to display 'https://assets.apollo.rio.br/' in a frame because it
 *   set 'X-Frame-Options' to 'sameorigin'.
 *
 * That header is sent by the ASSET HOST and enforced by the browser against the
 * FRAMED document's own origin. `apollo.rio.br` framing `assets.apollo.rio.br`
 * is cross-origin, so "sameorigin" forbids it — and no CSP, sandbox attribute
 * or markup change on THIS site can override a header sent by a different
 * server. The previous pass documented that correctly but left the embed
 * broken, which is not a fix.
 *
 * The way to satisfy `SAMEORIGIN` without touching the CDN is to stop making
 * the request cross-origin: fetch the file SERVER-SIDE and re-serve it from
 * apollo.rio.br. The browser then frames a same-origin document and the header
 * is satisfied by construction.
 *
 *   /wp-json/apollo/v1/iframe/highlighted-fallback  →  200 text/html
 *
 * Deliberate constraints:
 *
 *  · ALLOWLIST ONLY. The route takes a `slug` from a fixed map, never a URL.
 *    A proxy that forwards an arbitrary caller-supplied URL is an SSRF hole —
 *    it would let anyone use this server to reach internal addresses. There is
 *    no code path here that fetches something the map does not name.
 *  · Cached in a transient so a portal page view does not become an outbound
 *    HTTP round trip. 6h, plus a short negative cache so an outage doesn't turn
 *    into a fetch storm.
 *  · Re-serves with `X-Frame-Options: SAMEORIGIN` of our OWN, so the proxied
 *    document is still only embeddable by this site — the CDN's intent is
 *    preserved, it is just now expressed from the right origin.
 *  · Fails CLOSED and QUIET: on any error it returns 204 with an empty body.
 *    The hero's CSS gradient (layer 0, styles-hero.php) already carries the
 *    card on its own, so a dead CDN degrades to "plain dark panel", never to a
 *    broken-page graphic or an error dumped into the layout.
 *
 * @package Apollo\Event
 * @since   1.5.5
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_event_iframe_proxy_map' ) ) {
	/**
	 * slug => absolute upstream URL. THE ONLY fetchable targets.
	 *
	 * @return array<string, string>
	 */
	function apollo_event_iframe_proxy_map(): array {
		return (array) apply_filters(
			'apollo/event/iframe_proxy_map',
			array(
				'highlighted-fallback' => 'https://assets.apollo.rio.br/iframe/highlighted-fallback_portal-events.html',
			)
		);
	}
}

if ( ! function_exists( 'apollo_event_iframe_proxy_url' ) ) {
	/**
	 * Public same-origin URL for a proxied iframe slug.
	 *
	 * @param string $slug Key in apollo_event_iframe_proxy_map().
	 */
	function apollo_event_iframe_proxy_url( string $slug ): string {
		return esc_url_raw( rest_url( 'apollo/v1/iframe/' . $slug ) );
	}
}

if ( ! function_exists( 'apollo_event_register_iframe_proxy' ) ) {
	/**
	 * Register GET /apollo/v1/iframe/{slug}.
	 */
	function apollo_event_register_iframe_proxy(): void {
		register_rest_route(
			'apollo/v1',
			'/iframe/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => 'apollo_event_serve_proxied_iframe',
				'args'                => array(
					'slug' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}
	add_action( 'rest_api_init', 'apollo_event_register_iframe_proxy' );
}

if ( ! function_exists( 'apollo_event_serve_proxied_iframe' ) ) {
	/**
	 * Serve an allowlisted upstream HTML document from this origin.
	 *
	 * Emits raw HTML (not a JSON envelope) because the consumer is an <iframe>
	 * src, so the response must BE the document.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return void Exits.
	 */
	function apollo_event_serve_proxied_iframe( WP_REST_Request $request ): void {
		$slug = (string) $request->get_param( 'slug' );
		$map  = apollo_event_iframe_proxy_map();

		$send = static function ( string $body, int $status ): void {
			status_header( $status );
			header( 'Content-Type: text/html; charset=UTF-8' );
			// Our own clickjacking guard: the proxied doc stays embeddable only here.
			header( 'X-Frame-Options: SAMEORIGIN' );
			header( 'Content-Security-Policy: frame-ancestors \'self\'' );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Cache-Control: public, max-age=1800' );
			echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- upstream HTML document, served verbatim by design.
			exit;
		};

		if ( ! isset( $map[ $slug ] ) ) {
			$send( '', 204 );
			return;
		}

		$cache_key = 'apollo_ev_iframe_' . md5( $slug );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) ) {
			$send( $cached, '' === $cached ? 204 : 200 );
			return;
		}

		$response = wp_remote_get(
			$map[ $slug ],
			array(
				'timeout'     => 6,
				'redirection' => 2,
				'user-agent'  => 'apollo-rio-iframe-proxy/1.0',
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Short negative cache — an upstream outage must not become a
			// 6-second outbound wait on every single portal page view.
			set_transient( $cache_key, '', 2 * MINUTE_IN_SECONDS );
			$send( '', 204 );
			return;
		}

		$body = (string) wp_remote_retrieve_body( $response );
		if ( '' === trim( $body ) ) {
			set_transient( $cache_key, '', 2 * MINUTE_IN_SECONDS );
			$send( '', 204 );
			return;
		}

		/* Relative URLs inside the upstream document would now resolve against
		   apollo.rio.br instead of the CDN. Inject <base> so its own assets keep
		   resolving to where they actually live. */
		if ( false === stripos( $body, '<base ' ) ) {
			$base = '<base href="' . esc_url( trailingslashit( dirname( $map[ $slug ] ) ) ) . '">';
			if ( preg_match( '/<head\b[^>]*>/i', $body, $m ) ) {
				$body = str_replace( $m[0], $m[0] . $base, $body );
			} else {
				$body = $base . $body;
			}
		}

		set_transient( $cache_key, $body, 6 * HOUR_IN_SECONDS );
		$send( $body, 200 );
	}
}
