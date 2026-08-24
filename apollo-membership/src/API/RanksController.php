<?php
/**
 * REST API: Ranks Controller
 *
 * Endpoints:
 *   GET  /ranks            — list all ranks
 *   GET  /ranks/{id}       — single rank
 *   GET  /user-rank        — user's current rank
 *   POST /membership/ranks/award       — assign rank manually (admin)
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RanksController {

	private string $namespace;

	public function __construct() {
		$this->namespace = APOLLO_MEMBERSHIP_REST_NAMESPACE;
	}

	public function register_routes(): void {

		register_rest_route(
			$this->namespace,
			'/ranks',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_ranks' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'/ranks/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_rank' ),
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
			'/user-rank',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_rank' ),
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
			'/membership/ranks/award',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'award_rank' ),
				'permission_callback' => array( $this, 'check_admin' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'rank_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public function get_ranks(): \WP_REST_Response {
		$ranks = apollo_get_all_ranks();
		$items = array();

		foreach ( $ranks as $rank ) {
			$items[] = array(
				'id'              => $rank->ID,
				'title'           => $rank->title,
				'description'     => $rank->description ?? '',
				'priority'        => $rank->priority,
				'points_required' => $rank->points,
				'image'           => $rank->image,
			);
		}

		return new \WP_REST_Response( array( 'items' => $items ) );
	}

	public function get_rank( \WP_REST_Request $request ): \WP_REST_Response {
		$rank = apollo_build_rank_object( $request->get_param( 'id' ) );

		if ( ! $rank ) {
			return new \WP_REST_Response( array( 'error' => 'Rank not found' ), 404 );
		}

		// Count holders
		global $wpdb;
		$table   = $wpdb->prefix . APOLLO_TABLE_RANKS;
		$holders = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE rank_id = %d",
				$rank->ID
			)
		);

		return new \WP_REST_Response(
			array(
				'id'              => $rank->ID,
				'title'           => $rank->title,
				'description'     => $rank->description ?? '',
				'priority'        => $rank->priority,
				'points_required' => $rank->points,
				'image'           => $rank->image,
				'holders'         => $holders,
			)
		);
	}

	public function get_user_rank( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id   = $request->get_param( 'user_id' ) ?: get_current_user_id();
		$rank      = apollo_get_user_rank( $user_id );
		$next_rank = apollo_get_next_rank( $user_id );
		$total_pts = apollo_get_users_points( $user_id );

		$data = array(
			'user_id'      => $user_id,
			'current_rank' => $rank ? array(
				'id'       => $rank->ID,
				'title'    => $rank->title,
				'priority' => $rank->priority,
				'image'    => $rank->image,
			) : null,
			'next_rank'    => $next_rank ? array(
				'id'              => $next_rank->ID,
				'title'           => $next_rank->title,
				'points_required' => $next_rank->points,
				'points_needed'   => max( 0, $next_rank->points - $total_pts ),
			) : null,
			'total_points' => $total_pts,
		);

		return new \WP_REST_Response( $data );
	}

	public function award_rank( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' );
		$rank_id = $request->get_param( 'rank_id' );

		$result = apollo_award_rank_to_user( $rank_id, $user_id, 'admin_award', get_current_user_id() );

		return new \WP_REST_Response( array( 'success' => $result ) );
	}

	public function check_logged_in(): bool {
		return is_user_logged_in();
	}

	public function check_admin(): bool {
		return current_user_can( apollo_membership_get_manager_capability() );
	}
}
