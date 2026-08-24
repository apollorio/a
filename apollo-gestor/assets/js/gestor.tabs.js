/* ═══════════════════════════════════════════════════════════════
   gestor.tabs.js — Tab System Controller
   Manages panel switching with animated transitions
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorTabs = {
        init: function () {
            var tabs   = G.$$('.tab');
            var panels = G.$$('.panel');

            tabs.forEach(function (tab) {
                G.on(tab, 'click', function () {
                    var target = tab.dataset.tab;

                    tabs.forEach(function (t) { t.classList.remove('on'); });
                    tab.classList.add('on');

                    panels.forEach(function (p) {
                        p.classList.toggle('on', p.id === target);
                    });

                    /* Resize Gantt when switching to cronograma */
                    if (target === 'panel-cronograma' && window._ganttRoot) {
                        window._ganttRoot.resize();
                    }
                    /* Re-init charts when going back to overview */
                    if (target === 'panel-overview' && window.GestorCharts) {
                        window.GestorCharts.init();
                    }
                });
            });
        },

        /** Open a specific tab by index */
        openByIndex: function (idx) {
            var tab = G.$$('.tab')[idx];
            if (tab) tab.click();
        }
    };

    /** Global helper for FAB menu */
    window.openTab = function (name) {
        /* Resolve aliases to panel IDs */
        var aliases = {
            'overview':     'panel-overview',
            'kanban':       'panel-kanban',
            'team':         'panel-equipe',
            'budget':       'panel-budget',
            'finance':      'panel-financeiro',
            'ctrl-financeiro': 'panel-ctrl-financeiro',
            'supplier':     'panel-fornecedores',
            'gantt':        'panel-cronograma',
            'doc':          'panel-doc',
            'sign':         'panel-assina',
            'assina':       'panel-assina',
            'mural':        'panel-mural'
        };
        var target = aliases[name] || name;
        var tab = document.querySelector('.tab[data-tab="' + target + '"]');
        if (tab) tab.click();
    };
})();
