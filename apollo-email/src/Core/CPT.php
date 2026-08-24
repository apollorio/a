<?php

/**
 * Register the email_aprio CPT.
 *
 * @package Apollo\Email\Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\Core;

use Apollo\Email\Template\MergeTagRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CPT {

	/**
	 * Register the email_aprio custom post type.
	 */
	public function register(): void {
		// If already registered by apollo-core fallback, skip
		if ( post_type_exists( 'email_aprio' ) ) {
			$this->addMetaBoxes();
			return;
		}

		register_post_type(
			'email_aprio',
			array(
				'labels'          => array(
					'name'               => __( 'Email Templates', 'apollo-email' ),
					'singular_name'      => __( 'Email Template', 'apollo-email' ),
					'add_new'            => __( 'Novo Template', 'apollo-email' ),
					'add_new_item'       => __( 'Adicionar Template', 'apollo-email' ),
					'edit_item'          => __( 'Editar Template', 'apollo-email' ),
					'new_item'           => __( 'Novo Template', 'apollo-email' ),
					'view_item'          => __( 'Ver Template', 'apollo-email' ),
					'search_items'       => __( 'Buscar Templates', 'apollo-email' ),
					'not_found'          => __( 'Nenhum template encontrado', 'apollo-email' ),
					'not_found_in_trash' => __( 'Nenhum template na lixeira', 'apollo-email' ),
					'menu_name'          => __( 'Email Templates', 'apollo-email' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false, // Managed via AdminPage
				'show_in_rest'    => true,
				'rest_base'       => 'email-templates',
				'rest_namespace'  => 'apollo/v1',
				'supports'        => array( 'title', 'editor' ),
				'has_archive'     => false,
				'rewrite'         => false,
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);

		$this->addMetaBoxes();
	}

	/**
	 * Register meta boxes for email template editing.
	 */
	private function addMetaBoxes(): void {
		add_action(
			'add_meta_boxes',
			function () {
				add_meta_box(
					'apollo_email_template_meta',
					__( 'Configurações do Template', 'apollo-email' ),
					array( $this, 'renderMetaBox' ),
					'email_aprio',
					'side',
					'high'
				);

				add_meta_box(
					'apollo_email_template_preview',
					__( 'Preview', 'apollo-email' ),
					array( $this, 'renderPreviewBox' ),
					'email_aprio',
					'normal',
					'low'
				);

				add_meta_box(
					'apollo_email_placeholders',
					__( 'Placeholders disponíveis', 'apollo-email' ),
					array( $this, 'renderPlaceholdersBox' ),
					'email_aprio',
					'normal',
					'default'
				);
			}
		);

		add_action( 'save_post_email_aprio', array( $this, 'saveMetaBox' ), 10, 2 );
	}

	/**
	 * Render the template settings meta box.
	 */
	public function renderMetaBox( \WP_Post $post ): void {
		$subject   = get_post_meta( $post->ID, '_email_subject', true );
		$type      = get_post_meta( $post->ID, '_email_type', true ) ?: 'transactional';
		$variables = get_post_meta( $post->ID, '_email_variables', true );
		if ( ! is_array( $variables ) ) {
			$variables = array();
		}

		wp_nonce_field( 'apollo_email_meta', '_apollo_email_meta_nonce' );
		?>
		<p>
			<label for="email_subject"><strong><?php esc_html_e( 'Assunto:', 'apollo-email' ); ?></strong></label>
			<input type="text" id="email_subject" name="_email_subject" value="<?php echo esc_attr( $subject ); ?>" class="widefat" placeholder="Ex: Bem-vindo(a) ao {{site_name}}">
			<small><?php esc_html_e( 'Use {{variavel}} para merge tags', 'apollo-email' ); ?></small>
		</p>
		<p>
			<label for="email_type"><strong><?php esc_html_e( 'Tipo:', 'apollo-email' ); ?></strong></label>
			<select id="email_type" name="_email_type" class="widefat">
				<option value="transactional" <?php selected( $type, 'transactional' ); ?>><?php esc_html_e( 'Transacional', 'apollo-email' ); ?></option>
				<option value="marketing" <?php selected( $type, 'marketing' ); ?>><?php esc_html_e( 'Marketing', 'apollo-email' ); ?></option>
				<option value="digest" <?php selected( $type, 'digest' ); ?>><?php esc_html_e( 'Resumo', 'apollo-email' ); ?></option>
			</select>
		</p>
		<p>
			<label for="email_variables"><strong><?php esc_html_e( 'Variáveis disponíveis:', 'apollo-email' ); ?></strong></label>
			<textarea id="email_variables" name="_email_variables" class="widefat" rows="3" placeholder="user_name, site_name, action_url"><?php echo esc_textarea( implode( ', ', $variables ) ); ?></textarea>
			<small><?php esc_html_e( 'Separadas por vírgula', 'apollo-email' ); ?></small>
		</p>
		<?php
	}

	/**
	 * Render the placeholder explorer meta box.
	 *
	 * Shows all known {{tags}}, which are already used in this template,
	 * and allows click-to-insert into the WP classic editor textarea.
	 */
	public function renderPlaceholdersBox( \WP_Post $post ): void {
		$content      = $post->post_content;
		$used_tags    = MergeTagRegistry::extractFromContent( $content );
		$all_tags     = MergeTagRegistry::getAllTags();
		$grouped_tags = MergeTagRegistry::getTagsByContext();

		$context_labels = array(
			'global'       => __( 'Site / Global', 'apollo-email' ),
			'user'         => __( 'Usuário', 'apollo-email' ),
			'auth'         => __( 'Auth / Segurança', 'apollo-email' ),
			'notification' => __( 'Notificação', 'apollo-email' ),
			'event'        => __( 'Evento', 'apollo-email' ),
			'digest'       => __( 'Digest', 'apollo-email' ),
		);

		$context_colors = array(
			'global'       => '#6c757d',
			'user'         => '#0073aa',
			'auth'         => '#d63638',
			'notification' => '#0085ba',
			'event'        => '#46b450',
			'digest'       => '#826eb4',
		);
		?>
		<style>
			.apollo-ph-search { width:100%; max-width:320px; padding:6px 10px; margin-bottom:12px; border:1px solid #ccc; border-radius:3px; }
			.apollo-ph-tag { display:inline-block; margin:2px; padding:4px 8px; background:#f0f6fc; border:1px solid #cce5ff; border-radius:3px;
				font-family:monospace; font-size:12px; cursor:pointer; color:#0073aa; transition:background 0.15s; }
			.apollo-ph-tag:hover { background:#cce5ff; }
			.apollo-ph-tag.is-used { background:#edfaef; border-color:#68de7c; color:#1a7a2c; }
			.apollo-ph-tag.is-used::after { content:' ✓'; }
			.apollo-ph-group-title { font-size:12px; font-weight:600; color:#555; margin:12px 0 4px;
				padding:3px 8px; border-left:3px solid #ccc; background:#f6f7f7; }
			.apollo-ph-used-section { background:#edfaef; border:1px solid #68de7c; border-radius:4px; padding:10px 12px; margin-bottom:14px; }
			.apollo-ph-used-section h4 { margin:0 0 8px; font-size:12px; color:#1a7a2c; }
			.apollo-ph-insert-hint { color:#888; font-size:11px; margin-bottom:10px; }
			#apollo-ph-inserted-notice { display:none; color:#46b450; font-size:12px; margin-left:8px; }
		</style>

		<?php if ( ! empty( $used_tags ) ) : ?>
		<div class="apollo-ph-used-section">
			<h4><?php esc_html_e( 'Placeholders detectados neste template:', 'apollo-email' ); ?></h4>
			<?php foreach ( $used_tags as $ut ) : ?>
				<span class="apollo-ph-tag is-used">{{<?php echo esc_html( $ut ); ?>}}</span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<p class="apollo-ph-insert-hint">
			<?php esc_html_e( 'Clique em um placeholder para inserir no editor. Tags com ✓ já estão no template.', 'apollo-email' ); ?>
			<span id="apollo-ph-inserted-notice"><?php esc_html_e( 'Inserido!', 'apollo-email' ); ?></span>
		</p>

		<input type="search" id="apollo-ph-search" class="apollo-ph-search"
			placeholder="<?php esc_attr_e( 'Filtrar placeholders...', 'apollo-email' ); ?>"
			autocomplete="off" />

		<div id="apollo-ph-list">
			<?php foreach ( $grouped_tags as $ctx_key => $ctx_tags ) : ?>
				<?php
				$ctx_label = $context_labels[ $ctx_key ] ?? ucfirst( $ctx_key );
				$ctx_color = $context_colors[ $ctx_key ] ?? '#aaa';
				?>
				<div class="apollo-ph-group" data-context="<?php echo esc_attr( $ctx_key ); ?>">
					<div class="apollo-ph-group-title" style="border-left-color:<?php echo esc_attr( $ctx_color ); ?>">
						<?php echo esc_html( $ctx_label ); ?>
					</div>
					<div class="apollo-ph-tags-row">
						<?php foreach ( $ctx_tags as $tag ) : ?>
							<?php
							$tag_str  = '{{' . $tag['tag'] . '}}';
							$is_used  = in_array( $tag['tag'], $used_tags, true );
							$cls      = 'apollo-ph-tag' . ( $is_used ? ' is-used' : '' );
							?>
							<span
								class="<?php echo esc_attr( $cls ); ?>"
								data-tag="<?php echo esc_attr( $tag['tag'] ); ?>"
								data-desc="<?php echo esc_attr( $tag['description'] ); ?>"
								title="<?php echo esc_attr( $tag['description'] . ' — ' . $tag['source'] ); ?>"
							>
								<?php echo esc_html( $tag_str ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<p id="apollo-ph-no-results" style="display:none;color:#888;padding:8px 0;">
				<?php esc_html_e( 'Nenhum placeholder encontrado.', 'apollo-email' ); ?>
			</p>
		</div><!-- #apollo-ph-list -->

		<script>
		(function () {
			'use strict';

			// ── Search / filter ──────────────────────────────────────
			const searchInput = document.getElementById('apollo-ph-search');
			const noResults   = document.getElementById('apollo-ph-no-results');
			const groups      = document.querySelectorAll('.apollo-ph-group');

			if (searchInput) {
				searchInput.addEventListener('input', function () {
					const q = this.value.toLowerCase().trim();
					let anyVisible = false;

					groups.forEach(function (group) {
						const tags = group.querySelectorAll('.apollo-ph-tag');
						let groupVisible = false;

						tags.forEach(function (tag) {
							const match = q === '' ||
								(tag.dataset.tag || '').includes(q) ||
								(tag.dataset.desc || '').toLowerCase().includes(q);

							tag.style.display = match ? 'inline-block' : 'none';
							if (match) { groupVisible = true; anyVisible = true; }
						});

						group.style.display = groupVisible ? '' : 'none';
					});

					noResults.style.display = (!anyVisible && q !== '') ? 'block' : 'none';
				});
			}

			// ── Click to insert ──────────────────────────────────────
			const notice = document.getElementById('apollo-ph-inserted-notice');
			let noticeTimer;

			function showNotice() {
				if (!notice) return;
				clearTimeout(noticeTimer);
				notice.style.display = 'inline';
				noticeTimer = setTimeout(function () { notice.style.display = 'none'; }, 1500);
			}

			function insertAtCursor(field, value) {
				if (field.setRangeText) {
					field.setRangeText(value, field.selectionStart, field.selectionEnd, 'end');
				} else {
					field.value += value;
				}
				field.dispatchEvent(new Event('input', { bubbles: true }));
				field.focus();
			}

			document.querySelectorAll('.apollo-ph-tag').forEach(function (el) {
				el.addEventListener('click', function () {
					const tagStr = '{{' + this.dataset.tag + '}}';

					// Gutenberg block editor
					if (window.wp && wp.data && wp.data.select('core/block-editor')) {
						if (wp.richText && wp.data.dispatch) {
							// Fallback: copy to clipboard and notify
							if (navigator.clipboard) {
								navigator.clipboard.writeText(tagStr);
							}
							showNotice();
							return;
						}
					}

					// Classic editor — target #content textarea
					const textarea = document.getElementById('content');
					if (textarea) {
						textarea.focus();
						insertAtCursor(textarea, tagStr);
						showNotice();
						return;
					}

					// Subject field fallback
					const subject = document.getElementById('email_subject');
					if (subject && document.activeElement === subject) {
						insertAtCursor(subject, tagStr);
						showNotice();
						return;
					}

					// Ultimate fallback — clipboard
					if (navigator.clipboard) {
						navigator.clipboard.writeText(tagStr);
					}
					showNotice();
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Render the preview meta box.
	 */
	public function renderPreviewBox( \WP_Post $post ): void {
		?>
		<div id="apollo-email-preview-container">
			<button type="button" class="button" id="apollo-email-preview-btn">
				<span class="dashicons dashicons-visibility"></span>
				<?php esc_html_e( 'Gerar Preview', 'apollo-email' ); ?>
			</button>
			<div id="apollo-email-preview-frame" style="margin-top: 10px; border: 1px solid #ddd; border-radius: 4px; display: none;">
				<iframe id="apollo-email-preview-iframe" style="width: 100%; height: 500px; border: 0;"></iframe>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 */
	public function saveMetaBox( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['_apollo_email_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_apollo_email_meta_nonce'] ) ), 'apollo_email_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Subject
		if ( isset( $_POST['_email_subject'] ) ) {
			update_post_meta( $post_id, '_email_subject', sanitize_text_field( wp_unslash( $_POST['_email_subject'] ) ) );
		}

		// Type
		if ( isset( $_POST['_email_type'] ) ) {
			$type = sanitize_text_field( wp_unslash( $_POST['_email_type'] ) );
			if ( in_array( $type, array( 'transactional', 'marketing', 'digest' ), true ) ) {
				update_post_meta( $post_id, '_email_type', $type );
			}
		}

		// Variables
		if ( isset( $_POST['_email_variables'] ) ) {
			$raw  = sanitize_text_field( wp_unslash( $_POST['_email_variables'] ) );
			$vars = array_map( 'trim', explode( ',', $raw ) );
			$vars = array_filter( $vars );
			update_post_meta( $post_id, '_email_variables', array_values( $vars ) );
		}
	}
}