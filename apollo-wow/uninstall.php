<?php
/**
 * Apollo Wow — Uninstall.
 *
 * Runs when the plugin is deleted via WP Admin.
 * Removes all plugin data: tables, post meta, options.
 *
 * @package Apollo\Wow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// ── Remove custom tables ────────────────────────────────────
$tables = array(
    $wpdb->prefix . 'apollo_wows',
    $wpdb->prefix . 'apollo_wow_types',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
}

// ── Remove post meta ────────────────────────────────────────
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_wow_count','_wow_counts')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL

// ── Remove options ──────────────────────────────────────────
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        'apollo_wow_%'
    )
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
