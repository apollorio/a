<?php
/**
 * Plugin activation.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {

	public static function activate(): void {
		$registry = new Registry();
		$registry->register_cpts();

		add_rewrite_rule( '^book/?$', 'index.php?apollo_scheduler_page=book', 'top' );
		add_rewrite_rule(
			'^nucleo/([^/]+)/schedule/?$',
			'index.php?apollo_scheduler_page=nucleo_schedule&apollo_nucleo_slug=$matches[1]',
			'top'
		);
		add_rewrite_rule( '^agent/availability/?$', 'index.php?apollo_scheduler_page=agent_availability', 'top' );

		if ( class_exists( '\Apollo\Core\Registry' ) ) {
			\Apollo\Core\Registry::clear_cache();
		}

		do_action( 'apollo/scheduler/activated' );
	}
}
