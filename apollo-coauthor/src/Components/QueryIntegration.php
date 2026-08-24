<?php
/**
 * Apollo CoAuthor — Query Integration Component.
 *
 * Modifies WP_Query to include co-authored posts in author archives and
 * other relevant queries.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor\Components;

use WP_Query;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Query integration for co-authored content.
 *
 * @since 1.0.0
 */
class QueryIntegration {

	/**
	 * Constructor — hooks into pre_get_posts.
	 */
	public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'filter_author_archive' ) );
		add_filter( 'posts_where', array( $this, 'filter_posts_where' ), 10, 2 );
		add_filter( 'posts_join', array( $this, 'filter_posts_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( $this, 'filter_posts_groupby' ), 10, 2 );
	}

	/**
	 * Modify author archives to include co-authored posts.
	 *
	 * On an author archive, we flag the query so our SQL filters can augment
	 * the WHERE clause to include posts where the user is a co-author via
	 * the taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query Query object.
	 */
	public function filter_author_archive( WP_Query $query ): void {
		// Only main query on author archives.
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_author() ) {
			return;
		}

		$author_id = $query->get( 'author' );
		if ( ! $author_id ) {
			// Try author_name.
			$author_name = $query->get( 'author_name' );
			if ( $author_name ) {
				$user = get_user_by( 'slug', $author_name );
				if ( $user ) {
					$author_id = $user->ID;
				}
			}
		}

		if ( ! $author_id ) {
			return;
		}

		// Flag this query for our SQL filters.
		$query->set( 'apollo_coauthor_include', (int) $author_id );

		// Include all supported post types in author archive.
		$post_types = apollo_coauthor_get_supported_post_types();
		$query->set( 'post_type', $post_types );
	}

	/**
	 * Join the taxonomy tables for co-author queries.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $join  SQL JOIN clause.
	 * @param WP_Query $query Query object.
	 * @return string
	 */
	public function filter_posts_join( string $join, WP_Query $query ): string {
		global $wpdb;

		$author_id = $query->get( 'apollo_coauthor_include' );
		if ( ! $author_id ) {
			return $join;
		}

		$join .= " LEFT JOIN {$wpdb->term_relationships} AS apollo_tr ON ({$wpdb->posts}.ID = apollo_tr.object_id)";
		$join .= " LEFT JOIN {$wpdb->term_taxonomy} AS apollo_tt ON (apollo_tr.term_taxonomy_id = apollo_tt.term_taxonomy_id AND apollo_tt.taxonomy = '" . esc_sql( APOLLO_COAUTHOR_TAX ) . "')";
		$join .= " LEFT JOIN {$wpdb->terms} AS apollo_t ON (apollo_tt.term_id = apollo_t.term_id)";

		return $join;
	}

	/**
	 * Modify WHERE to include co-authored posts.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $where SQL WHERE clause.
	 * @param WP_Query $query Query object.
	 * @return string
	 */
	public function filter_posts_where( string $where, WP_Query $query ): string {
		global $wpdb;

		$author_id = $query->get( 'apollo_coauthor_include' );
		if ( ! $author_id ) {
			return $where;
		}

		$user = get_userdata( (int) $author_id );
		if ( ! $user ) {
			return $where;
		}

		$term_slug = 'cap-' . $user->user_nicename;

		// Replace the standard author clause with one that includes co-authored posts.
		$where = preg_replace(
			"/AND\s+{$wpdb->posts}\.post_author\s+IN\s*\(\s*" . (int) $author_id . '\s*\)/',
			"AND ({$wpdb->posts}.post_author = " . (int) $author_id . " OR apollo_t.slug = '" . esc_sql( $term_slug ) . "')",
			$where
		);

		return $where;
	}

	/**
	 * Add GROUP BY to prevent duplicate results.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $groupby SQL GROUP BY clause.
	 * @param WP_Query $query   Query object.
	 * @return string
	 */
	public function filter_posts_groupby( string $groupby, WP_Query $query ): string {
		global $wpdb;

		$author_id = $query->get( 'apollo_coauthor_include' );
		if ( ! $author_id ) {
			return $groupby;
		}

		$new_groupby = "{$wpdb->posts}.ID";
		if ( ! str_contains( $groupby, $new_groupby ) ) {
			$groupby = $new_groupby;
		}

		return $groupby;
	}
}
