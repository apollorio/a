/*!
 * Apollo Events — Single Event runtime
 * ---------------------------------------------------------------------------
 * Multi-instance, root-scoped. The SAME module drives:
 *   · the full page  /evento/{slug}
 *   · an inline embed [apollo_event_single]
 *   · a lightbox instance injected from /apollo/v1/eventos/{id}/fragmento
 *
 * Every DOM lookup is scoped to the instance root via data-ev hooks, every
 * listener is bound through one AbortController and every rAF / observer /
 * ScrollTrigger / Leaflet map is tracked, so unmount() leaves nothing behind.
 *
 * core.js contract: GSAP + Lenis are booted by core.js. NOTHING here starts on
 * DOMContentLoaded — boot is gated on apollo:ready.
 *
 * Public API: window.ApolloEventSingle
 *   .mountAll(scope)  .mount(root)  .unmount(root)  .get(root)  .active()
 */
(function (w, d) {
  'use strict';

  if (w.ApolloEventSingle) { return; }

  var REDUCE = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var instances = [];

  /*
   * REVEAL SELECTOR CONTRACT (P3, 2026-08-07).
   *
   * PHP owns this list — APOLLO_EVENT_REVEAL_ANIM in includes/render-single.php
   * — and publishes it on window.APOLLO_EVENT_REVEAL. It used to be a literal
   * here that portal/bootstrap.php and portal/styles-lightbox.php each hand-
   * copied, both carrying a "keep in sync manually" comment. The literal below
   * is now only the offline fallback (harness, a fragment loaded without the
   * boot script); when the global is present it wins.
   */
  var REVEAL_ANIM = (w.APOLLO_EVENT_REVEAL && w.APOLLO_EVENT_REVEAL.anim) ||
    '.ev-fact,.ev-panel,[data-ev="access"],[data-ev="about"],.ev-spotify,.ev-gallery,.v-slider,.ev-map,.ev-vbody';

  /*
   * ENTER + REVERSE ON LEAVE (2026-08-07) — every scroll-driven scene here used
   * `once: true`, which plays forward one time and then kills the trigger. The
   * brief is explicit: animate in on entering the viewport AND reverse on
   * leaving it, so the scenes stay live.
   *
   * This is only safe because `scroller` is resolved per-instance (see
   * this.scroller): a trigger created against the default `scroller: window`
   * inside the Lenis-locked lightbox never resolves, and a non-`once` trigger
   * that never resolves strands its element at GSAP's inline opacity:0 forever.
   * That is the 2026-08-01 stranded-content bug. Do not reuse this object on a
   * scene whose scroller is not resolved.
   */
  function stCfg(self, id, trigger, start) {
    return {
      id: self.stId(id),
      scroller: self.scroller,
      trigger: trigger,
      start: start,
      toggleActions: 'play none none reverse'
    };
  }

  /* ═══════════════════════════ helpers ═══════════════════════════ */

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function hhmm(iso) {
    var t = iso ? new Date(String(iso).replace(' ', 'T')) : new Date();
    if (isNaN(t.getTime())) { t = new Date(); }
    return t.getHours() + ':' + String(t.getMinutes()).padStart(2, '0');
  }

  function readConfig(root) {
    var nodes = root.querySelectorAll('[data-ev-config]');
    for (var i = 0; i < nodes.length; i++) {
      /* Skip a config that belongs to a nested (lightboxed) instance. */
      var owner = nodes[i].closest('[data-ev-root]');
      if (owner && owner !== root) { continue; }
      try { return JSON.parse(nodes[i].textContent || '{}'); } catch (e) { /* next */ }
    }
    return w.APOLLO_SINGLE_EVENT || {};
  }

  /* ═══════════════════════════ instance ═══════════════════════════ */

  function Instance(root) {
    this.root = root;
    this.conf = readConfig(root);
    this.uid = root.getAttribute('data-ev-uid') || '';
    this.mode = root.getAttribute('data-ev-mode') || 'page';
    /*
     * Lightbox-aware scroller (2026-08-01). The fragment mounts inside
     * .ev-lb-scroll — a native overflow-y:auto panel, NOT the window — while
     * the document is Lenis-locked and never scrolls. Every ScrollTrigger
     * below defaulted to `scroller: window`, so on /eventos and /portal every
     * reveal (facts, venue map, slider, panels, DJ rail) stayed at its inline
     * `opacity:0` forever: present in the DOM, invisible on screen, because
     * the trigger that was supposed to clear it could never fire.
     * `root.closest('.ev-lb-scroll')` resolves to that panel only when this
     * instance was mounted inside the lightbox; on /evento/{slug} and inline
     * embeds it is null, so `scroller` stays undefined and behaviour there is
     * byte-for-byte unchanged.
     */
    this.scroller = (root.closest && root.closest('.ev-lb-scroll')) || undefined;
    this.ac = new AbortController();
    this.rafs = [];
    this.observers = [];
    this.triggers = [];
    this.timers = [];
    this.players = [];
    this.map = null;
    this.rsvp = null;
    root.__apolloEvent = this;
  }

  /**
   * Scoped, ownership-aware lookup of a single data-ev hook.
   *
   * In page mode the root IS <body>, which also contains the lightbox shell —
   * so a plain root.querySelector() would happily return an element belonging
   * to a lightboxed instance. Ownership always goes to the NEAREST root.
   */
  Instance.prototype.q = function (name) {
    var found = this.root.querySelectorAll('[data-ev="' + name + '"]');
    for (var i = 0; i < found.length; i++) {
      if (this.owns(found[i])) { return found[i]; }
    }
    return null;
  };

  /** Scoped, ownership-aware lookup of many nodes. */
  Instance.prototype.qa = function (sel) {
    var self = this;
    return Array.prototype.filter.call(
      this.root.querySelectorAll(sel),
      function (el) { return self.owns(el); }
    );
  };

  /** Scoped, ownership-aware lookup of the first node matching a selector. */
  Instance.prototype.q1 = function (sel) {
    return this.qa(sel)[0] || null;
  };
  Instance.prototype.on = function (el, type, fn, opts) {
    if (!el) { return; }
    var o = opts || {};
    o.signal = this.ac.signal;
    el.addEventListener(type, fn, o);
  };
  Instance.prototype.raf = function (fn) {
    var id = w.requestAnimationFrame(fn);
    this.rafs.push(id);
    return id;
  };
  Instance.prototype.later = function (fn, ms) {
    var id = w.setTimeout(fn, ms);
    this.timers.push(id);
    return id;
  };
  Instance.prototype.stId = function (name) {
    return (this.uid || 'page-') + name;
  };
  Instance.prototype.track = function (st) {
    if (st) { this.triggers.push(st); }
    return st;
  };

  /**
   * Does this instance own the given node?
   *
   * In page mode the root IS <body>, which also contains the lightbox shell.
   * Without this guard a click inside a lightboxed instance would fire on BOTH
   * that instance and the page instance. Ownership goes to the NEAREST root.
   *
   * @param {Node} node Event target.
   * @return {boolean}
   */
  Instance.prototype.owns = function (node) {
    if (!node || !this.root.contains(node)) { return false; }
    var nearest = node.closest ? node.closest('[data-ev-root]') : null;
    if (!nearest) { return this.root === d.body || this.root.contains(node); }
    return nearest === this.root;
  };

  /* ── mount ─────────────────────────────────────────────────────── */

  Instance.prototype.mount = function () {
    this.bindActions();
    this.initHeroVideo();
    this.initWarmupGlow();
    this.initRsvp();
    this.initChat();
    this.initCoupon();
    this.initGallery();
    this.initVenueSlider();
    this.initVenueMap();
    this.initDjPlayers();
    this.initAnim();
    return this;
  };

  /* ── unmount ───────────────────────────────────────────────────── */

  Instance.prototype.unmount = function () {
    this.ac.abort();

    this.rafs.forEach(function (id) { w.cancelAnimationFrame(id); });
    this.timers.forEach(function (id) { w.clearTimeout(id); w.clearInterval(id); });
    this.observers.forEach(function (o) { try { o.disconnect(); } catch (e) {} });
    this.triggers.forEach(function (st) { try { st.kill(); } catch (e) {} });
    this.players.forEach(function (p) { try { p.stop(); } catch (e) {} });

    if (this.map) {
      try { this.map.remove(); } catch (e) {}
      this.map = null;
    }

    this.rafs = [];
    this.timers = [];
    this.observers = [];
    this.triggers = [];
    this.players = [];

    if (this.root) { delete this.root.__apolloEvent; }

    var i = instances.indexOf(this);
    if (i > -1) { instances.splice(i, 1); }
  };

  /* ═══════════════════ delegated data-ev-action ═══════════════════ */

  Instance.prototype.bindActions = function () {
    var self = this;

    this.on(this.root, 'click', function (e) {
      var el = e.target.closest('[data-ev-action]');
      if (!el || !self.owns(el)) { return; }

      switch (el.getAttribute('data-ev-action')) {
        case 'back':
          e.preventDefault();
          w.history.back();
          break;
        case 'share':
          e.preventDefault();
          self.share();
          break;
        case 'open-chat':
          e.preventDefault();
          self.openChat();
          break;
        case 'close-chat':
          e.preventDefault();
          self.closeChat();
          break;
        case 'send-msg':
          e.preventDefault();
          self.sendMsg();
          break;
        case 'close-gallery':
          e.preventDefault();
          self.closeGallery();
          break;
        default:
          break;
      }
    });

    /* ESC closes the topmost overlay owned by THIS instance. */
    this.on(d, 'keydown', function (e) {
      if ('Escape' !== e.key) { return; }
      var gal = self.q('gallery-lightbox');
      var chat = self.q('chat');
      if (gal && gal.classList.contains('is-open')) { self.closeGallery(); }
      else if (chat && chat.classList.contains('is-open')) { self.closeChat(); }
    });
  };

  Instance.prototype.share = function () {
    var title = this.conf.title || d.title;
    var url = this.conf.shareUrl || w.location.href;
    var text = this.conf.shareText || '';
    if (text.length > 180) { text = text.slice(0, 177) + '…'; }
    if (navigator.share) {
      var payload = { title: title, url: url };
      if (text) { payload.text = text; }
      navigator.share(payload).catch(function () {});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(url).catch(function () {});
    }
  };

  /* ═══════════════════════ hero ambient video ═════════════════════
   * Wait until playing → +3.5s → fade.
   * STRICT chrome kill: whenever YT is not playing, drop .is-on so the
   * banner covers any prev/pause/next bezel, then force playVideo().
   */
  Instance.prototype.initHeroVideo = function () {
    var yt = this.q('hero-yt');
    if (!yt) { return; }
    var iframe = yt.querySelector('iframe');
    if (!iframe) { return; }
    if (REDUCE) { return; }

    var self = this;
    var FADE_AFTER_READY_MS = 3500;
    var SAFETY_MS = 14000;
    var armed = false;
    var everFaded = false;
    var win = iframe.contentWindow;

    function ytCmd(func, args) {
      try {
        win.postMessage(JSON.stringify({
          event: 'command',
          func: func,
          args: args || []
        }), '*');
      } catch (err) { /* ignore */ }
    }

    function showVideo() {
      yt.style.transition = '';
      yt.classList.add('is-on');
    }

    function hideVideoToBanner() {
      /* Snap under banner — do not animate chrome away slowly. */
      yt.style.transition = 'opacity .12s linear';
      yt.classList.remove('is-on');
    }

    function armFade() {
      if (armed) { return; }
      armed = true;
      self.later(function () {
        everFaded = true;
        showVideo();
        // #region agent log
        fetch('http://127.0.0.1:7754/ingest/da9d552b-a038-4061-bf95-e47d2c529b38', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '837565' },
          body: JSON.stringify({
            sessionId: '837565',
            runId: 'chrome-hide-v2',
            hypothesisId: 'BANNERCOVER',
            location: 'apollo-single-event.js:armFade',
            message: 'ambient fade-in + top-origin crop',
            data: {
              transform: w.getComputedStyle(iframe).transform,
              transformOrigin: w.getComputedStyle(iframe).transformOrigin,
              hasCatch: !!yt.querySelector('.apollo-yt-ambient__catch')
            },
            timestamp: Date.now()
          })
        }).catch(function () {});
        // #endregion
      }, FADE_AFTER_READY_MS);
    }

    function onMsg(e) {
      var origin = String(e && e.origin || '');
      if (origin.indexOf('youtube.com') === -1 && origin.indexOf('youtube-nocookie.com') === -1) {
        return;
      }
      var data = e.data;
      if (typeof data === 'string') {
        try { data = JSON.parse(data); } catch (err) { return; }
      }
      if (!data || typeof data !== 'object') { return; }

      if (data.event === 'onReady') {
        ytCmd('addEventListener', ['onStateChange']);
        ytCmd('mute');
        ytCmd('playVideo');
        return;
      }

      var state = null;
      if (data.event === 'onStateChange') {
        state = typeof data.info === 'number' ? data.info : (data.info && data.info.playerState);
      } else if (data.event === 'infoDelivery' && data.info && typeof data.info.playerState === 'number') {
        state = data.info.playerState;
      }

      /* YT states: -1 unstarted, 0 ended, 1 playing, 2 paused, 3 buffering, 5 cued */
      if (state === 1) {
        if (everFaded) { showVideo(); }
        armFade();
      } else if (state === 2 || state === 0 || state === 3) {
        /* Hide bezel behind banner; keep ambient looping. */
        if (everFaded) { hideVideoToBanner(); }
        if (state === 2 || state === 0) {
          ytCmd('playVideo');
          // #region agent log
          fetch('http://127.0.0.1:7754/ingest/da9d552b-a038-4061-bf95-e47d2c529b38', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '837565' },
            body: JSON.stringify({
              sessionId: '837565',
              runId: 'chrome-hide-v2',
              hypothesisId: 'BANNERCOVER',
              location: 'apollo-single-event.js:onMsg',
              message: 'hid yt under banner + force play',
              data: { state: state },
              timestamp: Date.now()
            })
          }).catch(function () {});
          // #endregion
        }
      }
    }

    w.addEventListener('message', onMsg);

    iframe.addEventListener('load', function () {
      try {
        win.postMessage(JSON.stringify({ event: 'listening', id: 1 }), '*');
      } catch (err) { /* ignore */ }
      self.later(function () {
        if (!armed) { armFade(); }
      }, 5000);
    });
    self.later(function () {
      if (!armed) { armFade(); }
    }, SAFETY_MS);
  };

  /* ═══════════ RSVP — mesh-gradient warm-up border + status ═══════ */

  /**
   * Drives --pointer-° (rotation) and --pointer-d (breathing) on the RSVP
   * section; the mesh-gradient border rules inherit both from here.
   */
  Instance.prototype.initWarmupGlow = function () {
    var section = this.q('rsvp');
    if (!section || REDUCE) { return; }

    var self = this;
    var ROTATION_MS = 3400;
    var PULSE_MS = 2800;
    var MIN_D = 50;
    var MAX_D = 100;
    var t0 = null;

    function tick(ts) {
      if (null === t0) { t0 = ts; }
      var elapsed = ts - t0;
      var angle = (elapsed % ROTATION_MS) / ROTATION_MS * 360;
      var sine = Math.sin((elapsed % PULSE_MS) / PULSE_MS * Math.PI * 2);
      var dist = MIN_D + (MAX_D - MIN_D) * ((sine + 1) / 2);
      section.style.setProperty('--pointer-°', angle.toFixed(1) + 'deg');
      section.style.setProperty('--pointer-d', dist.toFixed(1));
      self.raf(tick);
    }

    this.raf(tick);
  };

  Instance.prototype.initRsvp = function () {
    var self = this;
    var grid = this.q('rsvp-grid');
    if (!grid) { return; }

    this.qa('[data-rsvp]').forEach(function (btn) {
      self.on(btn, 'click', function () { self.doRsvp(btn.getAttribute('data-rsvp')); });
    });

    if (this.conf.loggedIn && this.conf.restRsvp) { this.fetchMyRsvp(); }
  };

  Instance.prototype.fetchMyRsvp = function () {
    var self = this;
    fetch(this.conf.restRsvp + '?mine=1', {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': this.conf.nonce || '' },
      signal: this.ac.signal
    })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (json) {
        var type = json && (json.mine || (json.data && json.data.mine));
        if ('will' === type || 'maybe' === type) { self.doRsvp(type, true); }
      })
      .catch(function () { /* silent — RSVP is progressive enhancement */ });
  };

  /**
   * Toggle an RSVP option.
   *
   * On select the viewer's own .ev-status is MOVED INSIDE the chosen card
   * (mockup rule: it replaces the old avatar strip). On deselect it returns to
   * its slot right after the grid.
   *
   * @param {string}  type   'will' | 'maybe'
   * @param {boolean} silent Skip the network write (used when hydrating).
   */
  Instance.prototype.doRsvp = function (type, silent) {
    var grid = this.q('rsvp-grid');
    if (!grid) { return; }

    var cells = Array.prototype.slice.call(grid.querySelectorAll('.ev-rsvp-cell'));
    var myBar = this.q('rsvp-status');
    var state = null;

    if (w.Flip && cells.length && !REDUCE) {
      state = w.Flip.getState(cells, { props: 'opacity,transform' });
    }

    var prev = this.rsvp;

    if (prev && prev !== type) {
      var prevBtn = this.q1('[data-rsvp="' + prev + '"]');
      if (prevBtn) {
        prevBtn.classList.remove('is-on');
        prevBtn.setAttribute('aria-pressed', 'false');
      }
    }

    if (prev === type) {
      /* Deselect — status leaves the card and returns after the grid. */
      this.rsvp = null;
      if (myBar) {
        myBar.classList.remove('is-show');
        grid.insertAdjacentElement('afterend', myBar);
      }
      grid.classList.remove('is-chosen');
      var cur = this.q1('[data-rsvp="' + type + '"]');
      if (cur) {
        cur.classList.remove('is-on');
        cur.setAttribute('aria-pressed', 'false');
      }
      cells.forEach(function (c) { c.classList.remove('is-dim'); });
    } else {
      /* Select — status moves INSIDE the chosen card. */
      this.rsvp = type;
      var btn = this.q1('[data-rsvp="' + type + '"]');
      if (btn) {
        btn.classList.add('is-on');
        btn.setAttribute('aria-pressed', 'true');
        if (myBar) { btn.appendChild(myBar); }
      }
      if (myBar) { myBar.classList.add('is-show'); }

      var txt = this.q('rsvp-status-text');
      if (txt) {
        txt.textContent = 'will' === type
          ? 'Você confirmou presença. O Warm-Up está aberto.'
          : 'Você marcou interesse. O Warm-Up está aberto.';
      }

      if (navigator.vibrate) { navigator.vibrate(10); }
      grid.classList.add('is-chosen');
      cells.forEach(function (c) {
        c.classList.toggle('is-dim', c.getAttribute('data-type') !== type);
      });
    }

    if (state && w.Flip) {
      w.Flip.from(state, { duration: 0.55, ease: 'power3.inOut', absolute: false });
    }

    if (!silent) { this.persistRsvp(this.rsvp); }

    if (w.ScrollTrigger) {
      this.later(function () { w.ScrollTrigger.refresh(); }, 620);
    }
  };

  Instance.prototype.persistRsvp = function (type) {
    if (!this.conf.restRsvp) { return; }
    if (!this.conf.loggedIn) {
      if (this.conf.loginUrl) { w.location.href = this.conf.loginUrl; }
      return;
    }
    fetch(this.conf.restRsvp, {
      method: type ? 'POST' : 'DELETE',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.conf.nonce || ''
      },
      body: type ? JSON.stringify({ status: type }) : null,
      signal: this.ac.signal
    }).catch(function () { /* optimistic UI — never block on the write */ });
  };

  /* ═══════════════ Warm-Up chat (apollo-comment REST) ═════════════ */

  Instance.prototype.openChat = function () {
    var chat = this.q('chat');
    if (!chat) { return; }
    chat.classList.add('is-open');
    chat.setAttribute('aria-hidden', 'false');
    if (w.Apollo && 'function' === typeof w.Apollo.lockScroll) { w.Apollo.lockScroll(true); }
    this.gateChatInput();
    this.loadChat();

    var input = this.q('chat-input');
    if (input && this.conf.loggedIn) { this.later(function () { input.focus(); }, 320); }
  };

  Instance.prototype.closeChat = function () {
    var chat = this.q('chat');
    if (!chat) { return; }
    chat.classList.remove('is-open');
    chat.setAttribute('aria-hidden', 'true');
    if (w.Apollo && 'function' === typeof w.Apollo.lockScroll) { w.Apollo.lockScroll(false); }
  };

  Instance.prototype.gateChatInput = function () {
    var row = this.q('chat-input-row');
    var hint = this.q('chat-login');
    if (this.conf.loggedIn) {
      if (row) { row.hidden = false; }
      if (hint) { hint.hidden = true; }
      return;
    }
    if (row) { row.hidden = true; }
    if (hint) {
      hint.hidden = false;
      hint.setAttribute('href', this.conf.loginUrl || '#');
    }
  };

  Instance.prototype.chatBubble = function (msg, isMe, avatar, time) {
    return '<div class="ev-msg' + (isMe ? ' is-me' : '') + '">'
      + (avatar ? '<img class="ev-msg-av" src="' + esc(avatar) + '" alt="" loading="lazy">' : '')
      + '<div class="ev-msg-b"><p>' + esc(msg) + '</p><time>' + esc(time) + '</time></div>'
      + '</div>';
  };

  Instance.prototype.initChat = function () {
    var self = this;
    var input = this.q('chat-input');
    if (!input) { return; }
    this.on(input, 'keydown', function (e) {
      if ('Enter' === e.key) { e.preventDefault(); self.sendMsg(); }
    });
  };

  Instance.prototype.loadChat = function () {
    var self = this;
    var box = this.q('chat-msgs');
    if (!box || !this.conf.restComments) { return; }

    fetch(this.conf.restComments + '?post=' + encodeURIComponent(this.conf.id) + '&per_page=50', {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': this.conf.nonce || '' },
      signal: this.ac.signal
    })
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (json) {
        var list = Array.isArray(json) ? json : ((json && json.data) || []);
        var host = d.createElement('div');
        host.innerHTML = list.map(function (c) {
          var mine = self.conf.me && Number(c.author) === Number(self.conf.me.id);
          return self.chatBubble(
            c.content_plain || c.content || '',
            !!mine,
            c.author_avatar || '',
            hhmm(c.date || '')
          );
        }).join('');

        var frag = d.createDocumentFragment();
        while (host.firstChild) { frag.appendChild(host.firstChild); }

        box.innerHTML = '';
        box.appendChild(frag);
        box.scrollTop = box.scrollHeight;
      })
      .catch(function () {});
  };

  Instance.prototype.sendMsg = function () {
    var self = this;
    var input = this.q('chat-input');
    var box = this.q('chat-msgs');
    if (!input || !box) { return; }

    var text = input.value.trim();
    if (!text) { return; }

    if (!this.conf.loggedIn) {
      if (this.conf.loginUrl) { w.location.href = this.conf.loginUrl; }
      return;
    }

    /* Optimistic bubble first, reconcile on response. */
    box.insertAdjacentHTML(
      'beforeend',
      this.chatBubble(text, true, (this.conf.me && this.conf.me.avatar) || '', hhmm())
    );
    box.scrollTop = box.scrollHeight;
    input.value = '';

    fetch(this.conf.restComments, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.conf.nonce || ''
      },
      body: JSON.stringify({ post: this.conf.id, content: text }),
      signal: this.ac.signal
    })
      .then(function (r) { if (r.ok) { self.loadChat(); } })
      .catch(function () {});
  };

  /* ═══════════════════════════ coupon ════════════════════════════ */

  Instance.prototype.initCoupon = function () {
    var self = this;
    var box = this.q('coupon');
    if (!box) { return; }

    function copy() {
      var code = self.q('coupon-code');
      var label = self.q('coupon-label');
      if (!code) { return; }
      if (navigator.clipboard) {
        navigator.clipboard.writeText(code.textContent.trim()).catch(function () {});
      }
      if (label) {
        var original = label.textContent;
        label.textContent = 'COPIADO ✓';
        self.later(function () { label.textContent = original; }, 1800);
      }
      if (navigator.vibrate) { navigator.vibrate(10); }
    }

    this.on(box, 'click', copy);
    this.on(box, 'keydown', function (e) {
      if ('Enter' === e.key || ' ' === e.key) { e.preventDefault(); copy(); }
    });
  };

  /* ═══════════════════════ gallery lightbox ══════════════════════ */

  Instance.prototype.initGallery = function () {
    var self = this;

    this.qa('.ev-gi').forEach(function (gi) {
      function open() {
        var img = gi.querySelector('img');
        self.openGallery(gi.getAttribute('data-full') || (img && img.src) || '', gi);
      }
      self.on(gi, 'click', open);
      self.on(gi, 'keydown', function (e) {
        if ('Enter' === e.key || ' ' === e.key) { e.preventDefault(); open(); }
      });
    });

    var lb = this.q('gallery-lightbox');
    if (lb) {
      this.on(lb, 'click', function (e) { if (e.target === lb) { self.closeGallery(); } });
    }
  };

  Instance.prototype.openGallery = function (src, trigger) {
    var lb = this.q('gallery-lightbox');
    var img = this.q('gallery-lightbox-img');
    if (!lb || !img || !src) { return; }

    this._galleryTrigger = trigger || null;
    img.src = src;
    lb.classList.add('is-open');
    lb.setAttribute('aria-hidden', 'false');
    if (w.Apollo && 'function' === typeof w.Apollo.lockScroll) { w.Apollo.lockScroll(true); }

    var x = lb.querySelector('.ev-lightbox-x');
    if (x) { x.focus(); }
  };

  Instance.prototype.closeGallery = function () {
    var lb = this.q('gallery-lightbox');
    if (!lb) { return; }
    lb.classList.remove('is-open');
    lb.setAttribute('aria-hidden', 'true');
    if (w.Apollo && 'function' === typeof w.Apollo.lockScroll) { w.Apollo.lockScroll(false); }
    if (this._galleryTrigger) {
      this._galleryTrigger.focus();
      this._galleryTrigger = null;
    }
  };

  /* ═════════════════════ loc photo slider ════════════════════════ */

  Instance.prototype.initVenueSlider = function () {
    var self = this;
    var track = this.q('venue-track');
    var dots = this.q('venue-dots');
    if (!track) { return; }

    var count = track.children.length;
    if (count < 2) { return; }

    var idx = 0;
    var timer = null;

    function go(i) {
      idx = ((i % count) + count) % count;
      track.style.transform = 'translateX(-' + (idx * 100) + '%)';
      if (!dots) { return; }
      Array.prototype.forEach.call(dots.children, function (dot, n) {
        dot.classList.toggle('on', n === idx);
      });
    }

    function stop() {
      if (timer) { w.clearInterval(timer); timer = null; }
    }

    function restart() {
      stop();
      if (REDUCE) { return; }
      timer = w.setInterval(function () { go(idx + 1); }, 4800);
      self.timers.push(timer);
    }

    if (dots) {
      this.on(dots, 'click', function (e) {
        var dot = e.target.closest('[data-vgo]');
        if (!dot) { return; }
        go(parseInt(dot.getAttribute('data-vgo'), 10) || 0);
        restart();
      });
    }

    /* Pause the carousel while it is off-screen. */
    if (w.IntersectionObserver) {
      var io = new w.IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) { restart(); } else { stop(); }
        });
      }, { rootMargin: '10% 0px' });
      io.observe(track);
      this.observers.push(io);
    } else {
      restart();
    }
  };

  /* ══════════════════ loc map (Leaflet, Apollo theme) ════════════ */

  Instance.prototype.initVenueMap = function () {
    var el = this.q('venue-map-frame');
    if (!el || 'undefined' === typeof w.L) { return; }

    var lat = parseFloat(el.getAttribute('data-lat'));
    var lng = parseFloat(el.getAttribute('data-lng'));
    if (!isFinite(lat) || !isFinite(lng) || (0 === lat && 0 === lng)) { return; }

    var map = w.L.map(el, {
      zoomControl: false,
      attributionControl: false,
      dragging: false,
      scrollWheelZoom: false,
      doubleClickZoom: false,
      touchZoom: false,
      boxZoom: false,
      keyboard: false,
      tap: false
    }).setView([lat, lng], 15);

    w.L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      subdomains: 'abcd',
      maxZoom: 20
    }).addTo(map);

    w.L.marker([lat, lng], {
      interactive: false,
      icon: w.L.divIcon({
        className: 'loc-marker-icon',
        html: '<div class="loc-marker-pulse"></div><div class="loc-marker"><i class="ri-map-pin-2-fill"></i></div>',
        iconSize: [26, 26],
        iconAnchor: [13, 13]
      })
    }).addTo(map);

    this.map = map;

    /*
     * Leaflet needs a size recalc whenever its container is revealed or
     * resized — critical inside a lightbox, which mounts while hidden.
     */
    function invalidate() { try { map.invalidateSize(); } catch (e) {} }
    this.invalidateMap = invalidate;

    this.later(invalidate, 80);
    this.later(invalidate, 400);
    this.on(w, 'resize', invalidate, { passive: true });

    if (w.ResizeObserver) {
      var ro = new w.ResizeObserver(invalidate);
      ro.observe(el);
      this.observers.push(ro);
    }
  };

  /* ══════════════════════ DJ audio preview ═══════════════════════ */

  function DjPlayer(article, instance) {
    this.article = article;
    this.instance = instance;
    this.audioUrl = article.getAttribute('data-audio');
    this.state = 'idle';
    this.audio = null;
    this.rafId = null;
    this.timeEl = article.querySelector('.ev-dj-time');

    var nameEl = article.querySelector('.ev-dj-name');
    article.style.setProperty('--dj-prog', '0');
    article.setAttribute('role', 'button');
    article.setAttribute('tabindex', '0');
    article.setAttribute(
      'aria-label',
      'Prévia de ' + (nameEl ? nameEl.textContent.trim() : 'artista') + ' — toque para ouvir'
    );

    var self = this;
    instance.on(article, 'click', function () { self.handleClick(); });
    instance.on(article, 'keydown', function (e) {
      if ('Enter' === e.key || ' ' === e.key) { e.preventDefault(); self.handleClick(); }
    });
    Array.prototype.forEach.call(article.querySelectorAll('a'), function (a) {
      instance.on(a, 'click', function (e) { e.stopPropagation(); });
    });
  }

  DjPlayer.active = null;

  DjPlayer.prototype.handleClick = function () {
    if (!this.audioUrl || 'loading' === this.state) { return; }
    if ('idle' === this.state) { this.boot(); }
    else if ('playing' === this.state) { this.pause(); }
    else if ('paused' === this.state) { this.resume(); }
  };

  DjPlayer.prototype.boot = function () {
    var self = this;
    if (DjPlayer.active && DjPlayer.active !== this) { DjPlayer.active.stop(); }

    this.setState('loading');
    if (!this.audio) {
      this.audio = new Audio(this.audioUrl);
      this.audio.preload = 'auto';
      this.audio.addEventListener('ended', function () { self.reset(); });
      this.audio.addEventListener('error', function () {
        self.setState('idle');
        self.article.style.setProperty('--dj-prog', '0');
      });
    }

    var p = this.audio.play();
    if (p && p.then) {
      p.then(function () { self.onPlaying(); }).catch(function () { self.setState('idle'); });
    } else {
      this.onPlaying();
    }
  };

  DjPlayer.prototype.onPlaying = function () {
    this.setState('playing');
    DjPlayer.active = this;
    this.tick();
  };

  DjPlayer.prototype.pause = function () {
    if (this.audio) { this.audio.pause(); }
    this.stopTick();
    this.setState('paused');
  };

  DjPlayer.prototype.resume = function () {
    var self = this;
    if (DjPlayer.active && DjPlayer.active !== this) { DjPlayer.active.stop(); }
    if (!this.audio) { this.boot(); return; }

    var p = this.audio.play();
    if (p && p.then) { p.then(function () { self.onPlaying(); }).catch(function () {}); }
    else { this.onPlaying(); }
  };

  DjPlayer.prototype.stop = function () {
    if (this.audio) {
      this.audio.pause();
      this.audio.currentTime = 0;
    }
    this.stopTick();
    this.reset();
  };

  DjPlayer.prototype.tick = function () {
    var self = this;
    this.stopTick();

    function frame() {
      if (!self.audio || 'playing' !== self.state) { return; }
      var dur = self.audio.duration;
      var cur = self.audio.currentTime;
      var ratio = (dur && isFinite(dur) && dur > 0) ? cur / dur : 0;
      self.article.style.setProperty('--dj-prog', (ratio * 100).toFixed(2));
      if (self.timeEl && dur && isFinite(dur)) {
        self.timeEl.textContent = self.fmt(cur) + ' / ' + self.fmt(dur);
      }
      self.rafId = w.requestAnimationFrame(frame);
    }

    this.rafId = w.requestAnimationFrame(frame);
  };

  DjPlayer.prototype.stopTick = function () {
    if (null != this.rafId) {
      w.cancelAnimationFrame(this.rafId);
      this.rafId = null;
    }
  };

  DjPlayer.prototype.fmt = function (sec) {
    var total = Math.max(0, Math.floor(sec || 0));
    return Math.floor(total / 60) + ':' + String(total % 60).padStart(2, '0');
  };

  DjPlayer.prototype.reset = function () {
    this.stopTick();
    this.setState('idle');
    this.article.style.setProperty('--dj-prog', '0');
    if (this.timeEl) { this.timeEl.textContent = ''; }
    if (DjPlayer.active === this) { DjPlayer.active = null; }
  };

  DjPlayer.prototype.setState = function (state) {
    this.state = state;
    this.article.dataset.state = state;
    this.article.setAttribute('aria-pressed', 'playing' === state ? 'true' : 'false');
  };

  Instance.prototype.initDjPlayers = function () {
    var self = this;
    this.qa('.ev-dj[data-audio]').forEach(function (el) {
      self.players.push(new DjPlayer(el, self));
    });
  };

  /* ═════════════════ DJ rail — pin at 50% then drive X ═══════════ */

  Instance.prototype.bindDjRail = function () {
    var self = this;
    var stickyEl = this.q1('.ev-dj-sticky');
    var rail = this.q1('.ev-dj-rail');
    var meterFill = this.q1('.ev-dj-meter-fill');
    var meterWrap = meterFill ? meterFill.parentElement : null;
    var hint = this.q1('.ev-hint');
    if (!stickyEl || !rail) { return; }

    function maxX() { return Math.max(0, rail.scrollWidth - rail.clientWidth); }

    function nativeSync() {
      if (!meterFill) { return; }
      var m = maxX();
      meterFill.style.width = m > 0 ? ((rail.scrollLeft / m) * 100).toFixed(2) + '%' : '0%';
    }

    function nativeFallback() {
      rail.classList.remove('is-jacked');
      if (meterWrap) { meterWrap.classList.remove('is-jacked'); }
      self.on(rail, 'scroll', nativeSync, { passive: true });
      nativeSync();
    }

    if (REDUCE || !w.gsap || !w.ScrollTrigger || maxX() <= 4) {
      nativeFallback();
      return;
    }

    rail.classList.add('is-jacked');
    if (meterWrap) { meterWrap.classList.add('is-jacked'); }
    rail.scrollLeft = 0;
    w.gsap.set(rail, { x: 0 });

    var tween = w.gsap.to(rail, {
      x: function () { return -maxX(); },
      ease: 'none',
      scrollTrigger: {
        id: self.stId('dj-rail'),
        scroller: self.scroller,
        trigger: stickyEl,
        start: 'center center',
        end: function () { return '+=' + Math.max(maxX(), 1); },
        pin: stickyEl,
        pinSpacing: true,
        scrub: 0.65,
        anticipatePin: 1,
        invalidateOnRefresh: true,
        onUpdate: function (st) {
          if (meterFill) { meterFill.style.width = (st.progress * 100).toFixed(2) + '%'; }
          if (hint) { hint.style.opacity = st.progress > 0.02 ? '0' : '1'; }
        }
      }
    });
    this.track(tween.scrollTrigger);

    /* Late-loading images grow scrollWidth — the pin end must follow. */
    Array.prototype.forEach.call(rail.querySelectorAll('img'), function (img) {
      if (!img.complete) {
        self.on(img, 'load', function () { w.ScrollTrigger.refresh(); }, { once: true });
      }
    });

    var cards = rail.querySelectorAll('.ev-dj');
    if (cards.length) {
      this.track(
        w.gsap.from(cards, {
          x: 40,
          opacity: 0,
          duration: 0.8,
          stagger: 0.08,
          ease: 'expo.out',
          scrollTrigger: stCfg(self, 'dj-cards', rail, 'top 88%')
        }).scrollTrigger
      );
    }
  };

  /* ═══════════════════════ GSAP scenes ═══════════════════════════ */

  Instance.prototype.initAnim = function () {
    var self = this;
    if ('undefined' === typeof w.gsap) { return; }

    if (REDUCE) { w.gsap.defaults({ duration: 0.001 }); }

    /* Hero copy — always runs, never gated on ScrollTrigger. */
    var title = this.q('hero-title');
    if (title) {
      w.gsap.from(title, { y: 26, opacity: 0, duration: 1.1, ease: 'expo.out', delay: 0.15 });
    }

    var metaSpans = this.qa('.ev-meta span');
    if (metaSpans.length) {
      w.gsap.from(metaSpans, {
        y: 14, opacity: 0, duration: 0.8, stagger: 0.07, ease: 'power3.out', delay: 0.4
      });
    }

    /*
     * About words. Runs with OR without ScrollTrigger — the original bug was a
     * silent no-op when ScrollTrigger was absent, which left the words stuck
     * at translateY(110%) forever and the section looking empty.
     */
    this.qa('[data-reveal-word]').forEach(function (word, i) {
      if (w.ScrollTrigger) {
        w.gsap.to(word, {
          y: 0,
          duration: 1.1,
          ease: 'expo.out',
          delay: i * 0.08,
          scrollTrigger: stCfg(self, 'word-' + i, word, 'top 90%')
        });
      } else {
        w.gsap.to(word, { y: 0, duration: 1.1, ease: 'expo.out', delay: 0.3 + i * 0.08 });
      }
    });

    if (!w.ScrollTrigger) {
      this.bindDjRail();
      return;
    }

    /* Hero parallax */
    var heroBg = this.q('hero-bg');
    var hero = this.q('hero');
    if (heroBg && hero) {
      this.track(
        w.gsap.to(heroBg, {
          yPercent: 14,
          ease: 'none',
          scrollTrigger: {
            id: self.stId('hero-par'),
            scroller: self.scroller,
            trigger: hero,
            start: 'top top',
            end: 'bottom top',
            scrub: true
          }
        }).scrollTrigger
      );
    }

    /* Facts + panels soft reveal — enters on scroll in, reverses on scroll out. */
    this.qa(REVEAL_ANIM)
      .forEach(function (el, i) {
        self.track(
          w.gsap.from(el, {
            y: 34,
            opacity: 0,
            duration: 0.95,
            ease: 'power3.out',
            scrollTrigger: stCfg(self, 'reveal-' + i, el, 'top 88%')
          }).scrollTrigger
        );
      });

    /* Footer parallax */
    var footImg = this.q1('.ev-foot img');
    var foot = this.q('footer');
    if (footImg && foot) {
      this.track(
        w.gsap.to(footImg, {
          yPercent: 18,
          ease: 'none',
          scrollTrigger: {
            id: self.stId('foot-par'),
            scroller: self.scroller,
            trigger: foot,
            start: 'top bottom',
            end: 'bottom top',
            scrub: true
          }
        }).scrollTrigger
      );
    }

    this.bindDjRail();
    w.ScrollTrigger.refresh();
  };

  /* ═══════════════════════ public API ════════════════════════════ */

  function mount(root) {
    if (!root) { return null; }
    if (root.__apolloEvent) { return root.__apolloEvent; }

    var inst = new Instance(root).mount();
    instances.push(inst);
    return inst;
  }

  function unmount(root) {
    var inst = root && root.__apolloEvent;
    if (inst) { inst.unmount(); }
  }

  function mountAll(scope) {
    var host = scope || d;
    var roots = host.querySelectorAll('[data-ev-root]');
    var found = [];

    Array.prototype.forEach.call(roots, function (root) {
      var inst = mount(root);
      if (inst) { found.push(inst); }
    });

    /*
     * Page mode renders WITHOUT the .ev-root wrapper (the document IS the
     * instance), so fall back to <body> when no wrapper exists.
     */
    if (!roots.length && d.body && d.body.classList.contains('apollo-single-event') && !d.body.__apolloEvent) {
      d.body.setAttribute('data-ev-mode', 'page');
      var pageInst = mount(d.body);
      if (pageInst) { found.push(pageInst); }
    }

    return found;
  }

  w.ApolloEventSingle = {
    mount: mount,
    unmount: unmount,
    mountAll: mountAll,
    get: function (root) { return root && root.__apolloEvent; },
    active: function () { return instances[instances.length - 1] || null; },
    instances: function () { return instances.slice(); }
  };

  /* ══════════════════════════ boot ═══════════════════════════════
     Gated on apollo:ready — GSAP/Lenis are owned by core.js and are NOT
     guaranteed to exist at parse time. Never DOMContentLoaded.            */

  function boot() { mountAll(d); }

  if (w.Apollo && 'function' === typeof w.Apollo.whenReady) {
    w.Apollo.whenReady(boot);
  } else {
    w.addEventListener('apollo:ready', boot, { once: true });
    if (w.Apollo && w.Apollo.isReady) { boot(); }
  }
})(window, document);
