<?php

/**
 * New Home — Radio Widget
 *
 * Renders the Apollo Radio widget via [apollo_radio mode="widget"].
 * Requires the apollo-radio plugin to be active. Falls back to a
 * static placeholder when the plugin is not available.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( shortcode_exists( 'apollo_radio' ) ) {
	echo do_shortcode( '[apollo_radio mode="widget"]' );
} else {
	?>
	<aside class="nh-radio nh-fixed is-fixed is-paused" id="nhRadio"
		aria-label="<?php esc_attr_e( 'Rádio Apollo', 'apollo-templates' ); ?>">
		<div class="nh-radio-info" aria-live="polite">
			<span class="nh-radio-track">Apollo Radio</span>
			<span class="nh-radio-artist">offline</span>
		</div>
	</aside>
	<?php
}
?>