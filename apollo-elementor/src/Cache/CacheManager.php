<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CacheManager {

	private const GROUP = 'apollo_elementor_v1';

	public static function get( string $key ): string|false {
		$full_key = self::GROUP . ':' . $key;

		if ( wp_using_ext_object_cache() ) {
			$value = wp_cache_get( $full_key, self::GROUP );
			return ( false === $value ) ? false : (string) $value;
		}

		$value = get_transient( $full_key );
		return ( false === $value ) ? false : (string) $value;
	}

	public static function set( string $key, string $value, int $ttl = 900 ): bool {
		$full_key = self::GROUP . ':' . $key;

		if ( wp_using_ext_object_cache() ) {
			return wp_cache_set( $full_key, $value, self::GROUP, $ttl );
		}

		return set_transient( $full_key, $value, $ttl );
	}

	public static function delete( string $key ): bool {
		$full_key = self::GROUP . ':' . $key;

		if ( wp_using_ext_object_cache() ) {
			return wp_cache_delete( $full_key, self::GROUP );
		}

		return delete_transient( $full_key );
	}

	public static function flush_group(): void {
		if ( wp_using_ext_object_cache() ) {
			wp_cache_flush_group( self::GROUP );
			return;
		}

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::GROUP . ':%',
				'_transient_timeout_' . self::GROUP . ':%'
			)
		);
	}

	public static function on_save_post( int $post_id ): void {
		$type = get_post_type( $post_id );
		if ( in_array( $type, [ 'apollo_event', 'apollo_dj', 'appointment' ], true ) ) {
			self::flush_group();
		}
	}

	/**
	 * @param int    $meta_id  Meta row ID.
	 * @param int    $object_id Post or user ID.
	 * @param string $meta_key Meta key.
	 */
	public static function on_meta_update( int $meta_id, int $object_id, string $meta_key ): void {
		if ( str_starts_with( $meta_key, '_apollo_' ) ) {
			self::flush_group();
		}
	}
}
