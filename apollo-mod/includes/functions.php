<?php
/**
 * Apollo Mod — Helper Functions
 *
 * @package Apollo\Mod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Report content for moderation.
 */
function apollo_report_content( int $reporter_id, string $object_type, int $object_id, string $reason, string $details = '' ): int|false {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_reports';

	$wpdb->insert(
		$table,
		array(
			'reporter_id' => $reporter_id,
			'object_type' => $object_type,
			'object_id'   => $object_id,
			'reason'      => sanitize_text_field( $reason ),
			'details'     => wp_kses_post( $details ),
			'status'      => 'pending',
			'created_at'  => current_time( 'mysql' ),
		)
	);

	$report_id = (int) $wpdb->insert_id;
	if ( ! $report_id ) {
		return false;
	}

	// Notify admins
	if ( function_exists( 'apollo_create_notification' ) ) {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'fields' => 'ID',
			)
		);
		foreach ( $admins as $admin_id ) {
			apollo_create_notification(
				(int) $admin_id,
				'mod_report',
				'Novo report de conteúdo',
				"Tipo: {$object_type}, Razão: {$reason}",
				admin_url( 'admin.php?page=apollo-mod' ),
				array( 'report_id' => $report_id )
			);
		}
	}

	do_action( 'apollo/mod/reported', $report_id, $object_type, $object_id );
	return $report_id;
}

/**
 * Take moderation action.
 */
function apollo_mod_action( int $moderator_id, string $action_type, string $target_type, int $target_id, string $reason = '', array $metadata = array() ): int|false {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_actions';

	$wpdb->insert(
		$table,
		array(
			'moderator_id' => $moderator_id,
			'action_type'  => $action_type,
			'target_type'  => $target_type,
			'target_id'    => $target_id,
			'reason'       => $reason,
			'metadata'     => ! empty( $metadata ) ? wp_json_encode( $metadata ) : null,
			'created_at'   => current_time( 'mysql' ),
		)
	);

	$action_id = (int) $wpdb->insert_id;
	if ( ! $action_id ) {
		return false;
	}

	// Apply action to target
	if ( $target_type === 'post' && get_post( $target_id ) ) {
		update_post_meta( $target_id, '_mod_status', $action_type );
		update_post_meta( $target_id, '_mod_reviewed_by', $moderator_id );
		update_post_meta( $target_id, '_mod_reviewed_at', current_time( 'mysql' ) );
		if ( $reason ) {
			update_post_meta( $target_id, '_mod_notes', $reason );
		}

		if ( $action_type === 'rejected' ) {
			wp_update_post(
				array(
					'ID'          => $target_id,
					'post_status' => 'draft',
				)
			);
		}
	}

	do_action( 'apollo/mod/action_taken', $action_id, $action_type, $target_type, $target_id );
	return $action_id;
}

/**
 * Get moderation queue.
 */
function apollo_get_mod_queue( string $status = 'pending', int $limit = 20, int $offset = 0 ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_reports';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT r.*, u.display_name as reporter_name
         FROM {$table} r
         LEFT JOIN {$wpdb->users} u ON r.reporter_id = u.ID
         WHERE r.status = %s
         ORDER BY r.created_at DESC
         LIMIT %d OFFSET %d",
			$status,
			$limit,
			$offset
		),
		ARRAY_A
	) ?: array();
}

/**
 * Resolve a report.
 */
function apollo_resolve_report( int $report_id, int $reviewer_id, string $status, string $action_taken = '' ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_reports';

	$updated = (bool) $wpdb->update(
		$table,
		array(
			'status'       => $status,
			'reviewed_by'  => $reviewer_id,
			'reviewed_at'  => current_time( 'mysql' ),
			'action_taken' => $action_taken,
		),
		array( 'id' => $report_id )
	);

	if ( $updated ) {
		do_action( 'apollo/mod/report_resolved', $report_id, $status, $reviewer_id, $action_taken );
	}

	return $updated;
}

/**
 * Get moderation stats.
 */
function apollo_get_mod_stats(): array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_mod_reports';

	$stats = $wpdb->get_results(
		"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
		ARRAY_A
	);

	$result = array(
		'pending'   => 0,
		'reviewed'  => 0,
		'actioned'  => 0,
		'dismissed' => 0,
		'total'     => 0,
	);
	foreach ( $stats as $row ) {
		$result[ $row['status'] ] = (int) $row['count'];
		$result['total']         += (int) $row['count'];
	}

	return $result;
}
