/* ═══════════════════════════════════════════════════════════════
   gestor.gantt.js — Gantt Chart (amCharts 5 XY)
   Reads task data from GestorStore.gantt (loaded via AJAX)
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorGantt = {
        init: function () {
            var el = document.getElementById('gantt-chart');
            if (!el || typeof am5 === 'undefined') return;

            var store = window.GestorStore;
            var raw   = (store && store.gantt && store.gantt.tasks) || [];

            var data = raw.map(function (d) {
                return {
                    task:  d.task,
                    start: new Date(d.start).getTime(),
                    end:   new Date(d.end).getTime(),
                    color: d.color
                };
            });

            /* Fallback if data didn't load */
            if (!data.length) {
                data = [
                    { task: 'Planejamento',    start: new Date(2026,0,5).getTime(),  end: new Date(2026,1,1).getTime(),  color: '#6366f1' },
                    { task: 'Lineup/DJs',      start: new Date(2026,0,15).getTime(), end: new Date(2026,1,10).getTime(), color: '#6366f1' },
                    { task: 'Fornecedores',    start: new Date(2026,1,1).getTime(),  end: new Date(2026,1,20).getTime(), color: '#d97706' },
                    { task: 'Divulgação',      start: new Date(2026,1,5).getTime(),  end: new Date(2026,2,1).getTime(),  color: '#16a34a' },
                    { task: 'Venda Ingressos', start: new Date(2026,1,10).getTime(), end: new Date(2026,1,27).getTime(), color: 'FF9820' },
                    { task: 'Montagem',        start: new Date(2026,1,25).getTime(), end: new Date(2026,1,27).getTime(), color: '#dc2626' },
                    { task: 'EVENTO',          start: new Date(2026,1,27).getTime(), end: new Date(2026,1,28).getTime(), color: 'FF9820' },
                    { task: 'Pós-evento',      start: new Date(2026,1,28).getTime(), end: new Date(2026,2,10).getTime(), color: '#64748b' }
                ];
            }

            var root = am5.Root.new('gantt-chart');
            root.setThemes([am5themes_Animated.new(root)]);

            var chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: true, panY: false,
                wheelX: 'panX', wheelY: 'zoomX',
                pinchZoomX: true,
                layout: root.verticalLayout,
                paddingLeft: 0
            }));

            var yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: 'task',
                renderer: am5xy.AxisRendererY.new(root, {
                    inversed: true,
                    minGridDistance: 30
                }),
                tooltip: am5.Tooltip.new(root, {})
            }));
            yAxis.data.setAll(data);

            var xAxis = chart.xAxes.push(am5xy.DateAxis.new(root, {
                baseInterval: { timeUnit: 'day', count: 1 },
                renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 80 })
            }));

            var series = chart.series.push(am5xy.ColumnSeries.new(root, {
                xAxis: xAxis, yAxis: yAxis,
                openValueXField: 'start', valueXField: 'end',
                categoryYField: 'task',
                sequencedInterpolation: true
            }));

            series.columns.template.setAll({
                strokeOpacity: 0,
                cornerRadiusBR: 6, cornerRadiusTR: 6,
                cornerRadiusBL: 6, cornerRadiusTL: 6,
                height: am5.percent(60),
                templateField: 'columnSettings'
            });

            series.columns.template.adapters.add('fill', function (fill, target) {
                var ctx = target.dataItem && target.dataItem.dataContext;
                return am5.color((ctx && ctx.color) || '#6366f1');
            });

            series.data.setAll(data.map(function (d) {
                return Object.assign({}, d, {
                    columnSettings: { fill: am5.color(d.color) }
                });
            }));

            series.appear(1000, 100);
            chart.appear(1000, 100);

            window._ganttRoot = root;
        }
    };
})();
