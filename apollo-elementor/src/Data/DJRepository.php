<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Data;

use Apollo\ElementorAE\Cache\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DJRepository {

	public static function get( int $user_id ): array {
		$key = 'dj_' . $user_id;
		$cached = CacheManager::get( $key );
		if ( false !== $cached ) {
			$decoded = json_decode( $cached, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return self::defaults( $user_id );
		}

		$data = [
			'id'          => $user_id,
			'display_name' => $user->display_name,
			'membership'  => (string) get_user_meta( $user_id, '_apollo_membership', true ),
			'bio'         => (string) get_user_meta( $user_id, '_apollo_bio', true ),
			'city'        => (string) get_user_meta( $user_id, '_apollo_city', true ),
			'instagram'   => (string) get_user_meta( $user_id, '_apollo_instagram', true ),
			'soundcloud'  => (string) get_user_meta( $user_id, '_apollo_soundcloud', true ),
			'avatar_url'  => (string) get_user_meta( $user_id, '_apollo_avatar_url', true ),
			'wow_count'   => (int) get_user_meta( $user_id, '_apollo_wow_count', true ),
			'fav_count'   => (int) get_user_meta( $user_id, '_apollo_fav_count', true ),
			'sounds'      => self::get_sounds( $user_id ),
		];

		if ( empty( $data['avatar_url'] ) ) {
			$data['avatar_url'] = get_avatar_url( $user_id, [ 'size' => 200 ] );
		}

		CacheManager::set( $key, (string) wp_json_encode( $data ), 900 );
		return $data;
	}

	public static function played_with( int $user_id, int $limit = 12 ): array {
		$key = 'dj_played_' . $user_id;
		$cached = CacheManager::get( $key );
		if ( false !== $cached ) {
			$decoded = json_decode( $cached, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		$events = get_posts( [
			'post_type'      => 'apollo_event',
			'posts_per_page' => 50,
			'meta_query'     => [ [
				'key'     => '_apollo_lineup',
				'value'   => (string) $user_id,
				'compare' => 'LIKE',
			] ],
			'fields' => 'ids',
		] );

		$partners = [];
		foreach ( $events as $event_id ) {
			$lineup = get_post_meta( $event_id, '_apollo_lineup', true );
			if ( ! is_array( $lineup ) ) {
				continue;
			}
			foreach ( $lineup as $dj_id ) {
				$dj_id = (int) $dj_id;
				if ( $dj_id === $user_id || isset( $partners[ $dj_id ] ) ) {
					continue;
				}
				$u = get_userdata( $dj_id );
				if ( $u ) {
					$partners[ $dj_id ] = [
						'id'   => $dj_id,
						'name' => $u->display_name,
						'url'  => get_author_posts_url( $dj_id ),
					];
				}
			}
		}

		$result = array_slice( array_values( $partners ), 0, $limit );
		CacheManager::set( $key, (string) wp_json_encode( $result ), 900 );
		return $result;
	}

	private static function get_sounds( int $user_id ): array {
		$terms = wp_get_object_terms( $user_id, 'sounds', [ 'fields' => 'names' ] );
		return is_wp_error( $terms ) ? [] : (array) $terms;
	}

	private static function defaults( int $user_id ): array {
		return [
			'id'           => $user_id,
			'display_name' => 'DJ',
			'membership'   => '',
			'bio'          => '',
			'city'         => '',
			'instagram'    => '',
			'soundcloud'   => '',
			'avatar_url'   => '',
			'wow_count'    => 0,
			'fav_count'    => 0,
			'sounds'       => [],
		];
	}
}
