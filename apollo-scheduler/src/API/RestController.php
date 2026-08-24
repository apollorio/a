<?php
/**
 * REST base for scheduler controllers.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class RestController {

	protected string $namespace = APOLLO_SCHEDULER_REST_NAMESPACE;
	protected string $rest_base  = APOLLO_SCHEDULER_REST_BASE;

	abstract public function register_routes(): void;

	protected function logged_in_permission(): bool {
		return is_user_logged_in();
	}

	protected function manager_permission( int $nucleo_id ): bool {
		return \Apollo\Scheduler\apollo_scheduler_can_manage_nucleo( $nucleo_id );
	}
}
