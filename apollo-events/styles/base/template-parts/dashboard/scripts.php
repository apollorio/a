<?php
/**
 * Meus Eventos — Scripts (filter, search, publish) + shared shell JS.
 *
 * Shell behaviour (drawer, panels, theme pill, comboboxes) is provided by the
 * same apollo-events-create-shell.js used by the create/edit form.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event_v   = defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.0.0';
$dash_conf = array(
	'restUrl' => esc_url_raw( rest_url( 'apollo/v1/eventos' ) ),
	'nonce'   => wp_create_nonce( 'wp_rest' ),
	'i18n'    => array(
		'events'  => __( 'eventos', 'apollo-events' ),
		'publish' => __( 'Publicar', 'apollo-events' ),
		'sending' => __( 'Publicando...', 'apollo-events' ),
		'live'    => __( 'Publicado', 'apollo-events' ),
		'fail'    => __( 'Falhou — tente de novo', 'apollo-events' ),
	),
);
?>
<script>
window.APOLLO_DASH = <?php echo wp_json_encode( $dash_conf ); ?>;
(function () {
	'use strict';
	var CFG = window.APOLLO_DASH || {};
	var pills = document.querySelectorAll('.ev-filter');
	var cards = Array.prototype.slice.call(document.querySelectorAll('.ev-mcard'));
	var countEl = document.getElementById('evCount');
	var searchEl = document.getElementById('dashSearch');
	var activeFilter = 'all';

	function matchesFilter(status, filter) {
		if (filter === 'all') return true;
		if (filter === 'published') return status === 'publish';
		if (filter === 'draft') return status === 'draft' || status === 'pending';
		if (filter === 'scheduled') return status === 'future' || status === 'scheduled';
		return true;
	}

	function apply() {
		var q = (searchEl && searchEl.value || '').toLowerCase().trim();
		var visible = 0;
		cards.forEach(function (card) {
			var status = card.getAttribute('data-status') || '';
			var nameEl = card.querySelector('.ev-mcard__name');
			var name = (nameEl && nameEl.textContent || '').toLowerCase();
			var ok = matchesFilter(status, activeFilter) && (!q || name.indexOf(q) !== -1);
			card.style.display = ok ? '' : 'none';
			if (ok) visible++;
		});
		if (countEl) countEl.textContent = visible + ' ' + ((CFG.i18n && CFG.i18n.events) || 'eventos');
	}

	pills.forEach(function (pill) {
		pill.addEventListener('click', function () {
			pills.forEach(function (p) { p.classList.remove('is-active'); });
			this.classList.add('is-active');
			activeFilter = this.getAttribute('data-filter') || 'all';
			apply();
		});
	});
	if (searchEl) searchEl.addEventListener('input', apply);

	/* Publish a draft via REST */
	document.querySelectorAll('[data-action="publish"]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var id = this.getAttribute('data-event-id');
			if (!id || !CFG.restUrl) return;
			var original = btn.innerHTML;
			btn.disabled = true;
			btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> ' + ((CFG.i18n && CFG.i18n.sending) || 'Publicando...');
			fetch(CFG.restUrl + '/' + id, {
				method: 'PUT',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce || '' },
				credentials: 'same-origin',
				body: JSON.stringify({ status: 'publish' })
			}).then(function (r) {
				if (!r.ok) throw new Error('http ' + r.status);
				return r.json();
			}).then(function () {
				var card = btn.closest('.ev-mcard');
				if (card) {
					card.setAttribute('data-status', 'publish');
					var badge = card.querySelector('.ev-badge');
					if (badge) { badge.className = 'ev-badge ev-badge--published'; badge.textContent = (CFG.i18n && CFG.i18n.live) || 'Publicado'; }
				}
				btn.innerHTML = '<i class="ri-check-line"></i> ' + ((CFG.i18n && CFG.i18n.live) || 'Publicado');
			}).catch(function () {
				btn.disabled = false;
				btn.innerHTML = original;
			});
		});
	});
})();
</script>
<script src="<?php echo esc_url( APOLLO_EVENT_URL . 'assets/js/apollo-events-create-shell.js?v=' . rawurlencode( $event_v ) ); ?>"></script>
