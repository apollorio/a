<?php
/**
 * Dual calendar bridge — links appointments to apollo-events / apollo-gestor.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Bridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CalendarBridge {

	/**
	 * Create or link calendar entry atomically with compensating hook on failure.
	 *
	 * @param array<string, mixed> $data
	 */
	public static function link_to_calendar( int $appointment_id, array $data ): int {
		$event_id = 0;

		/**
		 * Primary bridge: apollo-events creates linked event CPT.
		 */
		$event_id = (int) apply_filters( 'apollo/scheduler/create_event', $event_id, $appointment_id, $data );

		if ( $event_id <= 0 && post_type_exists( 'event' ) ) {
			$event_id = self::create_fallback_event( $appointment_id, $data );
		}

		if ( $event_id > 0 ) {
			do_action( 'apollo/scheduler/calendar_linked', $appointment_id, $event_id, $data );
			return $event_id;
		}

		add_action(
			'apollo/scheduler/booking_rollback',
			static function ( int $rolled_id ) use ( $appointment_id ): void {
				if ( $rolled_id === $appointment_id ) {
					wp_delete_post( $appointment_id, true );
				}
			}
		);

		return 0;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private static function create_fallback_event( int $appointment_id, array $data ): int {
		$start = (string) ( $data['start'] ?? '' );
		$end   = (string) ( $data['end'] ?? '' );

		$event_id = wp_insert_post(
			array(
				'post_type'   => 'event',
				'post_title'  => sprintf(
					/* translators: %d: appointment id */
					__( 'Appointment #%d', 'apollo-scheduler' ),
					$appointment_id
				),
				'post_status' => 'publish',
				'post_author' => (int) ( $data['customer_id'] ?? get_current_user_id() ),
			),
			true
		);

		if ( is_wp_error( $event_id ) ) {
			return 0;
		}

		update_post_meta( (int) $event_id, '_event_start_date', substr( $start, 0, 10 ) );
		update_post_meta( (int) $event_id, '_event_end_date', substr( $end, 0, 10 ) );
		update_post_meta( (int) $event_id, '_event_start_time', substr( $start, 11, 5 ) );
		update_post_meta( (int) $event_id, '_event_end_time', substr( $end, 11, 5 ) );
		update_post_meta( (int) $event_id, '_apollo_scheduler_appointment_id', $appointment_id );

		/**
		 * Secondary bridge: apollo-gestor milestone hook.
		 */
		do_action( 'apollo/gestor/milestone_from_appointment', $appointment_id, (int) $event_id, $data );

		return (int) $event_id;
	}
}
