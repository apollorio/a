<?php
declare(strict_types=1);
namespace Apollo\Groups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activation {

	/**
	 * Columns the create/edit forms already submit but that core's
	 * `apollo_groups` schema never defined. Format: column => ALTER fragment.
	 */
	private const GROUPS_EXTRA_COLUMNS = array(
		'tags'  => "ADD COLUMN tags VARCHAR(500) NOT NULL DEFAULT '' AFTER description",
		'rules' => 'ADD COLUMN rules TEXT NULL AFTER tags',
	);

	public static function activate(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix . 'apollo_';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Table: apollo_group_meta (supplements groups tables from core)
		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$prefix}group_meta (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value LONGTEXT,
            PRIMARY KEY (id),
            KEY group_id (group_id),
            KEY meta_key (meta_key(191))
        ) {$charset}"
		);

		// Table: apollo_group_invitations (BuddyPress bp-groups invite pattern)
		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$prefix}group_invitations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL COMMENT 'Invitee',
            inviter_id BIGINT UNSIGNED NOT NULL,
            status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
            message TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_user_invite (group_id,user_id),
            KEY group_id (group_id),
            KEY user_id (user_id),
            KEY inviter_id (inviter_id),
            KEY status (status)
        ) {$charset}"
		);

		// Table: apollo_group_bans (BuddyPress is_banned pattern, Apollo-native)
		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$prefix}group_bans (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            banned_by BIGINT UNSIGNED NOT NULL,
            reason VARCHAR(500),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_user_ban (group_id,user_id),
            KEY group_id (group_id),
            KEY user_id (user_id)
        ) {$charset}"
		);

		// Table: apollo_group_requests (membership requests for future private comunas)
		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$prefix}group_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            message TEXT,
            status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
            handled_by BIGINT UNSIGNED,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_user_request (group_id,user_id),
            KEY group_id (group_id),
            KEY user_id (user_id),
            KEY status (status)
        ) {$charset}"
		);

		self::migrate();

		update_option( 'apollo_groups_version', APOLLO_GROUPS_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Run idempotent schema migrations against the core-owned groups tables.
	 * Safe to call on every request; each step is guarded by an existence check.
	 */
	public static function migrate(): void {
		global $wpdb;
		$prefix = $wpdb->prefix . 'apollo_';

		$groups_table = "{$prefix}groups";
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $groups_table ) ) === $groups_table ) {
			$existing = array_column(
				(array) $wpdb->get_results( "SHOW COLUMNS FROM {$groups_table}" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'Field'
			);
			foreach ( self::GROUPS_EXTRA_COLUMNS as $col => $fragment ) {
				if ( ! in_array( $col, $existing, true ) ) {
					$wpdb->query( "ALTER TABLE {$groups_table} {$fragment}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}
		}

		// Covering index for the admin-list lookup used by the multi-admin flow.
		$members_table = "{$prefix}group_members";
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $members_table ) ) === $members_table ) {
			$idx = $wpdb->get_var( "SHOW INDEX FROM {$members_table} WHERE Key_name = 'group_role'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $idx ) {
				$wpdb->query( "ALTER TABLE {$members_table} ADD INDEX group_role (group_id, role)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}
	}

	/**
	 * Apply migrations once per version bump (the plugin is already active on
	 * live installs, so `activate()` will not fire again).
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( 'apollo_groups_db_version' ) === APOLLO_GROUPS_VERSION ) {
			return;
		}
		self::migrate();
		update_option( 'apollo_groups_db_version', APOLLO_GROUPS_VERSION );
	}
}
