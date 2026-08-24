<?php

/**
 * Events Section — EventTop Settings
 *
 * Page ID: page-evt-eventtop
 * Designer blocks + color pickers + toggle options
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-evt-eventtop">
	<div class="panel">
		<div class="panel-header"><i class="ri-layout-top-line"></i> <?php esc_html_e( 'EventTop Settings', 'apollo-admin' ); ?></div>
		<div class="panel-body">

			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Event Title Font Color', 'apollo-admin' ); ?></label>
					<div class="color-pick"><input type="color" class="color-swatch" value="<?php echo esc_attr( $apollo['et_title_color'] ?? '#121214' ); ?>"><input type="text" class="apollo-input color-hex" name="apollo[et_title_color]" value="<?php echo esc_attr( $apollo['et_title_color'] ?? '#121214' ); ?>"></div>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Event Subtitle Font Color', 'apollo-admin' ); ?></label>
					<div class="color-pick"><input type="color" class="color-swatch" value="<?php echo esc_attr( $apollo['et_subtitle_color'] ?? '#71717a' ); ?>"><input type="text" class="apollo-input color-hex" name="apollo[et_subtitle_color]" value="<?php echo esc_attr( $apollo['et_subtitle_color'] ?? '#71717a' ); ?>"></div>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Text Under Title', 'apollo-admin' ); ?></label>
					<div class="color-pick"><input type="color" class="color-swatch" value="<?php echo esc_attr( $apollo['et_text_under'] ?? '#a1a1aa' ); ?>"><input type="text" class="apollo-input color-hex" name="apollo[et_text_under]" value="<?php echo esc_attr( $apollo['et_text_under'] ?? '#a1a1aa' ); ?>"></div>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Category Title Color', 'apollo-admin' ); ?></label>
					<div class="color-pick"><input type="color" class="color-swatch" value="<?php echo esc_attr( $apollo['et_cat_color'] ?? 'FF9820' ); ?>"><input type="text" class="apollo-input color-hex" name="apollo[et_cat_color]" value="<?php echo esc_attr( $apollo['et_cat_color'] ?? 'FF9820' ); ?>"></div>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Hover Border Left Size', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[et_border_size]">
						<?php for ( $i = 7; $i <= 15; $i++ ) : ?>
							<option value="<?php echo $i; ?>px" <?php selected( $apollo['et_border_size'] ?? '10px', $i . 'px' ); ?>><?php echo $i; ?>px</option>
						<?php endfor; ?>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Background Color', 'apollo-admin' ); ?></label>
					<div class="color-pick"><input type="color" class="color-swatch" value="<?php echo esc_attr( $apollo['et_bg_color'] ?? '#ffffff' ); ?>"><input type="text" class="apollo-input color-hex" name="apollo[et_bg_color]" value="<?php echo esc_attr( $apollo['et_bg_color'] ?? '#ffffff' ); ?>"></div>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Colorful Text Color', 'apollo-admin' ); ?></label>
					<div class="color-pick"><input type="color" class="color-swatch" value="<?php echo esc_attr( $apollo['et_colorful_text'] ?? '#ffffff' ); ?>"><input type="text" class="apollo-input color-hex" name="apollo[et_colorful_text]" value="<?php echo esc_attr( $apollo['et_colorful_text'] ?? '#ffffff' ); ?>"></div>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Default EventTop Style', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[et_style]">
						<option value="colorful_gap"><?php esc_html_e( 'Colorful with gap between events', 'apollo-admin' ); ?></option>
						<option value="colorful" selected><?php esc_html_e( 'Colorful EventTop', 'apollo-admin' ); ?></option>
						<option value="colorful_bubble"><?php esc_html_e( 'Colorful event date bubbles', 'apollo-admin' ); ?></option>
						<option value="clear_border"><?php esc_html_e( 'Clear with left border colors', 'apollo-admin' ); ?></option>
						<option value="clear_border_gap"><?php esc_html_e( 'Clear with left border & gaps', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Organizer Click Action', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[et_organizer_action]">
						<option value="lightbox" selected><?php esc_html_e( 'Open more details in lightbox', 'apollo-admin' ); ?></option>
						<option value="archive"><?php esc_html_e( 'Open organizer archive page', 'apollo-admin' ); ?></option>
						<option value="link"><?php esc_html_e( 'Open organizer learn more link', 'apollo-admin' ); ?></option>
						<option value="none"><?php esc_html_e( 'Do nothing', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Location Display Data', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[et_location_display]">
						<option value="address"><?php esc_html_e( 'Location Address', 'apollo-admin' ); ?></option>
						<option value="name" selected><?php esc_html_e( 'Location Name', 'apollo-admin' ); ?></option>
						<option value="both"><?php esc_html_e( 'Both', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>

			<!-- Designer -->
			<div class="section-title"><?php esc_html_e( 'EventTop Designer — Drag to reorder', 'apollo-admin' ); ?></div>
			<div class="designer-grid">
				<div class="designer-block active"><i class="ri-font-size-2"></i> <?php esc_html_e( 'Title', 'apollo-admin' ); ?></div>
				<div class="designer-block active"><i class="ri-text"></i> <?php esc_html_e( 'Subtitle', 'apollo-admin' ); ?></div>
				<div class="designer-block active"><i class="ri-time-line"></i> <?php esc_html_e( 'Event Time', 'apollo-admin' ); ?></div>
				<div class="designer-block active"><i class="ri-map-pin-line"></i> <?php esc_html_e( 'Location', 'apollo-admin' ); ?></div>
				<div class="designer-block active"><i class="ri-exchange-box-line"></i> <?php esc_html_e( 'Tags', 'apollo-admin' ); ?></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Unused Event Top Fields', 'apollo-admin' ); ?></div>
			<div class="unused-fields">
				<div class="designer-block"><i class="ri-image-line"></i> <?php esc_html_e( 'Event Image', 'apollo-admin' ); ?></div>
				<div class="designer-block"><i class="ri-calendar-line"></i> <?php esc_html_e( 'Date Block', 'apollo-admin' ); ?></div>
				<div class="designer-block"><i class="ri-user-line"></i> <?php esc_html_e( 'Organizer', 'apollo-admin' ); ?></div>
				<div class="designer-block"><i class="ri-bookmark-line"></i> <?php esc_html_e( 'Event Tag Types', 'apollo-admin' ); ?></div>
				<div class="designer-block"><i class="ri-loader-line"></i> <?php esc_html_e( 'Progress Bar', 'apollo-admin' ); ?></div>
				<div class="designer-block"><i class="ri-input-field"></i> <?php esc_html_e( 'Custom Field 1', 'apollo-admin' ); ?></div>
				<div class="designer-block"><i class="ri-list-check"></i> <?php esc_html_e( 'Event Type', 'apollo-admin' ); ?></div>
				<div class="designer-block"><i class="ri-list-check-2"></i> <?php esc_html_e( 'Event Type 2', 'apollo-admin' ); ?></div>
				<div class="designer-block"><i class="ri-list-check-3"></i> <?php esc_html_e( 'Event Type 3', 'apollo-admin' ); ?></div>
			</div>

			<!-- Day Block Fields -->
			<div class="section-title"><?php esc_html_e( 'Day Block Fields', 'apollo-admin' ); ?></div>
			<div class="form-grid cols-3">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Event Day Name', 'apollo-admin' ); ?></label><select class="select" name="apollo[et_day_name]">
						<option value="show" selected><?php esc_html_e( 'Show', 'apollo-admin' ); ?></option>
						<option value="hide"><?php esc_html_e( 'Hide', 'apollo-admin' ); ?></option>
					</select></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Event Start Year', 'apollo-admin' ); ?></label><select class="select" name="apollo[et_start_year]">
						<option value="show" selected><?php esc_html_e( 'Show', 'apollo-admin' ); ?></option>
						<option value="hide"><?php esc_html_e( 'Hide', 'apollo-admin' ); ?></option>
					</select></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'End Year (if different)', 'apollo-admin' ); ?></label><select class="select" name="apollo[et_end_year]">
						<option value="show" selected><?php esc_html_e( 'Show', 'apollo-admin' ); ?></option>
						<option value="hide"><?php esc_html_e( 'Hide', 'apollo-admin' ); ?></option>
					</select></div>
			</div>

			<!-- Additional Options -->
			<div class="section-title"><?php esc_html_e( 'Additional Options', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_show_meta_icons]" value="1" <?php checked( $apollo['et_show_meta_icons'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show custom meta data icons on EventTop', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_show_edit_btn]" value="1" <?php checked( $apollo['et_show_edit_btn'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show edit event button for each event', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_hide_progress]" value="1" <?php checked( $apollo['et_hide_progress'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Hide live event progress bar with time remaining', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_hide_live_icon]" value="1" <?php checked( $apollo['et_hide_live_icon'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Hide blinking "Live Now" icon from EventTop', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_widget_fields]" value="1" <?php checked( $apollo['et_widget_fields'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Display all fields in widget as well', 'apollo-admin' ); ?></span></div>
			</div>

			<!-- Hide Tags -->
			<div class="section-title"><?php esc_html_e( 'Hide Tags (selected will be hidden)', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_hide_virtual]" value="1" <?php checked( $apollo['et_hide_virtual'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Virtual Event', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_hide_hybrid]" value="1" <?php checked( $apollo['et_hide_hybrid'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Virtual/Physical Event', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_hide_status]" value="1" <?php checked( $apollo['et_hide_status'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Event Status (cancelled, reschedule, etc.)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_hide_featured]" value="1" <?php checked( $apollo['et_hide_featured'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Featured', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[et_hide_completed]" value="1" <?php checked( $apollo['et_hide_completed'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Completed', 'apollo-admin' ); ?></span></div>
			</div>

		</div>
	</div>
</div>