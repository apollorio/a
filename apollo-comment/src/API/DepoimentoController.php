<?php
/**
 * REST Controller — apollo/v1/depoimentos
 *
 * @package Apollo\Comment
 */

namespace Apollo\Comment\API;

use Apollo\Comment\Depoimento;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DepoimentoController extends \WP_REST_Controller {

	protected $namespace = 'apollo/v1';
	protected $rest_base = 'depoimentos';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /depoimentos?post_id=&limit=&offset=
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'post_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'limit'   => array(
							'default'           => 10,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'offset'  => array(
							'default'           => 0,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
				// POST /depoimentos
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'post_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'content' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
						'parent'  => array(
							'default'           => 0,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// GET/PUT/DELETE /depoimentos/{id}
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id'      => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'content' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/* ─── GET Collection ─────────────────────────────────────── */

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ): \WP_REST_Response {
		$post_id = $request->get_param( 'post_id' );
		$limit   = $request->get_param( 'limit' );
		$offset  = $request->get_param( 'offset' );

		$result = Depoimento::query( $post_id, $limit, $offset );

		$items = array_map(
			function ( $c ) {
				return Depoimento::prepare_for_rest( $c );
			},
			$result['depoimentos']
		);

		return new \WP_REST_Response(
			array(
				'depoimentos' => $items,
				'total'       => $result['total'],
				'post_id'     => $post_id,
			),
			200
		);
	}

	/* ─── GET Single ─────────────────────────────────────────── */

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$comment = get_comment( $request->get_param( 'id' ) );
		if ( ! $comment ) {
			return new \WP_Error( 'not_found', 'Depoimento não encontrado.', array( 'status' => 404 ) );
		}

		return new \WP_REST_Response( Depoimento::prepare_for_rest( $comment ), 200 );
	}

	/* ─── POST Create ────────────────────────────────────────── */

	/**
	 * @param \WP_REST_Request $request
	 * @return true|\WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'rest_forbidden', 'Faça login para deixar um depoimento.', array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		$user    = wp_get_current_user();
		$post_id = $request->get_param( 'post_id' );
		$content = $request->get_param( 'content' );
		$parent  = $request->get_param( 'parent' );

		// Validate post exists
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'invalid_post', 'Post não encontrado.', array( 'status' => 404 ) );
		}

		// Allowed post types
		$allowed = apply_filters( 'apollo/depoimento/allowed_post_types', array( 'post', 'page', 'event', 'loc', 'dj', 'classified' ) );
		if ( ! in_array( $post->post_type, $allowed, true ) ) {
			return new \WP_Error( 'not_allowed', 'Depoimentos não são permitidos neste tipo de conteúdo.', array( 'status' => 403 ) );
		}

		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $user->display_name,
				'comment_author_email' => $user->user_email,
				'comment_author_url'   => home_url( '/id/' . $user->user_login ),
				'comment_content'      => $content,
				'comment_parent'       => $parent,
				'user_id'              => $user->ID,
				'comment_approved'     => 1, // Auto-approve logged-in users; filter via WP core if needed
				'comment_type'         => 'comment',
			)
		);

		if ( ! $comment_id ) {
			return new \WP_Error( 'insert_failed', 'Erro ao salvar depoimento.', array( 'status' => 500 ) );
		}

		/**
		 * Action: depoimento created.
		 *
		 * @param int $comment_id
		 * @param int $post_id
		 * @param int $user_id
		 */
		do_action( 'apollo/depoimento/created', $comment_id, $post_id, $user->ID );

		$comment = get_comment( $comment_id );
		return new \WP_REST_Response( Depoimento::prepare_for_rest( $comment ), 201 );
	}

	/* ─── PUT Update ─────────────────────────────────────────── */

	/**
	 * @param \WP_REST_Request $request
	 * @return true|\WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'rest_forbidden', 'Faça login.', array( 'status' => 401 ) );
		}

		$comment = get_comment( $request->get_param( 'id' ) );
		if ( ! $comment ) {
			return new \WP_Error( 'not_found', 'Depoimento não encontrado.', array( 'status' => 404 ) );
		}

		$user = wp_get_current_user();
		if ( (int) $comment->user_id !== $user->ID && ! current_user_can( 'moderate_comments' ) ) {
			return new \WP_Error( 'rest_forbidden', 'Sem permissão.', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$comment_id = $request->get_param( 'id' );
		$content    = $request->get_param( 'content' );

		$result = wp_update_comment(
			array(
				'comment_ID'      => $comment_id,
				'comment_content' => $content,
			)
		);

		if ( ! $result ) {
			return new \WP_Error( 'update_failed', 'Erro ao atualizar depoimento.', array( 'status' => 500 ) );
		}

		do_action( 'apollo/depoimento/updated', $comment_id );

		$comment = get_comment( $comment_id );
		return new \WP_REST_Response( Depoimento::prepare_for_rest( $comment ), 200 );
	}

	/* ─── DELETE ──────────────────────────────────────────────── */

	/**
	 * @param \WP_REST_Request $request
	 * @return true|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'rest_forbidden', 'Faça login.', array( 'status' => 401 ) );
		}

		$comment = get_comment( $request->get_param( 'id' ) );
		if ( ! $comment ) {
			return new \WP_Error( 'not_found', 'Depoimento não encontrado.', array( 'status' => 404 ) );
		}

		$user = wp_get_current_user();
		if ( (int) $comment->user_id !== $user->ID && ! current_user_can( 'moderate_comments' ) ) {
			return new \WP_Error( 'rest_forbidden', 'Sem permissão.', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$comment_id = $request->get_param( 'id' );
		$result     = wp_trash_comment( $comment_id );

		if ( ! $result ) {
			return new \WP_Error( 'delete_failed', 'Erro ao remover depoimento.', array( 'status' => 500 ) );
		}

		do_action( 'apollo/depoimento/deleted', $comment_id );

		return new \WP_REST_Response(
			array(
				'deleted' => true,
				'id'      => $comment_id,
			),
			200
		);
	}
}
