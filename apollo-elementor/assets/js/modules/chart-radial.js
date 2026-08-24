(function() {
  "use strict";

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init() {
    var charts = document.querySelectorAll('[data-ae-chart-radial]');
    if (!charts.length) return;

    for (var i = 0; i < charts.length; i++) {
      setupRadial(charts[i]);
    }
  }

  function setupRadial(container) {
    var segments = container.querySelectorAll('[data-ae-segment]');
    if (!segments.length) return;

    for (var i = 0; i < segments.length; i++) {
      var seg = segments[i];
      var circle = seg.querySelector('circle') || seg;
      if (!circle) continue;

      var r = parseFloat(circle.getAttribute('r')) || 0;
      var circumference = 2 * Math.PI * r;
      var pct = parseFloat(seg.getAttribute('data-ae-segment') || seg.getAttribute('data-value')) || 0;
      var dashLen = (pct / 100) * circumference;

      circle.style.strokeDasharray = circumference;
      circle.style.strokeDashoffset = circumference;

      if (REDUCED) {
        circle.style.strokeDashoffset = circumference - dashLen;
        continue;
      }

      animateSegment(circle, container, circumference, dashLen);
    }
  }

  function animateSegment(circle, container, circumference, dashLen) {
    if (typeof gsap !== 'undefined') {
      gsap.to(circle, {
        strokeDashoffset: circumference - dashLen,
        duration: 1.2,
        ease: 'power2.out',
        scrollTrigger: typeof ScrollTrigger !== 'undefined' ? {
          trigger: container,
          start: 'top 80%',
          toggleActions: 'play none none reverse'
        } : undefined
      });
      return;
    }

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          circle.style.transition = 'stroke-dashoffset 1.2s ease-out';
          circle.style.strokeDashoffset = circumference - dashLen;
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
