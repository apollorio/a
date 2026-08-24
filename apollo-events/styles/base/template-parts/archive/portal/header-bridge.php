<?php

/**
 * Portal de Eventos — ponte cabeçalho ↔ portal.
 *
 * O bloco apollo_listing_header() é um COMPONENTE CONTROLADO: ele não sabe o
 * que é um evento, não consulta nada e não filtra lista nenhuma. Ele anuncia
 * intenção por CustomEvent e aceita estado de volta. Quem tem os dados é o
 * portal. Este arquivo é a única costura entre os dois — e é de propósito o
 * único lugar do sistema que conhece as duas metades.
 *
 * Sem ele o cabeçalho funciona (abre, fecha, anima) e não muda nada na tela;
 * sem o cabeçalho o portal funciona (renderiza o mês corrente) e não tem
 * controles. Nenhum dos dois derruba o outro — foi assim que o acoplamento
 * ficou desenhado.
 *
 * FLUXO
 *   apollo:lh:month   → AppPortalEventos.setMonth(ym, dir) + rerrotula o scrubber
 *   apollo:lh:filter  → AppPortalEventos.setFilters({ mode, tags, sounds })
 *   apollo:lh:search  → AppPortalEventos.setSearch(q)
 *   apollo:pev:search-clear (do portal) → limpa o campo de busca do cabeçalho
 *
 * @package Apollo\Event
 * @since   1.7.1
 * @see     header.php  monta os argumentos e publica window.APOLLO_PEV_HEADER
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
(function (w, d) {
	'use strict';

	var CFG = w.APOLLO_PEV_HEADER || {};
	if (!CFG.header) { return; }

	function app() { return w.AppPortalEventos || null; }
	function portal() { return w.APOLLO_PORTAL || {}; }
	function header() {
		return w.ApolloListingHeader ? w.ApolloListingHeader.get(CFG.header) : null;
	}

	function ymOf(year, index) {
		return year + '-' + (index + 1 < 10 ? '0' : '') + (index + 1);
	}

	/* Recontagem por mês para o ano exibido. O servidor já mandou o ano
	   corrente pronto; isto existe para quando o usuário atravessa dezembro e
	   o scrubber passa a descrever um ano que o PHP nunca contou. */
	function recount(year) {
		var h = header();
		var P = portal();
		if (!h || typeof P.byMonth !== 'function') { return; }
		var labels = CFG.months || [];
		var abbrs = CFG.abbr || [];
		var rows = [];
		for (var i = 0; i < 12; i++) {
			rows.push({
				label: labels[i] || '',
				abbr: abbrs[i] || '',
				count: (P.byMonth(ymOf(year, i)) || []).length
			});
		}
		h.setMonths(rows);
	}

	d.addEventListener('apollo:lh:month', function (e) {
		if (!e.detail || e.detail.id !== CFG.header) { return; }
		var a = app();
		if (a && typeof a.setMonth === 'function') {
			a.setMonth(e.detail.ym, e.detail.dir);
		}
		recount(e.detail.year);
	});

	d.addEventListener('apollo:lh:filter', function (e) {
		if (!e.detail || e.detail.id !== CFG.header) { return; }
		var a = app();
		if (!a || typeof a.setFilters !== 'function') { return; }

		var values = e.detail.values || {};
		var tags = [];

		/* Categorias, Tipos e Tags são taxonomias diferentes no WordPress, mas
		   archive-event.php funde os três em `e.tags` (nomes) na linha do
		   evento — então aqui elas voltam a ser uma lista só. `sound` é
		   separado porque casa por SLUG contra `e.genres`. */
		Object.keys(values).forEach(function (key) {
			if (key.indexOf('tax_') !== 0) { return; }
			tags = tags.concat(values[key] || []);
		});

		a.setFilters({
			mode: values.periodo || 'mes',
			tags: tags,
			sounds: values.sound || []
		});
	});

	d.addEventListener('apollo:lh:search', function (e) {
		if (!e.detail || e.detail.id !== CFG.header) { return; }
		var a = app();
		if (a && typeof a.setSearch === 'function') { a.setSearch(e.detail.query); }
	});

	/* O portal desenha um chip "Busca · x" que o usuário pode fechar. Quando
	   isso acontece o campo do cabeçalho tem que esvaziar junto, senão reabrir
	   a busca mostra um termo que já não está filtrando nada. */
	d.addEventListener('apollo:pev:search-clear', function () {
		var input = d.querySelector('[data-alh-search-input]');
		if (input) { input.value = ''; }
	});

	/* O portal monta em DOMContentLoaded; a primeira recontagem espera por ele
	   para que APOLLO_PORTAL já esteja derivado. */
	d.addEventListener('DOMContentLoaded', function () {
		var h = header();
		if (h) { recount(h.getYear()); }
	});
})(window, document);
</script>
