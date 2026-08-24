<?php

namespace Apollo\Remind\Channels;

if ( ! defined( 'ABSPATH' ) ) exit;

class TelegramChannel {

    /**
     * Send reminder via Telegram Bot API.
     *
     * @return array{success: bool, response: string}
     */
    public static function send( object $reminder ): array {
        $token   = apollo_remind_get_bot_token();
        $chat_id = apollo_remind_get_chat_id( (int) $reminder->user_id );

        if ( empty( $token ) || empty( $chat_id ) ) {
            return [ 'success' => false, 'response' => 'No token or chat_id' ];
        }

        $text = "🔔 *Lembrete Apollo*\n\n";
        if ( ! empty( $reminder->title ) ) {
            $text .= "*{$reminder->title}*\n";
        }
        $text .= $reminder->message;

        // Add deep-link if there's a referenced entity
        if ( ! empty( $reminder->ref_type ) && $reminder->ref_id > 0 ) {
            $url = get_permalink( (int) $reminder->ref_id );
            if ( $url ) {
                $text .= "\n\n🔗 " . $url;
            }
        }

        $body = [
            'chat_id'                  => $chat_id,
            'text'                     => $text,
            'parse_mode'               => 'Markdown',
            'disable_web_page_preview' => false,
        ];

        $response = wp_remote_post(
            "https://api.telegram.org/bot{$token}/sendMessage",
            [
                'timeout'   => 15,
                'sslverify' => true,
                'body'      => $body,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'response' => $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $rbody = wp_remote_retrieve_body( $response );

        if ( $code >= 200 && $code < 300 ) {
            return [ 'success' => true, 'response' => 'HTTP ' . $code ];
        }

        // Handle blocked bot (user blocked the bot)
        $decoded = json_decode( $rbody, true );
        if ( isset( $decoded['error_code'] ) && $decoded['error_code'] === 403 ) {
            // Mark chat as inactive
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'apollo_remind_telegram',
                [ 'active' => 0 ],
                [ 'chat_id' => $chat_id ]
            );
        }

        return [ 'success' => false, 'response' => "HTTP $code: $rbody" ];
    }
}
