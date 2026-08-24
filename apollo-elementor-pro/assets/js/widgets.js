/**
 * Apollo Elementor Pro — Frontend JS
 *
 * Handles:
 *   1. Lazy REST hydration for all apollo-widget elements
 *   2. WoW / Fav counter interactions
 *   3. GSAP ScrollTrigger entrance animation for apollo-gsap-trigger grids
 */
(function () {
	'use strict';

	/* ─── Utilities ──────────────────────────────────────────────────── */

	function escHtml(str) {
		if (!str) { return ''; }
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	/**
	 * GET from Apollo REST (with optional WP nonce).
	 */
	function apolloFetch(url, nonce) {
		var headers = { 'Content-Type': 'application/json' };
		if (nonce) { headers['X-WP-Nonce'] = nonce; }
		return fetch(url, { headers: headers }).then(function (r) {
			if (!r.ok) { throw new Error(r.status); }
			return r.json();
		});
	}

	/**
	 * POST to Apollo REST.
	 */
	function apolloPost(url, nonce, body) {
		return fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce
			},
			body: JSON.stringify(body)
		}).then(function (r) {
			if (!r.ok) { throw new Error(r.status); }
			return r.json();
		});
	}

	/**
	 * Read REST config injected by SettingsBridge::print_rest_config().
	 */
	function getRestConfig(key) {
		return (window.apolloRest && window.apolloRest[key]) || null;
	}

	/* ─── Grid renderer ──────────────────────────────────────────────── */

	function renderGrid(wrapper, items, renderItem) {
		var cols = wrapper.dataset.columns || '3';
		var grid = document.createElement('div');
		grid.className = 'apollo-grid';
		grid.dataset.columns = cols;

		items.forEach(function (item) {
			var card = document.createElement('div');
			card.className = 'apollo-card';
			card.innerHTML = renderItem(item);
			grid.appendChild(card);
		});

		wrapper.innerHTML = '';
		wrapper.appendChild(grid);
		return grid;
	}

	/* ─── Events Grid ────────────────────────────────────────────────── */

	function initEventsGrid(el) {
		var cfg      = getRestConfig('events');
		if (!cfg) { return; }

		var perPage  = el.dataset.perPage  || 9;
		var upcoming = el.dataset.upcoming === 'yes' ? '1' : '0';
		var cat      = el.dataset.category || '';
		var showDate = el.dataset.showDate !== 'no';
		var url      = cfg.root + '?per_page=' + perPage + '&upcoming=' + upcoming + (cat ? '&category=' + encodeURIComponent(cat) : '');

		apolloFetch(url, cfg.nonce).then(function (data) {
			var items = Array.isArray(data) ? data : (data.items || []);
			if (!items.length) {
				el.innerHTML = '<p style="color:#71717a;text-align:center;padding:24px">Nenhum evento encontrado.</p>';
				return;
			}
			renderGrid(el, items, function (evt) {
				return (evt.cover ? '<img src="' + escHtml(evt.cover) + '" alt="" class="apollo-card-cover">' : '')
					+ '<div class="apollo-card-body">'
					+ (showDate && evt.date ? '<div class="apollo-card-meta"><span>' + escHtml(evt.date) + '</span></div>' : '')
					+ '<h3 class="apollo-card-title">' + escHtml(evt.title || '') + '</h3>'
					+ (evt.venue ? '<div class="apollo-card-meta"><span>' + escHtml(evt.venue) + '</span></div>' : '')
					+ '</div>';
			});
		}).catch(function () {
			el.innerHTML = '<p style="color:#ef4444;text-align:center;padding:24px">Erro ao carregar eventos.</p>';
		});
	}

	/* ─── DJ Card ────────────────────────────────────────────────────── */

	function initDJCard(el) {
		var cfg    = getRestConfig('users');
		if (!cfg) { return; }

		var uid    = el.dataset.userId || 0;
		var url    = cfg.root + '/' + uid;

		apolloFetch(url, cfg.nonce).then(function (dj) {
			var sounds     = el.dataset.showSounds  !== 'no';
			var stats      = el.dataset.showStats   !== 'no';
			var radioBtn   = el.dataset.showRadio   === 'yes';
			var pillsHtml  = '';

			if (sounds && dj.sounds && dj.sounds.length) {
				pillsHtml = '<div class="apollo-dj-sounds">'
					+ dj.sounds.map(function (s) { return '<span class="apollo-sound-pill">' + escHtml(s) + '</span>'; }).join('')
					+ '</div>';
			}

			el.innerHTML = '<div class="apollo-card">'
				+ '<div class="apollo-dj-header">'
				+ '<img src="' + escHtml(dj.avatar || '') + '" alt="" class="apollo-card-avatar">'
				+ '<div>'
				+ '<div class="apollo-card-title">' + escHtml(dj.display_name || '') + '</div>'
				+ (dj.membership ? '<div class="apollo-membership-badge apollo-accent-bg">' + escHtml(dj.membership) + '</div>' : '')
				+ '</div>'
				+ '</div>'
				+ pillsHtml
				+ (stats ? '<div class="apollo-card-body apollo-card-meta">'
					+ '<span>WoW ' + escHtml(String(dj.wow_total || 0)) + '</span>'
					+ '<span>Fav ' + escHtml(String(dj.fav_total || 0)) + '</span>'
					+ '</div>' : '')
				+ (radioBtn ? '<div class="apollo-card-body"><button class="apollo-btn apollo-accent-bg" style="width:100%">🎙 On Air</button></div>' : '')
				+ '</div>';
		}).catch(function () {
			el.innerHTML = '<p style="color:#ef4444;text-align:center;padding:24px">Erro ao carregar DJ.</p>';
		});
	}

	/* ─── Classifieds Grid ───────────────────────────────────────────── */

	function initClassifiedsGrid(el) {
		var cfg  = getRestConfig('classifieds');
		if (!cfg) { return; }
		var url  = cfg.root + '?per_page=' + (el.dataset.perPage || 12)
			+ '&category=' + encodeURIComponent(el.dataset.category || '')
			+ '&orderby='  + encodeURIComponent(el.dataset.sort || 'date');

		apolloFetch(url, cfg.nonce).then(function (data) {
			var items = Array.isArray(data) ? data : (data.items || []);
			if (!items.length) {
				el.innerHTML = '<p style="text-align:center;padding:24px;color:#71717a">Sem anúncios.</p>';
				return;
			}
			renderGrid(el, items, function (c) {
				return (c.cover ? '<img src="' + escHtml(c.cover) + '" alt="" class="apollo-card-cover">' : '')
					+ '<div class="apollo-card-body">'
					+ '<h3 class="apollo-card-title">' + escHtml(c.title || '') + '</h3>'
					+ (c.price ? '<div class="apollo-card-meta apollo-accent"><strong>' + escHtml(c.price) + '</strong></div>' : '')
					+ '</div>';
			});
		}).catch(function () {
			el.innerHTML = '<p style="color:#ef4444;text-align:center;padding:24px">Erro.</p>';
		});
	}

	/* ─── Profile Card ───────────────────────────────────────────────── */

	function initProfileCard(el) {
		var cfg = getRestConfig('users');
		if (!cfg) { return; }
		var uid = el.dataset.userId || 0;
		var url = cfg.root + '/' + uid;

		apolloFetch(url, cfg.nonce).then(function (u) {
			var socials = '';
			if (el.dataset.social !== 'no' && u.instagram) {
				socials = '<a href="https://instagram.com/' + encodeURIComponent(u.instagram || '') + '" target="_blank" rel="noopener" class="apollo-accent" style="margin-top:8px;display:inline-block">@' + escHtml(u.instagram) + '</a>';
			}
			el.innerHTML = '<div class="apollo-profile-card apollo-card">'
				+ '<img src="' + escHtml(u.avatar || '') + '" alt="" class="apollo-avatar">'
				+ (el.dataset.membership !== 'no' && u.membership ? '<div class="apollo-membership-badge">' + escHtml(u.membership) + '</div>' : '')
				+ '<div class="apollo-card-title">' + escHtml(u.display_name || '') + '</div>'
				+ (u.bio ? '<p style="font-size:13px;color:#52525b;margin:6px 0">' + escHtml(u.bio) + '</p>' : '')
				+ socials
				+ (el.dataset.wowFav !== 'no' ? '<div class="apollo-card-meta" style="justify-content:center;margin-top:10px">'
					+ '<span>WoW ' + escHtml(String(u.wow_total || 0)) + '</span>'
					+ '<span>Fav ' + escHtml(String(u.fav_total || 0)) + '</span>'
					+ '</div>' : '')
				+ '</div>';
		}).catch(function () {
			el.innerHTML = '<p style="text-align:center;color:#ef4444;padding:24px">Erro.</p>';
		});
	}

	/* ─── Hub Listing ────────────────────────────────────────────────── */

	function initHubListing(el) {
		var cfg  = getRestConfig('hubs');
		if (!cfg) { return; }
		var url  = cfg.root + '?per_page=' + (el.dataset.perPage || 6)
			+ (el.dataset.hubType ? '&type=' + encodeURIComponent(el.dataset.hubType) : '');

		apolloFetch(url, cfg.nonce).then(function (data) {
			var items = Array.isArray(data) ? data : (data.items || []);
			if (!items.length) {
				el.innerHTML = '<p style="text-align:center;padding:24px;color:#71717a">Nenhum hub encontrado.</p>';
				return;
			}
			var grid = renderGrid(el, items, function (h) {
				return (el.dataset.showCover !== 'no' && h.cover ? '<img src="' + escHtml(h.cover) + '" alt="" class="apollo-card-cover">' : '')
					+ '<div class="apollo-card-body">'
					+ (h.type ? '<span class="apollo-hub-type-pill">' + escHtml(h.type) + '</span>' : '')
					+ '<h3 class="apollo-card-title">' + escHtml(h.title || '') + '</h3>'
					+ (h.city ? '<div class="apollo-card-meta"><span>' + escHtml(h.city) + '</span></div>' : '')
					+ '</div>';
			});

			// GSAP entrance if requested.
			if (el.classList.contains('apollo-gsap-trigger') && window.gsap && window.ScrollTrigger) {
				var cards = grid.querySelectorAll('.apollo-card');
				gsap.fromTo(cards,
					{ opacity: 0, y: 24 },
					{
						opacity: 1, y: 0,
						duration: 0.45,
						stagger: 0.08,
						ease: 'power2.out',
						scrollTrigger: { trigger: grid, start: 'top 85%' }
					}
				);
			} else if (el.classList.contains('apollo-gsap-trigger')) {
				// Fallback without GSAP: just show the cards.
				grid.querySelectorAll('.apollo-card').forEach(function (c) {
					c.style.opacity = '1';
					c.style.transform = 'none';
				});
			}
		}).catch(function () {
			el.innerHTML = '<p style="color:#ef4444;text-align:center;padding:24px">Erro.</p>';
		});
	}

	/* ─── WoW Counter ────────────────────────────────────────────────── */

	function initWowCounter(el) {
		var cfg = getRestConfig('wow');
		if (!cfg) { return; }

		var target   = el.dataset.target     || 'post';
		var targetId = el.dataset.targetId   || 0;
		var url      = cfg.root + '?target=' + encodeURIComponent(target) + '&id=' + targetId;
		var wowBtn   = el.querySelector('.apollo-wow-btn');
		var favBtn   = el.querySelector('.apollo-fav-btn');

		apolloFetch(url, cfg.nonce).then(function (data) {
			if (wowBtn) { wowBtn.querySelector('.apollo-wow-count').textContent = data.wow || 0; }
			if (favBtn) { favBtn.querySelector('.apollo-fav-count').textContent = data.fav || 0; }
			if (data.user_wowed && wowBtn)  { wowBtn.classList.add('active'); }
			if (data.user_faved && favBtn)  { favBtn.classList.add('active'); }
		});

		if (el.dataset.allowClick === 'yes') {
			if (wowBtn) {
				wowBtn.addEventListener('click', function () {
					apolloPost(cfg.root + '/toggle', cfg.nonce, { target: target, id: targetId, type: 'wow' })
						.then(function (r) {
							wowBtn.querySelector('.apollo-wow-count').textContent = r.wow || 0;
							wowBtn.classList.toggle('active', !!r.user_wowed);
						});
				});
			}
			if (favBtn) {
				favBtn.addEventListener('click', function () {
					apolloPost(cfg.root + '/toggle', cfg.nonce, { target: target, id: targetId, type: 'fav' })
						.then(function (r) {
							favBtn.querySelector('.apollo-fav-count').textContent = r.fav || 0;
							favBtn.classList.toggle('active', !!r.user_faved);
						});
				});
			}
		}
	}

	/* ─── User Radar ─────────────────────────────────────────────────── */

	function initUserRadar(el) {
		// Radar chart: requires a <canvas> inside the widget.
		// Renders inline using a simple JS-drawn radar (no external dep).
		var canvas = el.querySelector('.apollo-radar-canvas');
		if (!canvas) { return; }

		var cfg  = getRestConfig('users');
		if (!cfg) { return; }

		var uid  = el.dataset.userId || 0;
		var dims = (el.dataset.dimensions || 'energy,groove,vocal,tempo,creativity').split(',');
		var h    = parseInt(el.dataset.height, 10) || 260;
		canvas.height = h;

		apolloFetch(cfg.root + '/' + uid + '/vibe', cfg.nonce).then(function (data) {
			drawRadar(canvas, dims, data);
		}).catch(function () {
			/* Draw empty radar */
			var empty = {};
			dims.forEach(function (d) { empty[d] = 0; });
			drawRadar(canvas, dims, empty);
		});
	}

	function drawRadar(canvas, dims, data) {
		var ctx = canvas.getContext('2d');
		if (!ctx) { return; }
		var w   = canvas.offsetWidth;
		canvas.width = w;
		var cx  = w / 2;
		var cy  = canvas.height / 2;
		var r   = Math.min(cx, cy) * 0.75;
		var n   = dims.length;
		var angle = function (i) { return (Math.PI * 2 * i / n) - Math.PI / 2; };

		// Background rings.
		ctx.strokeStyle = 'rgba(0,0,0,0.06)';
		[0.25, 0.5, 0.75, 1].forEach(function (scale) {
			ctx.beginPath();
			dims.forEach(function (_, i) {
				var a = angle(i);
				var x = cx + Math.cos(a) * r * scale;
				var y = cy + Math.sin(a) * r * scale;
				if (i === 0) { ctx.moveTo(x, y); } else { ctx.lineTo(x, y); }
			});
			ctx.closePath();
			ctx.stroke();
		});

		// Axes.
		ctx.strokeStyle = 'rgba(0,0,0,0.1)';
		dims.forEach(function (_, i) {
			var a = angle(i);
			ctx.beginPath();
			ctx.moveTo(cx, cy);
			ctx.lineTo(cx + Math.cos(a) * r, cy + Math.sin(a) * r);
			ctx.stroke();
		});

		// Data polygon.
		ctx.fillStyle   = 'rgba(255,152,32,0.18)';
		ctx.strokeStyle = '#FF9820';
		ctx.lineWidth   = 2;
		ctx.beginPath();
		dims.forEach(function (d, i) {
			var val = Math.max(0, Math.min(100, data[d] || 0)) / 100;
			var a   = angle(i);
			var x   = cx + Math.cos(a) * r * val;
			var y   = cy + Math.sin(a) * r * val;
			if (i === 0) { ctx.moveTo(x, y); } else { ctx.lineTo(x, y); }
		});
		ctx.closePath();
		ctx.fill();
		ctx.stroke();

		// Labels.
		ctx.fillStyle = '#121214';
		ctx.font      = 'bold 11px sans-serif';
		ctx.textAlign = 'center';
		dims.forEach(function (d, i) {
			var a   = angle(i);
			var x   = cx + Math.cos(a) * (r + 16);
			var y   = cy + Math.sin(a) * (r + 16);
			ctx.fillText(d, x, y + 4);
		});
	}

	/* ─── Dispatcher ─────────────────────────────────────────────────── */

	var initMap = {
		'apollo-events-grid':     initEventsGrid,
		'apollo-dj-card':         initDJCard,
		'apollo-classifieds-grid':initClassifiedsGrid,
		'apollo-profile-card':    initProfileCard,
		'apollo-user-radar':      initUserRadar,
		'apollo-membership-gate': null,
		'apollo-wow-counter':     initWowCounter,
		'apollo-hub-listing':     initHubListing,
	};

	function initAll() {
		Object.keys(initMap).forEach(function (cls) {
			var fn = initMap[cls];
			if (!fn) { return; }
			document.querySelectorAll('.' + cls).forEach(function (el) {
				fn(el);
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

	// Elementor editor live preview hook.
	if (window.elementorFrontend) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', function () {
			initAll();
		});
	}

}());
