<?php
/**
 * Single Event — Scripts + runtime config
 *
 * The per-instance config is emitted as a JSON <script type="application/json">
 * tag INSIDE the instance root so a lightboxed event carries its own config
 * with zero globals. window.APOLLO_SINGLE_EVENT stays as a page-mode alias for
 * backwards compatibility with older integrations.
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apollo_ev_version = defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.1.0';
$apollo_ev_url     = defined( 'APOLLO_EVENT_URL' ) ? APOLLO_EVENT_URL : '';
$apollo_ev_config  = function_exists( 'apollo_event_single_js_config' )
	? apollo_event_single_js_config( (int) $post_id )
	: array( 'id' => (int) $post_id );
?>
<!-- cdn.jsdelivr.net, not unpkg.com — unpkg isn't in the CSP allowlist. -->
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"
	integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>window.APOLLO_SINGLE_EVENT = <?php echo wp_json_encode( $apollo_ev_config ); ?>;</script>

<?php
/*
 * REAL <script defer src> (2026-08-01) — same fix as
 * apollo_event_lightbox_boot() in includes/render-single.php, and the same bug:
 * these two were emitted as type="text/apollo-defer" data-apollo-when="ready".
 *
 * A non-standard `type` is inert to the browser; core.js's promoter only
 * re-executes INLINE bodies and skips tags whose payload is behind `src`. So
 * apollo-single-event.js never ran HERE EITHER — meaning /evento/{slug}, the
 * canonical single event page, was shipping with its whole runtime dead: no
 * GSAP reveals, no Leaflet venue map, no chat, no gallery. It looked like a
 * static page because it effectively was one.
 *
 * `defer` is both correct and sufficient: apollo-single-event.js gates its own
 * boot on apollo:ready via Apollo.whenReady() (with an Apollo.isReady check for
 * the already-fired case), so the core.js contract in this file's header is
 * still honoured — it is enforced inside the module, not by the tag.
 */
?>
<!-- The modules self-gate on apollo:ready; `defer` only orders them after parse. -->
<script defer src="<?php echo esc_url( $apollo_ev_url . 'assets/js/apollo-single-event.js?v=' . rawurlencode( apollo_event_asset_ver( 'assets/js/apollo-single-event.js' ) ) ); ?>"></script>
<script defer src="<?php echo esc_url( $apollo_ev_url . 'assets/js/apollo-event-lightbox.js?v=' . rawurlencode( apollo_event_asset_ver( 'assets/js/apollo-event-lightbox.js' ) ) ); ?>"></script>
