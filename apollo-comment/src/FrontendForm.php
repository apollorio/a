<?php

/**
 * FrontendForm — Renders inline Depoimento creation form for frontend
 *
 * Hooks into `apollo/comment/render_depoimento_form`
 *
 * Registry compliance (apollo-comment):
 *   REST: POST /depoimentos (auth: logged_in)
 *   Fields: post_id (int, required), content (string, required), parent (int, optional)
 *   FORBIDDEN terms: "comment", "review" → use "depoimento"
 *
 * @package Apollo\Comment
 */

declare(strict_types=1);

namespace Apollo\Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FrontendForm {


	public function __construct() {
		add_action( 'apollo/comment/render_depoimento_form', array( $this, 'render' ), 10, 1 );
	}

	/**
	 * Render the inline depoimento creation form inside panel-forms.php
	 *
	 * @param int $user_id Current user ID
	 */
	public function render( int $user_id ): void {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Faça login para deixar um depoimento.', 'apollo-comment' ) . '</p>';
			return;
		}

		$rest_url = esc_url_raw( rest_url( 'apollo/v1/depoimentos' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );
		?>
		<div class="apl-form apl-form--depoimento" id="apolloDepoimentoForm">
			<div class="apl-form__header">
				<i class="ri-quill-pen-fill"></i>
				<h3><?php esc_html_e( 'Escrever Depoimento', 'apollo-comment' ); ?></h3>
			</div>

			<form id="apl-depoimento-create" autocomplete="off" novalidate>

				<!-- ═══ DESTINO ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-links-line"></i> <?php esc_html_e( 'Destino', 'apollo-comment' ); ?></legend>

					<div class="apl-form__field">
						<label for="df_post_id"><?php esc_html_e( 'ID do Conteúdo', 'apollo-comment' ); ?> <span class="req">*</span></label>
						<input type="number" id="df_post_id" name="post_id" required min="1" placeholder="<?php esc_attr_e( 'Ex: 1234', 'apollo-comment' ); ?>" class="apollo-input">
						<span class="apl-form__hint"><?php esc_html_e( 'ID do evento, anúncio, loc ou publicação que receberá o depoimento.', 'apollo-comment' ); ?></span>
					</div>

					<div class="apl-form__field" id="df_post_preview" style="display:none;">
						<div class="apl-form__preview">
							<i class="ri-article-line"></i>
							<span id="df_post_title"></span>
						</div>
					</div>
				</fieldset>

				<!-- ═══ DEPOIMENTO ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-chat-quote-line"></i> <?php esc_html_e( 'Seu Depoimento', 'apollo-comment' ); ?></legend>

					<div class="apl-form__field">
						<label for="df_content"><?php esc_html_e( 'Conteúdo', 'apollo-comment' ); ?> <span class="req">*</span></label>
						<textarea id="df_content" name="content" rows="5" required minlength="10" maxlength="2000" placeholder="<?php esc_attr_e( 'Compartilhe sua experiência...', 'apollo-comment' ); ?>" class="apollo-textarea"></textarea>
						<span class="apl-form__hint">
							<span id="df_char_count">0</span>/2000 <?php esc_html_e( 'caracteres', 'apollo-comment' ); ?>
						</span>
					</div>
				</fieldset>

				<!-- parent (hidden — for reply threads, set via JS) -->
				<input type="hidden" name="parent" value="0">

				<!-- ═══ SUBMIT ═══ -->
				<div class="apl-form__error" id="dfError" style="display:none;"></div>

				<button type="submit" class="apl-form__submit" id="dfSubmit">
					<i class="ri-quill-pen-fill"></i>
					<span><?php esc_html_e( 'Enviar Depoimento', 'apollo-comment' ); ?></span>
				</button>
			</form>
		</div>

		<style>
			.apl-form--depoimento {
				max-width: 640px;
			}

			.apl-form--depoimento .apl-form__header {
				display: flex;
				align-items: center;
				gap: 8px;
				margin-bottom: 16px;
			}

			.apl-form--depoimento .apl-form__header i {
				font-size: 22px;
				color: #a855f7;
			}

			.apl-form--depoimento .apl-form__header h3 {
				margin: 0;
				font-size: 18px;
				font-weight: 700;
				color: var(--txt, #fff);
			}

			.apl-form--depoimento .apl-form__fieldset {
				border: 1px solid var(--border, rgba(var(--rgb-t), .08));
				border-radius: 12px;
				padding: 16px;
				margin: 0 0 16px;
			}

			.apl-form--depoimento .apl-form__fieldset legend {
				display: flex;
				align-items: center;
				gap: 6px;
				font-size: 14px;
				font-weight: 600;
				color: var(--txt-muted, #aaa);
				padding: 0 8px;
			}

			.apl-form--depoimento .apl-form__fieldset legend i {
				font-size: 16px;
			}

			.apl-form--depoimento .apl-form__field {
				margin-bottom: 12px;
			}

			.apl-form--depoimento .apl-form__field label {
				display: block;
				font-size: 13px;
				font-weight: 600;
				color: var(--txt, #fff);
				margin-bottom: 4px;
			}

			.apl-form--depoimento .apl-form__field .req {
				color: #ef4444;
			}

			.apl-form--depoimento .apl-form__field input,
			.apl-form--depoimento .apl-form__field select,
			.apl-form--depoimento .apl-form__field textarea {
				width: 100%;
				padding: 10px 12px;
				background: var(--bg-card, #1a1a2e);
				border: 1px solid var(--border, rgba(var(--rgb-t), .08));
				border-radius: 8px;
				color: var(--txt, #fff);
				font-size: 14px;
				transition: border-color .2s;
			}

			.apl-form--depoimento .apl-form__field input:focus,
			.apl-form--depoimento .apl-form__field select:focus,
			.apl-form--depoimento .apl-form__field textarea:focus {
				outline: none;
				border-color: #a855f7;
			}

			.apl-form--depoimento .apl-form__hint {
				display: block;
				font-size: 11px;
				color: var(--txt-muted, #888);
				margin-top: 4px;
			}

			.apl-form--depoimento .apl-form__preview {
				display: flex;
				align-items: center;
				gap: 8px;
				padding: 10px 14px;
				border-radius: 8px;
				background: rgba(168, 85, 247, .08);
				color: var(--txt, #fff);
				font-size: 13px;
			}

			.apl-form--depoimento .apl-form__preview i {
				color: #a855f7;
				font-size: 16px;
			}

			.apl-form--depoimento .apl-form__error {
				background: rgba(239, 68, 68, .12);
				color: #ef4444;
				padding: 10px 14px;
				border-radius: 8px;
				font-size: 13px;
				margin-bottom: 12px;
			}

			.apl-form--depoimento .apl-form__submit {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 8px;
				width: 100%;
				padding: 12px;
				border: none;
				border-radius: 10px;
				background: #a855f7;
				color: #fff;
				font-size: 15px;
				font-weight: 700;
				cursor: pointer;
				transition: opacity .2s;
			}

			.apl-form--depoimento .apl-form__submit:hover {
				opacity: .9;
			}

			.apl-form--depoimento .apl-form__submit:disabled {
				opacity: .5;
				cursor: not-allowed;
			}

			.apl-form--depoimento .apl-form__submit i {
				font-size: 18px;
			}
		</style>

		<script>
			(function() {
				'use strict';
				var REST = '<?php echo esc_js( $rest_url ); ?>';
				var NONCE = '<?php echo esc_js( $nonce ); ?>';
				var form = document.getElementById('apl-depoimento-create');
				var errEl = document.getElementById('dfError');
				var btn = document.getElementById('dfSubmit');

				if (!form) return;

				/* Character counter */
				var textarea = form.querySelector('[name="content"]');
				var counter = document.getElementById('df_char_count');
				if (textarea && counter) {
					textarea.addEventListener('input', function() {
						counter.textContent = textarea.value.length;
					});
				}

				/* Post title preview on post_id blur */
				var postIdInput = form.querySelector('[name="post_id"]');
				var previewWrap = document.getElementById('df_post_preview');
				var previewTitle = document.getElementById('df_post_title');

				if (postIdInput && previewWrap && previewTitle) {
					postIdInput.addEventListener('blur', async function() {
						var pid = parseInt(postIdInput.value, 10);
						if (!pid || pid < 1) {
							previewWrap.style.display = 'none';
							return;
						}
						try {
							var r = await fetch('<?php echo esc_js( rest_url( 'wp/v2/posts/' ) ); ?>' + pid, {
								headers: {
									'X-WP-Nonce': NONCE
								},
								credentials: 'same-origin'
							});
							if (r.ok) {
								var p = await r.json();
								previewTitle.textContent = p.title && p.title.rendered ? p.title.rendered : '#' + pid;
								previewWrap.style.display = '';
							} else {
								previewWrap.style.display = 'none';
							}
						} catch (_) {
							previewWrap.style.display = 'none';
						}
					});
				}

				/* Pre-fill from external trigger: apollo:form:open with detail.postId */
				document.addEventListener('apollo:form:open', function(e) {
					if (!e.detail) return;
					if (e.detail.postId && postIdInput) {
						postIdInput.value = e.detail.postId;
						postIdInput.dispatchEvent(new Event('blur'));
					}
					if (e.detail.parent) {
						var parentInput = form.querySelector('[name="parent"]');
						if (parentInput) parentInput.value = e.detail.parent;
					}
				});

				form.addEventListener('submit', async function(e) {
					e.preventDefault();
					errEl.style.display = 'none';
					btn.disabled = true;
					btn.querySelector('span').textContent = 'Enviando...';

					try {
						var data = {
							post_id: parseInt(form.querySelector('[name="post_id"]').value, 10) || 0,
							content: form.querySelector('[name="content"]').value.trim(),
							parent: parseInt(form.querySelector('[name="parent"]').value, 10) || 0
						};

						if (!data.post_id || data.post_id < 1) throw new Error('Informe o ID do conteúdo.');
						if (!data.content || data.content.length < 10) throw new Error('O depoimento deve ter pelo menos 10 caracteres.');

						var res = await fetch(REST, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-WP-Nonce': NONCE
							},
							credentials: 'same-origin',
							body: JSON.stringify(data)
						});

						var result = await res.json();

						if (res.ok && (result.id || result.comment_ID)) {
							btn.querySelector('span').textContent = 'Depoimento Enviado!';
							btn.style.background = '#22c55e';
							form.reset();
							if (counter) counter.textContent = '0';
							if (previewWrap) previewWrap.style.display = 'none';
							setTimeout(function() {
								btn.style.background = '';
								btn.querySelector('span').textContent = 'Enviar Depoimento';
								btn.disabled = false;
							}, 3000);
						} else {
							throw new Error(result.error || result.message || 'Erro ao enviar depoimento.');
						}
					} catch (err) {
						errEl.textContent = err.message;
						errEl.style.display = '';
						btn.disabled = false;
						btn.querySelector('span').textContent = 'Enviar Depoimento';
					}
				});
			})();
		</script>
		<?php
	}
}