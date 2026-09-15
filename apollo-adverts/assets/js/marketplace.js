/**
 * Apollo Classifieds — Marketplace Loader
 *
 * This file is kept as the registered WP handle ('apollo-adverts-marketplace').
 * It loads all modular scripts from marketplace/ via DOM injection,
 * preserving correct execution order (state → inputs → init last).
 *
 * @package Apollo\Adverts
 */
(function () {
    'use strict';

    /* Resolve base URL from this <script> tag (defer-safe). */
    var current = document.currentScript || document.querySelector('script[src*="marketplace.js"]');
    var baseUrl = current && current.src ? current.src.replace(/marketplace\.js(\?.*)?$/, 'marketplace/') : '';
    if (!baseUrl) {
        return;
    }

    /* Ordered module list — dependencies first, init last */
    var modules = [
        'gsap-reveal',
        'carousel-state',
        'carousel-drag',
        'carousel-wheel',
        'carousel-click',
        'filter-toggle',
        'modal-open',
        'modal-consent',
        'modal-close',
        'modal-chat',
        'init'
    ];

    var nonceMeta = document.querySelector('meta[name="apollo-csp-nonce"]');
    var nonce = nonceMeta ? nonceMeta.getAttribute('content') : '';

    /* Load each module sequentially (async=false preserves order) */
    modules.forEach(function (name) {
        var s = document.createElement('script');
        s.src = baseUrl + name + '.js';
        s.async = false;
        if (nonce) {
            s.setAttribute('nonce', nonce);
        }
        document.head.appendChild(s);
    });
})();
