<?php

/**
 * Income Model — CRUD for event income / receitas
 *
 * Replaces _gestor_income_items post_meta JSON (deprecated since DB v4).
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Income {


	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_gestor_income';
	}

	/**
	 * Get all income items for an event, ordered by date then insertion order.
	 *
	 * @param int $event_id
	 * @return array
	 */
	public static function get_by_event( int $event_id ): array {
		global $wpdb;
		$table = self::table();

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE event_id = %d ORDER BY date ASC, id ASC",
				$event_id
			),
			ARRAY_A
		) ?: array();

		return array_map( static function ( array $row ): array {
			$row['amount'] = (float) $row['amount'];
			$row['unit']   = (float) $row['unit'];
			$row['qty']    = (int) $row['qty'];
			return $row;
		}, $rows );
	}

	/**
	 * Get a single income item by ID.
	 *
	 * @param int $id
	 * @return array|null
	 */
	public static function get_by_id( int $id ): ?array {
		global $wpdb;
		$table = self::table();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$row['amount'] = (float) $row['amount'];
		$row['unit']   = (float) $row['unit'];
		$row['qty']    = (int) $row['qty'];

		return $row;
	}

	/**
	 * Create an income item.
	 *
	 * @param array $data
	 * @return int|false  Inserted row ID or false on failure.
	 */
	public static function create( array $data ): int|false {
		global $wpdb;

		$allowed_cats = array( 'ingressos', 'bar', 'patrocinio', 'cobertura', 'outros' );
		$category     = sanitize_key( $data['category'] ?? 'outros' );
		if ( ! in_array( $category, $allowed_cats, true ) ) {
			$category = 'outros';
		}

		$insert = array(
			'event_id'    => absint( $data['event_id'] ?? 0 ),
			'category'    => $category,
			'description' => sanitize_text_field( $data['description'] ?? '' ),
			'amount'      => round( floatval( $data['amount'] ?? 0 ), 2 ),
			'date'        => ! empty( $data['date'] ) ? sanitize_text_field( $data['date'] ) : null,
			'qty'         => max( 1, absint( $data['qty'] ?? 1 ) ),
			'unit'        => round( floatval( $data['unit'] ?? 0 ), 2 ),
			'created_by'  => absint( $data['created_by'] ?? get_current_user_id() ),
		);

		$formats = array( '%d', '%s', '%s', '%f', '%s', '%d', '%f', '%d' );

		$result = $wpdb->insert( self::table(), $insert, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( $result ) {
			$id = (int) $wpdb->insert_id;

			/**
			 * Fires after an income item is created.
			 *
			 * @param int   $id     Income row ID.
			 * @param array $insert Income data.
			 */
			do_action( 'apollo/gestor/income_created', $id, $insert );

			return $id;
		}

		return false;
	}

	/**
	 * Delete an income item.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		/**
		 * Fires before an income item is deleted.
		 *
		 * @param int $id Income row ID.
		 */
		do_action( 'apollo/gestor/income_before_delete', $id );

		return (bool) $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Return the sum of all income amounts for an event.
	 *
	 * @param int $event_id
	 * @return float
	 */
	public static function get_total( int $event_id ): float {
		global $wpdb;
		$table = self::table();

		$total = $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare( "SELECT SUM(amount) FROM {$table} WHERE event_id = %d", $event_id )
		);

		return round( (float) $total, 2 );
	}
}
