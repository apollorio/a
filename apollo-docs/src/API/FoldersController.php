<?php
namespace Apollo\Docs\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST Controller for document folders — apollo/v1/docs/folders
 */
final class FoldersController {

	private string $namespace = 'apollo/v1';
	private string $base      = 'docs/folders';

	public function register_routes(): void {
		/* GET /docs/folders — List folders */
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_folders' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* POST /docs/folders — Create folder */
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_folder' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* PUT /docs/folders/{id} — Update folder */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_folder' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* DELETE /docs/folders/{id} — Delete folder */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_folder' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		);
	}

	/* ── Callbacks ─────────────────────────────────────────── */

	public function list_folders( \WP_REST_Request $request ): \WP_REST_Response {
		$args = array(
			'taxonomy'   => 'doc_folder',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$parent = $request->get_param( 'parent' );
		if ( $parent !== null ) {
			$args['parent'] = absint( $parent );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return new \WP_REST_Response( array( 'error' => $terms->get_error_message() ), 500 );
		}

		$folders = array_map(
			function ( $term ) {
				return array(
					'id'          => $term->term_id,
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => $term->description,
					'parent'      => $term->parent,
					'count'       => $term->count,
				);
			},
			$terms
		);

		return new \WP_REST_Response( $folders, 200 );
	}

	public function create_folder( \WP_REST_Request $request ): \WP_REST_Response {
		$name   = sanitize_text_field( $request->get_param( 'name' ) );
		$parent = absint( $request->get_param( 'parent' ) ?? 0 );

		if ( empty( $name ) ) {
			return new \WP_REST_Response( array( 'error' => 'Nome da pasta é obrigatório.' ), 400 );
		}

		$result = wp_insert_term(
			$name,
			'doc_folder',
			array(
				'parent'      => $parent,
				'description' => sanitize_text_field( $request->get_param( 'description' ) ?? '' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( array( 'error' => $result->get_error_message() ), 422 );
		}

		$term = get_term( $result['term_id'], 'doc_folder' );

		return new \WP_REST_Response(
			array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent'      => $term->parent,
				'count'       => $term->count,
			),
			201
		);
	}

	public function update_folder( \WP_REST_Request $request ): \WP_REST_Response {
		$term_id = (int) $request->get_param( 'id' );
		$term    = get_term( $term_id, 'doc_folder' );

		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_REST_Response( array( 'error' => 'Pasta não encontrada.' ), 404 );
		}

		$args = array();

		if ( $request->get_param( 'name' ) !== null ) {
			$args['name'] = sanitize_text_field( $request->get_param( 'name' ) );
		}
		if ( $request->get_param( 'description' ) !== null ) {
			$args['description'] = sanitize_text_field( $request->get_param( 'description' ) );
		}
		if ( $request->get_param( 'parent' ) !== null ) {
			$args['parent'] = absint( $request->get_param( 'parent' ) );
		}

		$result = wp_update_term( $term_id, 'doc_folder', $args );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( array( 'error' => $result->get_error_message() ), 422 );
		}

		$updated = get_term( $term_id, 'doc_folder' );

		return new \WP_REST_Response(
			array(
				'id'          => $updated->term_id,
				'name'        => $updated->name,
				'slug'        => $updated->slug,
				'description' => $updated->description,
				'parent'      => $updated->parent,
				'count'       => $updated->count,
			),
			200
		);
	}

	public function delete_folder( \WP_REST_Request $request ): \WP_REST_Response {
		$term_id = (int) $request->get_param( 'id' );
		$term    = get_term( $term_id, 'doc_folder' );

		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_REST_Response( array( 'error' => 'Pasta não encontrada.' ), 404 );
		}

		$result = wp_delete_term( $term_id, 'doc_folder' );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( array( 'error' => $result->get_error_message() ), 500 );
		}

		return new \WP_REST_Response( array( 'deleted' => true ), 200 );
	}

	/* ── Permission Callbacks ──────────────────────────────── */

	public function check_logged_in( \WP_REST_Request $request ): bool {
		return is_user_logged_in();
	}

	public function check_admin( \WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}
}
