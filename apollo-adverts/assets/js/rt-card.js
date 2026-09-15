/**
 * Expand / collapse resale ticket cards. Class toggle; height is CSS grid 0fr/1fr.
 */
(function () {
  'use strict';

  function wire(card) {
    if (!card || card.getAttribute('data-rt-wired') === '1') return;
    card.setAttribute('data-rt-wired', '1');
    var main = card.querySelector('.rt-main') || card.querySelector('.top');
    if (!main) return;

    function toggle(e) {
    if (e.target.closest('.rt-cta, [data-advert-share], .ap-advert-share-pop, a.rt-cta')) return;
      if (card.getAttribute('aria-hidden') === 'true') return;
      var open = !card.classList.contains('is-open');
      card.classList.toggle('is-open', open);
      card.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    main.addEventListener('click', toggle);
    main.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      toggle(e);
    });
  }

  function boot() {
    document.querySelectorAll('.rt-card[data-rt-card]').forEach(wire);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    setTimeout(boot, 0);
  }
  window.addEventListener('load', boot);
})();
