/* ═══════════════════════════════════════════════════════════════
   gestor.chart-periods.js — Chart Period Tab Switching
   (6M / 3M / 1M pills on chart cards)
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorChartPeriods = {
        init: function () {
            G.$$('.chart-periods').forEach(function (wrap) {
                G.$$('.chart-period', wrap).forEach(function (p) {
                    G.on(p, 'click', function () {
                        G.$$('.chart-period', wrap).forEach(function (x) {
                            x.classList.remove('on');
                        });
                        p.classList.add('on');
                        G.toast('Período: ' + p.textContent);
                    });
                });
            });
        }
    };
})();
