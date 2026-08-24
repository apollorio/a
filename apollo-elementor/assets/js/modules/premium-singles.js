(function() {
  "use strict";

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init() {
    var page = document.querySelector('[data-ae-single]');
    if (!page) return;

    if (typeof gsap === 'undefined') {
      console.warn('[ae/premium-singles] GSAP not found');
      return;
    }

    var ST = (typeof ScrollTrigger !== 'undefined') ? ScrollTrigger : null;
    if (ST && gsap.registerPlugin) gsap.registerPlugin(ST);

    var lenis = null;
    if (typeof Lenis !== 'undefined') {
      lenis = new Lenis({
        duration: 1.2,
        easing: function(t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
        smooth: true
      });

      if (ST) {
        lenis.on('scroll', function() { ST.update(); });
        gsap.ticker.add(function(time) { lenis.raf(time * 1000); });
      } else {
        function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
        requestAnimationFrame(raf);
      }
    }

    if (REDUCED) return;
    if (!ST) { console.warn('[ae/premium-singles] ScrollTrigger not found'); return; }

    heroScrub(page, ST);
    spotlightReveal(page, ST);
    stickyStrip(page, ST);
    splitTextReveal(page, ST);
    clipPathReveals(page, ST);
  }

  function heroScrub(page, ST) {
    var hero = page.querySelector('[data-ae-hero]');
    if (!hero) return;

    var img = hero.querySelector('img, video, [data-ae-hero-media]');
    if (!img) return;

    gsap.fromTo(img,
      { scale: 1.15, yPercent: 0 },
      {
        scale: 1,
        yPercent: -10,
        ease: 'none',
        scrollTrigger: {
          trigger: hero,
          start: 'top top',
          end: 'bottom top',
          scrub: 0.6
        }
      }
    );

    var overlay = hero.querySelector('[data-ae-hero-overlay]');
    if (overlay) {
      gsap.fromTo(overlay,
        { opacity: 0 },
        {
          opacity: 0.6,
          ease: 'none',
          scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true }
        }
      );
    }
  }

  function spotlightReveal(page, ST) {
    var spots = page.querySelectorAll('[data-ae-spotlight]');
    for (var i = 0; i < spots.length; i++) {
      gsap.fromTo(spots[i],
        { opacity: 0, y: 60 },
        {
          opacity: 1, y: 0, duration: 0.9, ease: 'power3.out',
          scrollTrigger: { trigger: spots[i], start: 'top 85%', toggleActions: 'play none none reverse' }
        }
      );
    }
  }

  function stickyStrip(page, ST) {
    var strip = page.querySelector('[data-ae-sticky-strip]');
    if (!strip) return;

    ST.create({
      trigger: strip,
      start: 'top top',
      end: 'bottom bottom',
      pin: strip.querySelector('[data-ae-strip-pin]') || strip,
      pinSpacing: false
    });
  }

  function splitTextReveal(page, ST) {
    if (typeof SplitText === 'undefined') return;

    var headings = page.querySelectorAll('[data-ae-split]');
    for (var i = 0; i < headings.length; i++) {
      var split = new SplitText(headings[i], { type: 'chars,words,lines', linesClass: 'ae-split-line' });

      gsap.fromTo(split.chars,
        { opacity: 0, yPercent: 80 },
        {
          opacity: 1, yPercent: 0,
          duration: 0.7, stagger: 0.02, ease: 'power3.out',
          scrollTrigger: { trigger: headings[i], start: 'top 85%', toggleActions: 'play none none reverse' }
        }
      );
    }
  }

  function clipPathReveals(page, ST) {
    var els = page.querySelectorAll('[data-ae-clip-reveal]');
    for (var i = 0; i < els.length; i++) {
      var dir = els[i].getAttribute('data-ae-clip-reveal') || 'bottom';
      var from, to;

      switch (dir) {
        case 'left':   from = 'inset(0 100% 0 0)'; to = 'inset(0 0% 0 0)'; break;
        case 'right':  from = 'inset(0 0 0 100%)'; to = 'inset(0 0 0 0%)'; break;
        case 'top':    from = 'inset(0 0 100% 0)'; to = 'inset(0 0 0% 0)'; break;
        default:       from = 'inset(100% 0 0 0)'; to = 'inset(0% 0 0 0)'; break;
      }

      gsap.fromTo(els[i],
        { clipPath: from },
        {
          clipPath: to, duration: 1, ease: 'power3.inOut',
          scrollTrigger: { trigger: els[i], start: 'top 85%', toggleActions: 'play none none reverse' }
        }
      );
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
