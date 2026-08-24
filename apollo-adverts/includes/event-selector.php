<?php
/**
 * Event Selector for Ticket Resale Classifieds
 *
 * When classified_domain = repasse|ingresso|ticket, the form shows
 * an ApolloSearch-powered event selector + "Solicitar Evento" popup.
 *
 * @package Apollo\Adverts
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domain slugs considered "ticket resale" — require event selection.
 */
define( 'APOLLO_ADVERTS_TICKET_DOMAINS', array( 'repasse', 'ingresso', 'ticket' ) );

/**
 * Add _classified_event_id field to "publish" form.
 * Injected right after the domain/category field (order 12).
 */
function apollo_adverts_add_event_field( array $fields, string $scheme ): array {
	if ( $scheme !== 'publish' ) {
		return $fields;
	}

	$fields[] = array(
		'name'        => '_classified_event_id',
		'type'        => 'event_search',
		'label'       => __( 'Evento vinculado', 'apollo-adverts' ),
		'order'       => 12,
		'is_required' => false, // JS makes it required dynamically for ticket domains
		'placeholder' => __( 'Buscar evento pelo nome...', 'apollo-adverts' ),
		'filter'      => array(),
		'validator'   => array(),
	);

	return $fields;
}
add_filter( 'apollo/adverts/form/load', 'apollo_adverts_add_event_field', 15, 2 );

/**
 * Register the "event_search" field type renderer.
 */
function apollo_adverts_register_event_field_type(): void {
	\Apollo\Adverts\Form::register_field_type( 'event_search', 'apollo_adverts_render_event_search_field' );
}
add_action( 'init', 'apollo_adverts_register_event_field_type', 9 );

/**
 * Render event search field with ApolloSearch typeahead + Solicitar Evento popup.
 *
 * @param array  $field Field config.
 * @param string $error Validation error.
 */
function apollo_adverts_render_event_search_field( array $field, string $error = '' ): void {
	$event_id   = absint( $field['value'] ?? 0 );
	$event_name = '';
	if ( $event_id ) {
		$event_post = get_post( $event_id );
		if ( $event_post && $event_post->post_type === 'event' ) {
			$event_name = $event_post->post_title;
		}
	}

	$ticket_domains_json = wp_json_encode( APOLLO_ADVERTS_TICKET_DOMAINS );
	?>
	<div
		class="apollo-field-wrap apollo-field-event-search <?php echo $error ? 'has-error' : ''; ?>"
		id="event-selector-wrap"
		style="display:none;"
		data-ticket-domains="<?php echo esc_attr( $ticket_domains_json ); ?>"
	>
		<label for="_classified_event_id_search">
			<?php echo esc_html( $field['label'] ); ?>
			<span class="required">*</span>
		</label>

		<div class="event-search-container">
			<!-- ApolloSearch typeahead input -->
			<input
				type="text"
				id="_classified_event_id_search"
				data-apollo-search="events"
				data-search-select="true"
				data-search-target="#_classified_event_id"
				data-search-min="2"
				data-search-limit="8"
				placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" class="apollo-input"
				value="<?php echo esc_attr( $event_name ); ?>"
				class="apollo-field-text"
				autocomplete="off"
			/>

			<!-- Hidden input for selected event ID -->
			<input
				type="hidden"
				id="_classified_event_id"
				name="_classified_event_id"
				value="<?php echo $event_id ?: ''; ?>"
			/>

			<!-- Selected event preview -->
			<div class="event-selected-preview" id="event-selected-preview" style="<?php echo $event_id ? '' : 'display:none;'; ?>">
				<i class="ri-calendar-event-fill"></i>
				<span id="event-selected-name"><?php echo esc_html( $event_name ); ?></span>
				<button type="button" class="event-clear-btn" id="event-clear-btn" aria-label="Limpar seleção">
					<i class="ri-close-circle-line"></i>
				</button>
			</div>
		</div>

		<!-- "Não encontrou?" link -->
		<div class="event-not-found-link">
			<span><?php esc_html_e( 'Não encontrou o evento?', 'apollo-adverts' ); ?></span>
			<button type="button" class="solicitar-evento-btn" id="solicitar-evento-open">
				<i class="ri-add-circle-line"></i>
				<?php esc_html_e( 'Solicitar Evento', 'apollo-adverts' ); ?>
			</button>
		</div>

		<?php if ( $error ) : ?>
			<span class="apollo-field-error"><?php echo esc_html( $error ); ?></span>
		<?php endif; ?>
	</div>

	<!-- ════════════════════════════════════════════════════════════════ -->
	<!-- SOLICITAR EVENTO — Multi-step Popup                           -->
	<!-- ════════════════════════════════════════════════════════════════ -->
	<div class="solicitar-evento-overlay" id="solicitar-evento-overlay">
		<div class="solicitar-evento-popup">
			<!-- Close -->
			<button type="button" class="popup-close" id="solicitar-evento-close" aria-label="Fechar">
				<i class="ri-close-line"></i>
			</button>

			<!-- Header -->
			<div class="popup-header">
				<h3><i class="ri-calendar-todo-line"></i> Solicitar Evento</h3>
				<p class="popup-subtitle">Preencha as informações para solicitar o cadastro de um evento</p>
			</div>

			<!-- Progress -->
			<div class="popup-progress">
				<div class="popup-progress-bar" id="solicitar-progress" style="width: 16.66%;"></div>
			</div>
			<div class="popup-step-indicator">
				<span id="solicitar-step-current">1</span> / <span>6</span>
			</div>

			<!-- Steps -->
			<div class="popup-steps-container" id="solicitar-steps">

				<!-- Step 1: Nome do evento -->
				<div class="popup-step active" data-step="1">
					<label class="popup-step-label">Nome do evento</label>
					<input
						type="text"
						id="solicitar_nome"
						class="apollo-input popup-step-input"
						placeholder="Ex: Festival Lollapalooza 2025"
						maxlength="120"
						required
					/>
				</div>

				<!-- Step 2: Espaço/Local -->
				<div class="popup-step" data-step="2">
					<label class="popup-step-label">Espaço / Local</label>
					<input
						type="text"
						id="solicitar_espaco"
						data-apollo-search="locs"
						data-search-min="2"
						data-search-limit="6"
						class="apollo-input popup-step-input"
						placeholder="Ex: Autódromo de Interlagos"
						maxlength="120"
					/>
				</div>

				<!-- Step 3: Data -->
				<div class="popup-step" data-step="3">
					<label class="popup-step-label">Data do evento</label>
					<div class="popup-date-group">
						<input type="text" id="solicitar_dia" class="apollo-input popup-date-field" placeholder="DD" maxlength="2" inputmode="numeric" />
						<span class="popup-date-sep">/</span>
						<input type="text" id="solicitar_mes" class="apollo-input popup-date-field" placeholder="MM" maxlength="2" inputmode="numeric" />
						<span class="popup-date-sep">/</span>
						<input type="text" id="solicitar_ano" class="apollo-input popup-date-field popup-date-year" placeholder="AA" maxlength="2" inputmode="numeric" />
					</div>
				</div>

				<!-- Step 4: DJs / Artistas -->
				<div class="popup-step" data-step="4">
					<label class="popup-step-label">DJs / Artistas</label>
					<input
						type="text"
						id="solicitar_djs"
						class="apollo-input popup-step-input"
						placeholder="Ex: Vintage Culture, Alok, ANNA"
						maxlength="300"
					/>
					<span class="popup-step-hint">Separe por vírgula</span>
				</div>

				<!-- Step 5: Gêneros musicais -->
				<div class="popup-step" data-step="5">
					<label class="popup-step-label">Gêneros musicais</label>
					<input
						type="text"
						id="solicitar_generos"
						class="apollo-input popup-step-input"
						placeholder="Ex: Techno, House, Trance"
						maxlength="200"
					/>
					<span class="popup-step-hint">Separe por vírgula</span>
				</div>

				<!-- Step 6: Link de vendas -->
				<div class="popup-step" data-step="6">
					<label class="popup-step-label">Link de vendas (oficial)</label>
					<input
						type="url"
						id="solicitar_link"
						class="apollo-input popup-step-input"
						placeholder="https://www.sympla.com.br/evento/..."
						maxlength="500"
					/>
					<span class="popup-step-hint">Opcional — link da página de venda oficial do evento</span>
				</div>

				<!-- Complete state -->
				<div class="popup-step popup-step-complete" data-step="complete">
					<div class="popup-complete-icon">
						<i class="ri-checkbox-circle-fill"></i>
					</div>
					<h4>Solicitação Enviada!</h4>
					<p>Analisaremos e cadastraremos o evento em breve. Você será notificado.</p>
				</div>
			</div>

			<!-- Navigation -->
			<div class="popup-nav" id="solicitar-nav">
				<button type="button" class="popup-btn popup-btn-prev" id="solicitar-prev" disabled>
					<i class="ri-arrow-left-s-line"></i> Voltar
				</button>
				<button type="button" class="popup-btn popup-btn-next" id="solicitar-next">
					Próximo <i class="ri-arrow-right-s-line"></i>
				</button>
				<button type="button" class="popup-btn popup-btn-submit" id="solicitar-submit" style="display:none;">
					<i class="ri-send-plane-fill"></i> Enviar Solicitação
				</button>
			</div>
		</div>
	</div>

	<style>
	/* ═══ Event Selector ═══ */
	.apollo-field-event-search { transition: all .3s ease; }

	.event-search-container { position: relative; }

	.event-selected-preview {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 8px;
		padding: 10px 14px;
		background: rgba(244, 95, 0, 0.08);
		border: 1px solid rgba(244, 95, 0, 0.3);
		border-radius: 10px;
		font: 500 0.9rem/1.4 'Space Grotesk', sans-serif;
		color: FF9820;
	}
	.event-selected-preview i { font-size: 1.1rem; }
	.event-clear-btn {
		margin-left: auto;
		background: none;
		border: none;
		cursor: pointer;
		color: #888;
		font-size: 1.2rem;
		padding: 0;
		transition: color .2s;
	}
	.event-clear-btn:hover { color: FF9820; }

	.event-not-found-link {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 8px;
		font: 400 0.82rem/1.4 'Space Grotesk', sans-serif;
		color: #888;
	}
	.solicitar-evento-btn {
		background: none;
		border: 1px dashed rgba(101, 31, 255, 0.4);
		border-radius: 8px;
		padding: 4px 12px;
		cursor: pointer;
		color: #651FFF;
		font: 500 0.82rem/1.4 'Space Grotesk', sans-serif;
		display: flex;
		align-items: center;
		gap: 4px;
		transition: all .2s ease;
	}
	.solicitar-evento-btn:hover {
		background: rgba(101, 31, 255, 0.08);
		border-color: #651FFF;
	}

	/* ═══ Solicitar Evento Popup ═══ */
	.solicitar-evento-overlay {
		position: fixed;
		inset: 0;
		z-index: 99999;
		display: none;
		align-items: center;
		justify-content: center;
		background: rgba(var(--rgb-d), 0.65);
		backdrop-filter: blur(6px);
		-webkit-backdrop-filter: blur(6px);
		opacity: 0;
		transition: opacity .3s ease;
	}
	.solicitar-evento-overlay.is-open {
		display: flex;
		opacity: 1;
	}

	.solicitar-evento-popup {
		position: relative;
		width: 94%;
		max-width: 480px;
		background: #1a1a1a;
		border: 1px solid rgba(var(--rgb-t), 0.08);
		border-radius: 20px;
		padding: 32px 28px 24px;
		box-shadow: 0 24px 80px rgba(var(--rgb-d), 0.5);
		animation: popIn .35s cubic-bezier(.175, .885, .32, 1.275) forwards;
	}
	@keyframes popIn {
		0% { transform: scale(0.85) translateY(20px); opacity: 0; }
		100% { transform: scale(1) translateY(0); opacity: 1; }
	}

	.popup-close {
		position: absolute;
		top: 16px;
		right: 16px;
		background: rgba(var(--rgb-t), 0.06);
		border: none;
		border-radius: 50%;
		width: 36px;
		height: 36px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		color: #aaa;
		font-size: 1.2rem;
		transition: all .2s;
	}
	.popup-close:hover { background: rgba(var(--rgb-t), 0.12); color: #fff; }

	.popup-header {
		text-align: center;
		margin-bottom: 20px;
	}
	.popup-header h3 {
		font: 700 1.25rem/1.3 'Space Grotesk', sans-serif;
		color: #fff;
		margin: 0 0 6px;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
	}
	.popup-header h3 i { color: FF9820; }
	.popup-subtitle {
		font: 400 0.82rem/1.4 'Space Grotesk', sans-serif;
		color: #888;
		margin: 0;
	}

	/* Progress bar */
	.popup-progress {
		width: 100%;
		height: 3px;
		background: rgba(var(--rgb-t), 0.06);
		border-radius: 3px;
		margin-bottom: 6px;
		overflow: hidden;
	}
	.popup-progress-bar {
		height: 100%;
		background: linear-gradient(90deg, FF9820, #651FFF);
		border-radius: 3px;
		transition: width .4s ease;
	}
	.popup-step-indicator {
		text-align: center;
		font: 500 0.75rem/1 'Space Mono', monospace;
		color: #555;
		margin-bottom: 20px;
	}

	/* Steps */
	.popup-steps-container {
		position: relative;
		min-height: 100px;
		overflow: hidden;
	}
	.popup-step {
		display: none;
		animation: stepFadeIn .3s ease forwards;
	}
	.popup-step.active { display: block; }
	@keyframes stepFadeIn {
		0% { opacity: 0; transform: translateX(30px); }
		100% { opacity: 1; transform: translateX(0); }
	}

	.popup-step-label {
		display: block;
		font: 600 0.88rem/1.4 'Space Grotesk', sans-serif;
		color: #ccc;
		margin-bottom: 10px;
	}
	.popup-step-input {
		width: 100%;
		padding: 12px 16px;
		background: rgba(var(--rgb-t), 0.04);
		border: 1px solid rgba(var(--rgb-t), 0.1);
		border-radius: 10px;
		color: #fff;
		font: 400 0.95rem/1.5 'Space Grotesk', sans-serif;
		outline: none;
		transition: border-color .2s;
		box-sizing: border-box;
	}
	.popup-step-input:focus {
		border-color: FF9820;
	}
	.popup-step-input::placeholder { color: #555; }

	.popup-step-hint {
		display: block;
		margin-top: 6px;
		font: 400 0.75rem/1.3 'Space Grotesk', sans-serif;
		color: #555;
	}

	/* Date group */
	.popup-date-group {
		display: flex;
		align-items: center;
		gap: 4px;
	}
	.popup-date-field {
		width: 64px;
		padding: 12px 8px;
		background: rgba(var(--rgb-t), 0.04);
		border: 1px solid rgba(var(--rgb-t), 0.1);
		border-radius: 10px;
		color: #fff;
		font: 400 1.1rem/1.5 'Space Mono', monospace;
		text-align: center;
		outline: none;
		transition: border-color .2s;
	}
	.popup-date-field:focus { border-color: FF9820; }
	.popup-date-field.popup-date-year { width: 76px; }
	.popup-date-sep {
		color: #555;
		font: 500 1.1rem/1 'Space Mono', monospace;
	}

	/* Complete state */
	.popup-step-complete { text-align: center; padding: 20px 0; }
	.popup-complete-icon {
		font-size: 3rem;
		color: #22c55e;
		margin-bottom: 12px;
	}
	.popup-step-complete h4 {
		font: 700 1.15rem/1.3 'Space Grotesk', sans-serif;
		color: #fff;
		margin: 0 0 8px;
	}
	.popup-step-complete p {
		font: 400 0.85rem/1.5 'Space Grotesk', sans-serif;
		color: #888;
		margin: 0;
	}

	/* Navigation */
	.popup-nav {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 24px;
		gap: 12px;
	}
	.popup-btn {
		padding: 10px 20px;
		border: none;
		border-radius: 10px;
		font: 600 0.88rem/1.4 'Space Grotesk', sans-serif;
		cursor: pointer;
		display: flex;
		align-items: center;
		gap: 6px;
		transition: all .2s ease;
	}
	.popup-btn:disabled {
		opacity: 0.3;
		cursor: not-allowed;
	}
	.popup-btn-prev {
		background: rgba(var(--rgb-t), 0.06);
		color: #aaa;
	}
	.popup-btn-prev:hover:not(:disabled) {
		background: rgba(var(--rgb-t), 0.1);
		color: #fff;
	}
	.popup-btn-next {
		background: FF9820;
		color: #fff;
		margin-left: auto;
	}
	.popup-btn-next:hover { background: #d95400; }
	.popup-btn-submit {
		background: linear-gradient(135deg, #651FFF, FF9820);
		color: #fff;
		margin-left: auto;
	}
	.popup-btn-submit:hover { opacity: 0.9; }

	/* ═══ Responsive ═══ */
	@media (max-width: 600px) {
		.solicitar-evento-popup {
			width: 100%;
			max-width: 100%;
			border-radius: 20px 20px 0 0;
			margin-top: auto;
			padding: 24px 20px 20px;
		}
		.popup-date-field { width: 54px; padding: 10px 6px; }
	}
	</style>

	<script>
	(function() {
		'use strict';

		// ── Domain visibility toggle ──────────────────────────────
		const wrap = document.getElementById('event-selector-wrap');
		if (!wrap) return;

		const ticketDomains = JSON.parse(wrap.dataset.ticketDomains || '[]');
		const domainSelect = document.querySelector('[name="<?php echo esc_js( APOLLO_TAX_CLASSIFIED_DOMAIN ); ?>"]');
		const hiddenInput = document.getElementById('_classified_event_id');
		const searchInput = document.getElementById('_classified_event_id_search');
		const preview = document.getElementById('event-selected-preview');
		const previewName = document.getElementById('event-selected-name');
		const clearBtn = document.getElementById('event-clear-btn');

		function isTicketDomain() {
			if (!domainSelect) return false;
			return ticketDomains.includes(domainSelect.value);
		}

		function toggleEventField() {
			const show = isTicketDomain();
			wrap.style.display = show ? '' : 'none';
			if (searchInput) {
				searchInput.required = show;
			}
			if (!show && hiddenInput) {
				hiddenInput.value = '';
				if (searchInput) searchInput.value = '';
				if (preview) preview.style.display = 'none';
			}
		}

		if (domainSelect) {
			domainSelect.addEventListener('change', toggleEventField);
			// Initial check
			toggleEventField();
		}

		// ── ApolloSearch select listener ──────────────────────────
		if (searchInput) {
			searchInput.addEventListener('apollo:search:select', function(e) {
				const { id, title } = e.detail;
				if (hiddenInput) hiddenInput.value = id;
				if (previewName) previewName.textContent = title;
				if (preview) preview.style.display = '';
			});

			searchInput.addEventListener('apollo:search:clear', function() {
				if (hiddenInput) hiddenInput.value = '';
				if (preview) preview.style.display = 'none';
			});
		}

		// ── Clear selection ────────────────────────────────────────
		if (clearBtn) {
			clearBtn.addEventListener('click', function() {
				if (hiddenInput) hiddenInput.value = '';
				if (searchInput) {
					searchInput.value = '';
					searchInput.focus();
				}
				if (preview) preview.style.display = 'none';
			});
		}

		// ── Form validation — require event for ticket domains ──
		const form = wrap.closest('form');
		if (form) {
			form.addEventListener('submit', function(e) {
				if (isTicketDomain() && hiddenInput && !hiddenInput.value) {
					e.preventDefault();
					wrap.classList.add('has-error');
					let errSpan = wrap.querySelector('.apollo-field-error');
					if (!errSpan) {
						errSpan = document.createElement('span');
						errSpan.className = 'apollo-field-error';
						wrap.appendChild(errSpan);
					}
					errSpan.textContent = 'Selecione o evento vinculado ao ingresso.';
					searchInput?.focus();
					return false;
				}
			});
		}

		// ══════════════════════════════════════════════════════════
		// SOLICITAR EVENTO — Multi-step popup controller
		// ══════════════════════════════════════════════════════════
		const overlay    = document.getElementById('solicitar-evento-overlay');
		const openBtn    = document.getElementById('solicitar-evento-open');
		const closeBtn   = document.getElementById('solicitar-evento-close');
		const prevBtn    = document.getElementById('solicitar-prev');
		const nextBtn    = document.getElementById('solicitar-next');
		const submitBtn  = document.getElementById('solicitar-submit');
		const progress   = document.getElementById('solicitar-progress');
		const stepLabel  = document.getElementById('solicitar-step-current');
		const stepsEl    = document.getElementById('solicitar-steps');
		const navEl      = document.getElementById('solicitar-nav');

		if (!overlay || !stepsEl) return;

		const steps = stepsEl.querySelectorAll('.popup-step:not(.popup-step-complete)');
		const totalSteps = steps.length;
		let current = 0;

		function showStep(idx) {
			steps.forEach((s, i) => {
				s.classList.toggle('active', i === idx);
			});
			stepsEl.querySelector('.popup-step-complete')?.classList.remove('active');
			current = idx;

			// Update progress
			if (progress) progress.style.width = ((idx + 1) / totalSteps * 100) + '%';
			if (stepLabel) stepLabel.textContent = idx + 1;

			// Nav buttons
			if (prevBtn) prevBtn.disabled = idx === 0;
			if (nextBtn) nextBtn.style.display = idx < totalSteps - 1 ? '' : 'none';
			if (submitBtn) submitBtn.style.display = idx === totalSteps - 1 ? '' : 'none';

			// Auto-focus first input
			const inp = steps[idx]?.querySelector('input');
			if (inp) setTimeout(() => inp.focus(), 100);
		}

		function showComplete() {
			steps.forEach(s => s.classList.remove('active'));
			const complete = stepsEl.querySelector('.popup-step-complete');
			if (complete) complete.classList.add('active');
			if (progress) progress.style.width = '100%';
			if (stepLabel) stepLabel.textContent = '✓';
			if (navEl) navEl.style.display = 'none';
		}

		// Open
		openBtn?.addEventListener('click', function() {
			overlay.classList.add('is-open');
			showStep(0);
			if (navEl) navEl.style.display = '';
			document.body.style.overflow = 'hidden';
		});

		// Close
		function closePopup() {
			overlay.classList.remove('is-open');
			document.body.style.overflow = '';
		}
		closeBtn?.addEventListener('click', closePopup);
		overlay.addEventListener('click', function(e) {
			if (e.target === overlay) closePopup();
		});
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
				closePopup();
			}
		});

		// Prev / Next
		prevBtn?.addEventListener('click', function() {
			if (current > 0) showStep(current - 1);
		});
		nextBtn?.addEventListener('click', function() {
			if (current < totalSteps - 1) showStep(current + 1);
		});

		// Submit
		submitBtn?.addEventListener('click', function() {
			const data = {
				nome:    document.getElementById('solicitar_nome')?.value.trim()    || '',
				espaco:  document.getElementById('solicitar_espaco')?.value.trim()  || '',
				dia:     document.getElementById('solicitar_dia')?.value.trim()     || '',
				mes:     document.getElementById('solicitar_mes')?.value.trim()     || '',
				ano:     document.getElementById('solicitar_ano')?.value.trim()     || '',
				djs:     document.getElementById('solicitar_djs')?.value.trim()     || '',
				generos: document.getElementById('solicitar_generos')?.value.trim() || '',
				link:    document.getElementById('solicitar_link')?.value.trim()    || '',
			};

			if (!data.nome) {
				showStep(0);
				return;
			}

			submitBtn.disabled = true;
			submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Enviando...';

			const restUrl = (window.apolloSearch && window.apolloSearch.restUrl) || '/wp-json/apollo/v1/';
			const nonce   = (window.apolloSearch && window.apolloSearch.nonce) || '';

			fetch(restUrl + 'events/request', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce
				},
				body: JSON.stringify(data)
			})
			.then(r => r.json())
			.then(res => {
				showComplete();
				// Auto-close after 4s
				setTimeout(closePopup, 4000);
			})
			.catch(() => {
				// Still show complete (the request was best-effort)
				showComplete();
				setTimeout(closePopup, 4000);
			})
			.finally(() => {
				submitBtn.disabled = false;
				submitBtn.innerHTML = '<i class="ri-send-plane-fill"></i> Enviar Solicitação';
			});
		});

		// Auto-advance date fields on 2-char input
		const dia = document.getElementById('solicitar_dia');
		const mes = document.getElementById('solicitar_mes');
		const ano = document.getElementById('solicitar_ano');

		if (dia && mes && ano) {
			dia.addEventListener('input', function() {
				this.value = this.value.replace(/\D/g, '');
				if (this.value.length >= 2) mes.focus();
			});
			mes.addEventListener('input', function() {
				this.value = this.value.replace(/\D/g, '');
				if (this.value.length >= 2) ano.focus();
			});
			ano.addEventListener('input', function() {
				this.value = this.value.replace(/\D/g, '');
			});
		}
	})();
	</script>
	<?php
}

/**
 * Save _classified_event_id meta when classified is saved.
 */
function apollo_adverts_save_event_meta( int $post_id, array $values ): void {
	if ( isset( $_POST['_classified_event_id'] ) ) {
		$event_id = absint( $_POST['_classified_event_id'] );
		if ( $event_id && get_post_type( $event_id ) === 'event' ) {
			update_post_meta( $post_id, '_classified_event_id', $event_id );
		} else {
			delete_post_meta( $post_id, '_classified_event_id' );
		}
	}
}
add_action( 'apollo/classifieds/created', 'apollo_adverts_save_event_meta', 10, 2 );
add_action( 'apollo/classifieds/updated', 'apollo_adverts_save_event_meta', 10, 2 );

/**
 * REST endpoint: POST /apollo/v1/events/request
 * Stores an event request as a custom option (admin reviews it).
 */
function apollo_adverts_register_event_request_endpoint(): void {
	register_rest_route(
		'apollo/v1',
		'/events/request',
		array(
			'methods'             => 'POST',
			'callback'            => 'apollo_adverts_handle_event_request',
			'permission_callback' => 'is_user_logged_in',
		)
	);
}
add_action( 'rest_api_init', 'apollo_adverts_register_event_request_endpoint' );

/**
 * Handle event request submission.
 */
function apollo_adverts_handle_event_request( WP_REST_Request $request ): WP_REST_Response {
	$user_id = get_current_user_id();
	$data    = array(
		'nome'       => sanitize_text_field( $request->get_param( 'nome' ) ),
		'espaco'     => sanitize_text_field( $request->get_param( 'espaco' ) ),
		'dia'        => sanitize_text_field( $request->get_param( 'dia' ) ),
		'mes'        => sanitize_text_field( $request->get_param( 'mes' ) ),
		'ano'        => sanitize_text_field( $request->get_param( 'ano' ) ),
		'djs'        => sanitize_text_field( $request->get_param( 'djs' ) ),
		'generos'    => sanitize_text_field( $request->get_param( 'generos' ) ),
		'link'       => esc_url_raw( $request->get_param( 'link' ) ),
		'user_id'    => $user_id,
		'user_login' => wp_get_current_user()->user_login,
		'created_at' => current_time( 'mysql' ),
	);

	if ( empty( $data['nome'] ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => 'Nome é obrigatório.',
			),
			400
		);
	}

	// Store as option (array of requests)
	$requests   = get_option( 'apollo_event_requests', array() );
	$requests[] = $data;
	update_option( 'apollo_event_requests', $requests );

	// Fire hook for notifications
	do_action( 'apollo/events/request_created', $data, $user_id );

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => 'Solicitação enviada.',
		),
		201
	);
}