<?php

/**
 * Template Part: Events Archive — Scripts
 *
 * Client-side filtering engine (sound, date, search, category),
 * GSAP page-loader curtain, hero entrance, card stagger, toolbar scroll.
 *
 * Expects: $events (array) — passed from parent for JSON hydration.
 *
 * @package Apollo\Event
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
(function() {
	'use strict';

	/* ───────────────────────────────────────────
		0. GSAP — Page Loader Curtain
		─────────────────────────────────────────── */
	const loader = document.querySelector('.page-loader');
	if (loader) {
		gsap.to(loader, {
			scaleY: 0,
			transformOrigin: 'top center',
			duration: 0.7,
			ease: 'power3.inOut',
			delay: 0.15,
			onComplete() {
				loader.remove();
			}
		});
	}

	/* ───────────────────────────────────────────
		1. GSAP — Hero Entrance
		─────────────────────────────────────────── */
	const heroTl = gsap.timeline({
		delay: 0.35,
		defaults: {
			ease: 'power3.out'
		}
	});
	heroTl
		.to('.ev-hero__breadcrumb', {
			opacity: 1,
			y: 0,
			duration: 0.5
		})
		.to('.ev-hero__title', {
			opacity: 1,
			y: 0,
			duration: 0.65
		}, '-=0.3')
		.to('.ev-hero__sub', {
			opacity: 1,
			y: 0,
			duration: 0.55
		}, '-=0.35')
		.to('.ev-hero__stat', {
			opacity: 1,
			y: 0,
			duration: 0.5
		}, '-=0.25');

	/* ───────────────────────────────────────────
		2. GSAP — Card Stagger on View
		─────────────────────────────────────────── */
	function revealCards() {
		const cards = document.querySelectorAll('.ev-card:not(.hidden):not(.visible)');
		if (!cards.length) return;

		gsap.to(cards, {
			opacity: 1,
			y: 0,
			duration: 0.55,
			stagger: 0.06,
			ease: 'power3.out',
			onStart() {
				cards.forEach(c => c.classList.add('visible'));
			}
		});
	}

	// Initial reveal after loader
	setTimeout(revealCards, 500);

	/* ───────────────────────────────────────────
		3. Filter State
		─────────────────────────────────────────── */
	let activeSound = 'all';
	let activeDate = 'all';
	let activeCat = 'all';
	let searchTerm = '';

	const cards = () => document.querySelectorAll('.ev-card');
	const counter = document.getElementById('ev-counter');
	const emptyLive = document.getElementById('ev-empty-live');
	const emptyStatic = document.getElementById('ev-empty');
	const filtersInfo = document.getElementById('ev-active-filters');
	const filtersText = document.getElementById('ev-active-text');

	const today = document.querySelector('.ev-main')?.dataset.today || '';
	const todayDate = today ? new Date(today + 'T00:00:00') : new Date();
	const currentWeek = getISOWeek(todayDate);
	const currentMonth = todayDate.getMonth();
	const currentYear = todayDate.getFullYear();

	function getISOWeek(d) {
		const date = new Date(d.getTime());
		date.setHours(0, 0, 0, 0);
		date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
		const week1 = new Date(date.getFullYear(), 0, 4);
		return 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
	}

	/* ───────────────────────────────────────────
		4. Apply Filters
		─────────────────────────────────────────── */
	function applyFilters() {
		let visible = 0;

		cards().forEach(card => {
			let show = true;

			// Sound filter
			if (activeSound !== 'all') {
				const cardSounds = (card.dataset.sounds || '').split(',');
				if (!cardSounds.includes(activeSound)) show = false;
			}

			// Date filter
			if (show && activeDate !== 'all') {
				const cardDate = card.dataset.date || '';
				if (activeDate === 'today') {
					if (cardDate !== today) show = false;
				} else if (activeDate === 'week') {
					const cardWeek = parseInt(card.dataset.week, 10);
					if (cardWeek !== currentWeek) show = false;
				} else if (activeDate === 'month') {
					if (cardDate) {
						const d = new Date(cardDate + 'T00:00:00');
						if (d.getMonth() !== currentMonth || d.getFullYear() !== currentYear) show = false;
					} else {
						show = false;
					}
				}
			}

			// Category filter
			if (show && activeCat !== 'all') {
				const cardCats = (card.dataset.cats || '').split(',');
				if (!cardCats.includes(activeCat)) show = false;
			}

			// Search filter
			if (show && searchTerm) {
				const haystack = card.dataset.search || '';
				if (!haystack.includes(searchTerm)) show = false;
			}

			card.classList.toggle('hidden', !show);
			if (show) visible++;
		});

		// Counter
		if (counter) counter.textContent = visible;

		// Empty state
		const anyFilters = activeSound !== 'all' || activeDate !== 'all' || activeCat !== 'all' || searchTerm;
		if (emptyLive) emptyLive.classList.toggle('visible', visible === 0 && anyFilters);
		if (emptyStatic) emptyStatic.style.display = visible === 0 && !anyFilters ? 'flex' : 'none';

		// Active filters indicator
		if (filtersInfo && filtersText) {
			if (anyFilters) {
				const parts = [];
				if (activeSound !== 'all') parts.push(activeSound);
				if (activeDate !== 'all') parts.push(activeDate);
				if (activeCat !== 'all') parts.push(activeCat);
				if (searchTerm) parts.push('"' + searchTerm + '"');
				filtersText.textContent = visible + ' resultado' + (visible !== 1 ? 's' : '') + ' — ' + parts.join(
					' · ');
				filtersInfo.classList.add('visible');
			} else {
				filtersInfo.classList.remove('visible');
			}
		}

		// Re-reveal visible cards
		document.querySelectorAll('.ev-card:not(.hidden)').forEach(c => {
			if (!c.classList.contains('visible')) {
				c.classList.add('visible');
				gsap.fromTo(c, {
					opacity: 0,
					y: 24
				}, {
					opacity: 1,
					y: 0,
					duration: 0.45,
					ease: 'power3.out'
				});
			}
		});
	}

	/* ───────────────────────────────────────────
		5. Sound Pills
		─────────────────────────────────────────── */
	document.querySelectorAll('#ev-sound-pills .ev-pill').forEach(pill => {
		pill.addEventListener('click', () => {
			document.querySelectorAll('#ev-sound-pills .ev-pill').forEach(p => p.classList.remove(
				'active'));
			pill.classList.add('active');
			activeSound = pill.dataset.sound;
			applyFilters();
		});
	});

	/* ───────────────────────────────────────────
		6. Date Pills
		─────────────────────────────────────────── */
	document.querySelectorAll('#ev-date-pills .ev-pill').forEach(pill => {
		pill.addEventListener('click', () => {
			document.querySelectorAll('#ev-date-pills .ev-pill').forEach(p => p.classList.remove(
				'active'));
			pill.classList.add('active');
			activeDate = pill.dataset.date;
			applyFilters();
		});
	});

	/* ───────────────────────────────────────────
		7. Search
		─────────────────────────────────────────── */
	const searchInput = document.getElementById('ev-search');
	let searchTimeout;
	if (searchInput) {
		searchInput.addEventListener('input', () => {
			clearTimeout(searchTimeout);
			searchTimeout = setTimeout(() => {
				searchTerm = searchInput.value.trim().toLowerCase();
				applyFilters();
			}, 200);
		});
	}

	/* ───────────────────────────────────────────
		8. Category Filter
		─────────────────────────────────────────── */
	const catFilter = document.getElementById('ev-category-filter');
	if (catFilter) {
		catFilter.addEventListener('change', () => {
			activeCat = catFilter.value;
			applyFilters();
		});
	}

	/* ───────────────────────────────────────────
		9. Clear All
		─────────────────────────────────────────── */
	function clearAll() {
		activeSound = 'all';
		activeDate = 'all';
		activeCat = 'all';
		searchTerm = '';

		document.querySelectorAll('#ev-sound-pills .ev-pill').forEach((p, i) => p.classList.toggle('active', i ===
			0));
		document.querySelectorAll('#ev-date-pills .ev-pill').forEach((p, i) => p.classList.toggle('active', i ===
			0));
		if (searchInput) searchInput.value = '';
		if (catFilter) catFilter.value = 'all';
		applyFilters();
	}

	document.getElementById('ev-clear-all')?.addEventListener('click', clearAll);
	document.querySelector('.ev-empty__clear')?.addEventListener('click', clearAll);

	/* ───────────────────────────────────────────
		10. Toolbar Scroll Shadow
		─────────────────────────────────────────── */
	const toolbar = document.getElementById('ev-toolbar');
	if (toolbar) {
		let ticking = false;
		window.addEventListener('scroll', () => {
			if (!ticking) {
				requestAnimationFrame(() => {
					toolbar.classList.toggle('scrolled', window.scrollY > 200);
					ticking = false;
				});
				ticking = true;
			}
		}, {
			passive: true
		});
	}

	/* ───────────────────────────────────────────
		11. Single-Event Lightbox
		Cards open the single page inside an iframe overlay so the user can
		browse many parties without piling up tabs. Direct URL access (share,
		Google, deep link) still renders the full single page — this layer
		only intercepts card clicks on the archive. Back button / ESC /
		backdrop close it. "Abrir página" jumps to the real URL.
		─────────────────────────────────────────── */
	(function eventLightbox() {
		let lb = null, frame = null, openBtn = null, lastFocus = null;

		function build() {
			lb = document.createElement('div');
			lb.id = 'evLightbox';
			lb.setAttribute('role', 'dialog');
			lb.setAttribute('aria-modal', 'true');
			lb.setAttribute('aria-label', 'Evento');
			lb.innerHTML =
				'<div class="evlb-backdrop"></div>' +
				'<div class="evlb-shell">' +
					'<div class="evlb-bar">' +
						'<button type="button" class="evlb-btn evlb-close" aria-label="Fechar"><i class="ri-close-line"></i></button>' +
						'<div class="evlb-bar-actions">' +
							'<button type="button" class="evlb-btn evlb-copy" title="Copiar link"><i class="ri-link"></i></button>' +
							'<a class="evlb-btn evlb-open" target="_blank" rel="noopener" title="Abrir página"><i class="ri-external-link-line"></i></a>' +
						'</div>' +
					'</div>' +
					'<div class="evlb-frame-wrap"><div class="evlb-spinner"></div><iframe class="evlb-frame" title="Evento" loading="eager" referrerpolicy="same-origin"></iframe></div>' +
				'</div>';
			document.body.appendChild(lb);

			const style = document.createElement('style');
			style.textContent = [
				'#evLightbox{position:fixed;inset:0;z-index:9990;display:none;}',
				'#evLightbox.on{display:block;}',
				'#evLightbox .evlb-backdrop{position:absolute;inset:0;background:rgba(8,8,8,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);opacity:0;transition:opacity .35s ease;}',
				'#evLightbox.vis .evlb-backdrop{opacity:1;}',
				'#evLightbox .evlb-shell{position:absolute;inset:0;margin:auto;width:min(920px,100vw);height:100dvh;display:flex;flex-direction:column;transform:translateY(24px);opacity:0;transition:transform .4s cubic-bezier(.16,1,.3,1),opacity .35s ease;}',
				'@media(min-width:960px){#evLightbox .evlb-shell{height:min(94dvh,1100px);border-radius:22px;overflow:hidden;box-shadow:0 40px 90px rgba(0,0,0,.55);}}',
				'#evLightbox.vis .evlb-shell{transform:translateY(0);opacity:1;}',
				'#evLightbox .evlb-bar{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;background:#101010;border-bottom:1px solid rgba(255,255,255,.06);}',
				'#evLightbox .evlb-bar-actions{display:flex;gap:8px;}',
				'#evLightbox .evlb-btn{width:38px;height:38px;border-radius:999px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.05);color:#e1e1e1;display:inline-flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;text-decoration:none;transition:background .25s;}',
				'#evLightbox .evlb-btn:hover{background:rgba(255,255,255,.12);}',
				'#evLightbox .evlb-frame-wrap{position:relative;flex:1;background:#0a0a0a;}',
				'#evLightbox .evlb-frame{position:absolute;inset:0;width:100%;height:100%;border:0;opacity:0;transition:opacity .3s ease;}',
				'#evLightbox .evlb-frame.ready{opacity:1;}',
				'#evLightbox .evlb-spinner{position:absolute;left:50%;top:50%;width:34px;height:34px;margin:-17px 0 0 -17px;border-radius:50%;border:2.5px solid rgba(255,255,255,.12);border-top-color:rgba(255,146,32,.9);animation:evlbspin .8s linear infinite;}',
				'@keyframes evlbspin{to{transform:rotate(360deg)}}',
				'body.evlb-lock{overflow:hidden;}'
			].join('');
			document.head.appendChild(style);

			frame = lb.querySelector('.evlb-frame');
			openBtn = lb.querySelector('.evlb-open');

			lb.querySelector('.evlb-close').addEventListener('click', close);
			lb.querySelector('.evlb-backdrop').addEventListener('click', close);
			lb.querySelector('.evlb-copy').addEventListener('click', function () {
				const url = openBtn.href || '';
				if (!url) return;
				(navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject())
					.then(() => { this.innerHTML = '<i class="ri-check-line"></i>'; setTimeout(() => { this.innerHTML = '<i class="ri-link"></i>'; }, 1400); })
					.catch(() => { window.prompt('Link do evento:', url); });
			});
			frame.addEventListener('load', () => frame.classList.add('ready'));
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && lb.classList.contains('on')) close();
			});
			window.addEventListener('popstate', () => {
				if (lb.classList.contains('on')) close(true);
			});
		}

		function open(url) {
			if (!lb) build();
			lastFocus = document.activeElement;
			openBtn.href = url;
			frame.classList.remove('ready');
			frame.src = url;
			lb.classList.add('on');
			requestAnimationFrame(() => lb.classList.add('vis'));
			document.body.classList.add('evlb-lock');
			try { history.pushState({ evlb: 1 }, '', location.href); } catch (e) {}
			lb.querySelector('.evlb-close').focus();
		}

		function close(fromPop) {
			if (!lb) return;
			lb.classList.remove('vis');
			setTimeout(() => {
				lb.classList.remove('on');
				frame.src = 'about:blank';
			}, 320);
			document.body.classList.remove('evlb-lock');
			if (!fromPop) { try { if (history.state && history.state.evlb) history.back(); } catch (e) {} }
			if (lastFocus && lastFocus.focus) lastFocus.focus();
		}

		document.addEventListener('click', function (e) {
			const link = e.target.closest('.ev-card__link, .ev-card__title a');
			if (!link) return;
			const url = link.getAttribute('href');
			if (!url || url === '#') return;
			// Respect modified clicks (new tab intent) and non-primary buttons.
			if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
			e.preventDefault();
			open(url);
		});
	})();

})();
</script>
