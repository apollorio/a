/**
 * APOLLO::RIO — HOME / fab.js
 * FAB toggle + upward sheet — replaces navbar navigation
 */
;(function () {
    'use strict';

    var menuFab   = document.getElementById('nhMenuFab');
    var menuSheet = document.getElementById('nhMenuSheet');

    if (!menuFab || !menuSheet) return;

    function openSheet() {
        menuFab.classList.add('is-open');
        menuSheet.classList.add('is-open');
        menuFab.setAttribute('aria-expanded', 'true');
    }
    function closeSheet() {
        menuFab.classList.remove('is-open');
        menuSheet.classList.remove('is-open');
        menuFab.setAttribute('aria-expanded', 'false');
    }
    function isOpen() {
        return menuSheet.classList.contains('is-open');
    }

    menuFab.addEventListener('click', function (e) {
        e.stopPropagation();
        isOpen() ? closeSheet() : openSheet();
    });

    /* Click outside closes */
    document.addEventListener('click', function (e) {
        if (!menuFab.contains(e.target) && !menuSheet.contains(e.target)) {
            closeSheet();
        }
    });

    /* ESC closes */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSheet();
    });
})();
