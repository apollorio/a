<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Apollo\Templates
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Templates;

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
	 * Cleans up temporary data, clears caches, and performs
	 * any other deactivation tasks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear scheduled events
		self::clear_scheduled_events();

		// Clear transients and cache
		self::clear_cache();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Clear any scheduled cron events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_scheduled_events(): void {
		$scheduled_hooks = array(
			'apollo_templates_cleanup',
			'apollo_templates_cache_refresh',
		);

		foreach ( $scheduled_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Clear plugin cache and transients.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_cache(): void {
		wp_cache_delete( 'apollo_templates_list', 'apollo' );
		delete_transient( 'apollo_templates_calendars' );
		delete_transient( 'apollo_canvas_blocks' );
		delete_transient( 'apollo_templates_activated' );
	}
}
