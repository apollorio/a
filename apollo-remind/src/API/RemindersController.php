<?php

namespace Apollo\Remind\API;

if ( ! defined( 'ABSPATH' ) ) exit;

class RemindersController {

    private string $namespace = 'apollo/v1';

    public function register_routes(): void {

        // List user's reminders
        register_rest_route( $this->namespace, '/remind', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'list_reminders' ],
            'permission_callback' => [ $this, 'auth' ],
            'args' => [
                'status'   => [ 'default' => 'pending', 'sanitize_callback' => 'sanitize_key' ],
                'per_page' => [ 'default' => 20, 'sanitize_callback' => 'absint' ],
                'page'     => [ 'default' => 1,  'sanitize_callback' => 'absint' ],
            ],
        ] );

        // Create reminder
        register_rest_route( $this->namespace, '/remind', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_reminder' ],
            'permission_callback' => [ $this, 'auth' ],
        ] );

        // Get single
        register_rest_route( $this->namespace, '/remind/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_reminder' ],
            'permission_callback' => [ $this, 'auth' ],
        ] );

        // Update
        register_rest_route( $this->namespace, '/remind/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'update_reminder' ],
            'permission_callback' => [ $this, 'auth' ],
        ] );

        // Delete / Cancel
        register_rest_route( $this->namespace, '/remind/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_reminder' ],
            'permission_callback' => [ $this, 'auth' ],
        ] );

        // Calendar view (all reminders for a date range)
        register_rest_route( $this->namespace, '/remind/calendar', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'calendar_view' ],
            'permission_callback' => [ $this, 'auth' ],
            'args' => [
                'from' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
                'to'   => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        // Link Telegram (user enters their Telegram link code)
        register_rest_route( $this->namespace, '/remind/telegram/link', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'link_telegram' ],
            'permission_callback' => [ $this, 'auth' ],
        ] );

        // Telegram status
        register_rest_route( $this->namespace, '/remind/telegram/status', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'telegram_status' ],
            'permission_callback' => [ $this, 'auth' ],
        ] );

        // Channels available for current user
        register_rest_route( $this->namespace, '/remind/channels', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'available_channels' ],
            'permission_callback' => [ $this, 'auth' ],
        ] );
    }

    public function auth(): bool {
        return is_user_logged_in();
    }

    /* ── LIST ────────────────────────────────────────────────── */
    public function list_reminders( \WP_REST_Request $req ): \WP_REST_Response {
        global $wpdb;
        $table   = $wpdb->prefix . 'apollo_reminders';
        $user_id = get_current_user_id();
        $status  = $req->get_param( 'status' );
        $limit   = min( (int) $req->get_param( 'per_page' ), 100 );
        $offset  = ( max( (int) $req->get_param( 'page' ), 1 ) - 1 ) * $limit;

        $where = $wpdb->prepare( "user_id = %d", $user_id );
        if ( $status && $status !== 'all' ) {
            $where .= $wpdb->prepare( " AND status = %s", $status );
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE $where ORDER BY due_at_gmt ASC LIMIT %d OFFSET %d",
                $limit, $offset
            )
        );

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where" );

        return new \WP_REST_Response( [
            'items'    => $rows,
            'total'    => $total,
            'page'     => (int) $req->get_param( 'page' ),
            'per_page' => $limit,
        ], 200 );
    }

    /* ── CREATE ──────────────────────────────────────────────── */
    public function create_reminder( \WP_REST_Request $req ): \WP_REST_Response {
        $body = $req->get_json_params();

        $title      = sanitize_text_field( $body['title'] ?? '' );
        $message    = sanitize_textarea_field( $body['message'] ?? '' );
        $due        = sanitize_text_field( $body['due_at_gmt'] ?? '' );
        $channels   = $body['channels'] ?? [ 'notif' ];
        $context    = sanitize_key( $body['context'] ?? 'manual' );
        $ref_type   = sanitize_key( $body['ref_type'] ?? '' );
        $ref_id     = absint( $body['ref_id'] ?? 0 );
        $recurrence = sanitize_key( $body['recurrence'] ?? 'none' );
        $rec_end    = ! empty( $body['recurrence_end'] ) ? sanitize_text_field( $body['recurrence_end'] ) : null;

        if ( empty( $message ) || empty( $due ) ) {
            return new \WP_REST_Response( [ 'error' => 'message and due_at_gmt are required.' ], 400 );
        }

        // Validate due_at_gmt is a valid datetime
        if ( strtotime( $due ) === false ) {
            return new \WP_REST_Response( [ 'error' => 'due_at_gmt must be a valid datetime string.' ], 400 );
        }

        $id = apollo_remind_schedule( get_current_user_id(), [
            'title'          => $title,
            'message'        => $message,
            'due_at_gmt'     => $due,
            'channels'       => $channels,
            'context'        => $context,
            'ref_type'       => $ref_type,
            'ref_id'         => $ref_id,
            'recurrence'     => $recurrence,
            'recurrence_end' => $rec_end,
        ] );

        if ( ! $id ) {
            return new \WP_REST_Response( [ 'error' => 'Failed to create reminder.' ], 500 );
        }

        return new \WP_REST_Response( [ 'id' => $id, 'status' => 'pending' ], 201 );
    }

    /* ── GET SINGLE ──────────────────────────────────────────── */
    public function get_reminder( \WP_REST_Request $req ): \WP_REST_Response {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}apollo_reminders WHERE id = %d AND user_id = %d",
            $req->get_param( 'id' ), get_current_user_id()
        ) );
        if ( ! $row ) {
            return new \WP_REST_Response( [ 'error' => 'Not found.' ], 404 );
        }
        return new \WP_REST_Response( $row, 200 );
    }

    /* ── UPDATE ──────────────────────────────────────────────── */
    public function update_reminder( \WP_REST_Request $req ): \WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'apollo_reminders';
        $id    = (int) $req->get_param( 'id' );
        $uid   = get_current_user_id();
        $body  = $req->get_json_params();

        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, status FROM $table WHERE id = %d AND user_id = %d", $id, $uid
        ) );
        if ( ! $existing ) {
            return new \WP_REST_Response( [ 'error' => 'Not found.' ], 404 );
        }
        if ( $existing->status !== 'pending' ) {
            return new \WP_REST_Response( [ 'error' => 'Only pending reminders can be edited.' ], 422 );
        }

        $update = [];
        $format = [];
        foreach ( [ 'title', 'message', 'due_at_gmt', 'channels', 'recurrence', 'recurrence_end' ] as $field ) {
            if ( isset( $body[ $field ] ) ) {
                $val = $body[ $field ];
                if ( $field === 'channels' && is_array( $val ) ) {
                    $val = implode( ',', $val );
                }
                $update[ $field ] = sanitize_text_field( $val );
                $format[]         = '%s';
            }
        }
        if ( empty( $update ) ) {
            return new \WP_REST_Response( [ 'error' => 'Nothing to update.' ], 400 );
        }
        $update['updated_at_gmt'] = gmdate( 'Y-m-d H:i:s' );
        $format[] = '%s';

        $wpdb->update( $table, $update, [ 'id' => $id ], $format, [ '%d' ] );

        return new \WP_REST_Response( [ 'updated' => true ], 200 );
    }

    /* ── DELETE / CANCEL ─────────────────────────────────────── */
    public function delete_reminder( \WP_REST_Request $req ): \WP_REST_Response {
        $cancelled = apollo_remind_cancel( (int) $req->get_param( 'id' ), get_current_user_id() );
        if ( ! $cancelled ) {
            return new \WP_REST_Response( [ 'error' => 'Not found or already processed.' ], 404 );
        }
        return new \WP_REST_Response( [ 'cancelled' => true ], 200 );
    }

    /* ── CALENDAR VIEW ───────────────────────────────────────── */
    public function calendar_view( \WP_REST_Request $req ): \WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'apollo_reminders';
        $uid   = get_current_user_id();
        $from  = $req->get_param( 'from' );
        $to    = $req->get_param( 'to' );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, title, message, due_at_gmt, channels, status, context, ref_type, ref_id, recurrence
             FROM $table
             WHERE user_id = %d AND due_at_gmt BETWEEN %s AND %s
             ORDER BY due_at_gmt ASC",
            $uid, $from, $to
        ) );

        return new \WP_REST_Response( $rows, 200 );
    }

    /* ── TELEGRAM LINK ───────────────────────────────────────── */
    public function link_telegram( \WP_REST_Request $req ): \WP_REST_Response {
        $body    = $req->get_json_params();
        $code    = sanitize_text_field( $body['link_code'] ?? '' );
        $user_id = get_current_user_id();

        // Code format: stored as transient by the webhook when user sends /link CODE
        $stored = get_transient( 'apollo_tg_link_' . $code );
        if ( ! $stored ) {
            return new \WP_REST_Response( [ 'error' => 'Invalid or expired code. Send /link to the bot first.' ], 400 );
        }

        $chat_id  = $stored['chat_id'];
        $username = $stored['username'] ?? '';

        apollo_remind_link_telegram( $user_id, $chat_id, $username );
        delete_transient( 'apollo_tg_link_' . $code );

        return new \WP_REST_Response( [ 'linked' => true, 'chat_id' => $chat_id ], 200 );
    }

    /* ── TELEGRAM STATUS ─────────────────────────────────────── */
    public function telegram_status( \WP_REST_Request $req ): \WP_REST_Response {
        $chat_id = apollo_remind_get_chat_id( get_current_user_id() );
        return new \WP_REST_Response( [
            'linked'  => ! empty( $chat_id ),
            'chat_id' => $chat_id,
        ], 200 );
    }

    /* ── AVAILABLE CHANNELS ──────────────────────────────────── */
    public function available_channels( \WP_REST_Request $req ): \WP_REST_Response {
        global $wpdb;
        $uid = get_current_user_id();

        $channels = [ 'notif' ]; // Always available (apollo-notif)

        // Email — always available if user has email
        $user = get_userdata( $uid );
        if ( $user && ! empty( $user->user_email ) ) {
            $channels[] = 'email';
        }

        // Telegram
        if ( apollo_remind_get_chat_id( $uid ) ) {
            $channels[] = 'telegram';
        }

        // Web Push
        $push_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_remind_push_subs WHERE user_id = %d",
            $uid
        ) );
        if ( $push_count > 0 ) {
            $channels[] = 'push';
        }

        return new \WP_REST_Response( [ 'channels' => $channels ], 200 );
    }
}
