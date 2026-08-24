<?php

/**
 * Email Section — Settings (Triggers + SMTP)
 *
 * Page ID: page-email-settings
 *
 * @package Apollo\Admin
 */

if (! defined('ABSPATH')) {
    exit;
}

?>
<div class="page" id="page-email-settings">
    <div class="panel">
        <div class="panel-header"><i class="ri-compasses-2-line"></i> <?php esc_html_e('Email Delivery Configuration', 'apollo-admin'); ?></div>
        <div class="panel-body">

            <div class="section-title"><?php esc_html_e('Trigger Settings — Who Gets Emails and When', 'apollo-admin'); ?></div>

            <div class="toggle-row">
                <label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[email_trigger_verification]" value="1" <?php checked($apollo['email_trigger_verification'] ?? true); ?>><span class="switch-track"></span></label>
                <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('User Account Confirmation', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Send verification email when a new user registers', 'apollo-admin'); ?></span></div>
            </div>
            <div class="toggle-row">
                <label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[email_trigger_welcome]" value="1" <?php checked($apollo['email_trigger_welcome'] ?? true); ?>><span class="switch-track"></span></label>
                <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Welcome Email', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Send welcome email after successful verification', 'apollo-admin'); ?></span></div>
            </div>
            <div class="toggle-row">
                <label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[email_trigger_password]" value="1" <?php checked($apollo['email_trigger_password'] ?? true); ?>><span class="switch-track"></span></label>
                <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Password Reset', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Send password reset instructions when requested', 'apollo-admin'); ?></span></div>
            </div>
            <div class="toggle-row">
                <label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[email_trigger_digest]" value="1" <?php checked($apollo['email_trigger_digest'] ?? true); ?>><span class="switch-track"></span></label>
                <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Weekly Social Digest', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Summary of social activity, new followers, and messages', 'apollo-admin'); ?></span></div>
            </div>
            <div class="toggle-row">
                <label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[email_trigger_event_reminder]" value="1" <?php checked($apollo['email_trigger_event_reminder'] ?? true); ?>><span class="switch-track"></span></label>
                <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Event Reminder', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Notify users about upcoming events they\'re interested in', 'apollo-admin'); ?></span></div>
            </div>
            <div class="toggle-row">
                <label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[email_trigger_new_event]" value="1" <?php checked($apollo['email_trigger_new_event'] ?? false); ?>><span class="switch-track"></span></label>
                <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('New Event Notification', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Inform users when events matching their sound preferences are created', 'apollo-admin'); ?></span></div>
            </div>
            <div class="toggle-row">
                <label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[email_trigger_chat]" value="1" <?php checked($apollo['email_trigger_chat'] ?? false); ?>><span class="switch-track"></span></label>
                <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Chat Message Notification', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Email notification for unread DMs (after 1 hour delay)', 'apollo-admin'); ?></span></div>
            </div>

            <div class="section-title"><?php esc_html_e('SMTP Configuration', 'apollo-admin'); ?></div>

            <div class="form-grid">
                <div class="field"><label class="field-label"><?php esc_html_e('From Name', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[email_from_name]" value="<?php echo esc_attr( $B::get( 'email_from_name', 'Apollo Rio' ) ); ?>"></div>
                <div class="field"><label class="field-label"><?php esc_html_e('From Email', 'apollo-admin'); ?></label><input type="email" class="apollo-input input" name="apollo[email_from_email]" value="<?php echo esc_attr( $B::get( 'email_from_email', 'noreply@apollo.rio.br' ) ); ?>"></div>
                <div class="field"><label class="field-label"><?php esc_html_e('SMTP Host', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[email_smtp_host]" value="<?php echo esc_attr( $B::get( 'email_smtp_host', 'smtp.sendgrid.net' ) ); ?>"></div>
                <div class="field"><label class="field-label"><?php esc_html_e('SMTP Port', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[email_smtp_port]" value="<?php echo esc_attr( $B::get( 'email_smtp_port', 587 ) ); ?>"></div>
                <div class="field"><label class="field-label"><?php esc_html_e('SMTP Username', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[email_smtp_user]" value="<?php echo esc_attr( $B::get( 'email_smtp_user', 'apikey' ) ); ?>"></div>
                <div class="field"><label class="field-label"><?php esc_html_e('SMTP Password', 'apollo-admin'); ?></label><input type="password" class="apollo-input input" name="apollo[email_smtp_pass]" value="<?php echo ! empty($apollo['email_smtp_pass']) ? '••••••••' : ''; ?>" autocomplete="new-password"></div>
            </div>

            <div style="margin-top:16px">
                <button class="btn btn-outline" type="button" id="apollo-test-email-btn" title="<?php esc_attr_e('Send Test Email', 'apollo-admin'); ?>"><i class="ri-test-tube-line"></i> <span><?php esc_html_e('Send Test Email', 'apollo-admin'); ?></span></button>
            </div>
            <script>
                (function() {
                    var btn = document.getElementById('apollo-test-email-btn');
                    if (!btn) return;
                    btn.addEventListener('click', function() {
                        var span = btn.querySelector('span');
                        var origText = span.textContent;
                        span.textContent = '<?php echo esc_js(__('Enviando...', 'apollo-admin')); ?>';
                        btn.disabled = true;
                        fetch('<?php echo esc_url(rest_url('apollo/v1/email/test')); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>'
                                },
                                body: JSON.stringify({
                                    to: '<?php echo esc_js(wp_get_current_user()->user_email); ?>'
                                })
                            })
                            .then(function(r) {
                                return r.json();
                            })
                            .then(function(data) {
                                if (data.success || (data.data && data.data.success)) {
                                    span.textContent = '<?php echo esc_js(__('✓ Enviado!', 'apollo-admin')); ?>';
                                } else {
                                    span.textContent = '<?php echo esc_js(__('✗ Falhou', 'apollo-admin')); ?>';
                                }
                                setTimeout(function() {
                                    span.textContent = origText;
                                    btn.disabled = false;
                                }, 3000);
                            })
                            .catch(function() {
                                span.textContent = '<?php echo esc_js(__('✗ Erro de rede', 'apollo-admin')); ?>';
                                setTimeout(function() {
                                    span.textContent = origText;
                                    btn.disabled = false;
                                }, 3000);
                            });
                    });
                })();
            </script>

        </div>
    </div>
</div>