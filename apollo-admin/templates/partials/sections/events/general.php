<?php

/**
 * Events Section — General Settings
 *
 * Page ID: page-evt-general
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-evt-general">
	<div class="panel">
		<div class="panel-header"><i class="ri-compasses-2-line"></i> <?php esc_html_e( 'General Event Settings', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[evt_hide_calendars]" value="1" <?php checked( $apollo['evt_hide_calendars'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Hide calendars from front-end', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[evt_remove_meta]" value="1" <?php checked( $apollo['evt_remove_meta'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Remove eventon generator meta data from website header', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[evt_enable_rtl]" value="1" <?php checked( $apollo['evt_enable_rtl'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable RTL (right-to-left) for all calendars', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[evt_hide_shortcode_btn]" value="1" <?php checked( $apollo['evt_hide_shortcode_btn'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Hide add shortcode generator button from wp-admin', 'apollo-admin' ); ?></span></div>
			</div>
		</div>
	</div>
</div>