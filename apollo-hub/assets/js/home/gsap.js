/**
 * APOLLO::RIO — HOME / gsap.js
 * Hero parallax, FAB entrance, radio entrance, scroll reveal
 * Depends on: GSAP + ScrollTrigger from Apollo CDN
 */
;(function () {
    'use strict';

    var homePanel  = document.querySelector('[data-panel="home"]');
    var menuFab    = document.getElementById('nhMenuFab');
    var radioWidget = document.getElementById('nhRadio');

    function waitForGsap(cb, n) {
        n = n || 0;
        if (typeof gsap !== 'undefined') return cb();
        if (n < 100) return setTimeout(function () { waitForGsap(cb, n + 1); }, 50);
    }

    waitForGsap(function () {

        /* Cancel CSS fallback animation — GSAP takes over */
        if (homePanel) {
            homePanel.querySelectorAll('.ai').forEach(function (el) {
                el.style.animation = 'none';
            });
        }

        /* Hero video parallax */
        var heroVid = document.querySelector('.nh-hero-vid');
        if (heroVid && homePanel && typeof ScrollTrigger !== 'undefined') {
            gsap.registerPlugin(ScrollTrigger);
            gsap.to(heroVid, {
                scale: 1.12,
                ease: 'none',
                scrollTrigger: {
                    trigger: '.nh-hero',
                    scroller: homePanel,
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.5,
                }
            });
        }

        /* FAB entrance — springy pop */
        if (menuFab) {
            gsap.fromTo(menuFab,
                { y: 30, scale: 0.5, opacity: 0 },
                { y: 0, scale: 1, opacity: 1, duration: 0.7, delay: 1.6, ease: 'back.out(2.2)', overwrite: 'auto', clearProps: 'transform' }
            );
        }

        /* Radio entrance — slide from left */
        if (radioWidget) {
            gsap.from(radioWidget, {
                x: -40, opacity: 0,
                duration: 0.9, delay: 2.0,
                ease: 'power3.out', overwrite: 'auto', clearProps: 'transform',
            });
        }

        /* Scroll-triggered reveal for .ai elements */
        if (typeof ScrollTrigger !== 'undefined' && homePanel) {
            var sections = homePanel.querySelectorAll('.section');
            sections.forEach(function (sec) {
                var items = sec.querySelectorAll('.ai');
                if (!items.length) return;
                ScrollTrigger.create({
                    trigger: sec,
                    scroller: homePanel,
                    start: 'top 86%',
                    once: true,
                    onEnter: function () {
                        gsap.to(items, {
                            y: 0, opacity: 1,
                            duration: 0.52, ease: 'power3.out',
                            stagger: 0.055, overwrite: 'auto',
                        });
                    }
                });
            });
        }

    });
})();
