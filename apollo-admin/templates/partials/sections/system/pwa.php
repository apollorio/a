<?php

/**
 * System Section — Progressive Web App
 *
 * Page ID: page-sys-pwa
 * App Identity, Service Worker settings
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-sys-pwa">
	<div class="panel">
		<div class="panel-header"><i class="ri-device-line"></i> <?php esc_html_e( 'Progressive Web App', 'apollo-admin' ); ?> <span class="badge">apollo-pwa</span></div>
		<div class="panel-body">

			<div class="section-title"><?php esc_html_e( 'App Identity', 'apollo-admin' ); ?></div>
			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'App Name', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[pwa_name]" value="<?php echo esc_attr( $apollo['pwa_name'] ?? 'Apollo Rio' ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Short Name', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[pwa_short_name]" value="<?php echo esc_attr( $apollo['pwa_short_name'] ?? 'Apollo' ); ?>"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Theme Color', 'apollo-admin' ); ?></label>
					<div class="color-pick">
						<input type="color" class="color-swatch" value="<?php echo esc_attr( $apollo['pwa_theme_color'] ?? '#6366f1' ); ?>">
						<input type="text" class="apollo-input color-hex" name="apollo[pwa_theme_color]" value="<?php echo esc_attr( $apollo['pwa_theme_color'] ?? '#6366f1' ); ?>">
					</div>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Background Color', 'apollo-admin' ); ?></label>
					<div class="color-pick">
						<input type="color" class="color-swatch" value="<?php echo esc_attr( $apollo['pwa_bg_color'] ?? '#09090b' ); ?>">
						<input type="text" class="apollo-input color-hex" name="apollo[pwa_bg_color]" value="<?php echo esc_attr( $apollo['pwa_bg_color'] ?? '#09090b' ); ?>">
					</div>
				</div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Service Worker', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[pwa_sw]" value="1" <?php checked( $apollo['pwa_sw'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Service Worker', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable PWA service worker for offline/caching support', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:12px">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Display Mode', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[pwa_display]">
						<option value="standalone" <?php selected( $apollo['pwa_display'] ?? 'standalone', 'standalone' ); ?>><?php esc_html_e( 'Standalone', 'apollo-admin' ); ?></option>
						<option value="fullscreen" <?php selected( $apollo['pwa_display'] ?? 'standalone', 'fullscreen' ); ?>><?php esc_html_e( 'Fullscreen', 'apollo-admin' ); ?></option>
						<option value="minimal-ui" <?php selected( $apollo['pwa_display'] ?? 'standalone', 'minimal-ui' ); ?>><?php esc_html_e( 'Minimal UI', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Orientation', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[pwa_orientation]">
						<option value="portrait" <?php selected( $apollo['pwa_orientation'] ?? 'portrait', 'portrait' ); ?>><?php esc_html_e( 'Portrait', 'apollo-admin' ); ?></option>
						<option value="landscape" <?php selected( $apollo['pwa_orientation'] ?? 'portrait', 'landscape' ); ?>><?php esc_html_e( 'Landscape', 'apollo-admin' ); ?></option>
						<option value="any" <?php selected( $apollo['pwa_orientation'] ?? 'portrait', 'any' ); ?>><?php esc_html_e( 'Any', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Start URL', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[pwa_start_url]" value="<?php echo esc_attr( $apollo['pwa_start_url'] ?? '/' ); ?>"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Offline Page', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[pwa_offline]">
						<option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option>
						<option value="/offline" <?php selected( $apollo['pwa_offline'] ?? '', '/offline' ); ?>>/offline</option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Cache Strategy', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[pwa_cache_strategy]">
						<option value="cache-first" <?php selected( $apollo['pwa_cache_strategy'] ?? 'network-first', 'cache-first' ); ?>><?php esc_html_e( 'Cache First', 'apollo-admin' ); ?></option>
						<option value="network-first" <?php selected( $apollo['pwa_cache_strategy'] ?? 'network-first', 'network-first' ); ?>><?php esc_html_e( 'Network First', 'apollo-admin' ); ?></option>
						<option value="stale-revalidate" <?php selected( $apollo['pwa_cache_strategy'] ?? 'network-first', 'stale-revalidate' ); ?>><?php esc_html_e( 'Stale While Revalidate', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>

		</div>
	</div>
</div>