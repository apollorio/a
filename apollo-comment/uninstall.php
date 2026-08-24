<?php
/**
 * Apollo Comment (Depoimentos) — Uninstall.
 *
 * Runs when the plugin is deleted via WP Admin.
 * Removes all plugin data: options.
 * Note: Uses native wp_comments — comment data is NOT removed.
 *
 * @package Apollo\Comment
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// ── Remove options ──────────────────────────────────────────
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        'apollo_comment_%'
    )
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
