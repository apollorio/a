<?php

/**
 * Social Section — Chat & Messaging
 *
 * Page ID: page-soc-chat
 * Core Chat, Features, Attachment Limits
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-soc-chat">
	<div class="panel">
		<div class="panel-header"><i class="ri-message-3-fill"></i> <?php esc_html_e( 'Chat & Messaging', 'apollo-admin' ); ?> <span class="badge">apollo-chat</span></div>
		<div class="panel-body">

			<div class="section-title"><?php esc_html_e( 'Core Chat', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[chat_dm]" value="1" <?php checked( $apollo['chat_dm'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Direct Messages', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow private 1-on-1 messaging between users', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[chat_group]" value="1" <?php checked( $apollo['chat_group'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Group Chat', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow multi-user group conversations', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:12px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Message Length', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[chat_max_length]" value="<?php echo esc_attr( $apollo['chat_max_length'] ?? 2000 ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Group Members', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[chat_max_members]" value="<?php echo esc_attr( $apollo['chat_max_members'] ?? 50 ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Polling Interval (ms)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[chat_polling]" value="<?php echo esc_attr( $apollo['chat_polling'] ?? 5000 ); ?>"><span class="field-hint"><?php esc_html_e( 'How often to check for new messages', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Features', 'apollo-admin' ); ?> 🔜</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[chat_attachments]" value="1" <?php checked( $apollo['chat_attachments'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Attachments', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow file attachments in chat messages', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[chat_voice]" value="1" <?php checked( $apollo['chat_voice'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Voice Messages', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow voice message recording in chat', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[chat_reactions]" value="1" <?php checked( $apollo['chat_reactions'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Reactions', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow emoji reactions on individual messages', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[chat_read_receipts]" value="1" <?php checked( $apollo['chat_read_receipts'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Read Receipts', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Show read/delivered receipts on messages', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[chat_typing]" value="1" <?php checked( $apollo['chat_typing'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Typing Indicator', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Show "user is typing..." indicator', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Attachment Limits', 'apollo-admin' ); ?> 🔜</div>
			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Attachment Size (MB)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[chat_max_attach_size]" value="<?php echo esc_attr( $apollo['chat_max_attach_size'] ?? 5 ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Allowed Types', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[chat_allowed_types]" value="<?php echo esc_attr( $apollo['chat_allowed_types'] ?? 'jpg,png,webp,pdf,mp3' ); ?>"><span class="field-hint"><?php esc_html_e( 'Comma-separated file extensions', 'apollo-admin' ); ?></span></div>
			</div>

		</div>
	</div>
</div>