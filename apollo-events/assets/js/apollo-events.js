/**
 * Apollo Events — Main JS
 *
 * Handles: view switching, form submission, banner upload, event interactions
 *
 * @package Apollo\Event
 */

(function () {
	'use strict';

	const ApolloEvents = {

		config: window.apolloEvents || {},

		init() {
			this.initViewSwitcher();
			this.initCreateForm();
			this.initBannerUpload();
		},

		// ─── View Switcher (archive) ─────────────────────────────────────

		initViewSwitcher() {
			const buttons = document.querySelectorAll('.a-eve-view-btn');
			const container = document.getElementById('a-eve-events-container');

			if (!buttons.length || !container) return;

			buttons.forEach(btn => {
				btn.addEventListener('click', () => {
					const view = btn.dataset.view;

					// Update active button
					buttons.forEach(b => b.classList.remove('a-eve-view-btn--active'));
					btn.classList.add('a-eve-view-btn--active');

					// Update container class
					container.className = container.className.replace(
						/a-eve-events--\w+/,
						`a-eve-events--${view}`
					);

					// Show/hide map
					const mapContainer = document.querySelector('.a-eve-map-container');
					if (mapContainer) {
						mapContainer.style.display = view === 'map' ? 'block' : 'none';
					}
				});
			});
		},

		// ─── Create Event Form ───────────────────────────────────────────

		initCreateForm() {
			const form = document.getElementById('a-eve-create-form');
			if (!form) return;

			form.addEventListener('submit', async (e) => {
				e.preventDefault();

				const messageEl = document.getElementById('a-eve-form-message');
				const submitBtn = form.querySelector('.a-eve-form__submit');

				submitBtn.disabled = true;
				submitBtn.textContent = 'Criando...';

				try {
					const formData = new FormData(form);
					const data = {};
					formData.forEach((value, key) => {
						if (key.endsWith('[]')) {
							const normalized = key.replace('[]', '');
							if (!Array.isArray(data[normalized])) {
								data[normalized] = [];
							}
							if (value !== '') {
								data[normalized].push(value);
							}
							return;
						}

						data[key] = value;
					});

					if (data.banner) {
						data.banner = parseInt(data.banner, 10) || 0;
					}

					if (data.loc_id) {
						data.loc_id = parseInt(data.loc_id, 10) || 0;
					}

					if (Array.isArray(data.dj_ids)) {
						data.dj_ids = data.dj_ids
							.map((id) => parseInt(id, 10))
							.filter((id) => !Number.isNaN(id) && id > 0);
					}

					// Editor content
					if (typeof tinymce !== 'undefined' && tinymce.get('event_content')) {
						data.content = tinymce.get('event_content').getContent();
					}

					const response = await fetch(`${this.config.rest_url}/eventos`, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': this.config.nonce,
						},
						body: JSON.stringify(data),
					});

					const result = await response.json();

					if (response.ok) {
						messageEl.className = 'a-eve-form__message a-eve-form__message--success';
						messageEl.textContent = 'Evento criado com sucesso!';
						messageEl.style.display = 'block';

						// Redirect after 1s
						if (result.permalink) {
							setTimeout(() => {
								window.location.href = result.permalink;
							}, 1000);
						}
					} else {
						throw new Error(result.error || 'Erro ao criar evento.');
					}
				} catch (error) {
					messageEl.className = 'a-eve-form__message a-eve-form__message--error';
					messageEl.textContent = error.message;
					messageEl.style.display = 'block';
				} finally {
					submitBtn.disabled = false;
					submitBtn.textContent = 'Criar Evento';
				}
			});
		},

		// ─── Banner Upload (WP Media) ────────────────────────────────────

		initBannerUpload() {
			const btn = document.getElementById('event_banner_btn');
			if (!btn) return;

			btn.addEventListener('click', () => {
				if (typeof wp === 'undefined' || !wp.media) return;

				const frame = wp.media({
					title: 'Selecionar Banner',
					button: { text: 'Usar como banner' },
					multiple: false,
					library: { type: 'image' },
				});

				frame.on('select', () => {
					const attachment = frame.state().get('selection').first().toJSON();
					document.getElementById('event_banner').value = attachment.id;
					const preview = document.getElementById('event_banner_preview');
					preview.innerHTML = `<img src="${attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url}" alt="Banner">`;
				});

				frame.open();
			});
		},
	};

	// Init on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => ApolloEvents.init());
	} else {
		ApolloEvents.init();
	}
})();
