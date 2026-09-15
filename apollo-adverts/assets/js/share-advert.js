/**
 * Advert share — readonly URL popover + copy.
 *
 * One handler for [data-advert-share] on /anuncios, /anuncio/{slug}/, /casa.
 * Stops propagation so marquee/card navigation does not fire.
 *
 * @package Apollo\Adverts
 */
(function () {
  'use strict';

  var active = null;

  function escAttr(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function toast(msg) {
    var el = document.createElement('div');
    el.className = 'ap-advert-share-toast';
    el.textContent = msg;
    document.body.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('is-visible'); });
    setTimeout(function () {
      el.classList.remove('is-visible');
      setTimeout(function () { el.remove(); }, 220);
    }, 1800);
  }

  function closeShare() {
    if (!active) return;
    if (active.pop && active.pop.parentNode) active.pop.remove();
    if (active.btn) active.btn.setAttribute('aria-expanded', 'false');
    active = null;
  }

  function copyUrl(url, input) {
    if (input) {
      input.focus();
      input.select();
      try { input.setSelectionRange(0, url.length); } catch (e) { /* ignore */ }
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(function () {
        toast('Link copiado');
      }).catch(fallbackCopy);
      return;
    }
    fallbackCopy();

    function fallbackCopy() {
      try {
        if (document.execCommand('copy')) toast('Link copiado');
      } catch (err) { /* ignore */ }
    }
  }

  function openShare(btn) {
    var url = btn.getAttribute('data-advert-share');
    if (!url) return;

    closeShare();

    var pop = document.createElement('div');
    pop.className = 'ap-advert-share-pop';
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-label', 'Link do anúncio');
    pop.innerHTML =
      '<input type="text" class="ap-advert-share-input" readonly value="' + escAttr(url) + '" aria-label="Link do anúncio" />' +
      '<span class="ap-advert-share-hint">Toque para copiar</span>';

    document.body.appendChild(pop);

    var rect = btn.getBoundingClientRect();
    var left = Math.min(rect.left, window.innerWidth - 280 - 8);
    var top = rect.top - pop.offsetHeight - 8;
    if (top < 8) top = rect.bottom + 8;
    pop.style.left = Math.max(8, left) + 'px';
    pop.style.top = top + 'px';

    btn.setAttribute('aria-expanded', 'true');
    active = { btn: btn, pop: pop };

    var input = pop.querySelector('.ap-advert-share-input');
    if (input) {
      input.addEventListener('click', function (ev) {
        ev.stopPropagation();
        copyUrl(url, input);
      });
    }
  }

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-advert-share]');
    if (btn) {
      ev.preventDefault();
      ev.stopPropagation();
      if (active && active.btn === btn) closeShare();
      else openShare(btn);
      return;
    }
    if (active && !ev.target.closest('.ap-advert-share-pop')) closeShare();
  }, true);

  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') closeShare();
  });
})();
