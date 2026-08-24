<?php

/**
 * Events Section — Maps Settings
 *
 * Page ID: page-evt-maps
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-evt-maps">
	<div class="panel">
		<div class="panel-header"><i class="ri-road-map-line"></i> <?php esc_html_e( 'Google Maps Settings', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[maps_disable_scroll]" value="1" <?php checked( $apollo['maps_disable_scroll'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable scrollwheel zooming on Google Maps', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[maps_logged_only]" value="1" <?php checked( $apollo['maps_logged_only'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Make location info visible only to logged-in users', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[maps_auto_generate]" value="1" <?php checked( $apollo['maps_auto_generate'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable generate Google Maps from address for new events', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="form-grid" style="margin-top:16px">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Maps Display Type', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[maps_display_type]">
						<option value="roadmap" <?php selected( $apollo['maps_display_type'] ?? 'roadmap', 'roadmap' ); ?>><?php esc_html_e( 'Roadmap', 'apollo-admin' ); ?></option>
						<option value="satellite" <?php selected( $apollo['maps_display_type'] ?? 'roadmap', 'satellite' ); ?>><?php esc_html_e( 'Satellite', 'apollo-admin' ); ?></option>
						<option value="hybrid" <?php selected( $apollo['maps_display_type'] ?? 'roadmap', 'hybrid' ); ?>><?php esc_html_e( 'Hybrid', 'apollo-admin' ); ?></option>
						<option value="terrain" <?php selected( $apollo['maps_display_type'] ?? 'roadmap', 'terrain' ); ?>><?php esc_html_e( 'Terrain', 'apollo-admin' ); ?></option>
					</select>
					<span class="field-hint"><?php esc_html_e( 'Roadmap = normal 2D · Satellite = photographic tiles · Hybrid = mix · Terrain = physical', 'apollo-admin' ); ?></span>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Starting Zoom Level', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[maps_zoom]">
						<option value="7" <?php selected( $apollo['maps_zoom'] ?? '14', '7' ); ?>>7 — <?php esc_html_e( 'Country view', 'apollo-admin' ); ?></option>
						<option value="8" <?php selected( $apollo['maps_zoom'] ?? '14', '8' ); ?>>8</option>
						<option value="10" <?php selected( $apollo['maps_zoom'] ?? '14', '10' ); ?>>10</option>
						<option value="12" <?php selected( $apollo['maps_zoom'] ?? '14', '12' ); ?>>12</option>
						<option value="14" <?php selected( $apollo['maps_zoom'] ?? '14', '14' ); ?>>14</option>
						<option value="16" <?php selected( $apollo['maps_zoom'] ?? '14', '16' ); ?>>16</option>
						<option value="18" <?php selected( $apollo['maps_zoom'] ?? '14', '18' ); ?>>18 — <?php esc_html_e( 'Street view', 'apollo-admin' ); ?></option>
					</select>
					<span class="field-hint"><?php esc_html_e( '18 = zoomed in (few roads) · 7 = zoomed out (most of the country)', 'apollo-admin' ); ?></span>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Leaflet Map Style', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[maps_style]">
						<?php
						$styles = array( 'Default', 'Apple Maps-esque', 'Avocado World', 'Bentley', 'Blue Essence', 'Blue Water', 'Cool Grey', 'Hot Pink', 'Muted Monotone', 'Pale', 'Retro Gold', 'Rich Black', 'Shift Worker', 'Vintage Yellow Light' );
						foreach ( $styles as $style ) :
							?>
							<option value="<?php echo esc_attr( sanitize_title( $style ) ); ?>" <?php selected( $apollo['maps_style'] ?? 'default', sanitize_title( $style ) ); ?>><?php echo esc_html( $style ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Custom Map Marker Icon URL', 'apollo-admin' ); ?></label>
					<input type="url" class="apollo-input input" name="apollo[maps_marker_icon]" value="<?php echo esc_attr( $apollo['maps_marker_icon'] ?? '' ); ?>" placeholder="https://cdn.example.com/marker.png">
				</div>
			</div>
		</div>
	</div>
</div>