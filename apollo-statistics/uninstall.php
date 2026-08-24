<?php

/**
 * Uninstall Apollo Statistics
 *
 * Drops all custom tables and removes all plugin options
 * when the plugin is deleted via WP admin.
 *
 * @package Apollo\Statistics
 */

// If uninstall not called from WordPress, exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop all 7 stats tables (3 legacy + 4 v2).
$tables = array(
    $wpdb->prefix . 'apollo_stats_events',
    $wpdb->prefix . 'apollo_stats_users',
    $wpdb->prefix . 'apollo_stats_content',
    $wpdb->prefix . 'apollo_stats_sessions',
    $wpdb->prefix . 'apollo_stats_pageviews',
    $wpdb->prefix . 'apollo_stats_clicks',
    $wpdb->prefix . 'apollo_stats_radio',
);

foreach ($tables as $table) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Remove plugin options.
delete_option('apollo_statistics_db_version');
delete_option('apollo_statistics_settings');

// Remove user meta keys created by v2.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_apollo_stats_%' OR meta_key LIKE '_apollo_content_score'");

// Clear all cron events.
$cron_hooks = array(
    'apollo_stats_daily_collect',
    'apollo_stats_daily_aggregate',
    'apollo_stats_weekly_rotate',
    'apollo_stats_session_cleanup',
    'apollo_stats_cron_events_reminder_email',
    'apollo_stats_cron_weekly_roundup_email',
);
foreach ($cron_hooks as $hook) {
    $timestamp = wp_next_scheduled($hook);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
    }
}
