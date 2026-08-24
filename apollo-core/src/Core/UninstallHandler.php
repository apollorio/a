<?php
/**
 * Apollo Core - Uninstall Options Handler
 *
 * Handles uninstall behavior with admin choice.
 * DEFAULT: Keep all data (safe uninstall)
 * OPTIONAL: Delete all data (requires explicit admin confirmation)
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uninstall Handler
 */
final class UninstallHandler {

	/**
	 * Option name for uninstall behavior
	 */
	const OPTION_DELETE_DATA = 'apollo_uninstall_delete_data';

	/**
	 * Check if admin chose to delete data on uninstall
	 *
	 * DEFAULT: false (keep data)
	 */
	public static function should_delete_data(): bool {
		return (bool) get_option( self::OPTION_DELETE_DATA, false );
	}

	/**
	 * Set delete data preference
	 */
	public static function set_delete_data( bool $delete ): void {
		update_option( self::OPTION_DELETE_DATA, $delete );
	}

	/**
	 * Execute uninstall
	 *
	 * Called from uninstall.php
	 */
	public static function uninstall(): void {
		// Only proceed if admin chose to delete data
		if ( ! self::should_delete_data() ) {
			// Log that data was preserved
			error_log( '[Apollo Core] Uninstall: Data preserved by admin choice.' );
			return;
		}

		global $wpdb;

		// ═══════════════════════════════════════════════════════════════
		// 1. Drop all Apollo database tables
		// ═══════════════════════════════════════════════════════════════
		$builder        = new DatabaseBuilder();
		$dropped_tables = $builder->drop_all_tables();

		// ═══════════════════════════════════════════════════════════════
		// 2. Delete all Apollo options
		// ═══════════════════════════════════════════════════════════════
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'apollo_%' ) );

		// ═══════════════════════════════════════════════════════════════
		// 3. Delete all Apollo user meta
		// ═══════════════════════════════════════════════════════════════
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", '_apollo_%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", 'apollo_%' ) );

		// ═══════════════════════════════════════════════════════════════
		// 4. Delete all Apollo post meta
		// ═══════════════════════════════════════════════════════════════
		$meta_prefixes = array(
			'_event_%',
			'_dj_%',
			'_loc_%',
			'_classified_%',
			'_supplier_%',
			'_doc_%',
			'_hub_%',
			'_email_%',
			'_fav_%',
			'_wow_%',
			'_mod_%',
			'_coauthors',
		);

		foreach ( $meta_prefixes as $prefix ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
					$prefix
				)
			);
		}

		// ═══════════════════════════════════════════════════════════════
		// 5. Delete all Apollo CPT posts (with their meta)
		// ═══════════════════════════════════════════════════════════════
		$cpt_slugs = array( 'event', 'dj', 'local', 'classified', 'supplier', 'doc', 'email_aprio', 'hub' );

		foreach ( $cpt_slugs as $cpt ) {
			$posts = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
					$cpt
				)
			);

			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		// ═══════════════════════════════════════════════════════════════
		// 6. Delete all Apollo taxonomies terms
		// ═══════════════════════════════════════════════════════════════
		$taxonomies = array(
			'sound',
			'season',
			'event_category',
			'event_type',
			'event_tag',
			'local_type',
			'local_area',
			'classified_domain',
			'classified_intent',
			'supplier_category',
			'supplier_service',
			'doc_folder',
			'doc_type',
			'coauthor',
		);

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term_id ) {
					wp_delete_term( $term_id, $taxonomy );
				}
			}
		}

		// ═══════════════════════════════════════════════════════════════
		// 7. Delete transients
		// ═══════════════════════════════════════════════════════════════
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_apollo_%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_apollo_%' ) );

		// ═══════════════════════════════════════════════════════════════
		// 8. Clear any scheduled cron events
		// ═══════════════════════════════════════════════════════════════
		$cron_hooks = array(
			'apollo_cleanup_expired_sessions',
			'apollo_cleanup_old_lockouts',
			'apollo_process_email_queue',
			'apollo_cleanup_audit_log',
			'apollo_daily_statistics',
		);

		foreach ( $cron_hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}

		// ═══════════════════════════════════════════════════════════════
		// 9. Flush rewrite rules
		// ═══════════════════════════════════════════════════════════════
		flush_rewrite_rules();

		// Log completion
		error_log( '[Apollo Core] Uninstall: All Apollo data has been deleted.' );
	}

	/**
	 * Deactivate plugin (soft - keeps data)
	 *
	 * Called on plugin deactivation
	 */
	public static function deactivate(): void {
		// Just flush rewrite rules
		flush_rewrite_rules();

		// Clear transients
		delete_transient( 'apollo_cpt_registered' );
		delete_transient( 'apollo_taxonomy_registered' );

		// Log
		apollo_log_audit(
			'plugin:deactivated',
			'apollo-core',
			null,
			array(
				'version' => APOLLO_VERSION,
			)
		);
	}
}
