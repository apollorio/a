<?php
/**
 * Apollo Remind — Admin Dashboard
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

global $wpdb;
$r_table = $wpdb->prefix . 'apollo_reminders';
$t_table = $wpdb->prefix . 'apollo_remind_telegram';
$p_table = $wpdb->prefix . 'apollo_remind_push_subs';
$l_table = $wpdb->prefix . 'apollo_remind_log';

$stats = [
    'pending'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $r_table WHERE status = 'pending'" ),
    'sent'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $r_table WHERE status = 'sent'" ),
    'failed'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $r_table WHERE status = 'failed'" ),
    'telegram'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t_table WHERE active = 1" ),
    'push_subs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $p_table" ),
    'logs_24h'  => (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $l_table WHERE sent_at_gmt > %s",
        gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS )
    ) ),
];

$bot_token  = apollo_remind_get_bot_token();
$vapid_pub  = apollo_remind_get_vapid_public();
$webhook_url = rest_url( 'apollo/v1/remind/telegram/webhook' );

// Handle Telegram webhook setup
$webhook_msg = '';
if ( isset( $_POST['apollo_set_webhook'] ) && check_admin_referer( 'apollo_remind_admin' ) ) {
    if ( ! empty( $bot_token ) ) {
        $resp = wp_remote_post( "https://api.telegram.org/bot{$bot_token}/setWebhook", [
            'body' => [ 'url' => $webhook_url ],
        ] );
        if ( ! is_wp_error( $resp ) ) {
            $body = json_decode( wp_remote_retrieve_body( $resp ), true );
            $webhook_msg = $body['ok'] ? '✅ Webhook set!' : '❌ ' . ( $body['description'] ?? 'Error' );
        } else {
            $webhook_msg = '❌ ' . $resp->get_error_message();
        }
    }
}

// Handle token save
if ( isset( $_POST['apollo_save_token'] ) && check_admin_referer( 'apollo_remind_admin' ) ) {
    update_option( 'apollo_remind_telegram_token', sanitize_text_field( $_POST['bot_token'] ?? '' ) );
    $bot_token = get_option( 'apollo_remind_telegram_token' );
    $webhook_msg = '✅ Token saved.';
}
?>
<div class="wrap">
    <h1><span class="dashicons dashicons-bell" style="margin-right:8px"></span> Apollo::Rio Reminders</h1>

    <?php if ( $webhook_msg ) : ?>
        <div class="notice notice-info"><p><?php echo esc_html( $webhook_msg ); ?></p></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:24px 0">
        <?php foreach ( [
            [ 'Pending',        $stats['pending'],   '#f59e0b' ],
            [ 'Sent',           $stats['sent'],      '#10b981' ],
            [ 'Failed',         $stats['failed'],    '#ef4444' ],
            [ 'Telegram Users', $stats['telegram'],  '#3b82f6' ],
            [ 'Push Subs',      $stats['push_subs'], '#8b5cf6' ],
            [ 'Logs (24h)',     $stats['logs_24h'],  '#6b7280' ],
        ] as [ $label, $count, $color ] ) : ?>
            <div style="background:#fff;border-left:4px solid <?php echo esc_attr( $color ); ?>;padding:16px;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.1)">
                <div style="font-size:28px;font-weight:700;color:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( number_format_i18n( $count ) ); ?></div>
                <div style="font-size:13px;color:#6b7280;margin-top:4px"><?php echo esc_html( $label ); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <!-- Telegram Config -->
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
            <h2>Telegram Bot</h2>
            <form method="post">
                <?php wp_nonce_field( 'apollo_remind_admin' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Bot Token</th>
                        <td>
                            <input type="text" name="bot_token" value="<?php echo esc_attr( $bot_token ); ?>"
                                   class="regular-text" placeholder="123456:ABCdef..." />
                            <p class="description">
                                Get from <a href="https://t.me/BotFather" target="_blank">@BotFather</a>.
                                Or define <code>APOLLO_TELEGRAM_BOT_TOKEN</code> in wp-config.php
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>Webhook URL</th>
                        <td>
                            <code><?php echo esc_html( $webhook_url ); ?></code>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="submit" name="apollo_save_token" class="button">Save Token</button>
                    <button type="submit" name="apollo_set_webhook" class="button button-primary"
                        <?php disabled( empty( $bot_token ) ); ?>>Set Webhook</button>
                </p>
            </form>
        </div>

        <!-- VAPID Config -->
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
            <h2>Web Push (VAPID)</h2>
            <table class="form-table">
                <tr>
                    <th>Public Key</th>
                    <td>
                        <?php if ( $vapid_pub ) : ?>
                            <code style="word-break:break-all;font-size:11px"><?php echo esc_html( $vapid_pub ); ?></code>
                        <?php else : ?>
                            <span style="color:#ef4444">Not configured</span>
                            <p class="description">
                                Define in wp-config.php:<br>
                                <code>define('APOLLO_VAPID_PUBLIC_KEY', '...');</code><br>
                                <code>define('APOLLO_VAPID_PRIVATE_KEY', '...');</code><br><br>
                                Generate keys: <code>composer require minishlink/web-push && php -r "var_dump(\Minishlink\WebPush\VAPID::createVapidKeys());"</code>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Library</th>
                    <td>
                        <?php if ( class_exists( '\\Minishlink\\WebPush\\WebPush' ) ) : ?>
                            <span style="color:#10b981">✅ minishlink/web-push installed</span>
                        <?php else : ?>
                            <span style="color:#f59e0b">⚠️ Fallback mode (no encryption)</span>
                            <p class="description">Run <code>composer require minishlink/web-push</code> in the plugin dir for production.</p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Recent queue -->
    <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-top:24px">
        <h2>Recent Reminders</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th><th>User</th><th>Title</th><th>Due (GMT)</th>
                    <th>Channels</th><th>Status</th><th>Attempts</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent = $wpdb->get_results(
                    "SELECT r.*, u.display_name FROM $r_table r
                     LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                     ORDER BY r.id DESC LIMIT 25"
                );
                foreach ( $recent as $row ) :
                    $status_color = match ( $row->status ) {
                        'sent'      => '#10b981',
                        'failed'    => '#ef4444',
                        'cancelled' => '#6b7280',
                        default     => '#f59e0b',
                    };
                ?>
                <tr>
                    <td>#<?php echo (int) $row->id; ?></td>
                    <td><?php echo esc_html( $row->display_name ?: "UID:{$row->user_id}" ); ?></td>
                    <td><?php echo esc_html( mb_substr( $row->title ?: $row->message, 0, 60 ) ); ?></td>
                    <td><?php echo esc_html( $row->due_at_gmt ); ?></td>
                    <td><code><?php echo esc_html( $row->channels ); ?></code></td>
                    <td style="color:<?php echo esc_attr( $status_color ); ?>;font-weight:600"><?php echo esc_html( $row->status ); ?></td>
                    <td><?php echo (int) $row->attempts; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
