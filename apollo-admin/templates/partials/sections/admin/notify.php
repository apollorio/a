<?php
/**
 * Admin Section — Notifications (Internal Push + Email Blast)
 *
 * Page ID: page-admin-notify
 * Feed tabs: Internal Push | Email Blast
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-admin-notify">
	<div class="feed-tabs">
		<button class="feed-tab active" data-sub="ntf-internal" title="<?php esc_attr_e( 'Internal Push', 'apollo-admin' ); ?>"><i class="ri-signal-tower-fill"></i></button>
		<button class="feed-tab" data-sub="ntf-email" title="<?php esc_attr_e( 'Email Blast', 'apollo-admin' ); ?>"><i class="ri-mail-send-line"></i></button>
	</div>

	<!-- Internal Notification -->
	<div class="sub-content visible" id="sub-ntf-internal">
		<div class="panel">
			<div class="panel-header"><i class="ri-broadcast-fill"></i> <?php esc_html_e( 'Send Internal Notification', 'apollo-admin' ); ?></div>
			<div class="panel-body">
				<div class="form-grid cols-1">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Title', 'apollo-admin' ); ?> <span class="required">*</span></label>
						<input type="text" class="apollo-input input" name="notif[title]" placeholder="<?php esc_attr_e( 'Notification title', 'apollo-admin' ); ?>">
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Message', 'apollo-admin' ); ?> <span class="required">*</span></label>
						<textarea class="apollo-textarea input" name="notif[message]" rows="3" placeholder="<?php esc_attr_e( 'What do you want to tell users?', 'apollo-admin' ); ?>"></textarea>
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Target Audience', 'apollo-admin' ); ?></label>
						<select class="select" name="notif[audience]">
							<option value="all"><?php esc_html_e( 'All Users', 'apollo-admin' ); ?></option>
							<option value="active"><?php esc_html_e( 'Active Members Only', 'apollo-admin' ); ?></option>
							<option value="vip"><?php esc_html_e( 'VIP+ Tier', 'apollo-admin' ); ?></option>
							<option value="dj"><?php esc_html_e( 'DJ/Artist Role', 'apollo-admin' ); ?></option>
							<option value="promoter"><?php esc_html_e( 'Promoter Role', 'apollo-admin' ); ?></option>
						</select>
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Link (optional)', 'apollo-admin' ); ?></label>
						<input type="url" class="apollo-input input" name="notif[link]" placeholder="https://apollo.rio.br/...">
					</div>
					<div><button class="btn btn-orange" type="button" id="send-push-notification" title="<?php esc_attr_e( 'Push Notification', 'apollo-admin' ); ?>"><i class="ri-send-plane-2-line"></i></button></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Email Blast -->
	<div class="sub-content" id="sub-ntf-email">
		<div class="panel">
			<div class="panel-header"><i class="ri-mail-send-line"></i> <?php esc_html_e( 'Quick Email Blast', 'apollo-admin' ); ?></div>
			<div class="panel-body">
				<div class="form-grid cols-1">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Subject', 'apollo-admin' ); ?></label>
						<input type="text" class="apollo-input input" name="blast[subject]" placeholder="<?php esc_attr_e( 'Email subject line', 'apollo-admin' ); ?>">
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Body', 'apollo-admin' ); ?></label>
						<textarea class="apollo-textarea input" name="blast[body]" rows="6" placeholder="<?php esc_attr_e( 'Email content (HTML supported)...', 'apollo-admin' ); ?>"></textarea>
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Send To', 'apollo-admin' ); ?></label>
						<select class="select" name="blast[send_to]">
							<option value="all"><?php esc_html_e( 'All Subscribers', 'apollo-admin' ); ?></option>
							<option value="active"><?php esc_html_e( 'Active Members', 'apollo-admin' ); ?></option>
							<option value="attendees"><?php esc_html_e( 'Event Attendees (Last 30 days)', 'apollo-admin' ); ?></option>
						</select>
					</div>
					<div style="display:flex;gap:10px">
						<button class="btn btn-outline" type="button" title="<?php esc_attr_e( 'Preview', 'apollo-admin' ); ?>"><i class="ri-eye-line"></i></button>
						<button class="btn btn-outline" type="button" title="<?php esc_attr_e( 'Send Test', 'apollo-admin' ); ?>"><i class="ri-test-tube-line"></i></button>
						<button class="btn btn-orange" type="button" title="<?php esc_attr_e( 'Send Now', 'apollo-admin' ); ?>"><i class="ri-send-plane-line"></i></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>