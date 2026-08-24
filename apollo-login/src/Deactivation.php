<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Apollo\Login
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Login;

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
		// Clear scheduled events
		self::clear_scheduled_events();

		// Clear transients and cache
		self::clear_cache();

		// Restore default WordPress login behavior
		self::restore_wp_login();

		// Flush rewrite rules
		flush_rewrite_rules( false );
	}

	/**
	 * Clear scheduled cron events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_scheduled_events(): void {
		$scheduled_hooks = array(
			'apollo_login_purge_unverified_users',
			'apollo_jwt_purge_expired',
			'apollo_login_pending_registration_cleanup',
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
		wp_cache_delete( 'apollo_login_settings', 'apollo' );
		delete_transient( 'apollo_login_rewrites' );
		delete_transient( 'apollo_login_activated' );
	}

	/**
	 * Restore WordPress login functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function restore_wp_login(): void {
		// Remove any .htaccess rules if they were added
		// Note: Apollo Login uses hook-based protection, not .htaccess

		// Clear any cached login URL redirects
		delete_option( 'apollo_login_custom_url_active' );
	}
}
