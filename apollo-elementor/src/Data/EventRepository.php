<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Data;

use Apollo\ElementorAE\Cache\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EventRepository {

	public static function get( int $post_id ): array {
		$key = 'event_' . $post_id;
		$cached = CacheManager::get( $key );
		if ( false !== $cached ) {
			$decoded = json_decode( $cached, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		$post = get_post( $post_id );
		if ( ! $post || 'apollo_event' !== $post->post_type ) {
			return self::defaults( $post_id );
		}

		$data = [
			'id'          => $post_id,
			'title'       => $post->post_title,
			'excerpt'     => $post->post_excerpt,
			'date'        => (string) get_post_meta( $post_id, '_apollo_event_date', true ),
			'time_start'  => (string) get_post_meta( $post_id, '_apollo_event_time_start', true ),
			'time_end'    => (string) get_post_meta( $post_id, '_apollo_event_time_end', true ),
			'venue'       => (string) get_post_meta( $post_id, '_apollo_venue', true ),
			'venue_addr'  => (string) get_post_meta( $post_id, '_apollo_venue_address', true ),
			'city'        => (string) get_post_meta( $post_id, '_apollo_city', true ),
			'capacity'    => (int) get_post_meta( $post_id, '_apollo_capacity', true ),
			'rsvp_count'  => (int) get_post_meta( $post_id, '_apollo_rsvp_count', true ),
			'lineup'      => self::resolve_lineup( $post_id ),
			'thumbnail'   => (string) get_the_post_thumbnail_url( $post_id, 'large' ),
			'status'      => $post->post_status,
		];

		CacheManager::set( $key, (string) wp_json_encode( $data ), 900 );
		return $data;
	}

	/**
	 * @param array{
	 *   per_page?: int,
	 *   upcoming_only?: bool,
	 *   category?: string,
	 * } $args
	 * @return array<array>
	 */
	public static function grid( array $args = [] ): array {
		$per_page = absint( $args['per_page'] ?? 9 );
		$upcoming = (bool) ( $args['upcoming_only'] ?? true );
		$category = sanitize_text_field( $args['category'] ?? '' );

		$key = 'events_grid_' . md5( (string) wp_json_encode( $args ) );
		$cached = CacheManager::get( $key );
		if ( false !== $cached ) {
			$decoded = json_decode( $cached, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		$query_args = [
			'post_type'      => 'apollo_event',
			'posts_per_page' => $per_page,
			'post_status'    => 'publish',
			'orderby'        => 'meta_value',
			'meta_key'       => '_apollo_event_date',
			'order'          => $upcoming ? 'ASC' : 'DESC',
		];

		if ( $upcoming ) {
			$query_args['meta_query'] = [ [
				'key'     => '_apollo_event_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			] ];
		}

		if ( '' !== $category ) {
			$query_args['tax_query'] = [ [
				'taxonomy' => 'event_category',
				'field'    => 'slug',
				'terms'    => $category,
			] ];
		}

		$posts = get_posts( $query_args );
		$result = array_map( static fn( \WP_Post $p ) => self::get( $p->ID ), $posts );

		CacheManager::set( $key, (string) wp_json_encode( $result ), 600 );
		return $result;
	}

	private static function resolve_lineup( int $post_id ): array {
		$raw = get_post_meta( $post_id, '_apollo_lineup', true );
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$lineup = [];
		foreach ( $raw as $entry ) {
			if ( is_array( $entry ) ) {
				$lineup[] = [
					'user_id'    => (int) ( $entry['user_id'] ?? 0 ),
					'name'       => (string) ( $entry['name'] ?? '' ),
					'role'       => (string) ( $entry['role'] ?? '' ),
					'time_start' => (string) ( $entry['time_start'] ?? '' ),
					'time_end'   => (string) ( $entry['time_end'] ?? '' ),
					'avatar_url' => (string) ( $entry['avatar_url'] ?? '' ),
				];
			} elseif ( is_numeric( $entry ) ) {
				$u = get_userdata( (int) $entry );
				$lineup[] = [
					'user_id'    => (int) $entry,
					'name'       => $u ? $u->display_name : 'DJ',
					'role'       => '',
					'time_start' => '',
					'time_end'   => '',
					'avatar_url' => $u ? (string) get_user_meta( (int) $entry, '_apollo_avatar_url', true ) : '',
				];
			}
		}
		return $lineup;
	}

	private static function defaults( int $post_id ): array {
		return [
			'id'         => $post_id,
			'title'      => '',
			'excerpt'    => '',
			'date'       => '',
			'time_start' => '',
			'time_end'   => '',
			'venue'      => '',
			'venue_addr' => '',
			'city'       => '',
			'capacity'   => 0,
			'rsvp_count' => 0,
			'lineup'     => [],
			'thumbnail'  => '',
			'status'     => 'draft',
		];
	}
}
