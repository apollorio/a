<?php
/**
 * Period manipulation helpers — ported from Easy!Appointments Availability library.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PeriodHelper {

	/**
	 * @param array<int, array{start:\DateTime,end:\DateTime}> $periods
	 * @param array<int, array{start:string,end:string}>     $breaks
	 * @return array<int, array{start:\DateTime,end:\DateTime}>
	 */
	public static function remove_breaks( string $date, array $periods, array $breaks ): array {
		if ( empty( $breaks ) ) {
			return $periods;
		}

		foreach ( $breaks as $break ) {
			$break_start = new \DateTime( $date . ' ' . $break['start'] );
			$break_end   = new \DateTime( $date . ' ' . $break['end'] );

			foreach ( $periods as &$period ) {
				$period_start = $period['start'];
				$period_end   = $period['end'];

				if ( $break_start <= $period_start && $break_end >= $period_start && $break_end <= $period_end ) {
					$period['start'] = $break_end;
					continue;
				}

				if ( $break_start >= $period_start && $break_start <= $period_end && $break_end >= $period_start && $break_end <= $period_end ) {
					$period['end'] = $break_start;
					$periods[]     = array(
						'start' => $break_end,
						'end'   => $period_end,
					);
					continue;
				}

				if ( $break_start >= $period_start && $break_start <= $period_end && $break_end >= $period_end ) {
					$period['end'] = $break_start;
					continue;
				}

				if ( $break_start <= $period_start && $break_end >= $period_end ) {
					$period['start'] = $break_end;
				}
			}
		}

		return $periods;
	}

	/**
	 * @param array<int, array{start:\DateTime,end:\DateTime}> $periods
	 * @param array<int, array{start_datetime:string,end_datetime:string}> $events
	 * @return array<int, array{start:\DateTime,end:\DateTime}>
	 */
	public static function remove_blocking_events( array $periods, array $events ): array {
		foreach ( $events as $event ) {
			$block_start = new \DateTime( $event['start_datetime'] );
			$block_end   = new \DateTime( $event['end_datetime'] );

			foreach ( $periods as &$period ) {
				$period_start = $period['start'];
				$period_end   = $period['end'];

				if ( $block_start <= $period_start && $block_end >= $period_start && $block_end <= $period_end ) {
					$period['start'] = $block_end;
					continue;
				}

				if ( $block_start >= $period_start && $block_start <= $period_end && $block_end >= $period_start && $block_end <= $period_end ) {
					$period['end'] = $block_start;
					$periods[]     = array(
						'start' => $block_end,
						'end'   => $period_end,
					);
					continue;
				}

				if ( $block_start >= $period_start && $block_start <= $period_end && $block_end >= $period_end ) {
					$period['end'] = $block_start;
					continue;
				}

				if ( $block_start <= $period_start && $block_end >= $period_end ) {
					$period['start'] = $block_end;
				}
			}
		}

		return $periods;
	}

	/**
	 * @param array<int, array{start:string,end:string}> $empty_periods
	 * @return array<int, string>
	 */
	public static function generate_available_hours( string $date, int $duration, int $slot_interval, array $empty_periods ): array {
		$available_hours = array();

		foreach ( $empty_periods as $period ) {
			$start_hour   = new \DateTime( $date . ' ' . $period['start'] );
			$end_hour     = new \DateTime( $date . ' ' . $period['end'] );
			$current_hour = clone $start_hour;
			$diff         = $current_hour->diff( $end_hour );

			while ( ( $diff->h * 60 + $diff->i ) >= $duration && 0 === $diff->invert ) {
				$available_hours[] = $current_hour->format( 'H:i' );
				$current_hour->add( new \DateInterval( 'PT' . max( 1, $slot_interval ) . 'M' ) );
				$diff = $current_hour->diff( $end_hour );
			}
		}

		return $available_hours;
	}
}
