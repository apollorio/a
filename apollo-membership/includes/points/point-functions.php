<?php
/**
 * Point Functions
 *
 * Extended point CRUD and queries.
 * Adapted from BadgeOS points/point-functions.php.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// GET POINTS — adapted from badgeos_get_users_points()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get a user's total points
 * Primary entry point — uses cached user meta value, recalculates if empty
 *
 * @param int $user_id User ID (0 = current)
 * @return int
 */
function apollo_get_users_points( int $user_id = 0 ): int {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return 0;
	}

	$total = get_user_meta( $user_id, '_apollo_points_total', true );

	if ( $total === '' || $total === false ) {
		// Recalculate from DB
		$total = apollo_membership_recalc_total_points( $user_id );
	}

	return (int) $total;
}

/**
 * Get user points by specific type
 * Wrapper around functions.php helper
 *
 * @param int    $user_id
 * @param string $type 'Award'|'Deduct'|'Utilized'
 * @return int
 */
function apollo_get_users_points_by_type( int $user_id, string $type = 'Award' ): int {
	return apollo_membership_get_points_by_type( $user_id, $type );
}

// ═══════════════════════════════════════════════════════════════════════════
// UPDATE POINTS — adapted from badgeos_update_users_points()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Directly set a user's total points (admin override)
 * Logs the change in points table
 *
 * @param int $user_id User ID
 * @param int $points  New total
 * @param int $admin_id Admin who made the change
 * @return bool
 */
function apollo_update_users_points( int $user_id, int $points, int $admin_id = 0 ): bool {
	if ( ! $user_id ) {
		return false;
	}

	$old_total = apollo_get_users_points( $user_id );
	$diff      = $points - $old_total;

	if ( $diff === 0 ) {
		return true;
	}

	// Log the adjustment
	apollo_log_users_points(
		$user_id,
		abs( $diff ),
		$diff > 0 ? 'Award' : 'Deduct',
		'admin_adjustment',
		$admin_id
	);

	update_user_meta( $user_id, '_apollo_points_total', $points );

	/**
	 * Action: Points updated
	 * Adapted from do_action('badgeos_update_users_points', ...)
	 */
	do_action( 'apollo_update_users_points', $user_id, $points, $old_total, $admin_id );

	return true;
}

// ═══════════════════════════════════════════════════════════════════════════
// LOG POINTS — adapted from badgeos_log_users_points()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Log a point transaction in the points table
 * Adapted from badgeos_log_users_points()
 *
 * @param int    $user_id       User ID
 * @param int    $credit        Points amount
 * @param string $type          'Award'|'Deduct'|'Utilized'
 * @param string $trigger       Trigger that caused it
 * @param int    $admin_id      Admin ID (if admin action)
 * @param int    $achievement_id Related achievement (0 = none)
 * @param int    $credit_id     Point type ID (0 = default)
 * @param int    $step_id       Step that triggered it (0 = none)
 * @return int|false Insert ID or false
 */
function apollo_log_users_points( int $user_id, int $credit, string $type = 'Award', string $trigger = '', int $admin_id = 0, int $achievement_id = 0, int $credit_id = 0, int $step_id = 0 ) {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_POINTS;

	$result = $wpdb->insert(
		$table,
		array(
			'achievement_id'     => $achievement_id,
			'credit_id'          => $credit_id,
			'step_id'            => $step_id,
			'user_id'            => $user_id,
			'admin_id'           => $admin_id,
			'type'               => $type,
			'this_trigger'       => $trigger,
			'credit'             => $credit,
			'actual_date_earned' => apollo_membership_default_datetime(),
			'dateadded'          => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
	);

	return $result ? $wpdb->insert_id : false;
}

// ═══════════════════════════════════════════════════════════════════════════
// AWARD / DEDUCT OPERATIONS — adapted from badgeos_award_user_points()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Award points to a user (log + recalculate)
 *
 * @param int    $user_id  User ID
 * @param int    $points   Points to award
 * @param string $trigger  Trigger name
 * @param int    $admin_id Admin ID (0 = automatic)
 * @return bool
 */
function apollo_award_user_points( int $user_id, int $points, string $trigger = '', int $admin_id = 0 ): bool {
	if ( ! $user_id || $points <= 0 ) {
		return false;
	}

	$logged = apollo_log_users_points( $user_id, $points, 'Award', $trigger, $admin_id );

	if ( $logged ) {
		apollo_membership_recalc_total_points( $user_id );

		/**
		 * Action: Points awarded
		 */
		do_action( 'apollo_award_user_points', $user_id, $points, $trigger, $admin_id );

		return true;
	}

	return false;
}

/**
 * Deduct points from a user
 *
 * @param int    $user_id  User ID
 * @param int    $points   Points to deduct
 * @param string $trigger  Trigger name
 * @param int    $admin_id Admin ID
 * @return bool
 */
function apollo_deduct_user_points( int $user_id, int $points, string $trigger = '', int $admin_id = 0 ): bool {
	if ( ! $user_id || $points <= 0 ) {
		return false;
	}

	$logged = apollo_log_users_points( $user_id, $points, 'Deduct', $trigger, $admin_id );

	if ( $logged ) {
		apollo_membership_recalc_total_points( $user_id );

		do_action( 'apollo_deduct_user_points', $user_id, $points, $trigger, $admin_id );

		return true;
	}

	return false;
}

// ═══════════════════════════════════════════════════════════════════════════
// POINT HISTORY — user's full point log
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get a user's point history (all transactions)
 *
 * @param int   $user_id
 * @param array $args Query args
 * @return array
 */
function apollo_get_user_point_history( int $user_id, array $args = array() ): array {
	global $wpdb;

	$defaults = array(
		'type'       => '',
		'trigger'    => '',
		'limit'      => 20,
		'page'       => 1,
		'order'      => 'DESC',
		'start_date' => '',
		'end_date'   => '',
	);
	$args     = wp_parse_args( $args, $defaults );

	$table = $wpdb->prefix . APOLLO_TABLE_POINTS;
	$where = $wpdb->prepare( 'user_id = %d', $user_id );

	if ( $args['type'] ) {
		$where .= $wpdb->prepare( ' AND type = %s', $args['type'] );
	}
	if ( $args['trigger'] ) {
		$where .= $wpdb->prepare( ' AND this_trigger = %s', $args['trigger'] );
	}
	if ( $args['start_date'] ) {
		$where .= $wpdb->prepare( ' AND dateadded >= %s', $args['start_date'] );
	}
	if ( $args['end_date'] ) {
		$where .= $wpdb->prepare( ' AND dateadded <= %s', $args['end_date'] );
	}

	$order  = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
	$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['limit'];

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE {$where} ORDER BY id {$order} LIMIT %d, %d",
			$offset,
			$args['limit']
		)
	);

	return is_array( $results ) ? $results : array();
}

// ═══════════════════════════════════════════════════════════════════════════
// LEADERBOARD
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get points leaderboard
 *
 * @param int $limit Number of users to return
 * @return array [{user_id, display_name, total_points}, ...]
 */
function apollo_get_points_leaderboard( int $limit = 10 ): array {
	global $wpdb;

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT um.user_id, u.display_name, CAST(um.meta_value AS SIGNED) as total_points
		 FROM {$wpdb->usermeta} um
		 JOIN {$wpdb->users} u ON um.user_id = u.ID
		 WHERE um.meta_key = '_apollo_points_total'
		 AND CAST(um.meta_value AS SIGNED) > 0
		 ORDER BY CAST(um.meta_value AS SIGNED) DESC
		 LIMIT %d",
			$limit
		)
	);

	return is_array( $results ) ? $results : array();
}

/**
 * Get a user's rank position in the leaderboard
 *
 * @param int $user_id
 * @return int Position (1-based, 0 if not ranked)
 */
function apollo_get_user_leaderboard_position( int $user_id ): int {
	$total = apollo_get_users_points( $user_id );

	if ( $total <= 0 ) {
		return 0;
	}

	global $wpdb;

	$position = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) + 1
		 FROM {$wpdb->usermeta}
		 WHERE meta_key = '_apollo_points_total'
		 AND CAST(meta_value AS SIGNED) > %d",
			$total
		)
	);

	return (int) $position;
}
