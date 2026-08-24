/* ═══════════════════════════════════════════════════════════════
   gestor.calculator.js — Cachê Calculator Overlay
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorCalculator = {
        init: function () {
            var overlay  = G.$('#calcOverlay');
            if (!overlay) return;
            var openBtn  = G.$('#calcOpen');
            var closeBtn = G.$('#calcClose');

            G.on(openBtn, 'click', function () { overlay.classList.add('on'); });
            G.on(closeBtn, 'click', function () { overlay.classList.remove('on'); });
            G.on(overlay, 'click', function (e) {
                if (e.target === overlay) overlay.classList.remove('on');
            });

            var compute = function () {
                var hrs    = parseFloat((G.$('#cHrs')    || {}).value) || 0;
                var rate   = parseFloat((G.$('#cRate')   || {}).value) || 0;
                var extras = parseFloat((G.$('#cExtras') || {}).value) || 0;
                var disc   = parseFloat((G.$('#cDisc')   || {}).value) || 0;
                var subtotal = (hrs * rate) + extras;
                var total    = subtotal - (subtotal * disc / 100);
                var result   = G.$('#cResult');
                if (result) result.textContent = G.formatCurrency(total);
            };

            G.$$('#calcOverlay input').forEach(function (inp) {
                G.on(inp, 'input', compute);
            });

            G.on(G.$('#calcClear'), 'click', function () {
                G.$$('#calcOverlay input').forEach(function (i) { i.value = ''; });
                var result = G.$('#cResult');
                if (result) result.textContent = 'R$ 0,00';
            });

            G.on(G.$('#calcAdd'), 'click', function () {
                var val = (G.$('#cResult') || {}).textContent || 'R$ 0,00';
                G.toast('Cachê ' + val + ' adicionado', 'success');
                overlay.classList.remove('on');
            });
        }
    };
})();
