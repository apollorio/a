/* ═══════════════════════════════════════════════════════════════
   gestor.helpers.js — Shared Utilities (WordPress-adapted)
   Provides: window.G namespace with $, $$, on, toast, formatCurrency, ajax
   Depends on: ApolloGestor (wp_localize_script)
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var G = window.G = {};
    var CFG = window.ApolloGestor || {};

    /** @param {string} s  @param {Element} [c] */
    G.$ = function (s, c) { return (c || document).querySelector(s); };

    /** @param {string} s  @param {Element} [c] */
    G.$$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

    /** @param {Element} el  @param {string} ev  @param {Function} fn  @param {Object} [o] */
    G.on = function (el, ev, fn, o) { if (el) el.addEventListener(ev, fn, o); };

    /**
     * Show a toast notification
     * @param {string} msg
     * @param {'default'|'success'|'error'} [type]
     * @param {number} [dur]
     */
    G.toast = function (msg, type, dur) {
        type = type || 'default';
        dur  = dur  || 2800;
        var c = G.$('#toast-container');
        if (!c) return;
        var icon = type === 'success' ? 'check' : type === 'error' ? 'close-circle' : 'information';
        var t = document.createElement('div');
        t.className = 'toast' + (type !== 'default' ? ' toast-' + type : '');
        t.innerHTML = '<i class="ri-' + icon + '-line"></i> ' + G.escapeHtml(msg);
        c.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('show'); });
        setTimeout(function () {
            t.classList.remove('show');
            setTimeout(function () { t.remove(); }, 300);
        }, dur);
    };

    /**
     * Format a Number to BRL currency string
     * @param {number} v
     * @returns {string}
     */
    G.formatCurrency = function (v) {
        return 'R$ ' + Number(v).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    /**
     * Simple escapeHtml to prevent XSS when rendering data
     * @param {string} str
     * @returns {string}
     */
    G.escapeHtml = function (str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };

    /**
     * WordPress AJAX helper via FormData
     * @param {string} action  - AJAX action name (without 'apollo_gestor_' prefix)
     * @param {Object} [data]  - Key/value pairs to send
     * @returns {Promise<Object>}
     */
    G.ajax = function (action, data) {
        var fd = new FormData();
        fd.append('action', 'apollo_gestor_' + action);
        fd.append('nonce', CFG.nonce || '');
        if (data) {
            Object.keys(data).forEach(function (k) {
                fd.append(k, data[k]);
            });
        }
        return fetch(CFG.ajaxUrl || '', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
        }).then(function (r) { return r.json(); });
    };

    /**
     * Access the localized config
     */
    G.cfg = CFG;

})();
