<?php
/**
 * REST API: Achievements Controller
 *
 * Endpoints:
 *   GET  /achievements         — list achievements
 *   GET  /achievements/{id}    — single achievement
 *   GET  /user-achievements    — user's earned achievements
 *   POST /membership/achievements/award    — award to user (admin)
 *   POST /membership/achievements/revoke   — revoke from user (admin)
 *   GET  /membership/evidence/{achievement_id} — Open Badge evidence page
 *   GET  /membership/verify/{hash}         — Verify Open Badge assertion
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AchievementsController {

	private string $namespace;

	public function __construct() {
		$this->namespace = APOLLO_MEMBERSHIP_REST_NAMESPACE;
	}

	public function register_routes(): void {

		register_rest_route(
			$this->namespace,
			'/achievements',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_achievements' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'type'  => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit' => array(
						'type'              => 'integer',
						'default'           => 20,
						'sanitize_callback' => 'absint',
					),
					'page'  => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/achievements/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_achievement' ),
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
			'/user-achievements',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_achievements' ),
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
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/membership/achievements/award',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'award_achievement' ),
				'permission_callback' => array( $this, 'check_admin' ),
				'args'                => array(
					'user_id'        => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'achievement_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/membership/achievements/revoke',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'revoke_achievement' ),
				'permission_callback' => array( $this, 'check_admin' ),
				'args'                => array(
					'user_id'        => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'achievement_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'entry_id'       => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/membership/evidence/(?P<achievement_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_evidence' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'achievement_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'user_id'        => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/membership/verify/(?P<hash>[a-zA-Z0-9]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'verify_badge' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'hash' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function get_achievements( \WP_REST_Request $request ): \WP_REST_Response {
		$query_args = array(
			'post_type'      => 'apollo_achievement',
			'post_status'    => 'publish',
			'posts_per_page' => min( 100, $request->get_param( 'limit' ) ),
			'paged'          => $request->get_param( 'page' ),
		);

		$type = $request->get_param( 'type' );
		if ( $type ) {
			$query_args['meta_query'] = array(
				array(
					'key'   => '_achievement_type',
					'value' => $type,
				),
			);
		}

		$query = new \WP_Query( $query_args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = $this->format_achievement( $post );
		}

		return new \WP_REST_Response(
			array(
				'items' => $items,
				'total' => $query->found_posts,
				'pages' => $query->max_num_pages,
			)
		);
	}

	public function get_achievement( \WP_REST_Request $request ): \WP_REST_Response {
		$post = get_post( $request->get_param( 'id' ) );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return new \WP_REST_Response( array( 'error' => 'Achievement not found' ), 404 );
		}

		$data                  = $this->format_achievement( $post );
		$data['earners_count'] = count( apollo_get_achievement_earners( $post->ID ) );
		$data['steps']         = array();

		$steps = apollo_get_required_steps_for_achievement( $post->ID );
		foreach ( $steps as $step ) {
			$triggers        = apollo_get_activity_triggers();
			$data['steps'][] = array(
				'trigger'        => $step->trigger_type,
				'trigger_label'  => $triggers[ $step->trigger_type ] ?? $step->trigger_type,
				'required_count' => (int) $step->required_count,
				'point_value'    => (int) $step->point_value,
			);
		}

		return new \WP_REST_Response( $data );
	}

	public function get_user_achievements( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = $request->get_param( 'user_id' ) ?: get_current_user_id();

		$args = array(
			'user_id'    => $user_id,
			'pagination' => true,
			'limit'      => min( 100, $request->get_param( 'limit' ) ),
			'order'      => 'DESC',
		);

		$type = $request->get_param( 'type' );
		if ( $type ) {
			$args['achievement_type'] = $type;
		}

		$achievements = apollo_get_user_achievements( $args );
		$total        = apollo_get_user_achievements( array_merge( $args, array( 'total_only' => true ) ) );

		return new \WP_REST_Response(
			array(
				'items' => $achievements,
				'total' => $total,
			)
		);
	}

	public function award_achievement( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id        = $request->get_param( 'user_id' );
		$achievement_id = $request->get_param( 'achievement_id' );

		$entry_id = apollo_award_achievement_to_user( $achievement_id, $user_id, 'admin_award' );

		if ( ! $entry_id ) {
			return new \WP_REST_Response( array( 'error' => 'Failed to award achievement' ), 400 );
		}

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'entry_id' => $entry_id,
			)
		);
	}

	public function revoke_achievement( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id        = $request->get_param( 'user_id' );
		$achievement_id = $request->get_param( 'achievement_id' );
		$entry_id       = $request->get_param( 'entry_id' );

		$result = apollo_revoke_achievement_from_user( $user_id, $achievement_id, $entry_id );

		return new \WP_REST_Response( array( 'success' => $result ) );
	}

	public function get_evidence( \WP_REST_Request $request ): \WP_REST_Response {
		$achievement_id = $request->get_param( 'achievement_id' );
		$user_id        = $request->get_param( 'user_id' );

		$achievement = get_post( $achievement_id );
		if ( ! $achievement || 'apollo_achievement' !== $achievement->post_type ) {
			return new \WP_REST_Response( array( 'error' => 'Achievement not found' ), 404 );
		}

		// Obter dados da conquista
		$evidence = array(
			'achievement'   => array(
				'id'          => $achievement->ID,
				'title'       => $achievement->post_title,
				'description' => $achievement->post_excerpt,
				'image'       => get_the_post_thumbnail_url( $achievement->ID, 'full' ) ?: APOLLO_MEMBERSHIP_DEFAULT_BADGE_IMAGE,
				'criteria'    => get_post_meta( $achievement->ID, '_open_badge_criteria', true ) ?: home_url( '/conquista/' . $achievement->post_name ),
			),
			'issuer'        => array(
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url(),
				'email' => get_bloginfo( 'admin_email' ),
			),
			'badge_enabled' => (bool) get_post_meta( $achievement->ID, '_open_badge_enable_baking', true ),
		);

		// Se user_id fornecido, pegar dados do earning
		if ( $user_id ) {
			$user_data = get_userdata( $user_id );
			if ( $user_data ) {
				global $wpdb;
				$table = $wpdb->prefix . APOLLO_TABLE_ACHIEVEMENTS;
				$entry = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$table} WHERE user_id = %d AND achievement_id = %d ORDER BY date_earned DESC LIMIT 1",
						$user_id,
						$achievement_id
					)
				);

				if ( $entry ) {
					$evidence['recipient'] = array(
						'name'   => $user_data->display_name,
						'issued' => $entry->date_earned,
						'hash'   => md5( $achievement_id . '_' . $user_id . '_' . $entry->date_earned ),
					);
				}
			}
		}

		return new \WP_REST_Response( $evidence );
	}

	public function verify_badge( \WP_REST_Request $request ): \WP_REST_Response {
		$hash = $request->get_param( 'hash' );

		// Buscar no banco por hash (simplified - you may need to adjust based on your DB structure)
		global $wpdb;
		$table = $wpdb->prefix . APOLLO_TABLE_ACHIEVEMENTS;

		// Como o hash é md5(achievement_id . '_' . user_id . '_' . date_earned)
		// Precisamos buscar todos os registros e verificar o hash
		$entries = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY date_earned DESC" );

		foreach ( $entries as $entry ) {
			$computed_hash = md5( $entry->achievement_id . '_' . $entry->user_id . '_' . $entry->date_earned );
			if ( $computed_hash === $hash ) {
				$achievement = get_post( $entry->achievement_id );
				$user        = get_userdata( $entry->user_id );

				if ( ! $achievement || ! $user ) {
					continue;
				}

				return new \WP_REST_Response(
					array(
						'valid'       => true,
						'achievement' => array(
							'id'    => $achievement->ID,
							'title' => $achievement->post_title,
							'image' => get_the_post_thumbnail_url( $achievement->ID, 'full' ),
						),
						'recipient'   => array(
							'name' => $user->display_name,
						),
						'issued'      => $entry->date_earned,
						'issuer'      => array(
							'name' => get_bloginfo( 'name' ),
							'url'  => home_url(),
						),
					)
				);
			}
		}

		return new \WP_REST_Response(
			array(
				'valid' => false,
				'error' => 'Badge not found or invalid hash',
			),
			404
		);
	}

	public function check_logged_in(): bool {
		return is_user_logged_in();
	}

	public function check_admin(): bool {
		return current_user_can( apollo_membership_get_manager_capability() );
	}

	private function format_achievement( \WP_Post $post ): array {
		return array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'description'  => $post->post_excerpt,
			'content'      => $post->post_content,
			'points'       => (int) get_post_meta( $post->ID, '_achievement_points', true ),
			'type'         => get_post_meta( $post->ID, '_achievement_type', true ) ?: 'default',
			'image'        => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: APOLLO_MEMBERSHIP_DEFAULT_BADGE_IMAGE,
			'max_earnings' => (int) get_post_meta( $post->ID, '_achievement_maximum_earnings', true ),
		);
	}
}
