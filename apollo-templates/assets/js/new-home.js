/* ═══════════════════════════════════════════════════════════════════
   APOLLO::RIO — new_home.js  v4.2.0
   Page-level interactions for /casa scrollable layout.
   Depends on: Apollo CDN core.js (GSAP, Lenis, RemixIcon)
   ═══════════════════════════════════════════════════════════════════
   PART MAPPING (PHP modular reference):
     Navbar scroll     → nh-navbar (persistent-ui.php)
     Menu FAB + sheet  → nh-menu-fab + nh-menu-sheet (persistent-ui.php)
     Profile dropdown  → nhProfileSheet (persistent-ui.php)
     GSAP hero         → panel-home.php hero section
   ═══════════════════════════════════════════════════════════════════ */

;(function () {
  'use strict';

  /* ─── CACHE DOM ─────────────────────────────────────────────────── */
  var navbar       = document.getElementById('nhNav');
  var homePanel    = document.getElementById('apollo-home');

  // Menu FAB + upward sheet
  var menuFab      = document.getElementById('nhMenuFab');
  var menuSheet    = document.getElementById('nhMenuSheet');

  // Profile dropdown
  var profileBtn   = document.getElementById('nhProfileBtn');
  var profileSheet = document.getElementById('nhProfileDropdown');

  // Apps dropdown
  var appsBtn      = document.getElementById('nhAppsBtn');
  var appsSheet    = document.getElementById('nhAppsDropdown');


  /* ─── NAVBAR SCROLL ────────────────────────────────────────────── */
  if (navbar) {
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(function () {
          navbar.classList.toggle('scrolled', window.scrollY > 72);
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }


  /* ─── HERO PHRASE ROTATOR (item 002) ────────────────────────────────
     Cycles .nh-hero-typer-view through PHRASES with a refined per-
     CHARACTER cascade (GSAP + SplitText) — chars drift up + fade out,
     then the new phrase's chars rise + fade in, staggered. Phrases
     carry inline HTML (<br>, <small>, <em>, <font>), which SplitText
     splits around correctly. Gated on window.Apollo.whenReady —
     SplitText only settles after apollo:ready (Tier 2c, same gate
     "LUXURY GSAP" below uses). Falls back to a plain, non-animated
     swap if GSAP/SplitText never load or the visitor prefers reduced
     motion — never leaves the hero stuck on one phrase.           ── */
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


  /* ─── MENU FAB — open/close upward sheet ───────────────────────────
     Single circle button bottom-right.
     Click → toggles .is-open on both FAB and sheet.
     Sheet slides from bottom to top via CSS transform.
     Clicking outside closes both.                                      */
  function openMenuSheet() {
    menuFab.classList.add('is-open');
    menuSheet.classList.add('is-open');
    menuFab.setAttribute('aria-expanded', 'true');
    // Close profile if open
    closeProfileSheet();
  }
  function closeMenuSheet() {
    menuFab.classList.remove('is-open');
    menuSheet.classList.remove('is-open');
    menuFab.setAttribute('aria-expanded', 'false');
  }
  function isMenuOpen() {
    return menuSheet.classList.contains('is-open');
  }

  if (menuFab && menuSheet) {
    // Claim ownership so CDN script.js does not create a duplicate listener
    menuFab.dataset.apolloFab = '1';
    menuFab.addEventListener('click', function (e) {
      e.stopPropagation();
      isMenuOpen() ? closeMenuSheet() : openMenuSheet();
    });
  }


  /* ─── PROFILE DROPDOWN (navbar top-right) ───────────────────────── */
  function openProfileSheet() {
    if (!profileSheet) return;
    profileSheet.classList.add('is-open');
    profileBtn.setAttribute('aria-expanded', 'true');
    closeMenuSheet();
    closeAppsSheet();
  }
  function closeProfileSheet() {
    if (!profileSheet) return;
    profileSheet.classList.remove('is-open');
    if (profileBtn) profileBtn.setAttribute('aria-expanded', 'false');
  }
  function isProfileOpen() {
    return profileSheet && profileSheet.classList.contains('is-open');
  }

  if (profileBtn && profileSheet) {
    profileBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      isProfileOpen() ? closeProfileSheet() : openProfileSheet();
    });
    profileSheet.addEventListener('click', function (e) { e.stopPropagation(); });
  }


  /* ─── APPS DROPDOWN (navbar) ─────────────────────────────────────── */
  function openAppsSheet() {
    if (!appsSheet) return;
    appsSheet.classList.add('is-open');
    appsBtn.setAttribute('aria-expanded', 'true');
    closeMenuSheet();
    closeProfileSheet();
  }
  function closeAppsSheet() {
    if (!appsSheet) return;
    appsSheet.classList.remove('is-open');
    if (appsBtn) appsBtn.setAttribute('aria-expanded', 'false');
  }
  function isAppsOpen() {
    return appsSheet && appsSheet.classList.contains('is-open');
  }

  if (appsBtn && appsSheet) {
    appsBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      isAppsOpen() ? closeAppsSheet() : openAppsSheet();
    });
    appsSheet.addEventListener('click', function (e) { e.stopPropagation(); });
  }


  /* ─── CLICK OUTSIDE — close all sheets ─────────────────────────── */
  document.addEventListener('click', function (e) {
    if (menuFab && menuSheet && !menuFab.contains(e.target) && !menuSheet.contains(e.target)) {
      closeMenuSheet();
    }
    if (profileBtn && profileSheet && !profileBtn.contains(e.target) && !profileSheet.contains(e.target)) {
      closeProfileSheet();
    }
    if (appsBtn && appsSheet && !appsBtn.contains(e.target) && !appsSheet.contains(e.target)) {
      closeAppsSheet();
    }
  });

  /* ESC key closes any open sheet */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMenuSheet();
      closeProfileSheet();
      closeAppsSheet();
    }
  });


  /* ─── AJAX LOGIN (guest profile dropdown) ───────────────────────── */
  var loginForm = document.getElementById('nhLoginForm');
  var loginBtn  = document.getElementById('nhLoginSubmit');

  if (loginForm && loginBtn) {
    loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var user = document.getElementById('nh-login-user');
      var pass = document.getElementById('nh-login-pass');
      if (!user || !pass || !user.value.trim() || !pass.value) return;

      loginBtn.classList.add('loading');
      loginBtn.disabled = true;

      var formData = new FormData(loginForm);
      formData.append('action', 'apollo_navbar_login');

      fetch(
        (typeof window.apolloNavbar !== 'undefined' ? window.apolloNavbar.ajaxUrl : '/wp-admin/admin-ajax.php'),
        { method: 'POST', body: formData, credentials: 'same-origin' }
      )
      .then(function (r) { return r.json(); })
      .then(function (data) {
        loginBtn.classList.remove('loading');
        loginBtn.disabled = false;
        if (data.success) {
          if (data.data && data.data.redirect) {
            window.location.href = data.data.redirect;
          } else {
            window.location.reload();
          }
        } else {
          showLoginError(data.data ? data.data.message : 'Erro ao fazer login');
        }
      })
      .catch(function (err) {
        loginBtn.classList.remove('loading');
        loginBtn.disabled = false;
        showLoginError(err.message || 'Erro de conexão');
      });
    });
  }

  function showLoginError(msg) {
    if (!loginForm) return;
    var el = loginForm.querySelector('.nh-login-error');
    if (!el) {
      el = document.createElement('div');
      el.className = 'nh-login-error';
      el.style.cssText = 'color:#ef4444;font-size:.78rem;text-align:center;margin-top:8px;';
      loginForm.appendChild(el);
    }
    el.textContent = msg;
    setTimeout(function () { if (el) el.remove(); }, 5000);
  }


  /* ─── LIVE POLLING — badge updates (chat + notif) ───────────────── */
  (function () {
    var nav = document.getElementById('nhNav');
    if (!nav || nav.getAttribute('data-auth') !== 'logged') return;
    if (!window.apolloNavbar || !window.apolloNavbar.restUrl) return;

    var pollInterval = 8000;
    var lastPollTime = '';

    function poll() {
      var url = window.apolloNavbar.restUrl + 'chat/poll';
      if (lastPollTime) url += '?since=' + encodeURIComponent(lastPollTime);

      fetch(url, {
        headers: { 'X-WP-Nonce': window.apolloNavbar.nonce },
        credentials: 'same-origin'
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        lastPollTime = data.timestamp || '';

        var nBadge = document.getElementById('nhNotifBadge');
        if (nBadge) nBadge.dataset.notif = (data.unread_notifs > 0) ? 'true' : 'false';

        var cBadge = document.getElementById('nhChatBadge');
        if (cBadge) cBadge.dataset.notif = (data.unread_messages > 0) ? 'true' : 'false';
      })
      .catch(function () { /* silent */ });
    }

    setTimeout(function () {
      poll();
      setInterval(poll, pollInterval);
    }, 3000);
  })();


  /* ─── SCROLL REVEAL — IntersectionObserver (theme pattern) ─────── */
  function revealAll() {
    if (!homePanel) return;
    homePanel.querySelectorAll('.ai, .nh-reveal').forEach(function (el) { el.classList.add('is-visible'); });
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
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    homePanel.querySelectorAll('.section, .ai, .nh-reveal').forEach(function (el) {
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
     At least 3 ScrollTriggers on /casa: hero pin+scrub, events scrub,
     crash pin+scrub. ──────────────────────────────────────────────────── */
  /* One boot only — whenReady + load fallback must not double-register ST */
  var _casaLuxuryBooted = false;

  /** Strip IO/reveal-up CSS that fights GSAP scrub on the same node */
  function prepStDrive(els) {
    if (!els || !els.length) return;
    Array.prototype.forEach.call(els, function (el) {
      el.classList.add('nh-st-drive', 'is-visible', 'ap-skip');
      el.classList.remove('reveal-up');
      el.style.transition = 'none';
    });
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

    /* ST1 — Hero scrub (pin removed: lighter layout, no giant pin-spacer) */
    if (hero && heroVid) {
      prepStDrive([heroSub, heroHint].filter(Boolean));
      var heroTl = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: {
          id: 'casa-hero',
          trigger: hero,
          /* hero sits at document position 0, so start:'top top' fired at
             scrollY=0 — the very first frame, no visible "before" state to
             contrast against. Starting at the hero's own center gives a
             static first half-screen of scroll, then compresses the full
             scrub (video scale + title collapse) into the second half —
             visible, not a moment-0 boundary case. */
          start: 'center top',
          end: 'bottom top',
          scrub: 1.2
        }
      });
      heroTl.to(heroVid, { scale: 1.14, yPercent: -8, duration: 1 }, 0);
      if (heroTitleWrap) {
        heroTl.fromTo(heroTitleWrap,
          { scaleY: 1, opacity: 1 },
          {
            scaleY: 0.85,
            opacity: 0.35,
            ease: 'sine.in',
            transformOrigin: 'top',
            duration: 0.55
          },
          0.2
        );
      }
      if (heroSub) heroTl.to(heroSub, { opacity: 0.4, y: -16, duration: 0.4 }, 0.25);
      if (heroHint) heroTl.to(heroHint, { opacity: 0, duration: 0.25 }, 0.1);
    }

    /* ST2 — Events scrub: section head rise only (title + month dropdown +
       Ver Todos). Event cards are intentionally NOT GSAP-scrubbed — they carry
       .reveal-up (see events.php) and are driven purely by reveal-up.js's own
       IntersectionObserver, auto-staggered per sibling via --ap-d (115ms each,
       see the uploaded reveal-up.js). Mixing both systems on the same node
       fights (IO toggling classes while GSAP scrubs inline transforms), so
       cards get reveal-up only; the section head keeps the ScrollTrigger scrub
       so /casa still holds >=3 triggers (hero, events-head, crash). */
    var eventsSec = document.getElementById('events');
    if (eventsSec) {
      var eventsHead = eventsSec.querySelector('.nh-section-head');
      if (eventsHead) {
        prepStDrive([eventsHead]);
        gsap.fromTo(eventsHead,
          { opacity: 0.35, y: 32 },
          {
            opacity: 1,
            y: 0,
            ease: 'none',
            scrollTrigger: {
              id: 'casa-events',
              trigger: eventsSec,
              start: 'top 82%',
              end: 'top 38%',
              scrub: 0.8
            }
          }
        );
      }
      var eventsTitleChars = eventsSec.querySelectorAll('#events-title .split-char');
      if (eventsTitleChars.length) {
        prepStDrive(eventsTitleChars);
        gsap.fromTo(eventsTitleChars,
          { scaleY: 0, opacity: 0 },
          {
            scaleY: 1,
            opacity: 1,
            ease: 'sine.out',
            transformOrigin: 'top',
            stagger: 0.04,
            scrollTrigger: {
              id: 'casa-events-title',
              trigger: eventsSec,
              start: 'top 85%',
              end: 'top 55%',
              scrub: 1
            }
          }
        );
      }
    }

    /* ST3 — Crash / Acomoda::Rio: scrub expand (no pin — lighter) */
    var crashSec = document.getElementById('crash');
    var crashMq = crashSec ? crashSec.querySelector('.nh-mq') : null;
    var crashMedia = crashMq || (crashSec ? crashSec.querySelector('.nh-empty-state') : null);
    var crashHead = crashSec ? crashSec.querySelector('.nh-section-head') : null;
    if (crashSec && crashMedia) {
      prepStDrive([crashHead, crashMedia].filter(Boolean));
      var crashTl = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: {
          id: 'casa-crash',
          trigger: crashSec,
          start: 'top 85%',
          end: 'top 35%',
          scrub: 0.9
        }
      });
      var crashTitleChars = crashSec.querySelectorAll('#crash-title .split-char');
      if (crashTitleChars.length) {
        crashTl.fromTo(crashTitleChars,
          { scaleY: 0.6, opacity: 0 },
          {
            scaleY: 1,
            opacity: 1,
            ease: 'sine.out',
            transformOrigin: 'top',
            stagger: 0.03,
            duration: 0.4
          },
          0
        );
      }
      crashTl.fromTo(crashMedia,
        { scale: 0.96, opacity: 0.7 },
        { scale: 1, opacity: 1, duration: 0.7, ease: 'expo.out' },
        0.15
      );
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
    /* Lenis drives real document scroll — keep ST in sync */
    if (window.Apollo && typeof window.Apollo.whenLenisReady === 'function') {
      window.Apollo.whenLenisReady(function (lenis) {
        if (lenis && typeof lenis.on === 'function') {
          try { lenis.on('scroll', ScrollTrigger.update); } catch (e) { /* ignore */ }
        }
        refreshST();
      });
    } else if (window.lenis && typeof window.lenis.on === 'function') {
      try { window.lenis.on('scroll', ScrollTrigger.update); } catch (e) { /* ignore */ }
      refreshST();
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
    var allMonths = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];
    var startIdx = new Date().getMonth(); // Dynamic: always starts at current month

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

    // Set trigger text
    monthText.textContent = curMonth;

    // Populate row
    monthMenu.innerHTML = monthOptions.map(function (opt) {
      var isActive = (opt.text === curMonth) ? ' active' : '';
      var isPortal = (opt.type === 'link') ? ' nh-portal-link' : '';
      var href = opt.url || '#';
      return '<li><a href="' + href + '" class="' + isActive + isPortal + '" data-type="' + opt.type + '">' + opt.text + '</a></li>';
    }).join('');

    // Toggle visibility
    monthTrigger.addEventListener('click', function (e) {
      e.stopPropagation();
      monthMenu.classList.toggle('is-visible');

      // GSAP stagger if available
      if (typeof gsap !== 'undefined' && monthMenu.classList.contains('is-visible')) {
        gsap.from('#nhMonthMenu li', {
          x: 10,
          opacity: 0,
          stagger: 0.05,
          ease: 'power2.out'
        });
      }
    });

    // Option click
    monthMenu.addEventListener('click', function (e) {
      var target = e.target.closest('a');
      if (!target) return;

      if (target.dataset.type === 'month') {
        e.preventDefault();
        monthText.textContent = target.textContent.trim();
        monthMenu.querySelectorAll('a').forEach(function (a) { a.classList.remove('active'); });
        target.classList.add('active');
        monthMenu.classList.remove('is-visible');
      }
      // Portal link follows href naturally
    });

    // Close on outside click
    document.addEventListener('click', function () {
      monthMenu.classList.remove('is-visible');
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
