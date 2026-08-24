<?php
/**
 * Apollo Calendar — Activation
 *
 * Creates apollo_user_appointments and apollo_holidays tables via dbDelta,
 * seeds holidays for current + next year, and flushes rewrite rules.
 *
 * @package Apollo\Calendar
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar;

use Apollo\Calendar\Holidays\Seeder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activation {

	public static function activate(): void {
		self::create_tables();
		Seeder::seed_years( (int) gmdate( 'Y' ), (int) gmdate( 'Y' ) + 1 );
		self::register_rewrite_rules();
		flush_rewrite_rules();
	}

	// ─── Table creation ──────────────────────────────────────────────

	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		// ── Personal appointments (private to each user) ──────────────
		$t1 = $prefix . 'apollo_user_appointments';
		dbDelta(
			"CREATE TABLE {$t1} (
				id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id        BIGINT UNSIGNED NOT NULL,
				title          VARCHAR(255) NOT NULL DEFAULT '',
				description    TEXT NOT NULL,
				starts_at      DATETIME NOT NULL COMMENT 'UTC',
				ends_at        DATETIME DEFAULT NULL COMMENT 'UTC',
				all_day        TINYINT(1) NOT NULL DEFAULT 0,
				location       VARCHAR(255) NOT NULL DEFAULT '',
				color          VARCHAR(16) NOT NULL DEFAULT '',
				recurrence     VARCHAR(30) NOT NULL DEFAULT 'none',
				remind_before  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'minutes before start',
				source         VARCHAR(30) NOT NULL DEFAULT 'personal',
				created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY starts_at (starts_at),
				KEY user_range (user_id, starts_at, ends_at)
			) {$charset}"
		);

		// ── Holidays (BR national + state + municipal) ─────────────────
		$t2 = $prefix . 'apollo_holidays';
		dbDelta(
			"CREATE TABLE {$t2} (
				id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				holiday_date DATE NOT NULL,
				title        VARCHAR(255) NOT NULL DEFAULT '',
				scope        VARCHAR(20) NOT NULL DEFAULT 'national',
				region       VARCHAR(50) NOT NULL DEFAULT '',
				recurring    TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'auto-seed each year',
				year         SMALLINT UNSIGNED NOT NULL,
				source       VARCHAR(30) NOT NULL DEFAULT 'seeded',
				created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY date_scope_region (holiday_date, scope, region),
				KEY holiday_date (holiday_date),
				KEY scope (scope)
			) {$charset}"
		);
	}

	// ─── Rewrite rules ───────────────────────────────────────────────

	public static function register_rewrite_rules(): void {
		add_rewrite_rule(
			'^agenda/?$',
			'index.php?apollo_calendar_page=1',
			'top'
		);
	}
}
