<?php
namespace Apollo\Docs\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Apollo\Docs\Model\Document;

/**
 * Admin Controller — menu page, asset enqueue, AJAX handlers.
 */
final class Controller {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		/* AJAX handlers */
		$actions = array(
			'load_documents',
			'create_document',
			'update_document',
			'delete_document',
			'load_folders',
			'create_folder',
			'delete_folder',
			'load_versions',
			'lock_document',
			'finalize_document',
			'update_status',
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_apollo_docs_' . $action, array( $this, $action ) );
		}
	}

	public function register_menu(): void {
		add_menu_page(
			'Documentos',
			'Docs',
			'edit_posts',
			'apollo-docs',
			array( $this, 'render_page' ),
			'dashicons-media-document',
			31
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( $hook !== 'toplevel_page_apollo-docs' ) {
			return;
		}

		wp_enqueue_style(
			'apollo-docs-css',
			APOLLO_DOCS_URL . 'assets/css/docs.css',
			array(),
			APOLLO_DOCS_VERSION
		);

		wp_enqueue_script(
			'apollo-docs-js',
			APOLLO_DOCS_URL . 'assets/js/docs.js',
			array( 'jquery' ),
			APOLLO_DOCS_VERSION,
			true
		);

		wp_localize_script(
			'apollo-docs-js',
			'ApolloDocs',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'apollo_docs_nonce' ),
				'user_id'  => get_current_user_id(),
				'rest_url' => rest_url( 'apollo/v1/docs' ),
				'is_admin' => current_user_can( 'manage_options' ),
			)
		);
	}

	public function render_page(): void {
		include APOLLO_DOCS_DIR . 'templates/documents.php';
	}

	/* ── Helper ────────────────────────────────────────────── */

	private function verify_nonce(): bool {
		return check_ajax_referer( 'apollo_docs_nonce', 'nonce', false );
	}

	private function json_success( array $data ): void {
		wp_send_json_success( $data );
	}

	private function json_error( string $msg, int $code = 400 ): void {
		wp_send_json_error( array( 'message' => $msg ), $code );
	}

	/* ── AJAX Handlers ─────────────────────────────────────── */

	public function load_documents(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$result = Document::list(
			array(
				'folder_id' => absint( $_POST['folder_id'] ?? 0 ) ?: null,
				'status'    => sanitize_text_field( $_POST['status'] ?? '' ),
				'search'    => sanitize_text_field( $_POST['search'] ?? '' ),
				'per_page'  => absint( $_POST['per_page'] ?? 20 ),
				'page'      => absint( $_POST['page'] ?? 1 ),
			)
		);

		$this->json_success( $result );
	}

	public function create_document(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$title = sanitize_text_field( $_POST['title'] ?? '' );
		if ( empty( $title ) ) {
			$this->json_error( 'Título obrigatório.' );
		}

		$doc_id = Document::create(
			array(
				'title'     => $title,
				'content'   => wp_kses_post( $_POST['content'] ?? '' ),
				'author_id' => get_current_user_id(),
				'access'    => sanitize_text_field( $_POST['access'] ?? 'private' ),
				'folder_id' => absint( $_POST['folder_id'] ?? 0 ) ?: null,
				'type'      => sanitize_text_field( $_POST['type'] ?? '' ),
				'cpf'       => sanitize_text_field( $_POST['cpf'] ?? '' ),
			)
		);

		if ( ! $doc_id ) {
			$this->json_error( 'Erro ao criar documento.' );
		}

		$this->json_success( Document::get( $doc_id ) );
	}

	public function update_document(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$doc_id = absint( $_POST['doc_id'] ?? 0 );
		if ( ! $doc_id ) {
			$this->json_error( 'ID inválido.' );
		}

		$data = array();
		if ( isset( $_POST['title'] ) ) {
			$data['title'] = sanitize_text_field( $_POST['title'] );
		}
		if ( isset( $_POST['content'] ) ) {
			$data['content'] = wp_kses_post( $_POST['content'] );
		}
		if ( isset( $_POST['access'] ) ) {
			$data['access'] = sanitize_text_field( $_POST['access'] );
		}
		if ( isset( $_POST['changelog'] ) ) {
			$data['changelog'] = sanitize_text_field( $_POST['changelog'] );
		}

		$success = Document::update( $doc_id, $data );

		if ( ! $success ) {
			$this->json_error( 'Documento bloqueado ou erro na atualização.' );
		}

		$this->json_success( Document::get( $doc_id ) );
	}

	public function delete_document(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$doc_id  = absint( $_POST['doc_id'] ?? 0 );
		$success = Document::delete( $doc_id );

		if ( ! $success ) {
			$this->json_error( 'Documentos assinados não podem ser excluídos.' );
		}

		$this->json_success( array( 'deleted' => true ) );
	}

	public function load_folders(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'doc_folder',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			$this->json_success( array() );
			return;
		}

		$folders = array_map(
			function ( $t ) {
				return array(
					'id'     => $t->term_id,
					'name'   => $t->name,
					'slug'   => $t->slug,
					'parent' => $t->parent,
					'count'  => $t->count,
				);
			},
			$terms
		);

		$this->json_success( $folders );
	}

	public function create_folder(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$name = sanitize_text_field( $_POST['name'] ?? '' );
		if ( empty( $name ) ) {
			$this->json_error( 'Nome obrigatório.' );
		}

		$result = wp_insert_term(
			$name,
			'doc_folder',
			array(
				'parent' => absint( $_POST['parent'] ?? 0 ),
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->json_error( $result->get_error_message() );
		}

		$term = get_term( $result['term_id'], 'doc_folder' );

		$this->json_success(
			array(
				'id'     => $term->term_id,
				'name'   => $term->name,
				'slug'   => $term->slug,
				'parent' => $term->parent,
				'count'  => 0,
			)
		);
	}

	public function delete_folder(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->json_error( 'Permissão negada.', 403 );
		}

		$term_id = absint( $_POST['folder_id'] ?? 0 );
		$result  = wp_delete_term( $term_id, 'doc_folder' );

		if ( is_wp_error( $result ) ) {
			$this->json_error( $result->get_error_message() );
		}

		$this->json_success( array( 'deleted' => true ) );
	}

	public function load_versions(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$doc_id   = absint( $_POST['doc_id'] ?? 0 );
		$versions = Document::get_versions( $doc_id );

		$this->json_success( $versions );
	}

	public function lock_document(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$doc_id  = absint( $_POST['doc_id'] ?? 0 );
		$success = Document::lock( $doc_id );

		if ( ! $success ) {
			$this->json_error( 'Documento não pode ser bloqueado.' );
		}

		$this->json_success( array( 'locked' => true ) );
	}

	public function finalize_document(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$doc_id  = absint( $_POST['doc_id'] ?? 0 );
		$success = Document::finalize( $doc_id );

		if ( ! $success ) {
			$this->json_error( 'Documento não pode ser finalizado.' );
		}

		$this->json_success( array( 'finalized' => true ) );
	}

	public function update_status(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido.', 403 );
		}

		$doc_id = absint( $_POST['doc_id'] ?? 0 );
		$status = sanitize_text_field( $_POST['status'] ?? '' );

		if ( ! in_array( $status, array( 'draft', 'locked', 'finalized' ), true ) ) {
			$this->json_error( 'Status inválido.' );
		}

		update_post_meta( $doc_id, '_doc_status', $status );

		$this->json_success( Document::get( $doc_id ) );
	}
}
