<?php
/**
 * REST API: Leaderboard Controller
 *
 * Endpoints:
 *   GET /leaderboard             — global leaderboard
 *   GET /leaderboard/user/{id}   — user position
 *   GET /user-summary            — full membership summary for a user
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeaderboardController {

	private string $namespace;

	public function __construct() {
		$this->namespace = APOLLO_MEMBERSHIP_REST_NAMESPACE;
	}

	public function register_routes(): void {

		register_rest_route(
			$this->namespace,
			'/leaderboard',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_leaderboard' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit' => array(
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/leaderboard/user/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_position' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/user-summary',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_summary' ),
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
			'/membership-badge',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_membership_badge' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/membership-badge',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_membership_badge' ),
				'permission_callback' => array( $this, 'check_admin' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'badge'   => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function get_leaderboard( \WP_REST_Request $request ): \WP_REST_Response {
		$limit   = max( 1, min( 100, $request->get_param( 'limit' ) ) );
		$leaders = apollo_get_points_leaderboard( $limit );

		$items = array();
		foreach ( $leaders as $pos => $leader ) {
			$badge      = apollo_membership_get_user_badge( (int) $leader->user_id );
			$badge_info = apollo_membership_get_badge_info( $badge );
			$rank       = apollo_get_user_rank( (int) $leader->user_id );

			$items[] = array(
				'position'     => $pos + 1,
				'user_id'      => (int) $leader->user_id,
				'display_name' => $leader->display_name,
				'total_points' => (int) $leader->total_points,
				'badge'        => array(
					'type'  => $badge,
					'label' => $badge_info['label'],
					'color' => $badge_info['color'],
				),
				'rank'         => $rank ? $rank->title : null,
				'avatar'       => get_avatar_url( (int) $leader->user_id, array( 'size' => 64 ) ),
			);
		}

		return new \WP_REST_Response(
			array(
				'items' => $items,
				'total' => count( $items ),
			)
		);
	}

	public function get_user_position( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id  = $request->get_param( 'id' );
		$position = apollo_get_user_leaderboard_position( $user_id );
		$total    = apollo_get_users_points( $user_id );

		return new \WP_REST_Response(
			array(
				'user_id'      => $user_id,
				'position'     => $position,
				'total_points' => $total,
			)
		);
	}

	public function get_user_summary( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' ) ?: get_current_user_id();

		$summary = apollo_get_user_membership_summary( $user_id );
		if ( empty( $summary ) ) {
			return new \WP_REST_Response( array( 'error' => 'User not found' ), 404 );
		}

		return new \WP_REST_Response( $summary );
	}

	public function get_membership_badge( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' );
		$badge   = apollo_membership_get_user_badge( $user_id );
		$info    = apollo_membership_get_badge_info( $badge );

		return new \WP_REST_Response(
			array(
				'user_id' => $user_id,
				'badge'   => $badge,
				'label'   => $info['label'],
				'icon'    => $info['icon'],
				'color'   => $info['color'],
			)
		);
	}

	public function set_membership_badge( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' );
		$badge   = $request->get_param( 'badge' );

		$result = apollo_membership_set_user_badge( $user_id, $badge );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( array( 'error' => $result->get_error_message() ), 400 );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'badge'   => $badge,
			)
		);
	}

	public function check_logged_in(): bool {
		return is_user_logged_in();
	}

	public function check_admin(): bool {
		return current_user_can( apollo_membership_get_manager_capability() );
	}
}
