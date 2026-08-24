<?php

/**
 * Uninstall apollo-gestor
 *
 * Removes all custom tables and options on plugin deletion.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables
$tables = array(
	$wpdb->prefix . 'apollo_gestor_reminder_log',
	$wpdb->prefix . 'apollo_gestor_activity',
	$wpdb->prefix . 'apollo_gestor_milestones',
	$wpdb->prefix . 'apollo_gestor_payments',
	$wpdb->prefix . 'apollo_gestor_team',
	$wpdb->prefix . 'apollo_gestor_tasks',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
}

// Remove options
delete_option( 'apollo_gestor_db_version' );
delete_option( 'apollo_gestor_settings' );
