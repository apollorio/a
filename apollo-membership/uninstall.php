<?php
/**
 * Apollo Membership — Uninstall
 *
 * Fires when the plugin is deleted (not deactivated).
 * Drops all custom tables and removes all plugin options and user meta.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ── Drop custom tables ──────────────────────────────────────────────
$tables = array(
	$wpdb->prefix . 'apollo_achievements',
	$wpdb->prefix . 'apollo_points',
	$wpdb->prefix . 'apollo_ranks',
	$wpdb->prefix . 'apollo_triggers',
	$wpdb->prefix . 'apollo_steps',
	$wpdb->prefix . 'apollo_membership_log',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
}

// ── Remove plugin options ───────────────────────────────────────────
$options = array(
	'apollo_membership_settings',
	'apollo_membership_db_version',
	'apollo_membership_badge_types',
	'apollo_membership_trigger_points',
	'apollo_membership_trigger_cooldowns',
	'apollo_membership_installed',
	'apollo_membership_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// ── Remove user meta ────────────────────────────────────────────────
$meta_keys = array(
	'_apollo_membership',
	'_apollo_membership_history',
	'_apollo_points_total',
	'_apollo_points_awarded',
	'_apollo_points_deducted',
	'_apollo_rank_id',
	'_apollo_achievements',
	'_apollo_trigger_count',
	'_apollo_last_active',
	'_apollo_online_time',
	'_apollo_last_online_credit',
);

foreach ( $meta_keys as $key ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
			$key
		)
	);
}

// ── Remove transients ───────────────────────────────────────────────
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_apollo_membership_%' OR option_name LIKE '_transient_timeout_apollo_membership_%'"
);

// ── Clear scheduled cron events ─────────────────────────────────────
wp_clear_scheduled_hook( 'apollo_membership_daily_cleanup' );
wp_clear_scheduled_hook( 'apollo_membership_weekly_recap' );
