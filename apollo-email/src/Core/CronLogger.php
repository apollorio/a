<?php
/**
 * CronLogger — Unified execution log for all Apollo cron hooks.
 *
 * ONE-WAY SYSTEM RUN:
 * ┌──────────────────────────────────────────────────────────────────┐
 * │  1. Cron fires → CronLogger::start($hook)                      │
 * │  2. Processing loop → CronLogger::addRecipient($id, $email)    │
 * │  3. On error       → CronLogger::addError($id, $message)       │
 * │  4. Loop ends      → CronLogger::finish($id)                   │
 * │                                                                  │
 * │  Table: {prefix}apollo_cron_execution_log                       │
 * │  Row = ONE cron tick. recipients = JSON of who was notified.    │
 * │  This is the AUDIT TRAIL for "what ran, when, for whom".        │
 * └──────────────────────────────────────────────────────────────────┘
 *
 * @package Apollo\Email\Core
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Email\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CronLogger {

    /**
     * In-memory recipient + error collectors per execution.
     *
     * @var array<int, array{recipients: array, errors: array}>
     */
    private static array $runs = array();

    /**
     * Start a cron execution log entry.
     *
     * @param  string $hook Cron hook name (e.g. 'apollo_email_process_queue').
     * @return int    The log row ID (used to finish/update later).
     */
    public static function start( string $hook ): int {
        global $wpdb;

        $wpdb->insert(
            self::table(),
            array(
                'cron_hook'       => sanitize_text_field( $hook ),
                'execution_start' => current_time( 'mysql' ),
                'status'          => 'running',
            ),
            array( '%s', '%s', '%s' )
        );

        $id = (int) $wpdb->insert_id;
        self::$runs[ $id ] = array(
            'recipients' => array(),
            'errors'     => array(),
        );

        return $id;
    }

    /**
     * Record a recipient who was processed during this cron run.
     *
     * @param int    $log_id     The execution log ID from start().
     * @param int    $user_id    WordPress user ID (0 if external).
     * @param string $email      Recipient email.
     * @param string $template   Template slug used.
     * @param string $context    Extra context (e.g. task title).
     */
    public static function addRecipient( int $log_id, int $user_id, string $email, string $template = '', string $context = '' ): void {
        if ( ! isset( self::$runs[ $log_id ] ) ) {
            return;
        }

        self::$runs[ $log_id ]['recipients'][] = array(
            'user_id'  => $user_id,
            'email'    => $email,
            'template' => $template,
            'context'  => $context,
            'at'       => current_time( 'mysql' ),
        );
    }

    /**
     * Record an error during this cron run.
     *
     * @param int    $log_id  The execution log ID.
     * @param string $message Error description.
     */
    public static function addError( int $log_id, string $message ): void {
        if ( ! isset( self::$runs[ $log_id ] ) ) {
            return;
        }

        self::$runs[ $log_id ]['errors'][] = array(
            'message' => $message,
            'at'      => current_time( 'mysql' ),
        );
    }

    /**
     * Finish a cron execution — writes items_processed, recipients, duration.
     *
     * @param int         $log_id The execution log ID.
     * @param string|null $status 'completed' or 'failed'. Auto-detected if null.
     */
    public static function finish( int $log_id, ?string $status = null ): void {
        global $wpdb;

        $run = self::$runs[ $log_id ] ?? array( 'recipients' => array(), 'errors' => array() );

        $processed = count( $run['recipients'] );
        $failed    = count( $run['errors'] );

        if ( null === $status ) {
            $status = $failed > 0 && 0 === $processed ? 'failed' : 'completed';
        }

        // Calculate duration from DB execution_start.
        $table = self::table();
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT execution_start FROM {$table} WHERE id = %d", $log_id ) // phpcs:ignore WordPress.DB.PreparedSQL
        );

        $duration = 0;
        if ( $row ) {
            $start_ts = strtotime( $row->execution_start );
            $duration = max( 0, (int) round( ( microtime( true ) - $start_ts ) * 1000 ) );
        }

        $wpdb->update(
            self::table(),
            array(
                'execution_end'  => current_time( 'mysql' ),
                'duration_ms'    => $duration,
                'status'         => $status,
                'items_processed' => $processed,
                'items_failed'   => $failed,
                'recipients'     => wp_json_encode( $run['recipients'] ),
                'error_log'      => ! empty( $run['errors'] ) ? wp_json_encode( $run['errors'] ) : null,
            ),
            array( 'id' => $log_id ),
            array( '%s', '%d', '%s', '%d', '%d', '%s', '%s' ),
            array( '%d' )
        );

        unset( self::$runs[ $log_id ] );
    }

    /**
     * Table name (with WP prefix).
     */
    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'apollo_cron_execution_log';
    }
}
