/**
 * Apollo Statistics — Admin Charts Engine (amCharts 5)
 *
 * ┌─────────────────── ARCHITECTURE ───────────────────┐
 * │  10 chart renderers covering ALL 68 MetricGroups   │
 * │  Auto-init via data-apollo-chart + data-chart-type │
 * │  Inline payload via data-chart-payload OR REST     │
 * │  Apollo theme: coral/amber/grayscale               │
 * │  Base engine for GESTOR, Dashboard, Profile        │
 * └────────────────────────────────────────────────────┘
 *
 * Renderers:
 *   1. time_series   — Line/area with date axis (ViewCounter, TimeSeries, Growth, Radio)
 *   2. stacked_area  — Multi-series stacked area (comparisons over time)
 *   3. donut         — Pie chart inner radius 50% (Distribution)
 *   4. bar           — Horizontal bar (Ranking, Leaderboard)
 *   5. grouped_bar   — Grouped vertical bars (Comparison: item vs average)
 *   6. funnel        — Sliced funnel chart (RSVP, Registration, Doc lifecycle)
 *   7. number        — Big number card with delta arrow (EngagementScore)
 *   8. sparkline     — Tiny inline chart without axes (dashboard cards)
 *   9. table         — HTML table renderer (Leaderboard, ClickTrack)
 *  10. radar         — Radar/polar chart (multi-dimension scores)
 *
 * Global: window.ApolloCharts
 *
 * Config via `apolloStats` global:
 *   apolloStats.restUrl  = '/wp-json/apollo/v1/stats/'
 *   apolloStats.nonce    = WP REST nonce
 *   apolloStats.am5Root  = CDN path to amCharts 5
 *
 * @package Apollo\Statistics
 * @since   2.0.0
 */
;(function (W, D) {
    'use strict';

    var CFG     = W.apolloStats || {};
    var API_URL = CFG.restUrl || '/wp-json/apollo/v1/stats/';
    var NONCE   = CFG.nonce || '';

    // Track root instances for cleanup.
    var roots = {};

    /* ═══════════════════════════════════════════════
       PALETTE — Apollo design tokens
       ═══════════════════════════════════════════════ */

    var P = {
        coral:   0xF95006,
        amber:   0xFFBF00,
        sunset1: 0xFF7A33,
        sunset2: 0xFFA066,
        gray1:   0x222222,
        gray2:   0x444444,
        gray3:   0x777777,
        gray4:   0xAAAAAA,
        gray5:   0xDDDDDD,
        white:   0xFFFFFF,
        black:   0x000000,
        success: 0x22C55E,
        danger:  0xEF4444,
    };

    // Chart palette rotation (coral-first, then sunset variations + grays).
    var SERIES_COLORS = [
        P.coral, P.amber, P.sunset1, P.gray2, P.sunset2,
        P.gray3, P.gray4, P.success, P.danger, P.gray1
    ];

    /* ═══════════════════════════════════════════════
       REST HELPER
       ═══════════════════════════════════════════════ */

    function apiGet(endpoint, params) {
        var url = new URL(API_URL + endpoint, W.location.origin);
        if (params) {
            Object.keys(params).forEach(function (k) {
                url.searchParams.set(k, String(params[k]));
            });
        }
        return fetch(url.toString(), {
            method: 'GET',
            headers: { 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        }).then(function (r) {
            if (!r.ok) throw new Error('API ' + r.status);
            return r.json();
        });
    }

    /* ═══════════════════════════════════════════════
       amCharts 5 — Apollo Theme
       ═══════════════════════════════════════════════ */

    function am5ok() { return !!W.am5; }
    function am5xyOk() { return !!(W.am5 && W.am5xy); }
    function am5pctOk() { return !!(W.am5 && W.am5percent); }

    function applyTheme(root) {
        if (!root || !am5ok()) return;
        var am5 = W.am5;
        var theme = am5.Theme.new(root);

        theme.rule('ColorSet').setAll({
            colors: SERIES_COLORS.map(function (c) { return am5.color(c); }),
            step: 1
        });

        theme.rule('Label').setAll({
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            fontSize: 12,
            fill: am5.color(P.gray2)
        });

        theme.rule('Grid').setAll({
            stroke: am5.color(P.gray5),
            strokeOpacity: 0.6
        });

        var themes = [theme];
        if (W.am5 && W.am5.themes && W.am5.themes.Animated) {
            themes.unshift(am5.themes.Animated.new(root));
        }
        root.setThemes(themes);
    }

    /**
     * Create or reuse an am5 Root on a container.
     */
    function getRoot(el) {
        var id = el.id;
        if (roots[id]) {
            roots[id].dispose();
        }
        var root = W.am5.Root.new(el);
        applyTheme(root);
        roots[id] = root;
        return root;
    }

    /* ═══════════════════════════════════════════════
       1. TIME SERIES — Line/Area
       ═══════════════════════════════════════════════ */

    function renderTimeSeries(el, data, label, opts) {
        if (!am5xyOk()) return;
        opts = opts || {};
        var am5 = W.am5, xy = W.am5xy;
        var root = getRoot(el);

        var chart = root.container.children.push(
            xy.XYChart.new(root, {
                panX: true, panY: false, wheelX: 'panX', wheelY: 'zoomX',
                layout: root.verticalLayout, paddingLeft: 0, paddingRight: 0
            })
        );

        var xAxis = chart.xAxes.push(
            xy.DateAxis.new(root, {
                baseInterval: { timeUnit: opts.timeUnit || 'day', count: 1 },
                renderer: xy.AxisRendererX.new(root, { minGridDistance: 60 }),
                tooltip: am5.Tooltip.new(root, {})
            })
        );

        var yAxis = chart.yAxes.push(
            xy.ValueAxis.new(root, {
                min: 0,
                renderer: xy.AxisRendererY.new(root, {})
            })
        );

        // Support multi-series: data can be [{date,value}] or {series1:[],series2:[]}
        var seriesData = Array.isArray(data) ? { 'Total': data } : data;
        var idx = 0;

        Object.keys(seriesData).forEach(function (name) {
            var color = am5.color(SERIES_COLORS[idx % SERIES_COLORS.length]);
            var s = chart.series.push(
                xy.LineSeries.new(root, {
                    name: name, xAxis: xAxis, yAxis: yAxis,
                    valueYField: 'value', valueXField: 'date',
                    tooltip: am5.Tooltip.new(root, {
                        labelText: name + ': {valueY}'
                    }),
                    fill: color, stroke: color
                })
            );

            s.fills.template.setAll({ visible: true, fillOpacity: 0.12 });
            s.strokes.template.setAll({ strokeWidth: 2 });

            var parsed = (seriesData[name] || []).map(function (d) {
                return { date: new Date(d.date).getTime(), value: Number(d.value) || 0 };
            });
            s.data.setAll(parsed);
            s.appear(700 + idx * 200);
            idx++;
        });

        // Cursor + scrollbar.
        chart.set('cursor', xy.XYCursor.new(root, { behavior: 'zoomX' }));
        if (opts.scrollbar !== false) {
            chart.set('scrollbarX', am5.Scrollbar.new(root, { orientation: 'horizontal' }));
        }

        // Legend for multi-series.
        if (idx > 1) {
            var legend = chart.children.push(am5.Legend.new(root, {
                centerX: am5.percent(50), x: am5.percent(50)
            }));
            legend.data.setAll(chart.series.values);
        }

        chart.appear(800, 100);
        return chart;
    }

    /* ═══════════════════════════════════════════════
       2. STACKED AREA — Multi-series
       ═══════════════════════════════════════════════ */

    function renderStackedArea(el, seriesMap, opts) {
        if (!am5xyOk()) return;
        opts = opts || {};
        var am5 = W.am5, xy = W.am5xy;
        var root = getRoot(el);

        var chart = root.container.children.push(
            xy.XYChart.new(root, {
                panX: true, panY: false, wheelX: 'panX', wheelY: 'zoomX',
                layout: root.verticalLayout
            })
        );

        var xAxis = chart.xAxes.push(
            xy.DateAxis.new(root, {
                baseInterval: { timeUnit: 'day', count: 1 },
                renderer: xy.AxisRendererX.new(root, { minGridDistance: 60 }),
                tooltip: am5.Tooltip.new(root, {})
            })
        );

        var yAxis = chart.yAxes.push(
            xy.ValueAxis.new(root, {
                min: 0,
                renderer: xy.AxisRendererY.new(root, {})
            })
        );

        var idx = 0;
        Object.keys(seriesMap).forEach(function (name) {
            var color = am5.color(SERIES_COLORS[idx % SERIES_COLORS.length]);
            var s = chart.series.push(
                xy.LineSeries.new(root, {
                    name: name, xAxis: xAxis, yAxis: yAxis,
                    valueYField: 'value', valueXField: 'date',
                    stacked: true,
                    tooltip: am5.Tooltip.new(root, { labelText: name + ': {valueY}' }),
                    fill: color, stroke: color
                })
            );
            s.fills.template.setAll({ visible: true, fillOpacity: 0.5 });
            s.strokes.template.setAll({ strokeWidth: 1.5 });

            var parsed = (seriesMap[name] || []).map(function (d) {
                return { date: new Date(d.date).getTime(), value: Number(d.value) || 0 };
            });
            s.data.setAll(parsed);
            s.appear(700 + idx * 200);
            idx++;
        });

        chart.set('cursor', xy.XYCursor.new(root, { behavior: 'zoomX' }));
        var legend = chart.children.push(am5.Legend.new(root, {
            centerX: am5.percent(50), x: am5.percent(50)
        }));
        legend.data.setAll(chart.series.values);
        chart.appear(800, 100);
        return chart;
    }

    /* ═══════════════════════════════════════════════
       3. DONUT — Distribution / Pie
       ═══════════════════════════════════════════════ */

    function renderDonut(el, data, opts) {
        if (!am5pctOk()) return;
        opts = opts || {};
        var am5 = W.am5, pct = W.am5percent;
        var root = getRoot(el);

        var chart = root.container.children.push(
            pct.PieChart.new(root, {
                innerRadius: am5.percent(opts.innerRadius || 50),
                layout: root.verticalLayout
            })
        );

        var series = chart.series.push(
            pct.PieSeries.new(root, {
                valueField: 'value',
                categoryField: 'label',
                tooltip: am5.Tooltip.new(root, { labelText: '{category}: {value} ({valuePercentTotal.formatNumber("0.0")}%)' })
            })
        );

        series.labels.template.setAll({ fontSize: 11, text: '{category}', maxWidth: 100, oversizedBehavior: 'truncate' });
        series.ticks.template.setAll({ visible: true, strokeOpacity: 0.4 });
        series.slices.template.setAll({ cornerRadius: 4, strokeWidth: 1, stroke: am5.color(P.white) });

        series.data.setAll(data || []);

        var legend = chart.children.push(am5.Legend.new(root, {
            centerX: am5.percent(50), x: am5.percent(50),
            layout: root.horizontalLayout,
            marginTop: 10
        }));
        legend.data.setAll(series.dataItems);

        series.appear(800);
        chart.appear(800, 100);
        return chart;
    }

    /* ═══════════════════════════════════════════════
       4. HORIZONTAL BAR — Rankings
       ═══════════════════════════════════════════════ */

    function renderBarH(el, data, opts) {
        if (!am5xyOk()) return;
        opts = opts || {};
        var am5 = W.am5, xy = W.am5xy;
        var root = getRoot(el);

        var chart = root.container.children.push(
            xy.XYChart.new(root, {
                panX: false, panY: false, wheelX: 'none', wheelY: 'none',
                layout: root.verticalLayout
            })
        );

        var yAxis = chart.yAxes.push(
            xy.CategoryAxis.new(root, {
                categoryField: 'label',
                renderer: xy.AxisRendererY.new(root, { inversed: true, minGridDistance: 20 })
            })
        );

        var xAxis = chart.xAxes.push(
            xy.ValueAxis.new(root, {
                min: 0,
                renderer: xy.AxisRendererX.new(root, {})
            })
        );

        var series = chart.series.push(
            xy.ColumnSeries.new(root, {
                name: 'Value', xAxis: xAxis, yAxis: yAxis,
                valueXField: 'value', categoryYField: 'label',
                tooltip: am5.Tooltip.new(root, { labelText: '{categoryY}: {valueX}' })
            })
        );

        series.columns.template.setAll({
            cornerRadiusTR: 4, cornerRadiusBR: 4,
            height: am5.percent(70),
            fill: am5.color(opts.color || P.coral),
            stroke: am5.color(opts.color || P.coral)
        });

        // Gradient fill per rank.
        series.columns.template.adapters.add('fill', function (fill, target) {
            var idx = target.dataItem ? target.dataItem.get('index') : 0;
            return am5.color(SERIES_COLORS[idx % SERIES_COLORS.length]);
        });
        series.columns.template.adapters.add('stroke', function (stroke, target) {
            var idx = target.dataItem ? target.dataItem.get('index') : 0;
            return am5.color(SERIES_COLORS[idx % SERIES_COLORS.length]);
        });

        yAxis.data.setAll(data || []);
        series.data.setAll(data || []);

        series.appear(800);
        chart.appear(800, 100);
        return chart;
    }

    /* ═══════════════════════════════════════════════
       5. GROUPED BAR — Comparison (item vs average)
       ═══════════════════════════════════════════════ */

    function renderGroupedBar(el, data, opts) {
        if (!am5xyOk()) return;
        opts = opts || {};
        var am5 = W.am5, xy = W.am5xy;
        var root = getRoot(el);

        // data: { labels: ['Metric1','Metric2'], series: { 'Item': [10,20], 'Average': [15,18] } }
        // OR simple: { item_value: 42, average: 30, item_label: 'My Hub' }
        var chartData, seriesNames;

        if (data.labels && data.series) {
            chartData = data.labels.map(function (label, i) {
                var row = { category: label };
                Object.keys(data.series).forEach(function (name) {
                    row[name] = data.series[name][i] || 0;
                });
                return row;
            });
            seriesNames = Object.keys(data.series);
        } else {
            // Simple comparison.
            chartData = [
                { category: data.item_label || 'Item', value: data.item_value || 0 },
                { category: 'Average', value: data.average || 0 }
            ];
            seriesNames = ['value'];
        }

        var chart = root.container.children.push(
            xy.XYChart.new(root, {
                panX: false, panY: false, layout: root.verticalLayout
            })
        );

        var xAxis = chart.xAxes.push(
            xy.CategoryAxis.new(root, {
                categoryField: 'category',
                renderer: xy.AxisRendererX.new(root, { minGridDistance: 40 })
            })
        );

        var yAxis = chart.yAxes.push(
            xy.ValueAxis.new(root, {
                min: 0,
                renderer: xy.AxisRendererY.new(root, {})
            })
        );

        xAxis.data.setAll(chartData);

        seriesNames.forEach(function (name, idx) {
            var color = am5.color(SERIES_COLORS[idx % SERIES_COLORS.length]);
            var s = chart.series.push(
                xy.ColumnSeries.new(root, {
                    name: name, xAxis: xAxis, yAxis: yAxis,
                    valueYField: name, categoryXField: 'category',
                    clustered: true,
                    tooltip: am5.Tooltip.new(root, { labelText: '{name}: {valueY}' })
                })
            );
            s.columns.template.setAll({
                cornerRadiusTL: 4, cornerRadiusTR: 4, width: am5.percent(80),
                fill: color, stroke: color
            });
            s.data.setAll(chartData);
            s.appear(700 + idx * 200);
        });

        if (seriesNames.length > 1) {
            var legend = chart.children.push(am5.Legend.new(root, {
                centerX: am5.percent(50), x: am5.percent(50)
            }));
            legend.data.setAll(chart.series.values);
        }

        chart.appear(800, 100);
        return chart;
    }

    /* ═══════════════════════════════════════════════
       6. FUNNEL — Stage conversion
       ═══════════════════════════════════════════════ */

    function renderFunnel(el, data, opts) {
        if (!am5xyOk()) return;
        opts = opts || {};
        var am5 = W.am5, xy = W.am5xy;
        var root = getRoot(el);

        // Funnel rendered as stepped horizontal bars (since am5 sliced charts
        // need am5percent which might not include funnel — use bar fallback).
        var stages = data.stages || data;
        if (!stages || !stages.length) return;

        var chart = root.container.children.push(
            xy.XYChart.new(root, {
                panX: false, panY: false, layout: root.verticalLayout
            })
        );

        var yAxis = chart.yAxes.push(
            xy.CategoryAxis.new(root, {
                categoryField: 'stage',
                renderer: xy.AxisRendererY.new(root, { inversed: true, minGridDistance: 24 })
            })
        );

        var xAxis = chart.xAxes.push(
            xy.ValueAxis.new(root, {
                min: 0,
                renderer: xy.AxisRendererX.new(root, {})
            })
        );

        var series = chart.series.push(
            xy.ColumnSeries.new(root, {
                name: 'Count', xAxis: xAxis, yAxis: yAxis,
                valueXField: 'count', categoryYField: 'stage',
                tooltip: am5.Tooltip.new(root, {
                    labelText: '{categoryY}: {valueX}'
                })
            })
        );

        series.columns.template.setAll({
            cornerRadiusTR: 4, cornerRadiusBR: 4,
            height: am5.percent(70)
        });

        // Gradient from coral (top) to gray (bottom).
        series.columns.template.adapters.add('fill', function (fill, target) {
            var i = target.dataItem ? target.dataItem.get('index') : 0;
            var total = stages.length || 1;
            var ratio = i / total;
            // Blend from coral orange → gray.
            return am5.Color.interpolate(ratio, am5.color(P.coral), am5.color(P.gray4));
        });
        series.columns.template.adapters.add('stroke', function (s, target) {
            var i = target.dataItem ? target.dataItem.get('index') : 0;
            var total = stages.length || 1;
            return am5.Color.interpolate(i / total, am5.color(P.coral), am5.color(P.gray4));
        });

        // Add conversion rate labels.
        series.bullets.push(function () {
            return am5.Bullet.new(root, {
                locationX: 1,
                sprite: am5.Label.new(root, {
                    text: '{valueX}',
                    fill: am5.color(P.gray2),
                    fontSize: 11,
                    centerY: am5.percent(50),
                    paddingLeft: 8
                })
            });
        });

        yAxis.data.setAll(stages);
        series.data.setAll(stages);

        series.appear(800);
        chart.appear(800, 100);
        return chart;
    }

    /* ═══════════════════════════════════════════════
       7. NUMBER CARD — Big value + delta
       ═══════════════════════════════════════════════ */

    function renderNumber(el, data) {
        var value = data.value || data.total || 0;
        var delta = data.delta || 0;
        var label = data.label || '';
        var cls   = delta > 0 ? 'positive' : (delta < 0 ? 'negative' : 'neutral');
        var arrow = delta > 0 ? '&#x2191;' : (delta < 0 ? '&#x2193;' : '');

        el.innerHTML = '<div class="apollo-number-chart">' +
            '<span class="apollo-number-chart__value">' + formatNum(value) + '</span>' +
            (delta !== 0 ? '<span class="apollo-number-chart__delta apollo-number-chart__delta--' + cls + '">' +
                arrow + ' ' + Math.abs(delta).toFixed(1) + '%' + '</span>' : '') +
            (label ? '<span class="apollo-number-chart__label">' + escHtml(label) + '</span>' : '') +
            '</div>';
    }

    /* ═══════════════════════════════════════════════
       8. SPARKLINE — Tiny inline chart
       ═══════════════════════════════════════════════ */

    function renderSparkline(el, data, opts) {
        if (!am5xyOk()) return;
        opts = opts || {};
        var am5 = W.am5, xy = W.am5xy;

        el.style.minHeight = el.style.minHeight || '48px';
        var root = getRoot(el);

        var chart = root.container.children.push(
            xy.XYChart.new(root, {
                panX: false, panY: false, wheelX: 'none', wheelY: 'none',
                paddingLeft: 0, paddingRight: 0, paddingTop: 0, paddingBottom: 0
            })
        );

        var xAxis = chart.xAxes.push(
            xy.DateAxis.new(root, {
                baseInterval: { timeUnit: 'day', count: 1 },
                renderer: xy.AxisRendererX.new(root, { visible: false, minGridDistance: 0 })
            })
        );
        xAxis.get('renderer').grid.template.set('visible', false);
        xAxis.get('renderer').labels.template.set('visible', false);

        var yAxis = chart.yAxes.push(
            xy.ValueAxis.new(root, {
                min: 0,
                renderer: xy.AxisRendererY.new(root, { visible: false })
            })
        );
        yAxis.get('renderer').grid.template.set('visible', false);
        yAxis.get('renderer').labels.template.set('visible', false);

        var series = chart.series.push(
            xy.LineSeries.new(root, {
                xAxis: xAxis, yAxis: yAxis,
                valueYField: 'value', valueXField: 'date',
                fill: am5.color(opts.color || P.coral),
                stroke: am5.color(opts.color || P.coral)
            })
        );

        series.fills.template.setAll({ visible: true, fillOpacity: 0.15 });
        series.strokes.template.setAll({ strokeWidth: 1.5 });

        var parsed = (data || []).map(function (d) {
            return { date: new Date(d.date).getTime(), value: Number(d.value) || 0 };
        });
        series.data.setAll(parsed);
        series.appear(400);
        chart.appear(400);
        return chart;
    }

    /* ═══════════════════════════════════════════════
       9. TABLE — HTML leaderboard / data table
       ═══════════════════════════════════════════════ */

    function renderTable(el, data, opts) {
        opts = opts || {};
        var items = data.items || data;
        if (!items || !items.length) {
            el.innerHTML = '<p class="apollo-chart-empty">No data yet.</p>';
            return;
        }

        var cols = opts.columns || detectColumns(items[0]);
        var html = '<table class="apollo-chart-table"><thead><tr>';
        html += '<th>#</th>';
        cols.forEach(function (col) {
            html += '<th>' + escHtml(col.label || col.key) + '</th>';
        });
        html += '</tr></thead><tbody>';

        items.slice(0, opts.maxRows || 20).forEach(function (item, i) {
            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            cols.forEach(function (col) {
                var val = item[col.key];
                if (col.format === 'number') val = formatNum(val);
                else val = escHtml(String(val || ''));
                html += '<td>' + val + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        el.innerHTML = html;
    }

    function detectColumns(sample) {
        var cols = [];
        var priority = ['label', 'name', 'title', 'value', 'total', 'count', 'score'];
        priority.forEach(function (k) {
            if (sample.hasOwnProperty(k)) {
                cols.push({
                    key: k,
                    label: k.charAt(0).toUpperCase() + k.slice(1),
                    format: (k === 'value' || k === 'total' || k === 'count' || k === 'score') ? 'number' : 'text'
                });
            }
        });
        return cols;
    }

    /* ═══════════════════════════════════════════════
       10. RADAR — Multi-dimension scores
       ═══════════════════════════════════════════════ */

    function renderRadar(el, data, opts) {
        if (!am5xyOk()) return;
        opts = opts || {};
        var am5 = W.am5, xy = W.am5xy;
        var root = getRoot(el);

        var chart = root.container.children.push(
            W.am5radar
            ? W.am5radar.RadarChart.new(root, { panX: false, panY: false })
            : xy.XYChart.new(root, { panX: false, panY: false })
        );

        // Fallback to grouped bar if am5radar not loaded.
        if (!W.am5radar) {
            renderGroupedBar(el, data, opts);
            return;
        }

        var rdr = W.am5radar;

        var xAxis = chart.xAxes.push(
            xy.CategoryAxis.new(root, {
                categoryField: 'category',
                renderer: rdr.AxisRendererCircular.new(root, {})
            })
        );

        var yAxis = chart.yAxes.push(
            xy.ValueAxis.new(root, {
                min: 0,
                renderer: rdr.AxisRendererRadial.new(root, {})
            })
        );

        var series = chart.series.push(
            rdr.RadarLineSeries.new(root, {
                name: opts.label || 'Score',
                xAxis: xAxis, yAxis: yAxis,
                valueYField: 'value', categoryXField: 'category',
                tooltip: am5.Tooltip.new(root, { labelText: '{category}: {valueY}' })
            })
        );

        series.strokes.template.setAll({ strokeWidth: 2, stroke: am5.color(P.coral) });
        series.fills.template.setAll({ visible: true, fill: am5.color(P.coral), fillOpacity: 0.2 });

        var items = data.dimensions || data;
        xAxis.data.setAll(items);
        series.data.setAll(items);

        series.appear(800);
        chart.appear(800, 100);
        return chart;
    }

    /* ═══════════════════════════════════════════════
       UTILITIES
       ═══════════════════════════════════════════════ */

    function formatNum(n) {
        n = Number(n) || 0;
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return String(n);
    }

    function escHtml(s) {
        var d = D.createElement('div');
        d.appendChild(D.createTextNode(s));
        return d.innerHTML;
    }

    /* ═══════════════════════════════════════════════
       ROUTER — type → renderer
       ═══════════════════════════════════════════════ */

    var RENDERERS = {
        time_series:  function (el, d, o) { renderTimeSeries(el, d.time_series || d, d.label || o.slug, o); },
        line:         function (el, d, o) { renderTimeSeries(el, d.time_series || d, d.label || o.slug, o); },
        stacked_area: function (el, d, o) { renderStackedArea(el, d.series || d, o); },
        donut:        function (el, d, o) { renderDonut(el, d.breakdown || d, o); },
        distribution: function (el, d, o) { renderDonut(el, d.breakdown || d, o); },
        bar:          function (el, d, o) { renderBarH(el, d.items || d, o); },
        ranking:      function (el, d, o) { renderBarH(el, d.items || d, o); },
        grouped_bar:  function (el, d, o) { renderGroupedBar(el, d, o); },
        comparison:   function (el, d, o) { renderGroupedBar(el, d, o); },
        funnel:       function (el, d, o) { renderFunnel(el, d, o); },
        number:       function (el, d)    { renderNumber(el, d); },
        sparkline:    function (el, d, o) { renderSparkline(el, d.time_series || d, o); },
        table:        function (el, d, o) { renderTable(el, d, o); },
        leaderboard:  function (el, d, o) { renderTable(el, d, o); },
        radar:        function (el, d, o) { renderRadar(el, d, o); }
    };

    /**
     * Universal render: pick the right renderer for the chart type.
     */
    function renderChart(el, data, chartType, opts) {
        opts = opts || {};
        var fn = RENDERERS[chartType] || RENDERERS.time_series;
        fn(el, data, opts);
    }

    /* ═══════════════════════════════════════════════
       DASHBOARD AUTO-INIT
       ═══════════════════════════════════════════════ */

    function initDashboardCharts() {
        var charts = D.querySelectorAll('[data-apollo-chart]');

        charts.forEach(function (el) {
            var slug = el.getAttribute('data-apollo-chart');
            var type = el.getAttribute('data-chart-type') || 'time_series';
            var days = parseInt(el.getAttribute('data-chart-days'), 10) || 30;

            // Check for inline payload first (from WidgetRenderer).
            var payload = el.getAttribute('data-chart-payload');
            if (payload) {
                try {
                    var parsed = JSON.parse(payload);
                    renderChart(el, parsed, type, { slug: slug });
                    el.removeAttribute('data-chart-payload');
                    return;
                } catch (_) {}
            }

            // Fetch from REST API.
            apiGet('metric/' + encodeURIComponent(slug), { days: days })
                .then(function (res) {
                    var data = (res && res.data) ? res.data : res;
                    if (!data) return;
                    renderChart(el, data, type, { slug: slug });
                })
                .catch(function (err) {
                    el.innerHTML = '<p class="apollo-chart-error">Failed to load chart.</p>';
                    console.warn('[ApolloCharts]', slug, err.message);
                });
        });
    }

    /* ═══════════════════════════════════════════════
       PERIOD SELECTOR — reusable date range control
       ═══════════════════════════════════════════════ */

    /**
     * Create a period selector and wire it to a chart element.
     *
     * @param {HTMLElement} container  Element to insert selector into.
     * @param {HTMLElement} chartEl    Chart container to re-render.
     * @param {string}      slug       Metric slug for API calls.
     * @param {string}      chartType  Chart type key.
     * @param {string[]}    periods    Array of period keys ['7d','30d','90d','1y','all'].
     * @param {string}      active     Initially active period.
     */
    function createPeriodSelector(container, chartEl, slug, chartType, periods, active) {
        periods = periods || ['7d', '30d', '90d', '6m', '1y', '5y'];
        active  = active || '30d';

        var LABELS = {
            '1d': 'Dia', '7d': '7d', '14d': '14d', '30d': '30d', '90d': '90d',
            '6m': '6m', '1y': '1a', '5y': '5a', '10y': '10a', 'all': 'Tudo'
        };
        var DAYS = {
            '1d': 1, '7d': 7, '14d': 14, '30d': 30, '90d': 90,
            '6m': 180, '1y': 365, '5y': 1825, '10y': 3650, 'all': 9999
        };

        var nav = D.createElement('nav');
        nav.className = 'apollo-period-selector';

        periods.forEach(function (p) {
            var btn = D.createElement('button');
            btn.type = 'button';
            btn.className = 'apollo-period-btn' + (p === active ? ' active' : '');
            btn.textContent = LABELS[p] || p;
            btn.setAttribute('data-period', p);

            btn.addEventListener('click', function () {
                nav.querySelectorAll('.apollo-period-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');

                var days = DAYS[p] || 30;
                chartEl.innerHTML = '<div class="apollo-chart-loading"></div>';

                apiGet('metric/' + encodeURIComponent(slug), { days: days })
                    .then(function (res) {
                        var data = (res && res.data) ? res.data : res;
                        renderChart(chartEl, data, chartType, { slug: slug });
                    })
                    .catch(function () {
                        chartEl.innerHTML = '<p class="apollo-chart-error">Failed to load.</p>';
                    });
            });

            nav.appendChild(btn);
        });

        container.appendChild(nav);
    }

    /* ═══════════════════════════════════════════════
       EXPORT — ApolloCharts global
       ═══════════════════════════════════════════════ */

    function dispose(id) {
        if (roots[id]) {
            roots[id].dispose();
            delete roots[id];
        }
    }

    function disposeAll() {
        Object.keys(roots).forEach(dispose);
    }

    W.ApolloCharts = {
        // Individual renderers.
        renderTimeSeries:   renderTimeSeries,
        renderStackedArea:  renderStackedArea,
        renderDonut:        renderDonut,
        renderBarH:         renderBarH,
        renderGroupedBar:   renderGroupedBar,
        renderFunnel:       renderFunnel,
        renderNumber:       renderNumber,
        renderSparkline:    renderSparkline,
        renderTable:        renderTable,
        renderRadar:        renderRadar,

        // Universal renderer.
        renderChart:        renderChart,

        // Period selector factory.
        createPeriodSelector: createPeriodSelector,

        // Auto-init.
        init:               initDashboardCharts,

        // REST helper (reusable by GESTOR, Dashboard, etc.).
        apiGet:             apiGet,

        // Cleanup.
        dispose:            dispose,
        disposeAll:         disposeAll,

        // Palette for external use.
        PALETTE:            P,
        SERIES_COLORS:      SERIES_COLORS
    };

    /* ═══════════════════════════════════════════════
       BOOTSTRAP
       ═══════════════════════════════════════════════ */

    if (D.readyState === 'loading') {
        D.addEventListener('DOMContentLoaded', initDashboardCharts);
    } else {
        initDashboardCharts();
    }

})(window, document);
