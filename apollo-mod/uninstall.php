<?php
/**
 * Apollo Mod — Uninstall.
 *
 * Runs when the plugin is deleted via WP Admin.
 * Removes all plugin data: tables, post meta, options.
 *
 * @package Apollo\Mod
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// ── Remove custom tables ────────────────────────────────────
$tables = array(
    $wpdb->prefix . 'apollo_mod_queue',
    $wpdb->prefix . 'apollo_mod_log',
);

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
}

// ── Remove post meta ────────────────────────────────────────
$mod_meta_keys = array( '_mod_status', '_mod_notes', '_mod_reviewed_by', '_mod_reviewed_at' );
foreach ( $mod_meta_keys as $meta_key ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ), array( '%s' ) );
}

// ── Remove options ──────────────────────────────────────────
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        'apollo_mod_%'
    )
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
