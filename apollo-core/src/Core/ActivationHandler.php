<?php
/**
 * Apollo Core - Activation Handler
 *
 * Pattern: CHECK IF EXISTS → BUILD IF NOT
 * All database tables and structures are created only if they don't exist.
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation Handler
 */
final class ActivationHandler {

	/**
	 * Option name for activation state
	 */
	const OPTION_ACTIVATED  = 'apollo_core_activated';
	const OPTION_VERSION    = 'apollo_core_version';
	const OPTION_DB_VERSION = 'apollo_core_db_version';

	/**
	 * Current database schema version
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Run activation
	 */
	public static function activate(): void {
		// ═══════════════════════════════════════════════════════════════
		// 1. BUILD DATABASE TABLES (check if exists → create if not)
		// ═══════════════════════════════════════════════════════════════
		$builder = new DatabaseBuilder();
		$results = $builder->build();

		// Store build results for admin notice
		set_transient( 'apollo_activation_results', $results, 60 );

		// ═══════════════════════════════════════════════════════════════
		// 2. CREATE DEFAULT OPTIONS (if not exist)
		// ═══════════════════════════════════════════════════════════════
		self::create_default_options();

		// ═══════════════════════════════════════════════════════════════
		// 3. SETUP ROLES AND CAPABILITIES
		// ═══════════════════════════════════════════════════════════════
		self::setup_roles();

		// ═══════════════════════════════════════════════════════════════
		// 4. SCHEDULE CRON EVENTS
		// ═══════════════════════════════════════════════════════════════
		self::schedule_cron_events();

		// ═══════════════════════════════════════════════════════════════
		// 5. UPDATE VERSION
		// ═══════════════════════════════════════════════════════════════
		update_option( self::OPTION_VERSION, APOLLO_VERSION );
		update_option( self::OPTION_DB_VERSION, self::DB_VERSION );
		update_option( self::OPTION_ACTIVATED, time() );

		// ═══════════════════════════════════════════════════════════════
		// 6. FLUSH REWRITE RULES (after CPTs registered)
		// ═══════════════════════════════════════════════════════════════
		add_action(
			'init',
			function () {
				flush_rewrite_rules();
			},
			99
		);

		// ═══════════════════════════════════════════════════════════════
		// 7. FIRE ACTIVATION HOOK
		// ═══════════════════════════════════════════════════════════════
		do_action( 'apollo/core/activated', $results );

		// Log activation
		if ( function_exists( 'apollo_log_audit' ) ) {
			apollo_log_audit(
				'plugin:activated',
				'apollo-core',
				null,
				array(
					'version'    => APOLLO_VERSION,
					'db_version' => self::DB_VERSION,
					'tables'     => $results,
				)
			);
		}
	}

	/**
	 * Create default options if they don't exist
	 */
	private static function create_default_options(): void {
		$defaults = array(
			// Core settings
			'apollo_site_mode'                   => 'production',
			'apollo_debug_mode'                  => false,

			// Login settings
			'apollo_login_url'                   => 'entre',
			'apollo_logout_url'                  => 'sair',
			'apollo_register_url'                => 'cadastro',
			'apollo_max_login_attempts'          => 3,
			'apollo_lockout_duration'            => 1800, // 30 minutes

			// Email settings
			'apollo_email_from_name'             => get_bloginfo( 'name' ),
			'apollo_email_from_address'          => get_option( 'admin_email' ),

			// Moderation settings
			'apollo_mod_auto_approve'            => false,
			'apollo_mod_notify_admin'            => true,

			// Uninstall settings (DEFAULT: keep data)
			UninstallHandler::OPTION_DELETE_DATA => false,

			// API settings
			'apollo_api_rate_limit'              => 60, // requests per minute

			// CDN settings (from registry)
			'apollo_cdn_enabled'                 => true,
			'apollo_cdn_url'                     => 'https://cdn.apollo.rio.br/v1.0.0/',
		);

		foreach ( $defaults as $key => $value ) {
			if ( get_option( $key ) === false ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Setup Apollo roles and capabilities
	 *
	 * Apollo Role System (from registry):
	 * - administrator → apollo (display label)
	 * - editor        → MOD
	 * - author        → cena+ (industry verified)
	 * - contributor   → cena (event verified)
	 * - subscriber    → clubber (standard user)
	 */
	private static function setup_roles(): void {
		// Get roles
		$admin       = get_role( 'administrator' );
		$editor      = get_role( 'editor' );
		$author      = get_role( 'author' );
		$contributor = get_role( 'contributor' );
		$subscriber  = get_role( 'subscriber' );

		// ═══════════════════════════════════════════════════════════════
		// ADMIN CAPABILITIES (apollo role)
		// ═══════════════════════════════════════════════════════════════
		if ( $admin ) {
			// Apollo management
			$admin->add_cap( 'apollo_manage_settings' );
			$admin->add_cap( 'apollo_view_audit_log' );
			$admin->add_cap( 'apollo_manage_users' );
			$admin->add_cap( 'apollo_manage_roles' );

			// Content management
			$admin->add_cap( 'apollo_manage_events' );
			$admin->add_cap( 'apollo_manage_djs' );
			$admin->add_cap( 'apollo_manage_locs' );
			$admin->add_cap( 'apollo_manage_classifieds' );
			$admin->add_cap( 'apollo_manage_suppliers' );
			$admin->add_cap( 'apollo_manage_docs' );
			$admin->add_cap( 'apollo_manage_email_templates' );

			// Moderation
			$admin->add_cap( 'apollo_moderate_content' );
			$admin->add_cap( 'apollo_approve_content' );
			$admin->add_cap( 'apollo_delete_content' );
			$admin->add_cap( 'apollo_ban_users' );

			// Industry
			$admin->add_cap( 'apollo_cena_access' );
			$admin->add_cap( 'apollo_verify_users' );
		}

		// ═══════════════════════════════════════════════════════════════
		// EDITOR CAPABILITIES (MOD role)
		// ═══════════════════════════════════════════════════════════════
		if ( $editor ) {
			// Moderation
			$editor->add_cap( 'apollo_moderate_content' );
			$editor->add_cap( 'apollo_approve_content' );

			// Content editing
			$editor->add_cap( 'apollo_edit_events' );
			$editor->add_cap( 'apollo_edit_djs' );
			$editor->add_cap( 'apollo_edit_locs' );
			$editor->add_cap( 'apollo_edit_classifieds' );

			// View
			$editor->add_cap( 'apollo_view_reports' );
		}

		// ═══════════════════════════════════════════════════════════════
		// AUTHOR CAPABILITIES (cena+ - industry verified)
		// ═══════════════════════════════════════════════════════════════
		if ( $author ) {
			// Industry access
			$author->add_cap( 'apollo_cena_access' );

			// Create content
			$author->add_cap( 'apollo_create_events' );
			$author->add_cap( 'apollo_create_djs' );
			$author->add_cap( 'apollo_create_classifieds' );

			// Edit own content
			$author->add_cap( 'apollo_edit_own_events' );
			$author->add_cap( 'apollo_edit_own_djs' );
			$author->add_cap( 'apollo_edit_own_classifieds' );

			// View suppliers
			$author->add_cap( 'apollo_view_suppliers' );
		}

		// ═══════════════════════════════════════════════════════════════
		// CONTRIBUTOR CAPABILITIES (cena::rio - event verified)
		// ═══════════════════════════════════════════════════════════════
		if ( $contributor ) {
			// Create content (pending approval)
			$contributor->add_cap( 'apollo_create_events' );
			$contributor->add_cap( 'apollo_create_classifieds' );

			// Edit own content
			$contributor->add_cap( 'apollo_edit_own_events' );
			$contributor->add_cap( 'apollo_edit_own_classifieds' );
		}

		// ═══════════════════════════════════════════════════════════════
		// SUBSCRIBER CAPABILITIES (clubber - standard user)
		// ═══════════════════════════════════════════════════════════════
		if ( $subscriber ) {
			// Basic capabilities
			$subscriber->add_cap( 'apollo_create_classifieds' );
			$subscriber->add_cap( 'apollo_edit_own_classifieds' );
			$subscriber->add_cap( 'apollo_view_events' );
			$subscriber->add_cap( 'apollo_favorite_content' );
			$subscriber->add_cap( 'apollo_wow_content' );
			$subscriber->add_cap( 'apollo_follow_users' );
		}
	}

	/**
	 * Schedule cron events
	 */
	private static function schedule_cron_events(): void {
		// Cleanup expired quiz sessions (hourly)
		if ( ! wp_next_scheduled( 'apollo_cleanup_expired_sessions' ) ) {
			wp_schedule_event( time(), 'hourly', 'apollo_cleanup_expired_sessions' );
		}

		// Cleanup old lockouts (daily)
		if ( ! wp_next_scheduled( 'apollo_cleanup_old_lockouts' ) ) {
			wp_schedule_event( time(), 'daily', 'apollo_cleanup_old_lockouts' );
		}

		// Process email queue (every 5 minutes)
		if ( ! wp_next_scheduled( 'apollo_process_email_queue' ) ) {
			wp_schedule_event( time(), 'five_minutes', 'apollo_process_email_queue' );
		}

		// Cleanup audit log older than 90 days (weekly)
		if ( ! wp_next_scheduled( 'apollo_cleanup_audit_log' ) ) {
			wp_schedule_event( time(), 'weekly', 'apollo_cleanup_audit_log' );
		}

		// Daily statistics aggregation
		if ( ! wp_next_scheduled( 'apollo_daily_statistics' ) ) {
			wp_schedule_event( strtotime( 'tomorrow midnight' ), 'daily', 'apollo_daily_statistics' );
		}
	}

	/**
	 * Add custom cron schedules
	 */
	public static function add_cron_schedules( array $schedules ): array {
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'apollo-core' ),
		);

		return $schedules;
	}

	/**
	 * Check if database needs upgrade
	 */
	public static function needs_upgrade(): bool {
		$current_db_version = get_option( self::OPTION_DB_VERSION, '0.0.0' );
		return version_compare( $current_db_version, self::DB_VERSION, '<' );
	}

	/**
	 * Run database upgrade
	 */
	public static function upgrade(): void {
		if ( ! self::needs_upgrade() ) {
			return;
		}

		// Rebuild tables (dbDelta handles upgrades)
		$builder = new DatabaseBuilder();
		$builder->build();

		// Update version
		update_option( self::OPTION_DB_VERSION, self::DB_VERSION );

		// Log upgrade
		if ( function_exists( 'apollo_log_audit' ) ) {
			apollo_log_audit(
				'plugin:upgraded',
				'apollo-core',
				null,
				array(
					'from_version' => get_option( self::OPTION_DB_VERSION ),
					'to_version'   => self::DB_VERSION,
				)
			);
		}
	}
}
