<?php

/**
 * Apollo Report Modal — Shared Component
 *
 * Reusable support/contact modal for ALL Apollo plugins (admin + frontend).
 * Dual submission: REST API (email to oi@apollo.rio.br) + Google Sheets fallback.
 *
 * Usage: apollo_render_report_modal() or include this file directly.
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Apollo Report/Contact modal.
 *
 * @param string $trigger_id   HTML id for the trigger element (default: 'apolloReportTrigger')
 * @param string $context      Context label: 'admin', 'frontend', 'post-report' (adjusts subject options)
 * @param array  $extra_data   Extra data to pass to the form (e.g., post_id for report)
 */
function apollo_render_report_modal( string $trigger_id = 'apolloReportTrigger', string $context = 'frontend', array $extra_data = array() ): void {
	// Google Forms endpoint for sheet backup
	$gf_action  = 'https://docs.google.com/forms/u/0/d/e/1FAIpQLSflexLf5HLHVdCUCUex1LXxKOhQROMGpfDPx7B85tLvohiczA/formResponse';
	$gf_name    = 'entry.294491473';
	$gf_email   = 'entry.317870723';
	$gf_subject = 'entry.195576492';
	$gf_message = 'entry.1724857942';
	$rest_url   = esc_url_raw( rest_url( 'apollo/v1/report' ) );
	$rest_nonce = wp_create_nonce( 'wp_rest' );
	$extra_json = ! empty( $extra_data ) ? wp_json_encode( $extra_data ) : '{}';

	// Subject options based on context
	$is_report = ( $context === 'post-report' );
	?>

		<!-- ═══════════════════════════════════════════════════════════════════ -->
		<!-- APOLLO REPORT MODAL — SHARED COMPONENT (STRICT COPY)               -->
		<!-- ALL PROPERTIES FORCED WITH !important as requested                 -->
		<!-- ═══════════════════════════════════════════════════════════════════ -->
		<style>
			:root {

			/* Extras dyue injected ROOT stylesheet via CDN APOLLO */
			--radius: 0px !important; /* Sharp corners for ultra-modern look */
			--space-4: 24px !important;
	   
			/* Animation */
			--ease-lux: cubic-bezier(0.16, 1, 0.3, 1) !important;
			--z-modal: 9999 !important;
		}

  
		/* ========================================= */
		/* 3. THE LUXURY FORM MODAL                  */
		/* ========================================= */
	
		/* Overlay Backdrop */
		.form-overlay {
			position: fixed !important;
			top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important;
			background: rgba(var(--rgb-d),0.4) !important;
			backdrop-filter: blur(15px) !important; /* Heavy Luxury Blur */
			-webkit-backdrop-filter: blur(15px) !important;
			opacity: 0 !important;
			visibility: hidden !important;
			transition: all 0.6s var(--ease-lux) !important;
			z-index: var(--z-modal) !important;
		}

		.apollo-open .form-overlay {
			opacity: 1 !important;
			visibility: visible !important;
		}

		/* The Modal Itself */
		.apollo-modal {
			position: fixed !important;
			bottom: 0 !important;
			right: 0 !important;
			width: 100% !important;
			max-width: 480px !important;
			height: 100vh !important;
			height: 100dvh !important;
			background: var(--white-1) !important;
			border-left: 1px solid var(--border) !important;
			padding: 60px 40px !important;
			box-sizing: border-box !important;
	   
			transform: translateX(100%) !important;
			transition: transform 0.6s var(--ease-lux) !important;
			z-index: calc(var(--z-modal) + 1) !important;
			display: flex !important;
			flex-direction: column !important;
		}

		.apollo-open .apollo-modal {
			transform: translateX(0) !important;
		}

		/* Close Button */
		.modal-close {
			position: absolute !important;
			top: 30px !important;
			right: 30px !important;
			z-index: 5 !important;
			background: none !important;
			border: none !important;
			color: var(--text-muted) !important;
			font-size: 24px !important;
			line-height: 1 !important;
			cursor: pointer !important;
			transition: color 0.3s !important;
			padding: 8px !important;
		}
		.modal-close:hover { color: var(--primary) !important; }

		.apollo-modal {
			overflow-y: auto !important;
		}

		/* Header (scoped — no global leak) */
		.apollo-modal .modal-header h2 {
			font-family: var(--ff-mono) !important;
			font-size: 12px !important;
			color: var(--primary) !important;
			letter-spacing: 0.2em !important;
			text-transform: uppercase !important;
			margin-bottom: 10px !important;
		}
		.apollo-modal .modal-header h1 {
			font-size: 32px !important;
			font-weight: 300 !important;
			color: var(--black-1) !important;
			margin: 0 0 40px 0 !important;
			line-height: 1.1 !important;
		}

		/* Form fields — scoped to modal shell (auth pages override via apollo-auth-uni.css) */
		.apollo-modal .input-group {
			position: relative !important;
			margin-bottom: 35px !important;
		}
		.apollo-modal .apollo-input {
			width: 100% !important;
			background: transparent !important;
			border: none !important;
			border-bottom: 1px solid var(--border) !important;
			padding: 12px 0 !important;
			font-family: var(--ff-main) !important;
			font-size: 16px !important;
			color: var(--black-1) !important;
			border-radius: 0 !important;
			outline: none !important;
			transition: border-color 0.4s var(--ease-lux), box-shadow 0.4s var(--ease-lux) !important;
		}
		.apollo-modal .apollo-input:focus {
			border-bottom-color: var(--primary) !important;
			box-shadow: 0 0 0 1px rgba(244, 95, 0, 0.25), 0 0 20px rgba(244, 95, 0, 0.15) !important;
		}
		.apollo-modal .apollo-label {
			position: absolute !important;
			top: 12px !important;
			left: 0 !important;
			font-family: var(--ff-mono) !important;
			font-size: 12px !important;
			color: var(--text-muted) !important;
			pointer-events: none !important;
			transition: all 0.4s var(--ease-lux) !important;
			text-transform: uppercase !important;
		}
		.apollo-modal .apollo-input:focus ~ .apollo-label,
		.apollo-modal .apollo-input:not(:placeholder-shown) ~ .apollo-label {
			top: -10px !important;
			font-size: 10px !important;
			color: var(--primary) !important;
		}
		.apollo-modal select.apollo-input {
			appearance: none !important;
			-webkit-appearance: none !important;
			cursor: pointer !important;
			border-radius: 0 !important;
			color: var(--black-1) !important;
		}
		.apollo-modal select.apollo-input:invalid { color: transparent !important; }
		.apollo-modal select.apollo-input:invalid ~ .apollo-label {
			top: 12px !important;
			font-size: 12px !important;
			color: var(--text-muted) !important;
		}
		.apollo-modal select.apollo-input:valid ~ .apollo-label {
			top: -10px !important;
			font-size: 10px !important;
			color: var(--primary) !important;
		}
		.apollo-modal select.apollo-input:valid { color: var(--black-1) !important; }
		.apollo-modal select.apollo-input option {
			background-color: var(--white-1) !important;
			color: var(--black-1) !important;
			font-family: var(--ff-main) !important;
			font-size: 80% !important;
			padding: 15px !important;
		}
		.apollo-modal .select-arrow {
			position: absolute !important;
			right: 0 !important;
			bottom: 15px !important;
			pointer-events: none !important;
			color: var(--text-muted) !important;
			font-size: 14px !important;
		}
		.apollo-modal .submit-btn {
			margin-top: auto !important;
			background: transparent !important;
			border: 1px solid var(--border) !important;
			color: var(--black-1) !important;
			padding: 20px !important;
			font-family: var(--ff-mono) !important;
			font-size: 12px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.1em !important;
			cursor: pointer !important;
			display: flex !important;
			justify-content: space-between !important;
			align-items: center !important;
			transition: all 0.3s !important;
		}
		.apollo-modal .submit-btn:hover {
			background: var(--black-1) !important;
			color: var(--white-1) !important;
			border-color: var(--black-1) !important;
		}

		/* Success state (scoped) */
		.apollo-modal .success-view {
			position: absolute !important;
			top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important;
			background: var(--white-1) !important;
			display: flex !important;
			flex-direction: column !important;
			justify-content: center !important;
			align-items: center !important;
			text-align: center !important;
			padding: 40px !important;
			box-sizing: border-box !important;
			opacity: 0 !important;
			pointer-events: none !important;
			transition: opacity 0.5s !important;
		}

		.apollo-modal.form-submitted .success-view {
			opacity: 1 !important;
			pointer-events: all !important;
		}

		.apollo-modal .success-icon {
			font-size: 4rem !important;
			color: var(--primary) !important;
			margin-bottom: 20px !important;
		}
		.apollo-modal .success-view h3 {
			font-size: 24px !important;
			margin: 0 !important;
			font-weight: 400 !important;
		}
		.apollo-modal .success-view p {
			color: var(--text-muted) !important;
			font-family: var(--ff-mono) !important;
			font-size: 12px !important;
			margin-top: 10px !important;
		}

	</style>

	<!-- GUARANTEED TRIGGER: Invisible fallback if page doesn't provide one -->
	<span id="apolloReportTrigger" style="display:none !important; visibility:hidden !important; position:absolute !important; pointer-events:none !important;"></span>

	<div class="form-overlay" id="apolloOverlay"></div>
	<div class="apollo-modal" id="apolloModal" role="dialog" aria-modal="true" aria-labelledby="apolloModalTitle" aria-hidden="true">
		<button class="modal-close" id="apolloClose" type="button" data-apollo-report-close aria-label="<?php esc_attr_e( 'Fechar', 'apollo-core' ); ?>"><i class="ri-close-fill" aria-hidden="true"></i></button>
		<div class="modal-header">
			<?php if ( $is_report ) : ?>
				<h2><?php esc_html_e( 'Denúncia', 'apollo-core' ); ?></h2>
				<h1><?php esc_html_e( 'Reportar', 'apollo-core' ); ?><br><?php esc_html_e( 'conteúdo', 'apollo-core' ); ?></h1>
			<?php else : ?>
				<h2><?php esc_html_e( 'Contato', 'apollo-core' ); ?></h2>
				<h1 id="apolloModalTitle"><?php esc_html_e( 'Como podemos', 'apollo-core' ); ?><br><?php esc_html_e( 'ajudar você?', 'apollo-core' ); ?></h1>
			<?php endif; ?>
		</div>
		<div class="success-view">
			<i class="ri-check-line success-icon"></i>
			<h3><?php esc_html_e( 'Mensagem Enviada', 'apollo-core' ); ?></h3>
			<p><?php esc_html_e( 'Nossa equipe entrará em contato em breve.', 'apollo-core' ); ?></p>
		</div>
		<form id="apolloForm" autocomplete="off">
			<div class="input-group">
				<input type="text" class="apollo-input" name="name" placeholder=" " required>
				<label class="apollo-label"><?php esc_html_e( 'Nome Completo', 'apollo-core' ); ?></label>
			</div>
			<div class="input-group">
				<input type="email" class="apollo-input" name="email" placeholder=" " required>
				<label class="apollo-label"><?php esc_html_e( 'E-mail', 'apollo-core' ); ?></label>
			</div>
			<div class="input-group">
				<select class="apollo-input" name="subject" required>
					<option value="" disabled selected></option>
					<?php if ( $is_report ) : ?>
						<option value="5"><?php esc_html_e( 'Conteúdo impróprio', 'apollo-core' ); ?></option>
						<option value="6"><?php esc_html_e( 'Spam ou fraude', 'apollo-core' ); ?></option>
						<option value="7"><?php esc_html_e( 'Perfil falso', 'apollo-core' ); ?></option>
						<option value="2"><?php esc_html_e( 'Outro problema', 'apollo-core' ); ?></option>
					<?php else : ?>
						<option value="1"><?php esc_html_e( 'Sobre Parceria', 'apollo-core' ); ?></option>
						<option value="2"><?php esc_html_e( 'Problema ou Denúncia', 'apollo-core' ); ?></option>
						<option value="3"><?php esc_html_e( 'Suporte Técnico', 'apollo-core' ); ?></option>
						<option value="4"><?php esc_html_e( 'Elogio ou Crítica', 'apollo-core' ); ?></option>
					<?php endif; ?>
				</select>
				<label class="apollo-label"><?php esc_html_e( 'Assunto', 'apollo-core' ); ?></label>
				<i class="ri-dropdown-list select-arrow"></i>
			</div>
			<div class="input-group">
				<input type="text" class="apollo-input" name="message" placeholder=" " required>
				<label class="apollo-label"><?php esc_html_e( 'Mensagem', 'apollo-core' ); ?></label>
			</div>
			<div class="error-msg" id="apolloError"></div>
			<button type="submit" class="submit-btn">
				<span><?php esc_html_e( 'Enviar Agora', 'apollo-core' ); ?></span>
				<i class="ri-mail-send-fill"></i>
			</button>
		</form>
	</div>

	<script>
		// Vanilla JS — auth blank-canvas pages do not load jQuery; close/overlay must work without it.
		(function() {
			'use strict';

			var GFORM_ACTION = <?php echo wp_json_encode( $gf_action ); ?>;
			var GF_NAME = <?php echo wp_json_encode( $gf_name ); ?>;
			var GF_EMAIL = <?php echo wp_json_encode( $gf_email ); ?>;
			var GF_SUBJECT = <?php echo wp_json_encode( $gf_subject ); ?>;
			var GF_MESSAGE = <?php echo wp_json_encode( $gf_message ); ?>;
			var REST_URL = <?php echo wp_json_encode( $rest_url ); ?>;
			var REST_NONCE = <?php echo wp_json_encode( $rest_nonce ); ?>;
			var EXTRA_DATA = <?php echo $extra_json; ?>;

			var subjectMap = {
				'1': 'Sobre Parceria',
				'2': 'Problema ou Denúncia',
				'3': 'Suporte Técnico',
				'4': 'Elogio ou Crítica',
				'5': 'Conteúdo impróprio',
				'6': 'Spam ou fraude',
				'7': 'Perfil falso'
			};

			function getEl(id) {
				return document.getElementById(id);
			}

			function openModal() {
				document.body.classList.add('apollo-open');
				var modal = getEl('apolloModal');
				if (modal) {
					modal.setAttribute('aria-hidden', 'false');
				}
			}

			function closeModal() {
				document.body.classList.remove('apollo-open');
				var modal = getEl('apolloModal');
				var form = getEl('apolloForm');
				var errDiv = getEl('apolloError');
				if (modal) {
					modal.setAttribute('aria-hidden', 'true');
				}
				if (errDiv) {
					errDiv.style.display = 'none';
					errDiv.textContent = '';
				}
				setTimeout(function() {
					if (modal) {
						modal.classList.remove('form-submitted');
					}
					if (form) {
						form.reset();
					}
				}, 600);
			}

			function bindModal() {
				var overlay = getEl('apolloOverlay');
				var closeBtn = getEl('apolloClose');
				var form = getEl('apolloForm');
				var modal = getEl('apolloModal');

				if (closeBtn) {
					closeBtn.addEventListener('click', function(e) {
						e.preventDefault();
						closeModal();
					});
				}

				if (overlay) {
					overlay.addEventListener('click', closeModal);
				}

				document.addEventListener('keydown', function(e) {
					if (e.key === 'Escape' && document.body.classList.contains('apollo-open')) {
						closeModal();
					}
				});

				document.addEventListener('click', function(e) {
					var trigger = e.target.closest('#apolloReportTrigger, [data-apollo-report-trigger]');
					if (trigger) {
						e.preventDefault();
						openModal();
					}
					var closer = e.target.closest('[data-apollo-report-close], .modal-close');
					if (closer) {
						e.preventDefault();
						closeModal();
					}
				});

				if (!form || !modal) {
					return;
				}

				form.addEventListener('submit', function(e) {
					e.preventDefault();
					var errDiv = getEl('apolloError');
					if (errDiv) {
						errDiv.style.display = 'none';
						errDiv.textContent = '';
					}

					var nameEl = form.querySelector('[name="name"]');
					var emailEl = form.querySelector('[name="email"]');
					var subjectEl = form.querySelector('[name="subject"]');
					var messageEl = form.querySelector('[name="message"]');
					var name = nameEl ? nameEl.value : '';
					var email = emailEl ? emailEl.value : '';
					var subject = subjectEl ? subjectEl.value : '';
					var message = messageEl ? messageEl.value : '';
					var subjectLabel = subjectMap[subject] || subject;

					var gfData = new FormData();
					gfData.append(GF_NAME, name);
					gfData.append(GF_EMAIL, email);
					gfData.append(GF_SUBJECT, subjectLabel);
					gfData.append(GF_MESSAGE, message + (EXTRA_DATA.post_id ? ' [Post #' + EXTRA_DATA.post_id + ']' : ''));

					fetch(GFORM_ACTION, { method: 'POST', body: gfData, mode: 'no-cors' }).catch(function() {});

					fetch(REST_URL, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': REST_NONCE
						},
						body: JSON.stringify({
							name: name,
							email: email,
							subject: subject,
							message: message,
							post_id: EXTRA_DATA.post_id || null
						})
					}).then(function() {
						modal.classList.add('form-submitted');
						setTimeout(closeModal, 2500);
					}).catch(function() {
						modal.classList.add('form-submitted');
						setTimeout(closeModal, 2500);
					});
				});
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', bindModal);
			} else {
				bindModal();
			}
		})();
	</script>
	<?php
}
/**
 * Render an inline "Reportar" button for any post.
 *
 * Outputs a small button + the report modal (post-report context).
 * Call once per page — the modal is shared.
 *
 * @param int    $post_id  The post to report.
 * @param string $style    'inline' (text link) or 'button' (styled button).
 */
function apollo_render_report_button( int $post_id = 0, string $style = 'inline' ): void {
	if ( $post_id <= 0 ) {
		$post_id = get_the_ID();
	}

	static $modal_rendered = false;

	$btn_id = 'apolloReportPost-' . $post_id;

	if ( $style === 'button' ) {
		?>
		<button type="button" id="<?php echo esc_attr( $btn_id ); ?>" class="apollo-report-btn" data-apollo-report-trigger title="<?php esc_attr_e( 'Reportar conteúdo', 'apollo-core' ); ?>">
			<i class="ri-alert-fill"></i> <span><?php esc_html_e( 'Reportar', 'apollo-core' ); ?></span>
		</button>
		<style>
			.apollo-report-btn {
				display: inline-flex;
				align-items: center;
				gap: 6px;
				background: transparent;
				border: 1px solid var(--border, #e0e0e0);
				color: var(--text-muted, #999);
				padding: 8px 16px;
				font-family: var(--ff-mono, monospace);
				font-size: 11px;
				text-transform: uppercase;
				letter-spacing: 0.05em;
				cursor: pointer;
				border-radius: 0;
				transition: all 0.3s;
			}

			.apollo-report-btn:hover {
				color: #b71c1c;
				border-color: #b71c1c;
			}

			.apollo-report-btn i {
				font-size: 14px;
			}
		</style>
		<?php
	} else {
		?>
		<a href="#" id="<?php echo esc_attr( $btn_id ); ?>" class="apollo-report-link" data-apollo-report-trigger title="<?php esc_attr_e( 'Reportar conteúdo', 'apollo-core' ); ?>">
			<i class="ri-alert-fill"></i> <?php esc_html_e( 'Reportar', 'apollo-core' ); ?>
		</a>
		<style>
			.apollo-report-link {
				display: inline-flex;
				align-items: center;
				gap: 4px;
				color: var(--text-muted, #999);
				font-size: 12px;
				text-decoration: none;
				font-family: var(--ff-mono, monospace);
				cursor: pointer;
				transition: color 0.3s;
			}

			.apollo-report-link:hover {
				color: #b71c1c;
			}
		</style>
		<?php
	}

	// Render modal once per page
	if ( ! $modal_rendered ) {
		apollo_render_report_modal( $btn_id, 'post-report', array( 'post_id' => $post_id ) );
		$modal_rendered = true;
	}
}

/**
 * Hook into CPT single templates to add "Reportar" button automatically.
 *
 * Events: uses apollo_event_single_after_content hook.
 * Other CPTs: uses the_content filter as fallback.
 */
function apollo_report_button_hooks(): void {
	// ── Events (hook fired by apollo-events single templates) ──
	add_action(
		'apollo_event_single_after_content',
		function ( $post_id ) {
			if ( ! file_exists( __FILE__ ) ) {
				return;
			}
			echo '<div style="margin: 20px 0; text-align: right;">';
			apollo_render_report_button( (int) $post_id, 'inline' );
			echo '</div>';
		},
		20
	);

	// ── Adverts/Classifieds: hook after single content ──
	add_action(
		'apollo_classified_single_after_content',
		function ( $post_id ) {
			echo '<div style="margin: 15px 0;">';
			apollo_render_report_button( (int) $post_id, 'inline' );
			echo '</div>';
		},
		20
	);

	// ── Loc (local): hook after single content ──
	add_action(
		'apollo_loc_single_after_content',
		function ( $post_id ) {
			echo '<div style="margin: 15px 0;">';
			apollo_render_report_button( (int) $post_id, 'inline' );
			echo '</div>';
		},
		20
	);

	// ── DJ profile: hook after content ──
	add_action(
		'apollo_dj_single_after_content',
		function ( $post_id ) {
			echo '<div style="margin: 15px 0;">';
			apollo_render_report_button( (int) $post_id, 'inline' );
			echo '</div>';
		},
		20
	);
}

// Auto-register hooks on frontend
if ( ! is_admin() ) {
	add_action( 'wp', 'apollo_report_button_hooks' );
}
/**
 * CRITICAL FIX: Inject modal at wp_footer priority 9999999
 * Renders OUTSIDE all wrappers ensuring z-index: 9999999!important works
 */
add_action(
	'wp_footer',
	function () {
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;
		apollo_render_report_modal( 'apolloGlobalTrigger', 'frontend' );
	},
	9999999
);

/**
 * ADMIN: Inject modal at admin_footer priority 9999999
 * Same fix for admin pages
 */
add_action(
	'admin_footer',
	function () {
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;
		apollo_render_report_modal( 'apolloAdminTrigger', 'admin' );
	},
	9999999
);