<?php

/**
 * Social Section — Activity & Interactions
 *
 * Page ID: page-soc-activity
 * Core Social, Activity, Media & Uploads, GDPR
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-soc-activity">
	<div class="panel">
		<div class="panel-header"><i class="ri-global-line"></i> <?php esc_html_e( 'Activity & Interactions', 'apollo-admin' ); ?> <span class="badge">apollo-social</span></div>
		<div class="panel-body">

			<div class="section-title"><?php esc_html_e( 'Core Social Settings', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[soc_activity_stream]" value="1" <?php checked( $apollo['soc_activity_stream'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Activity Stream', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable the social activity feed on the platform', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[soc_follow]" value="1" <?php checked( $apollo['soc_follow'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Follow System', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow users to follow/block other users', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[soc_reactions]" value="1" <?php checked( $apollo['soc_reactions'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Reactions', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow reactions on activity posts', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Activity Settings', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[soc_private_network]" value="1" <?php checked( $apollo['soc_private_network'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Private Network', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Force redirect to login for all unauthenticated visitors', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[soc_mentions]" value="1" <?php checked( $apollo['soc_mentions'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable @Mentions', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable @mentions in activity posts and comments', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[soc_hashtags]" value="1" <?php checked( $apollo['soc_hashtags'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable #Hashtags', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable hashtag parsing and discovery in activity', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:12px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Activity Per Page', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[soc_per_page]" value="<?php echo esc_attr( $apollo['soc_per_page'] ?? 20 ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Moderation Report Threshold', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[soc_report_threshold]" value="<?php echo esc_attr( $apollo['soc_report_threshold'] ?? 3 ); ?>"><span class="field-hint"><?php esc_html_e( 'Reports needed to auto-suppress content', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Media & Uploads', 'apollo-admin' ); ?> 🔜</div>
			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Allowed Extensions', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[soc_allowed_ext]" value="<?php echo esc_attr( $apollo['soc_allowed_ext'] ?? 'jpg,png,webp,gif,mp4' ); ?>"><span class="field-hint"><?php esc_html_e( 'Comma-separated MIME types for community uploads', 'apollo-admin' ); ?></span></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Upload Size (MB)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[soc_max_upload]" value="<?php echo esc_attr( $apollo['soc_max_upload'] ?? 10 ); ?>"></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'GDPR & Privacy', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[soc_gdpr_cache]" value="1" <?php checked( $apollo['soc_gdpr_cache'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'GDPR Local Cache', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Download social images locally (WebP) instead of hotlinking to comply with GDPR', 'apollo-admin' ); ?></span></div>
			</div>

		</div>
	</div>
</div>