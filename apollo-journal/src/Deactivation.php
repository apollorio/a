<?php

/**
 * Plugin Deactivation Handler
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation handler.
 */
class Deactivation {


	/**
	 * Run deactivation tasks.
	 *
	 * Data is preserved — only deleted on uninstall.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::clear_cron();
		self::clear_transients();

		// Flush rewrite rules so CPT/rewrite slugs are removed.
		flush_rewrite_rules( false );
	}

	/**
	 * Clear scheduled cron events.
	 *
	 * @return void
	 */
	private static function clear_cron(): void {
		wp_clear_scheduled_hook( 'apollo_journal_daily_digest' );
	}

	/**
	 * Clear transients.
	 *
	 * @return void
	 */
	private static function clear_transients(): void {
		delete_transient( 'apollo_journal_stats' );
	}
}
