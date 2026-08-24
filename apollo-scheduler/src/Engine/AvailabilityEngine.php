<?php
/**
 * Availability engine — Apollo-native port of Easy!Appointments slot logic.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Engine;

use Apollo\Scheduler\Cache\AvailabilityCache;
use Apollo\Scheduler\Models\AppointmentModel;
use Apollo\Scheduler\Models\ServiceModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AvailabilityEngine {

	private AppointmentModel $appointments;
	private ServiceModel $services;

	public function __construct(
		?AppointmentModel $appointments = null,
		?ServiceModel $services = null
	) {
		$this->appointments = $appointments ?? new AppointmentModel();
		$this->services     = $services ?? new ServiceModel();
	}

	/**
	 * @return array<int, string>
	 */
	public function get_available_hours(
		string $date,
		int $service_id,
		int $agent_id,
		?int $resource_id = null,
		?int $exclude_appointment_id = null
	): array {
		$cache_key = sprintf(
			'%s_%d_%d_%d_%s',
			$date,
			$service_id,
			$agent_id,
			(int) $resource_id,
			(string) $exclude_appointment_id
		);

		$cached = AvailabilityCache::get( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$service = $this->services->get( $service_id );
		if ( null === $service ) {
			return array();
		}

		$day_plan = WorkingPlan::for_date( $agent_id, $date );
		if ( null === $day_plan ) {
			return array();
		}

		$periods = array(
			array(
				'start' => new \DateTime( $date . ' ' . $day_plan['start'] ),
				'end'   => new \DateTime( $date . ' ' . $day_plan['end'] ),
			),
		);

		$periods = PeriodHelper::remove_breaks( $date, $periods, $day_plan['breaks'] );

		$blocking = $this->appointments->get_blocking_events_for_agent( $agent_id, $date, $exclude_appointment_id );

		if ( $resource_id ) {
			$blocking = array_merge(
				$blocking,
				$this->appointments->get_blocking_events_for_resource( $resource_id, $date, $exclude_appointment_id )
			);
		}

		$periods = PeriodHelper::remove_blocking_events( $periods, $blocking );

		$string_periods = array();
		foreach ( $periods as $period ) {
			$string_periods[] = array(
				'start' => $period['start']->format( 'H:i' ),
				'end'   => $period['end']->format( 'H:i' ),
			);
		}

		$hours = PeriodHelper::generate_available_hours(
			$date,
			(int) $service['duration'],
			(int) $service['slot_interval'],
			$string_periods
		);

		$hours = $this->consider_book_advance_timeout( $date, $hours );
		$hours = $this->consider_future_booking_limit( $date, $hours );

		$hours = apply_filters( 'apollo/scheduler/available_hours', $hours, $date, $service_id, $agent_id, $resource_id );

		AvailabilityCache::set( $cache_key, $hours );

		return $hours;
	}

	/**
	 * @return array<int, array{time:string,available:bool,reason?:string}>
	 */
	public function get_availability_grid(
		string $date,
		int $service_id,
		int $agent_id,
		?int $resource_id = null
	): array {
		$hours = $this->get_available_hours( $date, $service_id, $agent_id, $resource_id );
		$hour_map = array_fill_keys( $hours, true );

		$all_slots = array();
		$day_plan  = WorkingPlan::for_date( $agent_id, $date );

		if ( null === $day_plan ) {
			return array();
		}

		$cursor = new \DateTime( $date . ' ' . $day_plan['start'] );
		$end    = new \DateTime( $date . ' ' . $day_plan['end'] );
		$service = $this->services->get( $service_id );
		$interval = max( 1, (int) ( $service['slot_interval'] ?? 15 ) );

		while ( $cursor < $end ) {
			$time = $cursor->format( 'H:i' );
			$available = isset( $hour_map[ $time ] );

			$slot = array(
				'time'      => $time,
				'available' => $available,
			);

			if ( ! $available ) {
				$slot['reason'] = $this->resolve_block_reason( $date, $time, $agent_id, $resource_id );
			}

			$all_slots[] = $slot;
			$cursor->add( new \DateInterval( 'PT' . $interval . 'M' ) );
		}

		return $all_slots;
	}

	/**
	 * @param array<int, string> $available_hours
	 * @return array<int, string>
	 */
	private function consider_book_advance_timeout( string $date, array $available_hours ): array {
		$timeout = (int) apply_filters( 'apollo/scheduler/book_advance_timeout', 60 );
		$threshold = new \DateTime( 'now', wp_timezone() );
		$threshold->modify( '+' . max( 0, $timeout ) . ' minutes' );

		foreach ( $available_hours as $index => $value ) {
			$available_hour = new \DateTime( $date . ' ' . $value, wp_timezone() );
			if ( $available_hour->getTimestamp() <= $threshold->getTimestamp() ) {
				unset( $available_hours[ $index ] );
			}
		}

		sort( $available_hours, SORT_STRING );
		return array_values( $available_hours );
	}

	/**
	 * @param array<int, string> $available_hours
	 * @return array<int, string>
	 */
	private function consider_future_booking_limit( string $date, array $available_hours ): array {
		$limit_days = (int) apply_filters( 'apollo/scheduler/future_booking_limit', 90 );
		$threshold  = new \DateTime( 'now', wp_timezone() );
		$threshold->modify( '+' . max( 0, $limit_days ) . ' days' );
		$selected   = new \DateTime( $date, wp_timezone() );

		if ( $threshold < $selected ) {
			return array();
		}

		return $available_hours;
	}

	private function resolve_block_reason( string $date, string $time, int $agent_id, ?int $resource_id ): string {
		$blocking = $this->appointments->get_blocking_events_for_agent( $agent_id, $date );

		foreach ( $blocking as $event ) {
			$start = strtotime( $event['start_datetime'] );
			$end   = strtotime( $event['end_datetime'] );
			$slot  = strtotime( $date . ' ' . $time );

			if ( $slot >= $start && $slot < $end ) {
				return __( 'Already booked', 'apollo-scheduler' );
			}
		}

		if ( $resource_id ) {
			$resource_blocks = $this->appointments->get_blocking_events_for_resource( $resource_id, $date );
			if ( ! empty( $resource_blocks ) ) {
				return __( 'Room unavailable', 'apollo-scheduler' );
			}
		}

		return __( 'Outside working hours', 'apollo-scheduler' );
	}
}
