<?php

/**
 * Events Section — EventCard Settings
 *
 * Page ID: page-evt-eventcard
 * Designer blocks + custom icons + toggles
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-evt-eventcard">
	<div class="panel">
		<div class="panel-header"><i class="ri-layout-masonry-line"></i> <?php esc_html_e( 'EventCard Settings', 'apollo-admin' ); ?></div>
		<div class="panel-body">

			<!-- Featured Image -->
			<div class="section-title"><?php esc_html_e( 'Featured Image', 'apollo-admin' ); ?></div>
			<div class="form-grid">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Display Style', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[ec_img_style]">
						<option value="direct" selected><?php esc_html_e( 'Direct Image', 'apollo-admin' ); ?></option>
						<option value="minimized"><?php esc_html_e( 'Minimized height', 'apollo-admin' ); ?></option>
						<option value="stretch"><?php esc_html_e( '100% Image height stretch to fit', 'apollo-admin' ); ?></option>
						<option value="proportionate"><?php esc_html_e( '100% Image proportionate to width', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Minimal Height (px)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[ec_img_min_height]" value="<?php echo esc_attr( $apollo['ec_img_min_height'] ?? 250 ); ?>" placeholder="Pixels"></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[ec_disable_hover]" value="1" <?php checked( $apollo['ec_disable_hover'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable hover effect on featured image', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[ec_disable_zoom]" value="1" <?php checked( $apollo['ec_disable_zoom'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable zoom effect on click', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[ec_show_magnify]" value="1" <?php checked( $apollo['ec_show_magnify'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show magnifying glass over featured image', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="field" style="margin-top:12px"><label class="field-label"><?php esc_html_e( 'Default Event Image URL', 'apollo-admin' ); ?></label><input type="url" class="apollo-input input" name="apollo[ec_default_img]" value="<?php echo esc_attr( $apollo['ec_default_img'] ?? '' ); ?>" placeholder="https://cdn.example.com/default-event.jpg"><span class="field-hint"><?php esc_html_e( 'For events without featured images', 'apollo-admin' ); ?></span></div>

			<!-- Location Image -->
			<div class="section-title"><?php esc_html_e( 'Location Image', 'apollo-admin' ); ?></div>
			<div class="field"><label class="field-label"><?php esc_html_e( 'Location Image Height (px)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[ec_loc_img_height]" value="<?php echo esc_attr( $apollo['ec_loc_img_height'] ?? 200 ); ?>" placeholder="Pixels"></div>

			<!-- Add to Calendar -->
			<div class="section-title"><?php esc_html_e( 'Add to Calendar Options', 'apollo-admin' ); ?></div>
			<div class="field">
				<label class="field-label"><?php esc_html_e( 'Calendar Options', 'apollo-admin' ); ?></label>
				<select class="select" name="apollo[ec_cal_options]">
					<option value="all" selected><?php esc_html_e( 'All options', 'apollo-admin' ); ?></option>
					<option value="google"><?php esc_html_e( 'Only Google Add to Calendar', 'apollo-admin' ); ?></option>
					<option value="ics"><?php esc_html_e( 'Only ICS download', 'apollo-admin' ); ?></option>
					<option value="none"><?php esc_html_e( 'Do not show any', 'apollo-admin' ); ?></option>
				</select>
			</div>

			<!-- Other Settings -->
			<div class="section-title"><?php esc_html_e( 'Other EventCard Settings', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[ec_full_desc]" value="1" <?php checked( $apollo['ec_full_desc'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show full event description', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[ec_open_all]" value="1" <?php checked( $apollo['ec_open_all'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Open all EventCards by default (except tile layout)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[ec_disable_filtering]" value="1" <?php checked( $apollo['ec_disable_filtering'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable location & organizer link filtering', 'apollo-admin' ); ?></span></div>
			</div>

			<!-- Designer -->
			<div class="section-title"><?php esc_html_e( 'EventCard Layout Designer — Drag to reorder', 'apollo-admin' ); ?></div>
			<div class="designer-grid">
				<?php
				$designer_blocks = array(
					'ri-image-line'          => __( 'Featured Image', 'apollo-admin' ),
					'ri-information-line'    => __( 'Event Details', 'apollo-admin' ),
					'ri-time-line'           => __( 'Time', 'apollo-admin' ),
					'ri-map-pin-line'        => __( 'Location', 'apollo-admin' ),
					'ri-vidicon-line'        => __( 'Virtual Details', 'apollo-admin' ),
					'ri-heart-pulse-line'    => __( 'Health Guidelines', 'apollo-admin' ),
					'ri-book-read-line'      => __( 'Learn More', 'apollo-admin' ),
					'ri-calendar-check-line' => __( 'Add to Calendar', 'apollo-admin' ),
					'ri-repeat-line'         => __( 'Repeats Info', 'apollo-admin' ),
					'ri-user-star-line'      => __( 'Organizer', 'apollo-admin' ),
					'ri-image-2-line'        => __( 'Location Image', 'apollo-admin' ),
					'ri-map-2-line'          => __( 'Google Maps', 'apollo-admin' ),
					'ri-link'                => __( 'Related Events', 'apollo-admin' ),
					'ri-share-line'          => __( 'Social Share', 'apollo-admin' ),
					'ri-route-line'          => __( 'Get Directions', 'apollo-admin' ),
				);
				foreach ( $designer_blocks as $icon => $label ) :
					?>
					<div class="designer-block active"><i class="<?php echo esc_attr( $icon ); ?>"></i> <?php echo esc_html( $label ); ?></div>
				<?php endforeach; ?>
			</div>

			<!-- Custom Icons -->
			<div class="section-title"><?php esc_html_e( 'Custom Icons for Calendar', 'apollo-admin' ); ?></div>
			<div class="form-grid cols-3">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Icon Size', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[ec_icon_size]">
						<?php foreach ( array( '10px', '11px', '12px', '14px', '16px', '18px', '20px' ) as $sz ) : ?>
							<option value="<?php echo esc_attr( $sz ); ?>" <?php selected( $apollo['ec_icon_size'] ?? '14px', $sz ); ?>><?php echo esc_html( $sz ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="form-grid cols-4" style="margin-top:12px">
				<?php
				$icon_fields = array(
					'Details'    => 'ri-information-line',
					'Time'       => 'ri-time-line',
					'Repeat'     => 'ri-repeat-line',
					'Virtual'    => 'ri-vidicon-line',
					'Health'     => 'ri-heart-pulse-line',
					'Location'   => 'ri-map-pin-line',
					'Organizer'  => 'ri-user-star-line',
					'Capacity'   => 'ri-group-line',
					'Learn More' => 'ri-book-read-line',
					'Related'    => 'ri-link',
					'Ticket'     => 'ri-ticket-line',
					'Add to Cal' => 'ri-calendar-check-line',
					'Directions' => 'ri-route-line',
				);
				foreach ( $icon_fields as $label => $default ) :
					$key = 'icon_' . sanitize_title( $label );
					?>
					<div class="field"><label class="field-label" style="font-size:11px"><?php echo esc_html( $label . ' Icon' ); ?></label><input type="text" class="apollo-input input" name="apollo[ec_<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $apollo[ 'ec_' . $key ] ?? $default ); ?>" style="font-family:var(--ff-mono);font-size:11px"></div>
				<?php endforeach; ?>
			</div>

		</div>
	</div>
</div>