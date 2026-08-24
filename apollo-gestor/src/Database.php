<?php

/**
 * Database — creates and upgrades custom tables
 *
 * Tables:
 *   apollo_gestor_tasks      — Tasks linked to events
 *   apollo_gestor_team       — Team assignments per event
 *   apollo_gestor_payments   — Financial records
 *   apollo_gestor_milestones — Milestone checkpoints
 *   apollo_gestor_activity   — Activity log
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {


	/**
	 * Install all tables
	 */
	public static function install(): void {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ─── Tasks ────────────────────────────────────────────────
		$sql_tasks = "CREATE TABLE {$prefix}apollo_gestor_tasks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT,
            assignee_id BIGINT UNSIGNED DEFAULT 0,
            category VARCHAR(100) DEFAULT '',
            priority ENUM('urgent','high','medium','low') DEFAULT 'medium',
            status ENUM('planned','tostart','ongoing','delayed','canceled','delivered') DEFAULT 'planned',
            due_date DATE DEFAULT NULL,
            sort_order INT UNSIGNED DEFAULT 0,
            parent_id BIGINT UNSIGNED DEFAULT 0,
            reminder_enabled TINYINT(1) NOT NULL DEFAULT 0,
            reminder_offset VARCHAR(20) NOT NULL DEFAULT '24h',
            created_by BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_assignee (assignee_id),
            KEY idx_status (status),
            KEY idx_due (due_date),
            KEY idx_parent (parent_id),
            KEY idx_reminder (reminder_enabled, due_date)
        ) {$charset};";

		// ─── Team ─────────────────────────────────────────────────
		$sql_team = "CREATE TABLE {$prefix}apollo_gestor_team (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            role ENUM('adm','gestor','tgestor','team') DEFAULT 'team',
            job_function VARCHAR(255) DEFAULT '',
            pix_key VARCHAR(255) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_event_user (event_id, user_id),
            KEY idx_user (user_id),
            KEY idx_role (role)
        ) {$charset};";

		// ─── Staff ────────────────────────────────────────────────
		$sql_staff = "CREATE TABLE {$prefix}apollo_gestor_staff (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            name VARCHAR(255) NOT NULL DEFAULT '',
            pix_key VARCHAR(255) DEFAULT '',
            price_deal DECIMAL(10,2) DEFAULT 0.00,
            job_function VARCHAR(255) DEFAULT '',
            contact_info TEXT,
            created_by BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_name (name)
        ) {$charset};";

		// ─── Payments ─────────────────────────────────────────────
		$sql_payments = "CREATE TABLE {$prefix}apollo_gestor_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            payee_type ENUM('user','staff','supplier') DEFAULT 'user',
            payee_id BIGINT UNSIGNED DEFAULT 0,
            description VARCHAR(255) DEFAULT '',
            category VARCHAR(100) DEFAULT '',
            amount DECIMAL(10,2) DEFAULT 0.00,
            paid_amount DECIMAL(10,2) DEFAULT 0.00,
            pix_key VARCHAR(255) DEFAULT '',
            status ENUM('paid','pending','late') DEFAULT 'pending',
            due_date DATE DEFAULT NULL,
            paid_at DATETIME DEFAULT NULL,
            created_by BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_payee (payee_type, payee_id),
            KEY idx_status (status)
        ) {$charset};";

		// ─── Milestones ───────────────────────────────────────────
		$sql_milestones = "CREATE TABLE {$prefix}apollo_gestor_milestones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(255) NOT NULL DEFAULT '',
            icon VARCHAR(100) DEFAULT 'ri-flag-2-line',
            due_date DATE DEFAULT NULL,
            status ENUM('pending','done') DEFAULT 'pending',
            sort_order INT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_status (status)
        ) {$charset};";

		// ─── Activity log ─────────────────────────────────────────
		$sql_activity = "CREATE TABLE {$prefix}apollo_gestor_activity (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT UNSIGNED DEFAULT 0,
            action VARCHAR(100) NOT NULL DEFAULT '',
            entity_type VARCHAR(50) DEFAULT '',
            entity_id BIGINT UNSIGNED DEFAULT 0,
            meta TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_user (user_id),
            KEY idx_created (created_at)
        ) {$charset};";

		dbDelta( $sql_tasks );
		dbDelta( $sql_team );
		dbDelta( $sql_staff );
		dbDelta( $sql_payments );
		dbDelta( $sql_milestones );
		dbDelta( $sql_activity );

		// ─── Task Reminder Log ────────────────────────────────────
		$sql_reminder_log = "CREATE TABLE {$prefix}apollo_gestor_reminder_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            event_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            reminder_offset VARCHAR(20) NOT NULL DEFAULT '24h',
            email_status ENUM('sent','failed','skipped') NOT NULL DEFAULT 'sent',
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            meta TEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_task_user (task_id, user_id),
            KEY idx_sent (sent_at),
            KEY idx_event (event_id)
        ) {$charset};";

		dbDelta( $sql_reminder_log );

		// ─── Income / Receitas ───────────────────────────────────────────
		$sql_income = "CREATE TABLE {$prefix}apollo_gestor_income (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            category VARCHAR(100) DEFAULT 'outros',
            description VARCHAR(255) DEFAULT '',
            amount DECIMAL(10,2) DEFAULT 0.00,
            date DATE DEFAULT NULL,
            qty INT UNSIGNED DEFAULT 1,
            unit DECIMAL(10,2) DEFAULT 0.00,
            created_by BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_date (date)
        ) {$charset};";

		dbDelta( $sql_income );
	}

	/**
	 * Run data migrations when upgrading the DB schema.
	 *
	 * Must be called AFTER install() so tables already exist.
	 *
	 * @param int $from DB version being upgraded from.
	 */
	public static function upgrade( int $from ): void {
		global $wpdb;
		$prefix = $wpdb->prefix;

		if ( $from < 4 ) {
			// Extend payee_type ENUM to include 'user','staff','supplier','manual' (ALTER is safe even if already updated).
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"ALTER TABLE {$prefix}apollo_gestor_payments
				 MODIFY COLUMN payee_type ENUM('user','staff','supplier','manual') DEFAULT 'user'"
			);

			// Migrate _gestor_income_items post_meta JSON → apollo_gestor_income table.
			$meta_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'_gestor_income_items'
				),
				ARRAY_A
			);

			if ( ! empty( $meta_rows ) ) {
				$table = "{$prefix}apollo_gestor_income";
				foreach ( $meta_rows as $row ) {
					$items = maybe_unserialize( $row['meta_value'] );
					if ( ! is_array( $items ) ) {
						continue;
					}
					foreach ( $items as $item ) {
						$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
							$table,
							array(
								'event_id'    => absint( $row['post_id'] ),
								'category'    => sanitize_key( $item['category'] ?? 'outros' ),
								'description' => sanitize_text_field( $item['description'] ?? '' ),
								'amount'      => round( floatval( $item['amount'] ?? 0 ), 2 ),
								'date'        => ! empty( $item['date'] ) ? sanitize_text_field( $item['date'] ) : null,
								'qty'         => max( 1, absint( $item['qty'] ?? 1 ) ),
								'unit'        => round( floatval( $item['unit'] ?? 0 ), 2 ),
								'created_by'  => absint( $item['created_by'] ?? 0 ),
							),
							array( '%d', '%s', '%s', '%f', '%s', '%d', '%f', '%d' )
						);
					}
					delete_post_meta( absint( $row['post_id'] ), '_gestor_income_items' );
				}
			}
		}
	}

	/**
	 * Drop all tables (used by uninstall)
	 */
	public static function uninstall(): void {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$tables = array(
			'apollo_gestor_income',
			'apollo_gestor_reminder_log',
			'apollo_gestor_activity',
			'apollo_gestor_milestones',
			'apollo_gestor_payments',
			'apollo_gestor_team',
			'apollo_gestor_tasks',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		delete_option( 'apollo_gestor_db_version' );
	}
}
