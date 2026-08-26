<?php

namespace Apollo\Telegram\Menus;

use Apollo\Telegram\Services\VerificationService;

/**
 * Admin space: broadcast to every chat that opened a message to the bot +
 * live system status (webhook, bot credentials, verification pipeline).
 */
class BroadcastMenu extends Base
{
    protected $menuSlug = APOLLO_TELEGRAM_MENUS_SLUG . '_broadcast';

    /**
     * Adds the submenu.
     *
     * @param	array	$submenus
     *
     * @return	array
     *
     * @hooked	filter: `APOLLO_TELEGRAM_menus_submenus` - 10
     */
    public function addSubmenu($submenus)
    {
        $submenus['broadcast'] = [
            'page_title' => esc_html__('Telegram — Broadcast & Status', 'apollo-telegram'),
            'menu_title' => esc_html__('Broadcast & Status', 'apollo-telegram'),
            'callback'   => [$this, 'displayContent'],
            'position'   => 2,
        ];

        return $submenus;
    }

    /**
     * Renders the page: status panel, recipients, broadcast form, recent verifications.
     *
     * @return	void
     */
    public function displayContent()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $result = $this->maybeHandleBroadcast();

        $service = apollo_telegram();
        $chats   = $service->getAllKnownChats();
        $token   = apollo_telegram_bot_token();
        $webhook = $this->getWebhookInfo($token);

        echo '<div class="wrap"><h1>' . esc_html__('Apollo Telegram — Broadcast & Status', 'apollo-telegram') . '</h1>';

        if (null !== $result) {
            $ok = ! empty($result['success']);
            printf(
                '<div class="notice %s"><p><strong>%s</strong> %s</p></div>',
                $ok ? 'notice-success' : 'notice-error',
                $ok ? esc_html__('Broadcast enviado!', 'apollo-telegram') : esc_html__('Broadcast com falhas.', 'apollo-telegram'),
                esc_html(
                    sprintf(
                        /* translators: 1: sent count, 2: failed count, 3: total. */
                        __('Enviadas: %1$d · Falhas: %2$d · Total: %3$d', 'apollo-telegram'),
                        (int) ($result['sent'] ?? 0),
                        (int) ($result['failed'] ?? 0),
                        (int) ($result['total'] ?? 0)
                    )
                )
            );
        }

        // ── Status panel ──
        echo '<h2>' . esc_html__('Status do sistema', 'apollo-telegram') . '</h2>';
        echo '<table class="widefat striped" style="max-width:900px"><tbody>';
        $this->statusRow(
            __('Bot token', 'apollo-telegram'),
            '' !== $token,
            '' !== $token ? __('Configurado', 'apollo-telegram') : __('FALTANDO — defina APOLLO_TELEGRAM_BOT_TOKEN no wp-config.php', 'apollo-telegram')
        );
        $this->statusRow(
            __('Bot username', 'apollo-telegram'),
            '' !== apollo_telegram_bot_username(),
            '@' . apollo_telegram_bot_username()
        );

        if (is_array($webhook)) {
            $wh_url     = (string) ($webhook['url'] ?? '');
            $wh_pending = (int) ($webhook['pending_update_count'] ?? 0);
            $wh_error   = (string) ($webhook['last_error_message'] ?? '');
            $this->statusRow(
                __('Webhook', 'apollo-telegram'),
                '' !== $wh_url,
                '' !== $wh_url ? $wh_url : __('NÃO registrado — o bot não recebe mensagens!', 'apollo-telegram')
            );
            $this->statusRow(
                __('Updates pendentes', 'apollo-telegram'),
                $wh_pending < 10,
                (string) $wh_pending
            );
            if ('' !== $wh_error) {
                $this->statusRow(__('Último erro do webhook', 'apollo-telegram'), false, $wh_error . (! empty($webhook['last_error_date']) ? ' (' . gmdate('d/m H:i', (int) $webhook['last_error_date']) . ' UTC)' : ''));
            }
        } else {
            $this->statusRow(__('Webhook', 'apollo-telegram'), false, __('Não foi possível consultar getWebhookInfo.', 'apollo-telegram'));
        }

        $verified = $this->countVerifications();
        $this->statusRow(
            __('Verificações de telefone', 'apollo-telegram'),
            true,
            sprintf(
                /* translators: 1: verified count, 2: pending count. */
                __('%1$d verificadas · %2$d pendentes', 'apollo-telegram'),
                $verified['verified'],
                $verified['pending']
            )
        );
        $this->statusRow(
            __('Chats alcançáveis (broadcast)', 'apollo-telegram'),
            count($chats) > 0,
            (string) count($chats)
        );
        echo '</tbody></table>';

        // ── Broadcast form ──
        echo '<h2 style="margin-top:24px">' . esc_html__('Notificar todos os usuários', 'apollo-telegram') . '</h2>';
        echo '<p class="description">' . esc_html__('Envia para todos os chats que já abriram conversa com o bot (verificação de telefone, vínculo ou mensagem direta). Suporta HTML do Telegram (<b>, <i>, <a>) e placeholders {phone}, {username}, {chat_id}.', 'apollo-telegram') . '</p>';
        echo '<form method="post" style="max-width:900px">';
        wp_nonce_field('apollo_telegram_broadcast', 'apollo_telegram_broadcast_nonce');
        echo '<textarea name="apollo_broadcast_message" rows="6" class="large-text" required placeholder="' . esc_attr__('🎉 Olá! Novidade no Apollo Rio…', 'apollo-telegram') . '"></textarea>';
        echo '<p><label><input type="checkbox" name="apollo_broadcast_test" value="1" checked> ';
        echo esc_html__('Modo teste: enviar somente para os IDs de admin configurados (recomendado antes do disparo geral).', 'apollo-telegram');
        echo '</label></p>';
        submit_button(__('Enviar broadcast', 'apollo-telegram'), 'primary', 'apollo_broadcast_submit');
        echo '</form>';

        // ── Recipients preview ──
        echo '<h2 style="margin-top:24px">' . esc_html__('Destinatários', 'apollo-telegram') . '</h2>';
        echo '<table class="widefat striped" style="max-width:900px"><thead><tr>';
        echo '<th>' . esc_html__('Chat ID', 'apollo-telegram') . '</th><th>' . esc_html__('Telefone', 'apollo-telegram') . '</th><th>' . esc_html__('Username', 'apollo-telegram') . '</th><th>' . esc_html__('Origem', 'apollo-telegram') . '</th>';
        echo '</tr></thead><tbody>';
        if (empty($chats)) {
            echo '<tr><td colspan="4">' . esc_html__('Nenhum chat conhecido ainda — assim que alguém falar com o bot ou verificar o telefone, aparece aqui.', 'apollo-telegram') . '</td></tr>';
        }
        foreach (array_slice($chats, 0, 50) as $chat) {
            printf(
                '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                (int) $chat['chat_id'],
                esc_html($chat['phone'] ? apollo_telegram_phone_log_suffix((string) $chat['phone']) : '—'),
                esc_html($chat['username'] ? '@' . ltrim((string) $chat['username'], '@') : '—'),
                esc_html((string) $chat['source'])
            );
        }
        echo '</tbody></table>';

        echo '</div>';
    }

    /**
     * Handles the broadcast POST (nonce + capability protected).
     *
     * @return	array|null	Broadcast summary or null when no submission.
     */
    private function maybeHandleBroadcast(): ?array
    {
        if (empty($_POST['apollo_broadcast_submit'])) {
            return null;
        }
        if (
            ! current_user_can('manage_options')
            || empty($_POST['apollo_telegram_broadcast_nonce'])
            || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['apollo_telegram_broadcast_nonce'])), 'apollo_telegram_broadcast')
        ) {
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $message = trim(wp_kses(wp_unslash((string) ($_POST['apollo_broadcast_message'] ?? '')), [
            'b' => [],
            'strong' => [],
            'i' => [],
            'em' => [],
            'u' => [],
            's' => [],
            'a' => ['href' => []],
            'code' => [],
            'pre' => [],
            'br' => [],
        ]));
        if ('' === $message) {
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $options = [];
        if (! empty($_POST['apollo_broadcast_test'])) {
            $admin_ids = array_filter(array_map('intval', explode(',', (string) apollo_telegram_config('admin_ids', ''))));
            if (empty($admin_ids)) {
                return ['success' => false, 'sent' => 0, 'failed' => 0, 'total' => 0, 'message' => 'No admin_ids configured for test mode.'];
            }
            $options['chat_ids'] = $admin_ids;
        }

        return apollo_telegram()->broadcastToAll($message, $options);
    }

    /**
     * getWebhookInfo direct call (status panel).
     *
     * @return	array|null
     */
    private function getWebhookInfo(string $token): ?array
    {
        if ('' === $token) {
            return null;
        }

        $response = wp_remote_get('https://api.telegram.org/bot' . $token . '/getWebhookInfo', ['timeout' => 10]);
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return ! empty($data['ok']) && isset($data['result']) ? (array) $data['result'] : null;
    }

    /**
     * Verified/pending counters from the verification table.
     *
     * @return	array{verified:int,pending:int}
     */
    private function countVerifications(): array
    {
        global $wpdb;

        $table = VerificationService::table();
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return ['verified' => 0, 'pending' => 0];
        }

        return [
            'verified' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", VerificationService::STATUS_VERIFIED)),
            'pending'  => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", VerificationService::STATUS_PENDING)),
        ];
    }

    /**
     * Prints a status table row with ✓/✗ badge.
     *
     * @return	void
     */
    private function statusRow(string $label, bool $ok, string $value)
    {
        printf(
            '<tr><td style="width:220px"><strong>%s</strong></td><td style="width:36px">%s</td><td>%s</td></tr>',
            esc_html($label),
            $ok ? '<span style="color:#00a32a">✔</span>' : '<span style="color:#d63638">✘</span>',
            esc_html($value)
        );
    }
}
