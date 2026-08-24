/**
 * GESTOR apollo v5 — JavaScript Controller
 *
 * AJAX-powered project management dashboard
 * Tabs: Overview, Kanban, Tarefas, Time, Financeiro, Fornecedores, Cronograma, Arquivos
 * Project=Projeto | Task=Tarefa | Team=Time | Budget→Financeiro
 *
 * Depends: jQuery, GSAP (via CDN), ApolloGestor localized object
 *
 * @package Apollo\Gestor
 * @version 1.1.0
 */

;(function($, G, gsap) {
    'use strict';

    /* ═══════════════════════════════════════════════════════════
     * STATE
     * ═══════════════════════════════════════════════════════════ */
    const S = {
        events:        [],
        currentEvent:  null,   // numeric ID or 'overview'
        currentTab:    'overview',
        tasks:         [],
        team:          [],
        payments:      [],
        milestones:    [],
        loading:       false,
        canManage:     false,
        canFinance:    false,
    };

    const STATUS_MAP  = { planning: 'Planejando', preparing: 'Preparando', live: 'Em Andamento', done: 'Concluído', cancelled: 'Cancelado' };
    const STATUS_COLORS = { planning: 'var(--s-plan)', preparing: 'var(--s-prep)', live: 'var(--s-live)', done: 'var(--s-done)', cancelled: 'var(--s-cancel)' };
    const ROLE_LABELS = { adm: 'ADM', gestor: 'Gestor', tgestor: 'T.Gestor', team: 'Team' };
    const ROLE_ICONS  = { adm: 'ri-shield-star-line', gestor: 'ri-user-star-line', tgestor: 'ri-user-settings-line', team: 'ri-user-line' };

    /* ═══════════════════════════════════════════════════════════
     * HELPERS
     * ═══════════════════════════════════════════════════════════ */
    function ajax(action, data) {
        return $.post(G.ajaxUrl, Object.assign({ action: 'apollo_gestor_' + action, nonce: G.nonce }, data || {}));
    }

    function money(v) {
        if (v === undefined || v === null) return '–';
        const n = parseFloat(v);
        if (n >= 1000) return 'R$ ' + (n / 1000).toFixed(n % 1000 === 0 ? 0 : 1) + 'k';
        return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 0 });
    }

    function moneyFull(v) {
        if (v === undefined || v === null) return '–';
        return 'R$ ' + parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    /** Apollo standard time-ago HTML block. Input: '53min' → icon+spans. */
    function tempoHTML(str) {
        if (!str) return '';
        var m = String(str).match(/^(\d+)([a-z]+)$/i);
        var num  = m ? m[1] : str;
        var unit = m ? m[2] : '';
        return '<i class="tempo-v"></i>\u00a0<span class="time-ago">' + num + '</span><span class="when-ago">' + unit + '</span>';
    }

    function animateIn(selector, opts) {
        const els = typeof selector === 'string' ? document.querySelectorAll(selector) : selector;
        if (els && els.length && typeof gsap !== 'undefined') {
            gsap.from(els, Object.assign({ y: 20, opacity: 0, duration: 0.4, stagger: 0.04, ease: 'power2.out' }, opts || {}));
        }
    }

    function animateBars(selector) {
        if (typeof gsap === 'undefined') return;
        document.querySelectorAll(selector || '.crono-fill,.ev-prog-fill').forEach(function(bar) {
            const w = bar.style.width;
            bar.style.width = '0%';
            gsap.to(bar, { width: w, duration: 1, ease: 'power2.out', delay: 0.3 });
        });
    }

    function skeleton(count, type) {
        let html = '';
        for (let i = 0; i < (count || 3); i++) {
            html += '<div class="skeleton skeleton-' + (type || 'row') + '"></div>';
        }
        return html;
    }

    /* ═══════════════════════════════════════════════════════════
     * LOADER
     * ═══════════════════════════════════════════════════════════ */
    function hideLoader() {
        setTimeout(function() {
            var l = document.getElementById('loader');
            if (l) l.classList.add('out');
            setTimeout(function() { if (l) l.style.display = 'none'; }, 600);
        }, 400);
    }

    /* ═══════════════════════════════════════════════════════════
     * TAB SYSTEM
     * ═══════════════════════════════════════════════════════════ */
    function initTabs() {
        var tabs = document.querySelectorAll('.tab');
        var panels = document.querySelectorAll('.panel');

        function updateIcons() {
            tabs.forEach(function(t) {
                var ico = t.querySelector('[data-apollo-icon]');
                if (!ico) return;
                var a = t.dataset.iconActive;
                var b = t.dataset.iconInactive;
                if (t.classList.contains('on') && a) {
                    ico.style.setProperty('--apollo-mask', "url('" + a + "')", 'important');
                } else if (b) {
                    ico.style.setProperty('--apollo-mask', "url('" + b + "')", 'important');
                }
            });
        }

        tabs.forEach(function(t) {
            t.addEventListener('click', function() {
                tabs.forEach(function(x) { x.classList.remove('on'); });
                panels.forEach(function(p) { p.classList.remove('on'); });
                t.classList.add('on');
                updateIcons();

                S.currentTab = t.dataset.tab;
                var target = document.getElementById('p-' + S.currentTab);
                if (target) {
                    target.classList.add('on');
                    onTabActivated(S.currentTab);
                }
            });
        });

        updateIcons();
    }

    function onTabActivated(tab) {
        switch (tab) {
            case 'overview':
                if (S.currentEvent && S.currentEvent !== 'overview') {
                    loadSingleEvent(S.currentEvent);
                } else {
                    loadOverview();
                }
                break;
            case 'kanban':
                loadKanban();
                break;
            case 'tarefas':
                if (S.currentEvent && S.currentEvent !== 'overview') loadTaskBoard(S.currentEvent);
                break;
            case 'equipe':
                if (S.currentEvent && S.currentEvent !== 'overview') loadTeam(S.currentEvent);
                break;
            case 'financeiro':
                if (S.currentEvent && S.currentEvent !== 'overview') loadPayments(S.currentEvent);
                break;
            case 'fornecedores':
                loadSuppliers();
                break;
            case 'cronograma':
                if (S.currentEvent && S.currentEvent !== 'overview') loadCronograma(S.currentEvent);
                break;
            case 'arquivos':
                loadGestorFiles();
                break;
        }
    }

    /* ═══════════════════════════════════════════════════════════
     * EVENT SELECTOR
     * ═══════════════════════════════════════════════════════════ */
    function initEventSelector() {
        var sel = document.getElementById('evSelect');
        if (!sel) return;

        sel.addEventListener('change', function() {
            var val = this.value;
            if (val === 'overview') {
                S.currentEvent = 'overview';
                document.getElementById('overviewContent').style.display = 'block';
                document.getElementById('singleContent').style.display = 'none';
                loadOverview();
            } else {
                S.currentEvent = parseInt(val, 10);
                document.getElementById('overviewContent').style.display = 'none';
                document.getElementById('singleContent').style.display = 'block';
                loadSingleEvent(S.currentEvent);
            }

            // Reload active tab data
            if (S.currentTab !== 'overview') {
                onTabActivated(S.currentTab);
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * DATA: Load all events
     * ═══════════════════════════════════════════════════════════ */
    function loadAllEvents(cb) {
        ajax('load_events').done(function(r) {
            if (r.success) {
                S.events = r.data || [];
                if (cb) cb(S.events);
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * OVERVIEW
     * ═══════════════════════════════════════════════════════════ */
    function loadOverview() {
        // Load stats
        ajax('load_overview').done(function(r) {
            if (!r.success) return;
            var d = r.data;
            $('#statEvents').text(d.active_events);
            $('#statDone').text(d.tasks_done);
            $('#statOverdue').text(d.tasks_overdue);
            $('#statTeam').text(d.team_total);
            $('#statBudget').text(d.budget_fmt);
            animateIn('#overviewStats .stat');
        });

        // Load event columns
        loadAllEvents(function(events) {
            renderOverviewColumns(events);
            updateTabCounts(events);
        });

        // Load activity feed
        loadActivityFeed(S.currentEvent);
    }

    function renderOverviewColumns(events) {
        var cols = { planning: [], preparing: [], live: [], done: [] };
        events.forEach(function(ev) {
            if (cols[ev.status]) cols[ev.status].push(ev);
        });

        var html = '';
        ['planning', 'preparing', 'done'].forEach(function(status) {
            var evts = cols[status];
            html += '<div class="ov-col">';
            html += '<div class="ov-col-head">';
            html += '<span class="sdot" style="background:' + STATUS_COLORS[status] + '"></span>';
            html += '<span class="mono" style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--smoke)">' + STATUS_MAP[status] + '</span>';
            html += '<span class="mono" style="font-size:8px;color:var(--mist);margin-left:auto">' + evts.length + '</span>';
            html += '</div>';

            if (!evts.length) {
                html += '<div class="empty" style="padding:16px"><p>Nenhum</p></div>';
            }

            evts.forEach(function(ev) {
                var opac = status === 'done' ? 'opacity:.5;' : '';
                var border = status === 'preparing' ? 'border-left:2px solid var(--s-prep);' : '';
                html += '<div class="ov-minicard" style="' + border + opac + 'cursor:pointer" data-event-id="' + ev.id + '">';
                html += '<div class="ov-minicard-title">' + esc(ev.title) + '</div>';
                html += '<div class="ov-minicard-meta"><i class="ri-calendar-line"></i> ' + esc(ev.start_fmt);
                if (ev.loc_name) html += ' · ' + esc(ev.loc_name);
                html += '</div>';
                if (ev.progress > 0 && status !== 'done') {
                    html += '<div style="margin-top:5px"><div class="ev-prog-track"><div class="ev-prog-fill" style="width:' + ev.progress + '%;background:var(--ink)"></div></div></div>';
                }
                html += '</div>';
            });

            html += '</div>';
        });

        $('#overviewCols').html(html);
        animateIn('#overviewCols .ov-minicard');
        animateBars('#overviewCols .ev-prog-fill');

        // Click on minicard -> select event
        $('#overviewCols').on('click', '.ov-minicard[data-event-id]', function() {
            var id = $(this).data('event-id');
            $('#evSelect').val(id).trigger('change');
        });
    }

    function updateTabCounts(events) {
        var cnt = events ? events.length : 0;
        var teamSet = {};
        events.forEach(function(ev) { teamSet[ev.id] = ev.team_count; });
        var teamTotal = 0;
        Object.values(teamSet).forEach(function(c) { teamTotal += c; });

        $('#cntKanban').text(cnt);
        $('#cntTeam').text(teamTotal);

        // Task count — aggregate from events
        var taskTotal = 0;
        events.forEach(function(ev) { taskTotal += (ev.task_count || 0); });
        $('#cntTasks').text(taskTotal);
    }

    /* ═══════════════════════════════════════════════════════════
     * SINGLE EVENT VIEW (Overview panel → specific event)
     * ═══════════════════════════════════════════════════════════ */
    function loadSingleEvent(eventId) {
        var ev = S.events.find(function(e) { return e.id === eventId; });

        if (!ev) {
            // Event not cached, reload all
            loadAllEvents(function() {
                ev = S.events.find(function(e) { return e.id === eventId; });
                if (ev) renderSingleEvent(ev);
            });
            return;
        }

        renderSingleEvent(ev);
    }

    function renderSingleEvent(ev) {
        S.canManage  = ev.can_manage;
        S.canFinance = ev.can_finance;

        $('#singleName').text(ev.title);
        $('#singleDate').text(ev.start_fmt + (ev.loc_name ? ' · ' + ev.loc_name : ''));
        $('#singleStatus').text(STATUS_MAP[ev.status] || ev.status);
        $('#singleStatusDot').css('background', STATUS_COLORS[ev.status] || 'var(--mist)');
        $('#singleTasks').text(ev.tasks.done + '/' + ev.tasks.total + ' tarefas');
        $('#singleBudget').text(ev.budget_fmt);
        $('#singleTeam').text(ev.team_count + ' membros');
        $('#sStat1').text(ev.tasks.total);
        $('#sStat2').text(ev.tasks.done);
        $('#sStat3').text(ev.tasks.pending + ev.tasks.in_progress);
        $('#sStat4').text(ev.team_count);

        animateIn('#singleContent .stat', { delay: 0.1 });

        // Load tasks
        loadTaskList(ev.id);
    }

    function loadTaskList(eventId) {
        var $list = $('#singleTasks_list');
        $list.html(skeleton(4));

        ajax('load_tasks', { event_id: eventId, status: 'pending' }).done(function(r) {
            if (!r.success) return;
            var tasks = r.data.tasks || [];
            S.tasks = tasks;

            if (!tasks.length) {
                $list.html('<div class="empty"><i class="ri-check-double-line"></i><h3>Tudo pronto!</h3><p>Nenhuma tarefa pendente</p></div>');
                return;
            }

            var html = '';
            tasks.forEach(function(t) {
                var isDone = t.status === 'done';
                var isOver = t.due_date && !isDone && new Date(t.due_date) < new Date();
                html += '<div class="task-row" data-task-id="' + t.id + '">';
                html += '<div class="task-chk' + (isDone ? ' done' : '') + '" data-task-id="' + t.id + '"></div>';
                html += '<div class="task-row-info">';
                html += '<div class="task-row-title' + (isDone ? ' done' : '') + '">' + esc(t.title) + '</div>';
                html += '<div class="task-row-sub">' + esc(t.category || '') + (t.assignee_name ? ' · ' + esc(t.assignee_name) : '') + '</div>';
                html += '</div>';
                if (t.due_date) {
                    var df = new Date(t.due_date);
                    var fmt = df.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
                    html += '<div class="task-row-date' + (isOver ? ' overdue' : '') + '">' + fmt + '</div>';
                }
                html += '</div>';
            });

            $list.html(html);
            animateIn('#singleTasks_list .task-row', { y: 10, duration: 0.3, stagger: 0.03 });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * KANBAN
     * ═══════════════════════════════════════════════════════════ */
    function loadKanban() {
        loadAllEvents(function(events) {
            renderKanban(events);
        });
    }

    function renderKanban(events) {
        var cols = { planning: [], preparing: [], live: [], done: [] };
        events.forEach(function(ev) {
            if (cols[ev.status]) cols[ev.status].push(ev);
        });

        // Place cards in each column
        ['planning', 'preparing', 'live', 'done'].forEach(function(status) {
            var $list = $('.ev-list[data-status="' + status + '"]');
            var $count = $list.closest('.ev-col').find('.ev-col-count');
            var evts = cols[status];
            $count.text(evts.length);

            if (!evts.length) {
                var emptyMsg = status === 'live'
                    ? '<div class="empty"><i class="ri-live-line"></i><h3>Nenhum em andamento</h3><p>Quando começar, aparece aqui</p></div>'
                    : '<div class="empty"><i class="ri-inbox-line"></i><p>Vazio</p></div>';
                $list.html(emptyMsg);
                return;
            }

            var html = '';
            evts.forEach(function(ev) {
                var isDone = status === 'done';
                var isUrgent = !isDone && ev.tasks.overdue > 0;
                html += '<div class="ev-card" draggable="true" data-event-id="' + ev.id + '" style="' + (isDone ? 'opacity:.45;' : '') + (status === 'preparing' ? 'border-left:2px solid var(--s-prep);' : '') + '">';

                // Top row
                html += '<div class="ev-card-top">';
                html += '<span class="ev-card-id">EV-' + String(ev.id).padStart(3, '0') + '</span>';
                html += '<span class="ev-card-date">';
                if (isUrgent) {
                    html += '<i class="ri-alarm-warning-line" style="color:var(--p-urg)"></i> ';
                } else if (isDone) {
                    html += '<i class="ri-check-line" style="color:var(--s-live)"></i> ';
                } else {
                    html += '<i class="ri-calendar-line"></i> ';
                }
                html += esc(ev.start_fmt) + '</span></div>';

                // Title + loc
                html += '<div class="ev-card-title">' + esc(ev.title) + '</div>';
                if (ev.loc_name) {
                    html += '<div class="ev-card-loc"><i class="ri-map-pin-line"></i> ' + esc(ev.loc_name) + '</div>';
                }

                // Bottom
                html += '<div class="ev-card-bottom">';
                html += '<div class="ev-card-team">';
                (ev.team_avatars || []).forEach(function(url) {
                    if (url) html += '<div class="tav"><img src="' + esc(url) + '" alt=""></div>';
                });
                html += '</div>';
                if (ev.can_finance) {
                    html += '<span class="ev-card-budget"><i class="ri-eye-line"></i> ' + esc(ev.budget_fmt) + '</span>';
                }
                html += '</div>';

                // Progress
                html += '<div class="ev-prog"><div class="ev-prog-track"><div class="ev-prog-fill" style="width:' + ev.progress + '%;background:' + (isDone ? 'var(--s-done)' : 'var(--ink)') + '"></div></div>';
                if (!isDone) {
                    html += '<div class="ev-prog-info"><span>' + ev.tasks.done + '/' + ev.tasks.total + '</span><span>' + ev.progress + '%</span></div>';
                }
                html += '</div>';

                html += '</div>'; // .ev-card
            });

            $list.html(html);
        });

        animateIn('.ev-card', { delay: 0.2 });
        animateBars('.ev-prog-fill');
        initKanbanDrag();
    }

    /* ═══════════════════════════════════════════════════════════
     * KANBAN DRAG & DROP
     * ═══════════════════════════════════════════════════════════ */
    var draggedCard = null;

    function initKanbanDrag() {
        document.querySelectorAll('.ev-card[draggable]').forEach(function(card) {
            card.addEventListener('dragstart', function(e) {
                draggedCard = card;
                card.style.opacity = '0.35';
                e.dataTransfer.effectAllowed = 'move';
            });
            card.addEventListener('dragend', function() {
                card.style.opacity = '';
                draggedCard = null;
                document.querySelectorAll('.ev-list').forEach(function(l) { l.classList.remove('drag-over'); });
            });
            card.addEventListener('dblclick', function() {
                openSlideOver(parseInt(card.dataset.eventId, 10));
            });
        });

        document.querySelectorAll('.ev-list').forEach(function(list) {
            list.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                list.classList.add('drag-over');
            });
            list.addEventListener('dragleave', function() {
                list.classList.remove('drag-over');
            });
            list.addEventListener('drop', function(e) {
                e.preventDefault();
                list.classList.remove('drag-over');
                if (!draggedCard) return;

                var empty = list.querySelector('.empty');
                if (empty) empty.remove();

                list.appendChild(draggedCard);
                var newStatus = list.dataset.status;
                var eventId = parseInt(draggedCard.dataset.eventId, 10);

                if (typeof gsap !== 'undefined') {
                    gsap.fromTo(draggedCard, { scale: 0.96, opacity: 0.5 }, { scale: 1, opacity: 1, duration: 0.3, ease: 'back.out(2)' });
                }

                // Save new status via AJAX
                ajax('update_event_status', { event_id: eventId, status: newStatus }).done(function(r) {
                    if (r.success) {
                        // Update local cache
                        var ev = S.events.find(function(e) { return e.id === eventId; });
                        if (ev) ev.status = newStatus;
                        updateKanbanCounts();
                    }
                });
            });
        });
    }

    function updateKanbanCounts() {
        document.querySelectorAll('.ev-col').forEach(function(col) {
            var cnt = col.querySelector('.ev-col-count');
            var n = col.querySelectorAll('.ev-card').length;
            if (cnt) cnt.textContent = n;
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * SLIDE-OVER (event detail panel)
     * ═══════════════════════════════════════════════════════════ */
    function openSlideOver(eventId) {
        var ev = S.events.find(function(e) { return e.id === eventId; });
        if (!ev) return;

        S.currentEvent = eventId;
        $('#evSelect').val(String(eventId));

        $('#soId').text('EV-' + String(ev.id).padStart(3, '0'));
        $('#soTitle').text(ev.title);
        $('#soLoc span').text(ev.loc_name || '–');
        $('#soStatus').html('<span class="sdot" style="background:' + STATUS_COLORS[ev.status] + '"></span> ' + (STATUS_MAP[ev.status] || ev.status));
        $('#soDate').text(ev.start_fmt || '–');
        $('#soTeamCount').text(ev.team_count + ' membros');
        $('#soBudget').text(ev.can_finance ? ev.budget_fmt : '–');

        // Load tasks for checklist
        ajax('load_tasks', { event_id: eventId }).done(function(r) {
            if (!r.success) return;
            var tasks = r.data.tasks || [];
            var done = tasks.filter(function(t) { return t.status === 'done'; }).length;
            $('#soChecklistLabel').text('Checklist (' + done + '/' + tasks.length + ')');

            var html = '';
            tasks.forEach(function(t) {
                var isDone = t.status === 'done';
                html += '<div class="so-task" data-task-id="' + t.id + '">';
                html += '<div class="so-task-chk' + (isDone ? ' done' : '') + '" data-task-id="' + t.id + '"></div>';
                html += '<span class="so-task-text' + (isDone ? ' done' : '') + '">' + esc(t.title) + '</span>';
                html += '</div>';
            });
            $('#soChecklist').html(html);
        });

        document.getElementById('so').classList.add('open');
        document.getElementById('soBg').classList.add('open');
    }

    function closeSlideOver() {
        document.getElementById('so').classList.remove('open');
        document.getElementById('soBg').classList.remove('open');
    }

    function initSlideOver() {
        $('#soClose').on('click', closeSlideOver);
        $('#soBg').on('click', closeSlideOver);
        $(document).on('keydown', function(e) { if (e.key === 'Escape') closeSlideOver(); });

        // Add task from slide-over
        $('#soAddTask').on('click', function() {
            var title = $('#soNewTask').val().trim();
            if (!title || !S.currentEvent || S.currentEvent === 'overview') return;

            ajax('create_task', { event_id: S.currentEvent, title: title }).done(function(r) {
                if (r.success) {
                    $('#soNewTask').val('');
                    openSlideOver(S.currentEvent); // refresh
                }
            });
        });

        $('#soNewTask').on('keydown', function(e) {
            if (e.key === 'Enter') $('#soAddTask').trigger('click');
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * TEAM / EQUIPE
     * ═══════════════════════════════════════════════════════════ */
    function loadTeam(eventId) {
        if (!eventId || eventId === 'overview') {
            $('#staffGrid').html('<div class="empty"><i class="ri-team-line"></i><h3>Selecione um projeto</h3></div>');
            return;
        }

        var $grid = $('#staffGrid');
        $grid.html(skeleton(4, 'card'));
        $('#teamSubtitle').text('Carregando…');

        ajax('load_team', { event_id: eventId }).done(function(r) {
            if (!r.success) return;
            S.team = r.data || [];

            var ev = S.events.find(function(e) { return e.id === eventId; });
            var evName = ev ? ev.title : 'Projeto #' + eventId;
            $('#teamSubtitle').text(evName + ' · ' + S.team.length + ' membros');

            if (!S.team.length) {
                $grid.html('<div class="empty"><i class="ri-team-line"></i><h3>Sem time</h3><p>Adicione membros</p></div>');
                return;
            }

            var html = '';
            S.team.forEach(function(m) {
                var roleClass = 'perm-' + (m.role || 'team');
                html += '<div class="staff-card" data-member-id="' + m.id + '">';
                html += '<div class="staff-av"><img src="' + esc(m.avatar_url) + '" alt="' + esc(m.display_name) + '"></div>';
                html += '<div class="staff-name">' + esc(m.display_name) + '</div>';
                html += '<div class="staff-func">' + esc(m.job_function || '–') + '</div>';
                html += '<div class="staff-role-badge"><span class="perm ' + roleClass + '"><i class="' + (ROLE_ICONS[m.role] || 'ri-user-line') + '"></i> ' + (ROLE_LABELS[m.role] || 'Team') + '</span></div>';
                html += '<div class="staff-stats">';
                html += '<div class="staff-stat"><div class="v">' + (m.task_count || 0) + '</div><div class="l">Tarefas</div></div>';
                html += '<div class="staff-stat"><div class="v">' + (m.event_count || 0) + '</div><div class="l">Projetos</div></div>';
                html += '</div>';
                if (m.pix_key) {
                    html += '<div class="staff-pix" data-pix="' + esc(m.pix_key) + '"><i class="ri-key-2-line"></i> ' + esc(m.pix_masked || '***') + '</div>';
                }
                html += '</div>';
            });

            $grid.html(html);
            animateIn('.staff-card');
            renderTeamSummary();
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * FINANCEIRO
     * ═══════════════════════════════════════════════════════════ */
    function loadPayments(eventId, finType) {
        if (!eventId || eventId === 'overview') {
            renderEmptyFinance();
            return;
        }

        var ev = S.events.find(function(e) { return e.id === eventId; });
        var evName = ev ? ev.title : '';
        $('#finSubtitle').text(evName + (ev ? ' · ' + ev.start_fmt : ''));

        var ajaxData = { event_id: eventId };
        if (finType) ajaxData.type = finType;
        ajax('load_payments', ajaxData).done(function(r) {
            if (!r.success) {
                if (r.data && r.data.message) {
                    renderEmptyFinance(r.data.message);
                }
                return;
            }

            var payments = r.data.payments || [];
            var summary  = r.data.summary || {};
            S.payments = payments;

            // Render table
            var html = '';
            if (!payments.length) {
                html = '<tr><td colspan="5" class="empty" style="padding:20px"><i class="ri-money-dollar-circle-line"></i><p>Sem pagamentos</p></td></tr>';
            } else {
                payments.forEach(function(p) {
                    var stClass = p.status === 'paid' ? 'paid' : (p.status === 'late' ? 'late' : 'pend');
                    var stLabel = p.status === 'paid' ? 'Pago' : (p.status === 'late' ? 'Atrasado' : 'Pendente');
                    var stIcon  = p.status === 'paid' ? 'ri-check-line' : (p.status === 'late' ? 'ri-alarm-warning-line' : 'ri-time-line');

                    html += '<tr data-payment-id="' + p.id + '">';
                    html += '<td><div class="flex aic gap4">';
                    if (p.payee_avatar) html += '<div class="user-av" style="width:22px;height:22px;border:none"><img src="' + esc(p.payee_avatar) + '"></div>';
                    html += ' ' + esc(p.payee_name) + '</div></td>';
                    html += '<td class="mono" style="font-size:10px;color:var(--ghost)">' + esc(p.description || p.category || '–') + '</td>';
                    html += '<td class="bold">' + moneyFull(p.amount) + '</td>';
                    html += '<td><span class="fin-pix"><i class="ri-key-2-line"></i> ' + esc(p.pix_masked || p.pix_key || '–') + '</span></td>';
                    html += '<td><span class="fin-st ' + stClass + '"><i class="' + stIcon + '"></i> ' + stLabel + '</span></td>';
                    html += '</tr>';
                });
            }
            $('#finTableBody').html(html);

            // Render summary
            $('#finBudget').text(moneyFull(summary.budget));
            $('#finStaff').text(moneyFull(summary.staff_total));
            $('#finSuppliers').text(moneyFull(summary.supplier_total));
            $('#finProduction').text(moneyFull(summary.production_total));
            $('#finPaid').text(moneyFull(summary.paid));
            $('#finPending').text(moneyFull(summary.pending));
            $('#finLate').text(moneyFull(summary.late));
            $('#finBalance').text(moneyFull(summary.balance));

            // Render KPI strip
            var budget = parseFloat(summary.budget) || 0;
            var committed = (parseFloat(summary.staff_total) || 0) + (parseFloat(summary.supplier_total) || 0) + (parseFloat(summary.production_total) || 0);
            $('#finBudgetKPI').text(money(summary.budget));
            $('#finCommitted').text(money(committed));
            $('#finPaidKPI').text(money(summary.paid));
            $('#finPendingKPI').text(money(summary.pending));
            $('#finLateKPI').text(money(summary.late));

            // Budget progress bar
            if (budget > 0) {
                var pct = Math.min(100, Math.round((committed / budget) * 100));
                var remaining = budget - committed;
                $('#finProgressBar').css('width', pct + '%').css('background', pct > 90 ? 'var(--p-urg)' : 'var(--ink)');
                $('#finProgressPct').text(pct + '% comprometido');
                $('#finProgressRemaining').text('Disponível: ' + moneyFull(remaining > 0 ? remaining : 0));
            } else {
                $('#finProgressBar').css('width', '0%');
                $('#finProgressPct').text('–');
                $('#finProgressRemaining').text('–');
            }

            animateIn('#finTableBody tr', { y: 8, duration: 0.25, stagger: 0.03 });
        });
    }

    function renderEmptyFinance(msg) {
        $('#finTableBody').html('<tr><td colspan="5" class="empty" style="padding:20px"><i class="ri-lock-line"></i><p>' + esc(msg || 'Selecione um projeto') + '</p></td></tr>');
        ['#finBudget','#finStaff','#finSuppliers','#finProduction','#finPaid','#finPending','#finLate','#finBalance','#finBudgetKPI','#finCommitted','#finPaidKPI','#finPendingKPI','#finLateKPI','#finProgressPct','#finProgressRemaining'].forEach(function(id) { $(id).text('–'); });
        $('#finProgressBar').css('width', '0%');
    }

    /* ═══════════════════════════════════════════════════════════
     * FORNECEDORES (Suppliers)
     * ═══════════════════════════════════════════════════════════ */
    function loadSuppliers() {
        var $list = $('#supplierList');
        $list.html(skeleton(3));

        var data = {};
        if (S.currentEvent && S.currentEvent !== 'overview') {
            data.event_id = S.currentEvent;
        }

        ajax('load_suppliers', data).done(function(r) {
            if (!r.success) return;
            var suppliers = r.data || [];

            if (!suppliers.length) {
                $list.html('<div class="empty" style="padding:20px"><i class="ri-store-2-line"></i><h3>Nenhum Fornecedor</h3><p>Fornecedores vinculados a eventos aparecerão aqui</p></div>');
                return;
            }

            var html = '';
            suppliers.forEach(function(s) {
                html += '<div class="staff-card" style="padding:14px">';
                html += '<div class="staff-av" style="width:40px;height:40px;border-radius:8px;background:var(--sf2);display:flex;align-items:center;justify-content:center"><i class="ri-store-2-line" style="font-size:18px;color:var(--smoke)"></i></div>';
                html += '<div class="staff-name">' + esc(s.title || 'Fornecedor #' + s.id) + '</div>';
                html += '<div class="staff-func">' + esc(s.category || s.email || '–') + '</div>';
                if (s.amount > 0) {
                    var stClass = s.status === 'paid' ? 'color:var(--s-done)' : (s.status === 'late' ? 'color:var(--p-urg)' : 'color:var(--smoke)');
                    html += '<div style="font-size:11px;font-weight:600;' + stClass + '">' + moneyFull(s.amount) + '</div>';
                }
                if (s.phone) {
                    html += '<div style="font-size:10px;color:var(--mist);margin-top:4px"><i class="ri-phone-line"></i> ' + esc(s.phone) + '</div>';
                }
                html += '</div>';
            });

            $list.html(html);
            animateIn('#supplierList .staff-card');
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * CRONOGRAMA (Milestones + Gantt)
     * ═══════════════════════════════════════════════════════════ */
    function loadCronograma(eventId) {
        if (!eventId || eventId === 'overview') return;

        loadMilestones(eventId);
        loadGanttData(eventId);
    }

    function loadMilestones(eventId) {
        ajax('load_milestones', { event_id: eventId }).done(function(r) {
            if (!r.success) return;
            S.milestones = r.data.milestones || [];
            var progress = r.data.progress || 0;

            var $list = $('#milestoneList');
            if (!S.milestones.length) {
                $list.html('<div class="empty" style="padding:16px"><i class="ri-flag-line"></i><p>Sem marcos definidos</p></div>');
                return;
            }

            var html = '';
            S.milestones.forEach(function(m) {
                var isDone = m.status === 'done';
                html += '<div class="ms" data-milestone-id="' + m.id + '">';
                html += '<div class="ms-icon"><i class="' + esc(m.icon || 'ri-flag-2-line') + '"></i></div>';
                html += '<div class="ms-info">';
                html += '<div class="ms-name">' + esc(m.title) + '</div>';
                if (m.due_date) {
                    var df = new Date(m.due_date);
                    html += '<div class="ms-date">' + df.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
                    if (isDone) html += ' · Concluído';
                    html += '</div>';
                }
                html += '</div>';
                html += '<div class="ms-chk' + (isDone ? ' done' : '') + '" data-milestone-id="' + m.id + '"></div>';
                html += '</div>';
            });

            $list.html(html);
            animateIn('.ms', { y: 12, duration: 0.3, stagger: 0.04, delay: 0.2 });
        });
    }

    function loadGanttData(eventId) {
        // Load tasks organized as gantt phases
        ajax('load_tasks', { event_id: eventId }).done(function(r) {
            if (!r.success) return;
            var tasks = r.data.tasks || [];
            renderGantt(tasks, eventId);
        });
    }

    function renderGantt(tasks, eventId) {
        var ev = S.events.find(function(e) { return e.id === eventId; });
        if (!ev) return;

        // Build timeline: 20 days from event start going backwards
        var startDate = ev.start_date ? new Date(ev.start_date) : new Date();
        var timelineStart = new Date(startDate);
        timelineStart.setDate(timelineStart.getDate() - 20);
        var today = new Date();

        var days = [];
        var dayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
        for (var i = 0; i < 20; i++) {
            var d = new Date(timelineStart);
            d.setDate(d.getDate() + i);
            days.push({
                num: String(d.getDate()).padStart(2, '0'),
                label: dayNames[d.getDay()],
                isWeekend: d.getDay() === 0 || d.getDay() === 6,
                isToday: d.toDateString() === today.toDateString(),
                date: d
            });
        }

        // Render timeline header
        var headHtml = '';
        days.forEach(function(day) {
            headHtml += '<div class="ms-day' + (day.isWeekend ? ' wk' : '') + '">';
            headHtml += '<div class="ms-day-n">' + day.num + '</div>';
            headHtml += '<div class="ms-day-l">' + day.label + '</div>';
            headHtml += '</div>';
        });
        $('#ganttTimeHead').html(headHtml).css('width', (days.length * 40) + 'px');

        // Render sidebar + bars grouped by category
        var categories = {};
        tasks.forEach(function(t) {
            var cat = t.category || 'Geral';
            if (!categories[cat]) categories[cat] = [];
            categories[cat].push(t);
        });

        var sideHtml = '';
        var chartHtml = '';
        var rowNum = 1;
        var barColors = ['plan', 'proc', 'wait', 'crit'];
        var colorIdx = 0;

        Object.keys(categories).forEach(function(cat) {
            // Group header
            sideHtml += '<div class="ms-task-row group"><span style="font-family:var(--mono);width:20px">' + String(rowNum).padStart(2, '0') + '</span><span>' + esc(cat) + '</span></div>';
            chartHtml += '<div class="ms-chart-row" style="background:var(--sf2);border-bottom-color:var(--brd)"></div>';
            rowNum++;

            categories[cat].forEach(function(t, idx) {
                // Sidebar row
                sideHtml += '<div class="ms-task-row">';
                sideHtml += '<span style="font-family:var(--mono);width:20px;color:var(--mist)">' + (rowNum - 1) + '.' + (idx + 1) + '</span>';
                sideHtml += '<span style="flex:1">' + esc(t.title) + '</span>';
                if (t.assignee_avatar) {
                    sideHtml += '<div class="user-av" style="width:20px;height:20px"><img src="' + esc(t.assignee_avatar) + '"></div>';
                }
                sideHtml += '</div>';

                // Gantt bar — calculate position from task dates
                var barStyle = barColors[colorIdx % barColors.length];
                if (t.priority === 'urgent') barStyle = 'crit';

                // Use real task due_date for positioning
                var taskDue = t.due_date ? new Date(t.due_date) : null;
                var taskStart = t.created_at ? new Date(t.created_at) : null;
                var left, width;

                if (taskDue) {
                    // Calculate day offset from timeline start
                    var dueOffset = Math.round((taskDue - timelineStart) / 86400000);
                    // Task "starts" 3 days before due or at creation date
                    var startOffset = taskStart ? Math.round((taskStart - timelineStart) / 86400000) : Math.max(0, dueOffset - 3);
                    startOffset = Math.max(0, Math.min(startOffset, days.length - 1));
                    dueOffset = Math.max(startOffset + 1, Math.min(dueOffset, days.length));
                    left = startOffset * 40;
                    width = Math.max(40, (dueOffset - startOffset) * 40);
                } else if (taskStart) {
                    var startOffset2 = Math.round((taskStart - timelineStart) / 86400000);
                    startOffset2 = Math.max(0, Math.min(startOffset2, days.length - 2));
                    left = startOffset2 * 40;
                    width = 120; // Default 3-day span for tasks without due date
                } else {
                    // No dates at all — place sequentially
                    left = (idx * 3) * 40;
                    width = 120;
                }

                // Clamp to timeline bounds
                left = Math.max(0, Math.min(left, (days.length - 1) * 40));
                width = Math.min(width, (days.length * 40) - left);

                var progress = t.status === 'done' ? 100 : (t.status === 'in_progress' ? 50 : 0);

                chartHtml += '<div class="ms-chart-row">';
                chartHtml += '<div class="g-bar ' + barStyle + '" style="left:' + left + 'px;width:' + width + 'px" data-tooltip="' + esc(t.title) + '">';
                if (progress > 0) chartHtml += '<div class="g-fill" style="width:' + progress + '%"></div>';
                chartHtml += '<span>' + esc(t.title.substring(0, 20)) + '</span>';
                chartHtml += '</div></div>';

                colorIdx++;
            });
        });

        // Today line
        var todayIdx = -1;
        days.forEach(function(day, idx) {
            if (day.isToday) todayIdx = idx;
        });

        $('#ganttSide').html('<div class="ms-side-head"><span style="width:20px">#</span><span style="flex:1">Atividade</span><span>Resp.</span></div>' + sideHtml);
        $('#ganttChartRows').html(chartHtml);

        if (todayIdx >= 0) {
            var linePos = todayIdx * 40 + 20;
            var lineHtml = '<div class="ms-today-line" style="left:' + linePos + 'px"><div class="ms-today-label">HOJE</div></div>';
            $('#ganttChartRows').prepend(lineHtml);
        }

        // Phase progress bars
        renderPhaseProgress(tasks, ev);
    }

    function renderPhaseProgress(tasks, ev) {
        var categories = {};
        tasks.forEach(function(t) {
            var cat = t.category || 'Geral';
            if (!categories[cat]) categories[cat] = { total: 0, done: 0 };
            categories[cat].total++;
            if (t.status === 'done') categories[cat].done++;
        });

        var phaseIcons = {
            'Planejamento': 'ri-draft-line', 'NCL': 'ri-team-line', 'Fornecedores': 'ri-store-2-line',
            'Lineup': 'ri-music-2-line', 'Divulgação': 'ri-megaphone-line', 'Ingressos': 'ri-ticket-2-line',
            'Montagem': 'ri-tools-line', 'Evento': 'ri-live-line', 'Design': 'ri-palette-line',
            'Produção': 'ri-tools-line', 'Técnica': 'ri-speaker-3-line', 'Operações': 'ri-compasses-2-line',
            'Bar': 'ri-goblet-line', 'Visual': 'ri-lightbulb-flash-line', 'Geral': 'ri-list-check-2'
        };

        var html = '<div class="crono-ev">';
        html += '<div class="crono-ev-head"><span class="sdot" style="background:' + STATUS_COLORS[ev.status] + '"></span>';
        html += '<span class="crono-ev-title">' + esc(ev.title) + '</span>';
        html += '<span class="crono-ev-date">' + esc(ev.start_fmt || '') + '</span></div>';
        html += '<div class="crono-phases">';

        Object.keys(categories).forEach(function(cat) {
            var data = categories[cat];
            var pct = data.total > 0 ? Math.round((data.done / data.total) * 100) : 0;
            var icon = phaseIcons[cat] || 'ri-checkbox-circle-line';
            var barColor = pct === 100 ? 'var(--ink)' : (pct > 50 ? 'var(--smoke)' : 'var(--mist)');

            html += '<div class="crono-phase">';
            html += '<span class="crono-phase-handle" data-tooltip="Arrastar"><i class="ri-draggable"></i></span>';
            html += '<span class="crono-phase-label"><i class="' + icon + '"></i> ' + esc(cat) + '</span>';
            html += '<div class="crono-track"><div class="crono-fill" style="width:' + pct + '%;background:' + barColor + '"></div></div>';
            html += '<span class="crono-pct">' + pct + '%</span>';
            html += '</div>';
        });

        html += '</div></div>';
        $('#cronoPhases').html(html);
        animateBars('#cronoPhases .crono-fill');
        animateIn('#cronoPhases .crono-phase', { y: 10, duration: 0.3, stagger: 0.05 });
    }

    /* ═══════════════════════════════════════════════════════════
     * TASK CHECKBOX TOGGLE (global)
     * ═══════════════════════════════════════════════════════════ */
    function initCheckboxes() {
        $(document).on('click', '.task-chk, .so-task-chk', function(e) {
            e.stopPropagation();
            var $chk = $(this);
            var taskId = $chk.data('task-id');
            if (!taskId) return;

            $chk.toggleClass('done');
            var $txt = $chk.next('.task-row-title, .so-task-text, .task-row-info');
            if ($txt.hasClass('task-row-info')) {
                $txt.find('.task-row-title').toggleClass('done');
            } else {
                $txt.toggleClass('done');
            }

            ajax('toggle_task', { task_id: taskId }).done(function(r) {
                if (r.success && r.data.counts) {
                    // Update overview stats if visible
                    var c = r.data.counts;
                    $('#sStat1').text(c.total);
                    $('#sStat2').text(c.done);
                    $('#sStat3').text(c.pending + c.in_progress);
                }
            });
        });

        // Milestone checkbox
        $(document).on('click', '.ms-chk', function(e) {
            e.stopPropagation();
            var $chk = $(this);
            var msId = $chk.data('milestone-id');
            if (!msId) return;

            $chk.toggleClass('done');
            ajax('toggle_milestone', { milestone_id: msId });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * ADD TASK (from single event view)
     * ═══════════════════════════════════════════════════════════ */
    function initAddTask() {
        $('#btnAddTask').on('click', function() {
            var title = $('#newTaskTitle').val().trim();
            if (!title || !S.currentEvent || S.currentEvent === 'overview') return;

            ajax('create_task', { event_id: S.currentEvent, title: title }).done(function(r) {
                if (r.success) {
                    $('#newTaskTitle').val('');
                    loadTaskList(S.currentEvent);
                    // Refresh overview data
                    loadAllEvents(function(events) { updateTabCounts(events); });
                }
            });
        });

        $('#newTaskTitle').on('keydown', function(e) {
            if (e.key === 'Enter') $('#btnAddTask').trigger('click');
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * PIX COPY TO CLIPBOARD
     * ═══════════════════════════════════════════════════════════ */
    function initPixCopy() {
        $(document).on('click', '.staff-pix', function() {
            var $pix = $(this);
            var key = $pix.data('pix');
            if (!key || !navigator.clipboard) return;

            navigator.clipboard.writeText(key);
            var orig = $pix.html();
            $pix.html('<i class="ri-check-line"></i> Copiado!');
            setTimeout(function() { $pix.html(orig); }, 1200);
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * PERMISSIONS MODAL
     * ═══════════════════════════════════════════════════════════ */
    function initPermModal() {
        $('#btnOpenPerms').on('click', function() {
            if (!S.currentEvent || S.currentEvent === 'overview' || !S.team.length) return;
            renderPermList();
            $('#permModal').css({ opacity: 1, 'pointer-events': 'auto' });
            $('#permModal .modal-card').css({ transform: 'scale(1)', opacity: 1 });
        });

        $('#closePermModal').on('click', closePermModal);
        $('#permModal').on('click', function(e) {
            if ($(e.target).closest('.modal-card').length === 0) closePermModal();
        });
        $(document).on('keydown.permModal', function(e) {
            if (e.key === 'Escape' && $('#permModal').css('opacity') !== '0') closePermModal();
        });

        // Save permissions
        $(document).on('click', '#btnSavePerms', function() {
            if (!S.currentEvent || S.currentEvent === 'overview') return;
            var perms = [];
            $('#permList .perm-row').each(function() {
                perms.push({
                    member_id: $(this).data('member-id'),
                    role: $(this).find('.perm-select').val()
                });
            });

            ajax('save_permissions', { event_id: S.currentEvent, permissions: perms }).done(function(r) {
                if (r.success) {
                    closePermModal();
                    loadTeam(S.currentEvent);
                }
            });
        });

        function closePermModal() {
            $('#permModal').css({ opacity: 0, 'pointer-events': 'none' });
            $('#permModal .modal-card').css({ transform: 'scale(.96)', opacity: 0 });
        }
    }

    function renderPermList() {
        var html = '';
        S.team.forEach(function(m) {
            html += '<div class="perm-row" data-member-id="' + m.id + '" style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--brd)">';
            html += '<div class="user-av" style="width:28px;height:28px"><img src="' + esc(m.avatar_url) + '"></div>';
            html += '<span style="flex:1;font-size:12px">' + esc(m.display_name) + '</span>';
            html += '<select class="perm-select" style="background:var(--sf2);color:var(--smoke);border:1px solid var(--brd);border-radius:6px;padding:4px 8px;font-size:11px;font-family:var(--mono)">';
            ['adm', 'gestor', 'tgestor', 'team'].forEach(function(role) {
                html += '<option value="' + role + '"' + (m.role === role ? ' selected' : '') + '>' + (ROLE_LABELS[role] || role) + '</option>';
            });
            html += '</select></div>';
        });
        $('#permUserList').html(html);
        $('#permCount').text(S.team.length);

        // Set event name
        var ev = S.events.find(function(e) { return e.id === S.currentEvent; });
        if (ev) $('#permEventName').text(ev.title);
    }

    /* ═══════════════════════════════════════════════════════════
     * DELETE PAYMENT
     * ═══════════════════════════════════════════════════════════ */
    function initDeletePayment() {
        $(document).on('dblclick', '#finTableBody tr[data-payment-id]', function() {
            var $row = $(this);
            var pid = $row.data('payment-id');
            if (!pid || !S.canManage) return;
            if (!confirm('Excluir este pagamento?')) return;

            ajax('delete_payment', { payment_id: pid, event_id: S.currentEvent }).done(function(r) {
                if (r.success) {
                    $row.fadeOut(200, function() { $(this).remove(); });
                    // Reload summary
                    loadPayments(S.currentEvent);
                }
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * CREATE MILESTONE
     * ═══════════════════════════════════════════════════════════ */
    function initCreateMilestone() {
        $(document).on('click', '#btnAddMilestone', function() {
            var title = $.trim($('#newMilestoneTitle').val());
            var due   = $.trim($('#newMilestoneDue').val());
            if (!title || !S.currentEvent || S.currentEvent === 'overview') return;

            ajax('create_milestone', { event_id: S.currentEvent, title: title, due_date: due }).done(function(r) {
                if (r.success) {
                    $('#newMilestoneTitle').val('');
                    $('#newMilestoneDue').val('');
                    loadCronograma(S.currentEvent);
                }
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * UPDATE TEAM MEMBER
     * ═══════════════════════════════════════════════════════════ */
    function initTeamEdit() {
        $(document).on('dblclick', '.staff-card[data-member-id]', function() {
            if (!S.canManage) return;
            var $card = $(this);
            var mid = $card.data('member-id');
            var member = S.team.find(function(m) { return m.id == mid; });
            if (!member) return;

            var html = '<div style="padding:16px;background:var(--sf2);border:1px solid var(--brd);border-radius:10px;position:fixed;z-index:10000;top:50%;left:50%;transform:translate(-50%,-50%);width:320px;box-shadow:0 20px 60px rgba(var(--rgb-d),.5)">';
            html += '<div style="font-weight:600;margin-bottom:12px;font-size:14px">' + esc(member.display_name) + '</div>';
            html += '<label style="font-size:10px;color:var(--mist);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Cargo</label>';
            html += '<input type="text" id="editFunc" value="' + esc(member.job_function || '') + '" style="width:100%;background:var(--sf3);color:var(--smoke);border:1px solid var(--brd);border-radius:6px;padding:8px;font-size:12px;margin-bottom:10px">';
            html += '<label style="font-size:10px;color:var(--mist);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Chave PIX</label>';
            html += '<input type="text" id="editPix" value="' + esc(member.pix_key || '') + '" style="width:100%;background:var(--sf3);color:var(--smoke);border:1px solid var(--brd);border-radius:6px;padding:8px;font-size:12px;margin-bottom:12px">';
            html += '<div style="display:flex;gap:8px;justify-content:flex-end">';
            html += '<button id="cancelEdit" style="background:transparent;color:var(--mist);border:1px solid var(--brd);border-radius:6px;padding:6px 14px;font-size:11px;cursor:pointer">Cancelar</button>';
            html += '<button id="saveEdit" data-mid="' + mid + '" style="background:var(--ink);color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:11px;cursor:pointer;font-weight:600">Salvar</button>';
            html += '</div></div>';
            html += '<div id="editBg" style="position:fixed;inset:0;background:rgba(var(--rgb-d),.5);z-index:9999"></div>';

            $('body').append(html);

            $(document).on('click.editTeam', '#cancelEdit, #editBg', function() {
                $('#editBg').remove();
                $('[data-mid]').closest('div[style*="fixed"]').remove();
                $(document).off('.editTeam');
            });

            $(document).on('click.editTeam', '#saveEdit', function() {
                var mid2 = $(this).data('mid');
                ajax('update_team_member', {
                    member_id: mid2,
                    event_id: S.currentEvent,
                    job_function: $.trim($('#editFunc').val()),
                    pix_key: $.trim($('#editPix').val())
                }).done(function(r) {
                    if (r.success) {
                        loadTeam(S.currentEvent);
                    }
                });
                $('#editBg').remove();
                $('[data-mid]').closest('div[style*="fixed"]').remove();
                $(document).off('.editTeam');
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * ACTIVITY LOG (render in overview)
     * ═══════════════════════════════════════════════════════════ */
    function loadActivityFeed(eventId) {
        var $feed = $('#activityFeed');
        if (!$feed.length) return;

        $feed.html(skeleton(4));

        var data = eventId && eventId !== 'overview' ? { event_id: eventId } : {};

        ajax('load_activity', data).done(function(r) {
            if (!r.success) return;
            var activities = r.data.activities || [];

            if (!activities.length) {
                $feed.html('<div class="empty" style="padding:16px"><i class="ri-history-line"></i><p>Sem atividade recente</p></div>');
                return;
            }

            var html = '';
            activities.slice(0, 10).forEach(function(a) {
                html += '<div class="activity-item" style="display:flex;gap:8px;padding:6px 0;border-bottom:1px solid rgba(var(--rgb-t),.04);font-size:11px">';
                if (a.avatar_url) html += '<div class="user-av" style="width:22px;height:22px;flex-shrink:0"><img src="' + esc(a.avatar_url) + '"></div>';
                html += '<div style="flex:1;min-width:0">';
                html += '<span style="color:var(--smoke);font-weight:500">' + esc(a.display_name || 'Sistema') + '</span> ';
                html += '<span style="color:var(--ghost)">' + esc(a.action_label) + '</span>';
                if (a.meta && a.meta.title) html += ' <span style="color:var(--mist)">· ' + esc(a.meta.title) + '</span>';
                html += '</div>';
                html += '<span style="color:var(--mist);white-space:nowrap;font-family:var(--mono);font-size:9px">' + tempoHTML(a.time_ago) + '</span>';
                html += '</div>';
            });

            $feed.html(html);
            animateIn('#activityFeed .activity-item', { y: 8, duration: 0.2, stagger: 0.03 });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * EMBEDDED FILE MANAGER (Arquivos Tab)
     * ═══════════════════════════════════════════════════════════ */

    var FM = {
        docs: [],
        folders: [],
        currentFolder: 0,
        viewMode: 'grid',
        search: '',
        loading: false,
    };

    function fmAjax(action, data) {
        data = data || {};
        data.action = 'apollo_docs_' + action;
        // Use docs nonce for cross-plugin auth
        data.nonce = C.docsNonce || (window.ApolloDocs && ApolloDocs.nonce) || C.nonce;
        return $.post(C.ajaxUrl, data);
    }

    function fmIcon(title) {
        var ext = (title || '').split('.').pop().toLowerCase();
        var map = {
            pdf: 'ri-file-pdf-2-fill', jpg: 'ri-image-fill', jpeg: 'ri-image-fill',
            png: 'ri-image-fill', gif: 'ri-image-fill', webp: 'ri-image-fill',
            svg: 'ri-image-fill', mp4: 'ri-video-fill', mov: 'ri-video-fill',
            mp3: 'ri-music-fill', wav: 'ri-music-fill', flac: 'ri-music-fill',
            doc: 'ri-file-word-fill', docx: 'ri-file-word-fill',
            xls: 'ri-file-excel-fill', xlsx: 'ri-file-excel-fill',
            zip: 'ri-file-zip-fill', rar: 'ri-file-zip-fill',
        };
        return map[ext] || 'ri-file-text-fill';
    }

    function fmStatusBadge(status) {
        var colors = { draft: 'var(--mist)', locked: '#ff9f43', finalized: '#00d2d3', signed: '#10ac84' };
        var labels = { draft: 'Rascunho', locked: 'Bloqueado', finalized: 'Finalizado', signed: 'Assinado' };
        return '<span style="font-size:9px;padding:2px 6px;border-radius:4px;background:' + (colors[status] || 'var(--mist)') + '20;color:' + (colors[status] || 'var(--mist)') + ';font-weight:600;letter-spacing:.03em">' + (labels[status] || status) + '</span>';
    }

    function loadGestorFiles() {
        if (FM.loading) return;
        FM.loading = true;
        var $files = $('#gestorFiles');
        $files.html(skeleton(6));

        // Load folders and documents in parallel
        $.when(
            fmAjax('load_folders'),
            fmAjax('load_documents', {
                folder_id: FM.currentFolder || '',
                search: FM.search,
                per_page: 50,
                page: 1,
            })
        ).done(function(fRes, dRes) {
            var fData = fRes[0];
            var dData = dRes[0];

            FM.folders = (fData && fData.success && fData.data) ? fData.data : [];
            FM.docs = (dData && dData.success && dData.data && dData.data.items) ? dData.data.items : [];

            renderGestorFiles();
        }).fail(function() {
            $files.html('<div class="empty"><i class="ri-error-warning-line"></i><p>Erro ao carregar arquivos</p></div>');
        }).always(function() {
            FM.loading = false;
        });
    }

    function renderGestorFiles() {
        var $files = $('#gestorFiles');

        // Build items: subfolders first, then docs
        var subfolders = FM.folders.filter(function(f) {
            return FM.currentFolder === 0 ? !f.parent : f.parent === FM.currentFolder;
        });

        var items = [];
        subfolders.forEach(function(f) {
            items.push({ id: f.id, title: f.name, _folder: true, count: f.count });
        });
        FM.docs.forEach(function(d) {
            items.push({ id: d.id, title: d.title, status: d.status, version: d.version, access: d.access, author_name: d.author_name, updated_at: d.updated_at, _folder: false });
        });

        if (!items.length) {
            $files.html('<div class="empty" style="padding:40px 0;text-align:center"><i class="ri-folder-open-line" style="font-size:36px;color:var(--mist)"></i><p style="color:var(--ghost);font-size:12px;margin-top:8px">Nenhum arquivo encontrado</p><p style="color:var(--mist);font-size:10px">Crie um documento ou faça upload</p></div>');
            return;
        }

        var html = '';
        if (FM.viewMode === 'grid') {
            html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px">';
            items.forEach(function(item) {
                var icon = item._folder ? 'ri-folder-3-fill' : fmIcon(item.title);
                var color = item._folder ? 'var(--primary)' : 'var(--smoke)';
                html += '<div class="gestor-fm-card" data-id="' + item.id + '" data-type="' + (item._folder ? 'folder' : 'doc') + '" style="background:var(--sf2);border:1px solid var(--brd);border-radius:10px;padding:16px 12px;cursor:pointer;transition:.2s;text-align:center" onmouseover="this.style.borderColor=\'var(--ink)\'" onmouseout="this.style.borderColor=\'var(--brd)\'">';
                html += '<i class="' + icon + '" style="font-size:28px;color:' + color + ';display:block;margin-bottom:8px"></i>';
                html += '<div style="font-size:11px;font-weight:500;color:var(--smoke);white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="' + esc(item.title) + '">' + esc(item.title) + '</div>';
                if (!item._folder && item.status) {
                    html += '<div style="margin-top:4px">' + fmStatusBadge(item.status) + '</div>';
                }
                if (item._folder && item.count) {
                    html += '<div style="font-size:9px;color:var(--mist);margin-top:4px">' + item.count + ' itens</div>';
                }
                html += '</div>';
            });
            html += '</div>';
        } else {
            html = '<table style="width:100%;font-size:11px;border-collapse:collapse">';
            html += '<thead><tr style="border-bottom:1px solid var(--brd);color:var(--mist);font-size:9px;text-transform:uppercase;letter-spacing:.05em">';
            html += '<th style="text-align:left;padding:6px 0">Nome</th><th>Status</th><th>Versão</th><th>Modificado</th><th>Autor</th></tr></thead><tbody>';
            items.forEach(function(item) {
                var icon = item._folder ? 'ri-folder-3-fill' : fmIcon(item.title);
                html += '<tr class="gestor-fm-card" data-id="' + item.id + '" data-type="' + (item._folder ? 'folder' : 'doc') + '" style="border-bottom:1px solid rgba(var(--rgb-t),.03);cursor:pointer" onmouseover="this.style.background=\'var(--sf2)\'" onmouseout="this.style.background=\'transparent\'">';
                html += '<td style="padding:8px 0"><div style="display:flex;align-items:center;gap:6px"><i class="' + icon + '" style="font-size:14px;color:' + (item._folder ? 'var(--primary)' : 'var(--smoke)') + '"></i>' + esc(item.title) + '</div></td>';
                if (item._folder) {
                    html += '<td colspan="4" style="text-align:center;color:var(--mist)">' + (item.count || 0) + ' itens</td>';
                } else {
                    html += '<td style="text-align:center">' + fmStatusBadge(item.status) + '</td>';
                    html += '<td style="text-align:center;font-family:var(--mono);color:var(--mist)">v' + esc(item.version || '1.0') + '</td>';
                    html += '<td style="text-align:center;color:var(--mist)">' + (item.updated_at ? new Date(item.updated_at).toLocaleDateString('pt-BR') : '–') + '</td>';
                    html += '<td style="text-align:center;color:var(--mist)">' + esc(item.author_name || '') + '</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table>';
        }

        $files.html(html);
        animateIn('.gestor-fm-card', { y: 10, duration: 0.2, stagger: 0.02 });
    }

    function renderFmBreadcrumbs() {
        var $bc = $('.gestor-fm-breadcrumbs');
        if (!$bc.length) return;

        var html = '<span class="gestor-fm-crumb' + (FM.currentFolder === 0 ? ' active' : '') + '" data-folder="0" style="cursor:pointer;' + (FM.currentFolder === 0 ? 'color:var(--smoke);font-weight:600' : 'color:var(--mist)') + '">Todos</span>';

        if (FM.currentFolder > 0) {
            var path = [];
            var cur = FM.folders.find(function(f) { return f.id === FM.currentFolder; });
            while (cur) {
                path.unshift(cur);
                cur = cur.parent ? FM.folders.find(function(f) { return f.id === cur.parent; }) : null;
            }
            path.forEach(function(p) {
                html += ' <i class="ri-arrow-right-s-line" style="font-size:10px;color:var(--mist)"></i> ';
                html += '<span class="gestor-fm-crumb" data-folder="' + p.id + '" style="cursor:pointer;color:var(--smoke)">' + esc(p.name) + '</span>';
            });
        }

        $bc.html(html);
    }

    function initGestorFM() {
        // Navigate into folder
        $(document).on('click', '.gestor-fm-card[data-type="folder"]', function() {
            FM.currentFolder = parseInt($(this).data('id'), 10);
            renderFmBreadcrumbs();
            loadGestorFiles();
        });

        // Click doc - open in docs admin page
        $(document).on('dblclick', '.gestor-fm-card[data-type="doc"]', function() {
            var docId = $(this).data('id');
            window.open('admin.php?page=apollo-docs&doc=' + docId, '_blank');
        });

        // Breadcrumb navigation
        $(document).on('click', '.gestor-fm-crumb', function() {
            FM.currentFolder = parseInt($(this).data('folder'), 10);
            renderFmBreadcrumbs();
            loadGestorFiles();
        });

        // View toggle grid/table
        $(document).on('click', '.gestor-fm-view-toggle', function() {
            FM.viewMode = FM.viewMode === 'grid' ? 'table' : 'grid';
            var icon = FM.viewMode === 'grid' ? 'ri-grid-fill' : 'ri-list-unordered';
            $(this).find('i').attr('class', icon);
            renderGestorFiles();
        });

        // Search
        var fmSearchTimer;
        $(document).on('input', '.gestor-fm-search', function() {
            var q = $(this).val();
            clearTimeout(fmSearchTimer);
            fmSearchTimer = setTimeout(function() {
                FM.search = q;
                loadGestorFiles();
            }, 350);
        });

        // Upload
        $(document).on('click', '.gestor-fm-upload', function() {
            $('#gestorFileInput').trigger('click');
        });

        $(document).on('change', '#gestorFileInput', function() {
            var files = this.files;
            if (!files || !files.length) return;
            Array.from(files).forEach(function(file) {
                fmAjax('create_document', {
                    title: file.name,
                    folder_id: FM.currentFolder || '',
                    access: 'private',
                    content: '',
                }).done(function(r) {
                    if (r.success) loadGestorFiles();
                });
            });
            $(this).val('');
        });

        // New folder
        $(document).on('click', '.gestor-fm-new-folder', function() {
            var name = prompt('Nome da pasta:');
            if (!name) return;
            fmAjax('create_folder', { name: name, parent: FM.currentFolder || 0 }).done(function(r) {
                if (r.success) loadGestorFiles();
            });
        });

        // New document
        $(document).on('click', '.gestor-fm-new-doc', function() {
            var title = prompt('Título do documento:');
            if (!title) return;
            fmAjax('create_document', { title: title, folder_id: FM.currentFolder || '', access: 'private', content: '' }).done(function(r) {
                if (r.success) loadGestorFiles();
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * TASK BOARD (dedicated Tarefas tab)
     * ═══════════════════════════════════════════════════════════ */
    function loadTaskBoard(eventId) {
        if (!eventId || eventId === 'overview') {
            $('#taskBoardList').html('<div class="empty"><i class="ri-list-check-2"></i><h3>Selecione um projeto</h3></div>');
            return;
        }

        var $list = $('#taskBoardList');
        $list.html(skeleton(4));
        var ev = S.events.find(function(e) { return e.id === eventId; });
        $('#taskSubtitle').text(ev ? ev.title : 'Projeto #' + eventId);

        var filters = {};
        var fStatus = $('#taskFilterStatus').val();
        var fPriority = $('#taskFilterPriority').val();
        if (fStatus) filters.status = fStatus;
        if (fPriority) filters.priority = fPriority;

        ajax('load_tasks', $.extend({ event_id: eventId }, filters)).done(function(r) {
            if (!r.success) return;
            var tasks = r.data.tasks || [];
            S.tasks = tasks;
            $('#cntTasks').text(tasks.length);

            if (!tasks.length) {
                $list.html('<div class="empty"><i class="ri-check-double-line"></i><h3>Nenhuma tarefa</h3><p>Comece adicionando uma tarefa</p></div>');
                return;
            }

            var prioIcons = { urgent: 'ri-alarm-warning-line', high: 'ri-arrow-up-line', medium: 'ri-subtract-line', low: 'ri-arrow-down-line' };
            var prioColors = { urgent: 'var(--p-urg)', high: 'var(--p-high)', medium: 'var(--p-med)', low: 'var(--p-low)' };

            var html = '';
            tasks.forEach(function(t) {
                var isDone = t.status === 'done';
                var isOver = t.due_date && !isDone && new Date(t.due_date) < new Date();
                html += '<div class="task-row" data-task-id="' + t.id + '">';
                html += '<div class="task-chk' + (isDone ? ' done' : '') + '" data-task-id="' + t.id + '"></div>';
                html += '<div class="task-row-info">';
                html += '<div class="task-row-title' + (isDone ? ' done' : '') + '">' + esc(t.title) + '</div>';
                html += '<div class="task-row-sub">';
                if (t.priority) html += '<i class="' + (prioIcons[t.priority] || 'ri-subtract-line') + '" style="color:' + (prioColors[t.priority] || 'var(--mist)') + ';font-size:11px"></i> ';
                html += esc(t.category || '') + (t.assignee_name ? ' · ' + esc(t.assignee_name) : '');
                html += '</div></div>';
                if (t.due_date) {
                    var df = new Date(t.due_date);
                    var fmt = df.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
                    html += '<div class="task-row-date' + (isOver ? ' overdue' : '') + '">' + fmt + '</div>';
                }
                html += '</div>';
            });
            $list.html(html);
            animateIn('#taskBoardList .task-row', { y: 10, duration: 0.3, stagger: 0.03 });
        });
    }

    function initTaskBoard() {
        // Filters
        $('#taskFilterStatus, #taskFilterPriority').on('change', function() {
            if (S.currentEvent && S.currentEvent !== 'overview') loadTaskBoard(S.currentEvent);
        });

        // Add task from board
        $('#btnTaskBoardAdd').on('click', function() {
            var title = $('#taskBoardNewTitle').val().trim();
            var priority = $('#taskBoardNewPriority').val();
            if (!title || !S.currentEvent || S.currentEvent === 'overview') return;

            ajax('create_task', { event_id: S.currentEvent, title: title, priority: priority }).done(function(r) {
                if (r.success) {
                    $('#taskBoardNewTitle').val('');
                    loadTaskBoard(S.currentEvent);
                    loadAllEvents(function(events) { updateTabCounts(events); });
                }
            });
        });

        $('#taskBoardNewTitle').on('keydown', function(e) {
            if (e.key === 'Enter') $('#btnTaskBoardAdd').trigger('click');
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * FINANCE SUB-TABS (Staff / Suppliers / Budget)
     * ═══════════════════════════════════════════════════════════ */
    function initFinSubTabs() {
        $(document).on('click', '.fin-subtab', function() {
            var $btn = $(this);
            $('.fin-subtab').removeClass('on');
            $btn.addClass('on');

            var type = $btn.data('fin');
            // Update table header based on sub-tab
            switch (type) {
                case 'staff':
                    $('#finTableHead').html('<th>Membro</th><th>Função</th><th>Valor</th><th>PIX</th><th>Status</th>');
                    $('#budgetAddForm').hide();
                    break;
                case 'suppliers':
                    $('#finTableHead').html('<th>Fornecedor</th><th>Serviço</th><th>Valor</th><th>Contato</th><th>Status</th>');
                    $('#budgetAddForm').hide();
                    break;
                case 'budget':
                    $('#finTableHead').html('<th>Descrição</th><th>Categoria</th><th>Valor</th><th>Data</th><th>Status</th>');
                    $('#budgetAddForm').show();
                    break;
            }

            // Reload with filter
            if (S.currentEvent && S.currentEvent !== 'overview') {
                loadPayments(S.currentEvent, type);
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * CALCULATOR
     * ═══════════════════════════════════════════════════════════ */
    function initCalculator() {
        var $overlay = $('#calcOverlay');

        $('#btnCalculator').on('click', function() {
            $overlay.addClass('open');
        });
        $('#calcClose').on('click', function() {
            $overlay.removeClass('open');
        });
        $overlay.on('click', function(e) {
            if ($(e.target).is($overlay)) $overlay.removeClass('open');
        });

        // Live calc
        $('#cHrs, #cRate, #cExtras, #cDisc').on('input', function() {
            var hrs = parseFloat($('#cHrs').val()) || 0;
            var rate = parseFloat($('#cRate').val()) || 0;
            var extras = parseFloat($('#cExtras').val()) || 0;
            var disc = parseFloat($('#cDisc').val()) || 0;
            var total = (hrs * rate + extras) * (1 - disc / 100);
            $('#cResult').text('R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        });

        // Add to financeiro
        $('#calcAdd').on('click', function() {
            var total = parseFloat($('#cResult').text().replace(/[^\d,.-]/g, '').replace(',', '.')) || 0;
            if (total <= 0) return;
            $overlay.removeClass('open');
            // Pre-fill budget add form
            $('#bVal').val(total.toFixed(2));
            // Switch to budget sub-tab
            $('.fin-subtab[data-fin="budget"]').trigger('click');
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * NEW PROJECT MODAL
     * ═══════════════════════════════════════════════════════════ */
    function initNewProject() {
        var $modal = $('#newProjectModal');

        $('#btnNewProject').on('click', function() {
            $modal.addClass('open');
        });
        $('#closeNewProject, #cancelNewProject').on('click', function() {
            $modal.removeClass('open');
        });
        $modal.on('click', function(e) {
            if ($(e.target).is($modal)) $modal.removeClass('open');
        });

        // Create project (creates event CPT post via REST)
        $('#btnCreateProject').on('click', function() {
            var title = $.trim($('#npTitle').val());
            if (!title) { $('#npTitle').focus(); return; }

            var data = {
                title: title,
                date: $('#npDate').val(),
                loc: $.trim($('#npLoc').val()),
                category: $.trim($('#npCategory').val()),
                budget: $('#npBudget').val(),
                description: $.trim($('#npDesc').val())
            };

            ajax('create_event', data).done(function(r) {
                if (r.success) {
                    $modal.removeClass('open');
                    $('#npTitle, #npDate, #npLoc, #npCategory, #npBudget, #npDesc').val('');
                    // Reload and select new project
                    loadAllEvents(function(events) {
                        if (r.data && r.data.id) {
                            S.currentEvent = r.data.id;
                            // Add new option to selector
                            var $sel = $('#evSelect');
                            $sel.append('<option value="' + r.data.id + '">' + esc(title) + '</option>');
                            $sel.val(r.data.id).trigger('change');
                        }
                        updateTabCounts(events);
                    });
                }
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * TEAM SUMMARY (role distribution bars)
     * ═══════════════════════════════════════════════════════════ */
    function renderTeamSummary() {
        if (!S.team.length) {
            $('#teamSummary').hide();
            return;
        }

        var roles = { adm: 0, gestor: 0, tgestor: 0, team: 0 };
        S.team.forEach(function(m) {
            var r = m.role || 'team';
            if (roles[r] !== undefined) roles[r]++;
        });

        var total = S.team.length;
        var roleColors = { adm: 'var(--role-adm)', gestor: 'var(--role-gestor)', tgestor: 'var(--role-tgestor)', team: 'var(--role-team)' };

        var html = '';
        Object.keys(roles).forEach(function(role) {
            var pct = total > 0 ? Math.round((roles[role] / total) * 100) : 0;
            html += '<div class="team-role-bar">';
            html += '<span class="bar-label">' + (ROLE_LABELS[role] || role) + '</span>';
            html += '<div class="bar-track"><div class="bar-fill" style="width:' + pct + '%;background:' + (roleColors[role] || 'var(--mist)') + '"></div></div>';
            html += '<span class="bar-count">' + roles[role] + '</span>';
            html += '</div>';
        });

        $('#teamRoleBars').html(html);
        $('#teamSummary').show();
        animateBars('.team-role-bar .bar-fill');
    }

    /* ═══════════════════════════════════════════════════════════
     * SEARCH (⌘K)
     * ═══════════════════════════════════════════════════════════ */
    function initSearch() {
        $(document).on('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                $('#gestorSearch').focus();
            }
        });

        var searchTimeout;
        $('#gestorSearch').on('input', function() {
            var q = $(this).val().toLowerCase().trim();
            clearTimeout(searchTimeout);
            if (!q) return;

            searchTimeout = setTimeout(function() {
                // Filter visible cards/rows
                $('.ev-card').each(function() {
                    var title = $(this).find('.ev-card-title').text().toLowerCase();
                    $(this).toggle(title.indexOf(q) > -1);
                });
                $('.staff-card').each(function() {
                    var name = $(this).find('.staff-name').text().toLowerCase();
                    $(this).toggle(name.indexOf(q) > -1);
                });
                $('.task-row').each(function() {
                    var title = $(this).find('.task-row-title').text().toLowerCase();
                    $(this).toggle(title.indexOf(q) > -1);
                });
            }, 200);
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * INIT — Boot everything
     * ═══════════════════════════════════════════════════════════ */
    function init() {
        initTabs();
        initEventSelector();
        initSlideOver();
        initCheckboxes();
        initAddTask();
        initPixCopy();
        initPermModal();
        initSearch();
        initDeletePayment();
        initCreateMilestone();
        initTeamEdit();
        initGestorFM();
        initTaskBoard();
        initFinSubTabs();
        initCalculator();
        initNewProject();

        // Initial load
        loadOverview();
        hideLoader();
    }

    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(jQuery, window.ApolloGestor || {}, window.gsap);
