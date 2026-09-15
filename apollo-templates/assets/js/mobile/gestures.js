/**
 * ApolloGestures — premium mobile touch map (plug-n-play).
 *
 *   quick short touch  → hover preview  (.ap-hover / is-hover)
 *   regular touch      → click / activate
 *   long touch, no move → context (right-click) with intelligent URL
 *
 * Intelligent URL resolution order:
 *   1. data-ap-url / data-url / data-href
 *   2. href on <a>
 *   3. data-ap-open / data-ev-open → surface permalink map (resolver hook)
 *   4. custom resolveUrl(el) from configure()
 *   5. mapa place.url via data-pid + APOLLO_MAPA_PLACES
 *
 * Bind anything: ApolloGestures.mount(root) or data-ap-gesture on a host.
 * Opt out: data-ap-gesture="off" on an element.
 *
 * @global window.ApolloGestures
 */
(function (w, d) {
  'use strict';

  if (w.ApolloGestures && w.ApolloGestures.__v) return;

  var HOVER_MS = 90;
  var LONG_MS = 480;
  var MOVE_PX = 10;
  var   DEFAULT_SEL =
    '[data-ap-open],[data-ev-open],[data-ap-card],a[href].card,[data-pid].pcm,[data-pid].pr,.mapa-app [data-pid],.mapa-app .place-card';

  var cfg = {
    selectors: DEFAULT_SEL,
    resolveUrl: null,
    onHover: null,
    onActivate: null,
    onContext: null,
    longMs: LONG_MS,
    hoverMs: HOVER_MS,
    movePx: MOVE_PX
  };

  var mounted = [];
  var menuEl = null;

  function closestTarget(node) {
    if (!node || !node.closest) return null;
    var el = node.closest(cfg.selectors);
    if (!el) return null;
    if (el.closest('[data-ap-gesture="off"]')) return null;
    return el;
  }

  function placeUrlByPid(pid) {
    var places = w.APOLLO_MAPA_PLACES;
    if (!pid || !Array.isArray(places)) return '';
    for (var i = 0; i < places.length; i++) {
      if (String(places[i].id) === String(pid)) {
        return places[i].url || places[i].link || '';
      }
    }
    return '';
  }

  /**
   * Intelligent map: card / post → single-page URL.
   */
  function resolveUrl(el) {
    if (!el) return '';
    if (typeof cfg.resolveUrl === 'function') {
      try {
        var custom = cfg.resolveUrl(el);
        if (custom) return String(custom);
      } catch (e) { /* isolate */ }
    }
    var u =
      el.getAttribute('data-ap-url') ||
      el.getAttribute('data-url') ||
      el.getAttribute('data-href') ||
      '';
    if (u) return u;
    if (el.tagName === 'A' && el.getAttribute('href') && el.getAttribute('href') !== '#') {
      return el.href || el.getAttribute('href');
    }
    var a = el.querySelector && el.querySelector('a[href]:not([href="#"])');
    if (a) return a.href || a.getAttribute('href') || '';

    var open = el.getAttribute('data-ap-open') || el.getAttribute('data-ev-open') || '';
    if (open && typeof cfg.resolveUrl === 'function') {
      /* already tried — surface map can be plugged via configure */
    }
    var pid = el.getAttribute('data-pid');
    if (pid) {
      var pu = placeUrlByPid(pid);
      if (pu) return pu;
    }
    /* Surface contract: data-ap-open="type:id" — find sibling/self href */
    if (open) {
      var href = el.getAttribute('href');
      if (href && href !== '#') return el.href || href;
    }
    return '';
  }

  function setHover(el, on) {
    if (!el) return;
    el.classList.toggle('ap-hover', on);
    el.classList.toggle('is-hover', on);
    if (on && typeof cfg.onHover === 'function') {
      try {
        cfg.onHover(el);
      } catch (e) { /* isolate */ }
    }
  }

  function clearHover() {
    var nodes = d.querySelectorAll('.ap-hover, .is-hover');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].classList.remove('ap-hover', 'is-hover');
    }
  }

  function ensureMenu() {
    if (menuEl && menuEl.isConnected) return menuEl;
    menuEl = d.createElement('div');
    menuEl.className = 'ap-ctx-menu';
    menuEl.setAttribute('role', 'menu');
    menuEl.hidden = true;
    d.body.appendChild(menuEl);
    d.addEventListener(
      'click',
      function (e) {
        if (!menuEl || menuEl.hidden) return;
        if (!menuEl.contains(e.target)) hideMenu();
      },
      true
    );
    return menuEl;
  }

  function hideMenu() {
    if (!menuEl) return;
    menuEl.hidden = true;
    menuEl.innerHTML = '';
  }

  function showContext(el, x, y) {
    var url = resolveUrl(el);
    if (typeof cfg.onContext === 'function') {
      try {
        if (cfg.onContext(el, url, { x: x, y: y }) === false) return;
      } catch (e) { /* isolate */ }
    }

    var menu = ensureMenu();
    var title = (el.getAttribute('data-ap-title') || el.getAttribute('aria-label') || el.textContent || 'Abrir')
      .trim()
      .slice(0, 48);
    menu.innerHTML =
      '<div class="ap-ctx-menu__title">' +
      esc(title) +
      '</div>' +
      (url
        ? '<button type="button" role="menuitem" data-act="open">Abrir página</button>' +
          '<button type="button" role="menuitem" data-act="copy">Copiar link</button>' +
          '<a role="menuitem" class="ap-ctx-menu__link" href="' +
          esc(url) +
          '" target="_blank" rel="noopener">Abrir em nova aba</a>'
        : '<div class="ap-ctx-menu__empty">Sem URL neste card</div>');
    menu.hidden = false;
    var vw = w.innerWidth || 360;
    var vh = w.innerHeight || 640;
    var mw = 220;
    var mh = 160;
    var left = Math.max(8, Math.min(x, vw - mw - 8));
    var top = Math.max(8, Math.min(y, vh - mh - 8));
    menu.style.left = left + 'px';
    menu.style.top = top + 'px';

    menu.onclick = function (ev) {
      var btn = ev.target.closest('[data-act]');
      if (!btn) return;
      var act = btn.getAttribute('data-act');
      if (act === 'open' && url) {
        w.location.href = url;
      } else if (act === 'copy' && url) {
        copyText(url);
      }
      hideMenu();
    };
  }

  function copyText(text) {
    if (w.navigator && navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).catch(function () {
        fallbackCopy(text);
      });
    } else {
      fallbackCopy(text);
    }
  }

  function fallbackCopy(text) {
    try {
      var ta = d.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      d.body.appendChild(ta);
      ta.select();
      d.execCommand('copy');
      d.body.removeChild(ta);
    } catch (e) { /* ignore */ }
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function suppressNextClick(el) {
    var block = function (ev) {
      ev.preventDefault();
      ev.stopPropagation();
      el.removeEventListener('click', block, true);
    };
    el.addEventListener('click', block, true);
    setTimeout(function () {
      el.removeEventListener('click', block, true);
    }, 450);
  }

  function bindRoot(root) {
    if (!root || root.__apGesturesBound) return;
    root.__apGesturesBound = true;

    var state = null;

    function wipeTimers(s) {
      if (!s) return;
      if (s.hoverT) clearTimeout(s.hoverT);
      if (s.longT) clearTimeout(s.longT);
      s.hoverT = s.longT = null;
    }

    function end(ev, cancelled) {
      if (!state) return;
      var s = state;
      state = null;
      wipeTimers(s);

      if (s.longFired) {
        setHover(s.el, false);
        suppressNextClick(s.el);
        return;
      }
      if (cancelled || s.moved) {
        setHover(s.el, false);
        return;
      }
      var dt = Date.now() - s.t0;
      if (dt < cfg.hoverMs) {
        /* quick short touch = hover preview only (no click) */
        setHover(s.el, true);
        suppressNextClick(s.el);
        setTimeout(function () {
          setHover(s.el, false);
        }, 420);
        return;
      }
      /* regular touch → native click (mapa / lightbox listeners) */
      setHover(s.el, false);
      if (typeof cfg.onActivate === 'function') {
        try {
          cfg.onActivate(s.el, ev);
        } catch (e) { /* isolate */ }
      }
    }

    root.addEventListener(
      'touchstart',
      function (ev) {
        if (ev.touches.length !== 1) return;
        var el = closestTarget(ev.target);
        if (!el) return;
        hideMenu();
        var t = ev.touches[0];
        state = {
          el: el,
          x0: t.clientX,
          y0: t.clientY,
          t0: Date.now(),
          moved: false,
          longFired: false,
          hoverT: null,
          longT: null
        };
        state.hoverT = setTimeout(function () {
          if (state && state.el === el && !state.moved) setHover(el, true);
        }, cfg.hoverMs);
        state.longT = setTimeout(function () {
          if (!state || state.el !== el || state.moved) return;
          state.longFired = true;
          try {
            if (navigator.vibrate) navigator.vibrate(12);
          } catch (e) { /* ignore */ }
          setHover(el, false);
          showContext(el, state.x0, state.y0);
        }, cfg.longMs);
      },
      { passive: true }
    );

    root.addEventListener(
      'touchmove',
      function (ev) {
        if (!state || !ev.touches[0]) return;
        var t = ev.touches[0];
        var dx = t.clientX - state.x0;
        var dy = t.clientY - state.y0;
        if (dx * dx + dy * dy > cfg.movePx * cfg.movePx) {
          state.moved = true;
          wipeTimers(state);
          setHover(state.el, false);
        }
      },
      { passive: true }
    );

    root.addEventListener(
      'touchend',
      function (ev) {
        end(ev, false);
      },
      { passive: true }
    );
    root.addEventListener(
      'touchcancel',
      function (ev) {
        end(ev, true);
      },
      { passive: true }
    );

    /* Desktop parity: contextmenu uses same URL map. */
    root.addEventListener('contextmenu', function (ev) {
      var el = closestTarget(ev.target);
      if (!el) return;
      var url = resolveUrl(el);
      if (!url) return;
      ev.preventDefault();
      showContext(el, ev.clientX, ev.clientY);
    });

    mounted.push(root);
  }

  function mount(root) {
    bindRoot(root || d);
    return Api;
  }

  function configure(next) {
    if (!next || typeof next !== 'object') return Api;
    for (var k in next) {
      if (Object.prototype.hasOwnProperty.call(next, k) && next[k] != null) {
        cfg[k] = next[k];
      }
    }
    return Api;
  }

  function registerUnits(S) {
    if (!S || typeof S.register !== 'function') return;
    S.register({
      id: 'gestures.bind',
      critical: false,
      timeout: 2000,
      retries: 1,
      run: function () {
        mount(d);
        var hosts = d.querySelectorAll('[data-ap-gesture="host"]');
        for (var i = 0; i < hosts.length; i++) mount(hosts[i]);
        return { mounted: mounted.length };
      },
      fallback: function () {
        return { degraded: true };
      }
    });
  }

  var Api = {
    __v: 1,
    configure: configure,
    mount: mount,
    resolveUrl: resolveUrl,
    hideMenu: hideMenu,
    registerUnits: registerUnits,
    config: function () {
      return cfg;
    }
  };

  w.ApolloGestures = Api;
})(window, document);
