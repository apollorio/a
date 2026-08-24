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


  /* ─── GSAP HERO EFFECTS ─────────────────────────────────────────── */
  function waitForGsap(cb, n) {
    n = n || 0;
    if (typeof gsap !== 'undefined') return cb();
    if (n < 100) return setTimeout(function () { waitForGsap(cb, n + 1); }, 50);
  }

  waitForGsap(function () {

    /* Hero text is always visible immediately — never gated on a reveal.
       (We do NOT cancel the CSS fallback here anymore: killing it is what
       left whole sections stuck invisible when a GSAP trigger didn't fire.) */
    if (homePanel) {
      homePanel.querySelectorAll('.nh-hero .ai').forEach(function (el) {
        el.classList.add('is-visible');
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
    }

    /* Hero video parallax — scroller is the panel, not window */
    var heroVid = document.querySelector('.nh-hero-vid');
    if (heroVid && homePanel && typeof ScrollTrigger !== 'undefined') {
      gsap.registerPlugin(ScrollTrigger);

      /* Video only scales on scroll — the -15% frame shift now lives on
         .nh-hero (CSS), so no yPercent needed here. */
      gsap.to(heroVid, {
        scale: 1.12,
        ease: 'none',
        scrollTrigger: {
          trigger: '.nh-hero',
          start: 'top top',
          end: 'bottom top',
          scrub: 1.5,
        }
      });
    }

    /* Menu FAB entrance — springy pop from bottom */
    if (menuFab) {
      gsap.fromTo(menuFab,
        { y: 30, scale: 0.5, opacity: 0 },
        { y: 0, scale: 1, opacity: 1, duration: 0.7, delay: 1.6, ease: 'back.out(2.2)', overwrite: 'auto', clearProps: 'transform' }
      );
    }

    /* Section .ai reveals are handled solely by IntersectionObserver
       (initScrollReveals) — it fires on real element visibility over the
       Lenis/native window scroll, with no trigger-position dependency. The
       old GSAP per-section reveal was removed: it double-drove the same
       nodes and left sections invisible whenever a trigger failed to fire. */

  });


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
