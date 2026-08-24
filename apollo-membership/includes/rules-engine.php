<?php
/**
 * Rules Engine
 *
 * Achievement completion checking and awarding logic.
 * Adapted from BadgeOS rules-engine.php.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// MAYBE AWARD ACHIEVEMENT — adapted from badgeos_maybe_award_achievement_to_user()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Maybe award an achievement to a user if they've completed requirements
 *
 * @param int    $achievement_id Achievement ID
 * @param int    $user_id        User ID
 * @param string $this_trigger   Trigger that caused the check
 * @param int    $site_id        Site ID
 * @param array  $args           Additional args
 * @return bool Whether achievement was awarded
 */
function apollo_maybe_award_achievement_to_user( int $achievement_id, int $user_id = 0, string $this_trigger = '', int $site_id = 0, array $args = array() ): bool {

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	// Check access
	if ( ! apollo_user_has_access_to_achievement( $user_id, $achievement_id ) ) {
		return false;
	}

	// Check max earnings exceeded
	if ( apollo_achievement_user_exceeded_max_earnings( $user_id, $achievement_id ) ) {
		return false;
	}

	// Check completion
	$completed = apollo_check_achievement_completion_for_user( $achievement_id, $user_id, $this_trigger, $site_id, $args );

	/**
	 * Filter: Should this user receive this achievement?
	 * Adapted from badgeos_user_deserves_achievement filter
	 *
	 * @param bool   $completed      Completion check result
	 * @param int    $achievement_id Achievement ID
	 * @param int    $user_id        User ID
	 * @param string $this_trigger   Trigger name
	 * @param int    $site_id        Site ID
	 * @param array  $args           Additional args
	 */
	$completed = apply_filters( 'apollo_user_deserves_achievement', $completed, $achievement_id, $user_id, $this_trigger, $site_id, $args );

	if ( $completed ) {
		apollo_award_achievement_to_user( $achievement_id, $user_id, $this_trigger, $site_id, $args );
		return true;
	}

	return false;
}

// ═══════════════════════════════════════════════════════════════════════════
// CHECK COMPLETION — adapted from badgeos_check_achievement_completion_for_user()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if user has completed all requirements for an achievement
 *
 * @param int    $achievement_id
 * @param int    $user_id
 * @param string $this_trigger
 * @param int    $site_id
 * @param array  $args
 * @return bool
 */
function apollo_check_achievement_completion_for_user( int $achievement_id, int $user_id, string $this_trigger = '', int $site_id = 0, array $args = array() ): bool {

	// Get required steps for this achievement
	$required_steps = apollo_get_required_steps_for_achievement( $achievement_id );

	if ( empty( $required_steps ) ) {
		// No steps defined = can be awarded directly if trigger matches
		$achievement_trigger = get_post_meta( $achievement_id, '_achievement_trigger', true );
		return ( $achievement_trigger === $this_trigger || empty( $achievement_trigger ) );
	}

	// Check each required step
	foreach ( $required_steps as $step ) {
		if ( ! apollo_check_step_completion_for_user( $step, $user_id, $this_trigger, $site_id, $args ) ) {
			return false;
		}
	}

	/**
	 * Also check points requirement if applicable
	 * Adapted from badgeos_user_meets_points_requirement filter
	 */
	$required_points = (int) get_post_meta( $achievement_id, '_achievement_points_required', true );
	if ( $required_points > 0 ) {
		$user_points = (int) get_user_meta( $user_id, '_apollo_points_total', true );
		if ( $user_points < $required_points ) {
			return false;
		}
	}

	return true;
}

/**
 * Get required steps for an achievement
 *
 * @param int $achievement_id
 * @return array
 */
function apollo_get_required_steps_for_achievement( int $achievement_id ): array {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_STEPS;

	$steps = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE parent_achievement_id = %d AND status = 'active' ORDER BY step_order ASC",
			$achievement_id
		)
	);

	return is_array( $steps ) ? $steps : array();
}

/**
 * Check if a single step is complete for a user
 *
 * @param object $step         Step object from DB
 * @param int    $user_id      User ID
 * @param string $this_trigger Current trigger
 * @param int    $site_id      Site ID
 * @param array  $args         Additional args
 * @return bool
 */
function apollo_check_step_completion_for_user( object $step, int $user_id, string $this_trigger = '', int $site_id = 0, array $args = array() ): bool {

	$trigger_type   = $step->trigger_type;
	$required_count = (int) $step->required_count;

	if ( $required_count <= 0 ) {
		$required_count = 1;
	}

	// Get the user's count for this trigger
	$user_count = apollo_get_user_trigger_count( $user_id, $trigger_type, $site_id ?: get_current_blog_id() );

	$complete = ( $user_count >= $required_count );

	/**
	 * Filter step completion
	 *
	 * @param bool   $complete   Whether step is complete
	 * @param object $step       Step data
	 * @param int    $user_id    User ID
	 * @param string $trigger    Current trigger
	 */
	return (bool) apply_filters( 'apollo_step_is_complete', $complete, $step, $user_id, $this_trigger );
}

// ═══════════════════════════════════════════════════════════════════════════
// ACCESS CONTROL — adapted from badgeos_user_has_access_to_achievement()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if user has access to earn this achievement
 *
 * @param int $user_id
 * @param int $achievement_id
 * @return bool
 */
function apollo_user_has_access_to_achievement( int $user_id, int $achievement_id ): bool {

	// Check if achievement exists
	$post = get_post( $achievement_id );
	if ( ! $post || $post->post_status !== 'publish' ) {
		return false;
	}

	// Check if user is allowed
	$restricted_to = get_post_meta( $achievement_id, '_achievement_restricted_to', true );
	if ( is_array( $restricted_to ) && ! empty( $restricted_to ) ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		$user_roles = $user->roles;
		if ( ! array_intersect( $user_roles, $restricted_to ) ) {
			return false;
		}
	}

	// Check hidden
	$hidden = get_post_meta( $achievement_id, '_achievement_hidden', true );
	if ( $hidden === 'hidden' ) {
		// Hidden achievements can still be earned, just not displayed
	}

	/**
	 * Filter access check
	 *
	 * @param bool $access         Whether user has access
	 * @param int  $user_id        User ID
	 * @param int  $achievement_id Achievement ID
	 */
	return (bool) apply_filters( 'apollo_user_has_access_to_achievement', true, $user_id, $achievement_id );
}

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENT / SEQUENTIAL ACHIEVEMENTS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check sequential (dependent) achievements after awarding
 * When achievement A is earned, check if it unlocks B
 *
 * Adapted from badgeos dependent achievements pattern
 *
 * @param int    $user_id
 * @param int    $achievement_id
 * @param string $trigger
 * @param int    $site_id
 * @param array  $args
 */
function apollo_check_dependent_achievements( int $user_id, int $achievement_id, string $trigger, int $site_id, array $args ): void {
	global $wpdb;

	$steps_table = $wpdb->prefix . APOLLO_TABLE_STEPS;

	// Find steps that require earning the just-earned achievement
	$dependent_steps = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$steps_table} WHERE trigger_type = 'specific_achievement' AND required_count <= 1 AND status = 'active'",
		)
	);

	if ( empty( $dependent_steps ) ) {
		return;
	}

	foreach ( $dependent_steps as $step ) {
		// Check if this step's required achievement matches
		$required_ach_id = get_post_meta( (int) $step->step_id, '_step_required_achievement_id', true );
		if ( (int) $required_ach_id === $achievement_id ) {
			apollo_maybe_award_achievement_to_user( (int) $step->parent_achievement_id, $user_id, 'specific_achievement', $site_id, $args );
		}
	}
}
add_action( 'apollo_award_achievement', 'apollo_check_dependent_achievements', 10, 5 );

// ═══════════════════════════════════════════════════════════════════════════
// BULK OPERATIONS — tools page helpers
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Recalculate all achievements for a user
 * Useful from admin tools page
 *
 * @param int $user_id
 * @return int Number of achievements awarded
 */
function apollo_recalculate_achievements_for_user( int $user_id ): int {
	global $wpdb;

	$steps_table   = $wpdb->prefix . APOLLO_TABLE_STEPS;
	$awarded_count = 0;

	// Get all active steps grouped by achievement
	$achievements = $wpdb->get_results(
		"SELECT DISTINCT parent_achievement_id FROM {$steps_table} WHERE status = 'active'"
	);

	foreach ( $achievements as $row ) {
		$ach_id = (int) $row->parent_achievement_id;

		// Skip if already earned and max earnings reached
		if ( apollo_achievement_user_exceeded_max_earnings( $user_id, $ach_id ) ) {
			continue;
		}

		// Check completion
		if ( apollo_check_achievement_completion_for_user( $ach_id, $user_id ) ) {
			apollo_award_achievement_to_user( $ach_id, $user_id, 'recalculation' );
			++$awarded_count;
		}
	}

	return $awarded_count;
}
