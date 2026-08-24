<?php
/**
 * Apollo Remind — Uninstall
 * Drops all custom tables and options when the plugin is deleted via wp-admin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Only drop if explicitly enabled
$delete_data = get_option( 'apollo_remind_delete_data_on_uninstall', false );
if ( ! $delete_data ) {
    return;
}

global $wpdb;

$tables = [
    $wpdb->prefix . 'apollo_reminders',
    $wpdb->prefix . 'apollo_remind_telegram',
    $wpdb->prefix . 'apollo_remind_push_subs',
    $wpdb->prefix . 'apollo_remind_log',
];

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS $table" ); // phpcs:ignore
}

$options = [
    'apollo_remind_version',
    'apollo_remind_db_version',
    'apollo_remind_telegram_token',
    'apollo_remind_delete_data_on_uninstall',
];

foreach ( $options as $opt ) {
    delete_option( $opt );
}

// Clean up transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_apollo_tg_link_%' OR option_name LIKE '_transient_timeout_apollo_tg_link_%'"
);

// Clear scheduled events
wp_clear_scheduled_hook( 'apollo_remind_process_queue' );
wp_clear_scheduled_hook( 'apollo_remind_daily_cleanup' );
