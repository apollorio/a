<?php

/**
 * System Section — Analytics & Tracking
 *
 * Page ID: page-sys-stats
 * Core Tracking, Advanced, Privacy & Integration
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-sys-stats">
	<div class="panel">
		<div class="panel-header"><i class="ri-line-chart-line"></i> <?php esc_html_e( 'Analytics & Tracking', 'apollo-admin' ); ?> <span class="badge">apollo-statistics</span></div>
		<div class="panel-body">

			<div class="section-title"><?php esc_html_e( 'Core Tracking', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_logins]" value="1" <?php checked( $apollo['stats_logins'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Track Logins', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Track user login events', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_pageviews]" value="1" <?php checked( $apollo['stats_pageviews'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Track Pageviews', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Track page view events', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:8px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Retention (days)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[stats_retention]" value="<?php echo esc_attr( $apollo['stats_retention'] ?? 90 ); ?>"><span class="field-hint"><?php esc_html_e( 'Data retention period', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Advanced Tracking', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_downloads]" value="1" <?php checked( $apollo['stats_downloads'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Track Downloads', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_outbound]" value="1" <?php checked( $apollo['stats_outbound'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Track Outbound Links', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_forms]" value="1" <?php checked( $apollo['stats_forms'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Track Form Submissions', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_ecommerce]" value="1" <?php checked( $apollo['stats_ecommerce'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Track E-commerce Events', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Track add-to-cart and purchase events', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Privacy & Integration', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_anon_ip]" value="1" <?php checked( $apollo['stats_anon_ip'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Anonymize IP', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Strip last IP octet for GDPR compliance', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_bot_detect]" value="1" <?php checked( $apollo['stats_bot_detect'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Bot Detection', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Filter bot traffic from statistics', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_self_hosted]" value="1" <?php checked( $apollo['stats_self_hosted'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Self-Hosted Mode', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Privacy-first self-hosted analytics (no external services)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[stats_disable_frontend]" value="1" <?php checked( $apollo['stats_disable_frontend'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable Frontend Tracking', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Disable frontend scripts (use GTM instead)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:12px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'GA4 Measurement ID', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[stats_ga4]" value="<?php echo esc_attr( $apollo['stats_ga4'] ?? '' ); ?>" placeholder="G-XXXXXXXXXX"></div>
			</div>

		</div>
	</div>
</div>