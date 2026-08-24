(function() {
  "use strict";

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init() {
    var charts = document.querySelectorAll('[data-ae-chart-line]');
    if (!charts.length) return;

    for (var i = 0; i < charts.length; i++) {
      setupLine(charts[i]);
    }
  }

  function setupLine(container) {
    var path = container.querySelector('[data-ae-line-path]');
    var area = container.querySelector('[data-ae-line-area]');
    if (!path) return;

    var length = path.getTotalLength ? path.getTotalLength() : 0;
    if (!length) return;

    path.style.strokeDasharray = length;
    path.style.strokeDashoffset = length;

    if (area) {
      area.style.opacity = '0';
    }

    if (REDUCED) {
      path.style.strokeDashoffset = '0';
      if (area) area.style.opacity = '1';
      return;
    }

    animateLine(path, area, container, length);
  }

  function animateLine(path, area, container, length) {
    if (typeof gsap !== 'undefined') {
      var tl = gsap.timeline({
        scrollTrigger: typeof ScrollTrigger !== 'undefined' ? {
          trigger: container,
          start: 'top 80%',
          toggleActions: 'play none none reverse'
        } : undefined
      });

      tl.to(path, {
        strokeDashoffset: 0,
        duration: 1.4,
        ease: 'power2.inOut'
      });

      if (area) {
        tl.to(area, {
          opacity: 1,
          duration: 0.6,
          ease: 'power1.out'
        }, '-=0.4');
      }

      var dots = container.querySelectorAll('[data-ae-line-dot]');
      if (dots.length) {
        tl.fromTo(dots,
          { scale: 0, opacity: 0 },
          { scale: 1, opacity: 1, duration: 0.3, stagger: 0.06, ease: 'back.out(2)' },
          '-=0.3'
        );
      }

      return;
    }

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          path.style.transition = 'stroke-dashoffset 1.4s ease-in-out';
          path.style.strokeDashoffset = '0';

          if (area) {
            setTimeout(function() {
              area.style.transition = 'opacity 0.6s ease-out';
              area.style.opacity = '1';
            }, 1000);
          }

          observer.unobserve(container);
        }
      });
    }, { threshold: 0.3 });

    observer.observe(container);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
