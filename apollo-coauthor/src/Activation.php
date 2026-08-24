<?php
/**
 * Apollo CoAuthor — Activation Handler.
 *
 * Runs on plugin activation: stores version, registers taxonomy
 * (fallback), and flushes rewrite rules.
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
 * Activation handler.
 *
 * @since 1.0.0
 */
final class Activation {

	/**
	 * Run activation tasks.
	 *
	 * @since 1.0.0
	 */
	public static function activate(): void {
		// Store version for future upgrade routines.
		$previous = get_option( 'apollo_coauthor_version', '0.0.0' );
		update_option( 'apollo_coauthor_version', APOLLO_COAUTHOR_VERSION );

		// Register taxonomy so rewrite rules can be flushed.
		if ( ! taxonomy_exists( APOLLO_COAUTHOR_TAX ) ) {
			$taxonomy = new Components\Taxonomy();
			$taxonomy->register_taxonomy();
		}

		// Flush rewrite rules for new taxonomy.
		flush_rewrite_rules( false );

		// First install: set defaults.
		if ( '0.0.0' === $previous ) {
			self::set_defaults();
		}

		/**
		 * Fires after apollo-coauthor activation.
		 *
		 * @since 1.0.0
		 *
		 * @param string $previous Previous version (or '0.0.0' on first install).
		 * @param string $current  Current version.
		 */
		do_action( 'apollo/coauthor/activated', $previous, APOLLO_COAUTHOR_VERSION );
	}

	/**
	 * Set default options on first install.
	 *
	 * @since 1.0.0
	 */
	private static function set_defaults(): void {
		add_option(
			'apollo_coauthor_settings',
			array(
				'append_byline'    => false,
				'show_in_archives' => true,
			)
		);
	}
}
