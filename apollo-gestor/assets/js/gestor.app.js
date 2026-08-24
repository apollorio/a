/* ═══════════════════════════════════════════════════════════════
   gestor.app.js — Boot Orchestrator
   Loads LAST. Initializes all modules in correct order.
   Depends on: G (helpers), GestorStore (data), all gestor.*.js
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        /* Phase 1 — Immediate UI (no data dependency) */
        if (window.GestorLoader)        GestorLoader.init();
        if (window.GestorTabs)          GestorTabs.init();
        if (window.GestorEventSelector) GestorEventSelector.init();
        if (window.GestorKanban)        GestorKanban.init();
        if (window.GestorSlideOver)     GestorSlideOver.init();
        if (window.GestorChecklist)     GestorChecklist.init();
        if (window.GestorBudget)        GestorBudget.init();
        if (window.GestorFinanceCtrl)   GestorFinanceCtrl.init();
        if (window.GestorCalculator)    GestorCalculator.init();
        if (window.GestorNotifications) GestorNotifications.init();
        if (window.GestorPermissions)   GestorPermissions.init();
        if (window.GestorModals)        GestorModals.init();
        if (window.GestorChartPeriods)  GestorChartPeriods.init();
        if (window.GestorFab)           GestorFab.init();

        /* Phase 2 — Data-dependent + Heavy (delayed) */
        setTimeout(function () {
            if (window.GestorCmdPalette) GestorCmdPalette.init();
            if (window.GestorGantt)      GestorGantt.init();
            if (window.GestorCharts)     GestorCharts.init();
            if (window.GestorAnimations) GestorAnimations.init();
        }, 1000);
    });

})();
