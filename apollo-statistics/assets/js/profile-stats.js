/**
 * Apollo Statistics — Profile Stats (visit graph + period selector).
 *
 * Renders the /id/{username}/stats page: visit timeline chart,
 * stat cards (sessions, time online, engagement score), and period tabs.
 *
 * Depends on: ApolloCharts (admin-charts.js), Apollo CDN (core.js).
 *
 * Reads data from body attributes:
 *   data-a-user    = target user ID
 *   data-rest-url  = REST endpoint URL
 *   data-nonce     = WP REST nonce
 *
 * @package Apollo\Statistics
 * @since   2.0.0
 */
;(function (W, D) {
    'use strict';

    var container = D.getElementById('apollo-profile-stats');
    if (!container) return;

    var body    = D.body;
    var userId  = body.getAttribute('data-a-user') || '';
    var restUrl = body.getAttribute('data-rest-url') || '';
    var nonce   = body.getAttribute('data-nonce') || '';

    if (!restUrl || !userId) return;

    /* ═══════════════════════════════════════════════
       CONFIG
       ═══════════════════════════════════════════════ */

    var PERIODS = [
        { key: '7d',   label: '7d',   days: 7 },
        { key: '30d',  label: '30d',  days: 30 },
        { key: '90d',  label: '90d',  days: 90 },
        { key: '6m',   label: '6m',   days: 180 },
        { key: '1y',   label: '1a',   days: 365 },
        { key: '5y',   label: '5a',   days: 1825 },
        { key: '10y',  label: '10a',  days: 3650 }
    ];

    var ACTIVE_PERIOD = '30d';

    /* ═══════════════════════════════════════════════
       REST FETCH
       ═══════════════════════════════════════════════ */

    function fetchStats(days) {
        var url = restUrl + '?days=' + days;
        return fetch(url, {
            headers: { 'X-WP-Nonce': nonce },
            credentials: 'same-origin'
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        });
    }

    /* ═══════════════════════════════════════════════
       RENDER SCAFFOLD
       ═══════════════════════════════════════════════ */

    function buildScaffold() {
        container.innerHTML = '';

        // Period selector.
        var periodNav = D.createElement('nav');
        periodNav.className = 'apollo-ps-periods';

        PERIODS.forEach(function (p) {
            var btn = D.createElement('button');
            btn.type = 'button';
            btn.className = 'apollo-ps-period-btn' + (p.key === ACTIVE_PERIOD ? ' active' : '');
            btn.textContent = p.label;
            btn.setAttribute('data-period', p.key);
            btn.setAttribute('data-days', String(p.days));
            btn.addEventListener('click', onPeriodClick);
            periodNav.appendChild(btn);
        });

        container.appendChild(periodNav);

        // Visit chart.
        var chartWrap = D.createElement('div');
        chartWrap.className = 'apollo-ps-chart-wrap';
        chartWrap.id = 'apollo-ps-visit-chart';
        chartWrap.style.width = '100%';
        chartWrap.style.minHeight = '280px';
        container.appendChild(chartWrap);

        // Stats cards row.
        var cardsRow = D.createElement('div');
        cardsRow.className = 'apollo-ps-cards';
        cardsRow.id = 'apollo-ps-cards';
        container.appendChild(cardsRow);

        // Widgets grid (additional metrics).
        var widgetsGrid = D.createElement('div');
        widgetsGrid.className = 'apollo-ps-widgets';
        widgetsGrid.id = 'apollo-ps-widgets';
        container.appendChild(widgetsGrid);
    }

    /* ═══════════════════════════════════════════════
       RENDER DATA
       ═══════════════════════════════════════════════ */

    function renderData(data) {
        // 1. Visit timeline chart.
        var chartEl = D.getElementById('apollo-ps-visit-chart');
        if (chartEl && data.visits) {
            if (W.ApolloCharts && W.ApolloCharts.renderTimeSeries) {
                W.ApolloCharts.renderTimeSeries(chartEl, data.visits, 'Visitas');
            } else {
                // Fallback: plain text.
                chartEl.innerHTML = '<pre>' + JSON.stringify(data.visits, null, 2) + '</pre>';
            }
        }

        // 2. Summary cards.
        var cardsEl = D.getElementById('apollo-ps-cards');
        if (cardsEl) {
            cardsEl.innerHTML = '';
            var cards = [
                { icon: 'ri-eye-line',        label: 'Visitas',           value: data.total_visits || 0 },
                { icon: 'ri-time-line',        label: 'Tempo Online',      value: formatDuration(data.total_time || 0) },
                { icon: 'ri-pages-line',       label: 'Páginas',           value: data.total_pages || 0 },
                { icon: 'ri-fire-line',        label: 'Score',             value: data.engagement_score || 0 },
                { icon: 'ri-calendar-line',    label: 'Sessões',           value: data.total_sessions || 0 },
                { icon: 'ri-arrow-up-line',    label: 'Scroll Médio',      value: (data.avg_scroll || 0) + '%' }
            ];

            cards.forEach(function (c) {
                var card = D.createElement('div');
                card.className = 'apollo-ps-card';
                card.innerHTML =
                    '<i class="' + c.icon + '"></i>' +
                    '<span class="apollo-ps-card__value">' + escHtml(String(c.value)) + '</span>' +
                    '<span class="apollo-ps-card__label">' + escHtml(c.label) + '</span>';
                cardsEl.appendChild(card);
            });
        }

        // 3. Additional widgets from API.
        var widgetsEl = D.getElementById('apollo-ps-widgets');
        if (widgetsEl && data.widgets && data.widgets.length) {
            widgetsEl.innerHTML = '';
            data.widgets.forEach(function (w) {
                var widget = D.createElement('div');
                widget.className = 'apollo-ps-widget';

                var header = D.createElement('h3');
                header.innerHTML = '<i class="' + (w.icon || 'ri-bar-chart-2-line') + '"></i> ' + escHtml(w.label || w.slug);
                widget.appendChild(header);

                var chartDiv = D.createElement('div');
                chartDiv.id = 'apollo-ps-w-' + (w.slug || Math.random().toString(36).slice(2));
                chartDiv.className = 'apollo-ps-widget-chart';
                chartDiv.style.width = '100%';
                chartDiv.style.minHeight = '200px';
                widget.appendChild(chartDiv);
                widgetsEl.appendChild(widget);

                // Render via ApolloCharts.
                if (W.ApolloCharts && W.ApolloCharts.renderChart && w.data) {
                    W.ApolloCharts.renderChart(chartDiv, w.data, w.chart_type || 'time_series', { slug: w.slug });
                }
            });
        }
    }

    /* ═══════════════════════════════════════════════
       PERIOD SWITCH
       ═══════════════════════════════════════════════ */

    function onPeriodClick(e) {
        var btn  = e.currentTarget;
        var days = parseInt(btn.getAttribute('data-days'), 10) || 30;

        // Update active state.
        var nav = btn.parentNode;
        nav.querySelectorAll('.apollo-ps-period-btn').forEach(function (b) {
            b.classList.remove('active');
        });
        btn.classList.add('active');

        // Show loading.
        var chartEl = D.getElementById('apollo-ps-visit-chart');
        if (chartEl) chartEl.innerHTML = '<div class="apollo-ps-loading"></div>';

        fetchStats(days)
            .then(renderData)
            .catch(function () {
                if (chartEl) chartEl.innerHTML = '<p class="apollo-ps-error">Erro ao carregar dados.</p>';
            });
    }

    /* ═══════════════════════════════════════════════
       UTILITIES
       ═══════════════════════════════════════════════ */

    function formatDuration(seconds) {
        seconds = Number(seconds) || 0;
        if (seconds < 60) return seconds + 's';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'min';
        var h = Math.floor(seconds / 3600);
        var m = Math.floor((seconds % 3600) / 60);
        return h + 'h ' + (m > 0 ? m + 'min' : '');
    }

    function escHtml(s) {
        var d = D.createElement('div');
        d.appendChild(D.createTextNode(s));
        return d.innerHTML;
    }

    /* ═══════════════════════════════════════════════
       BOOTSTRAP
       ═══════════════════════════════════════════════ */

    function init() {
        buildScaffold();

        // Find initial days.
        var initPeriod = PERIODS.find(function (p) { return p.key === ACTIVE_PERIOD; });
        var days = initPeriod ? initPeriod.days : 30;

        fetchStats(days)
            .then(renderData)
            .catch(function () {
                container.innerHTML = '<p class="apollo-ps-error">Não foi possível carregar as estatísticas.</p>';
            });
    }

    if (D.readyState === 'loading') {
        D.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window, document);
