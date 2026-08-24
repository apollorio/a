(function() {
  "use strict";

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init() {
    var charts = document.querySelectorAll('[data-ae-chart-bar]');
    if (!charts.length) return;

    for (var i = 0; i < charts.length; i++) {
      setupBar(charts[i]);
    }
  }

  function setupBar(container) {
    var bars = container.querySelectorAll('[data-ae-bar]');
    if (!bars.length) return;

    for (var i = 0; i < bars.length; i++) {
      var bar = bars[i];
      var value = parseFloat(bar.getAttribute('data-ae-bar') || bar.getAttribute('data-value')) || 0;
      var max = parseFloat(container.getAttribute('data-ae-bar-max') || '100');
      var pct = Math.min((value / max) * 100, 100);
      var fill = bar.querySelector('[data-ae-bar-fill]') || bar;

      if (REDUCED) {
        fill.style.height = pct + '%';
        fill.style.width = pct + '%';
        continue;
      }

      var isHorizontal = container.classList.contains('ae-bar-horizontal');

      if (isHorizontal) {
        fill.style.width = '0%';
      } else {
        fill.style.height = '0%';
      }

      animateBar(fill, container, pct, isHorizontal, i);
    }
  }

  function animateBar(fill, container, pct, isHorizontal, idx) {
    var prop = isHorizontal ? 'width' : 'height';

    if (typeof gsap !== 'undefined') {
      var tweenVars = {
        duration: 0.8,
        delay: idx * 0.08,
        ease: 'power2.out'
      };
      tweenVars[prop] = pct + '%';

      if (typeof ScrollTrigger !== 'undefined') {
        tweenVars.scrollTrigger = {
          trigger: container,
          start: 'top 80%',
          toggleActions: 'play none none reverse'
        };
      }

      gsap.to(fill, tweenVars);
      return;
    }

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          fill.style.transition = prop + ' 0.8s ease-out ' + (idx * 0.08) + 's';
          fill.style[prop] = pct + '%';
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
