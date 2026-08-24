/**
 * APOLLO::RIO — HOME / map.js
 * Leaflet map init — reuses apollo-maps REST endpoint (/map/explorer)
 * Uses apollo-maps registered Leaflet + CARTO tiles config.
 */
;(function () {
    'use strict';

    function waitForLeaflet(cb, n) {
        n = n || 0;
        if (typeof L !== 'undefined') { cb(); return; }
        if (n < 80) setTimeout(function () { waitForLeaflet(cb, n + 1); }, 50);
    }

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        } else {
            fn();
        }
    }

    ready(function () {
        waitForLeaflet(function () {
            var el = document.getElementById('nhMap');
            if (!el) return;

            var lat  = parseFloat(el.dataset.lat  || '-22.9502');
            var lng  = parseFloat(el.dataset.lng  || '-43.1903');
            var zoom = parseInt(el.dataset.zoom   || '12', 10);

            var map = L.map(el, {
                center: [lat, lng],
                zoom: zoom,
                zoomControl: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                touchZoom: false,
                boxZoom: false,
                keyboard: false,
                dragging: true
            });

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            /* Pulse icon factory (home-specific gradient circles) */
            function makePulseIcon() {
                return L.divIcon({
                    className: '',
                    html: '<div class="nh-map-pulse-wrap">' +
                        '<div class="nh-map-pulse-ring nh-pulse-a"></div>' +
                        '<div class="nh-map-pulse-ring nh-pulse-b"></div>' +
                        '</div>',
                    iconSize: [80, 80],
                    iconAnchor: [40, 40]
                });
            }

            /* Fetch from apollo-maps REST endpoint if available */
            var cfg = window.apolloMapsConfig;
            if (cfg && cfg.restUrl) {
                var url = cfg.restUrl.replace(/\/$/, '') + '/map/explorer?per_page=30&upcoming=1&with_locs=0';
                fetch(url, { headers: { 'X-WP-Nonce': cfg.nonce || '' } })
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(r.statusText); })
                    .then(function (data) {
                        if (data && Array.isArray(data.events)) {
                            data.events.forEach(function (ev) {
                                if (typeof ev.lat === 'undefined') return;
                                L.marker([ev.lat, ev.lng], { icon: makePulseIcon() }).addTo(map);
                            });
                        }
                    })
                    .catch(function () { /* silent — map shows empty */ });
            }

            /* CARTO tile enforcer */
            var obs = new MutationObserver(function (muts) {
                muts.forEach(function (m) {
                    m.addedNodes.forEach(function (n) {
                        if (n.tagName === 'IMG' && n.src && n.src.indexOf('openstreetmap') !== -1) {
                            n.src = n.src.replace(/tile\.openstreetmap\.org/, 'a.basemaps.cartocdn.com/light_all');
                        }
                    });
                });
            });
            obs.observe(el, { childList: true, subtree: true });

            window.ApolloMapHome = map;
        });
    });
})();
