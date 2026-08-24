(function() {
  "use strict";

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init() {
    var grids = document.querySelectorAll('[data-ae-bento]');
    if (!grids.length) return;

    if (typeof gsap === 'undefined' || typeof gsap.plugins === 'undefined' && typeof Flip === 'undefined') {
      if (typeof gsap === 'undefined') {
        console.warn('[ae/flip-bento] GSAP not found');
        return;
      }
    }

    var FlipPlugin = (typeof Flip !== 'undefined') ? Flip : (gsap.plugins && gsap.plugins.flip);
    if (!FlipPlugin && gsap.registerPlugin) {
      console.warn('[ae/flip-bento] GSAP Flip plugin not found');
    }

    for (var i = 0; i < grids.length; i++) {
      setupGrid(grids[i]);
    }
  }

  function setupGrid(grid) {
    var cells = grid.querySelectorAll('[data-ae-cell]');
    if (!cells.length) return;

    for (var i = 0; i < cells.length; i++) {
      cells[i].addEventListener('click', function(e) {
        toggleCell(this, grid);
      });
    }
  }

  function toggleCell(cell, grid) {
    if (typeof Flip === 'undefined') {
      cell.classList.toggle('ae-cell-expanded');
      return;
    }

    var state = Flip.getState(grid.querySelectorAll('[data-ae-cell]'));
    var isExpanded = cell.classList.contains('ae-cell-expanded');

    var siblings = grid.querySelectorAll('.ae-cell-expanded');
    for (var i = 0; i < siblings.length; i++) {
      siblings[i].classList.remove('ae-cell-expanded');
    }

    if (!isExpanded) {
      cell.classList.add('ae-cell-expanded');
    }

    if (REDUCED) return;

    Flip.from(state, {
      duration: 0.6,
      ease: 'power2.inOut',
      stagger: 0.03,
      absolute: true,
      onEnter: function(elements) {
        return gsap.fromTo(elements, { opacity: 0, scale: 0.9 }, { opacity: 1, scale: 1, duration: 0.4 });
      },
      onLeave: function(elements) {
        return gsap.to(elements, { opacity: 0, scale: 0.9, duration: 0.3 });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
