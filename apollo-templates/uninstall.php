<?php
/**
 * Apollo Templates — Uninstall.
 *
 * Runs when the plugin is deleted via WP Admin.
 * Removes all plugin options.
 *
 * @package Apollo\Templates
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// ── Remove options ──────────────────────────────────────────
delete_option( 'apollo_templates_settings' );
delete_option( 'apollo_test_spreadsheet' );

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        'apollo_templates_%'
    )
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
