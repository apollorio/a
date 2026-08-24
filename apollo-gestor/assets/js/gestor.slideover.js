/* ═══════════════════════════════════════════════════════════════
   gestor.slideover.js — Slide-Over Panel (Event Details)
   Opens from Kanban cards via double-click
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorSlideOver = {
        init: function () {
            var so = G.$('#so');
            var bd = G.$('#soBd');
            if (!so) return;

            var close = function () {
                so.classList.remove('open');
                if (bd) bd.classList.remove('open');
            };

            G.on(G.$('.so-close', so), 'click', close);
            G.on(bd, 'click', close);
            G.on(document, 'keydown', function (e) {
                if (e.key === 'Escape') close();
            });
        },

        openFromCard: function (card) {
            var so = G.$('#so');
            var bd = G.$('#soBd');
            if (!so) return;

            so.classList.add('open');
            if (bd) bd.classList.add('open');

            var title = G.$('.ev-card-title', card);
            var loc   = G.$('.ev-card-loc', card);
            var id    = G.$('.ev-card-id', card);

            var soTitle = G.$('.so-title', so);
            var soId    = G.$('.so-header-id', so);
            var soLoc   = G.$('.so-loc', so);

            if (soTitle && title) soTitle.textContent = title.textContent;
            if (soId && id)       soId.textContent    = id.textContent;
            if (soLoc && loc)     soLoc.textContent   = loc.textContent;
        }
    };
})();
