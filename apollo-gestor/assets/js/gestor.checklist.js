/* ═══════════════════════════════════════════════════════════════
   gestor.checklist.js — Task Checklist Toggle + Progress
   Persists toggle via AJAX: toggle_task
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorChecklist = {
        init: function () {
            var self = this;
            G.$$('.task-item').forEach(function (item) {
                G.on(item, 'click', function () {
                    item.classList.toggle('done');
                    var check = G.$('.task-check', item);
                    if (check) {
                        check.innerHTML = item.classList.contains('done')
                            ? '<i class="ri-check-line"></i>'
                            : '';
                    }
                    self.updateProgress();

                    /* Persist to backend */
                    var taskId = item.dataset.taskId;
                    if (taskId) {
                        G.ajax('toggle_task', { task_id: taskId }).catch(function () {
                            G.toast('Erro ao salvar tarefa', 'error');
                        });
                    }
                });
            });
        },

        updateProgress: function () {
            var all  = G.$$('.task-item');
            var done = G.$$('.task-item.done');
            var pct  = all.length ? Math.round((done.length / all.length) * 100) : 0;

            var bar   = G.$('.ev-progress-bar');
            var label = G.$('.ev-progress-label');
            if (bar)   bar.style.width    = pct + '%';
            if (label) label.textContent  = pct + '%';
        }
    };
})();
