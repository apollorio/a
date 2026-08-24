<?php

/**
 * Deactivation — runs on plugin deactivation
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {


	/**
	 * Run deactivation routines
	 */
	public static function deactivate(): void {
		self::clear_caches();
	}

	/**
	 * Clear all sheet caches
	 */
	private static function clear_caches(): void {
		global $wpdb;

		// Delete all apollo_sheet_ transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_apollo_sheet_%'
                OR option_name LIKE '_transient_timeout_apollo_sheet_%'"
		);
	}
}
