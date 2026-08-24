<?php
/**
 * Apollo Remind — global helper functions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Schedule a reminder for a user.
 *
 * @param int   $user_id
 * @param array $args {
 *   @type string $title        Short title (max 255).
 *   @type string $message      Full message body.
 *   @type string $due_at_gmt   MySQL datetime in GMT.
 *   @type array  $channels     ['notif','email','telegram','push'] — any combo.
 *   @type string $context      'manual'|'event_rsvp'|'event_warmup'|'system'.
 *   @type string $ref_type     CPT slug or entity ('event','dj','classified').
 *   @type int    $ref_id       Post/entity ID.
 *   @type string $recurrence   'none'|'daily'|'weekly'|'monthly'.
 *   @type string $recurrence_end MySQL datetime (nullable).
 * }
 * @return int|false  Reminder ID on success, false on failure.
 */
function apollo_remind_schedule( int $user_id, array $args ): int|false {
    global $wpdb;

    $defaults = [
        'title'          => '',
        'message'        => '',
        'due_at_gmt'     => '',
        'channels'       => [ 'notif' ],
        'context'        => 'manual',
        'ref_type'       => '',
        'ref_id'         => 0,
        'recurrence'     => 'none',
        'recurrence_end' => null,
    ];
    $args = wp_parse_args( $args, $defaults );

    if ( empty( $args['message'] ) || empty( $args['due_at_gmt'] ) ) {
        return false;
    }

    // Validate due_at_gmt as a proper datetime
    if ( strtotime( $args['due_at_gmt'] ) === false ) {
        return false;
    }

    $channels = is_array( $args['channels'] ) ? implode( ',', $args['channels'] ) : $args['channels'];
    $now      = gmdate( 'Y-m-d H:i:s' );

    $rec_end = ! empty( $args['recurrence_end'] ) ? $args['recurrence_end'] : null;

    $data = [
        'user_id'        => $user_id,
        'title'          => sanitize_text_field( $args['title'] ),
        'message'        => sanitize_textarea_field( $args['message'] ),
        'due_at_gmt'     => $args['due_at_gmt'],
        'channels'       => sanitize_text_field( $channels ),
        'status'         => 'pending',
        'context'        => sanitize_key( $args['context'] ),
        'ref_type'       => sanitize_key( $args['ref_type'] ),
        'ref_id'         => absint( $args['ref_id'] ),
        'recurrence'     => sanitize_key( $args['recurrence'] ),
        'recurrence_end' => $rec_end,
        'attempts'       => 0,
        'created_at_gmt' => $now,
        'updated_at_gmt' => $now,
    ];

    $format = [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ];

    // Handle NULL for recurrence_end properly
    if ( $rec_end === null ) {
        // Remove from data/format — insert NULL via raw SQL after
        unset( $data['recurrence_end'] );
    } else {
        $format[] = '%s';
    }

    $format = array_merge( $format, [ '%d', '%s', '%s' ] );

    $inserted = $wpdb->insert(
        $wpdb->prefix . 'apollo_reminders',
        $data,
        $format
    );

    if ( ! $inserted ) {
        return false;
    }

    $id = (int) $wpdb->insert_id;

    /**
     * Fires after a reminder is created.
     *
     * @param int   $id      Reminder ID.
     * @param int   $user_id User who owns the reminder.
     * @param array $args    Original args.
     */
    do_action( 'apollo/remind/created', $id, $user_id, $args );

    return $id;
}

/**
 * Cancel a pending reminder.
 */
function apollo_remind_cancel( int $reminder_id, int $user_id = 0 ): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'apollo_reminders';

    $where = [ 'id' => $reminder_id, 'status' => 'pending' ];
    if ( $user_id > 0 ) {
        $where['user_id'] = $user_id;
    }

    $updated = $wpdb->update( $table, [ 'status' => 'cancelled' ], $where );
    return (bool) $updated;
}

/**
 * Get the user's Telegram chat_id (if linked).
 */
function apollo_remind_get_chat_id( int $user_id ): ?string {
    global $wpdb;
    $table = $wpdb->prefix . 'apollo_remind_telegram';
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT chat_id FROM $table WHERE user_id = %d AND active = 1 LIMIT 1",
        $user_id
    ) );
}

/**
 * Link a Telegram chat_id to a WordPress user.
 */
function apollo_remind_link_telegram( int $user_id, string $chat_id, string $username = '' ): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'apollo_remind_telegram';

    // Upsert: if chat_id exists, update user_id
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table WHERE chat_id = %s", $chat_id
    ) );

    if ( $existing ) {
        return (bool) $wpdb->update( $table,
            [ 'user_id' => $user_id, 'username' => sanitize_text_field( $username ), 'active' => 1 ],
            [ 'chat_id' => $chat_id ]
        );
    }

    return (bool) $wpdb->insert( $table, [
        'user_id'   => $user_id,
        'chat_id'   => $chat_id,
        'username'  => sanitize_text_field( $username ),
        'linked_at' => gmdate( 'Y-m-d H:i:s' ),
        'active'    => 1,
    ] );
}

/**
 * Get VAPID public key for Web Push subscription.
 */
function apollo_remind_get_vapid_public(): string {
    $key = defined( 'APOLLO_VAPID_PUBLIC_KEY' )
        ? APOLLO_VAPID_PUBLIC_KEY
        : get_option( 'apollo_vapid_public_key', '' );
    return (string) $key;
}

/**
 * Get VAPID private key (server-side only, never expose).
 */
function apollo_remind_get_vapid_private(): string {
    $key = defined( 'APOLLO_VAPID_PRIVATE_KEY' )
        ? APOLLO_VAPID_PRIVATE_KEY
        : get_option( 'apollo_vapid_private_key', '' );
    return (string) $key;
}

/**
 * Get Telegram bot token.
 */
function apollo_remind_get_bot_token(): string {
    if ( defined( 'APOLLO_TELEGRAM_BOT_TOKEN' ) ) {
        return APOLLO_TELEGRAM_BOT_TOKEN;
    }
    return (string) get_option( 'apollo_remind_telegram_token', '' );
}
