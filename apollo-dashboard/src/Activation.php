<?php
/**
 * Fired during plugin activation.
 *
 * @package Apollo\Dashboard
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Dashboard;

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

		// Create dashboard page if it doesn't exist
		self::create_dashboard_page();

		// Clear any cached data
		self::clear_cache();

		// Set activation flag for redirect
		set_transient( 'apollo_dashboard_activated', true, 30 );

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
			deactivate_plugins( plugin_basename( APOLLO_DASHBOARD_FILE ) );
			wp_die(
				esc_html__( 'Apollo Dashboard requires PHP 8.1 or higher.', 'apollo-dashboard' ),
				esc_html__( 'Plugin Activation Error', 'apollo-dashboard' ),
				array( 'back_link' => true )
			);
		}

		// WordPress version check
		if ( version_compare( get_bloginfo( 'version' ), '6.4', '<' ) ) {
			deactivate_plugins( plugin_basename( APOLLO_DASHBOARD_FILE ) );
			wp_die(
				esc_html__( 'Apollo Dashboard requires WordPress 6.4 or higher.', 'apollo-dashboard' ),
				esc_html__( 'Plugin Activation Error', 'apollo-dashboard' ),
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
			'version'          => APOLLO_DASHBOARD_VERSION,
			'enable_widgets'   => true,
			'enable_analytics' => true,
			'default_layout'   => 'grid',
			'debug_mode'       => false,
		);

		if ( false === get_option( 'apollo_dashboard_settings' ) ) {
			add_option( 'apollo_dashboard_settings', $defaults );
		}

		// Default widget layout
		$default_widgets = array(
			'profile'   => array(
				'enabled' => true,
				'order'   => 1,
			),
			'events'    => array(
				'enabled' => true,
				'order'   => 2,
			),
			'favorites' => array(
				'enabled' => true,
				'order'   => 3,
			),
			'activity'  => array(
				'enabled' => true,
				'order'   => 4,
			),
		);

		if ( false === get_option( 'apollo_dashboard_widgets' ) ) {
			add_option( 'apollo_dashboard_widgets', $default_widgets );
		}
	}

	/**
	 * Create dashboard page if it doesn't exist.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_dashboard_page(): void {
		$page_id = get_option( 'apollo_dashboard_page_id' );

		if ( $page_id && get_post( $page_id ) ) {
			return; // Page already exists
		}

		// Check if a page with slug 'painel' exists
		$existing_page = get_page_by_path( 'painel' );
		if ( $existing_page ) {
			update_option( 'apollo_dashboard_page_id', $existing_page->ID );
			return;
		}

		// Create the dashboard page
		$page_data = array(
			'post_title'   => __( 'Painel', 'apollo-dashboard' ),
			'post_name'    => 'painel',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '<!-- Apollo Dashboard -->',
		);

		$page_id = wp_insert_post( $page_data );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'apollo_dashboard_page_id', $page_id );
		}
	}

	/**
	 * Clear plugin cache.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_cache(): void {
		wp_cache_delete( 'apollo_dashboard_layout', 'apollo' );
		delete_transient( 'apollo_dashboard_widgets' );
	}
}
