<?php

/**
 * Apollo Docs — Admin Metabox for `doc` CPT
 *
 * Provides form inputs for ALL 9 meta keys defined in apollo-registry.json:
 *   _doc_file_id, _doc_folder_id, _doc_access, _doc_version,
 *   _doc_downloads, _doc_status, _doc_checksum, _doc_cpf, _doc_signers
 *
 * @package Apollo\Docs\Admin
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Docs\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Metabox {


	/**
	 * Bootstrap — hook into WP.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_doc', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register the metabox on the `doc` CPT edit screen.
	 */
	public function register(): void {
		add_meta_box(
			'apollo_doc_details',
			__( 'Detalhes do Documento', 'apollo-docs' ),
			array( $this, 'render' ),
			'doc',
			'normal',
			'high'
		);
	}

	/**
	 * Render the metabox form.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'apollo_doc_metabox', 'apollo_doc_meta_nonce' );

		$file_id   = get_post_meta( $post->ID, '_doc_file_id', true );
		$folder_id = get_post_meta( $post->ID, '_doc_folder_id', true );
		$access    = get_post_meta( $post->ID, '_doc_access', true ) ?: 'private';
		$version   = get_post_meta( $post->ID, '_doc_version', true ) ?: '1.0';
		$downloads = get_post_meta( $post->ID, '_doc_downloads', true ) ?: 0;
		$status    = get_post_meta( $post->ID, '_doc_status', true ) ?: 'draft';
		$checksum  = get_post_meta( $post->ID, '_doc_checksum', true );
		$cpf       = get_post_meta( $post->ID, '_doc_cpf', true );
		$signers   = get_post_meta( $post->ID, '_doc_signers', true );
		if ( ! is_array( $signers ) ) {
			$signers = array();
		}
		?>
		<style>
			.apollo-doc-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 16px;
			}

			.apollo-doc-field {
				margin-bottom: 12px;
			}

			.apollo-doc-field label {
				display: block;
				font-weight: 600;
				margin-bottom: 4px;
				font-size: 13px;
			}

			.apollo-doc-field input,
			.apollo-doc-field select,
			.apollo-doc-field textarea {
				width: 100%;
				padding: 6px 8px;
			}

			.apollo-doc-full {
				grid-column: 1 / -1;
			}

			.apollo-doc-readonly {
				background: #f0f0f1;
				cursor: default;
			}
		</style>
		<div class="apollo-doc-grid">

			<!-- File ID (attachment) -->
			<div class="apollo-doc-field">
				<label for="apollo_doc_file_id"><?php esc_html_e( 'Arquivo (attachment ID)', 'apollo-docs' ); ?></label>
				<input type="number" id="apollo_doc_file_id" name="_doc_file_id"
					value="<?php echo esc_attr( (string) $file_id ); ?>" min="0">
				<?php if ( $file_id ) : ?>
					<p class="description">
						<?php
						$url = wp_get_attachment_url( (int) $file_id );
						if ( $url ) {
							printf(
								'<a href="%s" target="_blank">%s</a>',
								esc_url( $url ),
								esc_html( basename( $url ) )
							);
						}
						?>
					</p>
				<?php endif; ?>
			</div>

			<!-- Folder ID -->
			<div class="apollo-doc-field">
				<label for="apollo_doc_folder_id"><?php esc_html_e( 'Pasta (folder term ID)', 'apollo-docs' ); ?></label>
				<input type="number" id="apollo_doc_folder_id" name="_doc_folder_id"
					value="<?php echo esc_attr( (string) $folder_id ); ?>" min="0">
				<?php if ( $folder_id ) : ?>
					<p class="description">
						<?php
						$term = get_term( (int) $folder_id, 'doc_folder' );
						if ( $term && ! is_wp_error( $term ) ) {
							echo esc_html( $term->name );
						}
						?>
					</p>
				<?php endif; ?>
			</div>

			<!-- Access -->
			<div class="apollo-doc-field">
				<label for="apollo_doc_access"><?php esc_html_e( 'Acesso', 'apollo-docs' ); ?></label>
				<select id="apollo_doc_access" name="_doc_access">
					<option value="public" <?php selected( $access, 'public' ); ?>><?php esc_html_e( 'Público', 'apollo-docs' ); ?></option>
					<option value="private" <?php selected( $access, 'private' ); ?>><?php esc_html_e( 'Privado', 'apollo-docs' ); ?></option>
					<option value="restricted" <?php selected( $access, 'restricted' ); ?>><?php esc_html_e( 'Restrito', 'apollo-docs' ); ?></option>
					<option value="group" <?php selected( $access, 'group' ); ?>><?php esc_html_e( 'Grupo', 'apollo-docs' ); ?></option>
					<option value="industry" <?php selected( $access, 'industry' ); ?>><?php esc_html_e( 'Industry', 'apollo-docs' ); ?></option>
				</select>
			</div>

			<!-- Status -->
			<div class="apollo-doc-field">
				<label for="apollo_doc_status"><?php esc_html_e( 'Status', 'apollo-docs' ); ?></label>
				<select id="apollo_doc_status" name="_doc_status">
					<option value="draft" <?php selected( $status, 'draft' ); ?>><?php esc_html_e( 'Rascunho', 'apollo-docs' ); ?></option>
					<option value="review" <?php selected( $status, 'review' ); ?>><?php esc_html_e( 'Em revisão', 'apollo-docs' ); ?></option>
					<option value="approved" <?php selected( $status, 'approved' ); ?>><?php esc_html_e( 'Aprovado', 'apollo-docs' ); ?></option>
					<option value="archived" <?php selected( $status, 'archived' ); ?>><?php esc_html_e( 'Arquivado', 'apollo-docs' ); ?></option>
					<option value="locked" <?php selected( $status, 'locked' ); ?>><?php esc_html_e( 'Trancado', 'apollo-docs' ); ?></option>
					<option value="finalized" <?php selected( $status, 'finalized' ); ?>><?php esc_html_e( 'Finalizado', 'apollo-docs' ); ?></option>
					<option value="signed" <?php selected( $status, 'signed' ); ?>><?php esc_html_e( 'Assinado', 'apollo-docs' ); ?></option>
				</select>
			</div>

			<!-- Version -->
			<div class="apollo-doc-field">
				<label for="apollo_doc_version"><?php esc_html_e( 'Versão', 'apollo-docs' ); ?></label>
				<input type="text" id="apollo_doc_version" name="_doc_version"
					value="<?php echo esc_attr( $version ); ?>" placeholder="1.0">
			</div>

			<!-- Downloads (read-only counter) -->
			<div class="apollo-doc-field">
				<label for="apollo_doc_downloads"><?php esc_html_e( 'Downloads', 'apollo-docs' ); ?></label>
				<input type="number" id="apollo_doc_downloads" name="_doc_downloads"
					value="<?php echo esc_attr( (string) $downloads ); ?>"
					class="apollo-doc-readonly" min="0">
				<p class="description"><?php esc_html_e( 'Contador de downloads. Editável para correções.', 'apollo-docs' ); ?></p>
			</div>

			<!-- CPF -->
			<div class="apollo-doc-field">
				<label for="apollo_doc_cpf"><?php esc_html_e( 'CPF do Titular', 'apollo-docs' ); ?></label>
				<input type="text" id="apollo_doc_cpf" name="_doc_cpf"
					value="<?php echo esc_attr( $cpf ); ?>"
					placeholder="000.000.000-00" maxlength="14">
			</div>

			<!-- Checksum (read-only, auto-computed) -->
			<div class="apollo-doc-field">
				<label for="apollo_doc_checksum"><?php esc_html_e( 'Checksum (SHA-256)', 'apollo-docs' ); ?></label>
				<input type="text" id="apollo_doc_checksum" name="_doc_checksum"
					value="<?php echo esc_attr( $checksum ); ?>"
					class="apollo-doc-readonly" readonly>
				<p class="description"><?php esc_html_e( 'Hash gerado automaticamente. Não editável.', 'apollo-docs' ); ?></p>
			</div>

			<!-- Signers (JSON array) -->
			<div class="apollo-doc-field apollo-doc-full">
				<label for="apollo_doc_signers"><?php esc_html_e( 'Signatários (JSON)', 'apollo-docs' ); ?></label>
				<textarea id="apollo_doc_signers" name="_doc_signers" rows="4"
					style="font-family:monospace;font-size:12px;"
					placeholder='[{"user_id":1,"name":"João","signed_at":"2025-01-01T12:00:00"}]'><?php echo esc_textarea( ! empty( $signers ) ? wp_json_encode( $signers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : '' ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Array JSON de signatários: {user_id, name, signed_at}', 'apollo-docs' ); ?></p>
			</div>

		</div>
		<?php
	}

	/**
	 * Save metabox data on post save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		// Security checks
		if ( ! isset( $_POST['apollo_doc_meta_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_doc_meta_nonce'] ) ), 'apollo_doc_metabox' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// File ID
		if ( isset( $_POST['_doc_file_id'] ) ) {
			update_post_meta( $post_id, '_doc_file_id', absint( $_POST['_doc_file_id'] ) );
		}

		// Folder ID + taxonomy sync
		if ( isset( $_POST['_doc_folder_id'] ) ) {
			$folder_id = absint( $_POST['_doc_folder_id'] );
			update_post_meta( $post_id, '_doc_folder_id', $folder_id );
			if ( $folder_id > 0 ) {
				wp_set_object_terms( $post_id, array( $folder_id ), 'doc_folder' );
			}
		}

		// Access
		if ( isset( $_POST['_doc_access'] ) ) {
			$access = sanitize_text_field( wp_unslash( $_POST['_doc_access'] ) );
			$valid  = array( 'public', 'private', 'restricted', 'group', 'industry' );
			if ( in_array( $access, $valid, true ) ) {
				update_post_meta( $post_id, '_doc_access', $access );
			}
		}

		// Status
		if ( isset( $_POST['_doc_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_POST['_doc_status'] ) );
			$valid  = array( 'draft', 'review', 'approved', 'archived', 'locked', 'finalized', 'signed' );
			if ( in_array( $status, $valid, true ) ) {
				update_post_meta( $post_id, '_doc_status', $status );
			}
		}

		// Version
		if ( isset( $_POST['_doc_version'] ) ) {
			update_post_meta( $post_id, '_doc_version', sanitize_text_field( wp_unslash( $_POST['_doc_version'] ) ) );
		}

		// Downloads
		if ( isset( $_POST['_doc_downloads'] ) ) {
			update_post_meta( $post_id, '_doc_downloads', absint( $_POST['_doc_downloads'] ) );
		}

		// CPF
		if ( isset( $_POST['_doc_cpf'] ) ) {
			$cpf = sanitize_text_field( wp_unslash( $_POST['_doc_cpf'] ) );
			if ( '' !== $cpf ) {
				update_post_meta( $post_id, '_doc_cpf', $cpf );
			} else {
				delete_post_meta( $post_id, '_doc_cpf' );
			}
		}

		// Signers (JSON → array)
		if ( isset( $_POST['_doc_signers'] ) ) {
			$raw     = wp_unslash( $_POST['_doc_signers'] );
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$signers = array();
				foreach ( $decoded as $signer ) {
					if ( ! is_array( $signer ) ) {
						continue;
					}
					$signers[] = array(
						'user_id'   => absint( $signer['user_id'] ?? 0 ),
						'name'      => sanitize_text_field( $signer['name'] ?? '' ),
						'signed_at' => sanitize_text_field( $signer['signed_at'] ?? '' ),
					);
				}
				update_post_meta( $post_id, '_doc_signers', $signers );
			} elseif ( '' === trim( $raw ) ) {
				delete_post_meta( $post_id, '_doc_signers' );
			}
		}

		// Recompute checksum from post content
		$checksum = hash( 'sha256', $post->post_content );
		update_post_meta( $post_id, '_doc_checksum', $checksum );
	}
}