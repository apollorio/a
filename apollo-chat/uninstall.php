<?php
/**
 * Apollo Chat — Uninstall.
 *
 * Runs when the plugin is deleted via WP Admin.
 * Removes all plugin data: tables, options, cron events.
 *
 * @package Apollo\Chat
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// ── Remove custom tables ────────────────────────────────────
$tables = array(
    $wpdb->prefix . 'apollo_chat_threads',
    $wpdb->prefix . 'apollo_chat_messages',
    $wpdb->prefix . 'apollo_chat_participants',
    $wpdb->prefix . 'apollo_chat_typing',
    $wpdb->prefix . 'apollo_chat_presence',
    $wpdb->prefix . 'apollo_chat_blocks',
    $wpdb->prefix . 'apollo_chat_attachments',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
}

// ── Remove cron events ──────────────────────────────────────
$ts = wp_next_scheduled( 'apollo_chat_cleanup' );
if ( $ts ) {
    wp_unschedule_event( $ts, 'apollo_chat_cleanup' );
}

// ── Remove options ──────────────────────────────────────────
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        'apollo_chat_%'
    )
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
