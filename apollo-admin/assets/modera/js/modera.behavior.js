/*
   APOLLO::RIO · MODERA · modera.behavior.js
   Replaces the standalone mockup's shell.admin.js. core.js already injects
   script.theme.js (its own file header says so explicitly: "DO NOT LOAD
   SCRIPT THEME DUE IS INJECTED VIA CORE.JS") which binds, in CAPTURE phase,
   #burger/#ax-aside/#ax-overlay, #ic-act/#ic-pf (panels), #ic-apps/#apps-pop,
   .panel-tab, [data-modal]/[data-modal-close]/.modal-backdrop, #aside-pill
   (dark-mode, persisted the way core.js's pre-paint boot actually reads it).
   Re-binding any of those here would double-fire the exact handlers
   script.theme.js's own changelog documents fixing (double-toggle bugs).

   This file supplies ONLY what core's generic shell does NOT cover:
     · toast(msg, icon)   — dynamic message/icon; core's built-in toast is a
                            fixed copy-to-clipboard pattern with no public API.
     · drawerToL2/drawerToL1 — the admin panel's multi-level L1⇄L2 aside
                            slide is page-specific, not part of the generic
                            single-level shell-aside contract.
     · closeAside/closePanels — state-only helpers app.admin.js calls after
                            a nav action (e.g. picking a sub-item on mobile).
                            These mutate the SAME classes/attributes core.js's
                            own closeDrawer()/closePanel() use — no listener
                            is (re)bound, so there is no double-owner risk.

   Exposes window.AdminShell with the same 5-method contract the ported
   app.admin.js expects, so that file needed zero changes.
*/
(function (w, d) {
  'use strict';
  var $  = function (s, c) { return (c || d).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };

  var aside    = $('#ax-aside');
  var overlay  = $('#ax-overlay');
  var navStack = $('#nav-stack');

  /* ── Multi-level slide (L1 plugins ⇄ L2 sub-links) — admin-specific ── */
  function drawerToL2() { if (navStack) navStack.classList.add('is-l2'); }
  function drawerToL1() { if (navStack) navStack.classList.remove('is-l2'); }
  var navBack = $('#nav-back'); if (navBack) navBack.addEventListener('click', drawerToL1);

  /* ── State-only close helpers (mirror core.js's own class/attr model) ── */
  function closeAside() {
    if (aside) aside.classList.remove('open');
    if (overlay && !$$('.panel-r.open, .apps-pop.open').length) overlay.classList.remove('on');
  }
  function closePanels() {
    $$('.panel-r.open').forEach(function (p) { p.classList.remove('open'); p.setAttribute('aria-hidden', 'true'); });
    var apps = $('#apps-pop');
    if (apps) { apps.classList.remove('open'); apps.setAttribute('aria-hidden', 'true'); }
    if (overlay && !(aside && aside.classList.contains('open'))) overlay.classList.remove('on');
  }

  /* ── Dynamic toast (core's #toast-host toast is a fixed copy-pattern; this
     page has no #toast-btn so core's own show/loading path never triggers —
     only its harmless #toast-x hide-on-click also fires, redundantly). ── */
  var toastPanel = $('#toast-panel'), toastText = $('#toast-text'), toastX = $('#toast-x'), toastTimer = null;
  function toast(msg, icon) {
    if (!toastPanel) return;
    if (toastText) toastText.textContent = msg;
    var ic = toastPanel.querySelector('.toast-icon i');
    if (ic) ic.className = icon || 'ri-checkbox-circle-line';
    toastPanel.style.display = 'flex';
    requestAnimationFrame(function () { toastPanel.classList.add('show'); });
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, 2600);
  }
  function hideToast() { if (!toastPanel) return; toastPanel.classList.remove('show'); setTimeout(function () { toastPanel.style.display = 'none'; }, 350); }
  if (toastX) toastX.addEventListener('click', hideToast);

  w.AdminShell = { toast: toast, closePanels: closePanels, closeAside: closeAside, drawerToL2: drawerToL2, drawerToL1: drawerToL1 };
})(window, document);
