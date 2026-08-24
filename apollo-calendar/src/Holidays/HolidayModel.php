<?php
/**
 * Apollo Calendar — Holiday Model
 *
 * Read/Write interface for the apollo_holidays table.
 * The table is admin-editable and seeded from BR national + Rio municipal data.
 *
 * @package Apollo\Calendar\Holidays
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar\Holidays;

use Apollo\Core\Config\ApolloTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HolidayModel {

	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . ApolloTable::HOLIDAYS;
	}

	// ─── READ ──────────────────────────────────────────────────────────

	/**
	 * Get holidays in a date range for a given scope/region.
	 *
	 * @param string   $from_date  'Y-m-d' start.
	 * @param string   $to_date    'Y-m-d' end.
	 * @param string[] $scopes     Allowed scopes, e.g. ['national','municipal'].
	 * @param string   $region     Region code for filtering municipal rows.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_by_range(
		string $from_date,
		string $to_date,
		array  $scopes = array( 'national' ),
		string $region = 'RJ'
	): array {
		global $wpdb;

		$table        = self::table();
		$placeholders = implode( ',', array_fill( 0, count( $scopes ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table/placeholders are trusted.
				"SELECT * FROM {$table}
				 WHERE holiday_date BETWEEN %s AND %s
				   AND (scope IN ({$placeholders})
				        OR (scope = 'municipal' AND region = %s))
				 ORDER BY holiday_date ASC",
				...array_merge( array( $from_date, $to_date ), $scopes, array( $region ) )
			),
			ARRAY_A
		);

		return $rows ?: array();
	}

	/**
	 * Get all holidays for admin listing (paginated).
	 *
	 * @param int $year  Year to filter, 0 = all.
	 * @param int $page  1-based page number.
	 * @param int $limit Rows per page.
	 * @return array{rows:array, total:int}
	 */
	public static function get_all_admin( int $year = 0, int $page = 1, int $limit = 50 ): array {
		global $wpdb;

		$table  = self::table();
		$offset = ( max( 1, $page ) - 1 ) * $limit;

		if ( $year > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE year = %d ORDER BY holiday_date ASC LIMIT %d OFFSET %d",
					$year,
					$limit,
					$offset
				),
				ARRAY_A
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE year = %d", $year )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} ORDER BY holiday_date ASC LIMIT %d OFFSET %d",
					$limit,
					$offset
				),
				ARRAY_A
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return array( 'rows' => $rows ?: array(), 'total' => $total );
	}

	// ─── WRITE ─────────────────────────────────────────────────────────

	/**
	 * Upsert a holiday row (insert or update on duplicate key).
	 *
	 * @param array<string,mixed> $data  Must contain holiday_date, title, scope, region, year.
	 * @return bool
	 */
	public static function upsert( array $data ): bool {
		global $wpdb;

		$row = self::sanitize( $data );
		if ( empty( $row['holiday_date'] ) ) {
			return false;
		}

		$existing = self::find_by_key(
			$row['holiday_date'],
			$row['scope'] ?? 'national',
			$row['region'] ?? ''
		);

		if ( $existing ) {
			$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				self::table(),
				$row,
				array( 'id' => (int) $existing['id'] )
			);
			return false !== $updated;
		}

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			self::table(),
			$row
		);
		return false !== $inserted;
	}

	/**
	 * Admin: update a single holiday row by ID.
	 *
	 * @param int                 $id   Row ID.
	 * @param array<string,mixed> $data Fields to update.
	 * @return bool
	 */
	public static function update_admin( int $id, array $data ): bool {
		global $wpdb;

		$row = self::sanitize( $data );
		if ( empty( $row ) ) {
			return false;
		}

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table(),
			$row,
			array( 'id' => $id )
		);
		return false !== $updated;
	}

	/**
	 * Admin: delete a holiday row.
	 *
	 * @param int $id Row ID.
	 * @return bool
	 */
	public static function delete_admin( int $id ): bool {
		global $wpdb;

		$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table(),
			array( 'id' => $id )
		);
		return false !== $deleted && $deleted > 0;
	}

	// ─── HELPERS ───────────────────────────────────────────────────────

	private static function find_by_key( string $date, string $scope, string $region ): ?array {
		global $wpdb;

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE holiday_date = %s AND scope = %s AND region = %s",
				$date,
				$scope,
				$region
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	private static function sanitize( array $data ): array {
		$clean = array();

		if ( isset( $data['holiday_date'] ) ) {
			$clean['holiday_date'] = sanitize_text_field( $data['holiday_date'] );
		}
		if ( isset( $data['title'] ) ) {
			$clean['title'] = sanitize_text_field( $data['title'] );
		}
		if ( isset( $data['scope'] ) ) {
			$allowed = array( 'national', 'state', 'municipal' );
			$v       = sanitize_key( $data['scope'] );
			$clean['scope'] = in_array( $v, $allowed, true ) ? $v : 'national';
		}
		if ( isset( $data['region'] ) ) {
			$clean['region'] = sanitize_text_field( $data['region'] );
		}
		if ( isset( $data['recurring'] ) ) {
			$clean['recurring'] = (int) $data['recurring'] ? 1 : 0;
		}
		if ( isset( $data['year'] ) ) {
			$clean['year'] = absint( $data['year'] );
		}
		if ( isset( $data['source'] ) ) {
			$clean['source'] = sanitize_key( $data['source'] );
		}

		return $clean;
	}
}
