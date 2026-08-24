<?php
/**
 * Apollo CoAuthor — Deactivation Handler.
 *
 * Runs on plugin deactivation: flushes rewrite rules.
 * Data is preserved — use uninstall.php for full cleanup.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation handler.
 *
 * @since 1.0.0
 */
final class Deactivation {

	/**
	 * Run deactivation tasks.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate(): void {
		// Flush rewrite rules to remove taxonomy rules.
		flush_rewrite_rules( false );

		/**
		 * Fires after apollo-coauthor deactivation.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apollo/coauthor/deactivated' );
	}
}
