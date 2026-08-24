<?php

/**
 * Task Model — CRUD for gestor tasks
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Task {


	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_gestor_tasks';
	}

	/**
	 * Get tasks for an event
	 *
	 * @param int    $event_id
	 * @param string $status   Filter by status (empty = all)
	 * @return array
	 */
	public static function get_by_event( int $event_id, string $status = '' ): array {
		global $wpdb;
		$table = self::table();

		$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id = %d", $event_id ); // phpcs:ignore WordPress.DB.PreparedSQL

		if ( ! empty( $status ) ) {
			$sql .= $wpdb->prepare( ' AND status = %s', $status );
		}

		$sql .= ' ORDER BY sort_order ASC, due_date ASC, id ASC';

		return $wpdb->get_results( $sql, ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get tasks assigned to a user across all events
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function get_by_assignee( int $user_id ): array {
		global $wpdb;
		$table = self::table();

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE assignee_id = %d AND status != 'canceled' ORDER BY due_date ASC",
				$user_id
			),
			ARRAY_A
		) ?: array();
	}

	/**
	 * Get a single task
	 *
	 * @param int $id
	 * @return array|null
	 */
	public static function get( int $id ): ?array {
		global $wpdb;
		$table = self::table();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Create a task
	 *
	 * @param array $data
	 * @return int|false  Inserted ID or false
	 */
	public static function create( array $data ): int|false {
		global $wpdb;

		$defaults = array(
			'event_id'          => 0,
			'title'             => '',
			'description'       => '',
			'assignee_id'       => 0,
			'category'          => '',
			'priority'          => 'medium',
			'status'            => 'planned',
			'due_date'          => null,
			'sort_order'        => 0,
			'parent_id'         => 0,
			'reminder_enabled'  => 0,
			'reminder_offset'   => '24h',
			'created_by'        => get_current_user_id(),
		);

		$data    = wp_parse_args( $data, $defaults );
		$formats = array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d' );

		$insert = array(
			'event_id'         => absint( $data['event_id'] ),
			'title'            => sanitize_text_field( $data['title'] ),
			'description'      => wp_kses_post( $data['description'] ),
			'assignee_id'      => absint( $data['assignee_id'] ),
			'category'         => sanitize_text_field( $data['category'] ),
			'priority'         => sanitize_key( $data['priority'] ),
			'status'           => sanitize_key( $data['status'] ),
			'due_date'         => $data['due_date'] ? sanitize_text_field( $data['due_date'] ) : null,
			'sort_order'       => absint( $data['sort_order'] ),
			'parent_id'        => absint( $data['parent_id'] ),
			'created_by'       => absint( $data['created_by'] ),
			'reminder_offset'  => sanitize_text_field( $data['reminder_offset'] ),
			'reminder_enabled' => absint( $data['reminder_enabled'] ),
		);

		$result = $wpdb->insert( self::table(), $insert, $formats );

		if ( $result ) {
			$id = (int) $wpdb->insert_id;

			/**
			 * Fires after a task is created.
			 *
			 * @param int   $id     Task ID.
			 * @param array $insert Task data.
			 */
			do_action( 'apollo/gestor/task_created', $id, $insert );

			return $id;
		}

		return false;
	}

	/**
	 * Update a task
	 *
	 * @param int   $id
	 * @param array $data
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$allowed = array( 'title', 'description', 'assignee_id', 'category', 'priority', 'status', 'due_date', 'sort_order', 'parent_id', 'reminder_enabled', 'reminder_offset' );
		$update  = array();
		$formats = array();

		foreach ( $allowed as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}

			if ( in_array( $field, array( 'assignee_id', 'sort_order', 'parent_id', 'reminder_enabled' ), true ) ) {
				$update[ $field ] = absint( $data[ $field ] );
				$formats[]        = '%d';
			} else {
				$update[ $field ] = sanitize_text_field( $data[ $field ] );
				$formats[]        = '%s';
			}
		}

		if ( empty( $update ) ) {
			return false;
		}

		$result = $wpdb->update( self::table(), $update, array( 'id' => $id ), $formats, array( '%d' ) );

		if ( $result !== false ) {
			/**
			 * Fires after a task is updated.
			 *
			 * @param int   $id     Task ID.
			 * @param array $update Updated fields.
			 */
			do_action( 'apollo/gestor/task_updated', $id, $update );
		}

		return $result !== false;
	}

	/**
	 * Toggle task status (planned ↔ delivered)
	 *
	 * @param int $id
	 * @return string|false  New status or false
	 */
	public static function toggle( int $id ): string|false {
		$task = self::get( $id );
		if ( ! $task ) {
			return false;
		}

		$new_status = ( $task['status'] === 'delivered' ) ? 'planned' : 'delivered';
		$updated    = self::update( $id, array( 'status' => $new_status ) );

		return $updated ? $new_status : false;
	}

	/**
	 * Delete a task
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		/**
		 * Fires before a task is deleted.
		 *
		 * @param int $id Task ID.
		 */
		do_action( 'apollo/gestor/task_before_delete', $id );

		return (bool) $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Get task counts by status for an event
	 *
	 * @param int $event_id
	 * @return array  { total, planned, tostart, ongoing, delayed, canceled, delivered, overdue }
	 */
	public static function get_counts( int $event_id ): array {
		global $wpdb;
		$table = self::table();

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS cnt FROM {$table} WHERE event_id = %d GROUP BY status",
				$event_id
			),
			ARRAY_A
		) ?: array();

		$counts = array(
			'total'     => 0,
			'planned'   => 0,
			'tostart'   => 0,
			'ongoing'   => 0,
			'delayed'   => 0,
			'canceled'  => 0,
			'delivered' => 0,
		);

		foreach ( $rows as $row ) {
			if ( isset( $counts[ $row['status'] ] ) ) {
				$counts[ $row['status'] ] = (int) $row['cnt'];
			}
		}

		$counts['total'] = $counts['planned'] + $counts['tostart'] + $counts['ongoing'] + $counts['delayed'] + $counts['canceled'] + $counts['delivered'];

		// Overdue count (active tasks past due date)
		$counts['overdue'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE event_id = %d AND status IN ('planned','tostart','ongoing') AND due_date < CURDATE()",
				$event_id
			)
		);

		return $counts;
	}
}
