<?php declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/src/Cache/CacheManager.php';
Apollo\ElementorAE\Cache\CacheManager::flush_group();
