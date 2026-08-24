<?php

/**
 * Milestone Model — Timeline markers for events
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Milestone {


	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_gestor_milestones';
	}

	/**
	 * Get milestones for an event
	 */
	public static function get_by_event( int $event_id ): array {
		global $wpdb;
		$table = self::table();

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE event_id = %d ORDER BY due_date ASC, sort_order ASC",
				$event_id
			),
			ARRAY_A
		) ?: array();
	}

	/**
	 * Get single milestone
	 */
	public static function get( int $id ): array|null {
		global $wpdb;
		return $wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ),
			ARRAY_A
		);
	}

	/**
	 * Create milestone
	 */
	public static function create( array $data ): int|false {
		global $wpdb;

		$max = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				'SELECT MAX(sort_order) FROM ' . self::table() . ' WHERE event_id = %d',
				absint( $data['event_id'] ?? 0 )
			)
		);

		$insert = array(
			'event_id'   => absint( $data['event_id'] ?? 0 ),
			'title'      => sanitize_text_field( $data['title'] ?? '' ),
			'icon'       => sanitize_text_field( $data['icon'] ?? 'ri-flag-line' ),
			'due_date'   => ! empty( $data['due_date'] ) ? sanitize_text_field( $data['due_date'] ) : null,
			'status'     => sanitize_key( $data['status'] ?? 'pending' ),
			'sort_order' => $max + 1,
		);

		$result = $wpdb->insert( self::table(), $insert );

		if ( $result ) {
			$id = (int) $wpdb->insert_id;

			/**
			 * Fires after a milestone is created.
			 *
			 * @param int   $id     Milestone ID.
			 * @param array $insert Milestone data.
			 */
			do_action( 'apollo/gestor/milestone_created', $id, $insert );

			return $id;
		}

		return false;
	}

	/**
	 * Update milestone
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$allowed = array( 'title', 'icon', 'due_date', 'status', 'sort_order' );
		$update  = array();

		foreach ( $allowed as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}
			$update[ $field ] = $field === 'sort_order' ? absint( $data[ $field ] ) : sanitize_text_field( $data[ $field ] );
		}

		if ( empty( $update ) ) {
			return false;
		}

		return false !== $wpdb->update( self::table(), $update, array( 'id' => $id ) );
	}

	/**
	 * Toggle milestone status (pending ↔ done)
	 */
	public static function toggle( int $id ): string|false {
		$row = self::get( $id );
		if ( ! $row ) {
			return false;
		}

		$new_status = $row['status'] === 'done' ? 'pending' : 'done';
		self::update( $id, array( 'status' => $new_status ) );

		/**
		 * Fires after a milestone is toggled.
		 *
		 * @param int    $id         Milestone ID.
		 * @param string $new_status New status ('done' or 'pending').
		 * @param int    $event_id   Event ID.
		 */
		do_action( 'apollo/gestor/milestone_toggled', $id, $new_status, (int) ( $row['event_id'] ?? 0 ) );

		return $new_status;
	}

	/**
	 * Delete milestone
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		/**
		 * Fires before a milestone is deleted.
		 *
		 * @param int $id Milestone ID.
		 */
		do_action( 'apollo/gestor/milestone_before_delete', $id );

		return (bool) $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Timeline progress: % of milestones marked done
	 */
	public static function get_progress( int $event_id ): float {
		global $wpdb;
		$table = self::table();

		$total = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_id = %d", $event_id )
		);

		if ( $total === 0 ) {
			return 0.0;
		}

		$done = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_id = %d AND status = 'done'", $event_id )
		);

		return round( ( $done / $total ) * 100, 1 );
	}
}
