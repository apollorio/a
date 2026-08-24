<?php
/**
 * Apollo Calendar — Appointment Model
 *
 * CRUD for personal user appointments stored in apollo_user_appointments.
 * All queries use $wpdb->prepare(). Rows are always scoped to the requesting
 * user (owner-only pattern) — never return another user's records.
 *
 * Timestamps stored and returned as UTC ISO-8601 strings.
 *
 * @package Apollo\Calendar\Models
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar\Models;

use Apollo\Core\Config\ApolloTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AppointmentModel {

	/** Columns allowed for create/update. */
	private const EDITABLE = array(
		'title',
		'description',
		'starts_at',
		'ends_at',
		'all_day',
		'location',
		'color',
		'recurrence',
		'remind_before',
	);

	/** @return string Full table name with DB prefix. */
	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . ApolloTable::USER_APPOINTMENTS;
	}

	// ─── READ ──────────────────────────────────────────────────────────

	/**
	 * Get all appointments for a user within a date range.
	 *
	 * @param int    $user_id  Owner ID.
	 * @param string $from_utc ISO-8601 UTC start of range.
	 * @param string $to_utc   ISO-8601 UTC end of range.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_by_range( int $user_id, string $from_utc, string $to_utc ): array {
		global $wpdb;

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE user_id  = %d
				   AND starts_at < %s
				   AND (ends_at >= %s OR (ends_at IS NULL AND starts_at >= %s))
				 ORDER BY starts_at ASC",
				$user_id,
				$to_utc,
				$from_utc,
				$from_utc
			),
			ARRAY_A
		);

		return $rows ?: array();
	}

	/**
	 * Get a single appointment, verifying ownership.
	 *
	 * @param int $id      Appointment primary key.
	 * @param int $user_id Expected owner.
	 * @return array<string,mixed>|null  Row or null if not found / not owned.
	 */
	public static function get_owned( int $id, int $user_id ): ?array {
		global $wpdb;

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
				$id,
				$user_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	// ─── WRITE ─────────────────────────────────────────────────────────

	/**
	 * Create a new appointment.
	 *
	 * @param int                  $user_id Owner.
	 * @param array<string,mixed>  $data    Field values (only EDITABLE keys honoured).
	 * @return int|false New ID, or false on failure.
	 */
	public static function create( int $user_id, array $data ): int|false {
		global $wpdb;

		$row = self::sanitize( $data );
		$row['user_id'] = $user_id;
		$row['source']  = 'personal';

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			self::table(),
			$row
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an appointment — owner must match.
	 *
	 * @param int                  $id      Appointment ID.
	 * @param int                  $user_id Owner.
	 * @param array<string,mixed>  $data    Fields to update.
	 * @return bool
	 */
	public static function update( int $id, int $user_id, array $data ): bool {
		global $wpdb;

		$row = self::sanitize( $data );

		if ( empty( $row ) ) {
			return false;
		}

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table(),
			$row,
			array( 'id' => $id, 'user_id' => $user_id )
		);

		return false !== $updated;
	}

	/**
	 * Delete an appointment — owner must match.
	 *
	 * @param int $id      Appointment ID.
	 * @param int $user_id Owner.
	 * @return bool
	 */
	public static function delete( int $id, int $user_id ): bool {
		global $wpdb;

		$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table(),
			array( 'id' => $id, 'user_id' => $user_id )
		);

		return false !== $deleted && $deleted > 0;
	}

	// ─── HELPERS ───────────────────────────────────────────────────────

	/**
	 * Sanitize & whitelist inbound data.
	 *
	 * @param array<string,mixed> $data Raw input.
	 * @return array<string,mixed>
	 */
	private static function sanitize( array $data ): array {
		$clean = array();

		if ( isset( $data['title'] ) ) {
			$clean['title'] = sanitize_text_field( $data['title'] );
		}
		if ( isset( $data['description'] ) ) {
			$clean['description'] = sanitize_textarea_field( $data['description'] );
		}
		if ( isset( $data['starts_at'] ) ) {
			$clean['starts_at'] = sanitize_text_field( $data['starts_at'] );
		}
		if ( isset( $data['ends_at'] ) ) {
			$v = sanitize_text_field( $data['ends_at'] );
			$clean['ends_at'] = $v !== '' ? $v : null;
		}
		if ( isset( $data['all_day'] ) ) {
			$clean['all_day'] = (int) $data['all_day'] ? 1 : 0;
		}
		if ( isset( $data['location'] ) ) {
			$clean['location'] = sanitize_text_field( $data['location'] );
		}
		if ( isset( $data['color'] ) ) {
			// Allow #rrggbb or named tokens; strip everything else.
			$raw = sanitize_text_field( $data['color'] );
			$clean['color'] = preg_match( '/^#?[0-9a-fA-F]{3,8}$/', $raw ) ? $raw : '';
		}
		if ( isset( $data['recurrence'] ) ) {
			$allowed = array( 'none', 'daily', 'weekly', 'monthly', 'yearly' );
			$val     = sanitize_key( $data['recurrence'] );
			$clean['recurrence'] = in_array( $val, $allowed, true ) ? $val : 'none';
		}
		if ( isset( $data['remind_before'] ) ) {
			$clean['remind_before'] = absint( $data['remind_before'] );
		}

		return $clean;
	}
}
