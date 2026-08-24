<?php
/**
 * Availability REST endpoints.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\API;

use Apollo\Scheduler\Engine\AvailabilityEngine;
use Apollo\Scheduler\Security\BookingRateLimit;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AvailabilityController extends RestController {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/availability',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_hours' ),
					'permission_callback' => '__return_true',
					'args'                => $this->common_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/availability/grid',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_grid' ),
					'permission_callback' => '__return_true',
					'args'                => $this->common_args(),
				),
			)
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function common_args(): array {
		return array(
			'date'        => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static fn( $v ) => is_string( $v ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ),
			),
			'service_id'  => array(
				'required' => true,
				'type'     => 'integer',
			),
			'agent_id'    => array(
				'required' => true,
				'type'     => 'integer',
			),
			'resource_id' => array(
				'type' => 'integer',
			),
		);
	}

	public function get_hours( WP_REST_Request $request ) {
		if ( ! BookingRateLimit::allow_read() ) {
			return new \WP_Error( 'rate_limited', __( 'Too many requests.', 'apollo-scheduler' ), array( 'status' => 429 ) );
		}

		$engine = new AvailabilityEngine();
		$hours  = $engine->get_available_hours(
			$request->get_param( 'date' ),
			(int) $request->get_param( 'service_id' ),
			(int) $request->get_param( 'agent_id' ),
			$request->get_param( 'resource_id' ) ? (int) $request->get_param( 'resource_id' ) : null
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'hours'   => $hours,
			)
		);
	}

	public function get_grid( WP_REST_Request $request ) {
		if ( ! BookingRateLimit::allow_read() ) {
			return new \WP_Error( 'rate_limited', __( 'Too many requests.', 'apollo-scheduler' ), array( 'status' => 429 ) );
		}

		$engine = new AvailabilityEngine();
		$grid   = $engine->get_availability_grid(
			$request->get_param( 'date' ),
			(int) $request->get_param( 'service_id' ),
			(int) $request->get_param( 'agent_id' ),
			$request->get_param( 'resource_id' ) ? (int) $request->get_param( 'resource_id' ) : null
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'grid'    => $grid,
			)
		);
	}
}
