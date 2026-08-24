<?php
/**
 * Apollo Calendar — Cache Buster
 *
 * Hooks into ecosystem actions to bust the per-user aggregator cache
 * when the underlying data changes.
 *
 * @package Apollo\Calendar
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CacheBuster {

	public static function init(): void {
		// Personal appointment writes (handled inside CalendarController after DB ops).

		// RSVP create / cancel (apollo-events).
		add_action( 'apollo/event/rsvp',           array( self::class, 'on_rsvp' ), 10, 2 );
		add_action( 'apollo/event/rsvp_cancelled', array( self::class, 'on_rsvp' ), 10, 2 );

		// Scheduler booking / reschedule (apollo-scheduler).
		add_action( 'apollo/scheduler/booked',      array( self::class, 'on_scheduler' ), 10, 2 );
		add_action( 'apollo/scheduler/rescheduled', array( self::class, 'on_scheduler' ), 10, 2 );
	}

	/**
	 * @param int $user_id
	 * @param int $event_id
	 */
	public static function on_rsvp( int $user_id, int $event_id ): void {
		CalendarAggregator::bust_user( $user_id );
	}

	/**
	 * @param int $user_id
	 * @param int $appointment_post_id
	 */
	public static function on_scheduler( int $user_id, int $appointment_post_id ): void {
		CalendarAggregator::bust_user( $user_id );
	}
}
