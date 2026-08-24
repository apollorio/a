/* ═══════════════════════════════════════════════════════════════
   gestor.data.js — Data Loader / Central Store (WordPress AJAX)
   Fetches all data via wp_ajax handlers, populates window.GestorStore
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var Store = window.GestorStore = {
        events:      null,
        team:        null,
        budget:      null,
        suppliers:   null,
        gantt:       null,
        milestones:  null,
        activity:    null,
        financeiro:  null,
        commands:    null,
        charts:      null,
        _ready:      false,
        _callbacks:  [],
        _eventId:    null
    };

    /**
     * Register a callback to run once data is loaded
     * @param {Function} fn
     */
    Store.onReady = function (fn) {
        if (Store._ready) { fn(Store); return; }
        Store._callbacks.push(fn);
    };

    /**
     * Load initial data (events list + overview)
     */
    function loadAll() {
        return Promise.all([
            G.ajax('load_events'),
            G.ajax('load_overview')
        ]).then(function (results) {
            Store.events = (results[0] && results[0].success) ? results[0].data : [];
            Store.charts = (results[1] && results[1].success) ? results[1].data : {};
            Store._ready = true;

            Store._callbacks.forEach(function (fn) { fn(Store); });
            Store._callbacks = [];
        });
    }

    /**
     * Load data for a specific event
     * @param {number} eventId
     * @returns {Promise}
     */
    Store.loadEvent = function (eventId) {
        Store._eventId = eventId;

        return Promise.all([
            G.ajax('load_tasks',      { event_id: eventId }),
            G.ajax('load_team',       { event_id: eventId }),
            G.ajax('load_budget',     { event_id: eventId }),
            G.ajax('load_payments',   { event_id: eventId }),
            G.ajax('load_milestones', { event_id: eventId }),
            G.ajax('load_activity',   { event_id: eventId }),
            G.ajax('load_suppliers',  { event_id: eventId })
        ]).then(function (results) {
            Store.tasks      = (results[0] && results[0].success) ? results[0].data : {};
            Store.team       = (results[1] && results[1].success) ? results[1].data : [];
            Store.budget     = (results[2] && results[2].success) ? results[2].data : {};
            Store.financeiro = (results[3] && results[3].success) ? results[3].data : {};
            Store.milestones = (results[4] && results[4].success) ? results[4].data : [];
            Store.activity   = (results[5] && results[5].success) ? results[5].data : [];
            Store.suppliers  = (results[6] && results[6].success) ? results[6].data : [];

            /* Build static commands list (cmd palette) */
            Store.commands = {
                commands: [
                    { icon: 'ri-add-line',              label: 'Novo Projeto',          action: 'newEvent' },
                    { icon: 'ri-kanban-view-2',         label: 'Kanban',                action: 'tab:panel-kanban' },
                    { icon: 'ri-team-line',             label: 'Equipe',                action: 'tab:panel-equipe' },
                    { icon: 'ri-money-dollar-box-line', label: 'Budget',                action: 'tab:panel-budget' },
                    { icon: 'ri-bank-card-line',        label: 'Financeiro',            action: 'tab:panel-financeiro' },
                    { icon: 'ri-pie-chart-2-line',      label: 'Controle Financeiro',   action: 'tab:panel-ctrl-financeiro' },
                    { icon: 'ri-store-2-line',          label: 'Fornecedores',          action: 'tab:panel-fornecedores' },
                    { icon: 'ri-bar-chart-grouped-line',label: 'Cronograma',            action: 'tab:panel-cronograma' },
                    { icon: 'ri-book-read-fill',        label: 'DOC',                   action: 'tab:panel-doc' },
                    { icon: 'ri-shield-keyhole-fill',   label: 'Assina::rio',           action: 'tab:panel-assina' },
                    { icon: 'ri-chat-quote-line',       label: 'Mural',                 action: 'tab:panel-mural' },
                    { icon: 'ri-calculator-line',       label: 'Calculadora de Cachê',  action: 'calculator' }
                ]
            };
        });
    };

    /**
     * Refresh a specific store section
     * @param {string} section
     */
    Store.refresh = function (section) {
        var id = Store._eventId;
        if (!id) return Promise.resolve();

        var actions = {
            tasks:      function () { return G.ajax('load_tasks',    { event_id: id }); },
            team:       function () { return G.ajax('load_team',     { event_id: id }); },
            budget:     function () { return G.ajax('load_budget',   { event_id: id }); },
            financeiro: function () { return G.ajax('load_payments', { event_id: id }); },
            milestones: function () { return G.ajax('load_milestones', { event_id: id }); },
            activity:   function () { return G.ajax('load_activity', { event_id: id }); },
            suppliers:  function () { return G.ajax('load_suppliers', { event_id: id }); }
        };

        if (!actions[section]) return Promise.resolve();

        return actions[section]().then(function (res) {
            if (res && res.success) {
                Store[section] = res.data;
            }
        });
    };

    /* Auto-load events on include */
    loadAll().catch(function (err) {
        console.error('[GestorData]', err);
    });

})();
