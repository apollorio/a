<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Data;

use Apollo\ElementorAE\Cache\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlannerRepository {

	public static function week( int $user_id, int $offset = 0 ): array {
		$key = 'planner_week_' . $user_id . '_' . $offset;
		$cached = CacheManager::get( $key );
		if ( false !== $cached ) {
			$decoded = json_decode( $cached, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		$monday = self::get_monday( $offset );
		$sunday = clone $monday;
		$sunday->modify( '+6 days' );

		$appointments = get_posts( [
			'post_type'      => 'appointment',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'author'         => $user_id,
			'meta_query'     => [ [
				'key'     => '_apollo_appointment_date',
				'value'   => [ $monday->format( 'Y-m-d' ), $sunday->format( 'Y-m-d' ) ],
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			] ],
			'orderby'  => 'meta_value',
			'meta_key' => '_apollo_appointment_date',
			'order'    => 'ASC',
		] );

		$days = [];
		for ( $i = 0; $i < 7; $i++ ) {
			$d = clone $monday;
			$d->modify( '+' . $i . ' days' );
			$iso = $d->format( 'Y-m-d' );
			$days[ $iso ] = [
				'date'  => $iso,
				'label' => $d->format( 'D' ),
				'day'   => (int) $d->format( 'j' ),
				'items' => [],
			];
		}

		foreach ( $appointments as $post ) {
			$date = (string) get_post_meta( $post->ID, '_apollo_appointment_date', true );
			if ( ! isset( $days[ $date ] ) ) {
				continue;
			}
			$days[ $date ]['items'][] = [
				'id'         => $post->ID,
				'title'      => $post->post_title,
				'time_start' => (string) get_post_meta( $post->ID, '_apollo_appointment_time_start', true ),
				'time_end'   => (string) get_post_meta( $post->ID, '_apollo_appointment_time_end', true ),
				'type'       => (string) get_post_meta( $post->ID, '_apollo_appointment_type', true ) ?: 'appt',
				'venue'      => (string) get_post_meta( $post->ID, '_apollo_appointment_venue', true ),
				'progress'   => (int) get_post_meta( $post->ID, '_apollo_appointment_progress', true ),
			];
		}

		$result = array_values( $days );
		CacheManager::set( $key, (string) wp_json_encode( $result ), 300 );
		return $result;
	}

	private static function get_monday( int $offset ): \DateTimeImmutable {
		$now = new \DateTimeImmutable( 'now', wp_timezone() );
		$dow = (int) $now->format( 'N' );
		$monday = $now->modify( '-' . ( $dow - 1 ) . ' days' );
		if ( 0 !== $offset ) {
			$monday = $monday->modify( $offset * 7 . ' days' );
		}
		return $monday;
	}
}
