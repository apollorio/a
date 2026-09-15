/* ═══════════════════════════════════════════════════════════════════
   APOLLO::RIO — new_home.js  v4.4.0 /* SYNC_STAMP 2026-08-27T14:30 */
   Page-level interactions for /casa scrollable layout.
   Depends on: Apollo CDN core.js (GSAP, Lenis, RemixIcon)
   ═══════════════════════════════════════════════════════════════════
   PART MAPPING:
     App shell topbar  → apollo_render_app_shell() + topbar-styles.php
     Menu FAB + sheet  → nh-menu-fab + nh-menu-sheet (hidden on /casa)
     Hero typer        → hero.php · month toolbar → events.php
   ═══════════════════════════════════════════════════════════════════ */

;(function () {
  'use strict';

  /* ─── CACHE DOM ─────────────────────────────────────────────────── */
  var homePanel    = document.getElementById('apollo-home');

  // Menu FAB + upward sheet (hidden on /casa; kept for shared new-home surfaces)
  var menuFab      = document.getElementById('nhMenuFab');
  var menuSheet    = document.getElementById('nhMenuSheet');


  /* ─── MENU FAB — open/close upward sheet ───────────────────────────
     Single circle button bottom-right.
     Click → toggles .is-open on both FAB and sheet.
     Sheet slides from bottom to top via CSS transform.
     Clicking outside closes both.                                      */
  function openMenuSheet() {
    if (!menuFab || !menuSheet) return;
    menuFab.classList.add('is-open');
    menuSheet.classList.add('is-open');
    menuFab.setAttribute('aria-expanded', 'true');
  }
  function closeMenuSheet() {
    if (!menuFab || !menuSheet) return;
    menuFab.classList.remove('is-open');
    menuSheet.classList.remove('is-open');
    menuFab.setAttribute('aria-expanded', 'false');
  }
  function isMenuOpen() {
    return menuSheet && menuSheet.classList.contains('is-open');
  }

  if (menuFab && menuSheet) {
    // Claim ownership so CDN script.js does not create a duplicate listener
    menuFab.dataset.apolloFab = '1';
    menuFab.addEventListener('click', function (e) {
      e.stopPropagation();
      isMenuOpen() ? closeMenuSheet() : openMenuSheet();
    });
  }


  /* ─── CLICK OUTSIDE — close menu sheet ─────────────────────────── */
  document.addEventListener('click', function (e) {
    if (menuFab && menuSheet && !menuFab.contains(e.target) && !menuSheet.contains(e.target)) {
      closeMenuSheet();
    }
  });

  /* ESC key closes menu sheet */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenuSheet();
  });


  /* Legacy nh-navbar (scroll dim, profile/apps sheets, AJAX login, badge
     polling) was removed with F-01 — /casa uses apollo_render_app_shell()
     + core.js for that chrome. Dead getElementById calls against #nhNav /
     #nhProfile* / #nhApps* / #nhLogin* / badges are gone so the harness
     A1 contract stays green. */

  /* ─── HERO PHRASE ROTATOR (item 002) ────────────────────────────────
     Cycles .nh-hero-typer-view through PHRASES with a refined per-
     CHARACTER cascade (GSAP + SplitText). Gated on Apollo.whenReady. ── */
  (function heroPhraseRotator() {
    var view = document.querySelector('.nh-hero-typer-view');
    if (!view) return;

    var PHRASES = [
      `Espaço de<br>~ cultura e arte`,
      `Your eHub of<br><small>Party and Clubs in Rio!</small>`,
      `Explore <small>'n'</small> enjoy<br>like <font style="font-family:var(--ff-fun)!important">locals</font> do!`,
      `Viva o<br>hoje..`,
      `..ame no<br>amanhã`,
      `apollo::rio, <br><small>- seu espaço digital</small>`,
      `all <em style="font-family:var(--ff-fun)">Rio'<small>s</small></em> events<br><small>in one central place</small>`,
      `Qual a boa<br>do finds?!`,
      `Techno party<br>near me now`
    ];

    var HOLD_MS = 4200; // time between transition starts — generous, luxury pacing
    var idx = 0;
    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function plainSwap() {
      setInterval(function () {
        idx = (idx + 1) % PHRASES.length;
        view.innerHTML = PHRASES[idx];
      }, HOLD_MS);
    }

    if (prefersReduced) {
      plainSwap();
      return;
    }

    function start() {
      var SplitTextCtor = window.SplitText || (window.gsap && gsap.plugins && gsap.plugins.SplitText) || null;
      if (typeof gsap === 'undefined' || typeof SplitTextCtor !== 'function') {
        plainSwap();
        return;
      }

      var animating = false;

      function nextPhrase() {
        if (animating) return;
        animating = true;

        var outSplit = new SplitTextCtor(view, { type: 'chars', charsClass: 't3xt-char' });
        gsap.to(outSplit.chars, {
          opacity: 0,
          yPercent: -60,
          duration: 0.4,
          ease: 'power2.in',
          stagger: 0.012,
          onComplete: function () {
            outSplit.revert();
            idx = (idx + 1) % PHRASES.length;
            view.innerHTML = PHRASES[idx];

            var inSplit = new SplitTextCtor(view, { type: 'chars', charsClass: 't3xt-char' });
            gsap.set(inSplit.chars, { opacity: 0, yPercent: 70 });
            gsap.to(inSplit.chars, {
              opacity: 1,
              yPercent: 0,
              duration: 0.7,
              ease: 'power3.out',
              stagger: 0.02,
              onComplete: function () {
                inSplit.revert();
                view.innerHTML = PHRASES[idx];
                animating = false;
              }
            });
          }
        });
      }

      setInterval(nextPhrase, HOLD_MS);
    }

    if (window.Apollo && typeof window.Apollo.whenReady === 'function') {
      window.Apollo.whenReady(start);
    } else {
      /* Apollo CDN global not present yet — poll briefly rather than
         silently never starting; give up and degrade gracefully. */
      var tries = 0;
      (function waitForApollo() {
        if (window.Apollo && typeof window.Apollo.whenReady === 'function') {
          window.Apollo.whenReady(start);
          return;
        }
        tries++;
        if (tries > 40) { plainSwap(); return; } // ~10s at 250ms
        setTimeout(waitForApollo, 250);
      })();
    }
  })();


  /* ─── SCROLL REVEAL — IntersectionObserver (theme pattern) ─────── */
  function revealAll() {
    if (!homePanel) return;
    homePanel.querySelectorAll('.ai, .nh-reveal').forEach(function (el) {
      if (el.classList.contains('nh-st-drive')) return;
      el.classList.add('is-visible');
    });
  }

  function initScrollReveals() {
    if (!homePanel) return;
    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    /* No IO support OR reduced motion → reveal everything now (never hide). */
    if (!('IntersectionObserver' in window) || prefersReduced) {
      revealAll();
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        if (entry.target.classList.contains('nh-st-drive')) {
          io.unobserve(entry.target);
          return;
        }
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    homePanel.querySelectorAll('.section, .ai, .nh-reveal').forEach(function (el) {
      if (el.classList.contains('nh-st-drive')) return;
      io.observe(el);
    });

    /* Absolute safety net: if anything is still hidden after 4s (JS hiccup,
       Lenis stalling layout, offscreen math), force it visible. */
    setTimeout(revealAll, 4000);
  }

  initScrollReveals();


  /* ─── LUXURY GSAP + SCROLLTRIGGER (core.js / Vsimdim craft) ─────────
     Gate on Apollo.whenReady — SplitText lives in Tier 2c and is only
     guaranteed settled after apollo:ready. whenGsapReady alone is too early.
     Scrub = flow reverses on scroll-up. Discrete cards use
     toggleActions "play reverse play reverse". Never both on one node.
     Phone-first: matchMedia 720px (mockup Env.BREAKPOINT_TABLET). ───── */
  /* One boot only — whenReady + load fallback must not double-register ST */
  var _casaLuxuryBooted = false;
  var CASA_PHONE_MQ = '(max-width: 719px)';

  /** Strip IO/reveal-up CSS that fights GSAP scrub on the same node */
  function prepStDrive(els) {
    if (!els || !els.length) return;
    Array.prototype.forEach.call(els, function (el) {
      if (!el) return;
      el.classList.add('nh-st-drive', 'is-visible', 'ap-skip');
      el.classList.remove('reveal-up');
      el.style.transition = 'none';
      el.style.animation = 'none';
    });
  }

  /** Lenis ↔ ScrollTrigger — same bind as cells/motion.php (idempotent). */
  function bindCasaLenis(ST) {
    if (!ST || window.__casaLenisBound) return;
    var bind = function (l) {
      if (!l || typeof l.on !== 'function' || window.__casaLenisBound) return;
      window.__casaLenisBound = true;
      try { l.on('scroll', ST.update); } catch (e) { /* ignore */ }
    };
    if (window.lenis) {
      bind(window.lenis);
    } else {
      window.addEventListener('apollo:lenis-ready', function () {
        bind(window.lenis);
      }, { once: true });
      var t0 = 0;
      var lp = setInterval(function () {
        if (window.lenis) bind(window.lenis);
        if (window.__casaLenisBound || ++t0 > 40) clearInterval(lp);
      }, 250);
    }
  }

  function initLuxuryScroll() {
    if (_casaLuxuryBooted) {
      return;
    }
    _casaLuxuryBooted = true;

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
      if (homePanel) {
        homePanel.querySelectorAll('.nh-hero .ai').forEach(function (el) {
          el.classList.add('is-visible');
          el.style.opacity = '1';
          el.style.transform = 'none';
        });
      }
      return;
    }

    gsap.registerPlugin(ScrollTrigger);
    bindCasaLenis(ScrollTrigger);
    try {
      ScrollTrigger.config({ limitCallbacks: true, ignoreMobileResize: true });
    } catch (eCfg) { /* ignore */ }
    /* Snappier Lenis — default lerp 0.1 feels heavy under ST */
    try {
      if (window.lenis && window.lenis.options) {
        window.lenis.options.lerp = 0.22;
        window.lenis.options.wheelMultiplier = 1.12;
        window.lenis.options.touchMultiplier = 1.15;
      }
    } catch (eLen) { /* ignore */ }

    /* Hero copy always readable at rest (IO must not fight ST). */
    if (homePanel) {
      homePanel.querySelectorAll('.nh-hero .ai').forEach(function (el) {
        el.classList.add('is-visible');
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
    }

    /* SplitText — optional if plugin failed to load */
    var SplitTextCtor = window.SplitText || (gsap.plugins && gsap.plugins.SplitText) || null;
    if (typeof SplitTextCtor === 'function') {
      try {
        new SplitTextCtor('[split-lines]', { type: 'lines', linesClass: 'split-line' });
        new SplitTextCtor('[split-chars]', { type: 'lines,chars', linesClass: 'split-line', charsClass: 'split-char' });
      } catch (err) {
        if (window.console) console.warn('[apollo /casa] SplitText skipped', err);
      }
    }

    var isPhone = !!(window.matchMedia && window.matchMedia(CASA_PHONE_MQ).matches);
    var cardTravel = isPhone ? 28 : 44;

    // #region agent log
    function dbgCasaReveal(phase, stId, el, hypothesisId) {
      try {
        var r = el.getBoundingClientRect();
        var vh = window.innerHeight || 0;
        fetch('http://127.0.0.1:7754/ingest/da9d552b-a038-4061-bf95-e47d2c529b38', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '837565' },
          body: JSON.stringify({
            sessionId: '837565',
            runId: 'casa-reveal',
            hypothesisId: hypothesisId || 'VIEWPORT-BOUNDS',
            location: 'new-home.js:dbgCasaReveal',
            message: 'casa reveal ST ' + phase,
            data: {
              phase: phase,
              id: stId,
              top: Math.round(r.top),
              bottom: Math.round(r.bottom),
              vh: vh,
              inView: r.top < vh && r.bottom > 0,
              fullyAbove: r.bottom <= 0,
              fullyBelow: r.top >= vh
            },
            timestamp: Date.now()
          })
        }).catch(function () {});
      } catch (e) { /* ignore */ }
    }
    // #endregion

    var hero = document.querySelector('.nh-hero');
    var heroVid = document.querySelector('.nh-hero-vid');
    /* .nh-hero-title's text now rotates on a timer (item 002 — see the
       "hero phrase rotator" below), so per-char split-chars would be
       destroyed on every swap and this tween would silently animate
       detached nodes. Target the stable wrapper as one unit instead —
       same "collapse away on scroll" effect, safe against the dynamic
       content underneath it. */
    var heroTitleWrap = document.querySelector('.nh-hero-typer');
    var heroSub = document.querySelector('.nh-hero-sub');
    var heroHint = document.querySelector('.nh-scroll-hint');

    /* ST1 — Hero scrub only on desktop (phone scrub fights touch scroll). */
    if (hero && heroVid && !isPhone) {
      prepStDrive([heroSub, heroHint].filter(Boolean));
      var heroTl = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: {
          id: 'casa-hero',
          trigger: hero,
          start: 'center top',
          end: 'bottom top',
          scrub: 1.4
        }
      });
      heroTl.to(heroVid, { scale: 1.1, yPercent: -6, duration: 1, force3D: true }, 0);
      if (heroTitleWrap) {
        heroTl.fromTo(heroTitleWrap,
          { scaleY: 1, opacity: 1 },
          { scaleY: 0.92, opacity: 0.55, duration: 0.55, force3D: true },
          0
        );
      }
      if (heroSub) heroTl.to(heroSub, { opacity: 0.4, y: -12, duration: 0.4 }, 0.25);
      if (heroHint) heroTl.to(heroHint, { opacity: 0, duration: 0.25 }, 0.1);
    }

    /* Discrete section fades — no scrub on heads/chars (was the heavy feel). */
    function discreteIn(els, trigger, id) {
      var list = (els || []).filter(Boolean);
      if (!list.length || !trigger) return;
      prepStDrive(list);
      gsap.fromTo(list,
        { y: isPhone ? 16 : 22, opacity: 0.25 },
        {
          y: 0,
          opacity: 1,
          duration: isPhone ? 0.4 : 0.5,
          ease: 'power2.out',
          stagger: 0.05,
          overwrite: 'auto',
          force3D: true,
          immediateRender: false,
          scrollTrigger: {
            id: id,
            trigger: trigger,
            start: 'top 90%',
            end: 'bottom top',
            toggleActions: 'play reverse play reverse'
          }
        }
      );
    }

    var eventsSec = document.getElementById('events');
    if (eventsSec) {
      discreteIn([eventsSec.querySelector('.nh-section-head')], eventsSec, 'casa-events');
      var eventsTitleChars = eventsSec.querySelectorAll('#events-title .split-char');
      if (eventsTitleChars.length) {
        prepStDrive(eventsTitleChars);
        gsap.set(eventsTitleChars, { scaleY: 1, opacity: 1, clearProps: 'transform' });
      }
    }

    var crashSec = document.getElementById('crash');
    if (crashSec) {
      var crashMq = crashSec.querySelector('.nh-rt-rail, .nh-mq');
      var crashMedia = crashMq || crashSec.querySelector('.nh-empty-state');
      discreteIn([crashSec.querySelector('.nh-section-head'), crashMedia], crashSec, 'casa-crash');
      var crashTitleChars = crashSec.querySelectorAll('#crash-title .split-char');
      if (crashTitleChars.length) {
        prepStDrive(crashTitleChars);
        gsap.set(crashTitleChars, { scaleY: 1, opacity: 1, clearProps: 'transform' });
      }
    }

    var tracksSec = document.getElementById('tracks');
    if (tracksSec) {
      discreteIn([tracksSec.querySelector('.nh-section-head')], tracksSec, 'casa-tracks-head');
      var tracksTitleChars = tracksSec.querySelectorAll('#tracks-title .split-char');
      if (tracksTitleChars.length) {
        prepStDrive(tracksTitleChars);
        gsap.set(tracksTitleChars, { scaleY: 1, opacity: 1, clearProps: 'transform' });
      }
      var trackCards = tracksSec.querySelectorAll('.nh-track-card');
      if (trackCards.length && typeof ScrollTrigger.batch === 'function') {
        prepStDrive(trackCards);
        gsap.set(trackCards, { y: cardTravel, opacity: 0, force3D: true });
        ScrollTrigger.batch(trackCards, {
          start: 'top 92%',
          end: 'bottom top',
          interval: 0.1,
          batchMax: 6,
          onEnter: function (batch) {
            gsap.to(batch, { y: 0, opacity: 1, duration: 0.45, stagger: 0.04, ease: 'power2.out', overwrite: 'auto', force3D: true });
          },
          onLeave: function (batch) {
            gsap.to(batch, { y: -10, opacity: 0, duration: 0.25, stagger: 0.02, ease: 'power1.in', overwrite: 'auto', force3D: true });
          },
          onEnterBack: function (batch) {
            gsap.to(batch, { y: 0, opacity: 1, duration: 0.4, stagger: 0.03, ease: 'power2.out', overwrite: 'auto', force3D: true });
          },
          onLeaveBack: function (batch) {
            gsap.to(batch, { y: 16, opacity: 0, duration: 0.25, stagger: 0.02, ease: 'power1.in', overwrite: 'auto', force3D: true });
          }
        });
      }
    }

    var resellSec = document.getElementById('resell');
    if (resellSec) {
      var resellCards = resellSec.querySelectorAll('.rt-card:not([aria-hidden="true"]), .nh-mq-card:not([aria-hidden="true"])');
      var resellMedia = resellSec.querySelector('.nh-rt-rail, .nh-mq') || resellSec.querySelector('.nh-empty-state');
      discreteIn([resellSec.querySelector('.nh-section-head'), resellSec.querySelector('.nh-resale-intro')], resellSec, 'casa-resell-head');
      var resellTitleChars = resellSec.querySelectorAll('#resell-title .split-char');
      if (resellTitleChars.length) {
        prepStDrive(resellTitleChars);
        gsap.set(resellTitleChars, { scaleY: 1, opacity: 1, clearProps: 'transform' });
      }
      if (resellMedia && !resellCards.length) discreteIn([resellMedia], resellSec, 'casa-resell-media');
      if (resellCards.length && typeof ScrollTrigger.batch === 'function') {
        prepStDrive(resellCards);
        gsap.set(resellCards, { y: isPhone ? 16 : 22, opacity: 0, force3D: true });
        ScrollTrigger.batch(resellCards, {
          start: 'top 92%',
          end: 'bottom top',
          interval: 0.1,
          batchMax: 6,
          onEnter: function (batch) {
            gsap.to(batch, { y: 0, opacity: 1, duration: 0.45, stagger: 0.04, ease: 'power2.out', overwrite: 'auto', force3D: true });
          },
          onLeave: function (batch) {
            gsap.to(batch, { y: -10, opacity: 0, duration: 0.25, stagger: 0.02, ease: 'power1.in', overwrite: 'auto', force3D: true });
          },
          onEnterBack: function (batch) {
            gsap.to(batch, { y: 0, opacity: 1, duration: 0.4, stagger: 0.03, ease: 'power2.out', overwrite: 'auto', force3D: true });
          },
          onLeaveBack: function (batch) {
            gsap.to(batch, { y: 16, opacity: 0, duration: 0.25, stagger: 0.02, ease: 'power1.in', overwrite: 'auto', force3D: true });
          }
        });
      }
    }

    var mapSec = document.getElementById('map');
    if (mapSec) {
      discreteIn([
        mapSec.querySelector('.nh-section-head'),
        document.getElementById('nhMap') || mapSec.querySelector('.nh-map-wrap')
      ], mapSec, 'casa-map');
    }

    /* Leftover reveals — batch (casa-reveal-fix.js also lightens if stale). */
    if (homePanel && typeof ScrollTrigger.batch === 'function') {
      var leftover = [];
      homePanel.querySelectorAll('.reveal-up.ai, .ai.reveal-up').forEach(function (el) {
        if (el.classList.contains('nh-st-drive')) return;
        if (el.closest('.nh-hero')) return;
        if (el.closest('.nh-mq-track')) return;
        leftover.push(el);
      });
      if (leftover.length) {
        prepStDrive(leftover);
        gsap.set(leftover, { y: cardTravel, opacity: 0, force3D: true });
        ScrollTrigger.batch(leftover, {
          start: 'top 92%',
          end: 'bottom top',
          interval: 0.12,
          batchMax: 6,
          onEnter: function (batch) {
            gsap.to(batch, { y: 0, opacity: 1, duration: 0.45, stagger: 0.04, ease: 'power2.out', overwrite: 'auto', force3D: true });
          },
          onLeave: function (batch) {
            gsap.to(batch, { y: -10, opacity: 0, duration: 0.25, stagger: 0.02, ease: 'power1.in', overwrite: 'auto', force3D: true });
          },
          onEnterBack: function (batch) {
            gsap.to(batch, { y: 0, opacity: 1, duration: 0.4, stagger: 0.03, ease: 'power2.out', overwrite: 'auto', force3D: true });
          },
          onLeaveBack: function (batch) {
            gsap.to(batch, { y: 16, opacity: 0, duration: 0.25, stagger: 0.02, ease: 'power1.in', overwrite: 'auto', force3D: true });
          }
        });
      }
    }
    /* Menu FAB entrance — springy pop (not ScrollTrigger) */
    if (menuFab) {
      gsap.fromTo(menuFab,
        { y: 30, scale: 0.5, opacity: 0 },
        { y: 0, scale: 1, opacity: 1, duration: 0.7, delay: 1.6, ease: 'back.out(2.2)', overwrite: 'auto', clearProps: 'transform' }
      );
    }

    function refreshST() {
      try { ScrollTrigger.refresh(); } catch (e) { /* ignore */ }
    }
    refreshST();
    window.addEventListener('load', refreshST);
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(refreshST).catch(function () {});
    }
  }

  if (window.Apollo && typeof window.Apollo.whenReady === 'function') {
    window.Apollo.whenReady(initLuxuryScroll);
  } else {
    /* Fallback if core.js API missing — still prefer full load for SplitText */
    if (document.readyState === 'complete') initLuxuryScroll();
    else window.addEventListener('load', initLuxuryScroll);
  }


  /* ─── MONTH DROPDOWN (September start, +2 months, Portal) ────────
     Trigger → click → reveals row of month options + portal link.
     Current = September, then +1 (October), +2 (November), + Portal.  */
  var monthTrigger = document.getElementById('nhMonthTrigger');
  var monthMenu    = document.getElementById('nhMonthMenu');
  var monthText    = monthTrigger ? monthTrigger.querySelector('.nh-month-text') : null;

  if (monthTrigger && monthMenu && monthText) {
    /* Prefer server-rendered PT menu (events.php). Only build when empty —
       avoids English overwrite if a stale cached new-home.js still ships. */
    if (!monthMenu.children.length) {
      var allMonths = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                       'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
      var startIdx = new Date().getMonth();
      var curMonth  = allMonths[startIdx];
      var nextMonth = allMonths[(startIdx + 1) % 12];
      var plusTwo   = allMonths[(startIdx + 2) % 12];
      var monthOptions = [
        { text: curMonth,  type: 'month' },
        { text: nextMonth, type: 'month' },
        { text: plusTwo,   type: 'month' },
        { text: '<i class="ri-calendar-2-line"></i> Ver todos', type: 'link', url: '/portal/eventos' },
        { text: '<i class="ri-calendar-schedule-line"></i> Incluir evento', type: 'link', url: '/novo-evento' }
      ];
      monthText.textContent = curMonth;
      monthMenu.innerHTML = monthOptions.map(function (opt) {
        var isActive = (opt.text === curMonth) ? ' active' : '';
        var isPortal = (opt.type === 'link') ? ' nh-portal-link' : '';
        var href = opt.url || '#';
        return '<li><a href="' + href + '" class="' + isActive + isPortal + '" data-type="' + opt.type + '">' + opt.text + '</a></li>';
      }).join('');
    }
    monthMenu.removeAttribute('hidden');

    monthTrigger.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = monthMenu.classList.toggle('is-visible');
      monthTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');

      if (typeof gsap !== 'undefined' && open) {
        gsap.from('#nhMonthMenu li', {
          x: 8,
          opacity: 0,
          stagger: 0.04,
          duration: 0.2,
          ease: 'power2.out'
        });
      }
    });

    monthMenu.addEventListener('click', function (e) {
      var target = e.target.closest('a');
      if (!target) return;

      if (target.dataset.type === 'month') {
        e.preventDefault();
        monthText.textContent = target.textContent.trim();
        monthMenu.querySelectorAll('a').forEach(function (a) { a.classList.remove('active'); });
        target.classList.add('active');
        monthMenu.classList.remove('is-visible');
        monthTrigger.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('click', function () {
      monthMenu.classList.remove('is-visible');
      monthTrigger.setAttribute('aria-expanded', 'false');
    });
  }

  /* ═══ GUEST INTRO GATE — REMOVED ═══
     The splash / "Enter" overlay was permanently removed. The landing page
     renders immediately for every visitor. As a safety net, strip any stale
     loading class so content can never be left hidden by old cached markup. */
  function clearLegacyIntro() {
    var body = document.body;
    if (body) body.classList.remove('apollo-home-loading');
    ['introOverlay', 'apolloPreloader'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el && el.parentNode) el.parentNode.removeChild(el);
    });
  }

  clearLegacyIntro();

})();
