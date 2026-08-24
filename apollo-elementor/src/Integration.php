<?php declare(strict_types=1);

namespace Apollo\ElementorAE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integration {

	public static function bootstrap(): void {
		add_action( 'save_post', [ Cache\CacheManager::class, 'on_save_post' ], 10, 1 );
		add_action( 'updated_post_meta', [ Cache\CacheManager::class, 'on_meta_update' ], 10, 3 );
		add_action( 'updated_user_meta', [ Cache\CacheManager::class, 'on_meta_update' ], 10, 3 );
	}
}
