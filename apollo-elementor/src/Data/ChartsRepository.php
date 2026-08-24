<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Data;

use Apollo\ElementorAE\Cache\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChartsRepository {

	public static function event_stats( int $event_id ): array {
		$key = 'chart_event_' . $event_id;
		$cached = CacheManager::get( $key );
		if ( false !== $cached ) {
			$decoded = json_decode( $cached, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		$data = [
			'attendance'  => (int) get_post_meta( $event_id, '_apollo_rsvp_count', true ),
			'capacity'    => (int) get_post_meta( $event_id, '_apollo_capacity', true ),
			'revenue'     => (float) get_post_meta( $event_id, '_apollo_revenue', true ),
			'sell_through' => 0,
		];

		if ( $data['capacity'] > 0 ) {
			$data['sell_through'] = round( ( $data['attendance'] / $data['capacity'] ) * 100, 1 );
		}

		CacheManager::set( $key, (string) wp_json_encode( $data ), 600 );
		return $data;
	}

	public static function global_kpis(): array {
		$key = 'chart_global_kpis';
		$cached = CacheManager::get( $key );
		if ( false !== $cached ) {
			$decoded = json_decode( $cached, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		$live_count = (int) wp_count_posts( 'apollo_event' )->publish;

		$total_rsvp = 0;
		$total_revenue = 0.0;
		$events = get_posts( [
			'post_type'      => 'apollo_event',
			'posts_per_page' => 200,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		] );

		foreach ( $events as $eid ) {
			$total_rsvp += (int) get_post_meta( $eid, '_apollo_rsvp_count', true );
			$total_revenue += (float) get_post_meta( $eid, '_apollo_revenue', true );
		}

		$data = [
			'live_events'   => $live_count,
			'tickets_sold'  => $total_rsvp,
			'revenue'       => $total_revenue,
			'no_show_rate'  => 0,
		];

		CacheManager::set( $key, (string) wp_json_encode( $data ), 600 );
		return $data;
	}
}
