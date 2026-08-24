<?php
/**
 * Apollo Calendar — Aggregator
 *
 * Merges four event sources into a single normalized array of items,
 * cached per user + range in the 'apollo' object-cache group (TTL 300s).
 *
 * Normalized shape per item:
 * {
 *   id        : string  (source-prefixed, e.g. "personal-42", "event-7", "appt-3", "holiday-12")
 *   source    : string  ('personal' | 'event' | 'scheduler' | 'holiday')
 *   title     : string
 *   start_iso : string  ISO-8601 UTC datetime or date (all_day)
 *   end_iso   : string|null
 *   all_day   : bool
 *   url       : string|null
 *   color     : string
 *   location  : string
 *   status    : string  e.g. 'going', 'interested', 'booked', 'holiday'
 *   editable  : bool    (only personal appts can be edited via REST)
 * }
 *
 * @package Apollo\Calendar
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar;

use Apollo\Calendar\Models\AppointmentModel;
use Apollo\Calendar\Holidays\HolidayModel;
use Apollo\Core\Config\ApolloTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CalendarAggregator {

	private const CACHE_GROUP = 'apollo';
	private const CACHE_TTL   = 300; // seconds

	// Source colour defaults (overridden by user colour prefs or admin settings).
	private const COLORS = array(
		'personal'  => '#6366f1',
		'event'     => '#f59e0b',
		'scheduler' => '#10b981',
		'holiday'   => '#e11d48',
	);

	/**
	 * Fetch and merge all calendar items for a user in the given UTC range.
	 *
	 * @param int    $user_id   Requesting user.
	 * @param string $from_utc  ISO-8601 UTC range start (e.g. "2026-01-01T00:00:00").
	 * @param string $to_utc    ISO-8601 UTC range end.
	 * @param string[] $sources Requested sources; empty = all.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get( int $user_id, string $from_utc, string $to_utc, array $sources = array() ): array {
		$cache_key = self::build_cache_key( $user_id, $from_utc, $to_utc, $sources );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$items = array();
		$all   = empty( $sources );

		if ( $all || in_array( 'personal', $sources, true ) ) {
			$items = array_merge( $items, self::personal( $user_id, $from_utc, $to_utc ) );
		}
		if ( $all || in_array( 'event', $sources, true ) ) {
			$items = array_merge( $items, self::events( $user_id, $from_utc, $to_utc ) );
		}
		if ( $all || in_array( 'scheduler', $sources, true ) ) {
			$items = array_merge( $items, self::scheduler( $user_id, $from_utc, $to_utc ) );
		}
		if ( $all || in_array( 'holiday', $sources, true ) ) {
			$items = array_merge( $items, self::holidays( $user_id, $from_utc, $to_utc ) );
		}

		// Sort ascending by start.
		usort( $items, static function ( array $a, array $b ): int {
			return strcmp( $a['start_iso'] ?? '', $b['start_iso'] ?? '' );
		} );

		wp_cache_set( $cache_key, $items, self::CACHE_GROUP, self::CACHE_TTL );

		return $items;
	}

	/**
	 * Invalidate all calendar cache entries for a user.
	 * Called on RSVP/scheduler/personal-appt mutations.
	 *
	 * @param int $user_id
	 */
	public static function bust_user( int $user_id ): void {
		wp_cache_delete( 'calendar_bust_' . $user_id, self::CACHE_GROUP );
		// Increment a per-user generation key — all old keys naturally miss.
		$gen = (int) wp_cache_get( 'cal_gen_' . $user_id, self::CACHE_GROUP );
		wp_cache_set( 'cal_gen_' . $user_id, $gen + 1, self::CACHE_GROUP, 0 );
	}

	// ─── Sources ───────────────────────────────────────────────────────

	/**
	 * Personal appointments from apollo_user_appointments.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function personal( int $user_id, string $from, string $to ): array {
		$rows  = AppointmentModel::get_by_range( $user_id, $from, $to );
		$items = array();

		foreach ( $rows as $row ) {
			$items[] = array(
				'id'        => 'personal-' . (int) $row['id'],
				'source'    => 'personal',
				'title'     => $row['title'],
				'start_iso' => $row['starts_at'],
				'end_iso'   => $row['ends_at'] ?: null,
				'all_day'   => (bool) $row['all_day'],
				'url'       => null,
				'color'     => $row['color'] ?: self::COLORS['personal'],
				'location'  => $row['location'] ?? '',
				'status'    => 'personal',
				'editable'  => true,
			);
		}

		return $items;
	}

	/**
	 * Event RSVPs from apollo_event_rsvp + event post meta.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function events( int $user_id, string $from, string $to ): array {
		global $wpdb;

		// Check if the event RSVP table exists before querying.
		$rsvp_table = $wpdb->prefix . ApolloTable::EVENT_RSVP;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rsvps = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- only trusted table name.
				"SELECT r.event_id, r.status
				 FROM {$rsvp_table} r
				 WHERE r.user_id = %d
				   AND r.status IN ('going','interested')
				 ORDER BY r.created_at DESC",
				$user_id
			),
			ARRAY_A
		);

		if ( empty( $rsvps ) ) {
			return array();
		}

		$items = array();

		foreach ( $rsvps as $rsvp ) {
			$event_id = (int) $rsvp['event_id'];
			$post     = get_post( $event_id );

			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			// Build UTC datetime from 4 meta fields.
			$start_date = get_post_meta( $event_id, '_event_start_date', true );
			$start_time = get_post_meta( $event_id, '_event_start_time', true ) ?: '00:00:00';
			$end_date   = get_post_meta( $event_id, '_event_end_date', true );
			$end_time   = get_post_meta( $event_id, '_event_end_time', true ) ?: '00:00:00';

			if ( empty( $start_date ) ) {
				continue;
			}

			$start_iso = self::local_to_utc( $start_date . ' ' . $start_time );
			$end_iso   = $end_date ? self::local_to_utc( $end_date . ' ' . $end_time ) : null;

			// Skip events outside the requested range.
			if ( $start_iso > $to || ( $end_iso && $end_iso < $from ) || ( ! $end_iso && $start_iso < $from ) ) {
				continue;
			}

			$items[] = array(
				'id'        => 'event-' . $event_id,
				'source'    => 'event',
				'title'     => get_the_title( $post ),
				'start_iso' => $start_iso,
				'end_iso'   => $end_iso,
				'all_day'   => false,
				'url'       => get_permalink( $post ),
				'color'     => self::COLORS['event'],
				'location'  => (string) ( get_post_meta( $event_id, '_event_location', true ) ?: '' ),
				'status'    => $rsvp['status'],
				'editable'  => false,
			);
		}

		return $items;
	}

	/**
	 * Scheduler bookings (appointment CPT).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function scheduler( int $user_id, string $from, string $to ): array {
		$query = new \WP_Query( array(
			'post_type'      => 'appointment',
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => 200,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => '_apollo_appointment_customer_id',
					'value' => $user_id,
				),
				array(
					'key'     => '_apollo_appointment_start',
					'value'   => array( $from, $to ),
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME',
				),
			),
			'no_found_rows'  => true,
			'fields'         => 'ids',
		) );

		if ( empty( $query->posts ) ) {
			return array();
		}

		$items = array();

		foreach ( $query->posts as $post_id ) {
			$post_id   = (int) $post_id;
			$start_raw = get_post_meta( $post_id, '_apollo_appointment_start', true );
			$end_raw   = get_post_meta( $post_id, '_apollo_appointment_end', true );
			$service   = get_post_meta( $post_id, '_apollo_appointment_service_title', true );

			if ( ! $start_raw ) {
				continue;
			}

			$items[] = array(
				'id'        => 'scheduler-' . $post_id,
				'source'    => 'scheduler',
				'title'     => $service ? (string) $service : get_the_title( $post_id ),
				'start_iso' => $start_raw,
				'end_iso'   => $end_raw ?: null,
				'all_day'   => false,
				'url'       => null,
				'color'     => self::COLORS['scheduler'],
				'location'  => '',
				'status'    => 'booked',
				'editable'  => false,
			);
		}

		return $items;
	}

	/**
	 * Holidays visible to the user based on their region pref.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function holidays( int $user_id, string $from, string $to ): array {
		$prefs  = (array) get_user_meta( $user_id, '_apollo_calendar_prefs', true );
		$region = sanitize_text_field( $prefs['holiday_region'] ?? 'RJ' );

		$from_date = substr( $from, 0, 10 );
		$to_date   = substr( $to, 0, 10 );

		$rows  = HolidayModel::get_by_range( $from_date, $to_date, array( 'national', 'state', 'municipal' ), $region );
		$items = array();

		foreach ( $rows as $row ) {
			$items[] = array(
				'id'        => 'holiday-' . (int) $row['id'],
				'source'    => 'holiday',
				'title'     => $row['title'],
				'start_iso' => $row['holiday_date'],
				'end_iso'   => null,
				'all_day'   => true,
				'url'       => null,
				'color'     => self::COLORS['holiday'],
				'location'  => '',
				'status'    => 'holiday',
				'editable'  => false,
			);
		}

		return $items;
	}

	// ─── Helpers ───────────────────────────────────────────────────────

	/**
	 * Convert a local (site timezone) datetime string to a UTC ISO-8601 string.
	 *
	 * @param string $local_dt "Y-m-d H:i:s" in site timezone.
	 * @return string UTC ISO-8601 datetime.
	 */
	private static function local_to_utc( string $local_dt ): string {
		try {
			$tz  = wp_timezone();
			$dt  = new \DateTimeImmutable( $local_dt, $tz );
			$utc = $dt->setTimezone( new \DateTimeZone( 'UTC' ) );
			return $utc->format( 'Y-m-d\TH:i:s\Z' );
		} catch ( \Throwable $e ) {
			return $local_dt;
		}
	}

	private static function build_cache_key( int $user_id, string $from, string $to, array $sources ): string {
		// Include a per-user generation counter so cache busting is instant.
		$gen = (int) wp_cache_get( 'cal_gen_' . $user_id, self::CACHE_GROUP );
		$src = implode( ',', $sources );
		return 'calendar_' . $user_id . '_' . md5( $from . $to . $src ) . '_g' . $gen;
	}
}
