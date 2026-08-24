/* ═══════════════════════════════════════════════════════════════
   gestor.charts.js — Overview Charts (amCharts 5)
   Revenue (bar), Ticket Sales (line), Audience (donut)
   Reads data from GestorStore.charts
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var _initialized = false;

    window.GestorCharts = {
        init: function () {
            if (_initialized || typeof am5 === 'undefined') return;
            _initialized = true;

            this._initRevenue();
            this._initTickets();
            this._initAudience();
        },

        /* ─── Revenue Bar Chart ─── */
        _initRevenue: function () {
            var el = document.getElementById('chart-revenue');
            if (!el) return;

            var root = am5.Root.new('chart-revenue');
            root.setThemes([am5themes_Animated.new(root)]);

            var chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: true, panY: false,
                wheelX: 'panX', wheelY: 'zoomX'
            }));

            var store = window.GestorStore;
            var data  = (store && store.charts && store.charts.revenue) || [
                { month: 'Set', current: 12000, prev: 8000 },
                { month: 'Out', current: 18000, prev: 14000 },
                { month: 'Nov', current: 24000, prev: 19000 },
                { month: 'Dez', current: 42000, prev: 28000 },
                { month: 'Jan', current: 38000, prev: 32000 },
                { month: 'Fev', current: 51000, prev: 35000 }
            ];

            var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: 'month',
                renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 30 })
            }));
            var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            /* Previous period — gray */
            var s1 = chart.series.push(am5xy.ColumnSeries.new(root, {
                name: 'Anterior', xAxis: xAxis, yAxis: yAxis,
                valueYField: 'prev', categoryXField: 'month'
            }));
            s1.columns.template.setAll({
                cornerRadiusTL: 4, cornerRadiusTR: 4,
                width: am5.percent(40),
                fill: am5.color('#e4e4e7')
            });

            /* Current period — black */
            var s2 = chart.series.push(am5xy.ColumnSeries.new(root, {
                name: 'Atual', xAxis: xAxis, yAxis: yAxis,
                valueYField: 'current', categoryXField: 'month'
            }));
            s2.columns.template.setAll({
                cornerRadiusTL: 4, cornerRadiusTR: 4,
                width: am5.percent(40),
                fill: am5.color('#121214')
            });

            xAxis.data.setAll(data);
            s1.data.setAll(data);
            s2.data.setAll(data);
            chart.appear(1000, 100);
        },

        /* ─── Ticket Sales Line Chart ─── */
        _initTickets: function () {
            var el = document.getElementById('chart-tickets');
            if (!el) return;

            var root = am5.Root.new('chart-tickets');
            root.setThemes([am5themes_Animated.new(root)]);

            var chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: true, panY: false
            }));

            var store = window.GestorStore;
            var data  = (store && store.charts && store.charts.tickets) || [];

            /* Fallback demo data */
            if (!data.length) {
                for (var i = 1; i <= 30; i++) {
                    data.push({
                        day: i,
                        sold: Math.floor(Math.random() * 80) + 20,
                        capacity: 100
                    });
                }
            }

            var xAxis = chart.xAxes.push(am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 50 })
            }));
            var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            var s = chart.series.push(am5xy.SmoothedXLineSeries.new(root, {
                name: 'Vendas', xAxis: xAxis, yAxis: yAxis,
                valueXField: 'day', valueYField: 'sold'
            }));
            s.strokes.template.setAll({ strokeWidth: 2, stroke: am5.color('FF9820') });
            s.fills.template.setAll({ visible: true, fillOpacity: 0.08, fill: am5.color('FF9820') });
            s.data.setAll(data);
            chart.appear(1000, 100);
        },

        /* ─── Audience Donut ─── */
        _initAudience: function () {
            var el = document.getElementById('chart-audience');
            if (!el) return;

            var root = am5.Root.new('chart-audience');
            root.setThemes([am5themes_Animated.new(root)]);

            var chart = root.container.children.push(am5percent.PieChart.new(root, {
                innerRadius: am5.percent(50),
                layout: root.verticalLayout
            }));

            var store  = window.GestorStore;
            var raw    = (store && store.charts && store.charts.audience) || [
                { label: 'Techno', value: 42 },
                { label: 'House',  value: 28 },
                { label: 'Trance', value: 18 },
                { label: 'Outros', value: 12 }
            ];
            var colors = (store && store.charts && store.charts.audienceColors) ||
                         ['#121214', 'FF9820', '#6366f1', '#e4e4e7'];

            var data = raw.map(function (d, i) {
                return {
                    label: d.label,
                    value: d.value,
                    sliceSettings: { fill: am5.color(colors[i] || '#ccc') }
                };
            });

            var series = chart.series.push(am5percent.PieSeries.new(root, {
                valueField: 'value', categoryField: 'label'
            }));
            series.slices.template.setAll({ cornerRadius: 4, strokeOpacity: 0 });
            series.labels.template.set('visible', false);
            series.ticks.template.set('visible', false);

            series.slices.template.adapters.add('fill', function (fill, target) {
                return (target.dataItem && target.dataItem.dataContext &&
                        target.dataItem.dataContext.sliceSettings &&
                        target.dataItem.dataContext.sliceSettings.fill) || fill;
            });

            series.data.setAll(data);
            chart.appear(1000, 100);
        }
    };
})();
