<?php
/**
 * Availability query cache.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AvailabilityCache {

	public static function get( string $key ): ?array {
		$value = wp_cache_get( $key, APOLLO_SCHEDULER_CACHE_GROUP );
		return is_array( $value ) ? $value : null;
	}

	/**
	 * @param array<int, string> $hours
	 */
	public static function set( string $key, array $hours ): void {
		wp_cache_set( $key, $hours, APOLLO_SCHEDULER_CACHE_GROUP, APOLLO_SCHEDULER_CACHE_TTL );
	}

	public static function flush_agent( int $agent_id ): void {
		wp_cache_flush_group( APOLLO_SCHEDULER_CACHE_GROUP );
		do_action( 'apollo/scheduler/cache_flushed', $agent_id );
	}

	public static function flush_all(): void {
		wp_cache_flush_group( APOLLO_SCHEDULER_CACHE_GROUP );
	}
}
