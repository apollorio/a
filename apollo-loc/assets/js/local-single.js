/**
 * Local Single Page JavaScript
 * Hero slider auto-play, route button
 *
 * @package Apollo\Local
 * @version 2.0.0
 */
(function() {
  'use strict';

  function initHeroSlider() {
    var track = document.getElementById('heroTrack');
    if (!track) return;

    var slides = track.querySelectorAll('.hero-slide');
    var dots = document.querySelectorAll('.hero-dot');
    if (slides.length <= 1) return;

    var current = 0;
    var total = slides.length;
    var interval = null;

    function goToSlide(index) {
      current = index % total;
      track.style.transform = 'translateX(-' + (current * 100) + '%)';
      dots.forEach(function(d, i) {
        d.classList.toggle('active', i === current);
      });
    }

    function next() { goToSlide(current + 1); }

    function startAutoPlay() {
      stopAutoPlay();
      interval = setInterval(next, 5000);
    }

    function stopAutoPlay() {
      if (interval) { clearInterval(interval); interval = null; }
    }

    // Dot clicks
    dots.forEach(function(dot) {
      dot.addEventListener('click', function() {
        var idx = parseInt(this.getAttribute('data-slide'), 10);
        goToSlide(idx);
        startAutoPlay();
      });
    });

    // Touch support
    var touchStartX = 0;
    track.addEventListener('touchstart', function(e) {
      touchStartX = e.touches[0].clientX;
      stopAutoPlay();
    }, { passive: true });

    track.addEventListener('touchend', function(e) {
      var diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 40) {
        diff > 0 ? goToSlide(current + 1) : goToSlide(current - 1 + total);
      }
      startAutoPlay();
    }, { passive: true });

    startAutoPlay();
  }

  function init() {
    initHeroSlider();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
