<?php
/**
 * Deactivation Handler
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {

	/**
	 * Run deactivation tasks
	 * Keeps all data by default (soft deactivation)
	 */
	public static function deactivate(): void {
		// Clear any scheduled crons
		wp_clear_scheduled_hook( 'apollo_membership_daily_check' );

		// Log deactivation
		apollo_membership_log( 0, 'system', 'plugin_deactivated', 'plugin', 0, 'Apollo Membership deactivated' );
	}
}
