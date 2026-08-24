<?php
/**
 * Events Section — Single Event Page Settings
 *
 * Page ID: page-evt-single
 * 8 toggles + EventTop style select
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-evt-single">
	<div class="panel">
		<div class="panel-header"><i class="ri-pages-line"></i> <?php esc_html_e( 'Single Event Page Settings', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[single_disable_og]" value="1" <?php checked( $apollo['single_disable_og'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable auto-generated OG: meta data in single event header', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[single_sidebar]" value="1" <?php checked( $apollo['single_sidebar'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Create Single Events Page Sidebar', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[single_restrict_logged]" value="1" <?php checked( $apollo['single_restrict_logged'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Restrict single event pages to logged-in users only', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[single_disable_comments]" value="1" <?php checked( $apollo['single_disable_comments'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable comments section on single event template page', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[single_hide_title]" value="1" <?php checked( $apollo['single_hide_title'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Hide event title on single event page', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[single_show_month_year]" value="1" <?php checked( $apollo['single_show_month_year'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show month, year header on single event header', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[single_override_color]" value="1" <?php checked( $apollo['single_override_color'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Override event color with event type color', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[single_disable_ics]" value="1" <?php checked( $apollo['single_disable_ics'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable event link in ICS file', 'apollo-admin' ); ?></span></div></div>

			<div class="field" style="margin-top:16px">
				<label class="field-label"><?php esc_html_e( 'EventTop Style', 'apollo-admin' ); ?></label>
				<select class="select" name="apollo[single_eventtop_style]">
					<option value="immersive" <?php selected( $apollo['single_eventtop_style'] ?? 'colorful', 'immersive' ); ?>><?php esc_html_e( 'Immersive Flow', 'apollo-admin' ); ?></option>
					<option value="colorful" <?php selected( $apollo['single_eventtop_style'] ?? 'colorful', 'colorful' ); ?>><?php esc_html_e( 'Colorful', 'apollo-admin' ); ?></option>
					<option value="clean" <?php selected( $apollo['single_eventtop_style'] ?? 'colorful', 'clean' ); ?>><?php esc_html_e( 'Clean White', 'apollo-admin' ); ?></option>
				</select>
			</div>
		</div>
	</div>
</div>