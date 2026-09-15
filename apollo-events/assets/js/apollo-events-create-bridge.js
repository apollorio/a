/**
 * Apollo Events — Create/Edit form bridge
 * Wires form.html simulation layer to WP REST + media library.
 */
(function () {
	'use strict';

	var CFG = window.APOLLO_EVENT_FORM || {};
	var $id = function (id) { return document.getElementById(id); };

	function toastSafe(msg) {
		if (typeof toast === 'function') toast(msg);
	}

	/* ── Venues from PHP loc catalog ── */
	window.knownVenues = function () {
		return (CFG.locs || []).map(function (l) {
			return {
				id: l.id,
				name: l.name,
				address: l.address || '',
				lat: l.lat != null ? String(l.lat) : '',
				lon: l.lon != null ? String(l.lon) : '',
				images: l.images || []
			};
		});
	};

	var _applyVenue = window.applyVenue;
	window.applyVenue = function (v) {
		if (typeof _applyVenue === 'function') _applyVenue(v);
		var block = $id('venueInfoBlock');
		if (block) block.style.display = '';
		if (v && v.id) {
			var locInput = $id('ev-loc-id');
			if (locInput) locInput.value = String(v.id);
		}
	};

	var _showVenueInfo = window.showVenueInfo;
	window.showVenueInfo = function () {
		var q = ($id('ev-venue-search') && $id('ev-venue-search').value || '').trim().toLowerCase();
		var venues = window.knownVenues() || [];
		var hit = venues.find(function (v) {
			return v.name.toLowerCase() === q || v.name.toLowerCase().indexOf(q) !== -1;
		});
		if (hit) {
			window.applyVenue(hit);
			return;
		}
		if (typeof _showVenueInfo === 'function') _showVenueInfo();
	};

	/* ── DJ catalog for as2 combobox ── */
	window.buildDJOptions = function () {
		var box = $id('djSelectOpts');
		var djs = CFG.djs || [];
		if (!box) return;
		box.innerHTML = djs.map(function (dj) {
			var thumb = dj.thumb
				? '<img src="' + dj.thumb.replace(/"/g, '&quot;') + '" alt="">'
				: '<span class="as2-av">' + (dj.name || '?').slice(0, 2).toUpperCase() + '</span>';
			return '<div class="as2-opt" role="option" data-id="' + dj.id + '" data-name="' +
				String(dj.name || '').replace(/"/g, '&quot;') + '" data-thumb="' +
				String(dj.thumb || '').replace(/"/g, '&quot;') + '" data-handle="' +
				String(dj.handle || '').replace(/"/g, '&quot;') + '">' +
				thumb + '<span>' + String(dj.name || '').replace(/</g, '&lt;') + '</span></div>';
		}).join('');
	};
	/* Must run NOW (synchronously), not on DOMContentLoaded: apollo-events-create-shell.js
	 * (loaded right after this file) wires the .as2 combobox by snapshotting
	 * $$('.as2-opt', root) once at its own load time. If #djSelectOpts is still empty
	 * when shell.js runs, the DJ dropdown is wired to a permanently empty option list —
	 * typing/opening it shows nothing even though the real DJ data exists. */
	window.buildDJOptions();

	/*
	 * Cover / gallery clicks are owned by apollo-events-create-wire.js (loaded
	 * after this file). Do NOT bind #coverUpload here — a second listener that
	 * hard-requires wp.media used to race the wire picker and abort the UX
	 * when media scripts failed to boot on the blank-canvas page.
	 */

	document.addEventListener('DOMContentLoaded', function () {
		/* User initials in chrome */
		var init = (CFG.user && CFG.user.initials) || '';
		document.querySelectorAll('.ax-avb-init, .profile-panel-av, .av').forEach(function (el) {
			if (el.classList.contains('av') && el.closest('.urow')) {
				el.textContent = init;
			}
			if (el.classList.contains('ax-avb-init') || el.classList.contains('profile-panel-av')) {
				el.textContent = init;
			}
		});
		var pname = document.querySelector('.profile-panel-name');
		if (pname && CFG.user) pname.textContent = CFG.user.name || '';
	});

	/* ── Sidebar uses real edit URLs ── */
	window.renderSidebar = function () {
		var box = $id('sidebarEvents');
		if (!box) return;
		var editingId = window.editingId != null ? window.editingId : (CFG.editId || null);
		var draftOn = !editingId ? 'on' : '';
		var html = '<a class="ni ' + draftOn + '" href="' + (CFG.createUrl || '/novo-evento/') + '">' +
			'<i class="ri-calendar-event-' + (draftOn ? 'fill' : 'line') + '"></i>' +
			'<span class="sn">Novo Evento (Rascunho)</span></a>';
		(CFG.events || []).forEach(function (ev) {
			var on = String(ev.id) === String(editingId) ? 'on' : '';
			html += '<a class="ni ' + on + '" href="' + (ev.edit_url || (CFG.createUrl + '?edit=' + ev.id)) + '">' +
				'<i class="ri-calendar-event-' + (on ? 'fill' : 'line') + '"></i>' +
				'<span class="sn">' + String(ev.title || '').replace(/</g, '&lt;') + '</span></a>';
		});
		box.innerHTML = html;
	};

	/* ── Load edit payload from PHP ── */
	window.loadEventFromPayload = function (ev) {
		if (!ev) return;
		window.editingId = ev.id;
		if ($id('pageTitle')) $id('pageTitle').innerText = (CFG.i18n && CFG.i18n.editTitle) || 'Editar Evento';
		if ($id('pageSubtitle')) $id('pageSubtitle').innerText = 'Editando “' + (ev.title || '') + '”.';
		if ($id('saveBtn')) $id('saveBtn').innerHTML = '<i class="ri-save-line"></i> ' + ((CFG.i18n && CFG.i18n.update) || 'Atualizar Evento');
		if ($id('a_eve_edit_id')) $id('a_eve_edit_id').value = String(ev.id);
		syncDeleteBtnVisibility(true);

		if ($id('ev-title')) $id('ev-title').value = ev.title || '';
		if (window.ApolloEventAboutEditor && typeof window.ApolloEventAboutEditor.setHTML === 'function') {
			window.ApolloEventAboutEditor.setHTML(ev.content || '');
		} else if ($id('ev-about')) {
			$id('ev-about').value = ev.content || '';
		}
		if ($id('ev-season')) $id('ev-season').value = ev.season || '';
		if ($id('ev-tickets') && ev.ticket_status) $id('ev-tickets').value = ev.ticket_status;
		if ($id('ev-status') && ev.event_status) $id('ev-status').value = ev.event_status;
		if ($id('ev-privacy') && ev.privacy) $id('ev-privacy').value = ev.privacy;
		if ($id('ev-ticket-price')) $id('ev-ticket-price').value = ev.ticket_price || '';
		if ($id('ev-tickets-url')) $id('ev-tickets-url').value = ev.ticket_url || '';
		if ($id('ev-list-url')) $id('ev-list-url').value = ev.list_url || '';
		if ($id('ev-video')) $id('ev-video').value = ev.video_url || '';
		if ($id('ev-audio')) $id('ev-audio').value = ev.audio_url || '';
		if ($id('ev-banner')) $id('ev-banner').value = ev.banner ? String(ev.banner) : '';
		if ($id('ev-banner-url')) $id('ev-banner-url').value = ev.banner_url || '';
		var bgColor = /^#[0-9a-fA-F]{6}$/.test(ev.bg_color || '') ? ev.bg_color : '#0a0a0a';
		if ($id('ev-bg-color')) $id('ev-bg-color').value = bgColor;
		if ($id('ev-bg-color-picker')) $id('ev-bg-color-picker').value = bgColor;

		if (typeof setGenres === 'function') setGenres(ev.sounds || []);
		if (typeof setCover === 'function') setCover(ev.banner_url || null);
		if (typeof setCoupons === 'function') setCoupons(ev.coupons || []);

		if (typeof setLineup === 'function') {
			setLineup((ev.lineup || []).map(function (dj) {
				return {
					name: dj.name,
					start: dj.start || '',
					end: dj.end || '',
					id: dj.id,
					thumb: dj.thumb || '',
					badge: dj.badge || ''
				};
			}));
		}

		if (typeof setAccessFields === 'function') {
			setAccessFields(ev);
		} else if ($id('ev-lista-cta-label')) {
			$id('ev-lista-cta-label').value = ev.lista_cta_label || '';
		}

		if (typeof setCoauthors === 'function') {
			setCoauthors(ev.coauthors || []);
		} else if ($id('ev-coauthors')) {
			$id('ev-coauthors').value = JSON.stringify(ev.coauthors || []);
		}

		if (ev.loc_id) {
			var locHit = (window.knownVenues() || []).find(function (v) { return Number(v.id) === Number(ev.loc_id); });
			if (locHit) {
				if ($id('ev-venue-search')) $id('ev-venue-search').value = locHit.name;
				window.applyVenue(locHit);
			} else if (ev.loc) {
				if ($id('ev-venue-search')) $id('ev-venue-search').value = ev.loc.title || '';
				window.applyVenue({
					id: ev.loc_id,
					name: ev.loc.title || '',
					address: ev.loc.address || '',
					lat: ev.loc.lat != null ? String(ev.loc.lat) : '',
					lon: ev.loc.lng != null ? String(ev.loc.lng) : '',
					images: []
				});
			}
			if ($id('ev-loc-id')) $id('ev-loc-id').value = String(ev.loc_id);
		}

		if (typeof dtp !== 'undefined') {
			dtp.start = { date: ev.start_date || null, time: ev.start_time || '23:00' };
			dtp.end = { date: ev.end_date || null, time: ev.end_time || '07:00' };
			dtp.endTouched = true;
			dtp.active = null;
			if ($id('dtpPanel')) $id('dtpPanel').classList.remove('is-open');
			if (typeof dtpUpdate === 'function') dtpUpdate();
		}

		if (typeof renderSidebar === 'function') renderSidebar();
		if (typeof syncModel === 'function') syncModel();
		if (typeof renderReceipt === 'function') renderReceipt();
	};

	/* ── Collect payload for REST ── */
	function collectPayload() {
		if (typeof syncModel === 'function') syncModel();
		if (window.ApolloEventAboutEditor && typeof window.ApolloEventAboutEditor.syncHidden === 'function') {
			window.ApolloEventAboutEditor.syncHidden();
		}
		var data = {
			title: ($id('ev-title') && $id('ev-title').value || '').trim(),
			content: ($id('ev-about') && $id('ev-about').value || '').trim(),
			start_date: ($id('start_date') && $id('start_date').value) || '',
			end_date: ($id('end_date') && $id('end_date').value) || '',
			start_time: ($id('start_time') && $id('start_time').value) || '',
			end_time: ($id('end_time') && $id('end_time').value) || '',
			bg_color: (function () {
				var v = ($id('ev-bg-color') && $id('ev-bg-color').value || '').trim();
				return /^#[0-9a-fA-F]{6}$/.test(v) ? v : '#0a0a0a';
			})(),
			ticket_url: ($id('ev-tickets-url') && $id('ev-tickets-url').value || '').trim(),
			ticket_price: ($id('ev-ticket-price') && $id('ev-ticket-price').value || '').trim(),
			ticket_status: ($id('ev-tickets') && $id('ev-tickets').value) || 'available',
			/* ticket_btn_style / list_btn_style: no DOM on /eventos/novo — omit so
			   save_event_meta() does not overwrite existing meta with hard-coded defaults. */
			list_url: ($id('ev-list-url') && $id('ev-list-url').value || '').trim(),
			video_url: ($id('ev-video') && $id('ev-video').value || '').trim(),
			audio_url: ($id('ev-audio') && $id('ev-audio').value || '').trim(),
			privacy: ($id('ev-privacy') && $id('ev-privacy').value) || 'public',
			event_status: ($id('ev-status') && $id('ev-status').value) || 'scheduled',
			coupon_code: ($id('ev-coupon-code') && $id('ev-coupon-code').value) || '',
			lista_cta_label: ($id('ev-lista-cta-label') && $id('ev-lista-cta-label').value || '').trim(),
			highlighted: ($id('ev-highlighted') && $id('ev-highlighted').value === '1') ? '1' : '0',
			post_status: 'draft'
		};

		try {
			data.access_buttons = JSON.parse(($id('ev-access-buttons') && $id('ev-access-buttons').value) || '[]');
			if (!Array.isArray(data.access_buttons)) data.access_buttons = [];
		} catch (e4) { data.access_buttons = []; }

		/* banner + gallery entries are an attachment id OR an absolute URL
		   (image hosted outside Apollo) — never coerce, the server decides. */
		var banner = $id('ev-banner');
		if (banner && banner.value) {
			var bval = banner.value.trim();
			data.banner = /^https?:\/\//i.test(bval) ? bval : (parseInt(bval, 10) || 0);
		}

		var loc = $id('ev-loc-id');
		var locId = loc && loc.value ? (parseInt(loc.value, 10) || 0) : 0;
		/* Resolve typed venue name → CPT id when the combobox was not clicked. */
		if (!locId) {
			var typed = ($id('ev-venue-search') && $id('ev-venue-search').value || '').trim().toLowerCase();
			if (typed) {
				var venues = (typeof window.knownVenues === 'function' ? window.knownVenues() : (CFG.locs || [])) || [];
				var hit = venues.find(function (v) {
					return String(v.name || '').toLowerCase() === typed;
				}) || venues.find(function (v) {
					return String(v.name || '').toLowerCase().indexOf(typed) !== -1;
				});
				if (hit && hit.id) {
					locId = parseInt(hit.id, 10) || 0;
					if (loc) loc.value = String(locId);
				}
			}
		}
		/* Always send loc_id so save_event_meta persists / clears _event_loc_id. */
		data.loc_id = locId;

		var season = $id('ev-season');
		if (season) {
			data.seasons = season.value ? [season.value] : [];
		}

		data.sounds = Array.prototype.slice.call(document.querySelectorAll('.ev-genre:checked')).map(function (cb) {
			return cb.value;
		});

		try {
			data.dj_ids = JSON.parse(($id('ev-dj-ids') && $id('ev-dj-ids').value) || '[]');
			if (!Array.isArray(data.dj_ids)) data.dj_ids = [];
		} catch (e) { data.dj_ids = []; }
		try {
			data.dj_slots = JSON.parse(($id('ev-dj-slots') && $id('ev-dj-slots').value) || '[]');
			if (!Array.isArray(data.dj_slots)) data.dj_slots = [];
		} catch (e2) { data.dj_slots = []; }

		var gal = $id('ev-gallery');
		if (gal && gal.value.trim()) {
			/* Split only on commas that start a new ref — URLs may contain commas. */
			data.gallery = gal.value
				.split(/,(?=\s*(?:\d+\s*(?:,|$)|https?:\/\/))/i)
				.map(function (v) { return v.trim(); })
				.filter(Boolean)
				.map(function (v) { return /^https?:\/\//i.test(v) ? v : (parseInt(v, 10) || 0); })
				.filter(function (v) { return v !== 0; });
		} else {
			data.gallery = [];
		}

		try {
			data.coauthors = JSON.parse(($id('ev-coauthors') && $id('ev-coauthors').value) || '[]');
			if (!Array.isArray(data.coauthors)) data.coauthors = [];
			data.coauthors = data.coauthors.map(function (n) { return parseInt(n, 10); }).filter(function (n) { return n > 0; });
		} catch (e3) { data.coauthors = []; }

		return data;
	}

	/* ── Persist via REST ── */
	window.saveEvent = async function (btnId) {
		if (typeof validateForm === 'function' && !validateForm()) {
			toastSafe((CFG.i18n && CFG.i18n.errRequired) || 'Corrija os campos destacados');
			return;
		}

		var btn = $id(btnId || 'saveBtn');
		var originalHTML = btn ? btn.innerHTML : '';
		var originalAria = btn ? btn.getAttribute('aria-label') : '';
		var editId = parseInt(($id('a_eve_edit_id') && $id('a_eve_edit_id').value) || CFG.editId || 0, 10) || 0;
		var isEdit = editId > 0;
		var data = collectPayload();

		if (btn) {
			btn.innerHTML = '<i class="ri-loader-4-line ri-spin" aria-hidden="true"></i>';
			btn.setAttribute('aria-label', isEdit ? 'Atualizando...' : 'Gravando...');
			btn.style.pointerEvents = 'none';
		}

		var url = CFG.restUrl || '';
		var method = 'POST';
		if (isEdit) {
			url = url.replace(/\/?$/, '/') + editId;
			method = 'PUT';
		}

		try {
			var res = await fetch(url, {
				method: method,
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': CFG.nonce || ''
				},
				credentials: 'same-origin',
				body: JSON.stringify(data)
			});
			var result = await res.json().catch(function () { return {}; });
			if (!res.ok) {
				throw new Error(result.error || result.message || ('HTTP ' + res.status));
			}

			var newId = result.id || editId;
			if (btn) {
				btn.innerHTML = '<i class="ri-check-line" aria-hidden="true"></i>';
				btn.setAttribute('aria-label', isEdit
					? ((CFG.i18n && CFG.i18n.updated) || 'Atualizado!')
					: ((CFG.i18n && CFG.i18n.saved) || 'Gravado!'));
			}
			toastSafe(isEdit
				? ((CFG.i18n && CFG.i18n.updated) || 'Evento atualizado!')
				: ((CFG.i18n && CFG.i18n.saved) || 'Evento salvo!'));

			if (!isEdit && newId) {
				setTimeout(function () {
					window.location.href = (CFG.createUrl || '/novo-evento/') + '?edit=' + newId;
				}, 700);
			} else if (btn) {
				setTimeout(function () {
					btn.innerHTML = originalHTML;
					if (originalAria) btn.setAttribute('aria-label', originalAria);
					btn.style.pointerEvents = 'auto';
				}, 1600);
			}
		} catch (err) {
			toastSafe(err.message || ((CFG.i18n && CFG.i18n.errNetwork) || 'Falha ao salvar'));
			if (btn) {
				btn.innerHTML = originalHTML;
				if (originalAria) btn.setAttribute('aria-label', originalAria);
				btn.style.pointerEvents = 'auto';
			}
		}
	};

	window.createNewEvent = function () {
		window.location.href = CFG.createUrl || '/novo-evento/';
	};

	/* Destacar (highlight) toggle — flips the hidden field the REST payload
	   reads (see collectPayload() above) and mirrors state on the button so
	   it stays pressed/gold until the next save. Persists only on Salvar. */
	window.toggleHighlighted = function (btnId) {
		var hidden = $id('ev-highlighted');
		if (!hidden) return;
		var on = hidden.value === '1';
		hidden.value = on ? '0' : '1';
		var btn = $id(btnId || 'highlightBtn');
		if (btn) {
			btn.classList.toggle('is-on', !on);
			btn.setAttribute('aria-pressed', on ? 'false' : 'true');
		}
	};

	window.openDeleteEventModal = function () {
		var editId = parseInt(($id('a_eve_edit_id') && $id('a_eve_edit_id').value) || CFG.editId || 0, 10) || 0;
		if (!editId) {
			toastSafe((CFG.i18n && CFG.i18n.errDelete) || 'Só é possível deletar ao editar um evento');
			return;
		}
		if (typeof openModal === 'function') openModal('deleteEventModal');
		else {
			var m = $id('deleteEventModal');
			if (m) m.classList.add('is-open');
		}
	};

	window.confirmDeleteEvent = async function () {
		var editId = parseInt(($id('a_eve_edit_id') && $id('a_eve_edit_id').value) || CFG.editId || 0, 10) || 0;
		if (!editId) return;

		var btn = $id('confirmDeleteEventBtn');
		var originalHTML = btn ? btn.innerHTML : '';
		if (btn) {
			btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Deletando…';
			btn.style.pointerEvents = 'none';
		}

		var url = (CFG.restUrl || '').replace(/\/?$/, '/') + editId;
		try {
			var res = await fetch(url, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': CFG.nonce || '' },
				credentials: 'same-origin'
			});
			var result = await res.json().catch(function () { return {}; });
			if (!res.ok) {
				throw new Error(result.error || result.message || ('HTTP ' + res.status));
			}
			toastSafe((CFG.i18n && CFG.i18n.deleted) || 'Evento deletado');
			if (typeof closeModal === 'function') closeModal('deleteEventModal');
			setTimeout(function () {
				window.location.href = CFG.dashboardUrl || CFG.createUrl || '/eventos/meus/';
			}, 600);
		} catch (err) {
			toastSafe(err.message || ((CFG.i18n && CFG.i18n.errDeleteFail) || 'Falha ao deletar'));
			if (btn) {
				btn.innerHTML = originalHTML;
				btn.style.pointerEvents = 'auto';
			}
		}
	};

	function syncDeleteBtnVisibility(isEdit) {
		var del = $id('deleteBtn');
		if (!del) return;
		if (isEdit) del.removeAttribute('hidden');
		else del.setAttribute('hidden', 'hidden');
	}

	/* Boot after form.js init defaults */
	document.addEventListener('DOMContentLoaded', function () {
		if (typeof buildDJOptions === 'function') buildDJOptions();

		/* BUGFIX (2026-08-29): the hidden input's value is the STRING "0" on
		   the create page, and !!"0" is true in JS — that truthy-string trap
		   made syncDeleteBtnVisibility(true) fire on every load, showing the
		   Deletar icon with nothing to delete. Parse to a real number first. */
		syncDeleteBtnVisibility((parseInt(($id('a_eve_edit_id') && $id('a_eve_edit_id').value) || CFG.editId || 0, 10) || 0) > 0);

		/* form.js's own init() already ran synchronously before this script
		 * loaded and re-rendered #sidebarEvents with mockup-only links
		 * (forms.html?event=ID). aside.php always server-renders the real
		 * author/co-author list with correct /novo-evento/?edit=ID links,
		 * so re-assert the real sidebar here — renderSidebar() now points
		 * to this file's WP-aware version (redefined above). */
		if (typeof renderSidebar === 'function') renderSidebar();

		if (CFG.editEvent) {
			window.loadEventFromPayload(CFG.editEvent);
		} else if (typeof dtpInitDefaults === 'function') {
			/* keep form.js defaults but clear fake venue */
			var vs = $id('ev-venue-search');
			if (vs && /MAM/i.test(vs.value)) {
				vs.value = '';
				var block = $id('venueInfoBlock');
				if (block) block.style.display = 'none';
			}
		}
	});
})();
