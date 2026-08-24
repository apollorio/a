/* ═══════════════════════════════════════════════════════════════
   gestor.loader.js — Splash screen loader animation
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorLoader = {
        init: function () {
            var l = G.$('.loader');
            if (!l) return;
            setTimeout(function () {
                l.classList.add('out');
                setTimeout(function () { l.remove(); }, 600);
            }, 900);
        }
    };
})();
