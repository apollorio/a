<?php
/**
 * Apollo CoAuthor — Uninstall.
 *
 * Removes all plugin data on deletion:
 *   - `_coauthors` post meta from all posts.
 *   - `coauthor` taxonomy terms (if registered by this plugin).
 *   - Plugin options.
 *   - Transients / cache.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

// Exit if not called from WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Check if the user opted to keep data on uninstall.
 * Respects apollo-core's global setting if available.
 */
$keep_data = get_option( 'apollo_coauthor_keep_data', false );
if ( $keep_data ) {
	return;
}

global $wpdb;

/*
═══════════════════════════════════════════════════════════════════
 * §1 — Remove post meta
 * ═══════════════════════════════════════════════════════════════════ */
$wpdb->delete(
	$wpdb->postmeta,
	array( 'meta_key' => '_coauthors' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	array( '%s' )
);

/*
═══════════════════════════════════════════════════════════════════
 * §2 — Remove taxonomy terms
 * ═══════════════════════════════════════════════════════════════════ */
$taxonomy = 'coauthor';

// Get all terms in the coauthor taxonomy.
$terms = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT t.term_id, tt.term_taxonomy_id
		 FROM {$wpdb->terms} AS t
		 INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
		 WHERE tt.taxonomy = %s",
		$taxonomy
	)
);

if ( ! empty( $terms ) ) {
	foreach ( $terms as $term ) {
		// Remove relationships.
		$wpdb->delete(
			$wpdb->term_relationships,
			array( 'term_taxonomy_id' => $term->term_taxonomy_id ),
			array( '%d' )
		);

		// Remove term taxonomy.
		$wpdb->delete(
			$wpdb->term_taxonomy,
			array( 'term_taxonomy_id' => $term->term_taxonomy_id ),
			array( '%d' )
		);

		// Remove term.
		$wpdb->delete(
			$wpdb->terms,
			array( 'term_id' => $term->term_id ),
			array( '%d' )
		);

		// Remove term meta.
		$wpdb->delete(
			$wpdb->termmeta,
			array( 'term_id' => $term->term_id ),
			array( '%d' )
		);
	}
}

/*
═══════════════════════════════════════════════════════════════════
 * §3 — Remove options
 * ═══════════════════════════════════════════════════════════════════ */
delete_option( 'apollo_coauthor_version' );
delete_option( 'apollo_coauthor_keep_data' );
delete_option( 'apollo_coauthor_settings' );

/*
═══════════════════════════════════════════════════════════════════
 * §4 — Clear cache
 * ═══════════════════════════════════════════════════════════════════ */
wp_cache_flush_group( 'apollo_coauthor' );

// Clear any transients.
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '_transient_apollo_coauthor_%'
	    OR option_name LIKE '_transient_timeout_apollo_coauthor_%'"
);
