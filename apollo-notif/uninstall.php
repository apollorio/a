<?php
/**
 * Apollo Notif — Uninstall.
 *
 * Runs when the plugin is deleted via WP Admin.
 * Removes all plugin data: tables, user meta, options, cron events.
 *
 * @package Apollo\Notif
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// ── Remove custom tables ────────────────────────────────────
$tables = array(
    $wpdb->prefix . 'apollo_notifications',
    $wpdb->prefix . 'apollo_notif_prefs',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
}

// ── Remove user meta ────────────────────────────────────────
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('_apollo_notif_prefs','_apollo_notif_unread')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL

// ── Remove cron events ──────────────────────────────────────
$crons = array( 'apollo_notif_cleanup', 'apollo_notif_digest_dispatch' );
foreach ( $crons as $event ) {
    $ts = wp_next_scheduled( $event );
    if ( $ts ) {
        wp_unschedule_event( $ts, $event );
    }
}

// ── Remove options ──────────────────────────────────────────
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        'apollo_notif_%'
    )
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
