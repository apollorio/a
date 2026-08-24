/* ═══════════════════════════════════════════════════════════════
   gestor.task-detail.js — Task Detail Modal + Reminder Fields
   Click on any .task-row-title to open detail panel with edit.
   Persists via G.ajax → update_task
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var injected = false;

    /* ── Utility shortcuts ─────────────────────────────────────── */
    var $ = function (s) { return document.querySelector(s); };

    function el(id) { return document.getElementById(id); }

    /* ── Inject modal HTML (once) ──────────────────────────────── */
    function injectModal() {
        if (injected) return;
        injected = true;

        var wrap = document.createElement('div');
        wrap.innerHTML = [
            '<div id="taskDetailModal" class="modal-overlay">',
            '<div class="modal-inner" style="max-width:480px;width:95%">',
                '<div class="modal-header">',
                    '<h3><i class="ri-task-line"></i> Detalhes da Tarefa</h3>',
                    '<button type="button" id="taskDetailClose" class="modal-close-btn"><i class="ri-close-line"></i></button>',
                '</div>',
                '<div class="modal-body" style="padding:20px">',
                    '<input type="hidden" id="tdTaskId" /><input type="hidden" id="tdEventId" />',

                    '<div class="td-field" style="margin-bottom:16px">',
                        '<label class="td-label">Título</label>',
                        '<input type="text" id="tdTitle" class="apollo-input" style="width:100%" />',
                    '</div>',

                    '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">',
                        '<div class="td-field">',
                            '<label class="td-label">Prazo</label>',
                            '<input type="date" id="tdDueDate" class="apollo-input" style="width:100%" />',
                        '</div>',
                        '<div class="td-field">',
                            '<label class="td-label">Prioridade</label>',
                            '<select id="tdPriority" class="apollo-input" style="width:100%">',
                                '<option value="low">Baixa</option>',
                                '<option value="medium">Média</option>',
                                '<option value="high">Alta</option>',
                                '<option value="urgent">Urgente</option>',
                            '</select>',
                        '</div>',
                    '</div>',

                    '<div class="td-reminder-panel" style="margin-bottom:16px;padding:16px;background:var(--panel,#1a1a2e);border-radius:12px;border:1px solid var(--border,#333)">',
                        '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">',
                            '<label style="font-size:13px;font-weight:700;color:var(--text,#fff);display:flex;align-items:center;gap:8px">',
                                '<i class="ri-notification-3-line" style="font-size:16px;color:#f59e0b"></i> Criar lembretes?',
                            '</label>',
                            '<label class="td-switch">',
                                '<input type="checkbox" id="tdReminderEnabled" />',
                                '<span class="td-switch-track"><span class="td-switch-thumb"></span></span>',
                            '</label>',
                        '</div>',
                        '<div id="tdReminderOptions" style="display:none;margin-top:8px">',
                            '<label class="td-label">Lembrar quanto tempo antes?</label>',
                            '<select id="tdReminderOffset" class="apollo-input" style="width:100%">',
                                '<option value="1h">1 hora antes</option>',
                                '<option value="3h">3 horas antes</option>',
                                '<option value="6h">6 horas antes</option>',
                                '<option value="24h" selected>24 horas antes</option>',
                                '<option value="48h">48 horas antes</option>',
                                '<option value="72h">72 horas antes</option>',
                                '<option value="1w">1 semana antes</option>',
                            '</select>',
                        '</div>',
                    '</div>',

                '</div>',
                '<div class="modal-footer" style="padding:16px 20px;display:flex;justify-content:flex-end;gap:8px;border-top:1px solid var(--border,#333)">',
                    '<button type="button" id="tdCancel" class="apollo-btn ghost">Cancelar</button>',
                    '<button type="button" id="tdSave" class="apollo-btn primary"><i class="ri-save-line"></i> Salvar</button>',
                '</div>',
            '</div>',
            '</div>'
        ].join('');

        document.body.appendChild(wrap.firstElementChild);
        addInlineStyles();
        bindModal();
    }

    /* ── Minimal inline styles for switch + labels ─────────────── */
    function addInlineStyles() {
        if (document.getElementById('tdStyles')) return;
        var s = document.createElement('style');
        s.id = 'tdStyles';
        s.textContent = [
            '.td-label{font-size:11px;font-weight:700;color:var(--ghost,#888);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;display:block}',
            '.td-switch{position:relative;display:inline-block;width:44px;height:24px;cursor:pointer}',
            '.td-switch input{opacity:0;width:0;height:0;position:absolute}',
            '.td-switch-track{position:absolute;inset:0;background:#444;border-radius:24px;transition:background .25s}',
            '.td-switch-thumb{position:absolute;height:18px;width:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:transform .25s}',
            '.td-switch input:checked+.td-switch-track{background:#6C3BF5}',
            '.td-switch input:checked+.td-switch-track .td-switch-thumb{transform:translateX(20px)}'
        ].join('\n');
        document.head.appendChild(s);
    }

    /* ── Bind modal events ─────────────────────────────────────── */
    function bindModal() {
        var modal    = el('taskDetailModal');
        var cbRemind = el('tdReminderEnabled');
        var optWrap  = el('tdReminderOptions');

        function close() { modal.classList.remove('on'); }

        el('taskDetailClose').addEventListener('click', close);
        el('tdCancel').addEventListener('click', close);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) close();
        });

        /* Escape key */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('on')) close();
        });

        /* Toggle reminder options */
        cbRemind.addEventListener('change', function () {
            optWrap.style.display = cbRemind.checked ? '' : 'none';
        });

        /* Save */
        el('tdSave').addEventListener('click', function () {
            var taskId  = el('tdTaskId').value;
            var eventId = el('tdEventId').value;
            if (!taskId || !eventId) return;

            var btn = el('tdSave');
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Salvando…';

            G.ajax('update_task', {
                task_id:          taskId,
                event_id:         eventId,
                title:            el('tdTitle').value,
                due_date:         el('tdDueDate').value,
                priority:         el('tdPriority').value,
                reminder_enabled: cbRemind.checked ? 1 : 0,
                reminder_offset:  el('tdReminderOffset').value
            }).then(function (r) {
                if (r.success) {
                    G.toast('Tarefa atualizada!', 'success');
                    close();
                    if (typeof window.loadTasks === 'function') window.loadTasks();
                    if (typeof window.loadSingleTasks === 'function') window.loadSingleTasks();
                } else {
                    G.toast((r.data && r.data.message) || 'Erro ao salvar', 'error');
                }
            }).catch(function () {
                G.toast('Erro de conexão', 'error');
            }).finally(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-save-line"></i> Salvar';
            });
        });
    }

    /* ── Open modal and populate fields ────────────────────────── */
    function openTaskDetail(taskId, eventId) {
        injectModal();

        var modal = el('taskDetailModal');
        modal.classList.add('on');

        /* Reset */
        el('tdTaskId').value  = taskId;
        el('tdEventId').value = eventId || '';
        el('tdTitle').value   = '';
        el('tdDueDate').value = '';
        el('tdPriority').value = 'medium';
        el('tdReminderEnabled').checked = false;
        el('tdReminderEnabled').dispatchEvent(new Event('change'));
        el('tdReminderOffset').value = '24h';

        /* Fetch current task data */
        G.ajax('load_task_detail', { task_id: taskId }).then(function (r) {
            if (!r.success || !r.data) return;
            var t = r.data;
            el('tdTitle').value    = t.title || '';
            el('tdDueDate').value  = t.due_date || '';
            el('tdPriority').value = t.priority || 'medium';
            if (!eventId) el('tdEventId').value = t.event_id || '';

            var remOn = parseInt(t.reminder_enabled, 10) === 1;
            el('tdReminderEnabled').checked = remOn;
            el('tdReminderEnabled').dispatchEvent(new Event('change'));
            el('tdReminderOffset').value = t.reminder_offset || '24h';
        });
    }

    /* ── Click handler: open from task-row title click ─────────── */
    document.addEventListener('click', function (e) {
        var title = e.target.closest('.task-row-title, .task-row-info');
        if (!title) return;
        /* Don't intercept checkbox clicks */
        if (e.target.closest('.task-chk')) return;
        e.stopPropagation();

        var row    = title.closest('.task-row');
        if (!row) return;
        var taskId  = row.getAttribute('data-task-id');
        var eventId = (window.GestorStore && window.GestorStore._eventId) || '';
        if (taskId) openTaskDetail(taskId, eventId);
    });

    /* ── Expose for external calls ─────────────────────────────── */
    window.GestorTaskDetail = { open: openTaskDetail };

})();
