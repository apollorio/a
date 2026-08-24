<?php
/**
 * Identity Section — Users (Profile & Discovery)
 *
 * Page ID: page-id-users
 * Sections: Core User Settings, Profile Settings, Roles & Capabilities
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-id-users">
	<div class="panel">
		<div class="panel-header"><i class="ri-contacts-book-fill"></i> <?php esc_html_e( 'Profile & Discovery', 'apollo-admin' ); ?> <span class="badge">apollo-users</span></div>
		<div class="panel-body">

			<!-- Core User Settings -->
			<div class="section-title"><?php esc_html_e( 'Core User Settings', 'apollo-admin' ); ?></div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[users_enable_radar]" value="1" <?php checked( $apollo['users_enable_radar'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Radar', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable user radar / nearby discovery feature', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[users_enable_matchmaking]" value="1" <?php checked( $apollo['users_enable_matchmaking'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Matchmaking', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable matchmaking algorithm based on music preferences', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="form-grid" style="margin-top:12px">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Default WP Role', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[users_default_role]">
						<option value="subscriber" <?php selected( $apollo['users_default_role'] ?? 'subscriber', 'subscriber' ); ?>><?php esc_html_e( 'Subscriber', 'apollo-admin' ); ?></option>
						<option value="apollo_member" <?php selected( $apollo['users_default_role'] ?? 'subscriber', 'apollo_member' ); ?>><?php esc_html_e( 'Apollo Member', 'apollo-admin' ); ?></option>
						<option value="contributor" <?php selected( $apollo['users_default_role'] ?? 'subscriber', 'contributor' ); ?>><?php esc_html_e( 'Contributor', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>

			<!-- Profile Settings -->
			<div class="section-title"><?php esc_html_e( 'Profile Settings', 'apollo-admin' ); ?> 🔜</div>

			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Cover Max Width (px)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[users_cover_width]" value="<?php echo esc_attr( $apollo['users_cover_width'] ?? 1200 ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Cover Max Height (px)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[users_cover_height]" value="<?php echo esc_attr( $apollo['users_cover_height'] ?? 400 ); ?>"></div>
			</div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[users_online_status]" value="1" <?php checked( $apollo['users_online_status'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show Online Status', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Display online/offline indicator on profiles', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[users_profile_types]" value="1" <?php checked( $apollo['users_profile_types'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Profile Types', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable profile type labels (DJ, Producer, Promoter, etc.)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[users_hover_cards]" value="1" <?php checked( $apollo['users_hover_cards'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Hover Cards', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Show mini-profile on username hover (async JSON fetch)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[users_restrict_dashboard]" value="1" <?php checked( $apollo['users_restrict_dashboard'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Restrict Dashboard Access', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Block subscribers from accessing /wp-admin/', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[users_show_directory]" value="1" <?php checked( $apollo['users_show_directory'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show in Directory', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Show users in public directory by default', 'apollo-admin' ); ?></span></div>
			</div>

			<!-- Roles & Capabilities -->
			<div class="section-title"><?php esc_html_e( 'Roles & Capabilities', 'apollo-admin' ); ?> 🔜</div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[users_force_cpt_caps]" value="1" <?php checked( $apollo['users_force_cpt_caps'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Force CPT Capabilities', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Force CPTs to use own capabilities (isolate permissions)', 'apollo-admin' ); ?></span></div>
			</div>

			<div style="margin-top:12px">
				<button class="btn btn-outline" type="button" title="<?php esc_attr_e( 'Export Roles (JSON)', 'apollo-admin' ); ?>"><i class="ri-download-line"></i></button>
				<button class="btn btn-outline" type="button" title="<?php esc_attr_e( 'Import Roles', 'apollo-admin' ); ?>"><i class="ri-upload-line"></i></button>
			</div>

		</div>
	</div>
</div>