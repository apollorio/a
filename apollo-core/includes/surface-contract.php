<?php
/**
 * Apollo — Surface Contract. The one wire every content type plugs into.
 *
 * WHAT A "SURFACE" IS
 * -------------------
 * A surface is any content type that has a canonical single page AND wants that
 * same page to be openable in place, from a card, anywhere in the ecosystem —
 * an event, a DJ, a loc. Today exactly one of them can do that, because
 * apollo-events grew the machinery for itself:
 *
 *   · a modular renderer, apollo_event_render_single(), that is the SSOT for
 *     the page, an inline embed and a REST fragment alike
 *   · a REST fragment endpoint that reuses that renderer, so page and overlay
 *     cannot drift
 *   · a card contract, data-ev-open="{id}" + a real permalink href
 *   · a public enqueue API another plugin can call
 *
 * An audit on 2026-08-07 found none of that in apollo-djs, apollo-loc,
 * apollo-hub, apollo-social, apollo-users or apollo-adverts. Not a worse
 * version — none. `data-dj-open` and `data-loc-open` do not exist anywhere, and
 * neither plugin registers a single REST route.
 *
 * Copying the machinery into each plugin would produce four divergent copies of
 * the same four contracts, which is the failure mode the reveal-selector list
 * already demonstrated: three hand-synced copies, each with a "keep in sync
 * manually" comment attached. So the machinery moves HERE, once, and a plugin
 * opts in with one call.
 *
 * REGISTERING A SURFACE
 * ---------------------
 *     apollo_surface_register( 'dj', array(
 *         'post_type' => 'dj',
 *         'renderer'  => 'apollo_dj_render_single',   // fn( int $id ): string
 *         'rest_base' => 'djs',                       // apollo/v1/djs/{id}/fragmento
 *         'can_view'  => 'apollo_dj_can_view',        // optional, fn( int ): bool
 *     ) );
 *
 * That is the whole integration. Core then provides, for free:
 *
 *   · GET apollo/v1/{rest_base}/{id}/fragmento — the fragment endpoint, wired
 *     to the plugin's own renderer, gated by its own visibility rule
 *   · apollo_surface_open_attrs( 'dj', $id ) — the card contract, emitted
 *     correctly every time instead of remembered correctly every time
 *   · one lightbox runtime that serves every registered surface
 *
 * DORMANT UNTIL READY, BY DESIGN
 * ------------------------------
 * A registration whose renderer is not callable is accepted and then ignored:
 * no route, no attributes. A plugin can therefore declare its intent before its
 * renderer exists without breaking a single request, and the surface switches
 * on the moment the callable appears. Nothing here registers a CPT, taxonomy,
 * meta key or table — apollo-core remains the only place those happen, and this
 * file adds none of them.
 *
 * @package Apollo\Core
 * @since   6.0.0
 * @see     _inventory/registry/05-global-architecture.json  core as master registry
 * @see     apollo-events/_sandbox/SPEC-lightbox-integration.md  the proven pattern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_surface_registry' ) ) {
	/**
	 * The surface table. Read-mostly; written only by apollo_surface_register().
	 *
	 * @param string|null              $type Surface key to set.
	 * @param array<string,mixed>|null $args Definition.
	 * @return array<string,array<string,mixed>>
	 */
	function apollo_surface_registry( ?string $type = null, ?array $args = null ): array {
		static $surfaces = array();
		if ( null !== $type && null !== $args ) {
			$surfaces[ $type ] = $args;
		}
		return $surfaces;
	}
}

if ( ! function_exists( 'apollo_surface_register' ) ) {
	/**
	 * Declare a content type as an openable surface.
	 *
	 * Safe to call on every request and safe to call before the renderer exists.
	 *
	 * @param string              $type Surface key: 'event' | 'dj' | 'loc' | ….
	 * @param array<string,mixed> $args post_type, renderer, rest_base, can_view.
	 * @return void
	 */
	function apollo_surface_register( string $type, array $args ): void {
		$type = sanitize_key( $type );
		if ( '' === $type ) {
			return;
		}

		$args = wp_parse_args(
			$args,
			array(
				'post_type' => $type,
				'renderer'  => '',
				'rest_base' => $type . 's',
				'can_view'  => '',
				'enqueue'   => '', // Optional: the surface's own asset loader.
			)
		);

		apollo_surface_registry( $type, $args );
	}
}

if ( ! function_exists( 'apollo_surface_get' ) ) {
	/**
	 * Fetch a surface definition, but only if it is actually usable.
	 *
	 * "Usable" means the renderer is callable right now. Everything downstream
	 * checks through here, which is what makes a half-declared surface inert
	 * rather than a fatal.
	 *
	 * @param string $type Surface key.
	 * @return array<string,mixed>|null
	 */
	function apollo_surface_get( string $type ): ?array {
		$all = apollo_surface_registry();
		if ( ! isset( $all[ $type ] ) ) {
			return null;
		}
		$s = $all[ $type ];
		return is_callable( $s['renderer'] ) ? $s : null;
	}
}

if ( ! function_exists( 'apollo_surface_can_view' ) ) {
	/**
	 * Visibility gate for one item on one surface.
	 *
	 * Delegates to the surface's own rule when it has one. The fallback is
	 * deliberately strict — published posts of the right type only — so a
	 * surface that forgets to supply can_view cannot leak a draft or a private
	 * item through the public fragment endpoint.
	 *
	 * @param string $type    Surface key.
	 * @param int    $post_id Item id.
	 * @return bool
	 */
	function apollo_surface_can_view( string $type, int $post_id ): bool {
		$s = apollo_surface_get( $type );
		if ( ! $s || $post_id <= 0 ) {
			return false;
		}

		if ( is_callable( $s['can_view'] ) ) {
			return (bool) call_user_func( $s['can_view'], $post_id );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $s['post_type'] ) {
			return false;
		}
		return 'publish' === $post->post_status || current_user_can( 'read_post', $post_id );
	}
}

if ( ! function_exists( 'apollo_surface_open_attrs' ) ) {
	/**
	 * THE CARD CONTRACT — emit it, do not remember it.
	 *
	 *     <article <?php echo apollo_surface_open_attrs( 'dj', $id ); ?>>
	 *
	 * yields  data-ap-open="dj:123" href="https://…/dj/slug/"
	 *
	 * The href is not decoration and this is why it is emitted here rather than
	 * left to each card template. It carries three behaviours the runtime does
	 * not reimplement: the fallback when the fragment request fails, the browser
	 * affordances (middle-click, ⌘-click, "open in new tab"), and a destination
	 * for crawlers and screen readers that never run the click handler. On
	 * /portal, 0 of 10 cards carried one — every compact rail row was a dead end
	 * off the happy path. Emitting the pair together makes that state
	 * unreachable.
	 *
	 * @param string $type    Surface key.
	 * @param int    $post_id Item id.
	 * @param bool   $as_link Include href (pass false only for a non-anchor
	 *                        wrapper that contains its own <a>).
	 * @return string Escaped attribute string, '' when the surface is dormant.
	 */
	function apollo_surface_open_attrs( string $type, int $post_id, bool $as_link = true ): string {
		if ( ! apollo_surface_get( $type ) || $post_id <= 0 ) {
			return '';
		}

		$attrs = sprintf( 'data-ap-open="%s"', esc_attr( $type . ':' . $post_id ) );

		if ( $as_link ) {
			$url = get_permalink( $post_id );
			if ( $url ) {
				$attrs .= sprintf( ' href="%s"', esc_url( $url ) );
			}
		}

		return $attrs;
	}
}

if ( ! function_exists( 'apollo_surface_render' ) ) {
	/**
	 * Render one item through its surface's own renderer.
	 *
	 * @param string              $type    Surface key.
	 * @param int                 $post_id Item id.
	 * @param array<string,mixed> $args    Passed through to the renderer.
	 * @return string HTML, or '' when dormant or not viewable.
	 */
	function apollo_surface_render( string $type, int $post_id, array $args = array() ): string {
		$s = apollo_surface_get( $type );
		if ( ! $s || ! apollo_surface_can_view( $type, $post_id ) ) {
			return '';
		}
		return (string) call_user_func( $s['renderer'], $post_id, $args );
	}
}

if ( ! function_exists( 'apollo_surface_register_routes' ) ) {
	/**
	 * One fragment endpoint per usable surface.
	 *
	 * GET apollo/v1/{rest_base}/{id}/fragmento
	 *
	 * Public by design — it mirrors exactly what the canonical single page shows
	 * to the same visitor, and apollo_surface_can_view() is the single gate for
	 * both. Read-only, no side effects, so no nonce is required for the anonymous
	 * case; a logged-in caller's cookie still resolves their own visibility.
	 *
	 * @return void
	 */
	function apollo_surface_register_routes(): void {
		/*
		 * NEVER CLOBBER A PLUGIN'S OWN ENDPOINT.
		 *
		 * apollo-events already ships apollo/v1/eventos/{id}/fragmento and every
		 * data-ev-open card on /eventos and /portal depends on it. Registering
		 * the identical route here would replace a working, battle-tested
		 * handler with the generic one and take the lightbox down on both
		 * screens at once. A surface whose route already exists simply reuses
		 * it — which is the correct outcome anyway, since both resolve to the
		 * same renderer.
		 */
		$existing = array();
		if ( function_exists( 'rest_get_server' ) ) {
			$existing = array_keys( rest_get_server()->get_routes() );
		}
		$ns = defined( 'APOLLO_REST_NAMESPACE' ) ? APOLLO_REST_NAMESPACE : 'apollo/v1';

		foreach ( apollo_surface_registry() as $type => $def ) {
			if ( ! apollo_surface_get( $type ) ) {
				continue; // Dormant surface — declared, renderer not there yet.
			}

			$route = '/' . $ns . '/' . $def['rest_base'] . '/(?P<id>\d+)/fragmento';
			if ( in_array( $route, $existing, true ) ) {
				continue;
			}

			register_rest_route(
				$ns,
				'/' . $def['rest_base'] . '/(?P<id>\d+)/fragmento',
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'args'                => array(
						'id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => static function ( $v ) {
								return absint( $v ) > 0;
							},
						),
					),
					'callback'            => static function ( WP_REST_Request $req ) use ( $type ) {
						$id = absint( $req->get_param( 'id' ) );

						if ( ! apollo_surface_can_view( $type, $id ) ) {
							return new WP_Error(
								'apollo_surface_forbidden',
								__( 'Conteúdo indisponível.', 'apollo-core' ),
								array( 'status' => 404 ) // 404, not 403 — do not confirm existence.
							);
						}

						$html = apollo_surface_render( $type, $id );
						if ( '' === $html ) {
							return new WP_Error(
								'apollo_surface_empty',
								__( 'Conteúdo indisponível.', 'apollo-core' ),
								array( 'status' => 404 )
							);
						}

						return rest_ensure_response(
							array(
								'id'    => $id,
								'type'  => $type,
								'url'   => (string) get_permalink( $id ),
								'title' => wp_strip_all_tags( (string) get_the_title( $id ) ),
								'html'  => $html,
							)
						);
					},
				)
			);
		}
	}
}

/*
 * Late (20): every plugin registers its surface on init/plugins_loaded, and a
 * surface declared after this ran would silently have no endpoint.
 */
add_action( 'rest_api_init', 'apollo_surface_register_routes', 20 );

if ( ! function_exists( 'apollo_surface_js_config' ) ) {
	/**
	 * The surface table, as the browser needs it.
	 *
	 * The lightbox runtime reads this to turn data-ap-open="dj:123" into the
	 * right endpoint without hard-coding a single path. Adding a surface in PHP
	 * is therefore the *only* step — no JS edit, which is what keeps the two
	 * from drifting.
	 *
	 * @return array<string,array<string,string>>
	 */
	function apollo_surface_js_config(): array {
		$ns  = defined( 'APOLLO_REST_NAMESPACE' ) ? APOLLO_REST_NAMESPACE : 'apollo/v1';
		$out = array();
		foreach ( apollo_surface_registry() as $type => $def ) {
			if ( ! apollo_surface_get( $type ) ) {
				continue;
			}
			$out[ $type ] = array(
				'rest' => esc_url_raw( rest_url( $ns . '/' . $def['rest_base'] . '/' ) ),
			);
		}
		return $out;
	}
}
