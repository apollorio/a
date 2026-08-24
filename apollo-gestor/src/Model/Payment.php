<?php

/**
 * Payment Model — CRUD for event finances
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payment {


	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_gestor_payments';
	}

	/**
	 * Get all payments for an event
	 */
	public static function get_by_event( int $event_id ): array {
		global $wpdb;
		$table = self::table();

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE event_id = %d ORDER BY status ASC, due_date ASC",
				$event_id
			),
			ARRAY_A
		) ?: array();

		foreach ( $rows as &$row ) {
			if ( $row['payee_type'] === 'user' ) {
				$user                = get_userdata( (int) $row['payee_id'] );
				$row['payee_name']   = $user ? $user->display_name : 'Desconhecido';
				$row['payee_avatar'] = $user ? get_avatar_url( $user->ID, array( 'size' => 44 ) ) : '';
			} elseif ( $row['payee_type'] === 'staff' ) {
				$staff               = \Apollo\Gestor\Model\Staff::get( (int) $row['payee_id'] );
				$row['payee_name']   = $staff ? $staff['name'] : 'Staff Desconhecido';
				$row['payee_avatar'] = '';
			} else {
				$row['payee_name']   = get_the_title( (int) $row['payee_id'] ) ?: sanitize_text_field( $row['description'] );
				$row['payee_avatar'] = '';
			}
			$row['pix_masked'] = self::mask_pix( $row['pix_key'] ?? '' );
		}

		return $rows;
	}

	/**
	 * Get financial summary for an event
	 *
	 * @return array { budget, staff_total, supplier_total, production_total, paid, pending, late, balance }
	 */
	public static function get_summary( int $event_id ): array {
		global $wpdb;
		$table = self::table();

		$budget = (float) get_post_meta( $event_id, '_event_budget', true );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT payee_type, category, status, SUM(amount) AS total
                 FROM {$table}
                 WHERE event_id = %d
                 GROUP BY payee_type, category, status",
				$event_id
			),
			ARRAY_A
		) ?: array();

		$summary = array(
			'budget'           => $budget,
			'staff_total'      => 0.0,
			'supplier_total'   => 0.0,
			'production_total' => 0.0,
			'paid'             => 0.0,
			'pending'          => 0.0,
			'late'             => 0.0,
		);

		foreach ( $rows as $row ) {
			$total = (float) $row['total'];

			if ( $row['payee_type'] === 'staff' ) {
				$summary['staff_total'] += $total;
			} else {
				$summary['supplier_total'] += $total;
			}

			if ( $row['category'] === 'production' ) {
				$summary['production_total'] += $total;
			}

			switch ( $row['status'] ) {
				case 'paid':
					$summary['paid'] += $total;
					break;
				case 'pending':
					$summary['pending'] += $total;
					break;
				case 'late':
					$summary['late'] += $total;
					break;
			}
		}

		$total_spent        = $summary['paid'] + $summary['pending'] + $summary['late'];
		$summary['balance'] = $budget - $total_spent;

		return $summary;
	}

	/**
	 * Create a payment
	 */
	public static function create( array $data ): int|false {
		global $wpdb;

		$insert = array(
			'event_id'    => absint( $data['event_id'] ?? 0 ),
			'payee_type'  => sanitize_key( $data['payee_type'] ?? 'staff' ),
			'payee_id'    => absint( $data['payee_id'] ?? 0 ),
			'payee_name'  => sanitize_text_field( $data['payee_name'] ?? '' ),
			'description' => sanitize_text_field( $data['description'] ?? '' ),
			'category'    => sanitize_text_field( $data['category'] ?? '' ),
			'amount'      => floatval( $data['amount'] ?? 0 ),
			'pix_key'     => sanitize_text_field( $data['pix_key'] ?? '' ),
			'method'      => sanitize_key( $data['method'] ?? 'pix' ),
			'status'      => sanitize_key( $data['status'] ?? 'pending' ),
			'due_date'    => ! empty( $data['due_date'] ) ? sanitize_text_field( $data['due_date'] ) : null,
			'notes'       => sanitize_textarea_field( $data['notes'] ?? '' ),
			'created_by'  => get_current_user_id(),
		);

		$formats = array( '%d', '%s', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%d' );

		$result = $wpdb->insert( self::table(), $insert, $formats );

		if ( $result ) {
			$id = (int) $wpdb->insert_id;

			/**
			 * Fires after a payment is created.
			 *
			 * @param int   $id     Payment ID.
			 * @param array $insert Payment data.
			 */
			do_action( 'apollo/gestor/payment_created', $id, $insert );

			return $id;
		}

		return false;
	}

	/**
	 * Update payment
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$allowed = array( 'description', 'category', 'amount', 'paid_amount', 'pix_key', 'status', 'due_date', 'paid_at', 'payee_name', 'method', 'notes' );
		$update  = array();
		$formats = array();

		foreach ( $allowed as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}
			if ( in_array( $field, array( 'amount', 'paid_amount' ), true ) ) {
				$update[ $field ] = floatval( $data[ $field ] );
				$formats[]        = '%f';
			} elseif ( $field === 'notes' ) {
				$update[ $field ] = sanitize_textarea_field( $data[ $field ] );
				$formats[]        = '%s';
			} else {
				$update[ $field ] = sanitize_text_field( $data[ $field ] );
				$formats[]        = '%s';
			}
		}

		if ( empty( $update ) ) {
			return false;
		}

		$result = $wpdb->update( self::table(), $update, array( 'id' => $id ), $formats, array( '%d' ) );

		if ( false !== $result ) {
			/**
			 * Fires after a payment is updated.
			 *
			 * @param int   $id     Payment ID.
			 * @param array $update Updated fields.
			 */
			do_action( 'apollo/gestor/payment_updated', $id, $update );
		}

		return false !== $result;
	}

	/**
	 * Delete a payment
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		/**
		 * Fires before a payment is deleted.
		 *
		 * @param int $id Payment ID.
		 */
		do_action( 'apollo/gestor/payment_before_delete', $id );

		return (bool) $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Mask PIX key for display.
	 */
	private static function mask_pix( string $pix ): string {
		if ( empty( $pix ) ) {
			return '';
		}

		if ( str_contains( $pix, '@' ) ) {
			$parts = explode( '@', $pix );
			return substr( $parts[0], 0, 2 ) . '***@' . $parts[1];
		}

		if ( preg_match( '/^\d{11}$/', $pix ) ) {
			return 'CPF ***' . substr( $pix, 3, 3 ) . '***';
		}

		if ( preg_match( '/^\d{10,}$/', preg_replace( '/\D/', '', $pix ) ) ) {
			$clean = preg_replace( '/\D/', '', $pix );
			return '(' . substr( $clean, 0, 2 ) . ') 9***-' . substr( $clean, -4 );
		}

		return substr( $pix, 0, 3 ) . '***' . substr( $pix, -3 );
	}
}
