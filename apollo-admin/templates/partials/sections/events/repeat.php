<?php
/**
 * Events Section — Repeat Settings
 *
 * Page ID: page-evt-repeat
 * Single toggle for repeat event behavior
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-evt-repeat">
	<div class="panel">
		<div class="panel-header"><i class="ri-repeat-line"></i> <?php esc_html_e( 'Repeat Settings', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[repeat_load_current]" value="1" <?php checked( $apollo['repeat_load_current'] ?? true ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Load current repeat event by default on single event page', 'apollo-admin' ); ?></span></div></div>
		</div>
	</div>
</div>