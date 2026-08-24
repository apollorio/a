<?php

/**
 * Social Section — Push & Digest (Notifications)
 *
 * Page ID: page-soc-notif
 * Core Notification Settings, VAPID, Digest Schedule, RSS Push
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-soc-notif">
	<div class="panel">
		<div class="panel-header"><i class="ri-signal-tower-fill"></i> <?php esc_html_e( 'Push & Digest', 'apollo-admin' ); ?> <span class="badge">apollo-notif</span></div>
		<div class="panel-body">

		<div class="section-title"><?php esc_html_e( 'Core Notification Settings', 'apollo-admin' ); ?></div>
		<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[notif_push]" value="1" <?php checked( $B::get( 'notif_push', false ) ); ?>><span class="switch-track"></span></label>
			<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Push Notifications', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable web push notifications via service worker', 'apollo-admin' ); ?></span></div>
		</div>
		<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[notif_digest]" value="1" <?php checked( $B::get( 'notif_digest', true ) ); ?>><span class="switch-track"></span></label>
			<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Email Digest', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Send periodic email digest of notifications', 'apollo-admin' ); ?></span></div>
		</div>
			<div class="form-grid" style="margin-top:12px">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Digest Frequency', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[notif_digest_freq]">
						<option value="daily" <?php selected( $apollo['notif_digest_freq'] ?? 'weekly', 'daily' ); ?>><?php esc_html_e( 'Daily', 'apollo-admin' ); ?></option>
						<option value="weekly" <?php selected( $apollo['notif_digest_freq'] ?? 'weekly', 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'apollo-admin' ); ?></option>
						<option value="monthly" <?php selected( $apollo['notif_digest_freq'] ?? 'weekly', 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Web Push (VAPID)', 'apollo-admin' ); ?> 🔜</div>
		<div class="form-grid">
			<div class="field"><label class="field-label"><?php esc_html_e( 'VAPID Public Key', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[notif_vapid_public]" value="<?php echo esc_attr( $B::get( 'notif_vapid_public', '' ) ); ?>" placeholder="BNf1a..."><span class="field-hint"><?php esc_html_e( 'Web Push VAPID public key', 'apollo-admin' ); ?></span></div>
			<div class="field"><label class="field-label"><?php esc_html_e( 'VAPID Private Key', 'apollo-admin' ); ?></label><input type="password" class="apollo-input input" name="apollo[notif_vapid_private]" value="<?php echo esc_attr( $B::get( 'notif_vapid_private', '' ) ); ?>" placeholder="••••••••"></div>
		</div>

			<div class="section-title"><?php esc_html_e( 'Digest Schedule', 'apollo-admin' ); ?> 🔜</div>
			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Send Time', 'apollo-admin' ); ?></label><input type="time" class="input" name="apollo[notif_send_time]" value="<?php echo esc_attr( $apollo['notif_send_time'] ?? '09:00' ); ?>data-apollo-date-picker="true">"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Timezone', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[notif_timezone]">
						<option value="America/Sao_Paulo" <?php selected( $apollo['notif_timezone'] ?? 'America/Sao_Paulo', 'America/Sao_Paulo' ); ?>>America/Sao_Paulo</option>
						<option value="UTC" <?php selected( $apollo['notif_timezone'] ?? 'America/Sao_Paulo', 'UTC' ); ?>>UTC</option>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Items per Digest', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[notif_max_items]" value="<?php echo esc_attr( $apollo['notif_max_items'] ?? 10 ); ?>"></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'RSS Push', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[notif_rss_push]" value="1" <?php checked( $apollo['notif_rss_push'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable RSS Push', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Auto-push from RSS feed as notifications', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[notif_breaking]" value="1" <?php checked( $apollo['notif_breaking'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Breaking News', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( "Send immediately on publish (don't wait for digest)", 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:8px">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Top News Period', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[notif_top_period]">
						<option value="daily" <?php selected( $apollo['notif_top_period'] ?? 'daily', 'daily' ); ?>><?php esc_html_e( 'Daily', 'apollo-admin' ); ?></option>
						<option value="weekly" <?php selected( $apollo['notif_top_period'] ?? 'daily', 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>

		</div>
	</div>
</div>