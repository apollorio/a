<?php

/**
 * Template Part: DJ Archive — Scripts
 *
 * Client-side filtering (sound, search), GSAP page-loader,
 * hero entrance, card stagger, toolbar scroll shadow.
 *
 * @package Apollo\DJs
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
			.to('.dj-hero__breadcrumb', {
				opacity: 1,
				y: 0,
				duration: 0.5
			})
			.to('.dj-hero__title', {
				opacity: 1,
				y: 0,
				duration: 0.65
			}, '-=0.3')
			.to('.dj-hero__sub', {
				opacity: 1,
				y: 0,
				duration: 0.55
			}, '-=0.35')
			.to('.dj-hero__stat', {
				opacity: 1,
				y: 0,
				duration: 0.5
			}, '-=0.25');

		/* ───────────────────────────────────────────
			2. GSAP — Card Stagger
			─────────────────────────────────────────── */
		function revealCards() {
			const cards = document.querySelectorAll('.dj-card:not(.hidden):not(.visible)');
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

		/* ───────────────────────────────────────────
			3. Filter State
			─────────────────────────────────────────── */
		let activeSound = 'all';
		let searchTerm = '';

		const cards = () => document.querySelectorAll('.dj-card');
		const counter = document.getElementById('dj-counter');
		const emptyLive = document.getElementById('dj-empty-live');
		const emptyStatic = document.getElementById('dj-empty');
		const filtersInfo = document.getElementById('dj-active-filters');
		const filtersText = document.getElementById('dj-active-text');

		/* ───────────────────────────────────────────
			4. Apply Filters
			─────────────────────────────────────────── */
		function applyFilters() {
			let visible = 0;

			cards().forEach(card => {
				let show = true;

				// Sound
				if (activeSound !== 'all') {
					const cardSounds = (card.dataset.sounds || '').split(',');
					if (!cardSounds.includes(activeSound)) show = false;
				}

				// Search
				if (show && searchTerm) {
					const hay = card.dataset.search || '';
					if (!hay.includes(searchTerm)) show = false;
				}

				card.classList.toggle('hidden', !show);
				if (show) visible++;
			});

			if (counter) counter.textContent = visible;

			const anyFilters = activeSound !== 'all' || searchTerm;
			if (emptyLive) emptyLive.classList.toggle('visible', visible === 0 && anyFilters);
			if (emptyStatic) emptyStatic.style.display = visible === 0 && !anyFilters ? 'flex' : 'none';

			if (filtersInfo && filtersText) {
				if (anyFilters) {
					const parts = [];
					if (activeSound !== 'all') parts.push(activeSound);
					if (searchTerm) parts.push('"' + searchTerm + '"');
					filtersText.textContent = visible + ' resultado' + (visible !== 1 ? 's' : '') + ' — ' + parts.join(' · ');
					filtersInfo.classList.add('visible');
				} else {
					filtersInfo.classList.remove('visible');
				}
			}

			// Re-reveal newly visible cards
			document.querySelectorAll('.dj-card:not(.hidden)').forEach(c => {
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
		document.querySelectorAll('#dj-sound-pills .dj-pill').forEach(pill => {
			pill.addEventListener('click', () => {
				document.querySelectorAll('#dj-sound-pills .dj-pill').forEach(p => p.classList.remove('active'));
				pill.classList.add('active');
				activeSound = pill.dataset.sound;
				applyFilters();
			});
		});

		/* ───────────────────────────────────────────
			6. Search
			─────────────────────────────────────────── */
		const searchInput = document.getElementById('dj-search');
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
			7. Clear All
			─────────────────────────────────────────── */
		function clearAll() {
			activeSound = 'all';
			searchTerm = '';
			document.querySelectorAll('#dj-sound-pills .dj-pill').forEach((p, i) => p.classList.toggle('active', i === 0));
			if (searchInput) searchInput.value = '';
			applyFilters();
		}

		document.getElementById('dj-clear-all')?.addEventListener('click', clearAll);
		document.getElementById('dj-clear-all-inline')?.addEventListener('click', clearAll);
		document.querySelector('.dj-empty__clear')?.addEventListener('click', clearAll);

		/* ───────────────────────────────────────────
			8. Toolbar Scroll Shadow
			─────────────────────────────────────────── */
		const toolbar = document.getElementById('dj-toolbar');
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
