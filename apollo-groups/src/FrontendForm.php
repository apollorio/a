<?php
/**
 * FrontendForm — Renders inline group creation form for frontend
 *
 * Hooks into `apollo/groups/render_create_form`
 * Covers all creatable fields for the apollo_groups table:
 *   name, type (comuna/nucleo), description, tags, rules
 *
 * Cover/avatar are uploaded post-creation via REST endpoints.
 * Privacy is auto-determined by type (comuna=public, nucleo=private).
 *
 * @package Apollo\Groups
 */

declare(strict_types=1);

namespace Apollo\Groups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FrontendForm {

	public function __construct() {
		add_action( 'apollo/groups/render_create_form', array( $this, 'render' ), 10, 1 );
	}

	/**
	 * Render the inline group creation form inside panel-forms.php
	 *
	 * @param int $user_id Current user ID
	 */
	public function render( int $user_id ): void {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Faça login para criar grupos.', 'apollo-groups' ) . '</p>';
			return;
		}

		$rest_url = esc_url_raw( rest_url( 'apollo/v1/groups' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );
		?>
		<div class="apl-form apl-form--group" id="apolloGroupForm">
			<div class="apl-form__header">
				<i class="ri-team-fill"></i>
				<h3><?php esc_html_e( 'Criar Grupo', 'apollo-groups' ); ?></h3>
			</div>

			<form id="apl-group-create" autocomplete="off" novalidate>

				<!-- ═══ TIPO ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-organization-chart"></i> <?php esc_html_e( 'Tipo', 'apollo-groups' ); ?></legend>

					<div class="apl-form__field">
						<label for="gf_type"><?php esc_html_e( 'Tipo de Grupo', 'apollo-groups' ); ?> <span class="req">*</span></label>
						<select id="gf_type" name="type" required>
							<option value=""><?php esc_html_e( 'Selecione...', 'apollo-groups' ); ?></option>
							<option value="comuna"><?php esc_html_e( 'Comuna — Comunidade aberta', 'apollo-groups' ); ?></option>
							<?php if ( current_user_can( 'manage_options' ) ) : // SSOT: núcleo is admin-created only. ?>
								<option value="nucleo"><?php esc_html_e( 'Núcleo — Equipe de trabalho', 'apollo-groups' ); ?></option>
							<?php endif; ?>
						</select>
					</div>
				</fieldset>

				<!-- ═══ INFORMAÇÕES ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-information-line"></i> <?php esc_html_e( 'Informações', 'apollo-groups' ); ?></legend>

					<div class="apl-form__field">
						<label for="gf_name"><?php esc_html_e( 'Nome', 'apollo-groups' ); ?> <span class="req">*</span></label>
						<input type="text" id="gf_name" name="name" required maxlength="100" placeholder="<?php esc_attr_e( 'Nome do grupo', 'apollo-groups' ); ?>" class="apollo-input">
					</div>

					<div class="apl-form__field">
						<label for="gf_description"><?php esc_html_e( 'Descrição', 'apollo-groups' ); ?></label>
						<textarea id="gf_description" name="description" rows="3" placeholder="<?php esc_attr_e( 'Descreva o grupo...', 'apollo-groups' ); ?>" class="apollo-textarea"></textarea>
					</div>

					<div class="apl-form__field">
						<label for="gf_tags"><?php esc_html_e( 'Tags', 'apollo-groups' ); ?></label>
						<input type="text" id="gf_tags" name="tags" placeholder="<?php esc_attr_e( 'música, noite, rio (separadas por vírgula)', 'apollo-groups' ); ?>" class="apollo-input">
					</div>

					<div class="apl-form__field">
						<label for="gf_rules"><?php esc_html_e( 'Regras', 'apollo-groups' ); ?></label>
						<textarea id="gf_rules" name="rules" rows="3" placeholder="<?php esc_attr_e( 'Regras do grupo (uma por linha)...', 'apollo-groups' ); ?>" class="apollo-textarea"></textarea>
					</div>
				</fieldset>

				<!-- ═══ SUBMIT ═══ -->
				<div class="apl-form__error" id="gfError" style="display:none;"></div>

				<button type="submit" class="apl-form__submit" id="gfSubmit">
					<i class="ri-send-plane-fill"></i>
					<span><?php esc_html_e( 'Criar Grupo', 'apollo-groups' ); ?></span>
				</button>

			</form>
		</div>

		<style>
		.apl-form--group { max-width: 640px; }
		.apl-form--group .apl-form__header { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
		.apl-form--group .apl-form__header i { font-size: 22px; color: #eab308; }
		.apl-form--group .apl-form__header h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--txt, #fff); }
		.apl-form--group .apl-form__fieldset {
			border: 1px solid var(--border, rgba(var(--rgb-t),.08));
			border-radius: 12px; padding: 16px; margin: 0 0 16px;
		}
		.apl-form--group .apl-form__fieldset legend {
			display: flex; align-items: center; gap: 6px;
			font-size: 14px; font-weight: 600; color: var(--txt-muted, #aaa); padding: 0 8px;
		}
		.apl-form--group .apl-form__fieldset legend i { font-size: 16px; }
		.apl-form--group .apl-form__field { margin-bottom: 12px; }
		.apl-form--group .apl-form__field label {
			display: block; font-size: 13px; font-weight: 600;
			color: var(--txt, #fff); margin-bottom: 4px;
		}
		.apl-form--group .apl-form__field .req { color: #ef4444; }
		.apl-form--group .apl-form__field input,
		.apl-form--group .apl-form__field select,
		.apl-form--group .apl-form__field textarea {
			width: 100%; padding: 10px 12px;
			background: var(--bg-card, #1a1a2e);
			border: 1px solid var(--border, rgba(var(--rgb-t),.08));
			border-radius: 8px; color: var(--txt, #fff);
			font-size: 14px; transition: border-color .2s;
		}
		.apl-form--group .apl-form__field input:focus,
		.apl-form--group .apl-form__field select:focus,
		.apl-form--group .apl-form__field textarea:focus {
			outline: none; border-color: #eab308;
		}
		.apl-form--group .apl-form__error {
			background: rgba(239,68,68,.12); color: #ef4444;
			padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px;
		}
		.apl-form--group .apl-form__submit {
			display: flex; align-items: center; justify-content: center; gap: 8px;
			width: 100%; padding: 12px; border: none; border-radius: 10px;
			background: #eab308; color: #000; font-size: 15px;
			font-weight: 700; cursor: pointer; transition: opacity .2s;
		}
		.apl-form--group .apl-form__submit:hover { opacity: .9; }
		.apl-form--group .apl-form__submit:disabled { opacity: .5; cursor: not-allowed; }
		.apl-form--group .apl-form__submit i { font-size: 18px; }
		</style>

		<script>
		(function(){
			'use strict';
			var REST  = '<?php echo esc_js( $rest_url ); ?>';
			var NONCE = '<?php echo esc_js( $nonce ); ?>';
			var form  = document.getElementById('apl-group-create');
			var errEl = document.getElementById('gfError');
			var btn   = document.getElementById('gfSubmit');

			if (!form) return;

			form.addEventListener('submit', async function(e) {
				e.preventDefault();
				errEl.style.display = 'none';
				btn.disabled = true;
				btn.querySelector('span').textContent = 'Criando...';

				try {
					var data = {};

					['name','type','description','tags','rules'].forEach(function(k) {
						var el = form.querySelector('[name="'+k+'"]');
						if (el && el.value.trim()) data[k] = el.value.trim();
					});

					if (!data.name) throw new Error('Nome é obrigatório.');
					if (!data.type) throw new Error('Selecione o tipo.');

					var res = await fetch(REST, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
						credentials: 'same-origin',
						body: JSON.stringify(data)
					});

					var result = await res.json();

					if (res.ok && (result.id || result.slug)) {
						btn.querySelector('span').textContent = 'Criado!';
						btn.style.background = '#22c55e';
						btn.style.color = '#fff';
						var slug = result.slug || result.id;
						setTimeout(function(){
							window.location.href = '<?php echo esc_js( home_url( '/grupo/' ) ); ?>' + slug;
						}, 1500);
					} else {
						throw new Error(result.error || result.message || 'Erro ao criar grupo.');
					}
				} catch(err) {
					errEl.textContent = err.message;
					errEl.style.display = '';
					btn.disabled = false;
					btn.querySelector('span').textContent = 'Criar Grupo';
				}
			});
		})();
		</script>
		<?php
	}
}