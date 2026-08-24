<?php

namespace Apollo\Remind\API;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * REST endpoints for Web Push subscription management.
 * Uses the Push API / VAPID (RFC 8292) standard.
 */
class PushController {

    private string $namespace = 'apollo/v1';

    public function register_routes(): void {

        // Subscribe device
        register_rest_route( $this->namespace, '/remind/push/subscribe', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'subscribe' ],
            'permission_callback' => fn() => is_user_logged_in(),
        ] );

        // Unsubscribe device
        register_rest_route( $this->namespace, '/remind/push/unsubscribe', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'unsubscribe' ],
            'permission_callback' => fn() => is_user_logged_in(),
        ] );

        // Get VAPID public key
        register_rest_route( $this->namespace, '/remind/push/vapid-key', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'vapid_key' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function subscribe( \WP_REST_Request $req ): \WP_REST_Response {
        global $wpdb;
        $body    = $req->get_json_params();
        $user_id = get_current_user_id();

        $endpoint = sanitize_url( $body['endpoint'] ?? '' );
        $p256dh   = sanitize_text_field( $body['keys']['p256dh'] ?? '' );
        $auth     = sanitize_text_field( $body['keys']['auth'] ?? '' );

        if ( empty( $endpoint ) || empty( $p256dh ) || empty( $auth ) ) {
            return new \WP_REST_Response( [ 'error' => 'Missing subscription data.' ], 400 );
        }

        $table = $wpdb->prefix . 'apollo_remind_push_subs';

        // Check if endpoint already exists for this user
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND endpoint = %s",
            $user_id, $endpoint
        ) );

        if ( $existing ) {
            // Update keys (they rotate)
            $wpdb->update( $table,
                [ 'p256dh' => $p256dh, 'auth_key' => $auth ],
                [ 'id' => $existing ]
            );
        } else {
            $wpdb->insert( $table, [
                'user_id'    => $user_id,
                'endpoint'   => $endpoint,
                'p256dh'     => $p256dh,
                'auth_key'   => $auth,
                'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
                'created_at' => gmdate( 'Y-m-d H:i:s' ),
            ] );
        }

        return new \WP_REST_Response( [ 'subscribed' => true ], 200 );
    }

    public function unsubscribe( \WP_REST_Request $req ): \WP_REST_Response {
        global $wpdb;
        $body     = $req->get_json_params();
        $endpoint = sanitize_url( $body['endpoint'] ?? '' );

        if ( empty( $endpoint ) ) {
            return new \WP_REST_Response( [ 'error' => 'Missing endpoint.' ], 400 );
        }

        $wpdb->delete(
            $wpdb->prefix . 'apollo_remind_push_subs',
            [ 'user_id' => get_current_user_id(), 'endpoint' => $endpoint ]
        );

        return new \WP_REST_Response( [ 'unsubscribed' => true ], 200 );
    }

    public function vapid_key(): \WP_REST_Response {
        $key = apollo_remind_get_vapid_public();
        if ( empty( $key ) ) {
            return new \WP_REST_Response( [ 'error' => 'VAPID not configured.' ], 503 );
        }
        return new \WP_REST_Response( [ 'publicKey' => $key ], 200 );
    }
}
