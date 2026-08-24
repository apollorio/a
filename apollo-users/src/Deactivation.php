<?php
/**
 * Plugin Deactivation Handler
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation Handler
 */
class Deactivation {

	/**
	 * Run deactivation tasks
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear transients
		self::clear_transients();

		// Unschedule matchmaking cron events
		wp_clear_scheduled_hook( 'apollo_matchmaking_recompute' );
		wp_clear_scheduled_hook( 'apollo_matchmaking_incremental' );

		// Note: We do NOT delete data on deactivation
		// Data is only deleted on uninstall (uninstall.php)
	}

	/**
	 * Clear transients
	 *
	 * @return void
	 */
	private static function clear_transients(): void {
		delete_transient( 'apollo_users_radar_cache' );
		delete_transient( 'apollo_users_matchmaking_cache' );
		delete_transient( 'apollo_matchmaking_lock' );
	}
}
