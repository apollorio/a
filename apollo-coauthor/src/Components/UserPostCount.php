<?php
/**
 * Apollo CoAuthor — User Post Count Component.
 *
 * Filters `get_usernumposts` so that users who are co-authors on
 * posts get those posts counted toward their total, rather than only
 * counting posts where they are the primary `post_author`.
 *
 * Adapted from Co-Authors Plus filter_count_user_posts /
 * get_post_count_for_author_term.
 *
 * @package Apollo\CoAuthor
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor\Components;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User post count that includes co-authored posts.
 *
 * @since 1.1.0
 */
class UserPostCount {

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_filter( 'get_usernumposts', array( $this, 'filter_count_user_posts' ), 10, 4 );
	}

	/**
	 * Filter the post count for a user to include co-authored posts.
	 *
	 * Adapted from Co-Authors Plus filter_count_user_posts.
	 *
	 * @param int          $count       Current post count.
	 * @param int          $user_id     User ID.
	 * @param string|array $post_type   Post type(s).
	 * @param bool         $public_only Whether to count only public posts.
	 * @return int
	 */
	public function filter_count_user_posts( int $count, int $user_id, $post_type = 'post', bool $public_only = false ): int {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return $count;
		}

		// Find the coauthor taxonomy term for this user.
		$term_slug = 'cap-' . $user->user_nicename;
		$term      = get_term_by( 'slug', $term_slug, APOLLO_COAUTHOR_TAX );

		if ( ! $term instanceof \WP_Term ) {
			return $count;
		}

		// For the default 'post' type, allow filtering.
		$is_default = ( 'post' === $post_type || ( is_array( $post_type ) && array( 'post' ) === $post_type ) );
		if ( $is_default ) {
			/**
			 * Filter the post types counted for a co-author.
			 *
			 * @since 1.1.0
			 *
			 * @param string[] $post_types Default: ['post'].
			 */
			$post_type = apply_filters( 'apollo/coauthor/count_post_types', array( 'post' ) );
		}

		$coauthor_count = $this->get_count_for_term( $term, (array) $post_type, $public_only );

		// The built-in $count already includes posts where this user is post_author.
		// Posts in both categories would be double-counted, so we use the larger value.
		return max( $count, $coauthor_count );
	}

	/**
	 * Count posts associated with a coauthor taxonomy term.
	 *
	 * Adapted from Co-Authors Plus get_post_count_for_author_term.
	 *
	 * @param \WP_Term $term        Author term.
	 * @param string[] $post_types  Post types to count.
	 * @param bool     $public_only Only count public statuses.
	 * @return int
	 */
	private function get_count_for_term( \WP_Term $term, array $post_types, bool $public_only ): int {
		$cache_key = 'count_' . $term->term_id . '_' . md5( implode( ',', $post_types ) . ( $public_only ? '_pub' : '' ) );
		$cached    = wp_cache_get( $cache_key, APOLLO_COAUTHOR_CACHE_GROUP );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$args = array(
			'tax_query'              => array(
				array(
					'taxonomy' => APOLLO_COAUTHOR_TAX,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
			'post_type'              => $post_types,
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
		);

		if ( $public_only ) {
			$args['post_status'] = array( 'publish' );
		} else {
			$args['post_status'] = array( 'publish', 'private' );
		}

		$query = new \WP_Query( $args );
		$total = $query->post_count;

		wp_cache_set( $cache_key, $total, APOLLO_COAUTHOR_CACHE_GROUP, 3600 );

		return $total;
	}
}
