<?php
/**
 * Events Section — Calendar Settings
 *
 * Page ID: page-evt-calendar
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-evt-calendar">
	<div class="panel">
		<div class="panel-header"><i class="ri-calendar-2-line"></i> <?php esc_html_e( 'Calendar Settings', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="section-title"><?php esc_html_e( 'General Calendar', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[evt_hide_nav_arrows]" value="1" <?php checked( $apollo['evt_hide_nav_arrows'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Hide month navigation arrows', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[evt_align_arrows_right]" value="1" <?php checked( $apollo['evt_align_arrows_right'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Align month navigation arrows to right side', 'apollo-admin' ); ?></span></div></div>

			<div class="section-title"><?php esc_html_e( 'Featured Events', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[evt_override_featured_color]" value="1" <?php checked( $apollo['evt_override_featured_color'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Override featured event color', 'apollo-admin' ); ?></span></div></div>
		</div>
	</div>
</div>