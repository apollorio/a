<?php
/**
 * Deactivation Handler
 *
 * Soft deactivation: clear scheduled hooks, transients.
 * Does NOT delete any data (that's uninstall.php's job).
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {

	/**
	 * Run deactivation tasks
	 */
	public static function deactivate(): void {
		self::clear_scheduled_hooks();
		self::clear_transients();
		flush_rewrite_rules();
	}

	/**
	 * Clear all scheduled cron hooks
	 * Adapted from WPAdverts deactivation pattern
	 */
	private static function clear_scheduled_hooks(): void {
		$hooks = array(
			'apollo_classifieds_check_expired',
			'apollo_classifieds_notify_expiring',
			'apollo_classifieds_gc_temp',
		);

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Clear plugin transients
	 */
	private static function clear_transients(): void {
		global $wpdb;

		// Only the core options table identifier and literal LIKE patterns are used here;
		// there is no user-supplied input, so $wpdb->prepare() is not applicable.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_apollo_adverts_%' OR option_name LIKE '_transient_timeout_apollo_adverts_%'"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
