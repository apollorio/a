<?php
/**
 * Agent model — maps Apollo users to scheduler agents.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Models;

use function Apollo\Scheduler\apollo_scheduler_get_agent_context;
use function Apollo\Scheduler\apollo_scheduler_is_agent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AgentModel {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function list_for_nucleo( int $nucleo_id ): array {
		$query = new \WP_User_Query(
			array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => '_apollo_is_agent',
						'value' => '1',
					),
					array(
						'key'   => '_apollo_agent_of_nucleo',
						'value' => $nucleo_id,
					),
				),
				'number'     => 100,
			)
		);

		$agents = array();
		foreach ( $query->get_results() as $user ) {
			if ( $user instanceof \WP_User && apollo_scheduler_is_agent( (int) $user->ID ) ) {
				$ctx = apollo_scheduler_get_agent_context( (int) $user->ID );
				if ( $ctx ) {
					$agents[] = $ctx;
				}
			}
		}

		return apply_filters( 'apollo/scheduler/agents_for_nucleo', $agents, $nucleo_id );
	}

	/**
	 * Assign user as agent for nucleo.
	 *
	 * @param array<int, int> $service_ids
	 * @param array<int, int> $room_ids
	 */
	public function assign( int $user_id, int $nucleo_id, array $service_ids = array(), array $room_ids = array() ): bool {
		update_user_meta( $user_id, '_apollo_is_agent', true );
		update_user_meta( $user_id, '_apollo_agent_of_nucleo', $nucleo_id );
		update_user_meta( $user_id, '_apollo_agent_services', array_map( 'intval', $service_ids ) );
		update_user_meta( $user_id, '_apollo_agent_rooms', array_map( 'intval', $room_ids ) );

		do_action( 'apollo/scheduler/agent_assigned', $user_id, $nucleo_id, $service_ids, $room_ids );

		return true;
	}

	public function update_working_plan( int $user_id, array $plan ): bool {
		update_user_meta( $user_id, '_apollo_working_plan', $plan );

		do_action( 'apollo/scheduler/availability_updated', $user_id, $plan );

		return true;
	}
}
