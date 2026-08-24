<?php
/**
 * Plugin Activation Handler
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {

	/**
	 * Run activation tasks
	 */
	public static function activate(): void {
		self::set_defaults();
		update_option( 'apollo_admin_activated', time() );
		update_option( 'apollo_admin_version', APOLLO_ADMIN_VERSION );
	}

	/**
	 * Seed default settings (only if not already set)
	 */
	private static function set_defaults(): void {
		if ( false !== get_option( APOLLO_ADMIN_OPTION_KEY ) ) {
			return;
		}

		$defaults = array(
			'_global' => array(
				'brand_name'  => 'Apollo',
				'brand_color' => '#6366f1',
				'dark_mode'   => false,
				'compact_ui'  => false,
			),
		);

		update_option( APOLLO_ADMIN_OPTION_KEY, $defaults, true );
	}
}
