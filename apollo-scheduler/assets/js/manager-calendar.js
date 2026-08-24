/**
 * Apollo Scheduler — manager calendar view.
 */
(function () {
	'use strict';

	function readConfig() {
		var root = document.querySelector('[data-apollo-scheduler-config]');
		if (!root) return null;
		try {
			return JSON.parse(root.getAttribute('data-apollo-scheduler-config') || '{}');
		} catch (e) {
			return null;
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		var config = readConfig();
		if (!config || !document.getElementById('apollo-scheduler-manager-calendar')) return;

		if (window.Lenis) new Lenis({ smoothWheel: true });

		var cal = document.getElementById('apollo-scheduler-manager-calendar');
		var list = document.getElementById('apollo-scheduler-manager-list');

		if (!config.nucleoId) {
			cal.innerHTML = '<p>Nucleo not configured.</p>';
			return;
		}

		fetch(config.restUrl + '/nucleo/' + config.nucleoId + '/catalog', {
			headers: { 'X-WP-Nonce': config.restNonce },
			credentials: 'same-origin'
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				var agents = data.agents || [];
				cal.innerHTML = '<div class="apollo-scheduler-cards">' +
					agents.map(function (a) {
						return '<div class="apollo-scheduler-card"><h3>' + (a.display_name || '') + '</h3><p>Agent</p></div>';
					}).join('') + '</div>';

				list.innerHTML = '<ul>' + (data.services || []).map(function (s) {
					return '<li>' + s.title + ' — ' + s.duration + ' min</li>';
				}).join('') + '</ul>';

				document.getElementById('apollo-kpi-utilization').textContent = agents.length ? '—' : '0%';
				document.getElementById('apollo-kpi-today').textContent = String((data.services || []).length);

				if (window.gsap) {
					gsap.from('.apollo-scheduler-card', { opacity: 0, y: 12, stagger: 0.05, duration: 0.4 });
				}
			});
	});
})();
