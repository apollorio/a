/* ═══════════════════════════════════════════════════════════════
   gestor.modals.js — New Event Modal + Generic Escape Handler
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorModals = {
        init: function () {
            /* New Event button → modal */
            G.on(G.$('#newEventBtn'), 'click', function () {
                var modal = G.$('#newEventModal');
                if (modal) modal.classList.add('on');
            });

            G.on(G.$('#newEventClose'), 'click', function () {
                var modal = G.$('#newEventModal');
                if (modal) modal.classList.remove('on');
            });

            /* Global Escape → close all overlays */
            G.on(document, 'keydown', function (e) {
                if (e.key === 'Escape') {
                    G.$$('.modal-overlay.on, .calc-overlay.on, #cmdPalette.on').forEach(function (m) {
                        m.classList.remove('on');
                    });
                }
            });
        }
    };
})();
