<?php
/**
 * Achievement Functions
 *
 * CRUD for achievements. Adapted from BadgeOS achievement-functions.php.
 * Uses custom table apollo_achievements instead of CPT.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// GET USER ACHIEVEMENTS — adapted from badgeos_get_user_achievements()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get a user's earned achievements
 *
 * @param array $args Query arguments
 * @return array|int Array of achievement objects or count
 */
function apollo_get_user_achievements( array $args = array() ): array|int {
	global $wpdb;

	$defaults = array(
		'user_id'          => 0,
		'site_id'          => get_current_blog_id(),
		'achievement_id'   => false,
		'achievement_type' => false,
		'start_date'       => false,
		'end_date'         => false,
		'since'            => 0,
		'pagination'       => false,
		'limit'            => 10,
		'page'             => 1,
		'orderby'          => 'entry_id',
		'order'            => 'ASC',
		'total_only'       => false,
	);
	$args     = wp_parse_args( $args, $defaults );

	if ( $args['user_id'] === 0 ) {
		$args['user_id'] = get_current_user_id();
	}

	$table = $wpdb->prefix . APOLLO_TABLE_ACHIEVEMENTS;

	// Build WHERE clause
	$where = $wpdb->prepare( 'user_id = %d', $args['user_id'] );

	if ( $args['achievement_id'] !== false ) {
		$where .= $wpdb->prepare( ' AND achievement_id = %d', $args['achievement_id'] );
	}

	if ( $args['achievement_type'] !== false ) {
		if ( is_array( $args['achievement_type'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $args['achievement_type'] ), '%s' ) );
			$where       .= $wpdb->prepare( " AND post_type IN ({$placeholders})", ...$args['achievement_type'] );
		} else {
			$where .= $wpdb->prepare( ' AND post_type = %s', $args['achievement_type'] );
		}
	}

	if ( $args['since'] > 1 ) {
		$where .= $wpdb->prepare( ' AND date_earned > %s', date( 'Y-m-d H:i:s', $args['since'] ) );
	}

	if ( $args['start_date'] ) {
		$where .= $wpdb->prepare( ' AND date_earned >= %s', $args['start_date'] );
	}

	if ( $args['end_date'] ) {
		$where .= $wpdb->prepare( ' AND date_earned <= %s', $args['end_date'] );
	}

	if ( $args['total_only'] ) {
		return (int) $wpdb->get_var( "SELECT COUNT(entry_id) FROM {$table} WHERE {$where}" );
	}

	// Order
	$orderby   = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
	$order_str = $orderby ? " ORDER BY {$orderby}" : ' ORDER BY entry_id ASC';

	// Pagination
	$paginate = '';
	if ( $args['pagination'] ) {
		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['limit'];
		$paginate = $wpdb->prepare( ' LIMIT %d, %d', $offset, $args['limit'] );
	}

	$results = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where}{$order_str}{$paginate}" );

	return is_array( $results ) ? $results : array();
}

// ═══════════════════════════════════════════════════════════════════════════
// AWARD ACHIEVEMENT — adapted from badgeos_award_achievement_to_user()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Award an achievement to a user
 *
 * @param int    $achievement_id Achievement post/config ID
 * @param int    $user_id        User ID
 * @param string $trigger        Trigger that caused the award
 * @param int    $site_id        Site ID
 * @param array  $args           Additional args
 * @return int|false Entry ID or false
 */
function apollo_award_achievement_to_user( int $achievement_id, int $user_id = 0, string $trigger = '', int $site_id = 0, array $args = array() ) {
	global $wpdb;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	// Build achievement object
	$achievement = apollo_build_achievement_object( $achievement_id, 'earned', $trigger );
	if ( ! $achievement ) {
		return false;
	}

	$table = $wpdb->prefix . APOLLO_TABLE_ACHIEVEMENTS;

	$result = $wpdb->insert(
		$table,
		array(
			'achievement_id'     => $achievement->ID,
			'user_id'            => $user_id,
			'post_type'          => $achievement->post_type ?? 'achievement',
			'achievement_title'  => $achievement->title ?? '',
			'points'             => $achievement->points ?? 0,
			'point_type'         => $achievement->point_type ?? 0,
			'this_trigger'       => $trigger,
			'rec_type'           => $achievement->rec_type ?? 'normal',
			'image'              => $achievement->image ?? '',
			'site_id'            => $site_id,
			'actual_date_earned' => apollo_membership_default_datetime(),
			'date_earned'        => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
	);

	if ( ! $result ) {
		return false;
	}

	$entry_id = $wpdb->insert_id;

	// Update achievement count
	$count = (int) get_user_meta( $user_id, '_apollo_achievement_count', true );
	update_user_meta( $user_id, '_apollo_achievement_count', $count + 1 );

	// Log it
	apollo_membership_log( $user_id, 'achievement_awarded', 'achievement', '', $achievement_id, sprintf( 'Achievement "%s" awarded', $achievement->title ?? $achievement_id ) );

	/**
	 * Fires after achievement is awarded
	 * Adapted from do_action('badgeos_award_achievement', ...)
	 *
	 * @param int    $user_id        User ID
	 * @param int    $achievement_id Achievement ID
	 * @param string $trigger        Trigger name
	 * @param int    $site_id        Site ID
	 * @param array  $args           Additional args
	 */
	do_action( 'apollo_award_achievement', $user_id, $achievement_id, $trigger, $site_id, $args );

	// Award points if achievement has points
	if ( ! empty( $achievement->points ) && $achievement->points > 0 ) {
		apollo_membership_award_credit(
			(int) ( $achievement->point_type ?? 0 ),
			$user_id,
			(int) $achievement->points,
			'achievement_based'
		);
	}

	return $entry_id;
}

// ═══════════════════════════════════════════════════════════════════════════
// REVOKE ACHIEVEMENT — adapted from user.php revoke functions
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Revoke an achievement from a user
 *
 * @param int $user_id        User ID
 * @param int $achievement_id Achievement ID
 * @param int $entry_id       Specific entry ID (0 = revoke latest)
 * @return bool
 */
function apollo_revoke_achievement_from_user( int $user_id, int $achievement_id, int $entry_id = 0 ): bool {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_ACHIEVEMENTS;

	/**
	 * Fires before revoking
	 */
	do_action( 'apollo_before_revoke_achievement', $user_id, $achievement_id, $entry_id );

	if ( $entry_id > 0 ) {
		$deleted = $wpdb->delete(
			$table,
			array(
				'entry_id' => $entry_id,
				'user_id'  => $user_id,
			),
			array( '%d', '%d' )
		);
	} else {
		// Delete most recent entry
		$latest = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT entry_id FROM {$table} WHERE user_id = %d AND achievement_id = %d ORDER BY date_earned DESC LIMIT 1",
				$user_id,
				$achievement_id
			)
		);

		if ( $latest ) {
			$deleted = $wpdb->delete( $table, array( 'entry_id' => $latest ), array( '%d' ) );
		} else {
			$deleted = false;
		}
	}

	if ( $deleted ) {
		// Update count
		$count = max( 0, (int) get_user_meta( $user_id, '_apollo_achievement_count', true ) - 1 );
		update_user_meta( $user_id, '_apollo_achievement_count', $count );

		apollo_membership_log( $user_id, 'achievement_revoked', 'achievement', '', $achievement_id, 'Achievement revoked' );

		do_action( 'apollo_after_revoke_achievement', $user_id, $achievement_id, $entry_id );
	}

	return (bool) $deleted;
}

// ═══════════════════════════════════════════════════════════════════════════
// BUILD ACHIEVEMENT OBJECT — adapted from badgeos_build_achievement_object()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Build achievement object
 *
 * @param int    $achievement_id
 * @param string $context 'earned'|'started'
 * @param string $trigger
 * @return object|false
 */
function apollo_build_achievement_object( int $achievement_id, string $context = 'earned', string $trigger = '' ): object|false {

	$post = get_post( $achievement_id );

	$obj              = new \stdClass();
	$obj->ID          = $achievement_id;
	$obj->title       = $post ? $post->post_title : "Achievement #{$achievement_id}";
	$obj->post_type   = $post ? $post->post_type : 'achievement';
	$obj->the_trigger = $trigger;
	$obj->image       = '';
	$obj->rec_type    = 'normal';

	// Points
	$points          = get_post_meta( $achievement_id, '_achievement_points', true );
	$obj->points     = is_numeric( $points ) ? (int) $points : 0;
	$obj->point_type = (int) get_post_meta( $achievement_id, '_achievement_point_type', true );

	// Trigger
	$obj->trigger = $trigger;

	// Timestamps
	if ( $context === 'earned' ) {
		$obj->date_earned = time();
	} elseif ( $context === 'started' ) {
		$obj->date_started = time();
	}

	return apply_filters( 'apollo_achievement_object', $obj, $achievement_id, $context );
}

// ═══════════════════════════════════════════════════════════════════════════
// ACHIEVEMENT QUERIES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if user has already exceeded max earnings
 * Adapted from badgeos_achievement_user_exceeded_max_earnings()
 *
 * @param int $user_id
 * @param int $achievement_id
 * @return bool
 */
function apollo_achievement_user_exceeded_max_earnings( int $user_id, int $achievement_id ): bool {
	$max = (int) get_post_meta( $achievement_id, '_achievement_maximum_earnings', true );

	if ( $max === -1 ) {
		return false; // Infinite
	}

	if ( $max > 0 ) {
		$earned = apollo_get_user_achievements(
			array(
				'user_id'        => $user_id,
				'achievement_id' => $achievement_id,
			)
		);
		if ( is_array( $earned ) && count( $earned ) >= $max ) {
			return true;
		}
	}

	return false;
}

/**
 * Get user earned achievement IDs
 *
 * @param int    $user_id
 * @param string $type
 * @return array
 */
function apollo_get_user_earned_achievement_ids( int $user_id, string $type = '' ): array {
	$args = array( 'user_id' => $user_id );
	if ( $type ) {
		$args['achievement_type'] = $type;
	}

	$achievements = apollo_get_user_achievements( $args );
	$ids          = array();
	if ( is_array( $achievements ) ) {
		foreach ( $achievements as $ach ) {
			$ids[] = $ach->achievement_id;
		}
	}

	return $ids;
}

/**
 * Get achievement post thumbnail
 * Adapted from badgeos_get_achievement_post_thumbnail()
 *
 * @param int    $achievement_id
 * @param string $class
 * @return string HTML img tag
 */
function apollo_get_achievement_post_thumbnail( int $achievement_id, string $class = 'apollo-achievement-thumb' ): string {
	$settings = apollo_membership_get_settings();
	$w        = $settings['image_width'] ?? 50;
	$h        = $settings['image_height'] ?? 50;

	$image = get_the_post_thumbnail( $achievement_id, array( $w, $h ), array( 'class' => $class ) );

	if ( ! $image ) {
		$image = '<img src="' . esc_url( APOLLO_MEMBERSHIP_DEFAULT_BADGE_IMAGE ) . '" width="' . $w . '" height="' . $h . '" class="' . esc_attr( $class ) . '" />';
	}

	return $image;
}

/**
 * Get achievement earners list
 * Adapted from badgeos_get_achievement_earners()
 *
 * @param int $achievement_id
 * @return array User objects
 */
function apollo_get_achievement_earners( int $achievement_id ): array {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_ACHIEVEMENTS;

	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT user_id FROM {$table} WHERE achievement_id = %d",
			$achievement_id
		)
	);

	$users = array();
	foreach ( $user_ids as $uid ) {
		$user = get_user_by( 'ID', $uid );
		if ( $user ) {
			$users[] = $user;
		}
	}

	return $users;
}
