<?php
/**
 * Point Rules Engine
 *
 * Award and deduction step processing for points.
 * Adapted from BadgeOS points/point-rules-engine.php.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// MAYBE AWARD POINTS — adapted from badgeos_maybe_award_points_to_user()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Process point award step completion
 * Called when a trigger fires and point award steps exist
 *
 * @param int    $step_id   Step ID
 * @param int    $user_id   User ID
 * @param string $trigger   Trigger name
 * @param int    $site_id   Site ID
 * @param array  $args      Additional args
 * @return bool
 */
function apollo_maybe_award_points_to_user( int $step_id, int $user_id, string $trigger = '', int $site_id = 0, array $args = array() ): bool {
	global $wpdb;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$steps_table = $wpdb->prefix . APOLLO_TABLE_STEPS;

	// Get step definition
	$step = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$steps_table} WHERE id = %d AND status = 'active'",
			$step_id
		)
	);

	if ( ! $step ) {
		return false;
	}

	$points = (int) $step->point_value;
	if ( $points <= 0 ) {
		return false;
	}

	// Check if step is complete
	$trigger_count = apollo_get_user_trigger_count( $user_id, $step->trigger_type, $site_id ?: get_current_blog_id() );

	if ( $trigger_count < (int) $step->required_count ) {
		return false;
	}

	/**
	 * Filter: Should user receive these points?
	 *
	 * @param bool   $award   Whether to award
	 * @param int    $step_id Step ID
	 * @param int    $user_id User ID
	 * @param string $trigger Trigger name
	 * @param array  $args    Additional args
	 */
	$award = apply_filters( 'apollo_user_deserves_point_award', true, $step_id, $user_id, $trigger, $args );

	if ( ! $award ) {
		return false;
	}

	// Award the points
	apollo_log_users_points(
		$user_id,
		$points,
		'Award',
		$trigger,
		0,
		(int) $step->parent_achievement_id,
		0,
		$step_id
	);

	apollo_membership_recalc_total_points( $user_id );

	do_action( 'apollo_award_points_to_user', $user_id, $points, $step_id, $trigger );

	return true;
}

// ═══════════════════════════════════════════════════════════════════════════
// MAYBE DEDUCT POINTS — adapted from badgeos_maybe_deduct_points_to_user()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Process point deduction step
 *
 * @param int    $step_id
 * @param int    $user_id
 * @param string $trigger
 * @param int    $site_id
 * @param array  $args
 * @return bool
 */
function apollo_maybe_deduct_points_from_user( int $step_id, int $user_id, string $trigger = '', int $site_id = 0, array $args = array() ): bool {
	global $wpdb;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$steps_table = $wpdb->prefix . APOLLO_TABLE_STEPS;

	$step = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$steps_table} WHERE id = %d AND status = 'active'",
			$step_id
		)
	);

	if ( ! $step || (int) $step->point_value <= 0 ) {
		return false;
	}

	$points = (int) $step->point_value;

	// Check trigger count
	$trigger_count = apollo_get_user_trigger_count( $user_id, $step->trigger_type, $site_id ?: get_current_blog_id() );

	if ( $trigger_count < (int) $step->required_count ) {
		return false;
	}

	/**
	 * Filter: Should points be deducted?
	 */
	$deduct = apply_filters( 'apollo_user_deserves_point_deduction', true, $step_id, $user_id, $trigger, $args );

	if ( ! $deduct ) {
		return false;
	}

	// Check user has enough points (no negative balance)
	$current_total = apollo_get_users_points( $user_id );
	if ( $current_total < $points ) {
		$points = $current_total; // Deduct only what they have
	}

	if ( $points <= 0 ) {
		return false;
	}

	apollo_log_users_points(
		$user_id,
		$points,
		'Deduct',
		$trigger,
		0,
		(int) $step->parent_achievement_id,
		0,
		$step_id
	);

	apollo_membership_recalc_total_points( $user_id );

	do_action( 'apollo_deduct_points_from_user', $user_id, $points, $step_id, $trigger );

	return true;
}

// ═══════════════════════════════════════════════════════════════════════════
// BULK RECALCULATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Recalculate points for all users
 * Admin tools utility
 *
 * @return int Number of users updated
 */
function apollo_recalculate_all_user_points(): int {
	$users = get_users( array( 'fields' => 'ID' ) );
	$count = 0;

	foreach ( $users as $user_id ) {
		apollo_membership_recalc_total_points( (int) $user_id );
		++$count;
	}

	return $count;
}

/**
 * Reset all points for a specific user
 * Clears the points table and meta
 *
 * @param int $user_id
 * @param int $admin_id
 * @return bool
 */
function apollo_reset_user_points( int $user_id, int $admin_id = 0 ): bool {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_POINTS;

	$deleted = $wpdb->delete( $table, array( 'user_id' => $user_id ), array( '%d' ) );

	update_user_meta( $user_id, '_apollo_points_total', 0 );

	apollo_membership_log( $user_id, 'points_reset', 'points', '', 0, 'All points reset by admin', $admin_id );

	do_action( 'apollo_user_points_reset', $user_id, $admin_id );

	return (bool) $deleted;
}
