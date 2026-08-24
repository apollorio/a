<?php
/**
 * Single Event — <head> assets
 *
 * Emitted only in full-page mode (single-event.php). Inline/lightbox embeds
 * enqueue the very same bundle through apollo_event_enqueue_single_assets(),
 * so there is exactly one stylesheet for all three surfaces.
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apollo_ev_version = defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.1.0';
$apollo_ev_url     = defined( 'APOLLO_EVENT_URL' ) ? APOLLO_EVENT_URL : '';
$apollo_core_js    = function_exists( 'apollo_cdn_core_js_url' )
	? apollo_cdn_core_js_url()
	: 'https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb';
?>
<link rel="preconnect" href="https://assets.apollo.rio.br">
<link rel="preconnect" href="https://cdn.apollo.rio.br">
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

<!-- Apollo CORE.JS — SSOT for :root tokens, theme engine, Lenis and GSAP. Must be first, in <head>. -->
<script src="<?php echo esc_url( $apollo_core_js ); ?>" fetchpriority="high"></script>

<!-- cdn.jsdelivr.net, not unpkg.com — unpkg isn't in the CSP allowlist
     (script-src/style-src), so this was silently blocked. -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"
	integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

<link rel="stylesheet"
	href="<?php echo esc_url( $apollo_ev_url . 'assets/css/apollo-single-event.css?v=' . rawurlencode( apollo_event_asset_ver( 'assets/css/apollo-single-event.css' ) ) ); ?>">
