<?php

/**
 * System Section — Templates & Shortcodes
 *
 * Page ID: page-sys-templates
 * 3 feed-tabs: Templates, Shortcodes, Dashboard
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-sys-templates">
	<div class="feed-tabs">
		<button class="feed-tab active" data-sub="tpl-templates" title="Templates"><i class="ri-pages-line"></i></button>
		<button class="feed-tab" data-sub="tpl-shortcodes" title="Shortcodes"><i class="ri-qr-scan-line"></i></button>
		<button class="feed-tab" data-sub="tpl-dashboard" title="Dashboard"><i class="ri-server-line"></i></button>
	</div>

	<!-- Template Engine -->
	<div class="sub-content visible" id="sub-tpl-templates">
		<div class="panel">
			<div class="panel-header"><i class="ri-pages-line"></i> <?php esc_html_e( 'Template Engine', 'apollo-admin' ); ?> <span class="badge">apollo-templates</span></div>
			<div class="panel-body">
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[tpl_blank_canvas]" value="1" <?php checked( $apollo['tpl_blank_canvas'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Blank Canvas', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable blank canvas template mode (zero theme)', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[tpl_override_archive]" value="1" <?php checked( $apollo['tpl_override_archive'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Override Archive', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Override theme archive templates with Apollo templates', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[tpl_wp_head]" value="1" <?php checked( $apollo['tpl_wp_head'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Include wp_head()', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Include wp_head() in canvas templates', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[tpl_wp_footer]" value="1" <?php checked( $apollo['tpl_wp_footer'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Include wp_footer()', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Include wp_footer() in canvas templates', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="field" style="margin-top:16px"><label class="field-label"><?php esc_html_e( 'Global Custom CSS', 'apollo-admin' ); ?></label><textarea class="apollo-textarea input" rows="6" name="apollo[tpl_custom_css]" placeholder="/* Custom CSS injection */"><?php echo esc_textarea( $apollo['tpl_custom_css'] ?? '' ); ?></textarea></div>
			</div>
		</div>
	</div>

	<!-- Shortcode Registry -->
	<div class="sub-content" id="sub-tpl-shortcodes">
		<div class="panel">
			<div class="panel-header"><i class="ri-qr-scan-line"></i> <?php esc_html_e( 'Shortcode Registry', 'apollo-admin' ); ?> <span class="badge">apollo-shortcodes</span></div>
			<div class="panel-body">
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sc_manager]" value="1" <?php checked( $apollo['sc_manager'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Manager', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable shortcode manager UI in wp-admin', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sc_newsletter]" value="1" <?php checked( $apollo['sc_newsletter'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Newsletter Shortcode', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable native newsletter subscription shortcode', 'apollo-admin' ); ?></span></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Dashboard Widgets -->
	<div class="sub-content" id="sub-tpl-dashboard">
		<div class="panel">
			<div class="panel-header"><i class="ri-server-line"></i> <?php esc_html_e( 'Dashboard Widgets', 'apollo-admin' ); ?> <span class="badge">apollo-dashboard</span></div>
			<div class="panel-body">
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[dash_quick_publish]" value="1" <?php checked( $apollo['dash_quick_publish'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Quick Publish', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Quick publish widget on user dashboard', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[dash_mod_queue]" value="1" <?php checked( $apollo['dash_mod_queue'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Mod Queue', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Show moderation queue widget for moderators', 'apollo-admin' ); ?></span></div>
				</div>
			</div>
		</div>
	</div>
</div>