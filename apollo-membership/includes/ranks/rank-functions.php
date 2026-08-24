<?php
/**
 * Rank Functions
 *
 * Rank CRUD and user rank management.
 * Adapted from BadgeOS ranks/rank-functions.php.
 *
 * Ranks are level-based progression tied to point totals.
 * Unlike membership badges (admin-only), ranks advance automatically.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// GET USER RANK — adapted from badgeos_get_user_rank()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get a user's current rank
 *
 * @param int $user_id User ID (0 = current)
 * @return object|null Rank object or null
 */
function apollo_get_user_rank( int $user_id = 0 ): ?object {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return null;
	}

	$rank_id = (int) get_user_meta( $user_id, '_apollo_current_rank_id', true );

	if ( ! $rank_id ) {
		// Return default rank
		return apollo_get_default_rank();
	}

	$post = get_post( $rank_id );
	if ( ! $post ) {
		return apollo_get_default_rank();
	}

	return apollo_build_rank_object( $rank_id );
}

/**
 * Get a user's current rank ID
 * Adapted from badgeos_get_user_rank_id()
 *
 * @param int $user_id
 * @return int
 */
function apollo_get_user_rank_id( int $user_id = 0 ): int {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	return (int) get_user_meta( $user_id, '_apollo_current_rank_id', true );
}

// ═══════════════════════════════════════════════════════════════════════════
// RANK OBJECT BUILDING — adapted from badgeos rank functions
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Build rank object from post
 *
 * @param int $rank_id
 * @return object|null
 */
function apollo_build_rank_object( int $rank_id ): ?object {
	$post = get_post( $rank_id );
	if ( ! $post ) {
		return null;
	}

	$rank              = new \stdClass();
	$rank->ID          = $rank_id;
	$rank->title       = $post->post_title;
	$rank->description = $post->post_content;
	$rank->post_type   = $post->post_type;
	$rank->priority    = (int) get_post_meta( $rank_id, '_rank_priority', true );
	$rank->points      = (int) get_post_meta( $rank_id, '_rank_points_required', true );
	$rank->image       = get_the_post_thumbnail_url( $rank_id, 'thumbnail' ) ?: APOLLO_MEMBERSHIP_DEFAULT_RANK_IMAGE;

	return apply_filters( 'apollo_rank_object', $rank, $rank_id );
}

/**
 * Get the default (lowest) rank
 * Adapted from badgeos_is_default_rank()
 *
 * @return object
 */
function apollo_get_default_rank(): object {
	$default              = new \stdClass();
	$default->ID          = 0;
	$default->title       = __( 'Membro', 'apollo-membership' );
	$default->description = __( 'Membro padrão da comunidade', 'apollo-membership' );
	$default->post_type   = 'apollo_rank';
	$default->priority    = 0;
	$default->points      = 0;
	$default->image       = APOLLO_MEMBERSHIP_DEFAULT_RANK_IMAGE;

	return apply_filters( 'apollo_default_rank', $default );
}

// ═══════════════════════════════════════════════════════════════════════════
// RANK QUERIES — adapted from badgeos_get_ranks_by_unlock_points(), etc.
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get next rank for a user based on their points
 * Adapted from badgeos_get_next_rank_id()
 *
 * @param int $user_id
 * @return object|null Next rank object or null if at max
 */
function apollo_get_next_rank( int $user_id ): ?object {
	$current_rank     = apollo_get_user_rank( $user_id );
	$current_priority = $current_rank ? $current_rank->priority : 0;

	$ranks = get_posts(
		array(
			'post_type'      => 'apollo_rank',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_rank_priority',
			'meta_value'     => $current_priority,
			'meta_compare'   => '>',
			'meta_type'      => 'NUMERIC',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		)
	);

	if ( empty( $ranks ) ) {
		return null;
	}

	return apollo_build_rank_object( $ranks[0]->ID );
}

/**
 * Get previous rank
 * Adapted from badgeos_get_prev_rank_id()
 *
 * @param int $user_id
 * @return object|null
 */
function apollo_get_previous_rank( int $user_id ): ?object {
	$current_rank     = apollo_get_user_rank( $user_id );
	$current_priority = $current_rank ? $current_rank->priority : 0;

	if ( $current_priority <= 0 ) {
		return null;
	}

	$ranks = get_posts(
		array(
			'post_type'      => 'apollo_rank',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_rank_priority',
			'meta_value'     => $current_priority,
			'meta_compare'   => '<',
			'meta_type'      => 'NUMERIC',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		)
	);

	if ( empty( $ranks ) ) {
		return null;
	}

	return apollo_build_rank_object( $ranks[0]->ID );
}

/**
 * Get all ranks ordered by priority
 *
 * @return array
 */
function apollo_get_all_ranks(): array {
	$posts = get_posts(
		array(
			'post_type'      => 'apollo_rank',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_rank_priority',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		)
	);

	$ranks = array();
	foreach ( $posts as $post ) {
		$rank = apollo_build_rank_object( $post->ID );
		if ( $rank ) {
			$ranks[] = $rank;
		}
	}

	return $ranks;
}

/**
 * Get ranks achievable at a given point total
 * Adapted from badgeos_get_ranks_by_unlock_points()
 *
 * @param int $points
 * @return array
 */
function apollo_get_ranks_by_points( int $points ): array {
	$posts = get_posts(
		array(
			'post_type'      => 'apollo_rank',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_rank_points_required',
			'meta_value'     => $points,
			'meta_compare'   => '<=',
			'meta_type'      => 'NUMERIC',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		)
	);

	$ranks = array();
	foreach ( $posts as $post ) {
		$rank = apollo_build_rank_object( $post->ID );
		if ( $rank ) {
			$ranks[] = $rank;
		}
	}

	return $ranks;
}

// ═══════════════════════════════════════════════════════════════════════════
// AWARD / REVOKE RANK — adapted from badgeos rank awarding
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Award a rank to a user
 *
 * @param int    $rank_id
 * @param int    $user_id
 * @param string $trigger
 * @param int    $admin_id
 * @return bool
 */
function apollo_award_rank_to_user( int $rank_id, int $user_id, string $trigger = '', int $admin_id = 0 ): bool {
	global $wpdb;

	$rank = apollo_build_rank_object( $rank_id );
	if ( ! $rank ) {
		return false;
	}

	$table = $wpdb->prefix . APOLLO_TABLE_RANKS;

	$wpdb->insert(
		$table,
		array(
			'rank_id'            => $rank_id,
			'rank_type'          => $rank->post_type,
			'rank_title'         => $rank->title,
			'credit_id'          => 0,
			'credit_amount'      => $rank->points,
			'user_id'            => $user_id,
			'admin_id'           => $admin_id,
			'this_trigger'       => $trigger,
			'priority'           => $rank->priority,
			'actual_date_earned' => apollo_membership_default_datetime(),
			'dateadded'          => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s' )
	);

	// Update user's current rank
	update_user_meta( $user_id, '_apollo_current_rank_id', $rank_id );

	apollo_membership_log( $user_id, 'rank_awarded', 'rank', '', $rank_id, sprintf( 'Rank "%s" alcançado', $rank->title ), $admin_id );

	do_action( 'apollo_award_rank', $user_id, $rank_id, $trigger, $admin_id );

	return true;
}

/**
 * Revoke a rank from a user (demote)
 * Adapted from badgeos_revoke_rank_from_user_account()
 *
 * @param int $user_id
 * @param int $rank_id
 * @param int $admin_id
 * @return bool
 */
function apollo_revoke_rank_from_user( int $user_id, int $rank_id, int $admin_id = 0 ): bool {

	do_action( 'apollo_before_revoke_rank', $user_id, $rank_id );

	// Find previous rank to demote to
	$prev    = apollo_get_previous_rank( $user_id );
	$prev_id = $prev ? $prev->ID : 0;

	update_user_meta( $user_id, '_apollo_current_rank_id', $prev_id );

	apollo_membership_log( $user_id, 'rank_revoked', 'rank', '', $rank_id, 'Rank revogado pelo admin', $admin_id );

	do_action( 'apollo_after_revoke_rank', $user_id, $rank_id, $prev_id );

	return true;
}

// ═══════════════════════════════════════════════════════════════════════════
// AUTO-RANK CHECK — check rank progression after points change
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if user should be promoted/demoted based on points
 * Hook into points award/deduct actions
 *
 * @param int $user_id
 */
function apollo_check_rank_progression( int $user_id ): void {
	$total_points     = apollo_get_users_points( $user_id );
	$current_rank     = apollo_get_user_rank( $user_id );
	$current_priority = $current_rank ? $current_rank->priority : 0;

	// Find the highest rank the user qualifies for by points
	$eligible_ranks = apollo_get_ranks_by_points( $total_points );

	if ( empty( $eligible_ranks ) ) {
		return;
	}

	// The first one is the highest (sorted DESC by points)
	$best_rank = $eligible_ranks[0];

	if ( $best_rank->priority > $current_priority ) {
		// Promote
		apollo_award_rank_to_user( $best_rank->ID, $user_id, 'points_progression' );
	} elseif ( $best_rank->priority < $current_priority ) {
		// Demote (if points have decreased)
		$current_rank_id = apollo_get_user_rank_id( $user_id );
		if ( $current_rank_id ) {
			apollo_revoke_rank_from_user( $user_id, $current_rank_id );
			// Set to the correct rank
			if ( $best_rank->ID > 0 ) {
				apollo_award_rank_to_user( $best_rank->ID, $user_id, 'points_demotion' );
			}
		}
	}
}
add_action( 'apollo_award_user_points', 'apollo_check_rank_progression', 20, 1 );
add_action( 'apollo_deduct_points_from_user', 'apollo_check_rank_progression', 20, 1 );
add_action( 'apollo_update_users_points', 'apollo_check_rank_progression', 20, 1 );

/**
 * Get rank history for a user
 *
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function apollo_get_user_rank_history( int $user_id, int $limit = 20 ): array {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_RANKS;

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE user_id = %d ORDER BY dateadded DESC LIMIT %d",
			$user_id,
			$limit
		)
	);

	return is_array( $results ) ? $results : array();
}
