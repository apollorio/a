/* ============================================================================
   APOLLO LUX PANELS — vanilla JS (no jQuery dependency for logic).
   Tab switching, WP media pickers, repeaters (tracks / amenities / gallery),
   and Leaflet map preview. Enqueued only on evento/dj/loc edit screens.
   ========================================================================== */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var root = document.querySelector('.apollo-lux');
    if (!root) return;

    /* ── Tabs ── */
    var tabs = root.querySelectorAll('.lux-tab');
    var panels = root.querySelectorAll('.lux-panel');
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var target = tab.getAttribute('data-tab');
        tabs.forEach(function (t) { t.classList.toggle('active', t === tab); });
        panels.forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-panel') === target); });
        try { window.localStorage.setItem('apolloLuxTab_' + root.getAttribute('data-cpt'), target); } catch (e) {}
      });
    });
    // restore last tab
    try {
      var saved = window.localStorage.getItem('apolloLuxTab_' + root.getAttribute('data-cpt'));
      if (saved) { var t = root.querySelector('.lux-tab[data-tab="' + saved + '"]'); if (t) t.click(); }
    } catch (e) {}

    /* ── Single image picker (cover / avatar / banner) ──
       Reused both for top-level fields (paired via data-target -> #id) and for
       repeater_card rows (paired via a sibling .lux-rep-media-input, no id —
       repeated rows can't rely on unique ids). bindMediaSingle() is also called
       on rows cloned by the repeater "add" handler below, so newly added track
       cards get a working cover picker too, not just the ones rendered on load. */
    function bindMediaSingle(box) {
      if (box.__apolloLuxBound) return;
      box.__apolloLuxBound = true;
      var target = box.getAttribute('data-target');
      var input = target ? document.getElementById(target) : box.parentElement.querySelector('.lux-rep-media-input');
      if (!input) return;
      function paint(url) {
        var img = box.querySelector('img');
        if (url) {
          if (!img) { img = document.createElement('img'); box.insertBefore(img, box.firstChild); }
          img.src = url; box.classList.add('has-img');
        } else if (img) { img.remove(); box.classList.remove('has-img'); }
      }
      box.addEventListener('click', function (e) {
        if (e.target.classList.contains('cover-clear')) { e.stopPropagation(); input.value = ''; paint(''); return; }
        if (!window.wp || !window.wp.media) return;
        var frame = window.wp.media({ title: 'Selecionar imagem', multiple: false, library: { type: 'image' } });
        frame.on('select', function () {
          var att = frame.state().get('selection').first().toJSON();
          input.value = att.id;
          paint((att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url));
        });
        frame.open();
      });
    }
    root.querySelectorAll('[data-lux-media="single"]').forEach(bindMediaSingle);

    /* ── Multi gallery picker (CSV of ids) ── */
    root.querySelectorAll('[data-lux-media="gallery"]').forEach(function (wrap) {
      var input = document.getElementById(wrap.getAttribute('data-target'));
      wrap.querySelectorAll('.frame').forEach(function (frame, idx) {
        frame.addEventListener('click', function (e) {
          if (e.target.classList.contains('rm')) {
            e.stopPropagation();
            var ids = (input.value ? input.value.split(',') : []);
            ids.splice(idx, 1); input.value = ids.join(',');
            var img = frame.querySelector('img'); if (img) img.remove(); frame.classList.remove('has-img');
            return;
          }
          if (!window.wp || !window.wp.media) return;
          var frameM = window.wp.media({ title: 'Selecionar imagem', multiple: false, library: { type: 'image' } });
          frameM.on('select', function () {
            var att = frameM.state().get('selection').first().toJSON();
            var ids = (input.value ? input.value.split(',').filter(Boolean) : []);
            ids[idx] = String(att.id); input.value = ids.join(',');
            var img = frame.querySelector('img');
            if (!img) { img = document.createElement('img'); frame.insertBefore(img, frame.firstChild); }
            img.src = (att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url);
            frame.classList.add('has-img');
          });
          frameM.open();
        });
      });
    });

    /* ── Generic repeaters (tracks, amenities, lineup) ── */
    root.querySelectorAll('[data-lux-rep]').forEach(function (rep) {
      var addBtn = rep.parentElement.querySelector('[data-lux-rep-add]');
      function bindRow(row) {
        var rm = row.querySelector('.lux-rep-rm');
        if (rm) rm.onclick = function () {
          if (rep.querySelectorAll('.lux-rep-row').length > 1) row.remove();
          else row.querySelectorAll('input,select').forEach(function (i) { i.value = ''; });
        };
      }
      rep.querySelectorAll('.lux-rep-row').forEach(bindRow);
      rep.querySelectorAll('[data-lux-media="single"]').forEach(bindMediaSingle);

      if (addBtn) addBtn.addEventListener('click', function () {
        // Prefer the blank <template> (repeater_card rows) so a new row is
        // never a copy of the last row's values, including an attached cover.
        var tpl = rep.parentElement.querySelector('template[data-lux-rep-tpl="' + rep.getAttribute('data-lux-rep') + '"]');
        var clone;
        if (tpl && tpl.content) {
          clone = document.importNode(tpl.content, true).firstElementChild;
        } else {
          var first = rep.querySelector('.lux-rep-row');
          clone = first.cloneNode(true);
          clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
          clone.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
          clone.querySelectorAll('.cover-upload').forEach(function (b) {
            b.classList.remove('has-img');
            var img = b.querySelector('img'); if (img) img.remove();
          });
        }
        rep.appendChild(clone);
        bindRow(clone);
        clone.querySelectorAll('[data-lux-media="single"]').forEach(bindMediaSingle);
      });
    });

    /* ── Co-authors multi-select (search + chips + listbox, all users) ── */
    root.querySelectorAll('[data-lux-coauthors]').forEach(function (wrap) {
      var hidden = document.getElementById(wrap.getAttribute('data-lux-coauthors'));
      var search = wrap.querySelector('.lux-ca-search');
      var chips = wrap.querySelector('.lux-ca-chips');
      var list = wrap.querySelector('.lux-ca-list');
      if (!hidden || !list) return;

      var catalog = window.apolloLuxUsers || [];
      var selected = [];
      try { selected = JSON.parse(hidden.value || '[]'); } catch (e) { selected = []; }
      selected = selected.map(Number).filter(function (n) { return n > 0; });

      function esc(s) {
        var d = document.createElement('div');
        d.textContent = s === null || s === undefined ? '' : String(s);
        return d.innerHTML;
      }
      function sync() { hidden.value = JSON.stringify(selected); }
      function toggle(id) {
        id = Number(id);
        if (!id) return;
        var idx = selected.indexOf(id);
        if (idx > -1) selected.splice(idx, 1); else selected.push(id);
        sync();
        render(search ? search.value : '');
      }

      function render(filter) {
        var byId = {};
        catalog.forEach(function (u) { byId[u.id] = u; });

        chips.innerHTML = selected.map(function (id) {
          var u = byId[id] || { id: id, name: '#' + id, avatar: '' };
          return '<span class="lux-ca-chip" data-id="' + u.id + '">'
            + (u.avatar ? '<img src="' + esc(u.avatar) + '" alt="">' : '<i class="ri-user-line"></i>')
            + '<span>' + esc(u.name) + '</span>'
            + '<button type="button" data-rm="' + u.id + '" aria-label="Remover">&times;</button></span>';
        }).join('');

        var q = (filter || '').toLowerCase().trim();
        var allMatches = catalog.filter(function (u) {
          if (!q) return true;
          return (u.name && u.name.toLowerCase().indexOf(q) > -1)
            || (u.login && u.login.toLowerCase().indexOf(q) > -1)
            || (u.email && u.email.toLowerCase().indexOf(q) > -1);
        });
        var rows = allMatches.slice(0, 5);

        if (!rows.length) {
          list.innerHTML = '<div class="lux-ca-empty">Nenhum usuário encontrado</div>';
          return;
        }
        list.innerHTML = rows.map(function (u) {
          var on = selected.indexOf(Number(u.id)) > -1;
          return '<button type="button" class="lux-ca-opt' + (on ? ' is-on' : '') + '" role="option" aria-selected="' + (on ? 'true' : 'false') + '" data-id="' + u.id + '">'
            + (u.avatar ? '<img src="' + esc(u.avatar) + '" alt="">' : '<i class="ri-user-line"></i>')
            + '<span class="lux-ca-meta"><b>' + esc(u.name) + '</b><small>@' + esc(u.login) + '</small></span>'
            + (on ? '<i class="ri-check-line lux-ca-check"></i>' : '')
            + '</button>';
        }).join('') + (allMatches.length > rows.length
          ? '<div class="lux-ca-empty" style="padding:6px 9px;font-size:11px;">+' + (allMatches.length - rows.length) + ' — continue digitando para refinar</div>'
          : '');
      }

      chips.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-rm]');
        if (btn) toggle(btn.getAttribute('data-rm'));
      });
      list.addEventListener('click', function (e) {
        var btn = e.target.closest('.lux-ca-opt');
        if (btn) toggle(btn.getAttribute('data-id'));
      });
      if (search) search.addEventListener('input', function () { render(this.value); });

      render('');
    });

    /* ── Taxonomy multi-select (search + capped(5) + chips, e.g. Sons/Gêneros) ── */
    root.querySelectorAll('[data-lux-taxpick]').forEach(function (wrap) {
      var tax = wrap.getAttribute('data-lux-taxpick');
      var boxes = Array.prototype.slice.call(document.querySelectorAll('.lux-tax-cb[data-tax="' + tax + '"]'));
      var search = wrap.querySelector('.lux-ca-search');
      var chips = wrap.querySelector('.lux-ca-chips');
      var list = wrap.querySelector('.lux-ca-list');
      if (!boxes.length || !list) return;

      function esc(s) {
        var d = document.createElement('div');
        d.textContent = s === null || s === undefined ? '' : String(s);
        return d.innerHTML;
      }
      function toggle(id) {
        var cb = boxes.filter(function (b) { return b.value === String(id); })[0];
        if (!cb) return;
        cb.checked = !cb.checked;
        render(search ? search.value : '');
      }
      function render(filter) {
        chips.innerHTML = boxes.filter(function (b) { return b.checked; }).map(function (b) {
          return '<span class="lux-ca-chip" data-id="' + b.value + '"><span>' + esc(b.getAttribute('data-label') || b.value) + '</span>'
            + '<button type="button" data-rm="' + b.value + '" aria-label="Remover">&times;</button></span>';
        }).join('');

        /* Match /novo-evento: empty query → chips only (no option dump). */
        var q = (filter || '').toLowerCase().trim();
        if (!q.length) {
          list.innerHTML = '';
          return;
        }
        var allMatches = boxes.filter(function (b) {
          return (b.getAttribute('data-label') || b.value).toLowerCase().indexOf(q) > -1
            || String(b.value).toLowerCase().indexOf(q) > -1;
        });
        var rows = allMatches.slice(0, 5);
        if (!rows.length) {
          list.innerHTML = '<div class="lux-ca-empty">Nenhum resultado</div>';
          return;
        }
        list.innerHTML = rows.map(function (b) {
          var on = b.checked;
          return '<button type="button" class="lux-ca-opt' + (on ? ' is-on' : '') + '" role="option" aria-selected="' + (on ? 'true' : 'false') + '" data-id="' + b.value + '">'
            + '<span class="lux-ca-meta"><b>' + esc(b.getAttribute('data-label') || b.value) + '</b></span>'
            + (on ? '<i class="ri-check-line lux-ca-check"></i>' : '')
            + '</button>';
        }).join('') + (allMatches.length > rows.length
          ? '<div class="lux-ca-empty" style="padding:6px 9px;font-size:11px;">+' + (allMatches.length - rows.length) + ' — continue digitando para refinar</div>'
          : '');
      }

      chips.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-rm]');
        if (btn) toggle(btn.getAttribute('data-rm'));
      });
      list.addEventListener('click', function (e) {
        var btn = e.target.closest('.lux-ca-opt');
        if (btn) toggle(btn.getAttribute('data-id'));
      });
      if (search) search.addEventListener('input', function () { render(this.value); });

      render('');
    });

    /* ── Leaflet map preview (loc / event venue) ── */
    var mapEl = root.querySelector('[data-lux-map]');
    if (mapEl && window.L) {
      var latI = document.getElementById(mapEl.getAttribute('data-lat'));
      var lngI = document.getElementById(mapEl.getAttribute('data-lng'));
      var lat = parseFloat(latI && latI.value) || -22.9068;
      var lng = parseFloat(lngI && lngI.value) || -43.1729;
      var map = window.L.map(mapEl, { zoomControl: false, attributionControl: false, scrollWheelZoom: false, dragging: true }).setView([lat, lng], 14);
      window.L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 20 }).addTo(map);
      var icon = window.L.divIcon({ className: 'loc-marker-icon', html: '<div class="loc-marker-pulse"></div><div class="loc-marker"><i class="ri-map-pin-2-fill"></i></div>', iconSize: [26, 26], iconAnchor: [13, 13] });
      var marker = window.L.marker([lat, lng], { icon: icon, draggable: true }).addTo(map);
      marker.on('dragend', function () { var p = marker.getLatLng(); if (latI) latI.value = p.lat.toFixed(6); if (lngI) lngI.value = p.lng.toFixed(6); });
      function sync() { var la = parseFloat(latI && latI.value), lo = parseFloat(lngI && lngI.value); if (!isNaN(la) && !isNaN(lo)) { map.setView([la, lo], 14); marker.setLatLng([la, lo]); } }
      if (latI) latI.addEventListener('change', sync);
      if (lngI) lngI.addEventListener('change', sync);
      setTimeout(function () { map.invalidateSize(); }, 200);
    }
  });
})();
