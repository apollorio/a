/**
 * loc-ui.js — Interações de UI da single page do Local
 *
 * - Botão de favorito (toggle via apollo-fav REST)
 * - Botão de compartilhar (Web Share API / fallback clipboard)
 * - Formulário apollo_add_loc (POST via REST)
 * - Scroll reveal para seções
 *
 * @package Apollo\Local
 */
(function () {
  'use strict';

  // ── Share ──────────────────────────────────────────────────────────────────
  function initShare() {
    var btn = document.querySelector('[data-action="share-loc"]');
    if (!btn) return;

    btn.addEventListener('click', function () {
      var title = btn.dataset.title || document.title;
      var url   = btn.dataset.url   || window.location.href;

      if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(function () {});
      } else {
        navigator.clipboard.writeText(url).then(function () {
          btn.setAttribute('data-tooltip', 'Link copiado!');
          window.setTimeout(function () {
            btn.removeAttribute('data-tooltip');
          }, 2500);
        });
      }
    });
  }

  // ── Add Loc Form ───────────────────────────────────────────────────────────
  function initAddLocForm() {
    var form = document.getElementById('apolloAddLocForm');
    if (!form || !window.apolloAddLoc) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var btn = form.querySelector('[type="submit"]');
      var msg = document.getElementById('apolloAddLocMsg');

      btn.disabled = true;
      btn.textContent = 'Enviando\u2026';
      msg.hidden = true;

      var data = {
        title:     form.querySelector('#loc-name').value,
        address:   (form.querySelector('#loc-address')  || {}).value || '',
        city:      (form.querySelector('#loc-city')     || {}).value || '',
        state:     (form.querySelector('#loc-state')    || {}).value || '',
        phone:     (form.querySelector('#loc-phone')    || {}).value || '',
        instagram: (form.querySelector('#loc-instagram') || {}).value || '',
      };

      fetch(window.apolloAddLoc.rest_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce':   window.apolloAddLoc.nonce,
        },
        body: JSON.stringify(data),
      })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
          msg.hidden = false;
          if (res.ok) {
            msg.className = 'apollo-form-msg success';
            msg.textContent = 'Local enviado com sucesso!';
            form.reset();
          } else {
            msg.className = 'apollo-form-msg error';
            msg.textContent = res.data.message || 'Erro ao enviar. Tente novamente.';
          }
        })
        .catch(function () {
          msg.hidden = false;
          msg.className = 'apollo-form-msg error';
          msg.textContent = 'Erro de rede. Tente novamente.';
        })
        .finally(function () {
          btn.disabled = false;
          btn.textContent = 'Enviar local';
        });
    });
  }

  // ── Scroll Reveal (Intersection Observer) ─────────────────────────────────
  function initScrollReveal() {
    if (!window.IntersectionObserver) return;

    var els = document.querySelectorAll('.loc-section');
    if (!els.length) return;

    var io = new window.IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08 }
    );

    els.forEach(function (el) { io.observe(el); });
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  function init() {
    initShare();
    initAddLocForm();
    initScrollReveal();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
