<?php
/**
 * Manager REST endpoints — nucleo-scoped bulk actions.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\API;

use function Apollo\Scheduler\apollo_scheduler_get_agent_nucleo;
use Apollo\Scheduler\Models\AgentModel;
use Apollo\Scheduler\Models\ResourceModel;
use Apollo\Scheduler\Models\ServiceModel;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ManagerController extends RestController {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/nucleo/(?P<nucleo_id>\d+)/catalog',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_catalog' ),
					'permission_callback' => array( $this, 'catalog_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/agents/assign',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'assign_agent' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => array(
						'user_id'     => array( 'required' => true, 'type' => 'integer' ),
						'nucleo_id'   => array( 'required' => true, 'type' => 'integer' ),
						'service_ids' => array( 'type' => 'array' ),
						'room_ids'    => array( 'type' => 'array' ),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/availability/block',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'block_dates' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => array(
						'agent_id' => array( 'required' => true, 'type' => 'integer' ),
						'dates'    => array( 'required' => true, 'type' => 'array' ),
					),
				),
			)
		);
	}

	public function catalog_permission( WP_REST_Request $request ): bool {
		return $this->manager_permission( (int) $request->get_param( 'nucleo_id' ) );
	}

	public function get_catalog( WP_REST_Request $request ) {
		$nucleo_id = (int) $request->get_param( 'nucleo_id' );

		return rest_ensure_response(
			array(
				'services'  => ( new ServiceModel() )->list_for_nucleo( $nucleo_id ),
				'agents'    => ( new AgentModel() )->list_for_nucleo( $nucleo_id ),
				'resources' => ( new ResourceModel() )->list_for_nucleo( $nucleo_id ),
			)
		);
	}

	public function assign_agent( WP_REST_Request $request ) {
		$nucleo_id = (int) $request->get_param( 'nucleo_id' );

		if ( ! $this->manager_permission( $nucleo_id ) ) {
			return new \WP_Error( 'forbidden', __( 'Not allowed.', 'apollo-scheduler' ), array( 'status' => 403 ) );
		}

		$user_id     = (int) $request->get_param( 'user_id' );
		$service_ids = array_map( 'intval', (array) $request->get_param( 'service_ids' ) );
		$room_ids    = array_map( 'intval', (array) $request->get_param( 'room_ids' ) );

		( new AgentModel() )->assign( $user_id, $nucleo_id, $service_ids, $room_ids );

		return rest_ensure_response( array( 'success' => true, 'user_id' => $user_id ) );
	}

	public function block_dates( WP_REST_Request $request ) {
		$agent_id = (int) $request->get_param( 'agent_id' );
		$nucleo_id = (int) apollo_scheduler_get_agent_nucleo( $agent_id );

		if ( ! $this->manager_permission( $nucleo_id ) && get_current_user_id() !== $agent_id ) {
			return new \WP_Error( 'forbidden', __( 'Not allowed.', 'apollo-scheduler' ), array( 'status' => 403 ) );
		}

		$exceptions = \Apollo\Scheduler\Engine\WorkingPlan::get_exceptions( $agent_id );
		foreach ( (array) $request->get_param( 'dates' ) as $date ) {
			if ( is_string( $date ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				$exceptions[ $date ] = null;
			}
		}

		update_user_meta( $agent_id, '_apollo_working_plan_exceptions', $exceptions );
		do_action( 'apollo/scheduler/availability_updated', $agent_id, $exceptions );

		return rest_ensure_response( array( 'success' => true ) );
	}
}
