<?php
/**
 * REST API: Points Controller
 *
 * Endpoints:
 *   GET  /points             — user's points summary
 *   GET  /points/history     — point transaction history
 *   POST /points/award       — award points (admin)
 *   POST /points/deduct      — deduct points (admin)
 *   POST /points/reset       — reset user points (admin)
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PointsController {

	private string $namespace;

	public function __construct() {
		$this->namespace = APOLLO_MEMBERSHIP_REST_NAMESPACE;
	}

	public function register_routes(): void {

		register_rest_route(
			$this->namespace,
			'/points',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_points' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/points/history',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_history' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'type'    => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit'   => array(
						'type'              => 'integer',
						'default'           => 20,
						'sanitize_callback' => 'absint',
					),
					'page'    => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/points/award',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'award_points' ),
				'permission_callback' => array( $this, 'check_admin' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'points'  => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'reason'  => array(
						'type'              => 'string',
						'default'           => 'admin_award',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/points/deduct',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'deduct_points' ),
				'permission_callback' => array( $this, 'check_admin' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'points'  => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'reason'  => array(
						'type'              => 'string',
						'default'           => 'admin_deduct',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/points/reset',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'reset_points' ),
				'permission_callback' => array( $this, 'check_admin' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public function get_points( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' ) ?: get_current_user_id();

		return new \WP_REST_Response(
			array(
				'user_id'  => $user_id,
				'total'    => apollo_get_users_points( $user_id ),
				'awarded'  => apollo_get_users_points_by_type( $user_id, 'Award' ),
				'deducted' => apollo_get_users_points_by_type( $user_id, 'Deduct' ),
				'position' => apollo_get_user_leaderboard_position( $user_id ),
			)
		);
	}

	public function get_history( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' ) ?: get_current_user_id();

		$history = apollo_get_user_point_history(
			$user_id,
			array(
				'type'  => $request->get_param( 'type' ),
				'limit' => min( 100, $request->get_param( 'limit' ) ),
				'page'  => $request->get_param( 'page' ),
			)
		);

		$triggers = apollo_get_activity_triggers();
		$items    = array();

		foreach ( $history as $entry ) {
			$items[] = array(
				'id'      => (int) $entry->id,
				'type'    => $entry->type,
				'credit'  => (int) $entry->credit,
				'trigger' => $entry->this_trigger,
				'label'   => $triggers[ $entry->this_trigger ] ?? $entry->this_trigger,
				'date'    => $entry->dateadded,
			);
		}

		return new \WP_REST_Response( array( 'items' => $items ) );
	}

	public function award_points( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' );
		$points  = $request->get_param( 'points' );
		$reason  = $request->get_param( 'reason' );

		if ( $points <= 0 ) {
			return new \WP_REST_Response( array( 'error' => 'Points must be positive' ), 400 );
		}

		$result = apollo_award_user_points( $user_id, $points, $reason, get_current_user_id() );

		return new \WP_REST_Response(
			array(
				'success'   => $result,
				'new_total' => apollo_get_users_points( $user_id ),
			)
		);
	}

	public function deduct_points( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' );
		$points  = $request->get_param( 'points' );
		$reason  = $request->get_param( 'reason' );

		if ( $points <= 0 ) {
			return new \WP_REST_Response( array( 'error' => 'Points must be positive' ), 400 );
		}

		$result = apollo_deduct_user_points( $user_id, $points, $reason, get_current_user_id() );

		return new \WP_REST_Response(
			array(
				'success'   => $result,
				'new_total' => apollo_get_users_points( $user_id ),
			)
		);
	}

	public function reset_points( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' );

		$result = apollo_reset_user_points( $user_id, get_current_user_id() );

		return new \WP_REST_Response( array( 'success' => $result ) );
	}

	public function check_logged_in(): bool {
		return is_user_logged_in();
	}

	public function check_admin(): bool {
		return current_user_can( apollo_membership_get_manager_capability() );
	}
}
