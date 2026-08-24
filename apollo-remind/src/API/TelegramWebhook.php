<?php

namespace Apollo\Remind\API;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles incoming Telegram Bot webhook updates.
 *
 * Commands:
 *   /start          → Welcome + instructions
 *   /link           → Generate a 6-digit code to link WP account
 *   /remind HH:MM message → Quick-create reminder
 *   /reminders      → List upcoming reminders
 *   /cancel {id}    → Cancel a reminder
 *   /help           → Show available commands
 */
class TelegramWebhook {

    private string $namespace = 'apollo/v1';

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/remind/telegram/webhook', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true', // Telegram sends unsigned — verified by token in URL
        ] );
    }

    public function handle( \WP_REST_Request $req ): \WP_REST_Response {
        // Verify Telegram secret token if configured
        $secret = defined( 'APOLLO_TELEGRAM_WEBHOOK_SECRET' ) ? APOLLO_TELEGRAM_WEBHOOK_SECRET : '';
        if ( $secret ) {
            $header_secret = $req->get_header( 'X-Telegram-Bot-Api-Secret-Token' );
            if ( ! hash_equals( $secret, (string) $header_secret ) ) {
                return new \WP_REST_Response( [ 'error' => 'Unauthorized' ], 403 );
            }
        }

        $update = $req->get_json_params();

        // Only handle text messages
        $msg = $update['message'] ?? null;
        if ( ! $msg || empty( $msg['text'] ) ) {
            return new \WP_REST_Response( [ 'ok' => true ], 200 );
        }

        $chat_id  = (string) $msg['chat']['id'];
        $text     = trim( $msg['text'] );
        $username = $msg['from']['username'] ?? '';
        $parts    = explode( ' ', $text, 2 );
        $command  = strtolower( $parts[0] );
        $payload  = $parts[1] ?? '';

        match ( $command ) {
            '/start'     => $this->cmd_start( $chat_id, $username ),
            '/link'      => $this->cmd_link( $chat_id, $username ),
            '/remind'    => $this->cmd_remind( $chat_id, $payload ),
            '/reminders' => $this->cmd_list( $chat_id ),
            '/cancel'    => $this->cmd_cancel( $chat_id, $payload ),
            '/help'      => $this->cmd_help( $chat_id ),
            default      => null, // Ignore unknown
        };

        return new \WP_REST_Response( [ 'ok' => true ], 200 );
    }

    /* ── /start ──────────────────────────────────────────────── */
    private function cmd_start( string $chat_id, string $username ): void {
        $text = "🌅 *Apollo::Rio Reminders*\n\n"
              . "Nunca perca um evento!\n\n"
              . "Para vincular sua conta Apollo:\n"
              . "1. Digite /link aqui\n"
              . "2. Cole o código em apollo.rio.br → Configurações → Telegram\n\n"
              . "Comandos:\n"
              . "/link — Vincular conta Apollo\n"
              . "/remind HH:MM mensagem — Criar lembrete\n"
              . "/reminders — Ver próximos lembretes\n"
              . "/cancel {id} — Cancelar lembrete\n"
              . "/help — Ajuda";

        $this->send( $chat_id, $text, 'Markdown' );
    }

    /* ── /link ───────────────────────────────────────────────── */
    private function cmd_link( string $chat_id, string $username ): void {
        // Generate 6-char alphanumeric code
        $code = strtoupper( wp_generate_password( 6, false ) );

        // Store as transient (15 min TTL)
        set_transient( 'apollo_tg_link_' . $code, [
            'chat_id'  => $chat_id,
            'username' => $username,
        ], 15 * MINUTE_IN_SECONDS );

        $text = "🔗 Seu código de vinculação:\n\n"
              . "`$code`\n\n"
              . "Cole este código em:\n"
              . "apollo.rio.br → Painel → Configurações → Telegram\n\n"
              . "⏳ Válido por 15 minutos.";

        $this->send( $chat_id, $text, 'Markdown' );
    }

    /* ── /remind HH:MM message ───────────────────────────────── */
    private function cmd_remind( string $chat_id, string $payload ): void {
        if ( empty( $payload ) ) {
            $this->send( $chat_id, "Uso: /remind HH:MM mensagem\nExemplo: /remind 22:00 Festa na Lapa!" );
            return;
        }

        // Find linked user
        $user_id = $this->get_user_by_chat( $chat_id );
        if ( ! $user_id ) {
            $this->send( $chat_id, "⚠️ Conta não vinculada. Use /link primeiro." );
            return;
        }

        // Parse "HH:MM message"
        if ( ! preg_match( '/^(\d{1,2}:\d{2})\s+(.+)$/s', $payload, $m ) ) {
            $this->send( $chat_id, "Formato: /remind HH:MM mensagem" );
            return;
        }

        $time_str = $m[1];
        $message  = $m[2];

        // Build due_at for today or tomorrow (BRT = UTC-3)
        $now_utc = time();
        $brt     = $now_utc - ( 3 * HOUR_IN_SECONDS );
        $today   = gmdate( 'Y-m-d', $brt );
        $target  = strtotime( "$today $time_str:00" ) + ( 3 * HOUR_IN_SECONDS ); // Convert BRT→GMT

        // If time already passed today, schedule for tomorrow
        if ( $target <= $now_utc ) {
            $target += DAY_IN_SECONDS;
        }

        $id = apollo_remind_schedule( $user_id, [
            'title'      => mb_substr( $message, 0, 80 ),
            'message'    => $message,
            'due_at_gmt' => gmdate( 'Y-m-d H:i:s', $target ),
            'channels'   => [ 'telegram' ],
            'context'    => 'telegram_cmd',
        ] );

        if ( $id ) {
            $human_date = gmdate( 'd/m H:i', $target - ( 3 * HOUR_IN_SECONDS ) );
            $this->send( $chat_id, "✅ Lembrete #$id criado para $human_date (BRT)\n📝 $message" );
        } else {
            $this->send( $chat_id, "❌ Erro ao criar lembrete. Tente novamente." );
        }
    }

    /* ── /reminders ──────────────────────────────────────────── */
    private function cmd_list( string $chat_id ): void {
        global $wpdb;

        $user_id = $this->get_user_by_chat( $chat_id );
        if ( ! $user_id ) {
            $this->send( $chat_id, "⚠️ Conta não vinculada. Use /link primeiro." );
            return;
        }

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, title, due_at_gmt FROM {$wpdb->prefix}apollo_reminders
             WHERE user_id = %d AND status = 'pending'
             ORDER BY due_at_gmt ASC LIMIT 10",
            $user_id
        ) );

        if ( empty( $rows ) ) {
            $this->send( $chat_id, "📭 Nenhum lembrete pendente." );
            return;
        }

        $lines = [ "📋 *Seus lembretes:*\n" ];
        foreach ( $rows as $r ) {
            $brt = gmdate( 'd/m H:i', strtotime( $r->due_at_gmt ) - ( 3 * HOUR_IN_SECONDS ) );
            $lines[] = "#$r->id — $brt — " . ( $r->title ?: '(sem título)' );
        }

        $this->send( $chat_id, implode( "\n", $lines ), 'Markdown' );
    }

    /* ── /cancel {id} ────────────────────────────────────────── */
    private function cmd_cancel( string $chat_id, string $payload ): void {
        $user_id = $this->get_user_by_chat( $chat_id );
        if ( ! $user_id ) {
            $this->send( $chat_id, "⚠️ Conta não vinculada." );
            return;
        }

        $id = absint( trim( $payload ) );
        if ( ! $id ) {
            $this->send( $chat_id, "Uso: /cancel {id}" );
            return;
        }

        $ok = apollo_remind_cancel( $id, $user_id );
        $this->send( $chat_id, $ok ? "✅ Lembrete #$id cancelado." : "❌ Não encontrado ou já enviado." );
    }

    /* ── /help ───────────────────────────────────────────────── */
    private function cmd_help( string $chat_id ): void {
        $this->cmd_start( $chat_id, '' );
    }

    /* ── Helpers ─────────────────────────────────────────────── */

    private function get_user_by_chat( string $chat_id ): int {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}apollo_remind_telegram WHERE chat_id = %s AND active = 1",
            $chat_id
        ) );
    }

    private function send( string $chat_id, string $text, string $parse_mode = '' ): void {
        $token = apollo_remind_get_bot_token();
        if ( empty( $token ) ) return;

        $body = [
            'chat_id'                  => $chat_id,
            'text'                     => $text,
            'disable_web_page_preview' => true,
        ];
        if ( $parse_mode ) {
            $body['parse_mode'] = $parse_mode;
        }

        wp_remote_post( "https://api.telegram.org/bot{$token}/sendMessage", [
            'timeout' => 15,
            'body'    => $body,
        ] );
    }
}
