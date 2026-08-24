<?php
/**
 * Apollo Email — Uninstall.
 *
 * Runs when the plugin is deleted via WP Admin.
 * Removes all plugin data: options, tables, user meta, CPT posts.
 *
 * @package Apollo\Email
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ── Remove options ──────────────────────────────────────────
delete_option( 'apollo_email_settings' );
delete_option( 'apollo_email_db_version' );

// ── Remove cron ─────────────────────────────────────────────
$timestamp = wp_next_scheduled( 'apollo_email_process_queue' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'apollo_email_process_queue' );
}

// ── Remove transients ───────────────────────────────────────
delete_transient( 'apollo_email_queue_lock' );

// ── Remove CPT posts ────────────────────────────────────────
$posts = get_posts(
	array(
		'post_type'      => 'email_aprio',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}

// ── Remove custom tables ────────────────────────────────────
$tables_to_drop = array(
	'apollo_email_queue',
	'apollo_email_log',
	'apollo_newsletter_subscribers',
	'apollo_newsletter_campaigns',
);
foreach ( $tables_to_drop as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}

// ── Remove newsletter settings ───────────────────────────────
delete_option( 'apollo_newsletter_settings' );

// ── Unschedule newsletter cron ────────────────────────────────
$newsletter_ts = wp_next_scheduled( 'apollo_newsletter_send_scheduled' );
if ( $newsletter_ts ) {
	wp_unschedule_event( $newsletter_ts, 'apollo_newsletter_send_scheduled' );
}

// ── Remove user meta ────────────────────────────────────────
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	"DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('_apollo_email_prefs', '_apollo_email_verified')"
);
