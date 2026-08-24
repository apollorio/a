/* ═══════════════════════════════════════════════════════════════
   gestor.notifications.js — Notification Bell Panel
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorNotifications = {
        init: function () {
            var btn   = G.$('#notifBtn');
            var panel = G.$('#notifPanel');
            if (!btn || !panel) return;

            G.on(btn, 'click', function (e) {
                e.stopPropagation();
                panel.classList.toggle('open');
            });

            G.on(document, 'click', function (e) {
                if (!panel.contains(e.target)) {
                    panel.classList.remove('open');
                }
            });
        }
    };
})();
