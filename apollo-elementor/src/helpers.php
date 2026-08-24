<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function apollo_cache_get( string $key ): string|false {
	return \Apollo\ElementorAE\Cache\CacheManager::get( $key );
}

function apollo_cache_set( string $key, string $value, int $ttl = 900 ): bool {
	return \Apollo\ElementorAE\Cache\CacheManager::set( $key, $value, $ttl );
}

function apollo_cache_bust( string $prefix = '' ): void {
	if ( '' === $prefix ) {
		\Apollo\ElementorAE\Cache\CacheManager::flush_group();
		return;
	}
	\Apollo\ElementorAE\Cache\CacheManager::delete( $prefix );
}
