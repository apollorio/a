/*!
 * Apollo Events — Create/Edit form wiring
 * ---------------------------------------------------------------------------
 * Owns the four widgets that the legacy simulation layer never connected to
 * real data. Loaded LAST so it always wins, and written defensively so it works
 * whether or not wp.media booted.
 *
 *   1. Cover image      — #coverUpload  → #ev-banner  (attachment id)
 *   2. Photo gallery    — [data-gallery-slot] → #ev-gallery (CSV of ids)
 *   3. Loc picker       — #ev-venue-search → #ev-loc-id + info block
 *   4. DJ picker        — #djSearch → addDJToLineup(name,…,djId) → #ev-dj-ids
 *
 * Why this file exists
 * --------------------
 * apollo-events-create-form.js was extracted from a static mockup:
 *   · nothing ever bound [data-gallery-slot] (photos were simply dead);
 *   · handleDJSearch() only added a FREE-TEXT name on Enter — it never searched
 *     the registered `dj` catalog, so the row carried no dj_id and _event_dj_ids
 *     was saved empty;
 *   · the loc field relied on a <datalist>, which gives no id and no feedback.
 *
 * Everything here writes into the SAME hidden inputs syncModel()/collectPayload()
 * already read, so the REST payload contract is unchanged.
 */
(function (w, d) {
  'use strict';

  var CFG = w.APOLLO_EVENT_FORM || {};
  var $id = function (id) { return d.getElementById(id); };

  /* ── DEBUG BEACON REMOVED 2026-08-17 ──────────────────────────────────────
     w.__apolloAgentDbg used to POST every payload to
     http://127.0.0.1:7514/ingest/… from the visitor's browser, plus a second
     POST to apollo/v1/_agent_debug. On an HTTPS site the first is blocked
     mixed content and the second is pure noise; neither can ever resolve
     anywhere except one developer's laptop.

     $apollo_rule.data_flow: no debug output in production, ever.

     Kept as an inert no-op rather than deleted outright because call sites
     remain in this file's older branches; a missing global would throw where
     the beacon merely wasted a request. The calls are being removed alongside
     this — when the count reaches zero, delete this stub too.
     Harness E27 fails the build if the network form ever returns. */
  w.__apolloAgentDbg = w.__apolloAgentDbg || function () {};

  function ready(fn) {
    if ('loading' !== d.readyState) { fn(); return; }
    d.addEventListener('DOMContentLoaded', fn, { once: true });
  }

  function toast(msg) {
    if ('function' === typeof w.toast) { w.toast(msg); }
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /** Re-run the legacy model sync so hidden inputs + receipt stay truthful. */
  function sync() {
    if ('function' === typeof w.syncModel) { try { w.syncModel(); } catch (e) {} }
    if ('function' === typeof w.renderReceipt) { try { w.renderReceipt(); } catch (e) {} }
  }

  /* ═══════════════════════ media picking ═══════════════════════ */

  /**
   * Role-aware image chooser.
   *
   * apollo (administrator) · MOD (editor) · cena+ (author) · cena (contributor)
   * see BOTH halves: "Upload imagem" on top, "Imagem de link" underneath.
   * Everyone else sees only the external-URL half — they may reference an image
   * hosted outside Apollo, but never write to the media library.
   *
   * The permission shown here is cosmetic; the real gate is upload_files on the
   * server, which is why the link path stores a URL instead of an attachment.
   *
   * @param {Object}   opts {title, multiple}
   * @param {Function} cb   Receives an array of {id|null, url}.
   */
  function pickImages(opts, cb) {
    // #region agent log
    w.__apolloAgentDbg({runId:'pre-fix',hypothesisId:'D',location:'create-wire.js:pickImages',message:'pickImages called',data:{title:(opts&&opts.title)||'',multiple:!!(opts&&opts.multiple),hasWpMedia:!!(w.wp&&w.wp.media),canUpload:!!CFG.canUpload}});
    // #endregion
    openPicker(opts || {}, cb);
  }

  /** Open the WP media library (privileged path). Always falls back to <input type=file>. */
  function openLibrary(options, cb) {
    try {
      if (w.wp && w.wp.media) {
        // #region agent log
        w.__apolloAgentDbg({runId:'pre-fix',hypothesisId:'D',location:'create-wire.js:openLibrary',message:'opening wp.media frame',data:{title:(options&&options.title)||''}});
        // #endregion
        var frame = w.wp.media({
          title: options.title || 'Selecionar imagem',
          button: { text: 'Usar' },
          multiple: !!options.multiple,
          library: { type: 'image' }
        });
        frame.on('select', function () {
          var picked = frame.state().get('selection').toJSON().map(function (a) {
            return {
              id: a.id,
              url: (a.sizes && a.sizes.medium && a.sizes.medium.url) || a.url
            };
          });
          // #region agent log
          w.__apolloAgentDbg({runId:'pre-fix',hypothesisId:'C',location:'create-wire.js:openLibrary:select',message:'library select',data:{count:picked.length,firstId:picked[0]?picked[0].id:null}});
          // #endregion
          cb(picked);
        });
        frame.open();
        return;
      }
    } catch (err) {
      // #region agent log
      w.__apolloAgentDbg({runId:'pre-fix',hypothesisId:'D',location:'create-wire.js:openLibrary:catch',message:'media frame threw',data:{err:String(err&&err.message||err)}});
      // #endregion
      /* media frame can throw when templates/scripts partially loaded */
    }
    // #region agent log
    w.__apolloAgentDbg({runId:'pre-fix',hypothesisId:'D',location:'create-wire.js:uploadFallback',message:'falling back to file input',data:{hasWpMedia:!!(w.wp&&w.wp.media)}});
    // #endregion
    uploadFallback(options, cb);
  }

  /* ─── the small two-half popup ─── */

  var picker = null;

  function buildPicker() {
    if (picker) { return picker; }

    var canUpload = !!CFG.canUpload;
    var el = d.createElement('div');
    el.className = 'apx-pick';
    el.setAttribute('aria-hidden', 'true');
    el.hidden = true;
    el.innerHTML =
      '<div class="apx-pick-bd" data-pick-close></div>'
      + '<div class="apx-pick-box" role="dialog" aria-modal="true" aria-label="Adicionar imagem">'
      + (canUpload
        ? '<button type="button" class="apx-pick-up" data-pick-upload>'
          + '<i class="ri-upload-cloud-2-line"></i><span>Upload imagem</span></button>'
        : '')
      + '<div class="apx-pick-link">'
      + '<div class="apx-pick-lbl">Imagem de link</div>'
      + '<div class="apx-pick-row">'
      + '<input type="url" class="apx-pick-in" data-pick-url placeholder="https://…" '
      + 'aria-label="URL da imagem" autocomplete="off" spellcheck="false">'
      + '<button type="button" class="apx-pick-go" data-pick-submit aria-label="Usar este link">'
      + '<i class="ri-send-plane-2-fill"></i></button>'
      + '</div></div></div>';

    d.body.appendChild(el);
    picker = el;

    el.addEventListener('click', function (e) {
      if (e.target.closest('[data-pick-close]')) { closePicker(); }
    });

    el.querySelector('[data-pick-upload]') &&
      el.querySelector('[data-pick-upload]').addEventListener('click', function () {
        var ctx = el.__ctx || {};
        closePicker();
        openLibrary(ctx.options || {}, ctx.cb || function () {});
      });

    var urlInput = el.querySelector('[data-pick-url]');
    function submitUrl() {
      var raw = (urlInput.value || '').trim();
      if (!raw) { urlInput.focus(); return; }
      if (!/^https?:\/\//i.test(raw)) {
        toast('Use um endereço começando com https://');
        urlInput.focus();
        return;
      }
      var ctx = el.__ctx || {};
      closePicker();
      /* id:null marks an externally hosted image — the server stores the URL. */
      (ctx.cb || function () {})([{ id: null, url: raw }]);
    }

    el.querySelector('[data-pick-submit]').addEventListener('click', submitUrl);
    urlInput.addEventListener('keydown', function (e) {
      if ('Enter' === e.key) { e.preventDefault(); submitUrl(); }
    });

    d.addEventListener('keydown', function (e) {
      if ('Escape' === e.key && !el.hidden) { closePicker(); }
    });

    return el;
  }

  function openPicker(options, cb) {
    var el = buildPicker();
    el.__ctx = { options: options, cb: cb };
    var input = el.querySelector('[data-pick-url]');
    if (input) { input.value = ''; }
    el.hidden = false;
    el.setAttribute('aria-hidden', 'false');
    /*
     * Force a reflow instead of waiting for requestAnimationFrame: rAF is
     * throttled to zero in a background/hidden tab, which left the popup
     * stuck at opacity:0 forever once the user switched away and back.
     */
    void el.offsetWidth;
    el.classList.add('is-open');
    if (input) { w.setTimeout(function () { input.focus(); }, 120); }
  }

  function closePicker() {
    if (!picker) { return; }
    picker.classList.remove('is-open');
    picker.setAttribute('aria-hidden', 'true');
    w.setTimeout(function () { if (picker) { picker.hidden = true; } }, 220);
  }

  /** Plain <input type=file> → POST /wp/v2/media. */
  function uploadFallback(options, cb) {
    var input = d.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.multiple = !!options.multiple;
    input.style.display = 'none';
    d.body.appendChild(input);

    input.addEventListener('change', function () {
      var files = Array.prototype.slice.call(input.files || []);
      if (!files.length) { input.remove(); return; }

      toast('Enviando imagem…');

      Promise.all(files.map(function (file) {
        var fd = new FormData();
        fd.append('file', file, file.name);
        return fetch('/wp-json/wp/v2/media', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-WP-Nonce': CFG.nonce || '' },
          body: fd
        })
          .then(function (r) { return r.ok ? r.json() : null; })
          .then(function (j) {
            if (!j || !j.id) { return null; }
            var url = (j.media_details && j.media_details.sizes && j.media_details.sizes.medium
              && j.media_details.sizes.medium.source_url) || j.source_url;
            return { id: j.id, url: url };
          })
          .catch(function () { return null; });
      })).then(function (results) {
        var ok = results.filter(Boolean);
        if (!ok.length) { toast('Falha ao enviar a imagem'); }
        else { cb(ok); }
        input.remove();
      });
    }, { once: true });

    input.click();
  }

  /* ═══════════════════════ 1. cover image ══════════════════════ */

  function wireCover() {
    var cover = $id('coverUpload');
    var hidden = $id('ev-banner');
    if (!cover || !hidden) { return; }

    cover.removeAttribute('onclick');
    cover.style.cursor = 'pointer';
    cover.setAttribute('role', 'button');
    cover.setAttribute('tabindex', '0');

    function open() {
      pickImages({ title: 'Capa do evento', multiple: false }, function (picked) {
        if (!picked.length) { return; }
        /* id for library uploads, raw URL for externally hosted images. */
        hidden.value = (null == picked[0].id) ? picked[0].url : String(picked[0].id);
        paintCover(picked[0].url);
        sync();
      });
    }

    cover.addEventListener('click', function (e) { e.preventDefault(); open(); });
    cover.addEventListener('keydown', function (e) {
      if ('Enter' === e.key || ' ' === e.key) { e.preventDefault(); open(); }
    });

    /* Edit mode: repaint the stored banner (id → resolve, URL → straight in). */
    if (hidden.value) {
      if (/^https?:\/\//i.test(hidden.value)) { paintCover(hidden.value); }
      else { paintStoredAttachment(hidden.value, paintCover); }
    }
  }

  function paintCover(url) {
    if ('function' === typeof w.setCover) { try { w.setCover(url); return; } catch (e) {} }
    var c = $id('coverUpload');
    if (!c) { return; }
    c.classList.add('has-cover');
    c.innerHTML = '<img src="' + esc(url) + '" alt="Capa do evento">'
      + '<i class="ri-image-edit-line"></i><span>Clique para trocar a capa</span>';
  }

  /** Resolve an attachment id to a URL so edit mode shows the real thumbnail. */
  function paintStoredAttachment(id, cb) {
    fetch('/wp-json/wp/v2/media/' + encodeURIComponent(id), { credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) {
        if (!j) { return; }
        var url = (j.media_details && j.media_details.sizes && j.media_details.sizes.medium
          && j.media_details.sizes.medium.source_url) || j.source_url;
        if (url) { cb(url); }
      })
      .catch(function () {});
  }

  /* ═══════════════════════ 2. photo gallery ════════════════════ */

  function wireGallery() {
    var box = $id('galleryImages');
    var hidden = $id('ev-gallery');
    if (!box || !hidden) { return; }

    box.addEventListener('click', function (e) {
      var slot = e.target.closest('[data-gallery-slot]');
      if (!slot || !box.contains(slot)) { return; }
      e.preventDefault();

      /* Clicking a filled slot clears it; an empty slot opens the picker. */
      if (slot.querySelector('img')) {
        slot.innerHTML = '<i class="ri-add-line"></i>';
        slot.classList.remove('has-img');
        writeGallery();
        return;
      }

      var free = countFreeSlots(box);
      pickImages({ title: 'Fotos do evento', multiple: true }, function (picked) {
        fillSlots(box, picked.slice(0, free));
        writeGallery();
      });
    });

    box.querySelectorAll('[data-gallery-slot]').forEach(function (slot) {
      slot.style.cursor = 'pointer';
      slot.setAttribute('role', 'button');
      slot.setAttribute('tabindex', '0');
      slot.setAttribute('aria-label', 'Adicionar foto');
    });

    /*
     * Edit mode: repaint stored ids. The slot is bound by INDEX up front —
     * resolving it inside the async callback would let two in-flight requests
     * race for the same empty slot and drop an image.
     */
    /*
     * The bridge hydrates every other field on edit load but never #ev-gallery,
     * so fall back to the edit payload — otherwise the slots render empty and
     * the next save wipes the stored gallery.
     */
    if (!hidden.value && CFG.editEvent && CFG.editEvent.gallery && CFG.editEvent.gallery.length) {
      hidden.value = CFG.editEvent.gallery.join(',');
    }

    if (hidden.value) {
      var slots = Array.prototype.slice.call(box.querySelectorAll('[data-gallery-slot]'));
      splitRefs(hidden.value).forEach(function (ref, i) {
        var slot = slots[i];
        if (!slot) { return; }
        if (/^https?:\/\//i.test(ref)) {
          paintSlot(slot, { id: ref, url: ref });
        } else {
          paintStoredAttachment(ref, function (url) {
            paintSlot(slot, { id: ref, url: url });
          });
        }
      });
    }

    function writeGallery() {
      var refs = Array.prototype.slice
        .call(box.querySelectorAll('img[data-att-id]'))
        .map(function (img) { return img.getAttribute('data-att-id'); })
        .filter(Boolean);
      hidden.value = refs.join(',');
      sync();
    }
  }

  /**
   * Split the stored gallery value into refs.
   *
   * Entries are attachment ids OR absolute URLs — and URLs can legitimately
   * contain commas, so a naive split(',') would shred them. Only split on
   * commas that are followed by another ref boundary.
   */
  function splitRefs(value) {
    return String(value || '')
      .split(/,(?=\s*(?:\d+\s*(?:,|$)|https?:\/\/))/i)
      .map(function (v) { return v.trim(); })
      .filter(Boolean);
  }

  function countFreeSlots(box) {
    return box.querySelectorAll('[data-gallery-slot]:not(.has-img)').length;
  }

  function fillSlots(box, picked) {
    var free = Array.prototype.slice.call(box.querySelectorAll('[data-gallery-slot]:not(.has-img)'));
    picked.forEach(function (img, i) {
      if (free[i]) { paintSlot(free[i], img); }
    });
  }

  function paintSlot(slot, img) {
    /* data-att-id is the contract syncModel() reads — it holds the attachment
       id for library uploads and the URL itself for external images. */
    var ref = (null == img.id) ? img.url : img.id;
    slot.innerHTML = '<img src="' + esc(img.url) + '" data-att-id="' + esc(ref) + '" alt="">';
    slot.classList.add('has-img');
  }

  /* ═════════════════════ shared combobox ═══════════════════════ */

  /**
   * Turn a text input into a filtered dropdown.
   *
   * @param {Object} o {input, source(), render(item), onPick(item), empty}
   */
  function combobox(o) {
    var input = o.input;
    if (!input || input.__apolloCombo) { return null; }
    input.__apolloCombo = true;
    input.setAttribute('autocomplete', 'off');

    var host = input.parentNode;
    if (host && 'static' === w.getComputedStyle(host).position) {
      host.style.position = 'relative';
    }
    /*
     * .as2 raises its own stacking context while open so the drop is never
     * clipped by a later sibling card. Mirror that on the combobox host.
     */
    if (host) { host.style.zIndex = '9999999'; }

    var menu = d.createElement('div');
    menu.className = 'apollo-combo';
    menu.setAttribute('role', 'listbox');
    menu.hidden = true;
    host.appendChild(menu);

    var items = [];

    function close() { menu.hidden = true; }

    function open(q) {
      var needle = String(q || '').trim().toLowerCase();
      items = o.source().filter(function (it) {
        return !needle || String(it.name || '').toLowerCase().indexOf(needle) !== -1;
      }).slice(0, 40);

      if (!items.length) {
        menu.innerHTML = '<div class="apollo-combo-empty">' + esc(o.empty || 'Nada encontrado') + '</div>';
        menu.hidden = false;
        // #region agent log
        __apolloComboDbg('empty', input, host, menu, 0);
        // #endregion
        return;
      }

      menu.innerHTML = items.map(function (it, i) {
        return '<div class="apollo-combo-opt" role="option" data-i="' + i + '">' + o.render(it) + '</div>';
      }).join('');
      menu.hidden = false;
      // #region agent log
      __apolloComboDbg('open', input, host, menu, items.length);
      // #endregion
    }

    // #region agent log
    function __apolloComboDbg(phase, inp, hst, mnu, count) {
      try {
        var card = inp && inp.closest ? inp.closest('.card') : null;
        var nextCard = card && card.nextElementSibling;
        while (nextCard && (!nextCard.classList || !nextCard.classList.contains('card'))) {
          nextCard = nextCard.nextElementSibling;
        }
        var csCard = card ? w.getComputedStyle(card) : null;
        var csHost = hst ? w.getComputedStyle(hst) : null;
        var csMenu = mnu ? w.getComputedStyle(mnu) : null;
        var csNext = nextCard ? w.getComputedStyle(nextCard) : null;
        var menuR = mnu && !mnu.hidden ? mnu.getBoundingClientRect() : null;
        var nextR = nextCard ? nextCard.getBoundingClientRect() : null;
        var overlapY = !!(menuR && nextR && menuR.bottom > nextR.top && menuR.top < nextR.bottom);
        var overlapX = !!(menuR && nextR && menuR.right > nextR.left && menuR.left < nextR.right);
        var elAtOpt2 = null;
        if (menuR && count >= 2) {
          var opt2 = mnu.querySelector('.apollo-combo-opt[data-i="1"]');
          if (opt2) {
            var r2 = opt2.getBoundingClientRect();
            var cx = (r2.left + r2.right) / 2;
            var cy = (r2.top + r2.bottom) / 2;
            var hit = d.elementFromPoint(cx, cy);
            elAtOpt2 = hit ? {
              tag: hit.tagName,
              cls: (hit.className && String(hit.className).slice(0, 80)) || '',
              id: hit.id || '',
              inMenu: !!hit.closest('.apollo-combo'),
              inNextCard: !!(nextCard && nextCard.contains(hit))
            } : null;
          }
        }
        /* PRODUCTION FIX (2026-08-05): this used to POST to
           http://127.0.0.1:7704/ingest/… on every combobox open — i.e. on
           every keystroke in the DJ/local pickers of the create-event form.
           From https://apollo.rio.br that is blocked twice over (mixed
           content, then CORS preflight on the custom X-Debug-Session-Id
           header) and buried the console under failed requests while the
           form was being used. The probe itself is still useful, so it now
           reports to the console instead of the network — same data, no
           cross-origin request, nothing to strip before release. */
        if (w.console && w.console.debug) {
          w.console.debug('[apollo] combo stack probe', {
            location: 'apollo-events-create-wire.js:combobox.open',
            data: {
              phase: phase,
              inputId: inp && inp.id,
              itemCount: count,
              cardIsolation: csCard ? csCard.isolation : null,
              cardZ: csCard ? csCard.zIndex : null,
              cardOverflow: csCard ? (csCard.overflow + '/' + csCard.overflowY) : null,
              hostZ: csHost ? csHost.zIndex : null,
              hostPos: csHost ? csHost.position : null,
              menuZ: csMenu ? csMenu.zIndex : null,
              menuHidden: !!(mnu && mnu.hidden),
              nextCardCls: nextCard ? String(nextCard.className).slice(0, 60) : null,
              nextCardZ: csNext ? csNext.zIndex : null,
              nextCardIsolation: csNext ? csNext.isolation : null,
              overlapNextCard: overlapX && overlapY,
              elAtOpt2: elAtOpt2
            },
            phase: phase,
            timestamp: Date.now()
          });
        }
      } catch (err) { /* ignore */ }
    }
    // #endregion

    input.addEventListener('input', function () { open(input.value); });
    input.addEventListener('focus', function () { open(input.value); });

    menu.addEventListener('mousedown', function (e) {
      /* mousedown (not click) so the input's blur cannot close us first. */
      var opt = e.target.closest('.apollo-combo-opt');
      if (!opt) { return; }
      e.preventDefault();
      var it = items[parseInt(opt.getAttribute('data-i'), 10)];
      if (it) { o.onPick(it); }
      close();
    });

    input.addEventListener('blur', function () { w.setTimeout(close, 120); });
    input.addEventListener('keydown', function (e) {
      if ('Escape' === e.key) { close(); }
    });

    return { open: open, close: close };
  }

  /* ═══════════════════════ 3. loc picker ═══════════════════════ */

  function wireLoc() {
    var input = $id('ev-venue-search');
    var hidden = $id('ev-loc-id');
    if (!input || !hidden) { return; }

    /* Combobox owns venue search — no native datalist. */
    input.removeAttribute('list');

    var locs = (CFG.locs || []);

    combobox({
      input: input,
      empty: locs.length ? 'Nenhum local encontrado' : 'Nenhum local cadastrado ainda',
      source: function () { return locs; },
      render: function (l) {
        var thumb = (l.images && l.images[0])
          ? '<img src="' + esc(l.images[0]) + '" alt="">'
          : '<span class="apollo-combo-ph"><i class="ri-map-pin-2-line"></i></span>';
        return thumb + '<span><b>' + esc(l.name) + '</b>'
          + (l.address ? '<small>' + esc(l.address) + '</small>' : '') + '</span>';
      },
      onPick: function (l) {
        input.value = l.name;
        hidden.value = String(l.id);
        applyLoc(l);
        sync();
      }
    });

    /* Edit mode: rehydrate from the stored loc id. */
    if (hidden.value) {
      var stored = locs.filter(function (l) { return String(l.id) === String(hidden.value); })[0];
      if (stored) {
        input.value = stored.name;
        applyLoc(stored);
      }
    }
  }

  function applyLoc(l) {
    var block = $id('venueInfoBlock');
    if (block) { block.style.display = ''; }

    var nameEl = $id('venueName');
    var addrEl = $id('venueAddress');
    if (nameEl) { nameEl.textContent = l.name || '—'; }
    if (addrEl) { addrEl.textContent = l.address || '—'; }

    var lat = $id('lat');
    var lon = $id('lon');
    if (lat && l.lat != null && '' !== l.lat) { lat.value = String(l.lat); }
    if (lon && l.lon != null && '' !== l.lon) { lon.value = String(l.lon); }

    var imgs = $id('venueImages');
    if (imgs) {
      imgs.innerHTML = (l.images || []).map(function (src, i) {
        return '<div class="frame"><img src="' + esc(src) + '" alt="Local ' + (i + 1) + '"></div>';
      }).join('');
    }

    /* Legacy weather widget keys off lat/lon. */
    if ('function' === typeof w.scheduleWeather) { try { w.scheduleWeather(); } catch (e) {} }
  }

  /* ═══════════════════════ 4. DJ picker ════════════════════════ */

  function wireDjs() {
    var input = $id('djSearch');
    if (!input) { return; }

    /* Replaces onkeypress="handleDJSearch(event)", which only ever added a
       free-text name with no dj_id — so nothing linked to the dj CPT. */
    input.removeAttribute('onkeypress');

    var djs = (CFG.djs || []);

    combobox({
      input: input,
      empty: djs.length ? 'Nenhum DJ encontrado' : 'Nenhum DJ cadastrado — use “Novo DJ”',
      source: function () {
        var chosen = pickedDjIds();
        return djs.filter(function (dj) { return chosen.indexOf(String(dj.id)) === -1; });
      },
      render: function (dj) {
        var thumb = dj.thumb
          ? '<img src="' + esc(dj.thumb) + '" alt="">'
          : '<span class="apollo-combo-ph">' + esc(String(dj.name || '?').slice(0, 2).toUpperCase()) + '</span>';
        return thumb + '<span><b>' + esc(dj.name) + '</b>'
          + (dj.handle ? '<small>@' + esc(String(dj.handle).replace(/^@/, '')) + '</small>' : '') + '</span>';
      },
      onPick: function (dj) {
        addDj(dj);
        input.value = '';
      }
    });

    /* Enter still works, but now only for a genuinely unlisted name. */
    input.addEventListener('keydown', function (e) {
      if ('Enter' !== e.key) { return; }
      e.preventDefault();
      var q = input.value.trim();
      if (!q) { return; }
      var exact = djs.filter(function (dj) {
        return String(dj.name || '').toLowerCase() === q.toLowerCase();
      })[0];
      if (exact) { addDj(exact); }
      else if ('function' === typeof w.addDJToLineup) { w.addDJToLineup(q); }
      input.value = '';
    });
  }

  function pickedDjIds() {
    var box = $id('lineupContainer');
    if (!box) { return []; }
    return Array.prototype.slice.call(box.querySelectorAll('.lineup-row[data-dj-id]'))
      .map(function (r) { return r.getAttribute('data-dj-id'); })
      .filter(Boolean);
  }

  function addDj(dj) {
    if ('function' !== typeof w.addDJToLineup) { return; }
    /* addDJToLineup(name, start, end, photo, djId, badge) — the 5th argument is
       what makes _event_dj_ids link to the real dj CPT. */
    w.addDJToLineup(dj.name, '', '', dj.thumb || '', String(dj.id), '');
    sync();
  }

  /* ══════════════════════════ styles ══════════════════════════ */

  function injectStyles() {
    if ($id('apollo-combo-css')) { return; }
    var css = d.createElement('style');
    css.id = 'apollo-combo-css';
    css.textContent = [
      /* ── Dropdown surface ──────────────────────────────────────────────
         Replicated from .as2-drop in the Design System showcase
         (VIA_core.js_official__uni.theme.v2.1.css) so a code-built dropdown is
         indistinguishable from the taxonomy one. Alpha .2 + z-index per spec. */
      '.apollo-combo{position:absolute;top:calc(100% + 6px);left:0;right:0;',
      'z-index:9999999!important;',
      'background:rgba(var(--rgb-theme),.2)!important;',
      'backdrop-filter:blur(20px) saturate(180%)!important;',
      '-webkit-backdrop-filter:blur(20px) saturate(180%)!important;',
      'border:1px solid rgba(var(--rgb-diff),.04);border-radius:var(--r);',
      'padding:8px;max-height:280px;overflow-y:auto;overscroll-behavior:contain;',
      'display:flex;flex-direction:column;gap:2px;',
      'box-shadow:inset 0 1px 0 0 rgba(var(--rgb-theme),.2),',
      'inset 0 0 0 1px rgba(var(--rgb-theme),.075);}',
      '.apollo-combo[hidden]{display:none;}',
      /* .as2-opt geometry */
      '.apollo-combo-opt{display:flex;align-items:center;gap:11px;padding:10px;',
      'border-radius:var(--r-xs);cursor:pointer;position:relative;',
      'transition:background .15s var(--ease),transform .15s var(--ease);}',
      '.apollo-combo-opt:hover,.apollo-combo-opt.is-focused{background:rgba(var(--rgb-primary),.07);}',
      '.apollo-combo-opt.is-selected{background:rgba(var(--rgb-primary),.11);}',
      '.apollo-combo-opt img,.apollo-combo-ph{width:34px;height:34px;border-radius:9px;',
      'object-fit:cover;flex-shrink:0;display:flex;align-items:center;justify-content:center;',
      'background:var(--surface);font-size:11px;font-weight:700;',
      'box-shadow:inset 0 0 0 1px rgba(var(--rgb-diff),.14);',
      'color:var(--txt-heading,inherit);}',
      '.apollo-combo-opt b{display:block;font-size:var(--fs-body-sm);font-weight:500;',
      'color:var(--txt-heading);line-height:1.3;}',
      '.apollo-combo-opt small{display:block;font-family:var(--ff-mono);',
      'font-size:calc(var(--fsx) * 10px);color:var(--muted);line-height:1.35;}',
      /* .as2-empty */
      '.apollo-combo-empty{padding:18px 14px;text-align:center;font-family:var(--ff-mono);',
      'font-size:calc(var(--fsx) * 10px);text-transform:uppercase;letter-spacing:.08em;',
      'color:var(--muted);}',
      '#galleryImages [data-gallery-slot]{position:relative;overflow:hidden;}',
      '#galleryImages [data-gallery-slot] img{width:100%;height:100%;object-fit:cover;display:block;}',

      /* ── image picker popup ──
         Follows .modal-backdrop / .modal from the Design System showcase. */
      '.apx-pick{position:fixed;inset:0;z-index:9999999;display:flex;align-items:center;',
      'justify-content:center;padding:24px;opacity:0;',
      'transition:opacity .35s var(--ease);}',
      '.apx-pick[hidden]{display:none;}',
      '.apx-pick.is-open{opacity:1;}',
      '.apx-pick-bd{position:absolute;inset:0;background:rgba(var(--rgb-diff),.4);',
      'backdrop-filter:blur(10px)!important;-webkit-backdrop-filter:blur(10px)!important;}',
      '.apx-pick-box{position:relative;width:100%;max-width:340px;',
      'border-radius:var(--r);overflow:hidden;background:var(--bg);',
      'border:1px solid rgba(var(--rgb-diff),.04);',
      'box-shadow:inset 0 1px 0 0 rgba(var(--rgb-theme),.2),',
      'inset 0 0 0 1px rgba(var(--rgb-theme),.075);',
      'transform:translateY(20px) scale(.98);transition:transform .35s var(--ease);}',
      '.apx-pick.is-open .apx-pick-box{transform:translateY(0) scale(1);}',
      /* top half — the only clickable button */
      '.apx-pick-up{display:flex;width:100%;align-items:center;justify-content:center;gap:9px;',
      'padding:22px 18px;border:0;cursor:pointer;background:transparent;',
      'font:inherit;font-size:14.5px;font-weight:700;color:var(--txt-heading,inherit);',
      'border-bottom:1px solid rgba(var(--rgb-diff),.10);-webkit-tap-highlight-color:transparent;}',
      '.apx-pick-up:hover{background:rgba(var(--rgb-diff),.05);}',
      '.apx-pick-up i{font-size:19px;}',
      /* bottom half — NOT a button, just a labelled field */
      '.apx-pick-link{padding:16px 18px 18px;}',
      '.apx-pick-lbl{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;',
      'opacity:.5;margin-bottom:10px;}',
      '.apx-pick-row{display:flex;align-items:center;gap:8px;}',
      /* input: no background, no border, no shadow — as specified */
      '.apx-pick-in{flex:1;min-width:0;background:none!important;border:none!important;',
      'box-shadow:none!important;outline:none!important;padding:6px 0;font:inherit;',
      'font-size:13.5px;color:var(--txt-color,inherit);}',
      '.apx-pick-in::placeholder{opacity:.38;}',
      '.apx-pick-go{flex-shrink:0;width:38px;height:38px;border-radius:50%;border:0;',
      'cursor:pointer;display:grid;place-items:center;font-size:17px;',
      'background:rgba(var(--rgb-diff),.90);color:rgba(var(--rgb-theme),1);',
      '-webkit-tap-highlight-color:transparent;}',
      '.apx-pick-go:hover{background:rgba(var(--rgb-diff),1);}'
    ].join('');
    d.head.appendChild(css);
  }

  /* ═══════════════════════════ boot ═══════════════════════════ */

  ready(function () {
    injectStyles();
    wireCover();
    wireGallery();
    wireLoc();
    wireDjs();
    // #region agent log
    w.__apolloAgentDbg({runId:'pre-fix',hypothesisId:'A',location:'create-wire.js:init',message:'create-wire init',data:{hasWpMedia:!!(w.wp&&w.wp.media),canUpload:!!CFG.canUpload,hasNonce:!!(CFG&&CFG.nonce)}});
    // #endregion
  });
})(window, document);
