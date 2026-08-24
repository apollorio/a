/**
 * loc-hero.js — Hero slider para a single page do Local
 *
 * Auto-play, dots de paginação, swipe touch.
 * Inicializado automaticamente se #heroTrack existir.
 *
 * @package Apollo\Local
 */
(function () {
  'use strict';

  var AUTOPLAY_MS = 5000;

  function initHeroSlider() {
    var track = document.getElementById('heroTrack');
    if (!track) return;

    var slides = track.querySelectorAll('.hero-slide');
    var dots   = document.querySelectorAll('.hero-dot');
    if (slides.length <= 1) return;

    var current  = 0;
    var total    = slides.length;
    var interval = null;

    function goTo(index) {
      current = ((index % total) + total) % total; // wrap negativo
      track.style.transform = 'translateX(-' + (current * 100) + '%)';
      dots.forEach(function (d, i) {
        d.classList.toggle('active', i === current);
      });
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function startAutoPlay() {
      stopAutoPlay();
      interval = window.setInterval(next, AUTOPLAY_MS);
    }

    function stopAutoPlay() {
      if (interval) {
        window.clearInterval(interval);
        interval = null;
      }
    }

    // ── Dots ──────────────────────────────────────────────────────────────
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var idx = parseInt(this.getAttribute('data-slide'), 10);
        goTo(idx);
        startAutoPlay();
      });
    });

    // ── Setas (se existirem) ──────────────────────────────────────────────
    var btnPrev = document.querySelector('.hero-arrow-prev');
    var btnNext = document.querySelector('.hero-arrow-next');
    if (btnPrev) btnPrev.addEventListener('click', function () { prev(); startAutoPlay(); });
    if (btnNext) btnNext.addEventListener('click', function () { next(); startAutoPlay(); });

    // ── Touch / swipe ─────────────────────────────────────────────────────
    var touchStartX = 0;
    track.addEventListener('touchstart', function (e) {
      touchStartX = e.touches[0].clientX;
      stopAutoPlay();
    }, { passive: true });

    track.addEventListener('touchend', function (e) {
      var diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 40) {
        diff > 0 ? next() : prev();
      }
      startAutoPlay();
    }, { passive: true });

    // ── Pausa no hover ────────────────────────────────────────────────────
    track.closest('.hero-slider-wrapper') &&
      track.closest('.hero-slider-wrapper').addEventListener('mouseenter', stopAutoPlay);
    track.closest('.hero-slider-wrapper') &&
      track.closest('.hero-slider-wrapper').addEventListener('mouseleave', startAutoPlay);

    goTo(0);
    startAutoPlay();
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeroSlider, { once: true });
  } else {
    initHeroSlider();
  }
})();
