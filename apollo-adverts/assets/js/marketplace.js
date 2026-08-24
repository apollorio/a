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

    /* Resolve base URL from this <script> tag */
    var scripts = document.getElementsByTagName('script');
    var current = scripts[scripts.length - 1];
    var baseUrl = current.src.replace(/marketplace\.js(\?.*)?$/, 'marketplace/');

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

    /* Load each module sequentially (async=false preserves order) */
    modules.forEach(function (name) {
        var s = document.createElement('script');
        s.src = baseUrl + name + '.js';
        s.async = false;
        document.head.appendChild(s);
    });
})();
