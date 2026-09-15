/* ═══════════════════════════════════════════════════════════════════════════
   APOLLO::RIO · Mapa explorer — PHASE 006
   ─────────────────────────────────────────────────────────────────────────
   Adapted from _official_layout/js/view.mapa.js for a real WordPress page
   instead of the mockup's SPA route:
     - Data comes from window.APOLLO_MAPA_PLACES (wp_localize_script, real
       events + loc CPT posts from includes/mapa-data.php) — no fetch() of
       data/map-places.json, no simulated.data.js.
     - Only 2 real categories (evento, local) — the mockup's 9-category
       filter-bloom (park/metro/beach/museum/airport/port/stadium + fixture
       nightlife) is not ported; nothing in the Apollo ecosystem backs it.
     - No favorite heart: apollo-fav is a real system elsewhere in the
       ecosystem, but wiring it here is out of scope for this pass, so the
       control is omitted rather than faked as inert UI.
     - This is a dedicated WP page (not an SPA view mounted/unmounted on
       route change), so scroll-lock is applied once on load, not toggled
       on an "apollo:view-change" event.
   Everything else — clustering, marker rendering, the draggable bottom
   sheet, filter bloom, rail carousel, geolocation neighbourhood lookup —
   is the same engine as the mockup.
   ═══════════════════════════════════════════════════════════════════════════ */
(function (w, d) {
  'use strict';

  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]; }); };
  var haptic = function (ms) { try { navigator.vibrate && navigator.vibrate(ms || 8); } catch (e) {} };

  var IC = {
    cal:   '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M9 1V3H15V1H17V3H21C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3H7V1H9ZM20 11H4V19H20V11ZM7 5H4V9H20V5H17V7H15V5H9V7H7V5Z"/></svg>',
    clock: '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM13 12H17V14H11V7H13V12Z"/></svg>',
    pin:   '<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>',
    nav:   '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M3.64 2.33L21.36 11.5a.5.5 0 0 1 0 .9L3.64 21.67A.5.5 0 0 1 3 21.2V14l12-2-12-2V2.8a.5.5 0 0 1 .64-.47z"/></svg>'
  };

  /* Real, fixed categories — see mapa-data.php. Not loaded from a JSON
     fixture, because there are only ever these two. */
  var FILTERS = [
    { id: 'evento', color: 'var(--accent)', icon: 'ri-ticket-2-fill', filterIcon: 'ri-ticket-2-line', label: 'Eventos' },
    { id: 'local', color: 'var(--muted)', icon: 'ri-map-pin-2-fill', filterIcon: 'ri-map-pin-2-line', label: 'Locais' }
  ];
  var FILTER_BY_ID = {};
  FILTERS.forEach(function (f) { FILTER_BY_ID[f.id] = f; });
  function themeOf(type) { return FILTER_BY_ID[type] || { id: type, color: 'var(--muted)', icon: 'ri-map-pin-line', label: type || '—', filterIcon: 'ri-map-pin-line' }; }

  function boot() {
    var host = d.getElementById('mapaApp');
    if (!host || typeof L === 'undefined') return;

    var $ = function (s) { return host.querySelector(s); };
    var $$ = function (s) { return Array.prototype.slice.call(host.querySelectorAll(s)); };

    var PLACES = Array.isArray(w.APOLLO_MAPA_PLACES) ? w.APOLLO_MAPA_PLACES : [];
    var map, activeId = null, currentFilter = 'all';
    var markerMap = {}, labelMap = {};
    var userLat = null, userLng = null;
    var RIO = [-22.9068, -43.1729];

    /* ══ MAPA ══ */
    function initMap() {
      map = L.map($('#map'), { zoomControl: false, attributionControl: false, tap: true }).setView(RIO, 12.2);
      L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 20 }).addTo(map);
      setTimeout(function () { map.flyTo(RIO, 12.8, { duration: 2.1, easeLinearity: .26 }); }, 700);
      map.on('zoomend', renderMarkers);
      map.on('click', function () { closeCard(); closeBloom(); });
      renderMarkers();
    }

    function getClusters(places) {
      var zoom = map ? map.getZoom() : 12;
      if (zoom >= 13) return places.map(function (p) { return { type: 'single', place: p }; });
      var threshold = zoom < 11 ? 56 : 38;
      var assigned = {}; var clusters = [];
      places.forEach(function (p, i) {
        if (assigned[i]) return;
        var pt = map.latLngToContainerPoint([p.location.lat, p.location.lng]);
        var group = [p]; assigned[i] = true;
        places.forEach(function (p2, j) {
          if (assigned[j]) return;
          var pt2 = map.latLngToContainerPoint([p2.location.lat, p2.location.lng]);
          if (Math.hypot(pt.x - pt2.x, pt.y - pt2.y) < threshold) { group.push(p2); assigned[j] = true; }
        });
        if (group.length === 1) clusters.push({ type: 'single', place: group[0] });
        else {
          var avgLat = group.reduce(function (a, p) { return a + p.location.lat; }, 0) / group.length;
          var avgLng = group.reduce(function (a, p) { return a + p.location.lng; }, 0) / group.length;
          var dominant = group.reduce(function (a, b) {
            return group.filter(function (x) { return x.type === a.type; }).length >= group.filter(function (x) { return x.type === b.type; }).length ? a : b;
          });
          clusters.push({ type: 'cluster', places: group, lat: avgLat, lng: avgLng, dominant: dominant });
        }
      });
      return clusters;
    }

    function buildMarkerHTML(p) {
      var t = themeOf(p.type);
      return '<div class="apl-m" id="am-' + esc(p.id) + '" style="background:' + t.color + '"><i class="' + t.icon + '"></i></div>';
    }

    function renderMarkers() {
      Object.keys(markerMap).forEach(function (k) { map.removeLayer(markerMap[k]); delete markerMap[k]; });
      Object.keys(labelMap).forEach(function (k) { map.removeLayer(labelMap[k]); delete labelMap[k]; });

      var list = currentFilter === 'all' ? PLACES : PLACES.filter(function (p) { return p.type === currentFilter; });
      var clusters = getClusters(list);

      clusters.forEach(function (c) {
        if (c.type === 'single') {
          var p = c.place;
          var icon = L.divIcon({ className: 'apollo-leaflet-icon', html: buildMarkerHTML(p), iconSize: [22, 22], iconAnchor: [11, 11] });
          var m = L.marker([p.location.lat, p.location.lng], { icon: icon, riseOnHover: true }).addTo(map);
          m.on('click', function (e) { L.DomEvent.stopPropagation(e); haptic(); openCard(p.id); });
          markerMap[p.id] = m;
        } else {
          var t = themeOf(c.dominant.type);
          var miniItems = c.places.slice(0, 5), n = miniItems.length;
          var totalSpan = (n - 1) * 14, startX = (64 - 17) / 2 - totalSpan / 2;
          var miniHTML = miniItems.map(function (mp, idx) {
            var mt = themeOf(mp.type);
            var tNorm = n > 1 ? (idx / (n - 1)) * 2 - 1 : 0;
            var rot = (tNorm * 18).toFixed(1), yOff = (Math.abs(tNorm) * 3).toFixed(1), lx = (startX + idx * 14).toFixed(1);
            return '<div class="apl-m-mini" style="left:' + lx + 'px;bottom:2px;background:' + mt.color + ';transform:rotate(' + rot + 'deg) translateY(' + yOff + 'px);z-index:' + (idx + 1) + ';"><i class="' + mt.icon + '"></i></div>';
          }).join('');
          var cicon = L.divIcon({ className: 'apollo-leaflet-icon', html: '<div class="apl-cluster-basket">' + miniHTML + '</div>', iconSize: [64, 28], iconAnchor: [32, 14] });
          var cm = L.marker([c.lat, c.lng], { icon: cicon }).addTo(map);
          cm.on('click', function (e) { L.DomEvent.stopPropagation(e); haptic(12); map.flyTo([c.lat, c.lng], map.getZoom() + 2, { duration: .9 }); });
          markerMap['cluster_' + c.lat] = cm;
        }
      });

      if (map.getZoom() >= 14) {
        list.forEach(function (p) {
          if (!markerMap[p.id]) return;
          var l = L.marker([p.location.lat, p.location.lng], {
            icon: L.divIcon({ className: 'apollo-leaflet-icon', html: '<span class="apl-label">' + esc(p.name) + '</span>', iconSize: [170, 18], iconAnchor: [85, -14] }),
            interactive: false, zIndexOffset: -1
          }).addTo(map);
          labelMap[p.id] = l;
        });
      }
    }

    /* ══ CARTÃO ── ancorado embaixo-centro, acima da sheet ══ */
    var CARD_GAP = 12, sheetH = 0;
    function cardBottom() { return sheetH + CARD_GAP; }

    function openCard(id) {
      if (activeId === id) { closeCard(); return; }
      closeCard(true);
      activeId = id;
      var p = PLACES.filter(function (x) { return x.id === id; })[0];
      if (!p) return;

      $$('.apl-m').forEach(function (el) { el.classList.remove('active'); var old = el.querySelector('.apl-pulse'); if (old) old.remove(); });
      w.requestAnimationFrame(function () {
        var el = d.getElementById('am-' + id);
        if (el) { el.classList.add('active'); var pulse = d.createElement('div'); pulse.className = 'apl-pulse'; pulse.style.background = themeOf(p.type).color; el.appendChild(pulse); }
      });

      var stage = $('#cardStage');
      stage.innerHTML = ''; stage.style.bottom = cardBottom() + 'px';
      stage.appendChild(buildCard(p));
      $('#mapScrim').classList.add('on');
      closeBloom();
    }

    function closeCard(silent) {
      $$('.apl-m').forEach(function (el) { el.classList.remove('active'); var pulse = el.querySelector('.apl-pulse'); if (pulse) pulse.remove(); });
      var stage = $('#cardStage'), card = stage.querySelector('.place-card');
      if (card) {
        if (!silent) { card.classList.add('out'); setTimeout(function () { stage.innerHTML = ''; stage.style.bottom = '-600px'; }, 280); }
        else { stage.innerHTML = ''; stage.style.bottom = '-600px'; }
      }
      $('#mapScrim').classList.remove('on');
      activeId = null;
    }

    function buildCard(place) {
      var card = d.createElement('article');
      card.className = 'place-card place-card--loading';
      card.setAttribute('aria-label', place.name);
      card.setAttribute('data-ap-card', '1');
      card.setAttribute('data-pid', place.id || '');
      if (place.url) card.setAttribute('data-ap-url', place.url);
      card.setAttribute('data-ap-title', place.name || '');

      var imgWrap = d.createElement('div'); imgWrap.className = 'card__imgwrap';
      var img = d.createElement('img'); img.className = 'card__img'; img.alt = place.name; img.loading = 'eager'; img.decoding = 'async';
      imgWrap.appendChild(img);
      var grad = d.createElement('div'); grad.className = 'card__gradient';

      var closeBtn = d.createElement('button'); closeBtn.type = 'button'; closeBtn.className = 'card__close';
      closeBtn.innerHTML = '<i class="ri-close-line"></i>';
      closeBtn.addEventListener('click', function (e) { e.stopPropagation(); haptic(6); closeCard(); });

      var content = d.createElement('div'); content.className = 'card__content';
      var detailsRows = place.hours
        ? '<div class="card__drow"><span class="card__icon">' + IC.clock + '</span><div class="card__dtxt"><strong>' + esc(place.hours) + '</strong></div></div>'
        : '';
      content.innerHTML =
        (place.shortRef ? '<div class="card__loctag">' + IC.pin + '<span>' + esc(place.shortRef) + '</span></div>' : '') +
        '<h2 class="card__name">' + esc(place.name) + '</h2>' +
        '<p class="card__category">' + esc(place.category) + '</p>' +
        (detailsRows ? '<div class="card__details">' + detailsRows + '</div>' : '') +
        '<div class="card__actions">' +
          (place.url ? '<a class="card__cta" href="' + esc(place.url) + '" style="text-decoration:none;">' + IC.cal + esc(place.category === 'Local' || place.category === 'local' ? 'Ver local' : 'Ver evento') + '</a>' : '') +
          '<button class="card__cta" type="button" data-uber style="' + (place.url ? 'background:var(--surface);color:var(--txt-heading);' : '') + '">' + IC.nav + 'Uber</button>' +
        '</div>';
      content.querySelector('[data-uber]').addEventListener('click', function (e) {
        e.stopPropagation(); haptic(10);
        var lat = place.location.lat, lng = place.location.lng;
        w.open('https://m.uber.com/ul/?action=setPickup&pickup=my_location&dropoff[latitude]=' + lat + '&dropoff[longitude]=' + lng + '&dropoff[nickname]=' + encodeURIComponent(place.name), '_blank', 'noopener');
      });

      card.appendChild(imgWrap); card.appendChild(closeBtn); card.appendChild(content);

      function detectLayout(im) {
        return new Promise(function (res) {
          function go() { var wI = im.naturalWidth, hI = im.naturalHeight; res((!wI || !hI || (wI / hI) < 0.92) ? 'overlay' : 'split'); }
          if (im.complete && im.naturalWidth) go();
          else { im.addEventListener('load', go, { once: true }); im.addEventListener('error', function () { res('overlay'); }, { once: true }); }
        });
      }
      function applyLayout() {
        detectLayout(img).then(function (layout) {
          imgWrap.classList.add('loaded'); img.classList.add('visible');
          card.classList.remove('place-card--loading'); card.classList.add('place-card--' + layout);
          if (layout === 'overlay') imgWrap.appendChild(grad);
        });
      }
      img.addEventListener('load', applyLayout, { once: true });
      img.addEventListener('error', function () { imgWrap.classList.add('loaded'); card.classList.remove('place-card--loading'); card.classList.add('place-card--overlay'); imgWrap.appendChild(grad); }, { once: true });
      img.src = place.image;
      return card;
    }

    /* ══ FILTER BLOOM — Todos + os 2 filtros reais (evento/local) ══ */
    var bloomOpen = false;
    function buildBloom() {
      var bloom = $('#filterBloom');
      var entries = [{ id: 'all', label: 'Todos', icon: 'ri-asterisk', color: null }].concat(
        FILTERS.map(function (f) { return { id: f.id, label: f.label, icon: f.filterIcon, color: f.color }; })
      );
      bloom.innerHTML = entries.map(function (f, i) {
        var count = f.id === 'all' ? PLACES.length : PLACES.filter(function (p) { return p.type === f.id; }).length;
        var isOn = currentFilter === f.id;
        return '<button class="fb-item' + (isOn ? ' on' : '') + '" data-filter="' + esc(f.id) + '" style="animation-delay:' + (i * 35) + 'ms">' +
          '<i class="fb-icon ' + esc(f.icon) + '"' + (f.color && !isOn ? ' style="color:' + f.color + '"' : '') + '></i>' +
          '<div class="fb-count">' + count + '</div>' +
          '<div class="fb-name">' + esc(f.label) + '</div>' +
        '</button>';
      }).join('');
      $$('.fb-item').forEach(function (el) {
        el.addEventListener('click', function () {
          haptic(8); currentFilter = el.getAttribute('data-filter'); closeBloom(); renderMarkers(); renderSheet();
        });
      });
    }
    function openBloom() { bloomOpen = true; buildBloom(); $('#filterBloom').classList.add('open'); $('#filterToggle').classList.add('on'); $('#bloomScrim').classList.add('on'); }
    function closeBloom() { bloomOpen = false; $('#filterBloom').classList.remove('open'); $('#filterToggle').classList.remove('on'); $('#bloomScrim').classList.remove('on'); }
    $('#filterToggle').addEventListener('click', function (e) { e.stopPropagation(); haptic(8); bloomOpen ? closeBloom() : openBloom(); });
    $('#bloomScrim').addEventListener('click', closeBloom);

    /* ══ SHEET — física com velocidade ══ */
    var MID = function () { return Math.round(w.innerHeight * .44); };
    var FULL = function () { return Math.round(w.innerHeight * .86); };
    var sheetState = 'hidden', dragging = false, dragY0 = 0, dragH0 = 0, lastY = 0, lastT = 0, velocity = 0;

    function initSheet() {
      var sheet = $('#mapSheet'), drag = $('#sheetDrag');
      function setDragging(on) {
        dragging = !!on;
        try { w.__apSheetDragging = dragging; } catch (e) { /* isolate */ }
      }
      function start(y) {
        setDragging(true);
        dragY0 = y; dragH0 = sheet.offsetHeight; lastY = y; lastT = Date.now(); velocity = 0;
        sheet.style.transition = 'none';
      }
      function move(y) {
        if (!dragging) return;
        var now = Date.now(); velocity = (lastY - y) / Math.max(1, now - lastT); lastY = y; lastT = now;
        var nh = Math.max(0, Math.min(FULL() + 24, dragH0 + (dragY0 - y)));
        sheet.style.height = nh + 'px'; sheetH = nh;
      }
      function end() {
        if (!dragging) return;
        setDragging(false);
        sheet.style.transition = 'height .52s cubic-bezier(.16,1,.3,1)';
        if (velocity > 1.1) snap('full');
        else if (velocity < -1.1) snap('hidden');
        else {
          var states = ['hidden', 'peek', 'mid', 'full'], vals = [0, 72, MID(), FULL()];
          var dists = vals.map(function (v) { return Math.abs(sheetH - v); });
          snap(states[dists.indexOf(Math.min.apply(Math, dists))]);
        }
      }
      /* Touch listeners stay on the HANDLE — never document-wide preventDefault
         (that bricked sheet-inner slide-to-scroll when dragging stuck true). */
      drag.addEventListener('touchstart', function (e) {
        if (!e.touches[0]) return;
        start(e.touches[0].clientY);
      }, { passive: true });
      drag.addEventListener('touchmove', function (e) {
        if (!dragging || !e.touches[0]) return;
        e.preventDefault();
        move(e.touches[0].clientY);
      }, { passive: false });
      drag.addEventListener('touchend', end);
      drag.addEventListener('touchcancel', end);
      d.addEventListener('apollo:touch-watchdog-clear', function () {
        if (dragging) setDragging(false);
      });
      drag.addEventListener('mousedown', function (e) { start(e.clientY); });
      d.addEventListener('mousemove', function (e) { if (dragging) move(e.clientY); });
      d.addEventListener('mouseup', end);
      drag.addEventListener('click', function () { haptic(6); snap({ hidden: 'peek', peek: 'mid', mid: 'full', full: 'peek' }[sheetState] || 'peek'); });
      renderSheet();
    }

    function snap(state) {
      sheetState = state;
      var h = { hidden: 0, peek: 72, mid: MID(), full: FULL() }[state] || 0;
      var sheet = $('#mapSheet');
      sheet.style.height = h + 'px'; sheetH = h;
      $('#placeSep').style.display = state === 'full' ? '' : 'none';
      $('#placeList').style.display = state === 'full' ? '' : 'none';
      updateFloaters();
    }
    function updateFloaters() {
      $('#fabWrap').style.bottom = (sheetH + 16) + 'px';
      var stage = $('#cardStage');
      if (activeId) stage.style.bottom = cardBottom() + 'px';
    }

    /* ══ cartões "Perto de você" (rail) + lista (full) — 100% de PLACES real ══ */
    function renderSheet() {
      var list = currentFilter === 'all' ? PLACES : PLACES.filter(function (p) { return p.type === currentFilter; });
      var titleFilter = FILTER_BY_ID[currentFilter];
      var title = currentFilter === 'all' ? 'Perto de você' : (titleFilter ? titleFilter.label : 'Locais');
      $('#sheetTitle').textContent = title;
      $('#sheetCount').textContent = list.length + (list.length === 1 ? ' local' : ' locais');

      var rail = $('#placeRail'), llist = $('#placeList');

      if (!list.length) {
        rail.innerHTML = '<div class="mapa-empty" style="width:100%;"><i class="ri-map-pin-line"></i><span>Nenhum local neste filtro</span></div>';
        llist.innerHTML = '';
      } else {
        rail.innerHTML = list.slice(0, 12).map(function (p) {
          var t = themeOf(p.type);
          var u = p.url || p.link || '';
          return '<div class="pcm" data-pid="' + esc(p.id) + '" data-ap-url="' + esc(u) + '" data-ap-title="' + esc(p.name) + '" data-ap-card="1">' +
            '<div class="pcm-img-wrap"><img class="pcm-img" src="' + esc(p.image) + '" alt="' + esc(p.name) + '" loading="lazy" decoding="async"></div>' +
            '<div class="pcm-body">' +
              '<div class="pcm-cat"><span class="pcm-cat-dot" style="background:' + t.color + '"></span><span style="color:' + t.color + '">' + esc(t.label) + '</span></div>' +
              '<div class="pcm-name">' + esc(p.name) + '</div>' +
              (p.hours ? '<div class="pcm-meta"><i class="ri-time-line"></i>' + esc(p.hours) + '</div>' : '') +
            '</div></div>';
        }).join('');

        llist.innerHTML = list.map(function (p) {
          var t = themeOf(p.type);
          var u = p.url || p.link || '';
          return '<div class="pr" data-pid="' + esc(p.id) + '" data-ap-url="' + esc(u) + '" data-ap-title="' + esc(p.name) + '" data-ap-card="1">' +
            '<img class="pr-thumb" src="' + esc(p.image) + '" alt="' + esc(p.name) + '" loading="lazy" decoding="async">' +
            '<div class="pr-info"><div class="pr-name">' + esc(p.name) + '</div>' +
            '<div class="pr-sub"><span class="pr-cat-dot" style="background:' + t.color + '"></span>' + esc(p.category) + (p.shortRef ? ' · ' + esc(p.shortRef) : '') + '</div></div>' +
            '<i class="ri-arrow-right-s-line pr-arr"></i></div>';
        }).join('');
      }

      $$('[data-pid]').forEach(function (el) {
        el.addEventListener('click', function () {
          var p = PLACES.filter(function (x) { return x.id === el.getAttribute('data-pid'); })[0];
          if (!p) return;
          haptic(8);
          map.flyTo([p.location.lat, p.location.lng], 16, { duration: 1.1 });
          setTimeout(function () { openCard(p.id); }, 500);
          snap('peek');
        });
      });
      setTimeout(initRail, 60);
    }

    /* ══ RAIL CAROUSEL (GSAP) — só arranca via Apollo.whenGsapReady, nunca em DOMContentLoaded ══ */
    var _railScrub = null, _railLoop = null;
    function _buildRailLoop(items, spacing) {
      var overlap = Math.ceil(1 / spacing);
      var startTime = items.length * spacing + 0.5;
      var loopTime = (items.length + overlap) * spacing + 1;
      var rawSeq = w.gsap.timeline({ paused: true });
      var seamless = w.gsap.timeline({ paused: true, repeat: -1, onRepeat: function () { this._time === this._dur && (this._tTime += this._dur - 0.01); } });
      var l = items.length + overlap * 2;
      w.gsap.set(items, { xPercent: 400, opacity: 0 });
      for (var i = 0; i < l; i++) {
        var idx = i % items.length, item = items[idx], t = i * spacing;
        rawSeq
          .fromTo(item, { opacity: 0 }, { opacity: 1, zIndex: 100, duration: .5, yoyo: true, repeat: 1, ease: 'power1.in', immediateRender: false }, t)
          .fromTo(item, { xPercent: 400 }, { xPercent: -400, duration: 1, ease: 'none', immediateRender: false }, t);
        if (i <= items.length) seamless.add('label' + i, t);
      }
      rawSeq.time(startTime);
      seamless
        .to(rawSeq, { time: loopTime, duration: loopTime - startTime, ease: 'none' })
        .fromTo(rawSeq, { time: overlap * spacing + 1 }, { time: startTime, duration: startTime - (overlap * spacing + 1), immediateRender: false, ease: 'none' });
      return seamless;
    }
    function _railStep(delta) {
      if (!_railScrub) return;
      _railScrub.vars.totalTime = (_railScrub.vars.totalTime || 0) + delta;
      _railScrub.invalidate().restart();
    }
    function initRail() {
      if (_railScrub) { _railScrub.kill(); _railScrub = null; }
      if (_railLoop) { _railLoop.kill(); _railLoop = null; }
      var railEl = $('#placeRail'), ctrlEl = $('#railControls');
      var items = railEl ? Array.prototype.slice.call(railEl.querySelectorAll('.pcm')) : [];
      if (items.length < 2) { if (ctrlEl) ctrlEl.style.display = 'none'; return; }
      if (ctrlEl) ctrlEl.style.display = '';

      _railLoop = _buildRailLoop(items, .1);
      _railScrub = w.gsap.to(_railLoop, { totalTime: 0, duration: .5, ease: 'power3', paused: true });

      var tx0 = null;
      railEl.addEventListener('touchstart', function (e) { tx0 = e.touches[0].clientX; }, { passive: true });
      railEl.addEventListener('touchend', function (e) {
        if (tx0 === null) return;
        var dx = tx0 - e.changedTouches[0].clientX;
        if (Math.abs(dx) > 20) _railStep(dx > 0 ? .1 : -.1);
        tx0 = null;
      }, { passive: true });
      var prev = $('#railPrev'), next = $('#railNext');
      if (prev) prev.onclick = function () { haptic(6); _railStep(-.1); };
      if (next) next.onclick = function () { haptic(6); _railStep(.1); };
    }
    function bootRail() {
      if (w.Apollo && typeof w.Apollo.whenGsapReady === 'function') w.Apollo.whenGsapReady(function () { if (w.gsap) initRail(); });
      else if (w.gsap) initRail();
    }

    /* ══ GEO — bairro aproximado por raio (sem serviço externo, só geometria) ══ */
    function detectNeighborhood(lat, lng) {
      var nb = [
        { name: 'Ipanema', lat: -22.983, lng: -43.199, r: .010 }, { name: 'Copacabana', lat: -22.971, lng: -43.182, r: .012 },
        { name: 'Leblon', lat: -22.987, lng: -43.213, r: .010 }, { name: 'Botafogo', lat: -22.951, lng: -43.180, r: .011 },
        { name: 'Lapa', lat: -22.913, lng: -43.183, r: .008 }, { name: 'Centro', lat: -22.903, lng: -43.175, r: .017 },
        { name: 'Santa Teresa', lat: -22.920, lng: -43.183, r: .009 }, { name: 'Flamengo', lat: -22.964, lng: -43.173, r: .009 },
        { name: 'Urca', lat: -22.950, lng: -43.162, r: .007 }, { name: 'Tijuca', lat: -22.924, lng: -43.234, r: .013 },
        { name: 'Barra da Tijuca', lat: -23.000, lng: -43.366, r: .026 }, { name: 'Gávea', lat: -22.972, lng: -43.222, r: .011 },
        { name: 'Jardim Botânico', lat: -22.967, lng: -43.223, r: .010 }, { name: 'Maracanã', lat: -22.912, lng: -43.230, r: .010 },
        { name: 'Porto Maravilha', lat: -22.896, lng: -43.181, r: .009 }, { name: 'Niterói', lat: -22.883, lng: -43.103, r: .032 },
        { name: 'Glória', lat: -22.946, lng: -43.183, r: .007 }, { name: 'Catete', lat: -22.925, lng: -43.175, r: .007 }
      ];
      var closest = null, minD = Infinity;
      nb.forEach(function (n) { var dd = Math.hypot(lat - n.lat, lng - n.lng); if (dd < n.r && dd < minD) { minD = dd; closest = n; } });
      if (closest) return closest.name + ', Rio';
      if (lat > -23.1 && lat < -22.7 && lng > -43.8 && lng < -43.1) return 'Rio de Janeiro';
      return 'Sua localização';
    }
    function initGeo() {
      if (!navigator.geolocation) return;
      navigator.geolocation.getCurrentPosition(function (pos) {
        userLat = pos.coords.latitude; userLng = pos.coords.longitude;
        $('#sheetSub').textContent = detectNeighborhood(userLat, userLng);
      }, function () {}, { timeout: 6000 });
    }

    $('#focusBtn').addEventListener('click', function () { haptic(10); map.flyTo(userLat && userLng ? [userLat, userLng] : RIO, 14, { duration: 1.2 }); });
    $('#mapScrim').addEventListener('click', function () { haptic(6); closeCard(); });

    /* Scroll-lock — página dedicada, ativado uma vez (não há "saída de view"
       SPA aqui: sair de /mapa é navegação real de página). */
    if (w.Apollo && typeof w.Apollo.lockScroll === 'function') w.Apollo.lockScroll(true);
    else d.documentElement.classList.add('mapa-scroll-lock');

    initMap();
    initSheet();
    initGeo();
    bootRail();
    setTimeout(function () { snap('peek'); }, 1200);
    w.requestAnimationFrame(function () { w.requestAnimationFrame(function () { if (map) map.invalidateSize(); }); });
  }

  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', boot);
  else boot();
}(window, document));
