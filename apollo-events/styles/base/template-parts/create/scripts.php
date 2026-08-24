<?php
/**
 * Create Event — Config bootstrap + scripts
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event_v = defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.0.0';

/* Media library (blank canvas — must print assets manually). */
wp_enqueue_media();
wp_print_media_templates();
wp_print_styles( 'media-views' );
wp_print_styles( 'imgareaselect' );
wp_print_scripts( 'jquery' );
wp_print_scripts( 'jquery-ui-core' );
wp_print_scripts( 'jquery-ui-sortable' );
/*
 * wp-i18n MUST be printed before anything that ships a translations "after"
 * script. media-views / media-editor / wp-a11y all emit
 * `wp.i18n.setLocaleData(...)` inline; without wp-i18n itself on the page that
 * throws "Cannot read properties of undefined (reading 'setLocaleData')" four
 * times on every load.
 *
 * underscore + backbone are hard deps of media-models; print them explicitly so
 * a partial queue on blank-canvas pages cannot leave wp.media undefined.
 */
wp_print_scripts( 'wp-i18n' );
wp_print_scripts( 'underscore' );
wp_print_scripts( 'backbone' );
wp_print_scripts( 'wp-a11y' );
wp_print_scripts( 'wp-util' );
wp_print_scripts( 'wp-plupload' );
wp_print_scripts( 'media-models' );
wp_print_scripts( 'media-views' );
wp_print_scripts( 'media-editor' );
wp_print_scripts( 'media-audiovideo' );
?>
<script>
window.APOLLO_EVENT_FORM = <?php echo wp_json_encode( $form_config ); ?>;
window.APOLLO_EVENTS = (window.APOLLO_EVENT_FORM.events || []).map(function (ev) {
	return {
		id: String(ev.id),
		title: ev.title || '',
		about: '',
		season: '',
		tickets: 'available',
		status: ev.status || 'draft',
		startDate: ev.start_date || '',
		startTime: '',
		endDate: '',
		endTime: '',
		cover: ev.banner || '',
		videoUrl: '',
		audioUrl: '',
		ticketsUrl: '',
		genres: [],
		venue: { name: ev.loc_name || '', address: '', lat: null, lon: null, images: [] },
		lineup: [],
		coupons: [],
		editUrl: ev.edit_url || '',
		viewUrl: ev.view_url || ''
	};
});
window.getApolloEvent = function (id) {
	var list = window.APOLLO_EVENTS || [];
	for (var i = 0; i < list.length; i++) {
		if (String(list[i].id) === String(id)) return list[i];
	}
	return null;
};
</script>
<?php
/* Versioned by file mtime — a plugin-version-only cache buster served every
   fix stale until the constant was bumped. See apollo_event_asset_ver(). */
$apollo_js = static function ( string $rel ): string {
	$ver = function_exists( 'apollo_event_asset_ver' ) ? apollo_event_asset_ver( $rel ) : ( defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.0.0' );
	return APOLLO_EVENT_URL . $rel . '?v=' . rawurlencode( $ver );
};
?>
<script src="<?php echo esc_url( $apollo_js( 'assets/js/apollo-events-about-editor.js' ) ); ?>"></script>
<script src="<?php echo esc_url( $apollo_js( 'assets/js/apollo-events-create-form.js' ) ); ?>"></script>
<script src="<?php echo esc_url( $apollo_js( 'assets/js/apollo-events-create-bridge.js' ) ); ?>"></script>
<script src="<?php echo esc_url( $apollo_js( 'assets/js/apollo-events-create-shell.js' ) ); ?>"></script>
<!-- Loaded LAST: owns cover, gallery, loc picker and DJ picker — the four
     widgets the simulation layer never connected to real data. -->
<script src="<?php echo esc_url( $apollo_js( 'assets/js/apollo-events-create-wire.js' ) ); ?>"></script>
