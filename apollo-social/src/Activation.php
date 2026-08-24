<?php

declare(strict_types=1);

namespace Apollo\Social;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activation {

	public static function activate(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix . 'apollo_';

		// Table: apollo_activity (activity stream — not in DatabaseBuilder)
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}activity (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            component VARCHAR(50) NOT NULL DEFAULT 'social',
            type VARCHAR(50) NOT NULL,
            action_text TEXT,
            content LONGTEXT,
            primary_link VARCHAR(500),
            item_id BIGINT UNSIGNED DEFAULT 0,
            secondary_item_id BIGINT UNSIGNED DEFAULT 0,
            hide_sitewide TINYINT(1) DEFAULT 0,
            is_spam TINYINT(1) DEFAULT 0,
            is_edited TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY component (component),
            KEY type (type),
            KEY item_id (item_id),
            KEY secondary_item_id (secondary_item_id),
            KEY created_at (created_at)
        ) {$charset}";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Migrate existing installs: add columns if missing
		$cols = $wpdb->get_col( "DESC {$prefix}activity", 0 );
		if ( ! in_array( 'is_edited', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$prefix}activity ADD COLUMN is_edited TINYINT(1) DEFAULT 0 AFTER is_spam" );
		}
		if ( ! in_array( 'secondary_item_id', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$prefix}activity ADD COLUMN secondary_item_id BIGINT UNSIGNED DEFAULT 0 AFTER item_id" );
			$wpdb->query( "ALTER TABLE {$prefix}activity ADD KEY secondary_item_id (secondary_item_id)" );
		}

		// Auto-connect all existing users on activation
		self::auto_connect_all_users();

		update_option( 'apollo_social_version', APOLLO_SOCIAL_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Auto-connect all existing users (no friends/followers — everyone is connected)
	 */
	private static function auto_connect_all_users(): void {
		global $wpdb;
		$prefix = $wpdb->prefix . 'apollo_';

		$user_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} ORDER BY ID ASC" );
		if ( count( $user_ids ) < 2 ) {
			return;
		}

		// Insert mutual follows for all pairs (batch)
		$values = array();
		$now    = current_time( 'mysql' );
		foreach ( $user_ids as $i => $uid_a ) {
			foreach ( $user_ids as $j => $uid_b ) {
				if ( $i >= $j ) {
					continue; // avoid duplicates and self
				}
				$values[] = $wpdb->prepare( '(%d, %d, %s)', $uid_a, $uid_b, $now );
				$values[] = $wpdb->prepare( '(%d, %d, %s)', $uid_b, $uid_a, $now );
			}
			// Batch insert every 500 pairs
			if ( count( $values ) >= 500 ) {
				$wpdb->query( "INSERT IGNORE INTO {$prefix}follows (follower_id, following_id, created_at) VALUES " . implode( ',', $values ) );
				$values = array();
			}
		}
		if ( ! empty( $values ) ) {
			$wpdb->query( "INSERT IGNORE INTO {$prefix}follows (follower_id, following_id, created_at) VALUES " . implode( ',', $values ) );
		}
	}
}
