/* ═══════════════════════════════════════════════════════════════
   gestor.fab.js — FAB Menu (Mobile-First Core Navigation)
   Bottom-right floating action button + slide-up sheet
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorFab = {
        init: function () {
            var fab   = G.$('#nhMenuFab');
            var sheet = G.$('#nhMenuSheet');
            if (!fab || !sheet) return;

            var items = G.$$('.nh-sheet-item', sheet);

            var open = function () {
                fab.classList.add('is-open');
                sheet.classList.add('is-open');
                fab.setAttribute('aria-expanded', 'true');
            };

            var close = function () {
                fab.classList.remove('is-open');
                sheet.classList.remove('is-open');
                fab.setAttribute('aria-expanded', 'false');
            };

            G.on(fab, 'click', function (e) {
                e.stopPropagation();
                sheet.classList.contains('is-open') ? close() : open();
            });

            G.on(document, 'click', function (e) {
                if (!fab.contains(e.target) && !sheet.contains(e.target)) close();
            });

            G.on(document, 'keydown', function (e) {
                if (e.key === 'Escape') close();
            });

            items.forEach(function (item) {
                G.on(item, 'click', function () { close(); });
            });
        }
    };
})();
