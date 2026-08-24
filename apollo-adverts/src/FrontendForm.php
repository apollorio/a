<?php
/**
 * FrontendForm — Renders inline classified creation form for frontend
 *
 * Hooks into `apollo/adverts/render_create_form`
 * Covers ALL 9 meta keys + 2 taxonomies from apollo-registry.json
 *
 * Auto-generated/admin-only fields NOT shown:
 *   _classified_featured (admin boost), _classified_views (counter)
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FrontendForm {

	public function __construct() {
		add_action( 'apollo/adverts/render_create_form', array( $this, 'render' ), 10, 1 );
	}

	/**
	 * Render the inline classified creation form inside panel-forms.php
	 *
	 * @param int $user_id Current user ID
	 */
	public function render( int $user_id ): void {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Faça login para criar anúncios.', 'apollo-adverts' ) . '</p>';
			return;
		}

		// Domain taxonomy options
		$domain_terms   = get_terms(
			array(
				'taxonomy'   => APOLLO_TAX_CLASSIFIED_DOMAIN,
				'hide_empty' => false,
			)
		);
		$domain_options = is_wp_error( $domain_terms ) ? array() : $domain_terms;

		$rest_url = esc_url_raw( rest_url( 'apollo/v1/classifieds' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );
		?>
		<div class="apl-form apl-form--classified" id="apolloAdForm">
			<div class="apl-form__header">
				<i class="ri-exchange-box-line-fill"></i>
				<h3><?php esc_html_e( 'Criar Anúncio', 'apollo-adverts' ); ?></h3>
			</div>

			<form id="apl-ad-create" autocomplete="off" novalidate>

				<!-- ═══ BÁSICO ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-information-line"></i> <?php esc_html_e( 'Informações Básicas', 'apollo-adverts' ); ?></legend>

					<div class="apl-form__field">
						<label for="af_title"><?php esc_html_e( 'Título', 'apollo-adverts' ); ?> <span class="req">*</span></label>
						<input type="text" id="af_title" name="title" required maxlength="100" placeholder="<?php esc_attr_e( 'Nome do anúncio', 'apollo-adverts' ); ?>" class="apollo-input">
					</div>

					<div class="apl-form__field">
						<label for="af_content"><?php esc_html_e( 'Descrição', 'apollo-adverts' ); ?> <span class="req">*</span></label>
						<textarea id="af_content" name="content" rows="4" required placeholder="<?php esc_attr_e( 'Descreva seu anúncio...', 'apollo-adverts' ); ?>" class="apollo-textarea"></textarea>
					</div>
				</fieldset>

				<!-- ═══ CLASSIFICAÇÃO ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-exchange-box-line-line"></i> <?php esc_html_e( 'Classificação', 'apollo-adverts' ); ?></legend>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="af_domain"><?php esc_html_e( 'Categoria', 'apollo-adverts' ); ?> <span class="req">*</span></label>
							<select id="af_domain" name="domain" required>
								<option value=""><?php esc_html_e( 'Selecione...', 'apollo-adverts' ); ?></option>
								<?php foreach ( $domain_options as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="af_intent"><?php esc_html_e( 'Intenção', 'apollo-adverts' ); ?> <span class="req">*</span></label>
							<select id="af_intent" name="intent" required>
								<option value=""><?php esc_html_e( 'Selecione...', 'apollo-adverts' ); ?></option>
								<?php foreach ( APOLLO_ADVERTS_INTENTS as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="apl-form__field">
						<label for="af_condition"><?php esc_html_e( 'Condição', 'apollo-adverts' ); ?></label>
						<select id="af_condition" name="condition">
							<option value=""><?php esc_html_e( 'Selecione...', 'apollo-adverts' ); ?></option>
							<?php foreach ( APOLLO_ADVERTS_CONDITIONS as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</fieldset>

				<!-- ═══ VALOR ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-money-dollar-circle-line"></i> <?php esc_html_e( 'Valor', 'apollo-adverts' ); ?></legend>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="af_price"><?php esc_html_e( 'Valor de Referência', 'apollo-adverts' ); ?></label>
							<input type="text" id="af_price" name="price" placeholder="0,00" class="apollo-input">
							<span class="apl-form__hint"><?php esc_html_e( 'Apenas informativo. Negociação via Chat.', 'apollo-adverts' ); ?></span>
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="af_currency"><?php esc_html_e( 'Moeda', 'apollo-adverts' ); ?></label>
							<select id="af_currency" name="currency">
								<option value="BRL" selected>R$ — Real (BRL)</option>
								<option value="USD">$ — Dólar (USD)</option>
								<option value="EUR">€ — Euro (EUR)</option>
							</select>
						</div>
					</div>

					<div class="apl-form__field">
						<label class="custom-checkbox" class="apl-form__checkbox">
							<input type="checkbox" id="af_negotiable" name="negotiable" value="1">
							<span><?php esc_html_e( 'Negociável', 'apollo-adverts' ); ?></span>
						</label>
					</div>
				</fieldset>

				<!-- ═══ CONTATO ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-contacts-line"></i> <?php esc_html_e( 'Contato', 'apollo-adverts' ); ?></legend>

					<div class="apl-form__field">
						<label for="af_location"><?php esc_html_e( 'Localização', 'apollo-adverts' ); ?></label>
						<input type="text" id="af_location" name="location" placeholder="<?php esc_attr_e( 'Cidade, Estado', 'apollo-adverts' ); ?>" class="apollo-input">
					</div>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="af_phone"><?php esc_html_e( 'Telefone', 'apollo-adverts' ); ?></label>
							<input type="tel" id="af_phone" name="phone" placeholder="(00) 00000-0000" class="apollo-input">
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="af_whatsapp"><?php esc_html_e( 'WhatsApp', 'apollo-adverts' ); ?></label>
							<input type="tel" id="af_whatsapp" name="whatsapp" placeholder="(00) 00000-0000" class="apollo-input">
						</div>
					</div>
				</fieldset>

				<!-- ═══ EXPIRAÇÃO ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-calendar-check-line"></i> <?php esc_html_e( 'Expiração', 'apollo-adverts' ); ?></legend>

					<div class="apl-form__field">
						<label for="af_expires_at"><?php esc_html_e( 'Data de Expiração', 'apollo-adverts' ); ?></label>
						<input type="date" id="af_expires_at" name="expires_at" data-apollo-date-picker="true">
						<span class="apl-form__hint"><?php esc_html_e( 'Opcional. Após esta data o anúncio será desativado automaticamente.', 'apollo-adverts' ); ?></span>
					</div>
				</fieldset>

				<!-- ═══ SUBMIT ═══ -->
				<div class="apl-form__error" id="afError" style="display:none;"></div>

				<button type="submit" class="apl-form__submit" id="afSubmit">
					<i class="ri-send-plane-fill"></i>
					<span><?php esc_html_e( 'Publicar Anúncio', 'apollo-adverts' ); ?></span>
				</button>

			</form>
		</div>

		<style>
		.apl-form--classified { max-width: 640px; }
		.apl-form--classified .apl-form__header { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
		.apl-form--classified .apl-form__header i { font-size: 22px; color: #3b82f6; }
		.apl-form--classified .apl-form__header h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--txt, #fff); }
		.apl-form--classified .apl-form__fieldset {
			border: 1px solid var(--border, rgba(var(--rgb-t),.08));
			border-radius: 12px; padding: 16px; margin: 0 0 16px;
		}
		.apl-form--classified .apl-form__fieldset legend {
			display: flex; align-items: center; gap: 6px;
			font-size: 14px; font-weight: 600; color: var(--txt-muted, #aaa); padding: 0 8px;
		}
		.apl-form--classified .apl-form__fieldset legend i { font-size: 16px; }
		.apl-form--classified .apl-form__field { margin-bottom: 12px; }
		.apl-form--classified .apl-form__field label {
			display: block; font-size: 13px; font-weight: 600;
			color: var(--txt, #fff); margin-bottom: 4px;
		}
		.apl-form--classified .apl-form__field .req { color: #ef4444; }
		.apl-form--classified .apl-form__field input,
		.apl-form--classified .apl-form__field select,
		.apl-form--classified .apl-form__field textarea {
			width: 100%; padding: 10px 12px;
			background: var(--bg-card, #1a1a2e);
			border: 1px solid var(--border, rgba(var(--rgb-t),.08));
			border-radius: 8px; color: var(--txt, #fff);
			font-size: 14px; transition: border-color .2s;
		}
		.apl-form--classified .apl-form__field input:focus,
		.apl-form--classified .apl-form__field select:focus,
		.apl-form--classified .apl-form__field textarea:focus {
			outline: none; border-color: #3b82f6;
		}
		.apl-form--classified .apl-form__hint { display: block; font-size: 11px; color: var(--txt-muted, #888); margin-top: 4px; }
		.apl-form--classified .apl-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
		@media (max-width: 480px) { .apl-form--classified .apl-form__row { grid-template-columns: 1fr; } }
		.apl-form--classified .apl-form__checkbox {
			display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;
			color: var(--txt, #fff); font-weight: 400;
		}
		.apl-form--classified .apl-form__checkbox input { width: auto; }
		.apl-form--classified .apl-form__error {
			background: rgba(239,68,68,.12); color: #ef4444;
			padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px;
		}
		.apl-form--classified .apl-form__submit {
			display: flex; align-items: center; justify-content: center; gap: 8px;
			width: 100%; padding: 12px; border: none; border-radius: 10px;
			background: #3b82f6; color: #fff; font-size: 15px;
			font-weight: 700; cursor: pointer; transition: opacity .2s;
		}
		.apl-form--classified .apl-form__submit:hover { opacity: .9; }
		.apl-form--classified .apl-form__submit:disabled { opacity: .5; cursor: not-allowed; }
		.apl-form--classified .apl-form__submit i { font-size: 18px; }
		</style>

		<script>
		(function(){
			'use strict';
			var REST  = '<?php echo esc_js( $rest_url ); ?>';
			var NONCE = '<?php echo esc_js( $nonce ); ?>';
			var form  = document.getElementById('apl-ad-create');
			var errEl = document.getElementById('afError');
			var btn   = document.getElementById('afSubmit');

			if (!form) return;

			form.addEventListener('submit', async function(e) {
				e.preventDefault();
				errEl.style.display = 'none';
				btn.disabled = true;
				btn.querySelector('span').textContent = 'Publicando...';

				try {
					var data = {};

					/* Text fields */
					['title','content','domain','intent','condition','location',
					'phone','whatsapp','price','currency','expires_at'].forEach(function(k) {
						var el = form.querySelector('[name="'+k+'"]');
						if (el && el.value.trim()) data[k] = el.value.trim();
					});

					/* Checkbox: negotiable */
					var negEl = form.querySelector('[name="negotiable"]');
					if (negEl && negEl.checked) data.negotiable = '1';

					if (!data.title) throw new Error('Título é obrigatório.');
					if (!data.content) throw new Error('Descrição é obrigatória.');
					if (!data.domain) throw new Error('Categoria é obrigatória.');
					if (!data.intent) throw new Error('Intenção é obrigatória.');

					var res = await fetch(REST, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
						credentials: 'same-origin',
						body: JSON.stringify(data)
					});

					var result = await res.json();

					if (res.ok && (result.id || result.permalink)) {
						btn.querySelector('span').textContent = 'Publicado!';
						btn.style.background = '#22c55e';
						if (result.permalink) {
							setTimeout(function(){ window.location.href = result.permalink; }, 1500);
						}
					} else {
						throw new Error(result.error || result.message || 'Erro ao criar anúncio.');
					}
				} catch(err) {
					errEl.textContent = err.message;
					errEl.style.display = '';
					btn.disabled = false;
					btn.querySelector('span').textContent = 'Publicar Anúncio';
				}
			});
		})();
		</script>
		<?php
	}
}