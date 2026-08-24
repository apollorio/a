<?php

namespace Apollo\Remind\Cron;

use Apollo\Remind\Channels\TelegramChannel;
use Apollo\Remind\Channels\PushChannel;
use Apollo\Remind\Channels\EmailChannel;
use Apollo\Remind\Channels\NotifChannel;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Queue processor — runs every 2 min via WP-Cron.
 * Picks up due reminders, dispatches to channels, logs results.
 */
class Processor {

    private const BATCH_SIZE   = 50;
    private const MAX_ATTEMPTS = 3;

    /**
     * Main cron callback — process due reminders.
     */
    public static function run(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'apollo_reminders';
        $now   = gmdate( 'Y-m-d H:i:s' );

        // Lock: prevent overlapping runs
        $lock = get_transient( 'apollo_remind_processing' );
        if ( $lock ) return;
        set_transient( 'apollo_remind_processing', true, 120 );

        try {
            $reminders = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE status = 'pending' AND due_at_gmt <= %s AND attempts < %d
                 ORDER BY due_at_gmt ASC
                 LIMIT %d",
                $now, self::MAX_ATTEMPTS, self::BATCH_SIZE
            ) );

            foreach ( $reminders as $reminder ) {
                self::dispatch( $reminder );
            }
        } finally {
            delete_transient( 'apollo_remind_processing' );
        }
    }

    /**
     * Dispatch a single reminder to all configured channels.
     */
    private static function dispatch( object $reminder ): void {
        global $wpdb;
        $table    = $wpdb->prefix . 'apollo_reminders';
        $log_table = $wpdb->prefix . 'apollo_remind_log';
        $channels = array_filter( explode( ',', $reminder->channels ) );

        $all_ok = true;

        foreach ( $channels as $channel ) {
            $channel = trim( $channel );

            // Pre-filter: check user preferences before dispatching
            if ( function_exists( 'apollo_user_channel_allowed' )
                 && ! apollo_user_channel_allowed( (int) $reminder->user_id, $channel, 'reminder' ) ) {
                $wpdb->insert( $log_table, [
                    'reminder_id' => $reminder->id,
                    'channel'     => $channel,
                    'status'      => 'filtered',
                    'response'    => 'Blocked by user preferences',
                    'sent_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
                ] );
                continue;
            }

            $result  = match ( $channel ) {
                'telegram' => TelegramChannel::send( $reminder ),
                'push'     => PushChannel::send( $reminder ),
                'email'    => EmailChannel::send( $reminder ),
                'notif'    => NotifChannel::send( $reminder ),
                default    => [ 'success' => false, 'response' => "Unknown channel: $channel" ],
            };

            // Log delivery attempt
            $wpdb->insert( $log_table, [
                'reminder_id' => $reminder->id,
                'channel'     => $channel,
                'status'      => $result['success'] ? 'sent' : 'failed',
                'response'    => mb_substr( $result['response'] ?? '', 0, 500 ),
                'sent_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
            ] );

            if ( ! $result['success'] ) {
                $all_ok = false;
            }
        }

        // Update reminder status
        if ( $all_ok ) {
            $wpdb->update( $table, [
                'status'      => 'sent',
                'sent_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
                'attempts'    => $reminder->attempts + 1,
            ], [ 'id' => $reminder->id ] );

            // Handle recurrence: create next occurrence
            self::handle_recurrence( $reminder );

            /**
             * Fires after a reminder is successfully sent.
             */
            do_action( 'apollo/remind/sent', (int) $reminder->id, (int) $reminder->user_id, $channels );

        } else {
            // Increment attempt counter, keep pending for retry
            $wpdb->update( $table, [
                'attempts'   => $reminder->attempts + 1,
                'last_error' => 'Partial failure at ' . gmdate( 'Y-m-d H:i:s' ),
            ], [ 'id' => $reminder->id ] );

            // If max attempts reached, mark as failed
            if ( $reminder->attempts + 1 >= self::MAX_ATTEMPTS ) {
                $wpdb->update( $table, [ 'status' => 'failed' ], [ 'id' => $reminder->id ] );
                do_action( 'apollo/remind/failed', (int) $reminder->id, (int) $reminder->user_id );
            }
        }
    }

    /**
     * If reminder has recurrence, create the next occurrence.
     */
    private static function handle_recurrence( object $reminder ): void {
        if ( $reminder->recurrence === 'none' || empty( $reminder->recurrence ) ) {
            return;
        }

        $current = strtotime( $reminder->due_at_gmt );
        $next    = match ( $reminder->recurrence ) {
            'daily'   => $current + DAY_IN_SECONDS,
            'weekly'  => $current + WEEK_IN_SECONDS,
            'monthly' => strtotime( '+1 month', $current ),
            default   => null,
        };

        if ( ! $next ) return;

        // Check recurrence_end
        if ( ! empty( $reminder->recurrence_end ) ) {
            $end = strtotime( $reminder->recurrence_end );
            if ( $next > $end ) return;
        }

        // Don't create if next is in the past
        if ( $next <= time() ) return;

        apollo_remind_schedule( (int) $reminder->user_id, [
            'title'          => $reminder->title,
            'message'        => $reminder->message,
            'due_at_gmt'     => gmdate( 'Y-m-d H:i:s', $next ),
            'channels'       => explode( ',', $reminder->channels ),
            'context'        => $reminder->context,
            'ref_type'       => $reminder->ref_type,
            'ref_id'         => (int) $reminder->ref_id,
            'recurrence'     => $reminder->recurrence,
            'recurrence_end' => $reminder->recurrence_end,
        ] );
    }

    /**
     * Daily cleanup — purge old sent/cancelled/failed reminders.
     */
    public static function cleanup(): void {
        global $wpdb;
        $table   = $wpdb->prefix . 'apollo_reminders';
        $log     = $wpdb->prefix . 'apollo_remind_log';
        $cutoff  = gmdate( 'Y-m-d H:i:s', time() - ( 90 * DAY_IN_SECONDS ) );

        // Delete old completed reminders (90 days)
        $old_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM $table WHERE status IN ('sent','cancelled','failed') AND due_at_gmt < %s",
            $cutoff
        ) );

        if ( ! empty( $old_ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $old_ids ), '%d' ) );
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM $log WHERE reminder_id IN ($placeholders)",
                ...$old_ids
            ) );
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM $table WHERE id IN ($placeholders)",
                ...$old_ids
            ) );
        }
    }
}
