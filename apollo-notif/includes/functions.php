<?php
/**
 * Apollo Notif — Helper Functions
 *
 * @package Apollo\Notif
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create a notification for a user.
 *
 * @param int    $user_id  Recipient user ID.
 * @param string $type     Notification type slug (e.g. 'chat', 'wow', 'group_join').
 * @param string $title    Short notification title.
 * @param string $message  Optional longer description.
 * @param string $link     Optional URL to navigate to on click.
 * @param array  $data     Optional JSON-encodable extra payload.
 * @param array  $options  Optional extended fields:
 *   - severity      string  'info'|'success'|'warning'|'alert'  (default 'info')
 *   - icon          string  RemixIcon class e.g. 'ri-chat-1-line'
 *   - is_dismissible bool   Whether user can dismiss (default true)
 *   - action_label  string  CTA button label
 *   - action_link   string  CTA button URL
 *   - channel       string  Source channel slug e.g. 'apollo/chat'
 *   - expires_at    string  MySQL datetime when notif auto-expires
 * @return int|false Inserted notification ID or false on failure.
 */
function apollo_create_notification(
	int $user_id,
	string $type,
	string $title,
	string $message = '',
	string $link = '',
	array $data = array(),
	array $options = array()
): int|false {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';

	// Respect per-type user preference — skip if user muted this type.
	if ( apollo_is_type_snoozed( $user_id, $type ) ) {
		return false;
	}

	$severity       = in_array( $options['severity'] ?? 'info', array( 'info', 'success', 'warning', 'alert' ), true )
		? $options['severity']
		: 'info';
	$icon           = sanitize_text_field( $options['icon'] ?? '' );
	$is_dismissible = isset( $options['is_dismissible'] ) ? (int) (bool) $options['is_dismissible'] : 1;
	$action_label   = sanitize_text_field( $options['action_label'] ?? '' );
	$action_link    = esc_url_raw( $options['action_link'] ?? '' );
	$channel        = sanitize_key( $options['channel'] ?? '' );
	$expires_at     = ! empty( $options['expires_at'] ) ? $options['expires_at'] : null;

	$row     = array(
		'user_id'        => $user_id,
		'type'           => $type,
		'title'          => $title,
		'message'        => $message,
		'data'           => ! empty( $data ) ? wp_json_encode( $data ) : null,
		'link'           => $link,
		'is_read'        => 0,
		'created_at'     => current_time( 'mysql' ),
		'severity'       => $severity,
		'is_dismissible' => $is_dismissible,
	);
	$formats = array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d' );

	if ( $icon !== '' ) {
		$row['icon'] = $icon;
		$formats[]   = '%s';
	}
	if ( $action_label !== '' ) {
		$row['action_label'] = $action_label;
		$formats[]           = '%s';
	}
	if ( $action_link !== '' ) {
		$row['action_link'] = $action_link;
		$formats[]          = '%s';
	}
	if ( $channel !== '' ) {
		$row['channel'] = $channel;
		$formats[]      = '%s';
	}
	if ( $expires_at !== null ) {
		$row['expires_at'] = $expires_at;
		$formats[]         = '%s';
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert( $table, $row, $formats );
	// phpcs:enable

	if ( $inserted ) {
		$notif_id = (int) $wpdb->insert_id;
		// Increment cached unread count.
		$current = (int) get_user_meta( $user_id, '_apollo_notif_unread', true );
		update_user_meta( $user_id, '_apollo_notif_unread', $current + 1 );
		do_action( 'apollo/notif/created', $notif_id, $user_id, $type );
		return $notif_id;
	}
	return false;
}

/**
 * Fetch a single notification (must belong to the given user).
 *
 * @param int $notif_id Notification ID.
 * @param int $user_id  Owner user ID.
 * @return array|null Row or null if not found.
 */
function apollo_get_notification( int $notif_id, int $user_id ): ?array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
			$notif_id,
			$user_id
		),
		ARRAY_A
	);
	// phpcs:enable
	return $row ?: null;
}

/**
 * Get unread notification count for a user.
 *
 * @param int $user_id User ID.
 * @return int
 */
function apollo_get_unread_notif_count( int $user_id ): int {
	$cached = get_user_meta( $user_id, '_apollo_notif_unread', true );
	if ( $cached !== '' ) {
		return (int) $cached;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
			$user_id
		)
	);
	// phpcs:enable
	update_user_meta( $user_id, '_apollo_notif_unread', $count );
	return $count;
}

/**
 * Mark a notification as read.
 *
 * @param int $notif_id Notification ID.
 * @param int $user_id  Owner user ID (ownership check).
 * @return bool True on success.
 */
function apollo_mark_notif_read( int $notif_id, int $user_id ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$updated = $wpdb->update(
		$table,
		array(
			'is_read' => 1,
			'read_at' => current_time( 'mysql' ),
		),
		array(
			'id'      => $notif_id,
			'user_id' => $user_id,
		),
		array( '%d', '%s' ),
		array( '%d', '%d' )
	);
	// phpcs:enable
	if ( $updated ) {
		// Recalculate cached count.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
				$user_id
			)
		);
		update_user_meta( $user_id, '_apollo_notif_unread', $count );
	}
	return (bool) $updated;
}

/**
 * Mark all notifications as read for a user.
 *
 * @param int $user_id User ID.
 * @return int Number of rows updated.
 */
function apollo_mark_all_notifs_read( int $user_id ): int {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$updated = (int) $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET is_read = 1, read_at = %s WHERE user_id = %d AND is_read = 0",
			current_time( 'mysql' ),
			$user_id
		)
	);
	// phpcs:enable
	update_user_meta( $user_id, '_apollo_notif_unread', 0 );
	return $updated;
}

/**
 * Mark a notification as displayed (user saw it in the list/dropdown).
 * Only sets displayed_at once (on first view).
 *
 * @param int $notif_id Notification ID.
 * @param int $user_id  Owner user ID.
 * @return bool
 */
function apollo_mark_notif_displayed( int $notif_id, int $user_id ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET displayed_at = %s WHERE id = %d AND user_id = %d AND displayed_at IS NULL",
			current_time( 'mysql' ),
			$notif_id,
			$user_id
		)
	);
	// phpcs:enable
	return (bool) $updated;
}

/**
 * Delete a single notification owned by the user.
 *
 * @param int $notif_id Notification ID.
 * @param int $user_id  Owner user ID.
 * @return bool
 */
function apollo_delete_notification( int $notif_id, int $user_id ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$deleted = (bool) $wpdb->delete(
		$table,
		array(
			'id'      => $notif_id,
			'user_id' => $user_id,
		),
		array( '%d', '%d' )
	);
	// phpcs:enable
	if ( $deleted ) {
		// Recalculate cached unread count.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
				$user_id
			)
		);
		update_user_meta( $user_id, '_apollo_notif_unread', $count );
		do_action( 'apollo/notif/deleted', $notif_id, $user_id );
	}
	return $deleted;
}

/**
 * Delete all read notifications for a user.
 *
 * @param int $user_id User ID.
 * @return int Number of rows deleted.
 */
function apollo_delete_all_read_notifications( int $user_id ): int {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$deleted = (int) $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$table} WHERE user_id = %d AND is_read = 1",
			$user_id
		)
	);
	// phpcs:enable
	do_action( 'apollo/notif/deleted_all_read', $user_id, $deleted );
	return $deleted;
}

/**
 * Get notifications for a user with extended filtering.
 *
 * @param int       $user_id    User ID.
 * @param int       $limit      Max rows.
 * @param int       $offset     SQL offset.
 * @param bool|null $unread_only Null = all; true = unread; false = read.
 * @param array     $filters     Optional extra filters:
 *  - since    string  MySQL datetime — only notifs created after this
 *  - type     string  Exact type slug filter
 *  - channel  string  Channel slug filter
 *  - severity string  'info'|'success'|'warning'|'alert'
 * @return array<int, array>
 */
function apollo_get_notifications(
	int $user_id,
	int $limit = 20,
	int $offset = 0,
	?bool $unread_only = null,
	array $filters = array()
): array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';

	$conditions = array( $wpdb->prepare( 'user_id = %d', $user_id ) );

	if ( $unread_only === true ) {
		$conditions[] = 'is_read = 0';
	} elseif ( $unread_only === false ) {
		$conditions[] = 'is_read = 1';
	}

	if ( ! empty( $filters['since'] ) ) {
		$since        = sanitize_text_field( $filters['since'] );
		$conditions[] = $wpdb->prepare( 'created_at > %s', $since );
	}
	if ( ! empty( $filters['type'] ) ) {
		$conditions[] = $wpdb->prepare( 'type = %s', sanitize_key( $filters['type'] ) );
	}
	if ( ! empty( $filters['channel'] ) ) {
		$conditions[] = $wpdb->prepare( 'channel = %s', sanitize_text_field( $filters['channel'] ) );
	}
	if ( ! empty( $filters['severity'] ) && in_array( $filters['severity'], array( 'info', 'success', 'warning', 'alert' ), true ) ) {
		$conditions[] = $wpdb->prepare( 'severity = %s', $filters['severity'] );
	}

	// Exclude expired.
	$conditions[] = '(expires_at IS NULL OR expires_at > NOW())';

	$where = 'WHERE ' . implode( ' AND ', $conditions );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$limit,
			$offset
		),
		ARRAY_A
	);
	// phpcs:enable

	return $results ?: array();
}

/**
 * Snooze (mute) a notification type for a user for X hours.
 * Pass $hours = 0 to permanently mute (sets year 9999 as sentinel).
 *
 * @param int    $user_id User ID.
 * @param string $type    Notification type slug.
 * @param int    $hours   Hours to snooze. 0 = permanent.
 * @return bool
 */
function apollo_snooze_type( int $user_id, string $type, int $hours = 24 ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notif_prefs';

	$until = $hours > 0
		? gmdate( 'Y-m-d H:i:s', time() + $hours * HOUR_IN_SECONDS )
		: '9999-12-31 23:59:59';

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$result = $wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (user_id, notif_type, snoozed_until)
			 VALUES (%d, %s, %s)
			 ON DUPLICATE KEY UPDATE snoozed_until = VALUES(snoozed_until)",
			$user_id,
			sanitize_key( $type ),
			$until
		)
	);
	// phpcs:enable

	do_action( 'apollo/notif/type_snoozed', $user_id, $type, $until );
	return $result !== false;
}

/**
 * Un-snooze a notification type for a user.
 *
 * @param int    $user_id User ID.
 * @param string $type    Notification type slug.
 * @return bool
 */
function apollo_unsnooze_type( int $user_id, string $type ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notif_prefs';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$result = $wpdb->update(
		$table,
		array( 'snoozed_until' => null ),
		array(
			'user_id'    => $user_id,
			'notif_type' => sanitize_key( $type ),
		),
		array( '%s' ),
		array( '%d', '%s' )
	);
	// phpcs:enable
	return $result !== false;
}

/**
 * Check if a notification type is currently snoozed for a user.
 *
 * @param int    $user_id User ID.
 * @param string $type    Notification type slug.
 * @return bool
 */
function apollo_is_type_snoozed( int $user_id, string $type ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notif_prefs';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$until = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT snoozed_until FROM {$table} WHERE user_id = %d AND notif_type = %s",
			$user_id,
			sanitize_key( $type )
		)
	);
	// phpcs:enable
	if ( ! $until ) {
		return false;
	}
	return strtotime( $until ) > time();
}

/**
 * Delete all expired notifications from the database.
 * Typically called from the daily cron.
 *
 * @return int Number of rows deleted.
 */
function apollo_cleanup_expired_notifications(): int {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_notifications';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	return (int) $wpdb->query(
		"DELETE FROM {$table} WHERE expires_at IS NOT NULL AND expires_at < NOW()"
	);
	// phpcs:enable
}
