<?php
/**
 * Apollo Activation
 *
 * Handles plugin activation tasks including table creation
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {

	public static function install_tables(): void {
		self::check_requirements();
		self::create_audit_table();
	}

	private static function check_requirements(): void {
		$php_version = '8.1';
		$wp_version  = '6.4';

		if ( version_compare( PHP_VERSION, $php_version, '<' ) ) {
			wp_die(
				sprintf(
					'Apollo Core requires PHP %s or higher. You are running PHP %s.',
					$php_version,
					PHP_VERSION
				),
				'Apollo Core Activation Error',
				array( 'back_link' => true )
			);
		}

		global $wp_version;
		if ( version_compare( $wp_version, $wp_version, '<' ) ) {
			wp_die(
				sprintf(
					'Apollo Core requires WordPress %s or higher.',
					$wp_version
				),
				'Apollo Core Activation Error',
				array( 'back_link' => true )
			);
		}
	}

	private static function create_audit_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'apollo_audit_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            action varchar(100) NOT NULL,
            source varchar(50) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            details longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action_idx (action),
            KEY source_idx (source),
            KEY user_id_idx (user_id),
            KEY created_at_idx (created_at)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
