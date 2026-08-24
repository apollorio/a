<?php
/**
 * Apollo CoAuthor — REST API Controller.
 *
 * Endpoints:
 *   GET  /apollo/v1/coauthors/{post_id}      — List co-authors.
 *   PUT  /apollo/v1/coauthors/{post_id}      — Set co-authors.
 *   GET  /apollo/v1/coauthors/search          — Search users (AJAX).
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor\API;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller for co-author management.
 *
 * @since 1.0.0
 */
class CoauthorController extends WP_REST_Controller {

	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected $namespace = APOLLO_COAUTHOR_REST_NAMESPACE;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'coauthors';

	/**
	 * Register REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes(): void {

		// GET/PUT /coauthors/{post_id}
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<post_id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'post_id' => array(
							'description'       => __( 'ID do post.', 'apollo-coauthor' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'post_id'  => array(
							'description'       => __( 'ID do post.', 'apollo-coauthor' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'user_ids' => array(
							'description'       => __( 'Array de IDs de co-autores.', 'apollo-coauthor' ),
							'type'              => 'array',
							'required'          => true,
							'items'             => array( 'type' => 'integer' ),
							'sanitize_callback' => function ( $ids ) {
								return array_map( 'absint', (array) $ids );
							},
							'validate_callback' => function ( $ids ) {
								if ( ! is_array( $ids ) ) {
									return false;
								}
								foreach ( $ids as $id ) {
									if ( ! is_numeric( $id ) || (int) $id < 1 ) {
										return false;
									}
								}
								return true;
							},
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// GET /coauthors/search?q=term
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_users' ),
					'permission_callback' => array( $this, 'search_users_permissions_check' ),
					'args'                => array(
						'q' => array(
							'description'       => __( 'Termo de busca.', 'apollo-coauthor' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════
	 * §1 — GET /coauthors/{post_id}
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * Permission check for reading co-authors.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'apollo_coauthor_not_found',
				__( 'Post não encontrado.', 'apollo-coauthor' ),
				array( 'status' => 404 )
			);
		}

		// Public posts are readable by anyone.
		if ( 'publish' === $post->post_status ) {
			return true;
		}

		// Otherwise, user must be able to read the post.
		if ( ! current_user_can( 'read_post', $post_id ) ) {
			return new WP_Error(
				'apollo_coauthor_forbidden',
				__( 'Sem permissão para ler co-autores deste post.', 'apollo-coauthor' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get co-authors for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$post_id   = (int) $request->get_param( 'post_id' );
		$coauthors = apollo_get_coauthors( $post_id );

		return new WP_REST_Response(
			array(
				'post_id'   => $post_id,
				'coauthors' => $coauthors,
				'count'     => count( $coauthors ),
			),
			200
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════
	 * §2 — PUT /coauthors/{post_id}
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * Permission check for updating co-authors.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$post_id = (int) $request->get_param( 'post_id' );

		if ( ! get_post( $post_id ) ) {
			return new WP_Error(
				'apollo_coauthor_not_found',
				__( 'Post não encontrado.', 'apollo-coauthor' ),
				array( 'status' => 404 )
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'apollo_coauthor_forbidden',
				__( 'Sem permissão para editar co-autores.', 'apollo-coauthor' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Set co-authors for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$post_id  = (int) $request->get_param( 'post_id' );
		$user_ids = $request->get_param( 'user_ids' );
		$post     = get_post( $post_id );

		if ( $post && 'event' === $post->post_type && function_exists( 'apollo_event_save_coauthors' ) ) {
			apollo_event_save_coauthors( $post_id, is_array( $user_ids ) ? $user_ids : array() );
			$success = true;
		} else {
			$success = apollo_set_coauthors( $post_id, $user_ids );
		}

		if ( ! $success ) {
			return new WP_Error(
				'apollo_coauthor_update_failed',
				__( 'Falha ao atualizar co-autores.', 'apollo-coauthor' ),
				array( 'status' => 500 )
			);
		}

		$coauthors = apollo_get_coauthors( $post_id );

		return new WP_REST_Response(
			array(
				'post_id'   => $post_id,
				'coauthors' => $coauthors,
				'count'     => count( $coauthors ),
				'updated'   => true,
			),
			200
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════
	 * §3 — GET /coauthors/search
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * Permission check for user search.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function search_users_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'apollo_coauthor_forbidden',
				__( 'Sem permissão.', 'apollo-coauthor' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Search users for the co-author metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function search_users( WP_REST_Request $request ): WP_REST_Response {
		$search = $request->get_param( 'q' );

		$users = get_users(
			array(
				'search'         => '*' . $search . '*',
				'search_columns' => array( 'user_login', 'user_nicename', 'display_name', 'user_email' ),
				'number'         => 20,
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			)
		);

		$results = array();
		foreach ( $users as $user ) {
			$results[] = array(
				'id'           => $user->ID,
				'display_name' => $user->display_name,
				'user_login'   => $user->user_login,
				'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
			);
		}

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Schema for co-author response.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_public_item_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'apollo-coauthor',
			'type'       => 'object',
			'properties' => array(
				'post_id'   => array(
					'description' => __( 'ID do post.', 'apollo-coauthor' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'coauthors' => array(
					'description' => __( 'Lista de co-autores.', 'apollo-coauthor' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'user_id'      => array( 'type' => 'integer' ),
							'display_name' => array( 'type' => 'string' ),
							'user_login'   => array( 'type' => 'string' ),
							'avatar_url'   => array( 'type' => 'string' ),
							'profile_url'  => array( 'type' => 'string' ),
						),
					),
				),
				'count'     => array(
					'description' => __( 'Total de co-autores.', 'apollo-coauthor' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
			),
		);
	}
}
