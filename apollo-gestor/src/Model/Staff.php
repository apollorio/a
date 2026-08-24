<?php

/**
 * Staff Model — CRUD for non-user staff members
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Staff {


	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_gestor_staff';
	}

	/**
	 * Get staff members for an event
	 *
	 * @param int $event_id
	 * @return array  Rows with id, name, pix_key, price_deal, job_function, contact_info
	 */
	public static function get_by_event( int $event_id ): array {
		global $wpdb;
		$table = self::table();

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE event_id = %d ORDER BY name ASC",
				$event_id
			),
			ARRAY_A
		) ?: array();

		return $rows;
	}

	/**
	 * Create a new staff member
	 *
	 * @param array $data
	 * @return int|false  Staff ID or false on failure
	 */
	public static function create( array $data ): int|false {
		global $wpdb;
		$table = self::table();

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'event_id'     => $data['event_id'] ?? 0,
				'name'         => sanitize_text_field( $data['name'] ?? '' ),
				'pix_key'      => sanitize_text_field( $data['pix_key'] ?? '' ),
				'price_deal'   => floatval( $data['price_deal'] ?? 0 ),
				'job_function' => sanitize_text_field( $data['job_function'] ?? '' ),
				'contact_info' => sanitize_textarea_field( $data['contact_info'] ?? '' ),
				'created_by'   => get_current_user_id(),
			),
			array( '%d', '%s', '%s', '%f', '%s', '%s', '%d' )
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Update a staff member
	 *
	 * @param int   $staff_id
	 * @param array $data
	 * @return bool
	 */
	public static function update( int $staff_id, array $data ): bool {
		global $wpdb;
		$table = self::table();

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'name'         => sanitize_text_field( $data['name'] ?? '' ),
				'pix_key'      => sanitize_text_field( $data['pix_key'] ?? '' ),
				'price_deal'   => floatval( $data['price_deal'] ?? 0 ),
				'job_function' => sanitize_text_field( $data['job_function'] ?? '' ),
				'contact_info' => sanitize_textarea_field( $data['contact_info'] ?? '' ),
			),
			array( 'id' => $staff_id ),
			array( '%s', '%s', '%f', '%s', '%s' ),
			array( '%d' )
		);

		return $updated !== false;
	}

	/**
	 * Delete a staff member
	 *
	 * @param int $staff_id
	 * @return bool
	 */
	public static function delete( int $staff_id ): bool {
		global $wpdb;
		$table = self::table();

		$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array( 'id' => $staff_id ),
			array( '%d' )
		);

		return $deleted !== false;
	}

	/**
	 * Get a single staff member
	 *
	 * @param int $staff_id
	 * @return array|null
	 */
	public static function get( int $staff_id ): ?array {
		global $wpdb;
		$table = self::table();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$staff_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}
}