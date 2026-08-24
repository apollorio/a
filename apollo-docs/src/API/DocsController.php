<?php

namespace Apollo\Docs\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Apollo\Docs\Model\Document;
use Apollo\Docs\Storage;

/**
 * REST Controller for documents â€” apollo/v1/docs
 */
final class DocsController {

	private string $namespace = 'apollo/v1';
	private string $base      = 'docs';

	public function register_routes(): void {
		/* GET /docs â€” List documents */
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_documents' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => $this->list_args(),
			)
		);

		/* POST /docs â€” Create document */
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_document' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* GET /docs/{id} â€” Get single document */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_document' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* PUT /docs/{id} â€” Update document */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_document' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* DELETE /docs/{id} â€” Delete document */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_document' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* GET /docs/{id}/download â€” Download document file */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)/download',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'download_document' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* GET /docs/{id}/versions â€” Get version history */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)/versions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_versions' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* POST /docs/{id}/lock â€” Lock document */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)/lock',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'lock_document' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* POST /docs/{id}/finalize â€” Finalize document */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)/finalize',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'finalize_document' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* POST /docs/{id}/upload â€” Upload file attachment */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)/upload',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'upload_file' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		/* Legacy alias: POST /docs/upload */
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/upload',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'upload_file' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);
	}

	/* â”€â”€ Callbacks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */

	public function list_documents( \WP_REST_Request $request ): \WP_REST_Response {
		$result = Document::list(
			array(
				'folder_id' => $request->get_param( 'folder_id' ),
				'type'      => $request->get_param( 'type' ),
				'access'    => $request->get_param( 'access' ),
				'status'    => $request->get_param( 'status' ),
				'search'    => $request->get_param( 'search' ),
				'per_page'  => $request->get_param( 'per_page' ) ?: 20,
				'page'      => $request->get_param( 'page' ) ?: 1,
				'author_id' => $request->get_param( 'mine' ) ? get_current_user_id() : null,
			)
		);

		return new \WP_REST_Response( $result, 200 );
	}

	public function create_document( \WP_REST_Request $request ): \WP_REST_Response {
		$data = array(
			'title'     => $request->get_param( 'title' ),
			'content'   => $request->get_param( 'content' ) ?? '',
			'author_id' => get_current_user_id(),
			'access'    => $request->get_param( 'access' ) ?? 'private',
			'folder_id' => $request->get_param( 'folder_id' ),
			'type'      => $request->get_param( 'type' ),
			'file_id'   => $request->get_param( 'file_id' ),
			'cpf'       => $request->get_param( 'cpf' ),
		);

		if ( empty( $data['title'] ) ) {
			return new \WP_REST_Response( array( 'error' => 'TÃ­tulo Ã© obrigatÃ³rio.' ), 400 );
		}

		$doc_id = Document::create( $data );

		if ( ! $doc_id ) {
			return new \WP_REST_Response( array( 'error' => 'Erro ao criar documento.' ), 500 );
		}

		$doc = Document::get( $doc_id );

		return new \WP_REST_Response( $doc, 201 );
	}

	public function get_document( \WP_REST_Request $request ): \WP_REST_Response {
		$doc_id = (int) $request->get_param( 'id' );
		$doc    = Document::get( $doc_id );

		if ( ! $doc ) {
			return new \WP_REST_Response( array( 'error' => 'Documento nÃ£o encontrado.' ), 404 );
		}

		if ( ! Document::can_access( $doc_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado.' ), 403 );
		}

		return new \WP_REST_Response( $doc, 200 );
	}

	public function update_document( \WP_REST_Request $request ): \WP_REST_Response {
		$doc_id = (int) $request->get_param( 'id' );
		$doc    = Document::get( $doc_id );

		if ( ! $doc ) {
			return new \WP_REST_Response( array( 'error' => 'Documento nÃ£o encontrado.' ), 404 );
		}

		if ( ! Document::can_access( $doc_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado.' ), 403 );
		}

		$data = array_filter(
			array(
				'title'     => $request->get_param( 'title' ),
				'content'   => $request->get_param( 'content' ),
				'access'    => $request->get_param( 'access' ),
				'folder_id' => $request->get_param( 'folder_id' ),
				'file_id'   => $request->get_param( 'file_id' ),
				'changelog' => $request->get_param( 'changelog' ),
			),
			fn( $v ) => $v !== null
		);

		$success = Document::update( $doc_id, $data );

		if ( ! $success ) {
			return new \WP_REST_Response( array( 'error' => 'Documento estÃ¡ bloqueado ou erro na atualizaÃ§Ã£o.' ), 422 );
		}

		return new \WP_REST_Response( Document::get( $doc_id ), 200 );
	}

	public function delete_document( \WP_REST_Request $request ): \WP_REST_Response {
		$doc_id = (int) $request->get_param( 'id' );

		if ( ! Document::can_access( $doc_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado.' ), 403 );
		}

		$success = Document::delete( $doc_id );

		if ( ! $success ) {
			return new \WP_REST_Response( array( 'error' => 'Documentos assinados nÃ£o podem ser excluÃ­dos.' ), 422 );
		}

		return new \WP_REST_Response( array( 'deleted' => true ), 200 );
	}

	public function download_document( \WP_REST_Request $request ): \WP_REST_Response {
		$doc_id = (int) $request->get_param( 'id' );

		if ( ! Document::can_access( $doc_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado.' ), 403 );
		}

		$doc = Document::get( $doc_id );
		if ( ! $doc ) {
			return new \WP_REST_Response( array( 'error' => 'Documento nÃ£o encontrado.' ), 404 );
		}

		/* Log download */
		Document::log_download( $doc_id, get_current_user_id() );

		/* Check for PDF first */
		$pdf_path = Storage::pdf_path( $doc_id, $doc['version'] );
		if ( file_exists( $pdf_path ) ) {
			return new \WP_REST_Response(
				array(
					'url'      => Storage::base_url() . '/pdfs/' . $doc_id . '/v' . $doc['version'] . '.pdf',
					'filename' => sanitize_file_name( $doc['title'] ) . '.pdf',
					'type'     => 'application/pdf',
				),
				200
			);
		}

		/* Fallback: return attachment URL */
		if ( $doc['file_id'] ) {
			$url = wp_get_attachment_url( $doc['file_id'] );
			if ( $url ) {
				return new \WP_REST_Response(
					array(
						'url'      => $url,
						'filename' => basename( get_attached_file( $doc['file_id'] ) ),
						'type'     => get_post_mime_type( $doc['file_id'] ),
					),
					200
				);
			}
		}

		/* Fallback: return content as HTML */
		return new \WP_REST_Response(
			array(
				'content'  => $doc['content'],
				'filename' => sanitize_file_name( $doc['title'] ) . '.html',
				'type'     => 'text/html',
			),
			200
		);
	}

	public function get_versions( \WP_REST_Request $request ): \WP_REST_Response {
		$doc_id = (int) $request->get_param( 'id' );

		if ( ! Document::can_access( $doc_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado.' ), 403 );
		}

		$versions = Document::get_versions( $doc_id );

		return new \WP_REST_Response( $versions, 200 );
	}

	public function lock_document( \WP_REST_Request $request ): \WP_REST_Response {
		$doc_id = (int) $request->get_param( 'id' );

		if ( ! Document::can_access( $doc_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado.' ), 403 );
		}

		$success = Document::lock( $doc_id );

		if ( ! $success ) {
			return new \WP_REST_Response( array( 'error' => 'Documento nÃ£o pode ser bloqueado (status invÃ¡lido).' ), 422 );
		}

		return new \WP_REST_Response( array( 'locked' => true ), 200 );
	}

	public function finalize_document( \WP_REST_Request $request ): \WP_REST_Response {
		$doc_id = (int) $request->get_param( 'id' );

		if ( ! Document::can_access( $doc_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Acesso negado.' ), 403 );
		}

		$success = Document::finalize( $doc_id );

		if ( ! $success ) {
			return new \WP_REST_Response( array( 'error' => 'Documento nÃ£o pode ser finalizado (status invÃ¡lido).' ), 422 );
		}

		return new \WP_REST_Response( array( 'finalized' => true ), 200 );
	}

	public function upload_file( \WP_REST_Request $request ): \WP_REST_Response {
		$doc_id = absint( $request->get_param( 'id' ) );
		if ( $doc_id > 0 ) {
			$doc = Document::get( $doc_id );
			if ( ! $doc ) {
				return new \WP_REST_Response( array( 'error' => 'Documento nÃ£o encontrado.' ), 404 );
			}

			if ( ! Document::can_access( $doc_id ) ) {
				return new \WP_REST_Response( array( 'error' => 'Acesso negado.' ), 403 );
			}
		}

		$files = $request->get_file_params();

		if ( empty( $files['file'] ) ) {
			return new \WP_REST_Response( array( 'error' => 'Nenhum arquivo enviado.' ), 400 );
		}

		/* Validate MIME type â€” only allow documents, images, audio, PDF */
		$allowed_mimes = array(
			'application/pdf',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'text/plain',
			'text/csv',
			'text/html',
			'application/json',
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
			'image/svg+xml',
			'audio/mpeg',
			'audio/mp3',
			'audio/wav',
			'audio/ogg',
			'audio/flac',
			'audio/aac',
			'video/mp4',
			'video/webm',
		);

		$file_type = $files['file']['type'] ?? '';
		if ( ! in_array( $file_type, $allowed_mimes, true ) ) {
			return new \WP_REST_Response(
				array(
					'error' => 'Tipo de arquivo nÃ£o permitido: ' . sanitize_text_field( $file_type ),
				),
				415
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_upload( 'file', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return new \WP_REST_Response( array( 'error' => $attachment_id->get_error_message() ), 500 );
		}

		if ( $doc_id > 0 ) {
			update_post_meta( $doc_id, '_doc_file_id', $attachment_id );
		}

		return new \WP_REST_Response(
			array(
				'file_id'  => $attachment_id,
				'url'      => wp_get_attachment_url( $attachment_id ),
				'filename' => basename( get_attached_file( $attachment_id ) ),
				'type'     => get_post_mime_type( $attachment_id ),
				'doc_id'   => $doc_id,
			),
			201
		);
	}

	/* â”€â”€ Permission Callbacks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */

	public function check_logged_in( \WP_REST_Request $request ): bool {
		return is_user_logged_in();
	}

	/* â”€â”€ Argument Schemas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */

	private function list_args(): array {
		return array(
			'folder_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'type'      => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'access'    => array(
				'type'              => 'string',
				'enum'              => array( 'public', 'private', 'group', 'industry' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'    => array(
				'type'              => 'string',
				'enum'              => array( 'draft', 'locked', 'finalized', 'signed' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search'    => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'mine'      => array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'per_page'  => array(
				'type'              => 'integer',
				'default'           => 20,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'page'      => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
		);
	}
}
