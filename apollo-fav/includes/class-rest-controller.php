<?php
/**
 * REST Controller — Endpoints REST API para apollo-fav
 *
 * Conforme apollo-registry.json:
 * Namespace: apollo/v1
 * Endpoints:
 *   GET    /favs              — Lista favoritos do usuário (auth)
 *   POST   /favs              — Adiciona favorito (auth)
 *   DELETE /favs/{post_id}    — Remove favorito (auth)
 *   POST   /favs/toggle/{post_id} — Toggle favorito (auth)
 *   GET    /favs/count/{post_id}  — Contagem de favs (público)
 *   GET    /favs/check/{post_id}  — Verifica se favoritou (auth)
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

namespace Apollo\Fav;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class REST_Controller {

	/**
	 * Namespace da REST API conforme constants do registry.
	 */
	private const NAMESPACE = 'apollo/v1';

	/**
	 * Base do endpoint.
	 */
	private const BASE = 'favs';

	/**
	 * Registra todas as rotas REST.
	 */
	public function register_routes(): void {
		// GET /favs — Lista favoritos do usuário logado
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_favs' ),
					'permission_callback' => array( $this, 'check_auth' ),
					'args'                => array(
						'post_type' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'limit'     => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'default'           => 20,
						),
						'offset'    => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'default'           => 0,
						),
					),
				),
				// POST /favs — Adiciona favorito
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_fav' ),
					'permission_callback' => array( $this, 'check_auth' ),
					'args'                => array(
						'post_id'  => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => array( $this, 'validate_post_id' ),
						),
						'fav_type' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
							'enum'              => array( '', 'user' ),
						),
					),
				),
			)
		);

		// DELETE /favs/{post_id} — Remove favorito
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/(?P<post_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_fav' ),
				'permission_callback' => array( $this, 'check_auth' ),
				'args'                => array(
					'post_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
				),
			)
		);

		// POST /favs/toggle/{post_id} — Toggle favorito
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/toggle/(?P<post_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_fav' ),
				'permission_callback' => array( $this, 'check_auth' ),
				'args'                => array(
					'post_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
				),
			)
		);

		// GET /favs/count/{post_id} — Contagem de favs (público)
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/count/(?P<post_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_count' ),
				'permission_callback' => '__return_true', // Público
				'args'                => array(
					'post_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
				),
			)
		);

		// GET /favs/check/{post_id} — Verifica se o usuário favoritou
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/check/(?P<post_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'check_fav' ),
				'permission_callback' => array( $this, 'check_auth' ),
				'args'                => array(
					'post_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
				),
			)
		);
	}

	// ─────────────────────────────────────────────────────────────
	// CALLBACKS DOS ENDPOINTS
	// ─────────────────────────────────────────────────────────────

	/**
	 * GET /favs — Lista os favoritos do usuário logado.
	 *
	 * @param \WP_REST_Request $request  Objeto da request.
	 * @return \WP_REST_Response
	 */
	public function get_user_favs( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id   = get_current_user_id();
		$post_type = $request->get_param( 'post_type' ) ?: null;
		$limit     = $request->get_param( 'limit' );
		$offset    = $request->get_param( 'offset' );

		$favs  = apollo_get_user_favs( $user_id, $post_type, $limit, $offset );
		$total = apollo_get_user_fav_total( $user_id );

		// Enriquece com dados do post
		$items = array_map(
			function ( $fav ) {
				// User fav — enriquece com dados do usuário
				if ( $fav->post_type === 'user' ) {
					$user = get_userdata( (int) $fav->post_id );
					return array(
						'id'           => (int) $fav->id,
						'post_id'      => (int) $fav->post_id,
						'post_type'    => 'user',
						'post_title'   => $user ? $user->display_name : '',
						'permalink'    => $user ? home_url( '/id/' . $user->user_login ) : '',
						'thumbnail'    => get_avatar_url( (int) $fav->post_id, array( 'size' => 150 ) ),
						'fav_count'    => 0,
						'created_at'   => $fav->created_at,
					);
				}

				$post = get_post( (int) $fav->post_id );

				return array(
					'id'         => (int) $fav->id,
					'post_id'    => (int) $fav->post_id,
					'post_type'  => $fav->post_type,
					'post_title' => $fav->post_title ?? '',
					'permalink'  => get_permalink( (int) $fav->post_id ),
					'thumbnail'  => get_the_post_thumbnail_url( (int) $fav->post_id, 'medium' ) ?: null,
					'fav_count'  => apollo_get_fav_count( (int) $fav->post_id ),
					'created_at' => $fav->created_at,
				);
			},
			$favs
		);

		return new \WP_REST_Response(
			array(
				'items' => $items,
				'total' => $total,
				'meta'  => array(
					'limit'  => $limit,
					'offset' => $offset,
				),
			),
			200
		);
	}

	/**
	 * POST /favs — Adiciona um post aos favoritos.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function add_fav( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id  = get_current_user_id();
		$post_id  = (int) $request->get_param( 'post_id' );
		$fav_type = sanitize_text_field( $request->get_param( 'fav_type' ) ?: '' );

		$fav_id = apollo_add_fav( $user_id, $post_id, $fav_type );

		if ( $fav_id === false ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'Já está nos favoritos ou erro ao adicionar.', 'apollo-fav' ),
				),
				409
			);
		}

		return new \WP_REST_Response(
			array(
				'fav_id' => $fav_id,
				'count'  => apollo_get_fav_count( $post_id ),
			),
			201
		);
	}

	/**
	 * DELETE /favs/{post_id} — Remove favorito.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function remove_fav( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$post_id = (int) $request->get_param( 'post_id' );

		$removed = apollo_remove_fav( $user_id, $post_id );

		if ( ! $removed ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'Favorito não encontrado.', 'apollo-fav' ),
				),
				404
			);
		}

		return new \WP_REST_Response(
			array(
				'removed' => true,
				'count'   => apollo_get_fav_count( $post_id ),
			),
			200
		);
	}

	/**
	 * POST /favs/toggle/{post_id} — Toggle (add/remove).
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function toggle_fav( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$post_id = (int) $request->get_param( 'post_id' );

		$result = apollo_toggle_fav( $user_id, $post_id );

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /favs/count/{post_id} — Contagem de favoritos (público).
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_count( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );

		return new \WP_REST_Response(
			array(
				'post_id' => $post_id,
				'count'   => apollo_get_fav_count( $post_id ),
			),
			200
		);
	}

	/**
	 * GET /favs/check/{post_id} — Verifica se está favoritado.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function check_fav( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$post_id = (int) $request->get_param( 'post_id' );

		return new \WP_REST_Response(
			array(
				'post_id' => $post_id,
				'is_fav'  => apollo_is_fav( $user_id, $post_id ),
				'count'   => apollo_get_fav_count( $post_id ),
			),
			200
		);
	}

	// ─────────────────────────────────────────────────────────────
	// PERMISSION & VALIDATION CALLBACKS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Verifica se o usuário está autenticado.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_auth(): bool|\WP_Error {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_not_logged_in',
				__( 'Você precisa estar logado.', 'apollo-fav' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Valida que o post_id existe.
	 *
	 * @param mixed $value  Valor do parâmetro.
	 * @return bool|\WP_Error
	 */
	public function validate_post_id( mixed $value ): bool|\WP_Error {
		$post_id = absint( $value );

		if ( $post_id <= 0 ) {
			return new \WP_Error(
				'invalid_post_id',
				__( 'ID inválido.', 'apollo-fav' ),
				array( 'status' => 404 )
			);
		}

		// User fav — post_id is a user ID
		if ( get_userdata( $post_id ) ) {
			return true;
		}

		// Normal post fav
		if ( get_post( $post_id ) ) {
			return true;
		}

		return new \WP_Error(
			'invalid_post_id',
			__( 'Item não encontrado.', 'apollo-fav' ),
			array( 'status' => 404 )
		);
	}
}
