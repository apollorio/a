<?php
/**
 * Apollo Events — Uninstall
 *
 * Fired when the plugin is deleted via WP admin.
 * Cleans up options and optionally event data.
 *
 * @package Apollo\Event
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ─── Remove plugin options ───────────────────────────────────────────────────
delete_option( 'apollo_event_settings' );
delete_option( 'apollo_event_version' );

// ─── Remove scheduled cron events ───────────────────────────────────────────
$timestamp = wp_next_scheduled( 'apollo_event_check_expiration' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'apollo_event_check_expiration' );
}

/**
 * Optional: Remove all event data.
 * Uncomment the section below to delete all events, meta, and taxonomy terms
 * when the plugin is completely removed. USE WITH CAUTION.
 */

/*
global $wpdb;

// Delete all event posts
$events = get_posts([
	'post_type'      => 'event',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
]);

foreach ( $events as $event_id ) {
	wp_delete_post( $event_id, true );
}

// Clean up orphaned event meta
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_event_%'"
);

// Remove transients
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_apollo_event_%'"
);
*/
