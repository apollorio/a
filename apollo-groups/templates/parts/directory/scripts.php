<?php
/**
 * Directory Part — Scripts
 *
 * GSAP animations, tab switching, REST loading, search, trending, featured.
 * Expects: $rest_url, $nonce, $group_type_filter, $is_nucleos
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="bottom-space"></div>

<script>
(function() {
	'use strict';

	const REST      = '<?php echo esc_url( $rest_url ); ?>';
	const NONCE     = '<?php echo esc_js( $nonce ); ?>';
	const BASE_URL  = '<?php echo esc_url( home_url( '/grupo/' ) ); ?>';
	const IS_NUCLEO = <?php echo $is_nucleos ? 'true' : 'false'; ?>;

	const grid       = document.getElementById('groupsGrid');
	const loadMoreEl = document.getElementById('loadMore');
	const emptyEl    = document.getElementById('emptyState');
	const trendingEl = document.getElementById('trendingScroll');
	const featuredEl = document.getElementById('featuredCard');

	let page       = 1;
	let filter     = 'all';
	let searchTerm = '';

	const h = () => ({ 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' });
	const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

	/* ═══ Card HTML ═══ */
	function cardHTML(g, idx) {
		const type  = (g.type || 'comuna');
		const cover = g.cover_url || 'https://images.unsplash.com/photo-1545128485-c400e7702796?w=600&q=75';
		const desc  = (g.description || '').substring(0, 100);
		const count = g.member_count || 0;
		const live  = g.active_now || 0;

		let avatarsHTML = '';
		if (g.recent_members && g.recent_members.length) {
			avatarsHTML = g.recent_members.slice(0, 3).map(m =>
				'<div class="av"><img src="' + esc(m.avatar_url || '') + '" alt="" loading="lazy"></div>'
			).join('');
			if (count > 3) avatarsHTML += '<div class="av-more">+' + (count - 3) + '</div>';
		}

		return '<a href="' + BASE_URL + esc(g.slug || g.id) + '" class="card g-fade">' +
			'<span class="card-index">' + String(idx + 1).padStart(2, '0') + '</span>' +
			'<div class="card-cover">' +
				'<img src="' + esc(cover) + '" alt="' + esc(g.name) + '" loading="lazy">' +
				'<span class="card-type ' + type + '">' + esc(type) + '</span>' +
				(live > 0 ? '<span class="card-live"><span class="dot pulse"></span>' + live + ' on</span>' : '') +
			'</div>' +
			'<div class="card-body">' +
				'<div class="card-name">' + esc(g.name) + '</div>' +
				'<div class="card-desc">' + esc(desc) + '</div>' +
				'<div class="card-footer">' +
					'<div class="card-avatars">' + avatarsHTML + '</div>' +
					'<span class="card-members"><i class="ri-group-line"></i> ' + count + '</span>' +
				'</div>' +
			'</div>' +
		'</a>';
	}

	/* ═══ Trending chip HTML ═══ */
	function chipHTML(g) {
		const icon = g.avatar_url || g.cover_url || '';
		const count = g.member_count || 0;
		return '<a href="' + BASE_URL + esc(g.slug || g.id) + '" class="trending-chip">' +
			'<div class="trending-chip-icon"><img src="' + esc(icon) + '" alt="" loading="lazy"></div>' +
			'<div class="trending-chip-info">' +
				'<span class="trending-chip-name">' + esc(g.name) + '</span>' +
				'<span class="trending-chip-meta">' + count + ' membros</span>' +
			'</div>' +
		'</a>';
	}

	/* ═══ Featured card HTML ═══ */
	function featuredHTML(g) {
		const cover = g.cover_url || 'https://images.unsplash.com/photo-1545128485-c400e7702796?w=1200&q=75';
		const type  = (g.type || 'comuna');
		const desc  = (g.description || '').substring(0, 160);
		const count = g.member_count || 0;

		let avatarsHTML = '';
		if (g.recent_members && g.recent_members.length) {
			avatarsHTML = g.recent_members.slice(0, 4).map(m =>
				'<div class="av"><img src="' + esc(m.avatar_url || '') + '" alt="" loading="lazy"></div>'
			).join('');
		}

		return '<div class="featured-cover"><img src="' + esc(cover) + '" alt="" loading="lazy"></div>' +
			'<span class="featured-badge"><i class="ri-star-line"></i> Destaque</span>' +
			'<div class="featured-overlay">' +
				'<div class="featured-type">' + esc(type) + '</div>' +
				'<div class="featured-name">' + esc(g.name) + '</div>' +
				'<div class="featured-desc">' + esc(desc) + '</div>' +
				'<div class="featured-meta">' +
					'<div class="featured-avatars">' + avatarsHTML + '</div>' +
					'<span class="featured-stat"><i class="ri-group-line"></i> ' + count + '</span>' +
				'</div>' +
			'</div>';
	}

	/* ═══ Load directory ═══ */
	async function loadGroups(p, append) {
		if (!append) {
			grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px 0;"><div style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .6s linear infinite;margin:0 auto;"></div></div><style>@keyframes spin{to{transform:rotate(360deg)}}</style>';
		}

		let url = REST + '?page=' + p + '&per_page=12';
		if (filter === 'my')      url = REST + '/my?page=' + p + '&per_page=12';
		if (filter === 'comuna')  url += '&type=comuna';
		if (filter === 'nucleo')  url += '&type=nucleo';
		if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);

		try {
			const res  = await fetch(url, { headers: h(), credentials: 'same-origin' });
			const data = await res.json();

			if (!append) grid.innerHTML = '';

			if (data.length === 0 && p === 1) {
				emptyEl.style.display = '';
				loadMoreEl.style.display = 'none';
				return;
			}
			emptyEl.style.display = 'none';

			const startIdx = append ? grid.querySelectorAll('.card').length : 0;
			data.forEach((g, i) => {
				grid.insertAdjacentHTML('beforeend', cardHTML(g, startIdx + i));
			});

			loadMoreEl.style.display = data.length >= 12 ? '' : 'none';

			/* GSAP stagger cards */
			if (typeof gsap !== 'undefined') {
				gsap.from(grid.querySelectorAll('.card.g-fade'), {
					opacity: 0, y: 30, stagger: 0.06, duration: 0.5,
					ease: 'power2.out',
					onComplete: function() {
						grid.querySelectorAll('.card.g-fade').forEach(el => {
							el.style.opacity = ''; el.style.transform = '';
							el.classList.remove('g-fade');
						});
					}
				});
			}
		} catch (e) { console.error('loadGroups:', e); }
	}

	/* ═══ Load trending ═══ */
	async function loadTrending() {
		try {
			const res  = await fetch(REST + '?per_page=8&orderby=member_count&order=desc', { headers: h(), credentials: 'same-origin' });
			const data = await res.json();
			if (data.length) {
				trendingEl.innerHTML = data.map(g => chipHTML(g)).join('');
			}
		} catch (e) { console.error('loadTrending:', e); }
	}

	/* ═══ Load featured ═══ */
	async function loadFeatured() {
		try {
			const res  = await fetch(REST + '?per_page=1&orderby=member_count&order=desc&featured=1', { headers: h(), credentials: 'same-origin' });
			const data = await res.json();
			if (data.length) {
				featuredEl.innerHTML = featuredHTML(data[0]);
				featuredEl.style.display = '';
				featuredEl.onclick = () => window.location.href = BASE_URL + (data[0].slug || data[0].id);
			}
		} catch (e) { console.error('loadFeatured:', e); }
	}

	/* ═══ Init ═══ */
	document.addEventListener('DOMContentLoaded', () => {
		/* — Loader out — */
		const loader = document.getElementById('pageLoader');
		if (loader && typeof gsap !== 'undefined') {
			gsap.to(loader, { scaleY: 0, duration: .8, ease: 'power4.inOut', delay: .4,
				transformOrigin: 'bottom', onComplete: () => loader.style.display = 'none' });
		} else if (loader) {
			setTimeout(() => loader.style.display = 'none', 600);
		}

		/* — GSAP fade-ins — */
		if (typeof gsap !== 'undefined') {
			gsap.registerPlugin(ScrollTrigger);

			gsap.utils.toArray('.g-fade').forEach(el => {
				gsap.to(el, {
					opacity: 1, y: 0, duration: .7, ease: 'power2.out',
					scrollTrigger: { trigger: el, start: 'top 90%', once: true }
				});
			});

			/* Hero bg-text parallax */
			const bgText = document.getElementById('heroBgText');
			if (bgText) {
				gsap.to(bgText, {
					x: -200, ease: 'none',
					scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true }
				});
			}
		}

		/* — Tabs — */
		document.querySelectorAll('#dirTabs .tab').forEach(tab => {
			tab.addEventListener('click', () => {
				document.querySelectorAll('#dirTabs .tab').forEach(t => t.classList.remove('active'));
				tab.classList.add('active');
				filter = tab.dataset.filter;
				page = 1;
				loadGroups(1, false);
			});
		});

		/* — Search — */
		let debounce;
		document.getElementById('searchInput').addEventListener('input', function() {
			clearTimeout(debounce);
			debounce = setTimeout(() => {
				searchTerm = this.value.trim();
				page = 1;
				loadGroups(1, false);
			}, 300);
		});

		/* — Load data — */
		loadTrending();
		loadFeatured();
		loadGroups(1, false);
	});

	/* ═══ Public API ═══ */
	window._apolloDir = {
		loadMore: () => { page++; loadGroups(page, true); }
	};

})();
</script>

</body>
</html>
