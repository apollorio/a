/**
 * Apollo Mural — Dashboard Interactions
 *
 * @package Apollo\Templates
 * @since   1.1.0
 */

(function () {
    'use strict';

    // ═══ Scroll Reveal ═══
    function initScrollReveal() {
        var els = document.querySelectorAll('.reveal-up');
        if (!els.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });

        els.forEach(function (el) { observer.observe(el); });
    }

    // ═══ Ticker: Pause on hover ═══
    function initTicker() {
        var track = document.querySelector('.news-track');
        if (!track) return;

        var bar = track.closest('.news-ticker-bar');
        bar.addEventListener('mouseenter', function () {
            track.style.animationPlayState = 'paused';
        });
        bar.addEventListener('mouseleave', function () {
            track.style.animationPlayState = 'running';
        });
    }

    // ═══ Event cards: link to permalink ═══
    function initCardLinks() {
        document.querySelectorAll('.event-card').forEach(function (card) {
            var link = card.querySelector('a');
            if (!link) {
                // Cards are wrapped in <article>, the whole card is clickable
                // via CSS cursor:pointer — no extra JS needed unless we add links
                return;
            }
            card.addEventListener('click', function () {
                window.location.href = link.href;
            });
        });
    }

    // ═══ Smooth scroll anchors ═══
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    // ═══ Init ═══
    document.addEventListener('DOMContentLoaded', function () {
        initScrollReveal();
        initTicker();
        initCardLinks();
        initSmoothScroll();
    });
})();
