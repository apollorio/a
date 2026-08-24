/**
 * Apollo Events — Map JS (Leaflet / OpenStreetMap)
 *
 * Initializes Leaflet maps with event markers.
 * No Google Maps — uses OSM tiles exclusively.
 *
 * @package Apollo\Event
 */

(function () {
	'use strict';

	const ApolloMap = {

		config: window.apolloEvents || {},
		maps: {},

		init() {
			this.initEventMap();
			this.initSingleMap();
		},

		// ─── Event Listing Map ───────────────────────────────────────────

		initEventMap() {
			const container = document.getElementById('a-eve-map');
			if (!container || typeof L === 'undefined') return;

			let markers;
			try {
				markers = JSON.parse(container.dataset.markers || '[]');
			} catch (e) {
				console.error('Apollo Events: Invalid markers data');
				return;
			}

			if (!markers.length) {
				container.innerHTML = '<p style="text-align:center;padding:40px;color:#666;">Nenhum evento com localização definida.</p>';
				return;
			}

			const map = L.map(container).setView(
				[markers[0].lat, markers[0].lng],
				12
			);

			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
				maxZoom: 19,
			}).addTo(map);

			const bounds = L.latLngBounds();

			markers.forEach(marker => {
				const lat = parseFloat(marker.lat);
				const lng = parseFloat(marker.lng);

				if (isNaN(lat) || isNaN(lng)) return;

				const latlng = L.latLng(lat, lng);
				bounds.extend(latlng);

				const m = L.marker(latlng).addTo(map);

				const popupContent = `
					<div class="a-eve-map-popup">
						<div class="a-eve-map-popup__title">
							<a href="${marker.url}">${this.escapeHtml(marker.title)}</a>
						</div>
						<div class="a-eve-map-popup__date">${marker.date || ''}</div>
						${marker.loc ? `<div>📍 ${this.escapeHtml(marker.loc)}</div>` : ''}
					</div>
				`;

				m.bindPopup(popupContent);
			});

			if (markers.length > 1) {
				map.fitBounds(bounds, { padding: [30, 30] });
			}

			this.maps.events = map;

			// Fix map size on show (when hidden initially)
			const observer = new MutationObserver(() => {
				if (container.offsetParent !== null) {
					map.invalidateSize();
				}
			});

			const mapContainer = container.closest('.a-eve-map-container');
			if (mapContainer) {
				observer.observe(mapContainer, { attributes: true, attributeFilter: ['style'] });
			}
		},

		// ─── Single Event Map ────────────────────────────────────────────

		initSingleMap() {
			const container = document.getElementById('a-eve-single-map');
			if (!container || typeof L === 'undefined') return;

			const lat = parseFloat(container.dataset.lat);
			const lng = parseFloat(container.dataset.lng);
			const title = container.dataset.title || '';

			if (isNaN(lat) || isNaN(lng)) return;

			const map = L.map(container).setView([lat, lng], 15);

			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
				maxZoom: 19,
			}).addTo(map);

			L.marker([lat, lng])
				.addTo(map)
				.bindPopup(`<strong>${this.escapeHtml(title)}</strong>`)
				.openPopup();

			this.maps.single = map;
		},

		// ─── Helpers ─────────────────────────────────────────────────────

		escapeHtml(str) {
			const div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		},
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => ApolloMap.init());
	} else {
		ApolloMap.init();
	}
})();
