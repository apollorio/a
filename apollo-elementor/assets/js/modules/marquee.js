(function() {
  "use strict";

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init() {
    var tracks = document.querySelectorAll('[data-ae-marquee]');
    if (!tracks.length) return;
    if (typeof gsap === 'undefined') {
      console.warn('[ae/marquee] GSAP not found');
      return;
    }

    for (var i = 0; i < tracks.length; i++) {
      setupTrack(tracks[i]);
    }
  }

  function setupTrack(track) {
    var inner = track.querySelector('.ae-marquee-inner');
    if (!inner) return;

    var speed = parseFloat(track.getAttribute('data-ae-marquee-speed')) || 40;
    var direction = track.getAttribute('data-ae-marquee-dir') === 'right' ? 1 : -1;
    var items = inner.children;
    if (!items.length) return;

    var clone = inner.cloneNode(true);
    track.appendChild(clone);

    var sets = [inner, clone];

    if (REDUCED) {
      for (var s = 0; s < sets.length; s++) {
        sets[s].style.transform = 'translateX(0)';
      }
      return;
    }

    var totalWidth = inner.scrollWidth;
    var duration = totalWidth / speed;

    gsap.set(sets, { xPercent: function(idx) { return idx * 100 * direction * -1; } });

    var tl = gsap.timeline({ repeat: -1, defaults: { ease: 'none' } });

    tl.to(sets, {
      xPercent: '+=' + (100 * direction),
      duration: duration,
      modifiers: {
        xPercent: function(x) {
          x = parseFloat(x);
          return gsap.utils.wrap(-100, 100, x) + '%';
        }
      }
    });

    track._aeTl = tl;

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          tl.play();
        } else {
          tl.pause();
        }
      });
    }, { threshold: 0 });

    observer.observe(track);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
