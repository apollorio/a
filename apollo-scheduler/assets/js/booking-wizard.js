/**
 * Apollo Scheduler — luxury booking wizard (GSAP stagger, FLIP, Lenis).
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

	function api(config, path, opts) {
		opts = opts || {};
		return fetch(config.restUrl + path, {
			method: opts.method || 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.restNonce
			},
			body: opts.body ? JSON.stringify(opts.body) : undefined,
			credentials: 'same-origin'
		}).then(function (r) { return r.json(); });
	}

	document.addEventListener('DOMContentLoaded', function () {
		var config = readConfig();
		if (!config || !document.getElementById('apollo-scheduler-services')) return;

		if (window.Lenis) {
			new Lenis({ smoothWheel: true });
		}

		var state = {
			step: 'service',
			serviceId: 0,
			agentId: 0,
			resourceId: 0,
			date: '',
			time: '',
			nucleoId: config.nucleoId || 0,
			catalog: { services: [], agents: [], resources: [] }
		};

		function setStep(step) {
			state.step = step;
			document.querySelectorAll('.apollo-scheduler-panel').forEach(function (p) {
				p.classList.toggle('is-hidden', p.getAttribute('data-panel') !== step);
			});
			document.querySelectorAll('.apollo-scheduler-steps li').forEach(function (li) {
				li.classList.toggle('is-active', li.getAttribute('data-step') === step);
			});
			if (window.gsap) {
				gsap.from('.apollo-scheduler-panel:not(.is-hidden)', { opacity: 0, y: 16, duration: 0.45, ease: 'power2.out' });
			}
		}

		function renderCards(containerId, items, onSelect, labelKey) {
			var el = document.getElementById(containerId);
			if (!el) return;
			el.innerHTML = '';
			items.forEach(function (item, i) {
				var card = document.createElement('button');
				card.type = 'button';
				card.className = 'apollo-scheduler-card';
				card.innerHTML = '<h3>' + (item[labelKey] || item.title || item.display_name || '') + '</h3><p>' + (item.duration ? item.duration + ' min' : '') + '</p>';
				card.addEventListener('click', function () {
					if (window.gsap && window.Flip) {
						var stateFlip = Flip.getState(el.querySelectorAll('.apollo-scheduler-card'));
						el.querySelectorAll('.apollo-scheduler-card').forEach(function (c) { c.classList.remove('is-selected'); });
						card.classList.add('is-selected');
						Flip.from(stateFlip, { duration: 0.4, ease: 'power2.out' });
					} else {
						el.querySelectorAll('.apollo-scheduler-card').forEach(function (c) { c.classList.remove('is-selected'); });
						card.classList.add('is-selected');
					}
					onSelect(item);
				});
				el.appendChild(card);
				if (window.gsap) {
					gsap.from(card, { opacity: 0, y: 20, delay: i * 0.06, duration: 0.4, ease: 'power2.out' });
				}
			});
		}

		function loadCatalog() {
			if (!state.nucleoId) return Promise.resolve();
			return api(config, '/nucleo/' + state.nucleoId + '/catalog').then(function (res) {
				state.catalog = res;
				renderCards('apollo-scheduler-services', res.services || [], function (s) {
					state.serviceId = s.id;
					setStep('agent');
					renderCards('apollo-scheduler-agents', res.agents || [], function (a) {
						state.agentId = a.user_id || a.id;
						setStep('resource');
						renderCards('apollo-scheduler-resources', res.resources || [], function (r) {
							state.resourceId = r.id;
							setStep('time');
						}, 'title');
					}, 'display_name');
				}, 'title');
			});
		}

		function loadSlots() {
			var dateEl = document.getElementById('apollo-scheduler-date');
			if (!dateEl || !state.serviceId || !state.agentId) return;
			state.date = dateEl.value;
			if (!state.date) return;

			var slotsEl = document.getElementById('apollo-scheduler-slots');
			slotsEl.innerHTML = '<p>' + (config.i18n.loading || 'Loading…') + '</p>';

			var q = '?date=' + encodeURIComponent(state.date) + '&service_id=' + state.serviceId + '&agent_id=' + state.agentId;
			if (state.resourceId) q += '&resource_id=' + state.resourceId;

			api(config, '/availability/grid' + q).then(function (res) {
				slotsEl.innerHTML = '';
				(res.grid || []).forEach(function (slot, i) {
					var btn = document.createElement('button');
					btn.type = 'button';
					btn.className = 'apollo-scheduler-slot ' + (slot.available ? 'is-available' : 'is-disabled');
					btn.textContent = slot.time;
					btn.title = slot.reason || '';
					if (slot.available) {
						btn.addEventListener('click', function () {
							state.time = slot.time;
							slotsEl.querySelectorAll('.apollo-scheduler-slot').forEach(function (s) { s.classList.remove('is-selected'); });
							btn.classList.add('is-selected');
							document.getElementById('apollo-scheduler-summary').innerHTML =
								'<p><strong>' + state.date + ' ' + state.time + '</strong></p>';
							setStep('confirm');
						});
					}
					slotsEl.appendChild(btn);
					if (window.gsap && slot.available) {
						gsap.from(btn, { scale: 0.9, opacity: 0, delay: i * 0.02, duration: 0.25 });
					}
				});
			});
		}

		var dateEl = document.getElementById('apollo-scheduler-date');
		if (dateEl) {
			dateEl.min = new Date().toISOString().slice(0, 10);
			dateEl.addEventListener('change', loadSlots);
		}

		document.getElementById('apollo-scheduler-confirm')?.addEventListener('click', function () {
			api(config, '/book', {
				method: 'POST',
				body: {
					date: state.date,
					time: state.time,
					service_id: state.serviceId,
					agent_id: state.agentId,
					resource_id: state.resourceId,
					nucleo_id: state.nucleoId,
					payment: 'manual'
				}
			}).then(function (res) {
				if (res.success) {
					if (window.gsap) gsap.to('.apollo-scheduler-canvas', { opacity: 0.5, duration: 0.3 });
					alert('Booking confirmed #' + res.appointment_id);
				}
			});
		});

		loadCatalog();
	});
})();
