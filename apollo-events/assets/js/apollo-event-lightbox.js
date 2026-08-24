/*!
 * Apollo Events — Single Event lightbox
 * ---------------------------------------------------------------------------
 * Opens the FULL single event page inside any other page, with no iframe.
 *
 * Trigger (anywhere in the document):
 *   <a href="/evento/slug" data-ev-open="123">…</a>
 *   <button data-ev-open="123">…</button>
 *
 * Programmatic:
 *   ApolloEventLightbox.open(123)
 *   ApolloEventLightbox.close()
 *
 * The body comes from GET /apollo/v1/eventos/{id}/fragmento — the very same
 * PHP renderer that builds /evento/{slug} — so the lightbox and the page can
 * never drift apart. Responses are cached per id for the session.
 *
 * Anchors keep working with the real permalink: middle-click, ctrl-click and
 * "open in new tab" fall through untouched, and history.pushState keeps the
 * URL shareable while the lightbox is open.
 *
 * ONE RUNTIME, EVERY SURFACE
 * --------------------------
 * Despite living in apollo-events, this serves every type registered through
 * apollo-core's Surface Contract. Endpoints come from window.APOLLO_SURFACES,
 * published by PHP — this file never learns a path, so adding a surface stays
 * a PHP-only change.
 *
 * Injected fragments are HYDRATED (see hydrateScripts): a surface that ships
 * its behaviour as an inline <script> in its own markup — which apollo-djs
 * does, from a generated cell — wakes up without having to expose a mount API
 * this file would then have to know about. Events keep their explicit
 * ApolloEventSingle.mount() path, unchanged.
 */
(function (w, d) {
  'use strict';

  if (w.ApolloEventLightbox) { return; }

  var cache = {};
  var state = {
    el: null,
    body: null,
    loader: null,
    scroll: null,
    openId: null,
    openType: 'event',
    lastFocus: null,
    prevUrl: null,
    controller: null,
    bound: false,
    /* Fallback timer that guarantees .ev-lb-body gets .is-entered — see the
       long note in inject(). Without it the panel can render fully blank. */
    enterT: 0,
    /* Injection generation. Bumped on every inject() so an async hydration
       chain from a superseded fragment can detect it lost and stand down —
       see hydrateScripts(). Without it, opening two cards quickly can run
       two surfaces' runtimes against one container. */
    injectToken: 0
  };

  function conf() {
    return w.APOLLO_EVENT_LB || {};
  }

  /**
   * Force any absolute REST root onto the CURRENT origin.
   *
   * WHY (transport hardening, 2026-08-05)
   * -------------------------------------
   * Both sources below hand back an ABSOLUTE url built server-side from
   * home_url()/rest_url(). The moment that recorded origin differs from the
   * origin the visitor is actually on, this stops being a same-origin request
   * and starts being a CORS one — and it then fails in two ways at once:
   *
   *   · the request carries an X-WP-Nonce header, which is not a CORS-safe
   *     listed header, so the browser fires an OPTIONS preflight first;
   *     WordPress does not answer preflights for apollo/v1, so it dies there;
   *   · `credentials: 'same-origin'` means the auth cookie is NOT attached
   *     cross-origin, so even a request that got through would be treated as
   *     a guest and could not read a private/draft fragment.
   *
   * Real ways the origins drift apart on this install, none of them exotic:
   *   www vs apex, http vs https before really-simple-ssl rewrites, a cached
   *   page generated under one host and served under another, and any CDN or
   *   preview host that fronts the site.
   *
   * Rewriting to a same-origin path costs nothing when the origins already
   * match (identical URL out) and converts a hard failure into a working
   * request when they do not. Relative/root-relative values pass through
   * untouched — they are already same-origin by definition.
   *
   * @param {string} url Absolute or root-relative URL.
   * @return {string} Same-origin equivalent.
   */
  function sameOrigin(url) {
    if (!url || '/' === url.charAt(0)) { return url; }
    try {
      var u = new w.URL(url, w.location.href);
      if (u.origin === w.location.origin) { return url; }
      return u.pathname + u.search + u.hash;
    } catch (e) {
      /* Malformed value — hand it back rather than guessing. */
      return url;
    }
  }

  /*
   * Endpoint for a surface type. Events keep their existing config object so
   * nothing about /eventos or /portal changes; any other type is looked up in
   * window.APOLLO_SURFACES, which PHP builds from the surface registry
   * (apollo-core/includes/surface-contract.php). Adding a surface is therefore
   * a PHP-only change — this file never learns a path.
   */
  function restBase(type) {
    if (type && 'event' !== type) {
      var s = (w.APOLLO_SURFACES || {})[type];
      if (s && s.rest) { return sameOrigin(s.rest); }
    }
    var c = conf();
    if (c.rest) { return sameOrigin(c.rest); }
    /* Fall back to the site's default REST root. */
    var root = (w.wpApiSettings && w.wpApiSettings.root) ? w.wpApiSettings.root : '/wp-json/';
    return sameOrigin(root) + 'apollo/v1/eventos/';
  }

  function shell() {
    if (state.el && d.contains(state.el)) { return state.el; }

    state.el = d.querySelector('[data-ev-lightbox]');
    if (!state.el) { return null; }

    /* ── PORTAL THE SHELL TO <body> (2026-08-01) ────────────────────────────
       apollo_event_lightbox_boot() prints this markup wherever the calling
       template sits — on /eventos that is inside <main class="ax-main">. Two
       things then break, and neither is fixable with z-index:

         · CLIPPING. .ax-main carries `overflow-x: clip`. Unlike
           `overflow: hidden`, `clip` clips FIXED-POSITION descendants too, so
           a position:fixed full-viewport overlay gets cut down to the main
           column's box. That is the "content goes white / cut off below the
           fold" symptom — the element is painted, then clipped away.
         · STACKING. A clipping ancestor scopes its descendants' z-index into
           its own stacking context. Inside it, .ev-lb's z-index competes only
           with .ax-main's children — never with .ax-top (9901) or .ax-aside
           (9902), which are siblings of .ax-main. So the topbar paints over
           the "full-screen" overlay no matter how high the number goes.
           10500 vs 9902 is irrelevant when they are not in the same context.

       Re-parenting to <body> puts the overlay in the ROOT stacking context,
       where its z-index is finally compared against the shell chrome, and
       removes every ancestor that could clip it. This is the standard portal
       pattern for modals and is done once, lazily, on first open — the markup
       stays wherever PHP put it until something actually needs it. */
    if (state.el.parentNode !== d.body) {
      d.body.appendChild(state.el);
    }

    state.body = state.el.querySelector('[data-ev-lb-body]');
    state.loader = state.el.querySelector('[data-ev-lb-load]');
    state.scroll = state.el.querySelector('[data-ev-lb-scroll]');

    if (!state.bound) {
      state.bound = true;

      state.el.addEventListener('click', function (e) {
        if (e.target.closest('[data-ev-lb-close]')) {
          e.preventDefault();
          close();
        }
      });

      d.addEventListener('keydown', function (e) {
        if ('Escape' !== e.key || null === state.openId) { return; }
        /* Let the instance close its own overlays first. */
        var inner = state.body && state.body.querySelector('.ev-chat.is-open, .ev-lightbox.is-open');
        if (inner) { return; }
        close();
      });

      /* Keep focus inside the panel while it is open. */
      d.addEventListener('focusin', function (e) {
        if (null === state.openId || !state.el) { return; }
        if (!state.el.contains(e.target)) {
          var first = state.el.querySelector('[data-ev-lb-close]');
          if (first) { first.focus(); }
        }
      });
    }

    return state.el;
  }

  function setLoading(on) {
    if (state.loader) { state.loader.hidden = !on; }
  }

  /* How long to wait before treating a request as hung. The fragment is a
     single already-rendered page, so anything past this is a stalled socket,
     not a slow query. */
  var TIMEOUT_MS = 12000;

  /**
   * One attempt at the fragment endpoint.
   *
   * Hardened over the original in four ways, all failure modes seen on this
   * install rather than hypotheticals:
   *
   *  1. TIMEOUT — the original had none, so a stalled connection left the
   *     loader spinning forever with no path back to the real page. The abort
   *     is tagged `TimeoutError` so the caller can tell it apart from a user
   *     -initiated abort (closing the panel), which must stay silent.
   *  2. NON-JSON BODIES — r.json() on an HTML response throws "Unexpected
   *     token <", which says nothing useful. A security plugin (loginizer),
   *     a WAF, a maintenance page or a login wall all answer HTML with a 200.
   *     Read as text first and report what actually came back.
   *  3. REDIRECTS — `redirect: 'follow'` is the default, and a redirect to a
   *     DIFFERENT origin silently turns an opaque response into a confusing
   *     parse error. Kept explicit so the intent is visible.
   *  4. CACHE — 'no-store' keeps a stale bfcache/proxy copy of the fragment
   *     from being replayed after the event was edited.
   *
   * @param {number} id     Event post ID.
   * @param {AbortSignal} signal Caller-owned signal.
   * @return {Promise<Object>}
   */
  function requestFragment(id, signal, type) {
    var c = conf();
    var url = restBase(type) + encodeURIComponent(id) + '/fragmento';

    var timer = null;
    var localAbort = new AbortController();
    /* Chain the caller's signal into ours so either can cancel. */
    if (signal) {
      if (signal.aborted) { localAbort.abort(); }
      else { signal.addEventListener('abort', function () { localAbort.abort(); }, { once: true }); }
    }
    var timedOut = false;
    timer = w.setTimeout(function () {
      timedOut = true;
      localAbort.abort();
    }, TIMEOUT_MS);

    var headers = { 'Accept': 'application/json' };
    if (c.nonce) { headers['X-WP-Nonce'] = c.nonce; }

    return fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      redirect: 'follow',
      headers: headers,
      signal: localAbort.signal
    })
      .then(function (r) {
        return r.text().then(function (text) {
          return { ok: r.ok, status: r.status, text: text };
        });
      })
      .then(function (res) {
        if (!res.ok) { throw new Error('HTTP ' + res.status); }
        var json;
        try {
          json = JSON.parse(res.text);
        } catch (e) {
          /* Name the real problem instead of "Unexpected token <". */
          throw new Error('non-JSON response (' + res.status + ') — a security layer, cache or login wall likely answered instead of the REST route');
        }
        var payload = (json && json.data) || json;
        if (!payload || !payload.html) { throw new Error('empty fragment'); }
        return payload;
      })
      .catch(function (err) {
        if (timedOut) {
          var t = new Error('timeout after ' + TIMEOUT_MS + 'ms');
          t.name = 'TimeoutError';
          throw t;
        }
        throw err;
      })
      .then(
        function (v) { w.clearTimeout(timer); return v; },
        function (e) { w.clearTimeout(timer); throw e; }
      );
  }

  function fetchFragment(id, type) {
    /* CACHE KEY IS TYPE-SCOPED. Event 5 and DJ 5 are different documents; a
       bare id would serve one as the other the moment a second surface exists. */
    var key = (type || 'event') + ':' + id;
    if (cache[key]) { return Promise.resolve(cache[key]); }

    if (state.controller) { state.controller.abort(); }
    state.controller = new AbortController();
    var signal = state.controller.signal;

    /* ONE retry, and only for transport-level failures.
       A network blip, a dropped keep-alive socket or a cold-start timeout are
       all recoverable and cost the user nothing to retry. An HTTP status or a
       malformed payload is deterministic — retrying it just doubles the wait
       before the permalink fallback kicks in, so those rethrow immediately.
       Not retried either if the caller aborted (panel closed / another card
       opened), which must stay silent. */
    return requestFragment(id, signal, type)
      .catch(function (err) {
        if (signal.aborted) { throw err; }
        var recoverable = err && ('TimeoutError' === err.name || 'TypeError' === err.name);
        if (!recoverable) { throw err; }
        return requestFragment(id, signal, type);
      })
      .then(function (payload) {
        cache[key] = payload;
        return payload;
      });
  }

  /**
   * Types innerHTML-injected <script> nodes are allowed to be re-executed as.
   *
   * Anything else — application/json, application/ld+json, text/template — is
   * DATA, and the browser would never have executed it in the first place.
   * Re-creating those nodes is inert either way, but the allow-list keeps the
   * intent explicit rather than relying on that.
   */
  var EXECUTABLE = { '': 1, 'text/javascript': 1, 'application/javascript': 1, 'module': 1 };

  /**
   * Re-execute the scripts inside an injected fragment.
   *
   * WHY THIS EXISTS (2026-08-17)
   * ----------------------------
   * `innerHTML = html` never executes <script>. For the EVENT surface that was
   * exactly right and the original comment said so: the event fragment's only
   * script is an inert application/json config block, and its behaviour comes
   * from ApolloEventSingle.mount() afterwards.
   *
   * That stopped being the whole story the moment a second surface existed.
   * apollo-djs composes its page from cells and ships its runtime as an inline
   * IIFE in the `scripts` cell — and that cell is GENERATED from the approved
   * mockup by _sandbox/build-dj-cells.py, carrying a "do not hand-edit" banner.
   * So a DJ fragment injected without this step renders as correct but DEAD
   * html: no dock, no reveals, no shares, no scroll progress.
   *
   * WHY NOT A PER-SURFACE mount() API INSTEAD. That was the first design, and
   * it is wrong here: it would require hand-editing exactly the generated cell
   * that must not be hand-edited, once per surface, forever. Re-executing the
   * fragment's own scripts costs each plugin NOTHING and works for every
   * surface that will ever be registered — which is the property that makes
   * "any CPT" true rather than "any CPT we remembered to wire".
   *
   * SAFETY
   * ------
   * · Order is preserved, and a src= script blocks the ones after it, because
   *   a runtime that depends on a library loading first has to keep that
   *   guarantee. The browser gives it for free in a parsed document; here it
   *   has to be re-created deliberately.
   * · A stale injection is abandoned mid-chain (`token !== state.injectToken`)
   *   so a fast second open cannot interleave two fragments' runtimes.
   * · Failures are contained per script: one broken cell must not stop the
   *   rest of the page from waking up.
   * · Document-level listeners bound by a fragment runtime OUTLIVE the panel,
   *   because unmountCurrent() removes their targets but not the listeners.
   *   Their selector lookups then resolve to null and the handlers no-op. That
   *   is acceptable and deliberate — the alternative is demanding a teardown
   *   contract from generated code. Revisit if a surface ever binds something
   *   with side effects that survive its own DOM.
   *
   * @param {HTMLElement} scope Container holding the freshly-injected markup.
   * @param {number} token Injection generation this call belongs to.
   * @return {Promise<void>}
   */
  function hydrateScripts(scope, token) {
    var nodes = scope ? Array.prototype.slice.call(scope.querySelectorAll('script')) : [];

    return nodes.reduce(function (chain, old) {
      return chain.then(function () {
        /* A newer open() superseded us — stop, do not half-mount two pages. */
        if (token !== state.injectToken) { return; }

        var type = (old.getAttribute('type') || '').toLowerCase();
        if (!EXECUTABLE[type]) { return; }
        /* Detached between steps — the panel emptied under us. Nothing to
           replace, and replaceChild on a null parent would abort the chain
           for every script after this one. */
        if (!old.parentNode) { return; }

        var fresh = d.createElement('script');
        for (var i = 0; i < old.attributes.length; i++) {
          fresh.setAttribute(old.attributes[i].name, old.attributes[i].value);
        }

        if (!old.src) {
          fresh.textContent = old.textContent;
          old.parentNode.replaceChild(fresh, old);
          return;
        }

        /* External: wait for it, so anything after it can rely on it. */
        return new Promise(function (resolve) {
          var done = false;
          var finish = function () { if (!done) { done = true; resolve(); } };
          fresh.onload = finish;
          fresh.onerror = finish;
          old.parentNode.replaceChild(fresh, old);
          /* A cached script can fire before the handler attaches, and a blocked
             one may never fire at all. Never hang the chain on either. */
          w.setTimeout(finish, 8000);
        });
      });
    }, Promise.resolve()).catch(function () {
      /* Contained on purpose — see SAFETY above. */
    });
  }

  /**
   * Inject a fragment and mount its runtime.
   *
   * Two mount paths, in this order:
   *   1. hydrateScripts() — universal, wakes any surface's own inline runtime.
   *   2. ApolloEventSingle.mount() — the event surface's explicit mount, kept
   *      exactly as it was because the event fragment carries no executable
   *      script and gets all of its behaviour from that call.
   */
  function inject(payload) {
    if (!state.body) { return null; }

    unmountCurrent();

    /* ── SMOOTH CONTENT ENTRY (2026-08-06) ──────────────────────────────────
       The panel itself already animates (transform .52s + opacity .3s on
       .ev-lb-panel), but it animates in EMPTY — the fragment only arrives when
       the fetch resolves, and `innerHTML = …` paints it in a single frame with
       no transition of its own. So the eye sees: panel glides in → beat of
       blank → content SNAPS. That hard cut mid-glide is the "stairs" feel; the
       panel motion was never the problem, the un-animated fill was.

       Fixing it by slowing the panel would only lengthen the blank beat. The
       content needs its own short entry, started on the frame AFTER paint so
       the browser has a from-state to interpolate from. Two rAFs: the first
       lands after innerHTML is committed, the second guarantees the initial
       style has been flushed before the class flips — a single rAF is
       occasionally coalesced with the mutation and the transition is skipped.

       Kept CSS-only (a class + a transition in styles-lightbox.php) rather than
       GSAP: this runs before ApolloEventSingle mounts, so GSAP may not be ready
       yet, and a transition that silently no-ops is worse than one that cannot. */
    /* ── THE ENTRY CLASS MUST NEVER BE THE REASON CONTENT IS INVISIBLE ───────
       VERIFIED BLANK ON /portal, 2026-08-07. `.ev-lb-body` ships opacity:0 and
       is cleared ONLY by `.is-entered`. So this class is not decoration — it is
       load-bearing, and any path that removes it without putting it back leaves
       a full-screen white panel with a working ✕ and nothing else. The fragment
       is in the DOM, ApolloEventSingle is mounted, GSAP is mid-tween on the hero
       title — all of it inside a container at opacity 0.

       Two rAFs are enough when nothing interrupts. They are not enough when
       anything does: a second open() (the click can reach both the portal's
       handler and this module's delegated listener), an abort, a fetch that
       resolves from cache on a later frame. Each of those re-enters here, strips
       the class, and can return down a path that never re-adds it.

       Same philosophy as unstrand() in portal/bootstrap.php: verify the OUTCOME,
       do not trust the mechanism. The rAF pair still owns the animation — this
       timer only guarantees the end state. If the class is already on when it
       fires, adding it again is a no-op and the transition is untouched. */
    state.body.classList.remove('is-entered');
    state.body.innerHTML = payload.html;

    var enter = function () {
      if (state.body) { state.body.classList.add('is-entered'); }
    };
    w.requestAnimationFrame(function () { w.requestAnimationFrame(enter); });
    w.clearTimeout(state.enterT);
    state.enterT = w.setTimeout(enter, 500);

    /* Wake the fragment's own runtime. Universal, and a no-op for the event
       surface, whose only script is inert JSON. Deliberately NOT awaited: the
       panel must never wait on a slow third-party <script src> to become
       visible, and the event mount below has no dependency on it. */
    state.injectToken++;
    hydrateScripts(state.body, state.injectToken);

    var root = state.body.querySelector('[data-ev-root]');
    if (root && w.ApolloEventSingle) {
      var inst = w.ApolloEventSingle.mount(root);
      /* Leaflet and ScrollTrigger both measured a hidden panel — remeasure. */
      w.setTimeout(function () {
        if (inst && 'function' === typeof inst.invalidateMap) { inst.invalidateMap(); }
        if (w.ScrollTrigger) { w.ScrollTrigger.refresh(); }
      }, 260);
      return inst;
    }
    return null;
  }

  function unmountCurrent() {
    if (!state.body) { return; }
    /* Invalidate any hydration chain still walking the outgoing fragment. It
       checks this token between scripts and stops, so a slow external script
       cannot execute against a container that has already been emptied. */
    state.injectToken++;
    var root = state.body.querySelector('[data-ev-root]');
    if (root && w.ApolloEventSingle) { w.ApolloEventSingle.unmount(root); }
    state.body.innerHTML = '';
  }

  function open(id, opts) {
    id = parseInt(id, 10);
    if (!id) { return Promise.resolve(null); }

    var el = shell();
    if (!el) { return Promise.resolve(null); }

    var options = opts || {};
    /* Surface type. Defaults to 'event' so every existing call site —
       ApolloEventLightbox.open(123) and every data-ev-open card — behaves
       exactly as before. */
    var type = options.type || 'event';
    state.lastFocus = d.activeElement;
    state.openId = id;
    state.openType = type;

    el.hidden = false;
    el.setAttribute('aria-hidden', 'false');
    d.body.classList.add('ev-lb-open');
    /*
     * Force a reflow to give the transition a frame to start from. Using
     * requestAnimationFrame here would never fire in a background/hidden tab
     * (rAF is throttled to zero), leaving the panel stuck at opacity:0.
     */
    void el.offsetWidth;
    el.classList.add('is-open');

    if (state.scroll) { state.scroll.scrollTop = 0; }
    setLoading(true);

    return fetchFragment(id, type)
      .then(function (payload) {
        if (state.openId !== id) { return null; }
        setLoading(false);
        var inst = inject(payload);

        if (payload.permalink && w.history && w.history.pushState) {
          state.prevUrl = state.prevUrl || (w.location.pathname + w.location.search);
          try {
            w.history.pushState({ apolloEvent: id }, '', payload.permalink);
          } catch (e) { /* cross-origin permalink — keep the current URL */ }
        }
        if (payload.title) { d.title = payload.title; }

        var x = el.querySelector('[data-ev-lb-close]');
        if (x) { x.focus(); }

        return inst;
      })
      .catch(function (err) {
        if (err && 'AbortError' === err.name) { return null; }
        setLoading(false);
        /* Never trap the user in an empty modal — fall back to the real page. */
        if (options.href) { w.location.href = options.href; }
        else { close(); }
        return null;
      });
  }

  function close() {
    var el = state.el;
    if (!el || null === state.openId) { return; }

    if (state.controller) {
      state.controller.abort();
      state.controller = null;
    }

    el.classList.remove('is-open');
    el.setAttribute('aria-hidden', 'true');
    d.body.classList.remove('ev-lb-open');
    state.openId = null;

    if (state.prevUrl && w.history && w.history.pushState) {
      try { w.history.pushState({}, '', state.prevUrl); } catch (e) {}
      state.prevUrl = null;
    }

    /* Wait for the close transition before tearing the instance down. */
    w.setTimeout(function () {
      if (null !== state.openId) { return; }
      unmountCurrent();
      el.hidden = true;
    }, 420);

    if (state.lastFocus && 'function' === typeof state.lastFocus.focus) {
      state.lastFocus.focus();
      state.lastFocus = null;
    }
  }

  /* ── delegated triggers ───────────────────────────────────────── */

  /*
   * TWO SPELLINGS, ONE RUNTIME (2026-08-07).
   *
   *   data-ev-open="123"        legacy, event-only. Every card on /eventos and
   *                             /portal uses it. Never breaks.
   *   data-ap-open="dj:123"     generic. Emitted by apollo_surface_open_attrs()
   *                             in apollo-core for ANY registered surface.
   *
   * The generic form is what lets one runtime serve events, DJs and locs
   * instead of each plugin growing its own overlay. The endpoint comes from
   * window.APOLLO_SURFACES, published by PHP from the surface registry — so
   * adding a surface is a PHP-only change and this file never needs to learn
   * about it.
   *
   * Unknown type → no preventDefault, and the anchor's real href takes the user
   * to the canonical page. A surface that is declared but dormant therefore
   * degrades to a normal link rather than a dead click.
   */
  function resolveTrigger(el) {
    var generic = el.closest('[data-ap-open]');
    if (generic) {
      var raw = String(generic.getAttribute('data-ap-open') || '');
      var cut = raw.indexOf(':');
      if (cut > 0) {
        return {
          node: generic,
          type: raw.slice(0, cut),
          id: parseInt(raw.slice(cut + 1), 10)
        };
      }
      return null;
    }
    var legacy = el.closest('[data-ev-open]');
    if (legacy) {
      return {
        node: legacy,
        type: 'event',
        id: parseInt(legacy.getAttribute('data-ev-open'), 10)
      };
    }
    return null;
  }

  d.addEventListener('click', function (e) {
    var t = resolveTrigger(e.target);
    if (!t || !t.id) { return; }

    /* Respect modifier clicks and middle-click on real anchors. */
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || 0 !== e.button) { return; }

    /* Only claim a type this runtime can actually serve. */
    var surfaces = w.APOLLO_SURFACES || {};
    if ('event' !== t.type && !surfaces[t.type]) { return; }

    e.preventDefault();
    open(t.id, { href: t.node.getAttribute('href') || '', type: t.type });
  });

  w.addEventListener('popstate', function () {
    if (null !== state.openId) { close(); }
  });

  w.ApolloEventLightbox = {
    open: open,
    close: close,
    isOpen: function () { return null !== state.openId; },
    currentId: function () { return state.openId; },
    currentType: function () { return state.openType; },
    prefetch: function (id, type) { return fetchFragment(parseInt(id, 10), type || 'event'); },
    clearCache: function () { cache = {}; }
  };

  /*
   * TYPE-NEUTRAL ALIAS. The runtime lives in apollo-events for historical
   * reasons, but it now serves every registered surface. A plugin integrating
   * DJs or locs should not have to reference an event-specific global to open
   * one of its own items, and should not have to care which plugin ships the
   * overlay. Same object, honest name.
   *
   *   ApolloLightbox.open( 123, { type: 'dj' } );
   */
  w.ApolloLightbox = w.ApolloEventLightbox;
})(window, document);
