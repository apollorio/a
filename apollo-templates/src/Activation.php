<?php
/**
 * Fired during plugin activation.
 *
 * @package Apollo\Templates
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Templates;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0.0
 */
final class Activation {

	/**
	 * Activate the plugin.
	 *
	 * Creates necessary database tables, sets default options,
	 * and performs any other activation tasks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate(): void {
		// Check minimum requirements
		self::check_requirements();

		// Create default options
		self::create_options();

		// Create template directories
		self::create_directories();

		// Clear any cached data
		self::clear_cache();

		// Set activation flag for redirect
		set_transient( 'apollo_templates_activated', true, 30 );

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Check plugin requirements.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function check_requirements(): void {
		// PHP version check
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( plugin_basename( APOLLO_TEMPLATES_FILE ) );
			wp_die(
				esc_html__( 'Apollo Templates requires PHP 8.1 or higher.', 'apollo-templates' ),
				esc_html__( 'Plugin Activation Error', 'apollo-templates' ),
				array( 'back_link' => true )
			);
		}

		// WordPress version check
		if ( version_compare( get_bloginfo( 'version' ), '6.4', '<' ) ) {
			deactivate_plugins( plugin_basename( APOLLO_TEMPLATES_FILE ) );
			wp_die(
				esc_html__( 'Apollo Templates requires WordPress 6.4 or higher.', 'apollo-templates' ),
				esc_html__( 'Plugin Activation Error', 'apollo-templates' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create default plugin options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_options(): void {
		$defaults = array(
			'version'               => APOLLO_TEMPLATES_VERSION,
			'enable_canvas_editor'  => true,
			'enable_calendar_views' => true,
			'cache_templates'       => true,
			'debug_mode'            => false,
		);

		if ( false === get_option( 'apollo_templates_settings' ) ) {
			add_option( 'apollo_templates_settings', $defaults );
		}
	}

	/**
	 * Create necessary directories.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_directories(): void {
		$upload_dir   = wp_upload_dir();
		$template_dir = $upload_dir['basedir'] . '/apollo-templates';

		if ( ! file_exists( $template_dir ) ) {
			wp_mkdir_p( $template_dir );
		}

		// Create .htaccess for security
		$htaccess = $template_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Options -Indexes\n" );
		}
	}

	/**
	 * Clear plugin cache.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_cache(): void {
		wp_cache_delete( 'apollo_templates_list', 'apollo' );
		delete_transient( 'apollo_templates_calendars' );
		delete_transient( 'apollo_canvas_blocks' );
	}
}
