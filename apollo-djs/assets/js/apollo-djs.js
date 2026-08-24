/**
 * Apollo DJs — Main JS
 *
 * Archive: search, filter by sound, carousel
 *
 * @package Apollo\DJs
 */

(function () {
	'use strict';

	const ApolloDJs = {

		config: window.apolloDJs || {},

		init() {
			this.initSearch();
			this.initFilters();
			this.initCarousels();
		},

		// ─── Search ──────────────────────────────────────────────────────

		initSearch() {
			const input = document.getElementById('a-dj-search');
			if (!input) return;

			let timeout;
			input.addEventListener('input', () => {
				clearTimeout(timeout);
				timeout = setTimeout(() => this.searchDJs(input.value.trim()), 400);
			});
		},

		async searchDJs(query) {
			const grid = document.getElementById('a-dj-grid');
			if (!grid) return;

			if (!query) {
				location.reload();
				return;
			}

			try {
				const res = await fetch(
					`${this.config.rest_url}/djs/buscar?q=${encodeURIComponent(query)}&per_page=20`,
					{ headers: { 'X-WP-Nonce': this.config.nonce } }
				);

				const djs = await res.json();
				this.renderGrid(grid, djs);
			} catch (e) {
				console.error('Apollo DJs: search error', e);
			}
		},

		// ─── Filters ─────────────────────────────────────────────────────

		initFilters() {
			document.querySelectorAll('.a-dj-filter').forEach(btn => {
				btn.addEventListener('click', (e) => {
					document.querySelectorAll('.a-dj-filter').forEach(b => b.classList.remove('active'));
					e.target.classList.add('active');

					const sound = e.target.dataset.sound;
					this.filterBySound(sound === 'all' ? '' : sound);
				});
			});
		},

		async filterBySound(sound) {
			const grid = document.getElementById('a-dj-grid');
			if (!grid) return;

			try {
				let url = `${this.config.rest_url}/djs?per_page=50`;
				if (sound) {
					url = `${this.config.rest_url}/djs/por-som/${encodeURIComponent(sound)}`;
				}

				const res = await fetch(url, {
					headers: { 'X-WP-Nonce': this.config.nonce }
				});

				const djs = await res.json();
				this.renderGrid(grid, djs);
			} catch (e) {
				console.error('Apollo DJs: filter error', e);
			}
		},

		// ─── Grid render ─────────────────────────────────────────────────

		renderGrid(container, djs) {
			if (!djs.length) {
				container.innerHTML = `<div class="a-dj-empty">${this.config.i18n?.no_events || 'Nenhum DJ encontrado.'}</div>`;
				return;
			}

			container.innerHTML = djs.map(dj => `
				<article class="a-dj-card" data-dj-id="${dj.id}">
					<a href="${dj.permalink}">
						<img class="a-dj-card__image"
							 src="${this.escapeHtml(dj.image)}"
							 alt="${this.escapeHtml(dj.title)}"
							 loading="lazy">
					</a>
					<div class="a-dj-card__body">
						<div class="a-dj-card__header">
							<h3 class="a-dj-card__name">
								<a href="${dj.permalink}">${this.escapeHtml(dj.title)}</a>
							</h3>
							${dj.verified ? '<span class="a-dj-card__verified"><i class="ri-verified-badge-fill"></i></span>' : ''}
						</div>
						${dj.bio_short ? `<p class="a-dj-card__bio">${this.escapeHtml(dj.bio_short)}</p>` : ''}
						${dj.sounds?.length ? `
							<div class="a-dj-card__sounds">
								${dj.sounds.slice(0, 4).map(s => `<span class="a-dj-card__sound-tag">${this.escapeHtml(s)}</span>`).join('')}
							</div>
						` : ''}
						<div class="a-dj-card__footer">
							<span class="a-dj-card__events-count">${dj.upcoming_events_count || 0} evento(s)</span>
						</div>
					</div>
				</article>
			`).join('');
		},

		// ─── Carousels ───────────────────────────────────────────────────

		initCarousels() {
			document.querySelectorAll('[data-carousel]').forEach(carousel => {
				const track = carousel.querySelector('.a-dj-carousel__track');
				const prev = carousel.querySelector('.a-dj-carousel__prev');
				const next = carousel.querySelector('.a-dj-carousel__next');

				if (!track) return;

				const slideWidth = 304; // 280 + 24 gap

				prev?.addEventListener('click', () => {
					track.scrollBy({ left: -slideWidth * 2, behavior: 'smooth' });
				});

				next?.addEventListener('click', () => {
					track.scrollBy({ left: slideWidth * 2, behavior: 'smooth' });
				});
			});
		},

		// ─── Helpers ─────────────────────────────────────────────────────

		escapeHtml(str) {
			if (!str) return '';
			const div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		},
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => ApolloDJs.init());
	} else {
		ApolloDJs.init();
	}
})();
