<?php

/**
 * System Section — Security Hardening
 *
 * Page ID: page-sys-security
 * Core Security, Advanced Hardening
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-sys-security">
	<div class="panel">
		<div class="panel-header"><i class="ri-shield-keyhole-fill"></i> <?php esc_html_e( 'Security Hardening', 'apollo-admin' ); ?> <span class="badge">apollo-hardening</span></div>
		<div class="panel-body">

			<div class="section-title"><?php esc_html_e( 'Core Security', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_xmlrpc]" value="1" <?php checked( $apollo['sec_xmlrpc'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable XML-RPC', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Disable XML-RPC endpoint (prevents brute-force attacks)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_rest_auth]" value="1" <?php checked( $apollo['sec_rest_auth'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'REST API Auth Only', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Restrict REST API to authenticated users', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_cleanup_headers]" value="1" <?php checked( $apollo['sec_cleanup_headers'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Cleanup Headers', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Strip WP version & generator meta tags', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_lockout]" value="1" <?php checked( $apollo['sec_lockout'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Login Lockout', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable login lockout after failed attempts', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:8px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Lockout Attempts', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[sec_lockout_attempts]" value="<?php echo esc_attr( $B::get( 'sec_lockout_attempts', 5 ) ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Lockout Duration (min)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[sec_lockout_duration]" value="<?php echo esc_attr( $B::get( 'sec_lockout_duration', 15 ) ); ?>"></div>
			</div>
			<div class="field" style="margin-top:12px"><label class="field-label"><?php esc_html_e( 'IP Whitelist', 'apollo-admin' ); ?></label><textarea class="apollo-textarea input" rows="3" name="apollo[sec_ip_whitelist]" placeholder="<?php esc_attr_e( 'One IP per line...', 'apollo-admin' ); ?>"><?php echo esc_textarea( $apollo['sec_ip_whitelist'] ?? '' ); ?></textarea><span class="field-hint"><?php esc_html_e( 'IPs that bypass lockout', 'apollo-admin' ); ?></span></div>

			<div class="section-title"><?php esc_html_e( 'Advanced Hardening', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_file_edit]" value="1" <?php checked( $apollo['sec_file_edit'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable File Editing', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Disable theme/plugin editor (DISALLOW_FILE_EDIT)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_wc_gen]" value="1" <?php checked( $apollo['sec_wc_gen'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Remove WC Generator', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Remove WooCommerce generator tag from DOM', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_force_ssl]" value="1" <?php checked( $apollo['sec_force_ssl'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Force SSL', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Force HTTPS on all form submissions', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_https_filter]" value="1" <?php checked( $apollo['sec_https_filter'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Extra HTTPS URL Filter', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Rewrite mixed-content URLs in DOM output', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_disable_php]" value="1" <?php checked( $apollo['sec_disable_php'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable PHP Snippets', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Global kill-switch for arbitrary PHP code execution', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_hide_version]" value="1" <?php checked( $apollo['sec_hide_version'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Hide WP Version', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Remove WordPress version from source code', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sec_headers]" value="1" <?php checked( $apollo['sec_headers'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Security Headers', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Add X-Frame-Options, X-Content-Type-Options, Referrer-Policy', 'apollo-admin' ); ?></span></div>
			</div>

		</div>
	</div>
</div>