<?php

/**
 * Panel: Detail — Slides RIGHT
 *
 * Direction: RIGHT (data-dir="right" from home or any panel)
 *
 * This panel handles ALL detail/single views across the platform:
 * - Single Event (/evento/{slug})
 * - Single DJ (/dj/{slug})
 * - Single Loc (/gps/{slug})
 * - Single Classified (/anuncio/{slug})
 * - Single Group/Comuna (/grupo/{slug})
 * - Single Social Post
 * - Single Profile (/id/{username})
 *
 * Content is loaded dynamically via hooks or AJAX.
 * Plugins hook into `apollo/detail/render_panel` to render their content.
 *
 * Navigation rule from pages-layout.json:
 *   ALL detail/single pages slide RIGHT from home. Always.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → detail_rule
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- ═══════════════════════════════════════════════════════════════
	PANEL: DETAIL — slides RIGHT from home
	Direction: RIGHT | Content: single-event, single-dj, single-loc,
	single-classified, single-group, single-post, profile
	═══════════════════════════════════════════════════════════════ -->
<section data-panel="detail" data-glyph="→">
	<div class="container" style="padding-top:calc(80px + var(--safe-top)); padding-bottom: 40px;">

		<!-- Back button -->
		<button data-back="1" class="return-back" aria-label="<?php esc_attr_e( 'Voltar', 'apollo-templates' ); ?>">
			<i class="ri-corner-up-left-line"></i>
		</button>

		<!-- ─── Detail Content Container ─── -->
		<div class="apollo-detail-container" id="apolloDetailContainer">

			<!-- Loading state -->
			<div class="apollo-detail-loader" id="detailLoader" style="display:none;">
				<div class="apollo-spinner"></div>
				<p class="text-muted">Carregando...</p>
			</div>

			<!-- Dynamic content area — populated by hooks or AJAX -->
			<div class="apollo-detail-content" id="detailContent">

				<?php
				/**
				 * Hook: apollo/detail/render_panel
				 *
				 * Plugins hook here to render their single/detail views.
				 * The active content type is determined by data-detail-type attribute
				 * on the trigger button.
				 *
				 * Example trigger from an event card:
				 * <button data-to="detail" data-dir="right" data-detail-type="event" data-detail-id="123">
				 *
				 * Hooks available per plugin:
				 * - apollo/events/render_single   (event)
				 * - apollo/djs/render_single       (dj)
				 * - apollo/loc/render_single       (loc — NEVER venue/location)
				 * - apollo/adverts/render_single   (classified)
				 * - apollo/groups/render_single    (group / comuna / nucleo)
				 * - apollo/social/render_single_post (social post)
				 * - apollo/users/render_profile    (user profile at /id/)
				 */
				do_action( 'apollo/detail/render_panel' );

				if ( ! has_action( 'apollo/detail/render_panel' ) ) :
					?>
					<div class="apollo-detail-placeholder">
						<i class="ri-arrow-right-line" style="font-size: 2rem; opacity: 0.3;"></i>
						<p class="ai text-muted" style="margin-top: 12px;">
							Selecione um item para ver detalhes
						</p>
					</div>
				<?php endif; ?>

			</div>
			<!-- END .apollo-detail-content -->

			<!-- ── Forms inside detail (slide DOWN from here) ── -->
			<?php if ( is_user_logged_in() ) : ?>
			<div class="apollo-detail-actions" id="detailActions" style="display:none;">
				<!-- Report button — opens acesso panel DOWN with report form -->
				<button class="apollo-btn apollo-btn--ghost"
					data-to="acesso" data-dir="down" data-form="report"
					aria-label="<?php esc_attr_e( 'Reportar', 'apollo-templates' ); ?>">
					<i class="ri-alarm-warning-line"></i>
					<?php esc_html_e( 'Reportar', 'apollo-templates' ); ?>
				</button>

				<!-- RSVP button — opens acesso panel DOWN with RSVP form -->
				<button class="apollo-btn apollo-btn--ghost"
					data-to="acesso" data-dir="down" data-form="rsvp"
					data-detail-action="rsvp"
					style="display:none;"
					aria-label="<?php esc_attr_e( 'Confirmar presença', 'apollo-templates' ); ?>">
					<i class="ri-user-add-line"></i>
					<?php esc_html_e( 'RSVP', 'apollo-templates' ); ?>
				</button>

				<!-- Depoimento button — opens acesso panel DOWN with depoimento form -->
				<button class="apollo-btn apollo-btn--ghost"
					data-to="acesso" data-dir="down" data-form="depoimento"
					data-detail-action="depoimento"
					style="display:none;"
					aria-label="<?php esc_attr_e( 'Depoimento', 'apollo-templates' ); ?>">
					<i class="ri-chat-quote-line"></i>
					<?php esc_html_e( 'Depoimento', 'apollo-templates' ); ?>
				</button>
			</div>
			<?php endif; ?>

		</div>
		<!-- END .apollo-detail-container -->

	</div>
</section>
<!-- END data-panel="detail" -->

<script>
/**
 * Apollo Detail Panel Controller
 *
 * Handles dynamic content loading for the RIGHT detail panel.
 *
 * Trigger format:
 *   <a data-to="detail" data-dir="right" data-detail-type="event" data-detail-id="123">
 *
 * Supported types: event, dj, local, classified, group, post, profile
 * REST endpoints per type align with apollo-registry.json.
 */
(function() {
	'use strict';

	var container = document.getElementById('detailContent');
	var loader    = document.getElementById('detailLoader');
	var actions   = document.getElementById('detailActions');

	if (!container) return;

	/**
	 * REST base paths per detail type.
	 * @see _inventory/apollo-registry.json → quick_lookup.all_rest_prefixes
	 */
	var REST_MAP = {
		'event':      'events/',
		'dj':         'djs/',
		'local':      'locals/',
		'classified': 'classifieds/',
		'group':      'groups/',
		'post':       'feed/',
		'profile':    'profile/'
	};

	/**
	 * Action buttons visibility per type.
	 */
	var ACTIONS_MAP = {
		'event':      ['rsvp', 'depoimento', 'report'],
		'dj':         ['depoimento', 'report'],
		'local':      ['depoimento', 'report'],
		'classified': ['report'],
		'group':      ['report'],
		'post':       ['report'],
		'profile':    ['report']
	};

	/**
	 * Load detail content via REST API.
	 */
	function loadDetail(type, id) {
		if (!window.apolloNavbar || !window.apolloNavbar.restUrl) return;

		var restBase = REST_MAP[type];
		if (!restBase) return;

		// Show loader
		loader.style.display = '';
		container.innerHTML = '';

		var url = window.apolloNavbar.restUrl + restBase + encodeURIComponent(id);

		fetch(url, {
			headers: {
				'X-WP-Nonce': window.apolloNavbar.nonce || ''
			}
		})
		.then(function(res) { return res.json(); })
		.then(function(data) {
			loader.style.display = 'none';
			/**
			 * Dispatch event for plugins to render their detail view.
			 * Plugins listen for 'apollo:detail:loaded' and populate container.
			 */
			var event = new CustomEvent('apollo:detail:loaded', {
				detail: { type: type, id: id, data: data }
			});
			document.dispatchEvent(event);

			// If no plugin handled it, show raw data summary
			if (container.innerHTML === '') {
				container.innerHTML = '<pre style="font-size:12px;opacity:0.6;white-space:pre-wrap;">' +
					JSON.stringify(data, null, 2).substring(0, 2000) + '</pre>';
			}

			// Show relevant action buttons
			showActions(type);
		})
		.catch(function() {
			loader.style.display = 'none';
			container.innerHTML = '<p class="text-muted">Erro ao carregar conteúdo.</p>';
		});
	}

	/**
	 * Show/hide action buttons based on content type.
	 */
	function showActions(type) {
		if (!actions) return;
		var allowed = ACTIONS_MAP[type] || ['report'];
		actions.style.display = '';

		actions.querySelectorAll('[data-detail-action]').forEach(function(btn) {
			var action = btn.getAttribute('data-detail-action');
			btn.style.display = allowed.indexOf(action) !== -1 ? '' : 'none';
		});
	}

	/**
	 * Listen for detail trigger clicks.
	 */
	document.addEventListener('click', function(e) {
		var trigger = e.target.closest('[data-detail-type][data-to="detail"]');
		if (!trigger) return;

		var type = trigger.getAttribute('data-detail-type');
		var id   = trigger.getAttribute('data-detail-id') || trigger.getAttribute('data-detail-slug');
		if (type && id) {
			loadDetail(type, id);
		}
	});
})();
</script>
