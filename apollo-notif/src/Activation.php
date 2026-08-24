<?php
declare(strict_types=1);
namespace Apollo\Notif;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activation {

	/**
	 * Columns to add to the core `apollo_notifications` table.
	 * Format: column_name => ALTER TABLE SQL fragment.
	 */
	private const NOTIF_EXTRA_COLUMNS = array(
		'severity'       => "ADD COLUMN severity ENUM('info','success','warning','alert') NOT NULL DEFAULT 'info' AFTER type",
		'expires_at'     => 'ADD COLUMN expires_at DATETIME NULL AFTER created_at',
		'displayed_at'   => 'ADD COLUMN displayed_at DATETIME NULL AFTER expires_at',
		'icon'           => 'ADD COLUMN icon VARCHAR(100) NULL',
		'is_dismissible' => 'ADD COLUMN is_dismissible TINYINT(1) NOT NULL DEFAULT 1',
		'action_label'   => 'ADD COLUMN action_label VARCHAR(150) NULL',
		'action_link'    => 'ADD COLUMN action_link VARCHAR(500) NULL',
		'channel'        => 'ADD COLUMN channel VARCHAR(60) NULL',
	);

	/**
	 * Columns to add to the plugin-owned `apollo_notif_prefs` table.
	 * Format: column_name => ALTER TABLE SQL fragment.
	 */
	private const PREFS_EXTRA_COLUMNS = array(
		'snoozed_until' => 'ADD COLUMN snoozed_until DATETIME NULL',
	);

	public static function activate(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix . 'apollo_';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ── 1. Plugin-owned preferences table ──────────────────────────────
		$sql_prefs = "CREATE TABLE IF NOT EXISTS {$prefix}notif_prefs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			notif_type VARCHAR(50) NOT NULL,
			channel ENUM('in_app','email','both','none') NOT NULL DEFAULT 'both',
			snoozed_until DATETIME NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_type (user_id, notif_type)
		) {$charset}";
		dbDelta( $sql_prefs );

		// ── 2. Extend core apollo_notifications table if it exists ──────────
		$notif_table = "{$prefix}notifications";
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $notif_table ) ) === $notif_table ) {
			$existing = array_column(
				(array) $wpdb->get_results( "SHOW COLUMNS FROM {$notif_table}" ),
				'Field'
			);
			foreach ( self::NOTIF_EXTRA_COLUMNS as $col => $fragment ) {
				if ( ! in_array( $col, $existing, true ) ) {
					$wpdb->query( "ALTER TABLE {$notif_table} {$fragment}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}

			// Add index on expires_at for cleanup performance (safe if already exists)
			$idx = $wpdb->get_var( "SHOW INDEX FROM {$notif_table} WHERE Key_name = 'idx_notif_expires'" );
			if ( ! $idx ) {
				$wpdb->query( "ALTER TABLE {$notif_table} ADD INDEX idx_notif_expires (expires_at)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			// Add index on channel for faster filtering
			$idx_ch = $wpdb->get_var( "SHOW INDEX FROM {$notif_table} WHERE Key_name = 'idx_notif_channel'" );
			if ( ! $idx_ch ) {
				$wpdb->query( "ALTER TABLE {$notif_table} ADD INDEX idx_notif_channel (user_id, channel)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		// ── 3. Add snoozed_until to existing prefs table if missing ─────────
		$prefs_table = "{$prefix}notif_prefs";
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $prefs_table ) ) === $prefs_table ) {
			$existing_prefs = array_column(
				(array) $wpdb->get_results( "SHOW COLUMNS FROM {$prefs_table}" ),
				'Field'
			);
			foreach ( self::PREFS_EXTRA_COLUMNS as $col => $fragment ) {
				if ( ! in_array( $col, $existing_prefs, true ) ) {
					$wpdb->query( "ALTER TABLE {$prefs_table} {$fragment}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}
		}

		update_option( 'apollo_notif_version', APOLLO_NOTIF_VERSION );
		flush_rewrite_rules();
	}
}
