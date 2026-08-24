<?php

/**
 * Social Section — Groups
 *
 * Page ID: page-soc-groups
 * Core Groups + Group Features
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-soc-groups">
	<div class="panel">
		<div class="panel-header"><i class="ri-user-community-fill"></i> <?php esc_html_e( 'Groups', 'apollo-admin' ); ?> <span class="badge">apollo-groups</span></div>
		<div class="panel-body">

			<div class="section-title"><?php esc_html_e( 'Core Groups', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[grp_enable]" value="1" <?php checked( $apollo['grp_enable'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Groups', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable the groups system across the platform', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:12px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Groups per User', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[grp_max_per_user]" value="<?php echo esc_attr( $apollo['grp_max_per_user'] ?? 10 ); ?>"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Default Privacy', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[grp_privacy]">
						<option value="public" <?php selected( $apollo['grp_privacy'] ?? 'public', 'public' ); ?>><?php esc_html_e( 'Public', 'apollo-admin' ); ?></option>
						<option value="private" <?php selected( $apollo['grp_privacy'] ?? 'public', 'private' ); ?>><?php esc_html_e( 'Private', 'apollo-admin' ); ?></option>
						<option value="hidden" <?php selected( $apollo['grp_privacy'] ?? 'public', 'hidden' ); ?>><?php esc_html_e( 'Hidden', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Group Features', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[grp_cover]" value="1" <?php checked( $apollo['grp_cover'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Group Cover', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow group cover images upload', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[grp_types]" value="1" <?php checked( $apollo['grp_types'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Group Types', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable group type taxonomy (Music, Community, Industry, etc.)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[grp_events]" value="1" <?php checked( $apollo['grp_events'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Group Events', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow events to be created within groups', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[grp_media]" value="1" <?php checked( $apollo['grp_media'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Group Media', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow media albums in groups', 'apollo-admin' ); ?></span></div>
			</div>

		</div>
	</div>
</div>