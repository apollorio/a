<?php

/**
 * Apollo Admin — Luxury Slide-In Modal Form
 *
 * Full-height right-side panel with floating labels.
 * Triggered by any [data-apollo-form="form-id"] button.
 * Creates draft CPT posts via AJAX (wp_insert_post).
 *
 * Available forms:
 *   - new-event    → event CPT (draft)
 *   - new-dj       → dj CPT (draft)
 *   - new-hub      → hub CPT (draft)
 *   - new-local    → local CPT (draft)
 *   - new-classified → classified CPT (draft)
 *   - report       → generic contact/report form
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- Overlay Backdrop -->
<div class="apollo-form-overlay" id="apolloFormOverlay"></div>

<!-- The Modal Itself -->
<div class="apollo-form-modal" id="apolloFormModal">
	<button class="apollo-form-modal__close" id="apolloFormClose" type="button" aria-label="<?php esc_attr_e( 'Fechar', 'apollo-admin' ); ?>">
		<i class="ri-close-line"></i>
	</button>

	<!-- Success View -->
	<div class="apollo-form-success">
		<i class="ri-checkbox-circle-line apollo-form-success__icon"></i>
		<h3 id="apolloFormSuccessTitle"><?php esc_html_e( 'Draft Saved', 'apollo-admin' ); ?></h3>
		<p id="apolloFormSuccessMsg"><?php esc_html_e( 'The draft has been created successfully.', 'apollo-admin' ); ?></p>
	</div>

	<!-- Dynamic Header -->
	<div class="apollo-form-modal__header">
		<h2 id="apolloFormTag"></h2>
		<h1 id="apolloFormTitle"></h1>
	</div>

	<!-- Dynamic Form Body (injected via JS) -->
	<form id="apolloFormBody" autocomplete="off"></form>
</div>

<script>
	(function() {
		'use strict';

		/* ═══ FORM DEFINITIONS ═══ */
		var FORMS = {

			'new-event': {
				tag: 'NOVA',
				title: 'Criar Evento',
				success: 'Evento criado como rascunho.',
				cpt: 'event',
				fields: [{
						key: 'title',
						label: 'Nome do Evento',
						type: 'text',
						required: true
					},
					{
						key: '_event_start_date',
						label: 'Data Início',
						type: 'date',
						required: true
					},
					{
						key: '_event_end_date',
						label: 'Data Fim',
						type: 'date',
						required: false
					},
					{
						key: '_event_start_time',
						label: 'Hora Início',
						type: 'time',
						required: true
					},
					{
						key: '_event_end_time',
						label: 'Hora Fim',
						type: 'time',
						required: false
					},
					{
						key: '_event_loc_id',
						label: 'Local (ID)',
						type: 'number',
						required: false
					},
					{
						key: '_event_ticket_url',
						label: 'URL Ingressos',
						type: 'url',
						required: false
					},
					{
						key: '_event_ticket_price',
						label: 'Preço Ingresso',
						type: 'text',
						required: false
					},
					{
						key: '_event_privacy',
						label: 'Privacidade',
						type: 'select',
						required: false,
						options: [{
								value: 'public',
								text: 'Público'
							},
							{
								value: 'private',
								text: 'Privado'
							},
							{
								value: 'invite',
								text: 'Convidados'
							}
						]
					},
					{
						key: 'content',
						label: 'Descrição',
						type: 'textarea',
						required: false
					}
				]
			},

			'new-dj': {
				tag: 'NOVO',
				title: 'Adicionar DJ',
				success: 'DJ criado como rascunho.',
				cpt: 'dj',
				fields: [{
						key: 'title',
						label: 'Nome Artístico',
						type: 'text',
						required: true
					},
					{
						key: '_dj_bio_short',
						label: 'Bio (280 chars)',
						type: 'textarea',
						required: false
					},
					{
						key: '_dj_instagram',
						label: 'Instagram',
						type: 'text',
						required: false
					},
					{
						key: '_dj_soundcloud',
						label: 'SoundCloud URL',
						type: 'url',
						required: false
					},
					{
						key: '_dj_spotify',
						label: 'Spotify URL',
						type: 'url',
						required: false
					},
					{
						key: '_dj_youtube',
						label: 'YouTube URL',
						type: 'url',
						required: false
					},
					{
						key: '_dj_website',
						label: 'Website',
						type: 'url',
						required: false
					},
					{
						key: '_dj_user_id',
						label: 'User ID (vincular)',
						type: 'number',
						required: false
					}
				]
			},

			'new-hub': {
				tag: 'NOVO',
				title: 'Criar Hub',
				success: 'Hub criado como rascunho.',
				cpt: 'hub',
				fields: [{
						key: 'title',
						label: 'Título do Hub',
						type: 'text',
						required: true
					},
					{
						key: 'content',
						label: 'Descrição',
						type: 'textarea',
						required: false
					}
				]
			},

			'new-local': {
				tag: 'NOVO',
				title: 'Adicionar Local',
				success: 'Local criado como rascunho.',
				cpt: 'local',
				fields: [{
						key: 'title',
						label: 'Nome do Espaço',
						type: 'text',
						required: true
					},
					{
						key: '_local_address',
						label: 'Endereço',
						type: 'text',
						required: true
					},
					{
						key: '_local_city',
						label: 'Cidade',
						type: 'text',
						required: false
					},
					{
						key: '_local_lat',
						label: 'Latitude',
						type: 'text',
						required: false
					},
					{
						key: '_local_lng',
						label: 'Longitude',
						type: 'text',
						required: false
					},
					{
						key: '_local_capacity',
						label: 'Capacidade',
						type: 'number',
						required: false
					},
					{
						key: '_local_phone',
						label: 'Telefone',
						type: 'text',
						required: false
					},
					{
						key: '_local_instagram',
						label: 'Instagram',
						type: 'text',
						required: false
					},
					{
						key: '_local_website',
						label: 'Website',
						type: 'url',
						required: false
					},
					{
						key: '_local_price_range',
						label: 'Faixa de Preço',
						type: 'select',
						required: false,
						options: [{
								value: '$',
								text: '$'
							},
							{
								value: '$$',
								text: '$$'
							},
							{
								value: '$$$',
								text: '$$$'
							},
							{
								value: '$$$$',
								text: '$$$$'
							}
						]
					}
				]
			},

			'new-classified': {
				tag: 'NOVO',
				title: 'Criar Anúncio',
				success: 'Anúncio criado como rascunho.',
				cpt: 'classified',
				fields: [{
						key: 'title',
						label: 'Título do Anúncio',
						type: 'text',
						required: true
					},
					{
						key: '_classified_price',
						label: 'Preço (R$)',
						type: 'number',
						required: false
					},
					{
						key: '_classified_condition',
						label: 'Condição',
						type: 'select',
						required: false,
						options: [{
								value: 'novo',
								text: 'Novo'
							},
							{
								value: 'usado',
								text: 'Usado'
							},
							{
								value: 'recondicionado',
								text: 'Recondicionado'
							}
						]
					},
					{
						key: 'content',
						label: 'Descrição',
						type: 'textarea',
						required: false
					}
				]
			},

			'report': {
				tag: 'CONTATO',
				title: 'Como podemos\najudar você?',
				success: 'Mensagem enviada com sucesso.',
				cpt: null,
				fields: [{
						key: 'name',
						label: 'Nome Completo',
						type: 'text',
						required: true
					},
					{
						key: 'email',
						label: 'E-mail',
						type: 'email',
						required: true
					},
					{
						key: 'subject',
						label: 'Assunto',
						type: 'select',
						required: true,
						options: [{
								value: 'partnership',
								text: 'Sobre Parceria'
							},
							{
								value: 'report',
								text: 'Problema ou Denúncia'
							},
							{
								value: 'support',
								text: 'Suporte Técnico'
							},
							{
								value: 'feedback',
								text: 'Elogio ou Crítica'
							}
						]
					},
					{
						key: 'message',
						label: 'Mensagem',
						type: 'textarea',
						required: true
					}
				]
			}
		};

		var overlay = document.getElementById('apolloFormOverlay');
		var modal = document.getElementById('apolloFormModal');
		var closeBtn = document.getElementById('apolloFormClose');
		var formBody = document.getElementById('apolloFormBody');
		var tagEl = document.getElementById('apolloFormTag');
		var titleEl = document.getElementById('apolloFormTitle');
		var successTitle = document.getElementById('apolloFormSuccessTitle');
		var successMsg = document.getElementById('apolloFormSuccessMsg');
		var wrap = document.querySelector('.apollo-cpanel-wrap');
		var currentFormId = null;

		/* ── Build form HTML from definition ── */
		function buildForm(def) {
			var html = '';

			def.fields.forEach(function(f) {
				if (f.type === 'select') {
					html += '<div class="apollo-fg">';
					html += '<select class="apollo-fg__input" name="' + f.key + '"' + (f.required ? ' required' : '') + '>';
					html += '<option value="" disabled selected></option>';
					f.options.forEach(function(o) {
						html += '<option value="' + o.value + '">' + o.text + '</option>';
					});
					html += '</select>';
					html += '<label class="apollo-fg__label">' + f.label + '</label>';
					html += '<i class="ri-arrow-down-s-line apollo-fg__arrow"></i>';
					html += '</div>';
				} else if (f.type === 'textarea') {
					html += '<div class="apollo-fg">';
					html += '<textarea class="apollo-textarea apollo-fg__input" name="' + f.key + '" placeholder=" " rows="3"' + (f.required ? ' required' : '') + '></textarea>';
					html += '<label class="apollo-fg__label">' + f.label + '</label>';
					html += '</div>';
				} else {
					html += '<div class="apollo-fg">';
					html += '<input type="' + f.type + '" class="apollo-fg__input" name="' + f.key + '" placeholder=" "' + (f.required ? ' required' : '') + '>';
					html += '<label class="apollo-fg__label">' + f.label + '</label>';
					html += '</div>';
				}
			});

			html += '<button type="submit" class="apollo-form-submit" id="apolloFormSubmit">';
			html += '<span>' + (def.cpt ? 'Criar Rascunho' : 'Enviar Agora') + '</span>';
			html += '<i class="ri-send-plane-fill"></i>';
			html += '</button>';

			return html;
		}

		/* ── Open ── */
		function openForm(formId) {
			var def = FORMS[formId];
			if (!def) return;

			currentFormId = formId;
			tagEl.textContent = def.tag;
			titleEl.innerHTML = def.title.replace(/\n/g, '<br>');
			successMsg.textContent = def.success;
			formBody.innerHTML = buildForm(def);
			modal.classList.remove('apollo-form-submitted');

			if (wrap) wrap.classList.add('apollo-modal-open');
		}

		/* ── Close ── */
		function closeForm() {
			if (wrap) wrap.classList.remove('apollo-modal-open');
			currentFormId = null;

			setTimeout(function() {
				modal.classList.remove('apollo-form-submitted');
				formBody.innerHTML = '';
			}, 600);
		}

		if (closeBtn) closeBtn.addEventListener('click', closeForm);
		if (overlay) overlay.addEventListener('click', closeForm);

		/* ── Submit ── */
		if (formBody) {
			formBody.addEventListener('submit', function(e) {
				e.preventDefault();

				var def = FORMS[currentFormId];
				if (!def) return;

				var submitBtn = document.getElementById('apolloFormSubmit');
				if (submitBtn) {
					submitBtn.disabled = true;
					submitBtn.querySelector('span').textContent = 'Salvando…';
				}

				var fd = new FormData(formBody);
				fd.append('action', 'apollo_admin_create_draft');
				fd.append('apollo_form_id', currentFormId);
				fd.append('apollo_cpt', def.cpt || '');
				fd.append('_wpnonce', '<?php echo esc_js( wp_create_nonce( 'apollo_admin_create_draft' ) ); ?>');

				fetch(ajaxurl, {
						method: 'POST',
						body: fd,
						credentials: 'same-origin'
					})
					.then(function(r) {
						return r.json();
					})
					.then(function(data) {
						if (data.success) {
							modal.classList.add('apollo-form-submitted');
							setTimeout(function() {
								closeForm();
								location.reload();
							}, 2000);
						} else {
							if (submitBtn) {
								submitBtn.disabled = false;
								submitBtn.querySelector('span').textContent = 'Erro — tente novamente';
								submitBtn.style.borderColor = 'var(--red)';
								submitBtn.style.color = 'var(--red)';
								setTimeout(function() {
									submitBtn.querySelector('span').textContent = def.cpt ? 'Criar Rascunho' : 'Enviar Agora';
									submitBtn.style.borderColor = '';
									submitBtn.style.color = '';
								}, 2000);
							}
						}
					})
					.catch(function() {
						if (submitBtn) {
							submitBtn.disabled = false;
							submitBtn.querySelector('span').textContent = 'Erro de conexão';
						}
					});
			});
		}

		/* ── Delegate triggers ── */
		document.addEventListener('click', function(e) {
			var trigger = e.target.closest('[data-apollo-form]');
			if (trigger) {
				e.preventDefault();
				openForm(trigger.getAttribute('data-apollo-form'));
			}
		});

		/* ── Keyboard: ESC closes ── */
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && currentFormId) closeForm();
		});

	})();
</script>