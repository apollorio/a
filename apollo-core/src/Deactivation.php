<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Apollo\Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since 1.0.0
 */
final class Deactivation {

	/**
	 * Deactivate the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		// Delegate to existing UninstallHandler if it exists
		if ( class_exists( 'Apollo\\Core\\Core\\UninstallHandler' ) ) {
			// Note: UninstallHandler typically handles uninstall, not deactivation
		}

		self::clear_scheduled_events();
		self::clear_cache();

		flush_rewrite_rules();
	}

	/**
	 * Clear scheduled cron events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_scheduled_events(): void {
		$scheduled_hooks = array(
			'apollo_core_cleanup',
			'apollo_core_audit_cleanup',
			'apollo_core_cache_refresh',
		);

		foreach ( $scheduled_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Clear cache and transients.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_cache(): void {
		wp_cache_flush();
		delete_transient( 'apollo_core_registry' );
		delete_transient( 'apollo_core_activated' );
	}
}
