/* ═══════════════════════════════════════════════════════════════
   gestor.permissions.js — Permission Modal Controller
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorPermissions = {
        init: function () {
            var overlay = G.$('#permModal');
            if (!overlay) return;

            G.on(G.$('#permOpen'), 'click', function () {
                overlay.classList.add('on');
            });

            G.on(G.$('#permClose'), 'click', function () {
                overlay.classList.remove('on');
            });

            G.on(overlay, 'click', function (e) {
                if (e.target === overlay) overlay.classList.remove('on');
            });
        }
    };
})();
