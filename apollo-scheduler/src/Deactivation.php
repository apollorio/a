<?php
/**
 * Plugin deactivation.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler;

use Apollo\Scheduler\Cache\AvailabilityCache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {

	public static function deactivate(): void {
		AvailabilityCache::flush_all();

		if ( class_exists( '\Apollo\Core\Registry' ) ) {
			\Apollo\Core\Registry::clear_cache();
		}

		do_action( 'apollo/scheduler/deactivated' );
	}
}
