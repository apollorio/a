<?php
/**
 * Plugin Name: Apollo SoundCloud
 * Plugin URI: https://apollo.rio.br/plugins/apollo-soundcloud
 * Description: One SoundCloud player for the whole ecosystem. Apollo's own UI driving a hidden Widget-API iframe, with preview (25–65%) and full modes. Replaces seven hand-rolled implementations.
 * Version: 1.0.0
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-soundcloud
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * ── WHY THIS PLUGIN EXISTS ───────────────────────────────────────────────────
 *
 * An audit on 2026-08-17 found SEVEN independent SoundCloud players across the
 * ecosystem, no two alike:
 *
 *   apollo-djs/assets/js/dj-single.js:41          full SC.Widget
 *   apollo-djs/assets/js/dj-card-single.js:138    sets iframe.src, no widget
 *   apollo-djs/…/single/scripts.php:154           sets iframe.src, no widget
 *   apollo-users/templates/single-profile.php     full SC.Widget, INLINE in a template
 *   apollo-users/assets/js/profile.js:202         ANOTHER SC.Widget, same plugin
 *   apollo-hub/assets/js/home/radio.js:29         lazy api.js + own SC.Widget
 *   apollo-events/…/apollo-v2/single-dj.php:278   hardcoded iframe, no control
 *
 * plus api.js loaded from three places, a widget-URL builder written four times,
 * and two hardcoded track IDs shipping in production
 * (apollo-dashboard panel-feed.php → 1293057640, apollo-hub radio.php → 293).
 *
 * Seven players means seven pause behaviours, seven failure modes when the SDK
 * is slow, and no way to guarantee only one thing plays at a time — which a
 * social network needs. Same defect shape as the four accommodation cards and
 * the five "Out Now" sections: no single owner.
 *
 * ── THE API SITUATION, STATED PLAINLY ────────────────────────────────────────
 *
 * SoundCloud CLOSED open API registration. Access is application-only, reviewed
 * manually, selective, and can take weeks. Credentials cannot be assumed.
 *
 * That is why the Widget API is the PRIMARY path here and not a fallback: it
 * needs no credentials, it is available to everyone today, and it is the only
 * way to implement a 25–65% preview at all — that requires getDuration(), which
 * a bare iframe cannot provide.
 *
 * If credentials ever arrive, apollo_sc_provider() returns 'api', direct stream
 * URLs replace the iframe, and NOTHING at any call site changes. That seam is
 * the reason the closed registration is an inconvenience rather than a blocker.
 *
 * @package Apollo\SoundCloud
 * @see     _inventory/PLAN-apollo-soundcloud.md
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_SC_VERSION', '1.0.0' );
define( 'APOLLO_SC_FILE', __FILE__ );
define( 'APOLLO_SC_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_SC_URL', plugin_dir_url( __FILE__ ) );

/** Preview window, as percentages of total duration. Overridable per call. */
define( 'APOLLO_SC_PREVIEW_START_PCT', 25 );
define( 'APOLLO_SC_PREVIEW_END_PCT', 65 );

/**
 * Tracks shorter than this play in full even in preview mode.
 * A 4-second ID clip has no meaningful middle 40%.
 */
define( 'APOLLO_SC_PREVIEW_MIN_SECONDS', 20 );

require_once APOLLO_SC_DIR . 'includes/resolve.php';
require_once APOLLO_SC_DIR . 'includes/meta.php';
require_once APOLLO_SC_DIR . 'includes/render.php';

/**
 * Which transport is available.
 *
 * THE SEAM. Returns 'widget' today — the Widget API, no credentials, works for
 * everyone. Returns 'api' only if HTTP API credentials are configured, which
 * requires an application SoundCloud reviews by hand.
 *
 * Every consumer calls apollo_soundcloud_player() and never asks which provider
 * is active. Swapping this return value is the entire migration.
 *
 * @return string 'widget'|'api'
 */
function apollo_sc_provider(): string {
	$key = (string) get_option( 'apollo_sc_client_id', '' );
	/**
	 * Filter the active SoundCloud transport.
	 *
	 * @param string $provider 'widget'|'api'.
	 */
	return (string) apply_filters( 'apollo/soundcloud/provider', '' !== $key ? 'api' : 'widget' );
}

/**
 * Register assets. Enqueued on demand by the renderer, never globally.
 *
 * The SoundCloud SDK is NOT registered here — it is loaded lazily by the
 * runtime on first play. Three plugins currently load api.js on every page that
 * might contain a player, and a page carrying two of them loads it twice.
 *
 * @return void
 */
function apollo_sc_register_assets(): void {
	$ver = APOLLO_SC_VERSION;

	wp_register_style(
		'apollo-sc',
		APOLLO_SC_URL . 'assets/css/apollo-sc.css',
		array(),
		$ver
	);

	wp_register_script(
		'apollo-sc',
		APOLLO_SC_URL . 'assets/js/apollo-sc.js',
		array(),
		$ver,
		true
	);

	wp_localize_script(
		'apollo-sc',
		'APOLLO_SC',
		array(
			'sdk'        => 'https://w.soundcloud.com/player/api.js',
			'provider'   => apollo_sc_provider(),
			'startPct'   => (int) APOLLO_SC_PREVIEW_START_PCT,
			'endPct'     => (int) APOLLO_SC_PREVIEW_END_PCT,
			'minSeconds' => (int) APOLLO_SC_PREVIEW_MIN_SECONDS,
			'i18n'       => array(
				'play'  => __( 'Tocar', 'apollo-soundcloud' ),
				'pause' => __( 'Pausar', 'apollo-soundcloud' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'apollo_sc_register_assets' );
add_action( 'admin_enqueue_scripts', 'apollo_sc_register_assets' );
