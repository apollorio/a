(function() {
  'use strict';

  var cfg = window.apolloMapsConfig || null;
  if (!cfg) { return; }

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  function waitForLeaflet(cb, n) {
    n = n || 0;
    if (typeof L !== 'undefined') { cb(); return; }
    if (n < 100) {
      setTimeout(function() { waitForLeaflet(cb, n + 1); }, 50);
    }
  }

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function(ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }

  function makeEventIcon() {
    return L.divIcon({
      className: '',
      html: '<div class="apollo-map-pulse-wrap">' +
        '<div class="apollo-map-pulse-ring"></div>' +
        '<div class="apollo-map-pulse-ring"></div>' +
        '<div class="apollo-map-pulse-dot"></div>' +
        '</div>',
      iconSize: [64, 64],
      iconAnchor: [32, 32]
    });
  }

  function makeLocIcon() {
    return L.divIcon({
      className: '',
      html: '<div class="apollo-map-marker-loc"></div>',
      iconSize: [18, 18],
      iconAnchor: [9, 9]
    });
  }

  function addEventMarkers(events, map, bounds) {
    var icon = makeEventIcon();
    events.forEach(function(ev) {
      if (!ev || typeof ev.lat === 'undefined' || typeof ev.lng === 'undefined') { return; }
      var marker = L.marker([ev.lat, ev.lng], { icon: icon }).addTo(map);
      if (bounds) { bounds.push([ev.lat, ev.lng]); }

      var title = escapeHtml(ev.title);
      var when = escapeHtml(ev.when || '');
      var loc = ev.loc ? escapeHtml(ev.loc.title || '') : '';
      var content = '<div class="apollo-map-popup">' +
        '<h4>' + title + '</h4>' +
        (when ? '<p>' + when + '</p>' : '') +
        (loc ? '<p>' + loc + '</p>' : '') +
        (ev.link ? '<p><a href="' + escapeHtml(ev.link) + '" target="_blank" rel="noopener">Abrir</a></p>' : '') +
        '</div>';
      marker.bindPopup(content, { closeButton: false });
    });
  }

  function addLocMarkers(locs, map, bounds) {
    var icon = makeLocIcon();
    locs.forEach(function(loc) {
      if (!loc || typeof loc.lat === 'undefined' || typeof loc.lng === 'undefined') { return; }
      var marker = L.marker([loc.lat, loc.lng], { icon: icon }).addTo(map);
      if (bounds) { bounds.push([loc.lat, loc.lng]); }
      var content = '<div class="apollo-map-popup">' +
        '<h4>' + escapeHtml(loc.title) + '</h4>' +
        (loc.address ? '<p>' + escapeHtml(loc.address) + '</p>' : '') +
        (loc.link ? '<p><a href="' + escapeHtml(loc.link) + '" target="_blank" rel="noopener">Abrir</a></p>' : '') +
        '</div>';
      marker.bindPopup(content, { closeButton: false });
    });
  }

  function renderMap(el) {
    var centerLat = parseFloat(el.dataset.centerLat || cfg.defaultCenter[0]);
    var centerLng = parseFloat(el.dataset.centerLng || cfg.defaultCenter[1]);
    var zoom = parseInt(el.dataset.zoom || cfg.defaultZoom, 10);
    var perPage = parseInt(el.dataset.perPage || '60', 10);
    var upcoming = el.dataset.upcoming !== '0';
    var showEvents = el.dataset.showEvents !== '0';
    var showLocs = el.dataset.showLocs !== '0';

    var map = L.map(el, {
      center: [centerLat, centerLng],
      zoom: zoom,
      zoomControl: false,
      attributionControl: false,
      scrollWheelZoom: true,
      minZoom: 3,
      maxZoom: 20,
    });

    L.tileLayer(cfg.tiles.url, cfg.tiles.options || {}).addTo(map);

    var bounds = [];
    var url = cfg.restUrl.replace(/\/$/, '') + '/map/explorer?per_page=' + perPage + '&upcoming=' + (upcoming ? 1 : 0) + '&with_locs=' + (showLocs ? 1 : 0);

    fetch(url, { headers: { 'X-WP-Nonce': cfg.nonce } })
      .then(function(resp) { return resp.ok ? resp.json() : Promise.reject(resp.statusText || 'error'); })
      .then(function(payload) {
        if (showEvents && payload && Array.isArray(payload.events)) {
          addEventMarkers(payload.events, map, bounds);
        }
        if (showLocs && payload && Array.isArray(payload.locs)) {
          addLocMarkers(payload.locs, map, bounds);
        }
        if (bounds.length) {
          map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
        }
      })
      .catch(function(err) {
        console.error('Apollo Maps REST error', err);
      });
  }

  ready(function() {
    waitForLeaflet(function() {
      document.querySelectorAll('.apollo-maps-canvas').forEach(renderMap);
    });
  });
})();
