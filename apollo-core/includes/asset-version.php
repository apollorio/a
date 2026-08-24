<?php
/**
 * Apollo — automatic asset cache-busting.
 *
 * THE PROBLEM THIS SOLVES
 * ----------------------
 * `D:\dev\_apollo.rio.br\plugins` is mirrored to production on save. There is no
 * build step and no staging tier: a saved file is a deployed file. But every
 * plugin stamps its assets with a hand-bumped constant —
 * `wp_enqueue_script( 'x', $url, [], APOLLO_X_VERSION, true )` — and those
 * constants sit still for months.
 *
 * So the file changes and the URL does not. Cloudflare sits in front of the
 * origin and caches by URL, which means a CSS/JS fix is deployed, correct on
 * disk, and *invisible in the browser* — to returning visitors and to the CDN
 * edge alike — until a human remembers to bump a number nobody is watching.
 *
 * This is not hypothetical. On 2026-08-07 the event lightbox rendered as a blank
 * white panel through two full rounds of "verified on disk" fixes. The code was
 * right the whole time; the browser was being handed a copy of
 * apollo-single-event.js from an earlier deploy. A hard reload did not help —
 * only a unique query string did, which is precisely the signature of an edge
 * cache keyed on a URL that never changes.
 *
 * An audit that day found 26 of 29 asset-enqueuing plugins in the same state.
 * Only apollo-events, apollo-docs and apollo-sign used filemtime.
 *
 * THE FIX
 * -------
 * One filter pair, applied once, at the chokepoint every plugin already goes
 * through. `script_loader_src` / `style_loader_src` see the final URL of every
 * enqueued asset, so no plugin has to change a line: the version each one
 * declares is kept and the file's own mtime is appended to it.
 *
 *     …/apollo-events/assets/js/apollo-single-event.js?ver=1.7.0
 *  →  …/apollo-events/assets/js/apollo-single-event.js?ver=1.7.0.1786135947
 *
 * The declared version stays readable and meaningful; the mtime makes the URL
 * change the moment the file does. Both the browser and the edge see a new
 * resource and fetch it. Nothing else about caching needs to be configured.
 *
 * WHAT IT DELIBERATELY DOES NOT TOUCH
 * -----------------------------------
 *  · Anything not served from this WordPress install — cdn.apollo.rio.br,
 *    assets.apollo.rio.br, cdn.jsdelivr.net, Google, reCAPTCHA. Those are
 *    versioned by their own host and several carry SRI hashes; appending a
 *    query string to them would be wrong at best and would break integrity
 *    checks at worst. The core.js load contract (06-cdn.json) is untouched.
 *  · WordPress core and third-party plugin assets. Scope is `apollo-*` only, so
 *    a mistake here cannot take down anything outside this ecosystem.
 *  · Admin-only asset pipelines are included, but the mtime lookup is cached
 *    per request, so a page with 40 assets does 40 stat() calls at most once.
 *
 * Additive and reversible: deactivating this file restores the previous
 * behaviour exactly. No registry contract changes — this adds no CPT, meta key,
 * table, endpoint or template. It only makes the URLs honest.
 *
 * @package Apollo\Core
 * @since   6.0.0
 * @see     _inventory/registry/06-cdn.json  CDN hosts that must stay untouched
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_asset_mtime_version' ) ) {
	/**
	 * Append a file's mtime to its declared version string.
	 *
	 * @param string $src    Full asset URL as WordPress is about to print it.
	 * @param string $handle Registered handle (used only for diagnostics).
	 * @return string Rewritten URL, or $src untouched when out of scope.
	 */
	function apollo_asset_mtime_version( $src, $handle = '' ) {
		if ( ! is_string( $src ) || '' === $src ) {
			return $src;
		}

		/*
		 * Scope gate 1 — must live under this install's plugins directory.
		 * plugins_url() is resolved once and reused; it already reflects
		 * whatever scheme and host WordPress is serving on, so a protocol
		 * mismatch cannot make this silently miss.
		 */
		static $plugins_url = null;
		if ( null === $plugins_url ) {
			$plugins_url = plugins_url();
		}

		$base = preg_replace( '#^https?:#', '', (string) $plugins_url );
		$bare = preg_replace( '#^https?:#', '', $src );
		if ( 0 !== strpos( $bare, $base ) ) {
			return $src; // CDN, external host, theme, or wp-includes — leave alone.
		}

		// Scope gate 2 — apollo-* only. Never rewrite a third-party plugin.
		$relative = ltrim( substr( $bare, strlen( $base ) ), '/' );
		if ( 0 !== strpos( $relative, 'apollo-' ) ) {
			return $src;
		}

		// Strip any existing query before resolving to a path on disk.
		$path_part = strtok( $relative, '?' );
		if ( ! is_string( $path_part ) || '' === $path_part ) {
			return $src;
		}

		$file = wp_normalize_path( WP_PLUGIN_DIR . '/' . $path_part );

		/*
		 * Traversal guard. $src is built by plugins, not by user input, but a
		 * malformed URL must never let this stat outside the plugins dir.
		 */
		if ( 0 !== strpos( $file, wp_normalize_path( WP_PLUGIN_DIR ) . '/' ) ) {
			return $src;
		}

		static $mtimes = array();
		if ( ! array_key_exists( $file, $mtimes ) ) {
			$mtimes[ $file ] = is_readable( $file ) ? (int) filemtime( $file ) : 0;
		}
		$mtime = $mtimes[ $file ];
		if ( ! $mtime ) {
			return $src; // Concatenated/virtual asset, or a path we cannot see.
		}

		/*
		 * Keep the declared version and append the mtime, rather than replacing
		 * it. `?ver=1.7.0.1786135947` still tells a human which release this is
		 * while guaranteeing the URL moves with the file. Assets enqueued with
		 * ver=null or ver=false get the mtime alone.
		 */
		$parts   = wp_parse_url( $src );
		$query   = array();
		if ( ! empty( $parts['query'] ) ) {
			wp_parse_str( $parts['query'], $query );
		}
		$declared = isset( $query['ver'] ) && '' !== $query['ver'] ? (string) $query['ver'] : '';

		// Idempotent: never stack a second mtime onto an already-stamped URL.
		if ( '' !== $declared && substr( $declared, -strlen( (string) $mtime ) ) === (string) $mtime ) {
			return $src;
		}

		$query['ver'] = '' !== $declared ? $declared . '.' . $mtime : (string) $mtime;

		$stem = strtok( $src, '?' );

		return add_query_arg( $query, $stem );
	}
}

/*
 * Priority 20: after any plugin that rewrites its own src (a CDN swapper, an
 * optimiser), so this sees the final URL and stamps the file actually served.
 */
add_filter( 'script_loader_src', 'apollo_asset_mtime_version', 20, 2 );
add_filter( 'style_loader_src', 'apollo_asset_mtime_version', 20, 2 );

if ( ! function_exists( 'apollo_asset_url' ) ) {
	/**
	 * Build a cache-safe URL for a HAND-PRINTED asset.
	 *
	 * WHY THIS EXISTS ALONGSIDE THE FILTER ABOVE
	 * ------------------------------------------
	 * The filter covers everything that goes through wp_enqueue_script/style.
	 * On this platform that is not everything, and the gap is not an accident:
	 * Blank Canvas templates deliberately do not run wp_head/wp_footer, so a
	 * plugin that needs an asset on a canvas screen prints the tag itself —
	 *
	 *     printf('<script src="%s"></script>',
	 *         esc_url( APOLLO_STATS_URL . 'assets/js/tracker.js?v=' . APOLLO_STATS_VERSION ));
	 *
	 * — on hooks like apollo/canvas/head. Those tags never touch WP_Scripts, so
	 * script_loader_src never sees them and the version stays frozen at whatever
	 * constant the plugin declared. Since the canvas IS the app, that is most of
	 * the surface area that matters.
	 *
	 * Verified live on 2026-08-07: apollo-statistics/src/Plugin.php:283 prints
	 * tracker.js this way, and it was the one apollo-* asset on /portal and /feed
	 * still serving a frozen `?v=2.0.6` after the filter went in.
	 *
	 * USE
	 * ---
	 *     echo esc_url( apollo_asset_url( APOLLO_STATS_DIR, APOLLO_STATS_URL,
	 *                                     'assets/js/tracker.js', APOLLO_STATS_VERSION ) );
	 *
	 * Same output shape as the filter — `?ver=2.0.6.1786146186` — so the two
	 * paths cannot disagree about what a versioned Apollo asset looks like.
	 *
	 * @param string $dir      Plugin directory (APOLLO_*_DIR), trailing slash ok.
	 * @param string $url      Plugin URL (APOLLO_*_URL), trailing slash ok.
	 * @param string $relative Path relative to the plugin root.
	 * @param string $fallback Declared version, used when the file cannot be read.
	 * @return string
	 */
	function apollo_asset_url( string $dir, string $url, string $relative, string $fallback = '' ): string {
		$relative = ltrim( $relative, '/' );
		$file     = rtrim( $dir, '/\\' ) . '/' . $relative;
		$src      = rtrim( $url, '/' ) . '/' . $relative;

		$mtime = is_readable( $file ) ? (int) filemtime( $file ) : 0;
		if ( ! $mtime ) {
			return '' !== $fallback ? add_query_arg( 'ver', $fallback, $src ) : $src;
		}

		return add_query_arg( 'ver', '' !== $fallback ? $fallback . '.' . $mtime : (string) $mtime, $src );
	}
}
