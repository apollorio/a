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
     * Soft-settle one AJAX unit — never rejects the fan-out.
     * @param {string} action
     * @param {object=} data
     * @returns {Promise<{status:string, value?:*, reason?:*}>}
     */
    function settleAjax(action, data) {
        return Promise.resolve()
            .then(function () { return G.ajax(action, data || {}); })
            .then(function (res) {
                return { status: 'fulfilled', value: res };
            })
            .catch(function (err) {
                console.warn('[GestorData] unit failed:', action, err);
                return { status: 'rejected', reason: err };
            });
    }

    function pickData(settled, fallback) {
        if (settled && settled.status === 'fulfilled' && settled.value && settled.value.success) {
            return settled.value.data;
        }
        return fallback;
    }

    /**
     * Load initial data (events list + overview) — isolated parallel.
     */
    function loadAll() {
        return Promise.all([
            settleAjax('load_events'),
            settleAjax('load_overview')
        ]).then(function (results) {
            Store.events = pickData(results[0], []);
            Store.charts = pickData(results[1], {});
            Store._ready = true;
            Store._statuses = {
                load_events: results[0].status,
                load_overview: results[1].status
            };

            Store._callbacks.forEach(function (fn) { fn(Store); });
            Store._callbacks = [];
            return Store;
        });
    }

    /**
     * Load data for a specific event — allSettled fan-out (one dead AJAX
     * must not wipe sibling panels).
     * @param {number} eventId
     * @returns {Promise}
     */
    Store.loadEvent = function (eventId) {
        Store._eventId = eventId;

        var units = [
            ['load_tasks', 'tasks', {}],
            ['load_team', 'team', []],
            ['load_budget', 'budget', {}],
            ['load_payments', 'financeiro', {}],
            ['load_milestones', 'milestones', []],
            ['load_activity', 'activity', []],
            ['load_suppliers', 'suppliers', []]
        ];

        return Promise.all(
            units.map(function (u) {
                return settleAjax(u[0], { event_id: eventId }).then(function (s) {
                    return { key: u[1], fallback: u[2], settled: s, action: u[0] };
                });
            })
        ).then(function (rows) {
            Store._statuses = Store._statuses || {};
            rows.forEach(function (row) {
                Store[row.key] = pickData(row.settled, row.fallback);
                Store._statuses[row.action] = row.settled.status;
            });

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
            return Store;
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
