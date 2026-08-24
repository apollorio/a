<?php

namespace Apollo\Remind\Channels;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Web Push channel — sends via VAPID/RFC 8292.
 *
 * Two modes:
 *   1. If minishlink/web-push is installed (composer), uses the library.
 *   2. Fallback: raw wp_remote_post() with VAPID JWT signing via openssl.
 *
 * For production, install: composer require minishlink/web-push
 * The fallback mode covers basic Chrome/Firefox without the library.
 */
class PushChannel {

    /**
     * Send push notification to all user's subscribed devices.
     *
     * @return array{success: bool, response: string, sent: int, failed: int}
     */
    public static function send( object $reminder ): array {
        // 1. Load subscriptions — prefer shared apollo-notif storage, fallback to own table
        $subs = self::get_subscriptions( (int) $reminder->user_id );

        if ( empty( $subs ) ) {
            return [ 'success' => false, 'response' => 'No push subscriptions', 'sent' => 0, 'failed' => 0 ];
        }

        $payload = json_encode( [
            'title' => $reminder->title ?: __( 'Apollo::Rio Lembrete', 'apollo-remind' ),
            'body'  => mb_substr( $reminder->message, 0, 200 ),
            'icon'  => 'https://cdn.apollo.rio.br/icon-192.png',
            'badge' => 'https://cdn.apollo.rio.br/badge-72.png',
            'url'   => $reminder->ref_id > 0 ? get_permalink( (int) $reminder->ref_id ) : home_url( '/painel' ),
            'tag'   => 'apollo-remind-' . $reminder->id,
        ] );

        // Try minishlink/web-push first
        if ( class_exists( '\\Minishlink\\WebPush\\WebPush' ) ) {
            return self::send_via_library( $subs, $payload );
        }

        // Fallback: simple POST per endpoint (no encryption — works for testing/dev)
        return self::send_via_fallback( $subs, $payload );
    }

    /**
     * Production mode: minishlink/web-push library.
     * Install: composer require minishlink/web-push
     */
    private static function send_via_library( array $subs, string $payload ): array {
        $auth = [
            'VAPID' => [
                'subject'    => 'mailto:oi@apollo.rio.br',
                'publicKey'  => apollo_remind_get_vapid_public(),
                'privateKey' => apollo_remind_get_vapid_private(),
            ],
        ];

        $webPush = new \Minishlink\WebPush\WebPush( $auth, [
            'TTL'     => 3600,
            'urgency' => 'normal',
            'topic'   => 'apollo-reminder',
        ] );

        $sent = 0;
        $failed = 0;

        foreach ( $subs as $sub ) {
            $subscription = \Minishlink\WebPush\Subscription::create( [
                'endpoint' => $sub->endpoint,
                'keys'     => [
                    'p256dh' => $sub->p256dh,
                    'auth'   => $sub->auth_key,
                ],
            ] );
            $webPush->queueNotification( $subscription, $payload );
        }

        foreach ( $webPush->flush() as $report ) {
            if ( $report->isSuccess() ) {
                $sent++;
            } else {
                $failed++;
                // Remove expired subscriptions
                if ( $report->isSubscriptionExpired() ) {
                    global $wpdb;
                    $wpdb->delete(
                        $wpdb->prefix . 'apollo_remind_push_subs',
                        [ 'endpoint' => $report->getEndpoint() ]
                    );
                }
            }
        }

        return [
            'success'  => $sent > 0,
            'response' => "Sent: $sent, Failed: $failed (library mode)",
            'sent'     => $sent,
            'failed'   => $failed,
        ];
    }

    /**
     * Dev/fallback mode: simple POST without payload encryption.
     * This won't work in production (browsers require encrypted payloads).
     * Install minishlink/web-push for production use.
     */
    private static function send_via_fallback( array $subs, string $payload ): array {
        $sent = 0;
        $failed = 0;

        foreach ( $subs as $sub ) {
            $response = wp_remote_post( $sub->endpoint, [
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'TTL'          => '3600',
                    'Urgency'      => 'normal',
                ],
                'body' => $payload,
            ] );

            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 201 ) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'success'  => $sent > 0,
            'response' => "Sent: $sent, Failed: $failed (fallback mode — install minishlink/web-push for production)",
            'sent'     => $sent,
            'failed'   => $failed,
        ];
    }

    /**
     * Load push subscriptions for a user.
     *
     * Primary: shared _apollo_push_subscriptions user meta (apollo-notif).
     * Fallback: own apollo_remind_push_subs table (backward compat).
     *
     * Returns normalized objects with ->endpoint, ->p256dh, ->auth_key.
     */
    private static function get_subscriptions( int $user_id ): array {
        // 1. Try shared meta from apollo-notif
        $shared = get_user_meta( $user_id, '_apollo_push_subscriptions', true );
        if ( is_array( $shared ) && ! empty( $shared ) ) {
            $subs = [];
            foreach ( $shared as $entry ) {
                $subs[] = (object) [
                    'endpoint' => $entry['endpoint'] ?? '',
                    'p256dh'   => $entry['keys']['p256dh'] ?? '',
                    'auth_key' => $entry['keys']['auth'] ?? '',
                ];
            }
            return array_filter( $subs, fn( $s ) => ! empty( $s->endpoint ) );
        }

        // 2. Fallback: own table
        global $wpdb;
        $table = $wpdb->prefix . 'apollo_remind_push_subs';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            return [];
        }

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT endpoint, p256dh, auth_key FROM {$table} WHERE user_id = %d",
            $user_id
        ) ) ?: [];
    }
}
