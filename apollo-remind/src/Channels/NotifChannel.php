<?php

namespace Apollo\Remind\Channels;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * In-app notification channel — delegates to apollo-notif.
 */
class NotifChannel {

    public static function send( object $reminder ): array {
        // Delegate to apollo_create_notification() — handles snooze, unread count,
        // fires apollo/notif/created (which triggers auto Web Push via apollo-notif).
        if ( ! function_exists( 'apollo_create_notification' ) ) {
            return [ 'success' => false, 'response' => 'apollo-notif not active' ];
        }

        $link = '';
        if ( ! empty( $reminder->ref_id ) && $reminder->ref_id > 0 ) {
            $link = get_permalink( (int) $reminder->ref_id ) ?: '';
        }

        $notif_id = apollo_create_notification(
            (int) $reminder->user_id,
            'reminder',
            $reminder->title ?: __( 'Lembrete', 'apollo-remind' ),
            mb_substr( $reminder->message, 0, 200 ),
            $link,
            [
                'component'   => 'apollo-remind',
                'ref_type'    => $reminder->ref_type,
                'ref_id'      => (int) $reminder->ref_id,
                'reminder_id' => (int) $reminder->id,
            ],
            [
                'severity' => 'info',
                'icon'     => 'ri-alarm-line',
                'channel'  => 'apollo-remind',
            ]
        );

        if ( $notif_id === false ) {
            return [ 'success' => false, 'response' => 'Notification blocked (snoozed or insert failed)' ];
        }

        return [ 'success' => true, 'response' => 'In-app notif created (ID: ' . $notif_id . ')' ];
    }
}
