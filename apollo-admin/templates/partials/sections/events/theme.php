<?php

/**
 * Events Section — Theme & Colors
 *
 * Page ID: page-evt-theme
 * Contains extensive color picker groups for calendar theming.
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to render a color-pick field.
 */
if ( ! function_exists( 'apollo_admin_color_field' ) ) :
	function apollo_admin_color_field( $label, $name, $default, $opts = array() ) {
		$value = $opts[ 'evt_theme_' . $name ] ?? $default;
		?>
		<div class="field">
			<label class="field-label"><?php echo esc_html( $label ); ?></label>
			<div class="color-pick">
				<input type="color" class="color-swatch" value="<?php echo esc_attr( $value ); ?>">
				<input type="text" class="apollo-input color-hex" name="apollo[evt_theme_<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( $value ); ?>">
			</div>
		</div>
		<?php
	}
endif;
?>
<div class="page" id="page-evt-theme">
	<div class="panel">
		<div class="panel-header"><i class="ri-palette-line"></i> <?php esc_html_e( 'Calendar Theme', 'apollo-admin' ); ?></div>
		<div class="panel-body">

			<div class="form-grid">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Theme', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[evt_theme]">
						<option value="default" <?php selected( $apollo['evt_theme'] ?? 'default', 'default' ); ?>><?php esc_html_e( 'Default', 'apollo-admin' ); ?></option>
						<option value="dark" <?php selected( $apollo['evt_theme'] ?? 'default', 'dark' ); ?>><?php esc_html_e( 'Dark', 'apollo-admin' ); ?></option>
					</select>
					<span class="field-hint"><?php esc_html_e( 'After changing, click "Save Changes"', 'apollo-admin' ); ?></span>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Primary Font Family', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[evt_font_primary]" value="<?php echo esc_attr( $apollo['evt_font_primary'] ?? 'Space Grotesk' ); ?>" placeholder="e.g., Arial"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Secondary Font Family', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[evt_font_secondary]" value="<?php echo esc_attr( $apollo['evt_font_secondary'] ?? 'Space Mono' ); ?>" placeholder="e.g., Georgia"></div>
			</div>

			<!-- Calendar Header Colors -->
			<div class="section-title"><?php esc_html_e( 'Calendar Header Colors', 'apollo-admin' ); ?></div>
			<div class="form-grid cols-3">
				<?php
				apollo_admin_color_field( __( 'Header Month/Year', 'apollo-admin' ), 'header_month', '#121214', $apollo );
				apollo_admin_color_field( __( 'Calendar Date Color', 'apollo-admin' ), 'cal_date', '#121214', $apollo );
				apollo_admin_color_field( __( 'Sort Options Text', 'apollo-admin' ), 'sort_text', '#71717a', $apollo );
				apollo_admin_color_field( __( 'Jump Months Trigger', 'apollo-admin' ), 'jump_trigger', '#121214', $apollo );
				apollo_admin_color_field( __( 'Jumper Buttons', 'apollo-admin' ), 'jumper_btn', '#e4e4e7', $apollo );
				apollo_admin_color_field( __( 'Jumper Current', 'apollo-admin' ), 'jumper_current', 'FF9820', $apollo );
				apollo_admin_color_field( __( 'Jumper Active', 'apollo-admin' ), 'jumper_active', 'FF9820', $apollo );
				apollo_admin_color_field( __( 'Current Month Btn', 'apollo-admin' ), 'month_btn', '#121214', $apollo );
				apollo_admin_color_field( __( 'Arrow Circle', 'apollo-admin' ), 'arrow_circle', '#e4e4e7', $apollo );
				?>
			</div>

			<!-- General Calendar Colors -->
			<div class="section-title"><?php esc_html_e( 'General Calendar Colors', 'apollo-admin' ); ?></div>
			<div class="form-grid cols-3">
				<?php
				apollo_admin_color_field( __( 'Calendar Loader', 'apollo-admin' ), 'cal_loader', 'FF9820', $apollo );
				apollo_admin_color_field( __( 'Social Media Icons', 'apollo-admin' ), 'social_icons', '#71717a', $apollo );
				apollo_admin_color_field( __( 'Search Field', 'apollo-admin' ), 'search_field', '#ffffff', $apollo );
				apollo_admin_color_field( __( 'Search Icon', 'apollo-admin' ), 'search_icon', '#71717a', $apollo );
				apollo_admin_color_field( __( 'Search Effect', 'apollo-admin' ), 'search_effect', 'FF9820', $apollo );
				apollo_admin_color_field( __( 'Events Found Data', 'apollo-admin' ), 'events_found', '#121214', $apollo );
				apollo_admin_color_field( __( 'Show More Bar', 'apollo-admin' ), 'show_more_bar', '#f4f4f5', $apollo );
				apollo_admin_color_field( __( 'Timezone Section', 'apollo-admin' ), 'timezone_sec', '#71717a', $apollo );
				apollo_admin_color_field( __( 'No Event Box BG', 'apollo-admin' ), 'no_event_bg', '#f8f8f9', $apollo );
				apollo_admin_color_field( __( 'General Border Color', 'apollo-admin' ), 'border_color', '#e4e4e7', $apollo );
				apollo_admin_color_field( __( 'Repeat Header', 'apollo-admin' ), 'repeat_header', '#121214', $apollo );
				?>
			</div>

			<!-- EventTop Tag Colors -->
			<div class="section-title"><?php esc_html_e( 'EventTop Tag Colors', 'apollo-admin' ); ?></div>
			<div class="form-grid cols-4">
				<?php
				apollo_admin_color_field( __( 'Cancelled Tag', 'apollo-admin' ), 'tag_cancelled', '#ef4444', $apollo );
				apollo_admin_color_field( __( 'Sold Out Tag', 'apollo-admin' ), 'tag_sold_out', '#ef4444', $apollo );
				apollo_admin_color_field( __( 'Postponed Tag', 'apollo-admin' ), 'tag_postponed', '#eab308', $apollo );
				apollo_admin_color_field( __( 'Sold Out Soon', 'apollo-admin' ), 'tag_sold_soon', '#f97316', $apollo );
				apollo_admin_color_field( __( 'Rescheduled Tag', 'apollo-admin' ), 'tag_rescheduled', '#3b82f6', $apollo );
				apollo_admin_color_field( __( 'Featured Tag', 'apollo-admin' ), 'tag_featured', 'FF9820', $apollo );
				apollo_admin_color_field( __( 'Completed Tag', 'apollo-admin' ), 'tag_completed', '#71717a', $apollo );
				apollo_admin_color_field( __( 'Live Progress', 'apollo-admin' ), 'tag_live', '#22c55e', $apollo );
				?>
			</div>

			<!-- EventCard Styles -->
			<div class="section-title"><?php esc_html_e( 'EventCard Styles', 'apollo-admin' ); ?></div>
			<div class="form-grid cols-3">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Section Title Font Size', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[evt_theme_section_font_size]">
						<?php foreach ( array( '10px', '11px', '12px', '13px', '14px', '16px', '18px', '20px' ) as $sz ) : ?>
							<option value="<?php echo esc_attr( $sz ); ?>" <?php selected( $apollo['evt_theme_section_font_size'] ?? '13px', $sz ); ?>><?php echo esc_html( $sz ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
				apollo_admin_color_field( __( 'General Font Color', 'apollo-admin' ), 'card_font', '#121214', $apollo );
				apollo_admin_color_field( __( 'Card BG Color', 'apollo-admin' ), 'card_bg', '#ffffff', $apollo );
				apollo_admin_color_field( __( 'Inner Section BG', 'apollo-admin' ), 'card_inner_bg', '#f8f8f9', $apollo );
				apollo_admin_color_field( __( 'Directions Field', 'apollo-admin' ), 'card_directions_field', '#e4e4e7', $apollo );
				apollo_admin_color_field( __( 'Directions Button', 'apollo-admin' ), 'card_directions_btn', '#121214', $apollo );
				?>
			</div>

			<!-- Button Colors -->
			<div class="section-title"><?php esc_html_e( 'Button Colors', 'apollo-admin' ); ?></div>
			<div class="form-grid cols-3">
				<?php
				apollo_admin_color_field( __( 'Event Detail Link', 'apollo-admin' ), 'btn_detail', 'FF9820', $apollo );
				apollo_admin_color_field( __( 'Primary Button', 'apollo-admin' ), 'btn_primary', '#121214', $apollo );
				apollo_admin_color_field( __( 'Secondary Button', 'apollo-admin' ), 'btn_secondary', '#f4f4f5', $apollo );
				apollo_admin_color_field( __( 'Close Card Button', 'apollo-admin' ), 'btn_close', '#71717a', $apollo );
				apollo_admin_color_field( __( 'Lightbox Close', 'apollo-admin' ), 'btn_lightbox_close', '#ffffff', $apollo );
				apollo_admin_color_field( __( 'Repeat Instances', 'apollo-admin' ), 'btn_repeat', '#3b82f6', $apollo );
				?>
			</div>

			<!-- Live Now Calendar Styles -->
			<div class="section-title"><?php esc_html_e( 'Live Now Calendar Styles', 'apollo-admin' ); ?></div>
			<div class="form-grid cols-3">
				<?php
				apollo_admin_color_field( __( 'Happening Now Title', 'apollo-admin' ), 'live_title', '#22c55e', $apollo );
				apollo_admin_color_field( __( 'Coming Up BG', 'apollo-admin' ), 'live_coming_bg', '#f8f8f9', $apollo );
				apollo_admin_color_field( __( 'Coming Up Text', 'apollo-admin' ), 'live_coming_text', '#71717a', $apollo );
				apollo_admin_color_field( __( 'Coming Up Counter', 'apollo-admin' ), 'live_counter', 'FF9820', $apollo );
				apollo_admin_color_field( __( 'No Current Events', 'apollo-admin' ), 'live_no_events', '#d4d4d8', $apollo );
				?>
			</div>

		</div>
	</div>
</div>