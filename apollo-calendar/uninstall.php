<?php
/**
 * Apollo Calendar — Uninstall
 *
 * Drops tables and removes all plugin options/user-meta on uninstall.
 * Only executes when triggered via WordPress core uninstall mechanism.
 *
 * @package Apollo\Calendar
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$prefix = $wpdb->prefix;

// Drop tables (no data kept on uninstall — user is explicitly removing the plugin).
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$prefix}apollo_user_appointments" );
$wpdb->query( "DROP TABLE IF EXISTS {$prefix}apollo_holidays" );
// phpcs:enable

// Remove options.
delete_option( 'apollo_calendar_version' );
delete_option( 'apollo_calendar_db_version' );

// Remove per-user meta for all users.
delete_metadata( 'user', 0, '_apollo_calendar_timezone', '', true );
delete_metadata( 'user', 0, '_apollo_calendar_prefs', '', true );

// Remove cron events.
$timestamp = wp_next_scheduled( 'apollo_calendar_seed_holidays' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'apollo_calendar_seed_holidays' );
}
