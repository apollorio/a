<?php
/**
 * Apollo Social — Helper Functions
 *
 * @package Apollo\Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log an activity item to the social stream.
 */
function apollo_log_activity( array $args ): int|false {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_activity';

	$defaults = array(
		'user_id'           => get_current_user_id(),
		'component'         => 'social',
		'type'              => 'general',
		'action_text'       => '',
		'content'           => '',
		'primary_link'      => '',
		'item_id'           => 0,
		'secondary_item_id' => 0,
		'hide_sitewide'     => 0,
	);

	$data = wp_parse_args( $args, $defaults );

	$inserted = $wpdb->insert(
		$table,
		array(
			'user_id'           => $data['user_id'],
			'component'         => $data['component'],
			'type'              => $data['type'],
			'action_text'       => $data['action_text'],
			'content'           => $data['content'],
			'primary_link'      => $data['primary_link'],
			'item_id'           => $data['item_id'],
			'secondary_item_id' => $data['secondary_item_id'],
			'hide_sitewide'     => $data['hide_sitewide'],
			'created_at'        => current_time( 'mysql' ),
		)
	);

	if ( $inserted ) {
		$activity_id = (int) $wpdb->insert_id;
		do_action( 'apollo/social/activity_created', $activity_id, $data );

		// Check for mentions: @username pattern
		if ( ! empty( $data['content'] ) ) {
			apollo_process_mentions( $activity_id, $data['content'], $data['user_id'] );
		}

		return $activity_id;
	}
	return false;
}

/**
 * Process @mentions in content and create notifications.
 */
function apollo_process_mentions( int $activity_id, string $content, int $sender_id ): void {
	preg_match_all( '/@([a-zA-Z0-9_.]+)/', $content, $matches );
	if ( empty( $matches[1] ) ) {
		return;
	}

	$mentioned = array_unique( $matches[1] );
	foreach ( $mentioned as $username ) {
		$user = get_user_by( 'login', $username );
		if ( ! $user || $user->ID === $sender_id ) {
			continue;
		}

		if ( function_exists( 'apollo_create_notification' ) ) {
			$sender      = get_userdata( $sender_id );
			$sender_name = $sender ? $sender->display_name : 'Alguém';
			apollo_create_notification(
				$user->ID,
				'mention',
				"{$sender_name} mencionou você",
				'',
				home_url( '/feed' ),
				array(
					'activity_id' => $activity_id,
					'sender_id'   => $sender_id,
				)
			);
		}
	}
}

/**
 * Get activity feed for a user (all connections' activity).
 * Since all users are auto-connected, this returns global activity.
 */
function apollo_get_feed( int $user_id = 0, int $limit = 20, int $offset = 0, string $component = '' ): array {
	global $wpdb;
	$table  = $wpdb->prefix . 'apollo_activity';
	$blocks = $wpdb->prefix . 'apollo_blocks';

	$where = 'WHERE a.hide_sitewide = 0 AND a.is_spam = 0';

	// Exclude blocked users
	if ( $user_id > 0 ) {
		$where .= $wpdb->prepare(
			" AND a.user_id NOT IN (SELECT blocked_id FROM {$blocks} WHERE blocker_id = %d)",
			$user_id
		);
	}

	if ( $component ) {
		$where .= $wpdb->prepare( ' AND a.component = %s', $component );
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT a.*, u.display_name, u.user_login
         FROM {$table} a
         LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
         {$where}
         ORDER BY a.created_at DESC
         LIMIT %d OFFSET %d",
			$limit,
			$offset
		),
		ARRAY_A
	);

	return $results ?: array();
}

/**
 * Auto-connect a new user with ALL existing users.
 * Called on user_register hook.
 */
function apollo_auto_connect_new_user( int $new_user_id ): void {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_follows';
	$now   = current_time( 'mysql' );

	$existing_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->users} WHERE ID != %d",
			$new_user_id
		)
	);

	if ( empty( $existing_ids ) ) {
		return;
	}

	$values = array();
	foreach ( $existing_ids as $uid ) {
		$values[] = $wpdb->prepare( '(%d, %d, %s)', $new_user_id, $uid, $now );
		$values[] = $wpdb->prepare( '(%d, %d, %s)', $uid, $new_user_id, $now );
	}

	// Batch insert
	$chunks = array_chunk( $values, 500 );
	foreach ( $chunks as $chunk ) {
		$wpdb->query( "INSERT IGNORE INTO {$table} (follower_id, following_id, created_at) VALUES " . implode( ',', $chunk ) );
	}

	// Log activity
	apollo_log_activity(
		array(
			'user_id'     => $new_user_id,
			'component'   => 'social',
			'type'        => 'new_member',
			'action_text' => 'entrou na comunidade Apollo',
		)
	);
}

/**
 * Block a user.
 */
function apollo_block_user( int $blocker_id, int $blocked_id, string $reason = '' ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_blocks';

	$inserted = $wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO {$table} (blocker_id, blocked_id, reason, created_at) VALUES (%d, %d, %s, %s)",
			$blocker_id,
			$blocked_id,
			$reason,
			current_time( 'mysql' )
		)
	);

	return $inserted > 0;
}

/**
 * Unblock a user.
 */
function apollo_unblock_user( int $blocker_id, int $blocked_id ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_blocks';

	return (bool) $wpdb->delete(
		$table,
		array(
			'blocker_id' => $blocker_id,
			'blocked_id' => $blocked_id,
		),
		array( '%d', '%d' )
	);
}
