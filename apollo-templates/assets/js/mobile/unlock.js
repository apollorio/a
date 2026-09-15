/**
 * ApolloMobileUnlock — brutal ahead lockers of sliding on mobile.
 *
 * Independent units (register via ApolloSupervisor from boot.js):
 *   · lenis.gate          stop Lenis on coarse pointers → native momentum
 *   · scroll.lockers.purge clear stuck scroll-locks / body overscroll traps
 *   · touch.watchdog       clear stuck sheet-drag flags / rescue touchcancel
 *
 * Safe to refine: each unit has its own fallback; none share mutable state
 * beyond documentElement class flags they own.
 *
 * @global window.ApolloMobileUnlock
 */
(function (w, d) {
  'use strict';

  if (w.ApolloMobileUnlock && w.ApolloMobileUnlock.__v) return;

  var COARSE = false;
  try {
    COARSE = !!(w.matchMedia && w.matchMedia('(pointer: coarse)').matches);
  } catch (e) {
    COARSE = 'ontouchstart' in w;
  }

  function root() {
    return d.documentElement;
  }

  function stopLenis() {
    var L = w.lenis;
    if (!L) return { skipped: true, reason: 'no-lenis' };
    try {
      if (typeof L.stop === 'function') L.stop();
      if (typeof L.destroy === 'function') L.destroy();
    } catch (e) {
      /* degrade: flag only */
    }
    try {
      w.lenis = null;
    } catch (e2) { /* frozen */ }
    root().classList.add('ap-native-scroll');
    root().classList.add('ap-lenis-gated');
    return { stopped: true };
  }

  function purgeLockers() {
    var html = root();
    var body = d.body;
    var actions = [];

    /* Global shell traps that kill slide-to-scroll on phone feeds.
       Keep mapa-scroll-lock when #mapaApp is present — map is a fixed stage;
       sheet-inner owns vertical scroll there. */
    var onMapa = !!d.getElementById('mapaApp');
    if (!onMapa) {
      if (html.classList.contains('mapa-scroll-lock')) {
        html.classList.remove('mapa-scroll-lock');
        actions.push('removed:mapa-scroll-lock');
      }
      if (w.Apollo && typeof w.Apollo.lockScroll === 'function') {
        try {
          w.Apollo.lockScroll(false);
          actions.push('Apollo.lockScroll(false)');
        } catch (e) { /* ignore */ }
      }
    }

    if (body) {
      /* Restore pan gestures; keep manipulation only where we opt-in. */
      if (COARSE) {
        body.style.touchAction = body.style.touchAction || 'pan-x pan-y';
        html.style.overscrollBehaviorY = html.style.overscrollBehaviorY || 'contain';
        body.style.overscrollBehaviorY = body.style.overscrollBehaviorY || 'contain';
        actions.push('touch-action:pan restored');
      }
    }

    html.classList.add('ap-unlock-live');
    return { actions: actions, mapa: onMapa, coarse: COARSE };
  }

  /**
   * Watchdog: if any screen sets window.__apSheetDragging and it sticks
   * after touchcancel/visibilitychange, force-clear so document touchmove
   * preventDefault cannot brick scrolling.
   */
  function installTouchWatchdog() {
    var clear = function () {
      try {
        if (w.__apSheetDragging) w.__apSheetDragging = false;
      } catch (e) { /* ignore */ }
      try {
        d.dispatchEvent(new CustomEvent('apollo:touch-watchdog-clear'));
      } catch (e2) { /* ignore */ }
    };

    d.addEventListener('touchcancel', clear, { passive: true });
    d.addEventListener('visibilitychange', function () {
      if (d.visibilityState === 'hidden') clear();
    });
    w.addEventListener('pagehide', clear);

    /* Soft rescue: if a non-passive touchmove listener left dragging true
       for > 2s with no move, clear. */
    var lastMove = nowish();
    d.addEventListener(
      'touchmove',
      function () {
        lastMove = nowish();
      },
      { passive: true }
    );
    w.setInterval(function () {
      if (w.__apSheetDragging && nowish() - lastMove > 2000) clear();
    }, 1000);

    return { watchdog: true };
  }

  function nowish() {
    return Date.now();
  }

  /**
   * Register unlock units on ApolloSupervisor (idempotent).
   */
  function registerUnits(S) {
    if (!S || typeof S.register !== 'function') return;
    S.register({
      id: 'lenis.gate',
      critical: false,
      timeout: 1500,
      retries: 1,
      run: function () {
        if (!COARSE) return { skipped: true, reason: 'pointer-fine' };
        return stopLenis();
      },
      fallback: function () {
        root().classList.add('ap-native-scroll');
        return { degraded: true };
      }
    });
    S.register({
      id: 'scroll.lockers.purge',
      critical: false,
      timeout: 1500,
      retries: 1,
      run: purgeLockers,
      fallback: function () {
        root().classList.add('ap-unlock-live');
        return { degraded: true };
      }
    });
    S.register({
      id: 'touch.watchdog',
      critical: false,
      timeout: 1500,
      retries: 0,
      run: installTouchWatchdog,
      fallback: function () {
        return { degraded: true };
      }
    });
  }

  w.ApolloMobileUnlock = {
    __v: 1,
    isCoarse: function () {
      return COARSE;
    },
    stopLenis: stopLenis,
    purgeLockers: purgeLockers,
    registerUnits: registerUnits
  };
})(window, document);
