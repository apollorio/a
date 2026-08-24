/**
 * Apollo Navbar JavaScript
 *
 * Frontend JavaScript for the Apollo navigation bar.
 * Handles dropdown toggles, clock, login form, scroll capture, and dark mode.
 *
 * @package Apollo_Core
 * @since 1.9.0
 */

(function() {
  'use strict';

  // Prevent double initialization
  if (window.__APOLLO_NAVBAR__) return;
  window.__APOLLO_NAVBAR__ = 1;

  var d = document;
  var w = window;

  /**
   * Initialize navbar
   */
  function init() {
    var navbar = d.getElementById('apollo-navbar');
    var clockEl = d.getElementById('digital-clock');

    if (!navbar) return;

    var authState = navbar.getAttribute('data-auth') || 'guest';

    var toggles = [
      { btn: 'btn-admin-logo', menu: 'menu-admin', auth: 'all' },
      { btn: 'btn-notif', menu: 'menu-notif', auth: 'logged' },
      { btn: 'btn-apps', menu: 'menu-app', auth: 'all' },
      { btn: 'btn-profile', menu: 'menu-profile', auth: 'logged' }
    ];

    /* ========================================
       CLOCK
       ======================================== */
    if (clockEl) {
      (function updateClock() {
        var now = new Date();
        clockEl.textContent = now.toLocaleTimeString('pt-BR', { hour12: false });
        setTimeout(updateClock, 1000 - (Date.now() % 1000));
      })();
    }

    /* ========================================
       AUTH STATE MANAGEMENT
       ======================================== */
    function updateAuthUI(newState) {
      if (!navbar) return;
      navbar.setAttribute('data-auth', newState);
      authState = newState;

      var loginSection = d.getElementById('login-section');
      var loggedContent = d.getElementById('logged-content');

      if (newState === 'logged') {
        if (loginSection) loginSection.classList.add('hidden');
        if (loggedContent) loggedContent.classList.remove('hidden');
      } else {
        if (loginSection) loginSection.classList.remove('hidden');
        if (loggedContent) loggedContent.classList.add('hidden');
      }
    }

    /* ========================================
       DROPDOWN TOGGLE SYSTEM
       ======================================== */
    function closeAll() {
      toggles.forEach(function(t) {
        var menu = d.getElementById(t.menu);
        var btn = d.getElementById(t.btn);
        if (menu && btn) {
          menu.classList.remove('active');
          menu.setAttribute('aria-hidden', 'true');
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    }

    toggles.forEach(function(t) {
      var btn = d.getElementById(t.btn);
      var menu = d.getElementById(t.menu);
      if (!btn || !menu) return;

      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (t.auth === 'logged' && authState !== 'logged') return;

        var isActive = menu.classList.contains('active');
        closeAll();

        if (!isActive) {
          menu.style.display = 'flex';
          menu.offsetHeight; // Force reflow
          menu.classList.add('active');
          menu.setAttribute('aria-hidden', 'false');
          btn.setAttribute('aria-expanded', 'true');
        }
      });

      // Prevent menu clicks from closing
      menu.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    });

    // Close on outside click
    d.addEventListener('click', closeAll);

    // Close on escape
    d.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeAll();
    });

    /* ========================================
       NOTIFICATION STATE
       ======================================== */
    function updateNotifState() {
      var list = d.getElementById('notif-list');
      var empty = d.getElementById('notif-empty');
      var badge = d.getElementById('notif-badge');
      if (!list || !badge) return;

      var count = list.children.length;
      if (empty) empty.style.display = count === 0 ? 'flex' : 'none';
      list.style.display = count === 0 ? 'none' : 'block';
      badge.setAttribute('data-notif', count > 0 ? 'true' : 'false');
    }

    if (authState === 'logged') {
      updateNotifState();
    }

    /* ========================================
       LOGIN FORM HANDLER
       ======================================== */
    var loginForm = d.getElementById('apollo-login-form');
    var loginSection = d.getElementById('login-section');
    var loggedContent = d.getElementById('logged-content');
    var loginBtn = d.getElementById('login-submit');

    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        var user = d.getElementById('login-user');
        var pass = d.getElementById('login-pass');

        if (!user || !pass) return;

        var userVal = user.value.trim();
        var passVal = pass.value;

        if (!userVal || !passVal) return;

        loginBtn.classList.add('loading');
        loginBtn.disabled = true;

        // AJAX login
        var formData = new FormData(loginForm);
        formData.append('action', 'apollo_navbar_login');

        fetch(typeof apolloNavbar !== 'undefined' ? apolloNavbar.ajaxUrl : '/wp-admin/admin-ajax.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data.success) {
            handleLoginSuccess(data.data);
          } else {
            handleLoginError(data.data ? data.data.message : 'Erro ao fazer login');
          }
        })
        .catch(function(err) {
          handleLoginError(err.message || 'Erro de conexão');
        });
      });
    }

    function handleLoginSuccess(data) {
      loginBtn.classList.remove('loading');
      loginBtn.disabled = false;

      if (loginSection) {
        loginSection.classList.add('fade-out');

        setTimeout(function() {
          loginSection.classList.add('hidden');
          loginSection.classList.remove('fade-out');

          if (loggedContent) {
            loggedContent.classList.remove('hidden');
            loggedContent.classList.add('reveal-up');

            setTimeout(function() {
              loggedContent.classList.remove('reveal-up');
            }, 500);
          }

          updateAuthUI('logged');
          updateNotifState();

          // Reload page to update UI
          if (data && data.redirect) {
            window.location.href = data.redirect;
          } else {
            window.location.reload();
          }
        }, 400);
      }
    }

    function handleLoginError(msg) {
      loginBtn.classList.remove('loading');
      loginBtn.disabled = false;

      // Show error
      var errorEl = loginForm.querySelector('.login-error');
      if (!errorEl) {
        errorEl = d.createElement('div');
        errorEl.className = 'login-error';
        errorEl.style.cssText = 'color: #ef4444; font-size: .8rem; text-align: center; margin-top: .5rem;';
        loginForm.appendChild(errorEl);
      }
      errorEl.textContent = msg;

      setTimeout(function() {
        if (errorEl) errorEl.remove();
      }, 5000);
    }

    /* ========================================
       LOAD MORE CHAT
       ======================================== */
    var loadBtn = d.getElementById('load-more-btn');
    var chatScroller = d.getElementById('chat-scroller');

    if (loadBtn && chatScroller) {
      loadBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        var text = loadBtn.querySelector('.load-more-text');
        if (!text) return;

        text.textContent = '...';
        loadBtn.style.opacity = '0.5';
        loadBtn.style.pointerEvents = 'none';

        // Fetch more messages via AJAX
        fetch((typeof apolloNavbar !== 'undefined' ? apolloNavbar.restUrl : '/wp-json/apollo/v1/') + 'chat/more', {
          method: 'GET',
          headers: {
            'X-WP-Nonce': typeof apolloNavbar !== 'undefined' ? apolloNavbar.nonce : ''
          }
        })
        .then(function(res) { return res.json(); })
        .then(function(messages) {
          if (messages && messages.length) {
            messages.forEach(function(m) {
              var card = createMsgCard(m);
              chatScroller.insertBefore(card, loadBtn);
            });
          }

          text.textContent = '+ Mais';
          loadBtn.style.opacity = '1';
          loadBtn.style.pointerEvents = 'auto';
          chatScroller.scrollBy({ left: 150, behavior: 'smooth' });
        })
        .catch(function() {
          text.textContent = '+ Mais';
          loadBtn.style.opacity = '1';
          loadBtn.style.pointerEvents = 'auto';
        });
      });
    }

    function createMsgCard(m) {
      var card = d.createElement('div');
      card.className = 'msg-card';
      card.innerHTML =
        '<div class="msg-header">' +
          '<div class="msg-avatar ' + (m.color || 'bg-gray') + '">' + (m.avatar || m.name.charAt(0).toUpperCase()) + '</div>' +
          '<div class="msg-info">' +
            '<span class="msg-name">' + escapeHtml(m.name) + '</span>' +
            '<span class="msg-time">' + escapeHtml(m.time) + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="msg-preview' + (m.is_me ? ' me' : '') + '">' +
          (m.is_me ? 'Eu: ' : '') + escapeHtml(m.message) +
        '</div>';
      return card;
    }

    function escapeHtml(str) {
      var div = d.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    /* ========================================
       SCROLL CAPTURE SYSTEM
       ======================================== */
    var scrollRows = d.querySelectorAll('[data-scroll-capture="true"]');

    function canScrollLeft(el) { return el.scrollLeft > 0; }
    function canScrollRight(el) { return el.scrollLeft < (el.scrollWidth - el.clientWidth - 1); }

    function handleWheel(e) {
      var target = e.target.closest('[data-scroll-capture="true"]');
      if (!target) return;

      var deltaY = e.deltaY;
      var deltaX = e.deltaX;

      if (Math.abs(deltaY) > Math.abs(deltaX)) {
        var canRight = canScrollRight(target);
        var canLeft = canScrollLeft(target);

        if ((deltaY > 0 && canRight) || (deltaY < 0 && canLeft)) {
          e.preventDefault();
          e.stopPropagation();
          target.scrollLeft += deltaY;
          target.classList.add('scroll-active');

          clearTimeout(target._scrollTimer);
          target._scrollTimer = setTimeout(function() {
            target.classList.remove('scroll-active');
          }, 150);
        } else {
          target.classList.remove('scroll-active');
        }
      }
    }

    scrollRows.forEach(function(row) {
      row.addEventListener('wheel', handleWheel, { passive: false });
    });

    /* ========================================
       DARK MODE TOGGLE
       ======================================== */
    var darkModeToggle = d.getElementById('dark-mode-toggle');

    if (darkModeToggle) {
      // Check saved preference
      var isDark = localStorage.getItem('apollo-dark-mode') === 'true';
      if (isDark) {
        d.documentElement.setAttribute('data-theme', 'dark');
        d.documentElement.classList.add('dark-mode');
        d.body.classList.add('dark-mode');
      }

      darkModeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        var isDarkNow = d.documentElement.getAttribute('data-theme') === 'dark';

        if (isDarkNow) {
          d.documentElement.removeAttribute('data-theme');
          d.documentElement.classList.remove('dark-mode');
          d.body.classList.remove('dark-mode');
          localStorage.setItem('apollo-dark-mode', 'false');
        } else {
          d.documentElement.setAttribute('data-theme', 'dark');
          d.documentElement.classList.add('dark-mode');
          d.body.classList.add('dark-mode');
          localStorage.setItem('apollo-dark-mode', 'true');
        }
      });
    }

    /* ========================================
       TOUCH ENHANCEMENTS
       ======================================== */
    var touchTarget = null;

    d.addEventListener('touchstart', function(e) {
      touchTarget = e.target.closest('.msg-card, .notif-item, .app-item, .profile-link');
      if (touchTarget) touchTarget.style.transition = 'none';
    }, { passive: true });

    d.addEventListener('touchend', function() {
      if (touchTarget) {
        touchTarget.style.transition = '';
        touchTarget = null;
      }
    }, { passive: true });

    // Prevent context menu on interactive elements
    d.addEventListener('contextmenu', function(e) {
      if (e.target.closest('.msg-card, .notif-item, .app-item')) {
        e.preventDefault();
      }
    });

    /* ========================================
       GLOBAL NOTIFICATION TRIGGER HANDLER
       Captures ALL notification buttons across the site
       and opens the navbar notification modal
       ======================================== */
    function openNotificationModal() {
      closeAll();
      var notifMenu = d.getElementById('menu-notif');
      var notifBtn = d.getElementById('btn-notif');
      if (notifMenu && notifBtn) {
        notifMenu.style.display = 'flex';
        notifMenu.offsetHeight; // Force reflow
        notifMenu.classList.add('active');
        notifMenu.setAttribute('aria-hidden', 'false');
        notifBtn.setAttribute('aria-expanded', 'true');
        // Scroll navbar into view if needed
        navbar.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    // Capture all notification button clicks site-wide
    d.addEventListener('click', function(e) {
      var notifTrigger = e.target.closest([
        '[data-apollo-notif-trigger]',
        '.ap-notifications-btn',
        '.apollo-nav-btn-notif',
        '[data-action="mobile-notifications"]',
        '[aria-label*="Notificações"]',
        '[aria-label*="Notifications"]',
        '[data-tooltip="Notificações"]',
        '.header-btn[aria-label="Notificações"]'
      ].join(','));

      if (notifTrigger && notifTrigger.id !== 'btn-notif') {
        e.preventDefault();
        e.stopPropagation();
        openNotificationModal();
      }
    }, true); // Use capture phase to intercept before other handlers

    /* ========================================
       INIT AUTH UI
       ======================================== */
    updateAuthUI(authState);

    /* ========================================
       ADMIN SUBMENU TOGGLE (Bulk Edit accordion)
       ======================================== */
    var submenuToggles = d.querySelectorAll('[data-submenu-toggle]');
    submenuToggles.forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var key = btn.getAttribute('data-submenu-toggle');
        var sub = d.querySelector('[data-submenu="' + key + '"]');
        if (!sub) return;

        var isOpen = sub.classList.contains('open');
        // Close all other submenus first
        d.querySelectorAll('.admin-submenu.open').forEach(function(s) { s.classList.remove('open'); });
        d.querySelectorAll('.has-submenu.expanded').forEach(function(b) { b.classList.remove('expanded'); });

        if (!isOpen) {
          sub.classList.add('open');
          btn.classList.add('expanded');
        }
      });
    });

    /* ========================================
       LIVE POLLING — CHAT & NOTIFICATIONS
       ======================================== */
    if (authState === 'logged' && window.apolloNavbar && window.apolloNavbar.restUrl) {
      var lastPollTime = '';
      var pollInterval = 8000; // 8 seconds

      function apolloPoll() {
        var url = window.apolloNavbar.restUrl + 'chat/poll';
        if (lastPollTime) url += '?since=' + encodeURIComponent(lastPollTime);

        fetch(url, {
          headers: { 'X-WP-Nonce': window.apolloNavbar.nonce },
          credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          lastPollTime = data.timestamp || '';

          // Update notification badge
          var nBadge = d.getElementById('notif-badge');
          if (nBadge) {
            nBadge.dataset.notif = (data.unread_notifs > 0) ? 'true' : 'false';
          }

          // Update chat badge on btn-chat
          var cBtn = d.getElementById('btn-chat');
          if (cBtn) {
            var cBadge = cBtn.querySelector('.badge');
            if (cBadge) {
              cBadge.dataset.notif = (data.unread_messages > 0) ? 'true' : 'false';
            } else if (data.unread_messages > 0) {
              var b = d.createElement('div');
              b.className = 'badge';
              b.dataset.notif = 'true';
              b.style.cssText = 'position:absolute;top:4px;right:2px;';
              cBtn.appendChild(b);
            }
          }
        })
        .catch(function() { /* silent */ });
      }

      // Start polling after 3s delay
      setTimeout(function() {
        apolloPoll();
        setInterval(apolloPoll, pollInterval);
      }, 3000);
    }

    /* ========================================
       KEYBOARD NAVIGATION
       ======================================== */
    d.addEventListener('keydown', function(e) {
      // Tab navigation within dropdowns
      if (e.key === 'Tab') {
        var activeMenu = d.querySelector('.dropdown-menu.active');
        if (activeMenu) {
          var focusables = activeMenu.querySelectorAll('a, button, input, [tabindex]:not([tabindex="-1"])');
          if (focusables.length === 0) return;

          var first = focusables[0];
          var last = focusables[focusables.length - 1];

          if (e.shiftKey && d.activeElement === first) {
            e.preventDefault();
            last.focus();
          } else if (!e.shiftKey && d.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
        }
      }
    });
  }

  // Initialize on DOM ready
  if (d.readyState === 'loading') {
    d.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
