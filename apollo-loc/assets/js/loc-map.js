/**
 * loc-map.js — Mapa Leaflet/OSM para a single page do Local
 *
 * Inicializa mapa em qualquer .apollo-loc-map-wrap com data-* attributes.
 * Também suporta shortcode [apollo_map] com data-locs JSON.
 *
 * Requer: Leaflet 1.9.4 (carregado via wp_register_script).
 *
 * @package Apollo\Local
 */
(function () {
  'use strict';

  var TILE_URL  = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  var TILE_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

  function initMap(el) {
    var lat   = parseFloat(el.dataset.lat  || el.dataset.centerLat || -22.9068);
    var lng   = parseFloat(el.dataset.lng  || el.dataset.centerLng || -43.1729);
    var zoom  = parseInt(el.dataset.zoom || 15, 10);
    var name  = el.dataset.name || '';
    var locs  = null;

    if (el.dataset.locs) {
      try { locs = JSON.parse(el.dataset.locs); } catch (e) {}
    }

    var map = L.map(el, {
      center: [lat, lng],
      zoom: zoom,
      scrollWheelZoom: false,
      attributionControl: true,
    });

    L.tileLayer(TILE_URL, {
      attribution: TILE_ATTR,
      maxZoom: 19,
    }).addTo(map);

    // Marcador principal (single page)
    if (!locs || locs.length === 0) {
      var markerIcon = L.divIcon({
        className: 'apollo-map-marker',
        html: '<span class="ri-map-pin-2-fill"></span>',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32],
      });

      L.marker([lat, lng], { icon: markerIcon })
        .addTo(map)
        .bindPopup(name ? '<strong>' + name + '</strong>' : '')
        .openPopup();
    }

    // Múltiplos marcadores (shortcode [apollo_map])
    if (locs && locs.length > 0) {
      var bounds = [];
      locs.forEach(function (loc) {
        var m = L.marker([loc.lat, loc.lng])
          .addTo(map)
          .bindPopup('<a href="' + loc.url + '">' + loc.title + '</a>');
        bounds.push([loc.lat, loc.lng]);
      });
      if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40] });
      } else if (bounds.length === 1) {
        map.setView(bounds[0], zoom);
      }
    }

    // Acessibilidade: reativa scroll ao focar
    el.addEventListener('keydown', function () { map.scrollWheelZoom.enable(); });
    el.addEventListener('blur', function () { map.scrollWheelZoom.disable(); }, true);
  }

  function initAllMaps() {
    // Dados injetados pelo AssetLoader (single page)
    if (window.apolloLocMap) {
      var d = window.apolloLocMap;
      var singleEl = document.getElementById('apolloLocSingleMap');
      if (singleEl) {
        singleEl.dataset.lat  = d.lat;
        singleEl.dataset.lng  = d.lng;
        singleEl.dataset.zoom = d.zoom;
        singleEl.dataset.name = d.name;
        initMap(singleEl);
      }
    }

    // Shortcodes com data-locs (multiplos mapas na página)
    document.querySelectorAll('.apollo-loc-map-wrap').forEach(initMap);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllMaps, { once: true });
  } else {
    initAllMaps();
  }
})();
