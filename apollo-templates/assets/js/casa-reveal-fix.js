/* casa-reveal-fix.js — sync-probe-reveal-837565
   1) Off-viewport reveal reverse (no mid-screen dismiss)
   2) Lighten /casa scroll: drop non-hero scrub, batch reveals, snappier Lenis */
(function () {
  'use strict';
  var applied = false;
  var lightened = false;

  function softenLenis() {
    var l = window.lenis;
    if (!l) return;
    try {
      if (l.options) {
        /* 0.1 felt syrupy under ST scrub; higher lerp = snappier catch-up */
        l.options.lerp = 0.22;
        l.options.wheelMultiplier = 1.12;
        l.options.touchMultiplier = 1.15;
        if (typeof l.options.syncTouch === 'boolean') l.options.syncTouch = true;
      }
      if (typeof l.resize === 'function') l.resize();
    } catch (e) { /* ignore */ }
  }

  function killHeavyScrub(ST) {
    var kill = {
      'casa-events': 1,
      'casa-events-title': 1,
      'casa-crash': 1,
      'casa-tracks-title': 1,
      'casa-resell': 1,
      'casa-resell-title': 1,
      'casa-map': 1
    };
    var isPhone = !!(window.matchMedia && window.matchMedia('(max-width: 719px)').matches);
    ST.getAll().forEach(function (t) {
      var id = t.vars && t.vars.id;
      if (!id) return;
      if (kill[id]) {
        t.kill();
        return;
      }
      /* Phone: hero scrub also fights touch scroll */
      if (isPhone && id === 'casa-hero') t.kill();
    });
    /* Parallax scrub nodes from motion cell — expensive on phone */
    if (isPhone) {
      ST.getAll().forEach(function (t) {
        if (t.vars && t.vars.scrub && t.trigger && t.trigger.hasAttribute && t.trigger.hasAttribute('data-casa-parallax')) {
          t.kill();
        }
      });
    }
  }

  function showStatic(els) {
    Array.prototype.forEach.call(els, function (el) {
      if (!el) return;
      el.classList.add('nh-st-drive', 'is-visible', 'ap-skip');
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
  }

  function batchReveal(gsap, ST, nodes) {
    if (!nodes.length || typeof ST.batch !== 'function') {
      /* Fallback: one ST per node is worse; set visible */
      showStatic(nodes);
      return;
    }
    Array.prototype.forEach.call(nodes, function (el) {
      el.classList.add('nh-st-drive', 'is-visible', 'ap-skip');
      gsap.set(el, { y: 22, opacity: 0, force3D: true });
    });
    ST.batch(nodes, {
      id: 'casa-batch-reveal',
      start: 'top 92%',
      end: 'bottom top',
      interval: 0.12,
      batchMax: 6,
      onEnter: function (batch) {
        gsap.to(batch, {
          y: 0,
          opacity: 1,
          duration: 0.45,
          stagger: 0.04,
          ease: 'power2.out',
          overwrite: 'auto',
          force3D: true
        });
      },
      onLeave: function (batch) {
        gsap.to(batch, {
          y: -12,
          opacity: 0,
          duration: 0.28,
          stagger: 0.02,
          ease: 'power1.in',
          overwrite: 'auto',
          force3D: true
        });
      },
      onEnterBack: function (batch) {
        gsap.to(batch, {
          y: 0,
          opacity: 1,
          duration: 0.4,
          stagger: 0.03,
          ease: 'power2.out',
          overwrite: 'auto',
          force3D: true
        });
      },
      onLeaveBack: function (batch) {
        gsap.to(batch, {
          y: 18,
          opacity: 0,
          duration: 0.28,
          stagger: 0.02,
          ease: 'power1.in',
          overwrite: 'auto',
          force3D: true
        });
      }
    });
  }

  function lighten(gsap, ST) {
    if (!gsap || !ST || lightened) return false;
    softenLenis();
    try {
      ST.config({ limitCallbacks: true, ignoreMobileResize: true });
    } catch (e) { /* ignore */ }
    /* Restore lagSmoothing — lagSmoothing(0) keeps ticking under load and feels heavy */
    try {
      if (gsap.ticker && typeof gsap.ticker.lagSmoothing === 'function') {
        gsap.ticker.lagSmoothing(500, 33);
      }
    } catch (e2) { /* ignore */ }

    killHeavyScrub(ST);

    /* Title chars / heads that lost scrub — paint once, no per-frame scrub */
    var home = document.getElementById('apollo-home');
    if (home) {
      showStatic(home.querySelectorAll(
        '#events-title .split-char, #tracks-title .split-char, #resell-title .split-char, #crash-title .split-char, .nh-section-head, #map .nh-section-head, #nhMap, .nh-map-wrap'
      ));
    }

    /* Kill per-card circus / duplicate toggles, rebuild as batch */
    ST.getAll().forEach(function (t) {
      var id = t.vars && t.vars.id;
      if (!id) return;
      if (/^(casa-copy-|casa-track-|casa-resell-card-|casa-fix-)/.test(String(id))) t.kill();
      if (id === 'casa-tracks' || id === 'casa-resell') t.kill();
      if (String(id).indexOf('casa-batch-') === 0) t.kill();
    });

    if (home) {
      var nodes = [];
      home.querySelectorAll(
        '.a-eve-card.ai, .nh-track-card, #resell .rt-card:not([aria-hidden="true"]), #resell .nh-mq-card:not([aria-hidden="true"]), .reveal-up.ai, .ai.reveal-up'
      ).forEach(function (el) {
        if (el.closest('.nh-hero') || el.closest('.nh-mq-track')) return;
        if (nodes.indexOf(el) !== -1) return;
        nodes.push(el);
      });
      batchReveal(gsap, ST, nodes);
    }

    try { ST.refresh(); } catch (e3) { /* ignore */ }
    lightened = true;
    applied = true;

    // #region agent log
    fetch('http://127.0.0.1:7754/ingest/da9d552b-a038-4061-bf95-e47d2c529b38', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '837565' },
      body: JSON.stringify({
        sessionId: '837565',
        runId: 'casa-lighten',
        hypothesisId: 'SCROLL-WEIGHT',
        location: 'casa-reveal-fix.js',
        message: 'casa scroll lighten applied',
        data: {
          stCount: ST.getAll().length,
          scrubLeft: ST.getAll().filter(function (t) { return t.vars && t.vars.scrub; }).length,
          lerp: window.lenis && window.lenis.options && window.lenis.options.lerp
        },
        timestamp: Date.now()
      })
    }).catch(function () {});
    // #endregion
    return true;
  }

  function attempt() {
    lighten(window.gsap, window.ScrollTrigger);
  }

  function boot() {
    softenLenis();
    attempt();
    if (window.Apollo && typeof window.Apollo.whenReady === 'function') {
      window.Apollo.whenReady(function () {
        setTimeout(attempt, 80);
        setTimeout(attempt, 500);
        setTimeout(attempt, 1400);
      });
    }
    window.addEventListener('apollo:lenis-ready', softenLenis, { once: true });
    var n = 0;
    var iv = setInterval(function () {
      softenLenis();
      attempt();
      n += 1;
      if (applied || n > 20) clearInterval(iv);
    }, 350);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
