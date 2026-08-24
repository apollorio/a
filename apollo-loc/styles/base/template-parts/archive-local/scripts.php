<?php

/**
 * Template Part: GPS Archive — Scripts
 *
 * Client-side filtering (type, area, search), GSAP page-loader,
 * hero entrance, card stagger, toolbar scroll shadow.
 *
 * @package Apollo\Local
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
	(function() {
		'use strict';

		/* ─── 0. Page Loader ─── */
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

		/* ─── 1. Hero Entrance ─── */
		const heroTl = gsap.timeline({
			delay: 0.35,
			defaults: {
				ease: 'power3.out'
			}
		});
		heroTl
			.to('.gps-hero__breadcrumb', {
				opacity: 1,
				y: 0,
				duration: 0.5
			})
			.to('.gps-hero__title', {
				opacity: 1,
				y: 0,
				duration: 0.65
			}, '-=0.3')
			.to('.gps-hero__sub', {
				opacity: 1,
				y: 0,
				duration: 0.55
			}, '-=0.35')
			.to('.gps-hero__stat', {
				opacity: 1,
				y: 0,
				duration: 0.5
			}, '-=0.25');

		/* ─── 2. Card Stagger ─── */
		function revealCards() {
			const cards = document.querySelectorAll('.gps-card:not(.hidden):not(.visible)');
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
		setTimeout(revealCards, 500);

		/* ─── 3. Filter State ─── */
		let activeType = 'all';
		let activeArea = 'all';
		let searchTerm = '';

		const cards = () => document.querySelectorAll('.gps-card');
		const counter = document.getElementById('gps-counter');
		const emptyLive = document.getElementById('gps-empty-live');
		const emptyStatic = document.getElementById('gps-empty');
		const filtersInfo = document.getElementById('gps-active-filters');
		const filtersText = document.getElementById('gps-active-text');

		/* ─── 4. Apply Filters ─── */
		function applyFilters() {
			let visible = 0;

			cards().forEach(card => {
				let show = true;

				if (activeType !== 'all') {
					const ct = (card.dataset.types || '').split(',');
					if (!ct.includes(activeType)) show = false;
				}

				if (show && activeArea !== 'all') {
					const ca = (card.dataset.areas || '').split(',');
					if (!ca.includes(activeArea)) show = false;
				}

				if (show && searchTerm) {
					if (!(card.dataset.search || '').includes(searchTerm)) show = false;
				}

				card.classList.toggle('hidden', !show);
				if (show) visible++;
			});

			if (counter) counter.textContent = visible;

			const any = activeType !== 'all' || activeArea !== 'all' || searchTerm;
			if (emptyLive) emptyLive.classList.toggle('visible', visible === 0 && any);
			if (emptyStatic) emptyStatic.style.display = visible === 0 && !any ? 'flex' : 'none';

			if (filtersInfo && filtersText) {
				if (any) {
					const parts = [];
					if (activeType !== 'all') parts.push(activeType);
					if (activeArea !== 'all') parts.push(activeArea);
					if (searchTerm) parts.push('"' + searchTerm + '"');
					filtersText.textContent = visible + ' resultado' + (visible !== 1 ? 's' : '') + ' — ' + parts.join(' · ');
					filtersInfo.classList.add('visible');
				} else {
					filtersInfo.classList.remove('visible');
				}
			}

			document.querySelectorAll('.gps-card:not(.hidden)').forEach(c => {
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

		/* ─── 5. Type Pills ─── */
		document.querySelectorAll('#gps-type-pills .gps-pill').forEach(pill => {
			pill.addEventListener('click', () => {
				document.querySelectorAll('#gps-type-pills .gps-pill').forEach(p => p.classList.remove('active'));
				pill.classList.add('active');
				activeType = pill.dataset.type;
				applyFilters();
			});
		});

		/* ─── 6. Area Filter ─── */
		const areaFilter = document.getElementById('gps-area-filter');
		if (areaFilter) {
			areaFilter.addEventListener('change', () => {
				activeArea = areaFilter.value;
				applyFilters();
			});
		}

		/* ─── 7. Search ─── */
		const searchInput = document.getElementById('gps-search');
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

		/* ─── 8. Clear All ─── */
		function clearAll() {
			activeType = 'all';
			activeArea = 'all';
			searchTerm = '';
			document.querySelectorAll('#gps-type-pills .gps-pill').forEach((p, i) => p.classList.toggle('active', i === 0));
			if (searchInput) searchInput.value = '';
			if (areaFilter) areaFilter.value = 'all';
			applyFilters();
		}

		document.getElementById('gps-clear-all')?.addEventListener('click', clearAll);
		document.getElementById('gps-clear-all-inline')?.addEventListener('click', clearAll);
		document.querySelector('.gps-empty__clear')?.addEventListener('click', clearAll);

		/* ─── 9. Toolbar Scroll ─── */
		const toolbar = document.getElementById('gps-toolbar');
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

	})();
</script>
