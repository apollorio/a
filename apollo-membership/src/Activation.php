<?php
/**
 * Activation Handler
 *
 * Creates database tables + seeds membership badge types.
 * Table schemas adapted from BadgeOS db_upgrade() + apollo-registry.json spec.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {

	/**
	 * Run activation tasks
	 */
	public static function activate(): void {
		self::create_tables();
		self::migrate_triggers_table();
		self::set_defaults();
		self::seed_membership_badges();
		update_option( 'apollo_membership_activated', time() );
	}

	/**
	 * Align apollo_triggers timestamp column (legacy `date` → `date_triggered`).
	 */
	public static function migrate_triggers_table(): void {
		global $wpdb;

		$table = $wpdb->prefix . APOLLO_TABLE_TRIGGERS;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$columns = $wpdb->get_col( "DESCRIBE {$table}", 0 );

		if ( ! is_array( $columns ) ) {
			return;
		}

		$has_date           = in_array( 'date', $columns, true );
		$has_date_triggered = in_array( 'date_triggered', $columns, true );

		if ( $has_date && ! $has_date_triggered ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "ALTER TABLE {$table} CHANGE COLUMN `date` `date_triggered` DATETIME DEFAULT CURRENT_TIMESTAMP" );
		} elseif ( ! $has_date_triggered && ! $has_date ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN `date_triggered` DATETIME DEFAULT CURRENT_TIMESTAMP" );
		}
	}

	/**
	 * Create database tables
	 * Adapted from BadgeOS db_upgrade() — 6 tables per apollo-registry.json
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ─────────────────────────────────────────────────────────────────
		// 1. apollo_achievements — adapted from badgeos_achievements
		// ─────────────────────────────────────────────────────────────────
		$table = $prefix . APOLLO_TABLE_ACHIEVEMENTS;
		$sql   = "CREATE TABLE {$table} (
			entry_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			achievement_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			post_type VARCHAR(50) DEFAULT '',
			achievement_title TEXT DEFAULT NULL,
			points INT DEFAULT 0,
			point_type BIGINT UNSIGNED DEFAULT 0,
			this_trigger VARCHAR(100) DEFAULT '',
			rec_type VARCHAR(50) DEFAULT 'normal',
			image VARCHAR(255) DEFAULT '',
			site_id BIGINT UNSIGNED DEFAULT 1,
			actual_date_earned DATETIME DEFAULT CURRENT_TIMESTAMP,
			date_earned DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (entry_id),
			KEY achievement_id (achievement_id),
			KEY user_id (user_id),
			KEY post_type (post_type),
			KEY this_trigger (this_trigger),
			KEY date_earned (date_earned)
		) {$charset};";
		dbDelta( $sql );

		// ─────────────────────────────────────────────────────────────────
		// 2. apollo_points — adapted from badgeos_points
		// ─────────────────────────────────────────────────────────────────
		$table = $prefix . APOLLO_TABLE_POINTS;
		$sql   = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			achievement_id BIGINT UNSIGNED DEFAULT 0,
			credit_id BIGINT UNSIGNED DEFAULT 0,
			step_id BIGINT UNSIGNED DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			admin_id BIGINT UNSIGNED DEFAULT 0,
			type ENUM('Award','Deduct','Utilized') DEFAULT 'Award',
			this_trigger VARCHAR(100) DEFAULT '',
			credit INT DEFAULT 0,
			actual_date_earned DATETIME DEFAULT CURRENT_TIMESTAMP,
			date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY credit_id (credit_id),
			KEY type (type),
			KEY date_added (date_added)
		) {$charset};";
		dbDelta( $sql );

		// ─────────────────────────────────────────────────────────────────
		// 3. apollo_ranks — adapted from badgeos_ranks
		// ─────────────────────────────────────────────────────────────────
		$table = $prefix . APOLLO_TABLE_RANKS;
		$sql   = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			rank_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			rank_type VARCHAR(50) DEFAULT '',
			rank_title TEXT DEFAULT NULL,
			credit_id BIGINT UNSIGNED DEFAULT 0,
			credit_amount INT DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			admin_id BIGINT UNSIGNED DEFAULT 0,
			this_trigger VARCHAR(100) DEFAULT '',
			priority INT DEFAULT 0,
			actual_date_earned DATETIME DEFAULT CURRENT_TIMESTAMP,
			date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY rank_id (rank_id),
			KEY user_id (user_id),
			KEY rank_type (rank_type),
			KEY date_added (date_added)
		) {$charset};";
		dbDelta( $sql );

		// ─────────────────────────────────────────────────────────────────
		// 4. apollo_triggers — trigger event log
		// ─────────────────────────────────────────────────────────────────
		$table = $prefix . APOLLO_TABLE_TRIGGERS;
		$sql   = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			trigger_name VARCHAR(100) NOT NULL,
			trigger_count INT UNSIGNED DEFAULT 1,
			site_id BIGINT UNSIGNED DEFAULT 1,
			object_id BIGINT UNSIGNED DEFAULT 0,
			date_triggered DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY trigger_name (trigger_name),
			KEY date_triggered (date_triggered)
		) {$charset};";
		dbDelta( $sql );

		// ─────────────────────────────────────────────────────────────────
		// 5. apollo_steps — achievement/rank requirement steps
		// ─────────────────────────────────────────────────────────────────
		$table = $prefix . APOLLO_TABLE_STEPS;
		$sql   = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			step_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			parent_achievement_id BIGINT UNSIGNED DEFAULT 0,
			trigger_type VARCHAR(100) DEFAULT '',
			required_count INT UNSIGNED DEFAULT 1,
			point_value INT DEFAULT 0,
			step_order INT UNSIGNED DEFAULT 0,
			status VARCHAR(20) DEFAULT 'active',
			PRIMARY KEY (id),
			KEY step_id (step_id),
			KEY parent_achievement_id (parent_achievement_id),
			KEY trigger_type (trigger_type)
		) {$charset};";
		dbDelta( $sql );

		// ─────────────────────────────────────────────────────────────────
		// 6. apollo_membership_log — general activity log
		// ─────────────────────────────────────────────────────────────────
		$table = $prefix . APOLLO_TABLE_MEMBERSHIP_LOG;
		$sql   = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			admin_id BIGINT UNSIGNED DEFAULT 0,
			action VARCHAR(100) NOT NULL,
			object_type VARCHAR(50) DEFAULT '',
			object_id BIGINT UNSIGNED DEFAULT 0,
			details TEXT DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY object_type (object_type),
			KEY created_at (created_at)
		) {$charset};";
		dbDelta( $sql );

		self::migrate_triggers_table();
	}

	/**
	 * Set default settings
	 * Adapted from BadgeOS activation defaults
	 */
	private static function set_defaults(): void {
		$settings = get_option( 'apollo_membership_settings', array() );

		if ( empty( $settings ) ) {
			$settings = array(
				'minimum_role'        => 'manage_options',
				'debug_mode'          => 'disabled',
				'image_width'         => 50,
				'image_height'        => 50,
				'remove_on_uninstall' => 'no',
			);
			update_option( 'apollo_membership_settings', $settings );
		}
	}

	/**
	 * Seed the 13+1 membership badge types
	 * These are ADMIN-ONLY visual badges, NO relation to gamification points.
	 */
	private static function seed_membership_badges(): void {
		$existing = get_option( 'apollo_membership_badge_types_seeded', false );
		if ( $existing ) {
			return;
		}

		// Badge types are defined in constants.php APOLLO_MEMBERSHIP_BADGE_TYPES
		// They are used for admin-only assignment via _apollo_membership user meta

		update_option( 'apollo_membership_badge_types_seeded', true );
		update_option( 'apollo_membership_badge_types', APOLLO_MEMBERSHIP_BADGE_TYPES );

		// Log the seeding
		apollo_membership_log( 0, 'system', 'badge_types_seeded', 'option', 0, 'Seeded ' . count( APOLLO_MEMBERSHIP_BADGE_TYPES ) . ' membership badge types' );
	}
}
