<?php
/**
 * Apollo Groups — Uninstall.
 *
 * Runs when the plugin is deleted via WP Admin.
 * Removes all plugin data: tables, options.
 *
 * @package Apollo\Groups
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// ── Remove custom tables ────────────────────────────────────
$tables = array(
    $wpdb->prefix . 'apollo_groups',
    $wpdb->prefix . 'apollo_group_members',
    $wpdb->prefix . 'apollo_group_meta',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
}

// ── Remove options ──────────────────────────────────────────
delete_option( 'apollo_groups_rewrite_version' );

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        'apollo_groups_%'
    )
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
