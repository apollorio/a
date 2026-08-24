<?php
/**
 * Apollo Core - Sound REST API Controller
 *
 * Provides REST endpoints for sound/music genre taxonomy.
 * CRITICAL for user registration matchmaking.
 *
 * @package Apollo\Core\API
 * @since 6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sound Controller
 */
class SoundController {

	/**
	 * REST namespace
	 */
	const NAMESPACE = 'apollo/v1';

	/**
	 * Constructor - register routes
	 */
	public function __construct() {
		$this->register_routes();
	}

	/**
	 * Register REST routes
	 */
	public function register_routes(): void {
		// GET /apollo/v1/sounds - List all sounds (for registration, filters)
		register_rest_route(
			self::NAMESPACE,
			'/sounds',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sounds' ),
					'permission_callback' => '__return_true', // Public endpoint
				),
			)
		);

		// GET /apollo/v1/sounds/(?P<id>\d+) - Get single sound
		register_rest_route(
			self::NAMESPACE,
			'/sounds/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sound' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'id' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param );
							},
						),
					),
				),
			)
		);

		// GET /apollo/v1/sounds/user - Get current user's sound preferences
		register_rest_route(
			self::NAMESPACE,
			'/sounds/user',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_sounds' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
				),
			)
		);

		// POST /apollo/v1/sounds/user - Set current user's sound preferences
		register_rest_route(
			self::NAMESPACE,
			'/sounds/user',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_user_sounds' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'sounds' => array(
							'required'          => true,
							'type'              => 'array',
							'description'       => 'Array of sound term IDs',
							'validate_callback' => function ( $param ) {
								return is_array( $param );
							},
							'sanitize_callback' => function ( $param ) {
								return array_map( 'absint', $param );
							},
						),
					),
				),
			)
		);

		// GET /apollo/v1/sounds/popular - Get popular sounds (for suggestions)
		register_rest_route(
			self::NAMESPACE,
			'/sounds/popular',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_popular_sounds' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'limit' => array(
							'default'           => 10,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if user is logged in
	 */
	public function check_logged_in(): bool {
		return is_user_logged_in();
	}

	/**
	 * GET /sounds - List all sounds hierarchically
	 */
	public function get_sounds( \WP_REST_Request $request ): \WP_REST_Response {
		$flat = $request->get_param( 'flat' ) === 'true';

		if ( $flat ) {
			// Return flat list
			$terms = get_terms(
				array(
					'taxonomy'   => 'sound',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			if ( is_wp_error( $terms ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'error'   => $terms->get_error_message(),
					),
					500
				);
			}

			$data = array_map( array( $this, 'format_term' ), $terms );
		} else {
			// Return hierarchical
			$data = \Apollo\Core\TaxonomyRegistry::get_sounds_for_matchmaking();
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
				'total'   => count( $data ),
			),
			200
		);
	}

	/**
	 * GET /sounds/{id} - Get single sound
	 */
	public function get_sound( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_param( 'id' );

		$term = get_term( $id, 'sound' );

		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Sound not found',
				),
				404
			);
		}

		// Get children
		$children = get_terms(
			array(
				'taxonomy'   => 'sound',
				'hide_empty' => false,
				'parent'     => $term->term_id,
			)
		);

		$data             = $this->format_term( $term );
		$data['children'] = ! is_wp_error( $children ) ? array_map( array( $this, 'format_term' ), $children ) : array();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * GET /sounds/user - Get current user's preferences
	 */
	public function get_user_sounds( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();

		$sound_ids = apollo_get_user_sound_preferences( $user_id );

		// Get term details
		$sounds = array();
		foreach ( $sound_ids as $id ) {
			$term = get_term( $id, 'sound' );
			if ( $term && ! is_wp_error( $term ) ) {
				$sounds[] = $this->format_term( $term );
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'user_id' => $user_id,
					'sounds'  => $sounds,
					'ids'     => $sound_ids,
				),
			),
			200
		);
	}

	/**
	 * POST /sounds/user - Set user's sound preferences
	 */
	public function set_user_sounds( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id   = get_current_user_id();
		$sound_ids = $request->get_param( 'sounds' );

		// Validate minimum selection (at least 1 for matchmaking)
		if ( empty( $sound_ids ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Selecione pelo menos um gênero musical.',
					'code'    => 'min_selection',
				),
				400
			);
		}

		// Validate maximum selection (prevent spam)
		if ( count( $sound_ids ) > 20 ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Máximo de 20 gêneros permitidos.',
					'code'    => 'max_selection',
				),
				400
			);
		}

		// Set preferences
		$result = apollo_set_user_sound_preferences( $user_id, $sound_ids );

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Erro ao salvar preferências.',
				),
				500
			);
		}

		// Update matchmaking data
		$matchmaking               = get_user_meta( $user_id, '_apollo_matchmaking_data', true ) ?: array();
		$matchmaking['sounds']     = $sound_ids;
		$matchmaking['updated_at'] = current_time( 'mysql' );
		update_user_meta( $user_id, '_apollo_matchmaking_data', $matchmaking );

		// Log
		apollo_log_audit(
			'user:sounds_updated',
			'user',
			$user_id,
			array(
				'sounds_count' => count( $sound_ids ),
			)
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Preferências salvas com sucesso.',
				'data'    => array(
					'sounds_count' => count( $sound_ids ),
				),
			),
			200
		);
	}

	/**
	 * GET /sounds/popular - Get popular sounds
	 */
	public function get_popular_sounds( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = $request->get_param( 'limit' );

		// Get sounds with most posts
		$terms = get_terms(
			array(
				'taxonomy'   => 'sound',
				'hide_empty' => false,
				'number'     => $limit,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'parent'     => 0, // Only parent terms
			)
		);

		if ( is_wp_error( $terms ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => $terms->get_error_message(),
				),
				500
			);
		}

		$data = array_map( array( $this, 'format_term' ), $terms );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Format term for response
	 */
	private function format_term( \WP_Term $term ): array {
		return array(
			'id'          => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent'      => $term->parent,
			'count'       => $term->count,
		);
	}
}
