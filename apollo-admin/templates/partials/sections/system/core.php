<?php

/**
 * System Section — Core System Settings
 *
 * Page ID: page-sys-core
 * System settings, Performance, CDN & Assets
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-sys-core">
	<div class="panel">
		<div class="panel-header"><i class="ri-cpu-line"></i> <?php esc_html_e( 'Core System', 'apollo-admin' ); ?> <span class="badge">apollo-core</span></div>
		<div class="panel-body">

			<div class="section-title"><?php esc_html_e( 'System Settings', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_debug]" value="1" <?php checked( $apollo['core_debug'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Debug Mode', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable debug mode for development and troubleshooting', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_cdn]" value="1" <?php checked( $apollo['core_cdn'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'CDN Enabled', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable Apollo CDN loading for assets', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_cleanup]" value="1" <?php checked( $apollo['core_cleanup'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Cleanup on Uninstall', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Delete all Apollo data when uninstalling plugins', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Performance', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_cache]" value="1" <?php checked( $apollo['core_cache'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Cache', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable transient caching layer for queries', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:8px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Cache TTL (seconds)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[core_cache_ttl]" value="<?php echo esc_attr( $apollo['core_cache_ttl'] ?? 300 ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'API Rate Limit', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[core_rate_limit]" value="<?php echo esc_attr( $apollo['core_rate_limit'] ?? 100 ); ?>"><span class="field-hint"><?php esc_html_e( 'REST requests per minute per user', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_fallback]" value="1" <?php checked( $apollo['core_fallback'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Fallback Bridges', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Auto-register CPTs when owner plugin inactive', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'CDN & Assets', 'apollo-admin' ); ?></div>
			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'CDN Base URL', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[core_cdn_url]" value="<?php echo esc_attr( $B::get( 'core_cdn_url', 'https://cdn.apollo.rio.br' ) ); ?>"></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_minify_js]" value="1" <?php checked( $apollo['core_minify_js'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Minify JavaScript', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Minify JS assets served by CDN', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_minify_css]" value="1" <?php checked( $apollo['core_minify_css'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Minify CSS', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Minify CSS assets served by CDN', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_lazy_load]" value="1" <?php checked( $apollo['core_lazy_load'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Lazy Load', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Lazy-load images and iframes', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_webp]" value="1" <?php checked( $apollo['core_webp'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable WebP Conversion', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Auto-convert uploads to WebP format', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:8px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'WebP Quality', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[core_webp_quality]" value="<?php echo esc_attr( $apollo['core_webp_quality'] ?? 82 ); ?>"><span class="field-hint"><?php esc_html_e( '0–100 compression quality', 'apollo-admin' ); ?></span></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Upload (MB)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[core_max_upload]" value="<?php echo esc_attr( $apollo['core_max_upload'] ?? 10 ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Allowed File Types', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[core_file_types]" value="<?php echo esc_attr( $apollo['core_file_types'] ?? 'jpg,png,webp,pdf' ); ?>"></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[core_malware_scan]" value="1" <?php checked( $apollo['core_malware_scan'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Scan Uploads for Malware', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Scan file uploads for malware signatures', 'apollo-admin' ); ?></span></div>
			</div>

		</div>
	</div>
</div>
