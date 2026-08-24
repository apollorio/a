<?php
/**
 * Admin Section — Users & Roles Management
 *
 * Page ID: page-admin-users
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-admin-users">
	<div class="panel">
		<div class="panel-header"><i class="ri-group-2-fill"></i> <?php esc_html_e( 'Users & Roles Management', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="form-grid">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Default User Role', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[default_role]">
						<option value="subscriber"><?php esc_html_e( 'Subscriber', 'apollo-admin' ); ?></option>
						<option value="apollo_member" selected><?php esc_html_e( 'Apollo Member', 'apollo-admin' ); ?></option>
						<option value="contributor"><?php esc_html_e( 'Contributor', 'apollo-admin' ); ?></option>
						<option value="dj_artist"><?php esc_html_e( 'DJ / Artist', 'apollo-admin' ); ?></option>
						<option value="promoter"><?php esc_html_e( 'Promoter', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Registration Mode', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[registration_mode]">
						<option value="open"><?php esc_html_e( 'Open Registration', 'apollo-admin' ); ?></option>
						<option value="email_verify" selected><?php esc_html_e( 'Email Verification Required', 'apollo-admin' ); ?></option>
						<option value="admin_approval"><?php esc_html_e( 'Admin Approval Required', 'apollo-admin' ); ?></option>
						<option value="invite_only"><?php esc_html_e( 'Invite Only', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Max Login Attempts', 'apollo-admin' ); ?></label>
					<input type="number" class="apollo-input input" name="apollo[max_login_attempts]" value="5">
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Lockout Duration (min)', 'apollo-admin' ); ?></label>
					<input type="number" class="apollo-input input" name="apollo[lockout_duration]" value="15">
				</div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Role Capabilities', 'apollo-admin' ); ?></div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[cap_create_events]" value="1" checked><span class="switch-track"></span></label>
				<div class="toggle-text">
					<span class="toggle-title"><?php esc_html_e( 'Allow users to create events', 'apollo-admin' ); ?></span>
					<span class="toggle-desc"><?php esc_html_e( 'Members with DJ/Artist or Promoter role can submit events', 'apollo-admin' ); ?></span>
				</div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[cap_social_post]" value="1" checked><span class="switch-track"></span></label>
				<div class="toggle-text">
					<span class="toggle-title"><?php esc_html_e( 'Allow users to post in social feed', 'apollo-admin' ); ?></span>
					<span class="toggle-desc"><?php esc_html_e( 'All registered users can create social posts (280 char limit)', 'apollo-admin' ); ?></span>
				</div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[cap_classifieds]" value="1"><span class="switch-track"></span></label>
				<div class="toggle-text">
					<span class="toggle-title"><?php esc_html_e( 'Allow users to create classified listings', 'apollo-admin' ); ?></span>
					<span class="toggle-desc"><?php esc_html_e( 'Ticket resale, accommodation, and rideshare listings', 'apollo-admin' ); ?></span>
				</div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[cap_direct_messages]" value="1" checked><span class="switch-track"></span></label>
				<div class="toggle-text">
					<span class="toggle-title"><?php esc_html_e( 'Allow users to send direct messages', 'apollo-admin' ); ?></span>
					<span class="toggle-desc"><?php esc_html_e( 'Private messaging via Apollo Chat', 'apollo-admin' ); ?></span>
				</div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[cap_require_profile]" value="1"><span class="switch-track"></span></label>
				<div class="toggle-text">
					<span class="toggle-title"><?php esc_html_e( 'Require profile completion before posting', 'apollo-admin' ); ?></span>
					<span class="toggle-desc"><?php esc_html_e( 'Users must reach 60% profile completion before social features unlock', 'apollo-admin' ); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>