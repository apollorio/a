<?php
/**
 * Working plan resolution for agents.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Engine;

use function Apollo\Scheduler\apollo_scheduler_get_working_plan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkingPlan {

	/**
	 * @param array<string, mixed>|null $exceptions Date-keyed overrides.
	 * @return array{start:string,end:string,breaks:array<int,array{start:string,end:string}>}|null
	 */
	public static function for_date( int $agent_id, string $date, ?array $exceptions = null ): ?array {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}

		$exceptions = $exceptions ?? self::get_exceptions( $agent_id );

		if ( isset( $exceptions[ $date ] ) && is_array( $exceptions[ $date ] ) ) {
			return self::normalize_day_plan( $exceptions[ $date ] );
		}

		$plan = apollo_scheduler_get_working_plan( $agent_id );
		$day  = strtolower( gmdate( 'l', strtotime( $date . ' UTC' ) ) );

		if ( ! isset( $plan[ $day ] ) || ! is_array( $plan[ $day ] ) ) {
			return null;
		}

		return self::normalize_day_plan( $plan[ $day ] );
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array{start:string,end:string,breaks:array<int,array{start:string,end:string}>}
	 */
	private static function normalize_day_plan( array $raw ): array {
		return array(
			'start'  => (string) ( $raw['start'] ?? '09:00' ),
			'end'    => (string) ( $raw['end'] ?? '18:00' ),
			'breaks' => is_array( $raw['breaks'] ?? null ) ? $raw['breaks'] : array(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_exceptions( int $agent_id ): array {
		$raw = get_user_meta( $agent_id, '_apollo_working_plan_exceptions', true );
		return is_array( $raw ) ? $raw : array();
	}
}
