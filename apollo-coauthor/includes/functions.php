<?php
/**
 * Apollo CoAuthor — Global Helper Functions.
 *
 * Public API consumed by other plugins and themes.
 * Adapted from Co-Authors Plus get_coauthors() / add_coauthors() patterns.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Singleton accessor ──────────────────────────────────────────── */

/**
 * Get plugin singleton.
 *
 * @since 1.0.0
 * @return \Apollo\CoAuthor\Plugin
 */
function apollo_coauthor(): \Apollo\CoAuthor\Plugin {
	return \Apollo\CoAuthor\Plugin::get_instance();
}

/*
═══════════════════════════════════════════════════════════════════
 * GET / SET Co-Authors
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Set co-authors for a post via taxonomy + meta.
 *
 * Creates taxonomy terms per user (cap-{nicename} slug) and stores
 * user IDs in _coauthors meta for fast lookups.
 *
 * @since 1.0.0
 *
 * @param int   $post_id  Post ID.
 * @param int[] $user_ids Array of user IDs.
 * @param bool  $append   Whether to append or replace.
 * @return bool
 */
function apollo_set_coauthors( int $post_id, array $user_ids, bool $append = false ): bool {
	if ( empty( $user_ids ) ) {
		return false;
	}

	/**
	 * Filter whether a co-author can be added.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $allowed  Whether the action is allowed.
	 * @param int   $post_id  The post ID.
	 * @param int[] $user_ids The user IDs being set.
	 */
	$allowed = apply_filters( 'apollo/coauthor/can_add', true, $post_id, $user_ids );
	if ( ! $allowed ) {
		return false;
	}

	$terms     = array();
	$valid_ids = array();

	foreach ( $user_ids as $uid ) {
		$user = get_userdata( (int) $uid );
		if ( ! $user ) {
			continue;
		}

		$term_slug = 'cap-' . $user->user_nicename;
		$term      = get_term_by( 'slug', $term_slug, APOLLO_COAUTHOR_TAX );

		if ( ! $term ) {
			$result = wp_insert_term(
				$user->display_name,
				APOLLO_COAUTHOR_TAX,
				array(
					'slug'        => $term_slug,
					'description' => wp_json_encode(
						array(
							'user_id'      => $user->ID,
							'display_name' => $user->display_name,
							'user_login'   => $user->user_login,
							'user_email'   => $user->user_email,
							'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
						)
					),
				)
			);
			if ( ! is_wp_error( $result ) ) {
				$terms[]     = (int) $result['term_id'];
				$valid_ids[] = $user->ID;
			}
		} else {
			$terms[]     = (int) $term->term_id;
			$valid_ids[] = $user->ID;
		}
	}

	if ( empty( $terms ) ) {
		return false;
	}

	$result = wp_set_object_terms( $post_id, $terms, APOLLO_COAUTHOR_TAX, $append );

	if ( is_wp_error( $result ) ) {
		return false;
	}

	// Store IDs in meta for fast lookups.
	if ( $append ) {
		$existing  = get_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY, true );
		$existing  = is_array( $existing ) ? $existing : array();
		$valid_ids = array_unique( array_merge( $existing, $valid_ids ) );
	}
	update_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY, array_values( $valid_ids ) );

	// Invalidate cache.
	wp_cache_delete( 'coauthors_' . $post_id, APOLLO_COAUTHOR_CACHE_GROUP );

	/**
	 * Fires after co-authors are set on a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id   The post ID.
	 * @param int[] $valid_ids Validated user IDs.
	 * @param bool  $append    Whether appended or replaced.
	 */
	do_action( 'apollo/coauthor/updated', $post_id, $valid_ids, $append );

	return true;
}

/**
 * Get co-authors for a post.
 *
 * Returns enriched author data from taxonomy terms.
 *
 * @since 1.0.0
 *
 * @param int $post_id Post ID.
 * @return array<int, array{user_id: int, display_name: string, user_login: string, avatar_url: string, profile_url: string}>
 */
function apollo_get_coauthors( int $post_id ): array {
	// Check cache first.
	$cached = wp_cache_get( 'coauthors_' . $post_id, APOLLO_COAUTHOR_CACHE_GROUP );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$terms = wp_get_object_terms(
		$post_id,
		APOLLO_COAUTHOR_TAX,
		array( 'orderby' => 'term_order' )
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		wp_cache_set( 'coauthors_' . $post_id, array(), APOLLO_COAUTHOR_CACHE_GROUP, 300 );
		return array();
	}

	$authors = array();
	foreach ( $terms as $term ) {
		$data = json_decode( $term->description, true );
		if ( ! is_array( $data ) || empty( $data['user_id'] ) ) {
			continue;
		}

		$user = get_userdata( (int) $data['user_id'] );
		if ( ! $user ) {
			continue;
		}

		$authors[] = array(
			'user_id'      => $user->ID,
			'display_name' => $user->display_name,
			'user_login'   => $user->user_login,
			'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
			'profile_url'  => home_url( '/id/' . $user->user_login ),
		);
	}

	/**
	 * Filter the coauthors data before returning.
	 *
	 * @since 1.0.0
	 *
	 * @param array $authors The coauthor data array.
	 * @param int   $post_id The post ID.
	 */
	$authors = apply_filters( 'apollo/coauthor/display_format', $authors, $post_id );

	wp_cache_set( 'coauthors_' . $post_id, $authors, APOLLO_COAUTHOR_CACHE_GROUP, 300 );

	return $authors;
}

/**
 * Add a single co-author to a post.
 *
 * @since 1.0.0
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID to add.
 * @return bool
 */
function apollo_add_coauthor( int $post_id, int $user_id ): bool {
	$result = apollo_set_coauthors( $post_id, array( $user_id ), true );

	if ( $result ) {
		/**
		 * Fires when a co-author is added to a post.
		 *
		 * @since 1.0.0
		 *
		 * @param int $post_id The post ID.
		 * @param int $user_id The user ID added.
		 */
		do_action( 'apollo/coauthor/added', $post_id, $user_id );
	}

	return $result;
}

/**
 * Remove a co-author from a post.
 *
 * @since 1.0.0
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID to remove.
 * @return bool
 */
function apollo_remove_coauthor( int $post_id, int $user_id ): bool {
	$current = get_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY, true );
	if ( ! is_array( $current ) ) {
		return false;
	}

	$updated = array_values( array_diff( $current, array( $user_id ) ) );

	if ( empty( $updated ) ) {
		delete_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY );
		wp_set_object_terms( $post_id, array(), APOLLO_COAUTHOR_TAX );
	} else {
		apollo_set_coauthors( $post_id, $updated );
	}

	wp_cache_delete( 'coauthors_' . $post_id, APOLLO_COAUTHOR_CACHE_GROUP );

	/**
	 * Fires when a co-author is removed from a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $user_id The user ID removed.
	 */
	do_action( 'apollo/coauthor/removed', $post_id, $user_id );

	return true;
}

/**
 * Check if a user is a co-author of a post.
 *
 * @since 1.0.0
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID.
 * @return bool
 */
function apollo_is_coauthor( int $post_id, int $user_id ): bool {
	$coauthors = get_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY, true );
	if ( ! is_array( $coauthors ) ) {
		return false;
	}
	return in_array( $user_id, $coauthors, true );
}

/**
 * Get all posts where user is a co-author.
 *
 * @since 1.0.0
 *
 * @param int    $user_id   User ID.
 * @param string $post_type Post type slug or 'any'.
 * @param int    $limit     Max posts.
 * @return \WP_Post[]
 */
function apollo_get_coauthor_posts( int $user_id, string $post_type = 'any', int $limit = 20 ): array {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return array();
	}

	$term_slug = 'cap-' . $user->user_nicename;
	$term      = get_term_by( 'slug', $term_slug, APOLLO_COAUTHOR_TAX );
	if ( ! $term ) {
		return array();
	}

	$query = new \WP_Query(
		array(
			'post_type'      => $post_type,
			'posts_per_page' => $limit,
			'tax_query'      => array(
				array(
					'taxonomy' => APOLLO_COAUTHOR_TAX,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
			'post_status'    => 'publish',
		)
	);

	return $query->posts;
}

/**
 * Get supported post types for co-authoring.
 *
 * @since 1.0.0
 * @return string[]
 */
function apollo_coauthor_get_supported_post_types(): array {
	/**
	 * Filter supported post types for co-authoring.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $post_types Array of post type slugs.
	 */
	return apply_filters( 'apollo/coauthor/supported_post_types', APOLLO_COAUTHOR_POST_TYPES );
}

/**
 * Get co-author count for a post.
 *
 * @since 1.0.0
 *
 * @param int $post_id Post ID.
 * @return int
 */
function apollo_coauthor_count( int $post_id ): int {
	$ids = get_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY, true );
	return is_array( $ids ) ? count( $ids ) : 0;
}
