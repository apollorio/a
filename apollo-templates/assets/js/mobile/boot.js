/**
 * Apollo mobile boot — register unlock + gesture units, run supervisor.
 * Fail-soft: never throws; partial results published on apollo:mobile-supervisor.
 */
(function (w, d) {
  'use strict';

  if (w.__APOLLO_MOBILE_BOOTED__) return;
  w.__APOLLO_MOBILE_BOOTED__ = true;

  function boot() {
    var S = w.ApolloSupervisor;
    var U = w.ApolloMobileUnlock;
    var G = w.ApolloGestures;
    if (!S) return;

    try {
      if (U && typeof U.registerUnits === 'function') U.registerUnits(S);
    } catch (e) { /* isolate */ }
    try {
      if (G && typeof G.registerUnits === 'function') G.registerUnits(S);
    } catch (e2) { /* isolate */ }

    S.runAll()
      .then(function (report) {
        w.__APOLLO_MOBILE_STATUS__ = report;
        if (report && report.hardStopped) {
          try {
            console.error('[ApolloMobile] critical path failed', report.statuses);
          } catch (e3) { /* ignore */ }
        }
      })
      .catch(function () {
        /* runAll itself must not reject; belt + suspenders */
      });
  }

  function whenReady(fn) {
    var ran = false;
    function once() {
      if (ran) return;
      ran = true;
      fn();
    }
    if (w.Apollo && typeof w.Apollo.whenReady === 'function') {
      w.Apollo.whenReady(once);
    } else {
      w.addEventListener('apollo:ready', once, { once: true });
    }
    if (d.readyState === 'complete' || d.readyState === 'interactive') {
      setTimeout(once, 0);
    } else {
      d.addEventListener('DOMContentLoaded', once, { once: true });
    }
    /* Lenis may appear after ready — re-run unlock gate soft */
    w.addEventListener(
      'apollo:lenis-ready',
      function () {
        if (w.ApolloMobileUnlock && w.ApolloMobileUnlock.isCoarse()) {
          try {
            w.ApolloMobileUnlock.stopLenis();
          } catch (e) { /* isolate */ }
        }
      },
      { once: true }
    );
  }

  whenReady(boot);
})(window, document);
