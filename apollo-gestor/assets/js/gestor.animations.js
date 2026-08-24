/* ═══════════════════════════════════════════════════════════════
   gestor.animations.js — GSAP Entrance Animations
   Staggered reveal of cards, panels, and `.icon-user-access` badges.

   GSAP bundle (includes Core, ScrollTrigger, ScrollSmoother, Observer,
   Draggable, InertiaPlugin, Flip) is loaded from Apollo CDN with a
   fallback that applies CSS classes when the bundle fails or is absent.
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /* ── 1. GSAP bundle loader ─────────────────────────────────── */
    var GSAP_BUNDLE = 'https://cdn.apollo.rio.br/v1.0.0/js/gsap-bundle-plugins.v3.15.0.min.js?v=t0x1x';

    function loadGsapBundle(callback) {
        if (typeof gsap !== 'undefined') {
            // Already available via Apollo CDN core.js
            callback(true);
            return;
        }
        var s = document.createElement('script');
        s.src = GSAP_BUNDLE;
        s.async = true;
        s.onload  = function () { callback(true);  };
        s.onerror = function () { callback(false); };
        document.head.appendChild(s);
    }

    /* ── 2. CSS fallback (no GSAP) ─────────────────────────────── */
    function applyFallback() {
        document.documentElement.classList.add('no-gsap');
        // Reveal all badges immediately via CSS .no-gsap rule.
    }

    /* ── 3. icon-user-access glassmorphism reveal ──────────────── */
    function animateAccessBadges() {
        var badges = document.querySelectorAll('.icon-user-access');
        if (!badges.length) return;

        gsap.to(badges, {
            opacity: 1,
            scale: 1,
            duration: 0.55,
            ease: 'back.out(1.7)',
            stagger: 0.09,
            delay: 0.6,
            onComplete: function () {
                badges.forEach(function (b) { b.classList.add('is-revealed'); });
            }
        });

        /* Morph: pulse the icon once after reveal */
        gsap.to(badges, {
            scale: 1.25,
            duration: 0.22,
            ease: 'power2.out',
            delay: 0.6 + (badges.length * 0.09) + 0.1,
            stagger: 0.05,
            yoyo: true,
            repeat: 1
        });
    }

    /* ── 4. Main entrance animations ───────────────────────────── */
    function runAnimations() {
        /* Stagger stat cards */
        gsap.from('.stat-card', {
            y: 20, opacity: 0, duration: 0.5,
            stagger: 0.08, ease: 'power3.out', delay: 1
        });

        /* Stagger chart cards */
        gsap.from('.chart-card', {
            y: 20, opacity: 0, duration: 0.5,
            stagger: 0.1, ease: 'power3.out', delay: 1.2
        });

        /* Mini cards */
        gsap.from('.mini-card', {
            y: 12, opacity: 0, duration: 0.4,
            stagger: 0.05, ease: 'power2.out', delay: 1.4
        });

        /* Staff cards — glassmorphism scale-in */
        gsap.from('.staff-card', {
            scale: 0.95, opacity: 0, duration: 0.4,
            stagger: 0.06, ease: 'power2.out', delay: 0.3
        });

        /* Supplier cards */
        gsap.from('.supplier-card', {
            x: -12, opacity: 0, duration: 0.4,
            stagger: 0.08, ease: 'power2.out', delay: 0.3
        });

        /* Visibility badges */
        animateAccessBadges();
    }

    /* ── 5. Public API ─────────────────────────────────────────── */
    window.GestorAnimations = {
        init: function () {
            loadGsapBundle(function (loaded) {
                if (loaded && typeof gsap !== 'undefined') {
                    runAnimations();
                } else {
                    applyFallback();
                }
            });
        },

        /** Re-run only the access-badge morph (e.g. after tab switch). */
        revealAccessBadges: function () {
            if (typeof gsap !== 'undefined') {
                animateAccessBadges();
            } else {
                document.querySelectorAll('.icon-user-access').forEach(function (b) {
                    b.classList.add('is-revealed');
                });
            }
        }
    };
})();
