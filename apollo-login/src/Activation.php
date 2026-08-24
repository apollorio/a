<?php
/**
 * Fired during plugin activation.
 *
 * @package Apollo\Login
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Login;

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
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate(): void {
		// Check minimum requirements
		self::check_requirements();

		// Create database tables
		self::create_tables();

		// Create default options
		self::create_options();

		// Create login page if needed
		self::create_login_page();

		// Clear cache
		self::clear_cache();

		// Schedule purge of unverified registrations (48h TTL).
		if ( ! wp_next_scheduled( 'apollo_login_purge_unverified_users' ) ) {
			wp_schedule_event( time(), 'daily', 'apollo_login_purge_unverified_users' );
		}

		// Set activation flag
		set_transient( 'apollo_login_activated', true, 30 );

		// Flush rewrite rules (soft — DB only, no .htaccess write).
		flush_rewrite_rules( false );
	}

	/**
	 * Check plugin requirements.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function check_requirements(): void {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( APOLLO_LOGIN_FILE ) );
			wp_die(
				esc_html__( 'Apollo Login requires PHP 7.4 or higher.', 'apollo-login' ),
				esc_html__( 'Plugin Activation Error', 'apollo-login' ),
				array( 'back_link' => true )
			);
		}

		if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
			deactivate_plugins( plugin_basename( APOLLO_LOGIN_FILE ) );
			wp_die(
				esc_html__( 'Apollo Login requires WordPress 5.8 or higher.', 'apollo-login' ),
				esc_html__( 'Plugin Activation Error', 'apollo-login' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create database tables.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Quiz results table
		$table_quiz = $wpdb->prefix . 'apollo_quiz_results';
		$sql_quiz   = "CREATE TABLE IF NOT EXISTS $table_quiz (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            score int(11) NOT NULL DEFAULT 0,
            answers longtext DEFAULT NULL,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

		// Simon scores table
		$table_simon = $wpdb->prefix . 'apollo_simon_scores';
		$sql_simon   = "CREATE TABLE IF NOT EXISTS $table_simon (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            score int(11) NOT NULL DEFAULT 0,
            played_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY score (score)
        ) $charset_collate;";

		// Login attempts table
		$table_attempts = $wpdb->prefix . 'apollo_login_attempts';
		$sql_attempts   = "CREATE TABLE IF NOT EXISTS $table_attempts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            username varchar(100) DEFAULT NULL,
            success tinyint(1) NOT NULL DEFAULT 0,
            attempted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address),
            KEY attempted_at (attempted_at)
        ) $charset_collate;";

		// URL rewrites table
		$table_rewrites = $wpdb->prefix . 'apollo_url_rewrites';
		$sql_rewrites   = "CREATE TABLE IF NOT EXISTS $table_rewrites (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            original_url varchar(255) NOT NULL,
            rewrite_url varchar(255) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY original_url (original_url)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_quiz );
		dbDelta( $sql_simon );
		dbDelta( $sql_attempts );
		dbDelta( $sql_rewrites );
	}

	/**
	 * Create default options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_options(): void {
		$defaults = array(
			'version'            => defined( 'APOLLO_LOGIN_VERSION' ) ? APOLLO_LOGIN_VERSION : '1.0.0',
			'custom_login_url'   => 'acesso',
			'hide_wp_login'      => true,
			'hide_wp_admin'      => true,
			'rate_limiting'      => true,
			'max_attempts'       => 5,
			'lockout_duration'   => 900, // 15 minutes
			'recaptcha_enabled'  => false,
			'recaptcha_site_key' => '',
			'recaptcha_secret'   => '',
			'quiz_required'      => true,
			'simon_required'     => true,
		);

		if ( false === get_option( 'apollo_login_settings' ) ) {
			add_option( 'apollo_login_settings', $defaults );
		}
	}

	/**
	 * Create login page if it doesn't exist.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_login_page(): void {
		$page_id = get_option( 'apollo_login_page_id' );

		if ( $page_id && get_post( $page_id ) ) {
			return;
		}

		$existing_page = get_page_by_path( 'acesso' );
		if ( $existing_page ) {
			update_option( 'apollo_login_page_id', $existing_page->ID );
			return;
		}

		$page_data = array(
			'post_title'   => __( 'Acesso', 'apollo-login' ),
			'post_name'    => 'acesso',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[apollo_login]',
		);

		$page_id = wp_insert_post( $page_data );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'apollo_login_page_id', $page_id );
		}
	}

	/**
	 * Clear cache.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_cache(): void {
		wp_cache_delete( 'apollo_login_settings', 'apollo' );
		delete_transient( 'apollo_login_rewrites' );
	}
}
