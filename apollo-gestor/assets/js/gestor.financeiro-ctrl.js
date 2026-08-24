/* ═══════════════════════════════════════════════════════════════
   gestor.financeiro-ctrl.js — Controle Financeiro Unificado
   Sub-tabs: Receitas | Ingressos | Staff | Despesas | Resumo Geral
   Depends on: gestor.helpers.js (G namespace), gestor.app.js (GestorStore)
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /* ─── Internal state ────────────────────────────────────────── */
    var _incomeItems  = [];  // Receitas from AJAX load_income
    var _payments     = [];  // All payments from AJAX load_payments
    var _budgetData   = {};  // Budget summary from AJAX load_budget
    var _loaded       = false;
    var _activeCtab   = 'receitas';

    /* ─── Category labels (pt-BR) ───────────────────────────────── */
    var CAT_LABELS = {
        ingressos:  'Ingressos',
        bar:        'Bar / Consumação',
        patrocinio: 'Patrocínio',
        cobertura:  'Cobertura / Cover',
        outros:     'Outros'
    };

    /* ─── Status labels / pill class mapping ────────────────────── */
    var STATUS_LABELS = { paid: 'Pago', pending: 'Pendente', late: 'Atrasado' };
    var STATUS_PILLS  = { paid: 'delivered', pending: 'ongoing', late: 'delayed' };

    window.GestorFinanceCtrl = {

        /**
         * Mask PIX key for display (mirrors PHP Team::mask_pix)
         */
        _maskPix: function (pix) {
            if (!pix) return '';
            if (pix.indexOf('@') !== -1) {
                var parts = pix.split('@');
                return parts[0].substring(0, 2) + '***@' + parts[1];
            }
            if (/^\d{11}$/.test(pix)) {
                return 'CPF ***' + pix.substring(3, 6) + '***';
            }
            var clean = pix.replace(/\D/g, '');
            if (/^\d{10,}$/.test(clean)) {
                return '(' + clean.substring(0, 2) + ') 9***-' + clean.substring(clean.length - 4);
            }
            if (pix.length > 6) return pix.substring(0, 3) + '***' + pix.substring(pix.length - 3);
            return '***';
        },

        /* ── Bootstrap ──────────────────────────────────────────── */
        init: function () {
            var panel = G.$('#panel-ctrl-financeiro');
            if (!panel) return;

            this._bindTabNav();
            this._bindForms();

            /* Trigger first load when this main panel is activated */
            var mainTabBtn = G.$('[data-tab="panel-ctrl-financeiro"]');
            if (mainTabBtn) {
                G.on(mainTabBtn, 'click', this._maybeLoad.bind(this));
            }

            /* If already visible on page (e.g. direct URL hash), load now */
            if (panel.style.display !== 'none' && panel.classList.contains('active')) {
                this._maybeLoad();
            }
        },

        /* ── Lazy load: fetch once per page session ─────────────── */
        _maybeLoad: function () {
            if (_loaded) return;
            this._loadAll();
        },

        _loadAll: function () {
            var eventId = (window.GestorStore && window.GestorStore.currentEventId) || 0;
            if (!eventId) {
                G.toast('Selecione um evento primeiro', 'error');
                return;
            }

            var self = this;

            Promise.all([
                G.ajax('load_income',   { event_id: eventId }),
                G.ajax('load_payments', { event_id: eventId }),
                G.ajax('load_budget',   { event_id: eventId })
            ]).then(function (results) {
                _incomeItems = (results[0] && results[0].data && results[0].data.items) || [];
                _payments    = (results[1] && results[1].data && results[1].data.payments) || [];
                _budgetData  = (results[2] && results[2].data) || {};
                _loaded      = true;

                self._renderAll();
            }).catch(function () {
                G.toast('Erro ao carregar dados financeiros', 'error');
            });
        },

        _renderAll: function () {
            this._renderReceitas();
            this._renderIngressos();
            this._renderStaff();
            this._renderDespesas();
            this._renderResumo();
        },

        /* ── Sub-tab navigation ──────────────────────────────────── */
        _bindTabNav: function () {
            var self = this;
            G.$$('.ctrl-fin-tab').forEach(function (btn) {
                G.on(btn, 'click', function () {
                    var ctab = btn.dataset.ctab;
                    if (!ctab) return;
                    self._switchCtab(ctab);
                });
            });
        },

        _switchCtab: function (ctab) {
            _activeCtab = ctab;

            /* Tab button state */
            G.$$('.ctrl-fin-tab').forEach(function (b) {
                var isActive = b.dataset.ctab === ctab;
                b.classList.toggle('on', isActive);
                b.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            /* Panel visibility */
            G.$$('.ctrl-fin-panel').forEach(function (p) {
                var show = p.id === 'ctab-' + ctab;
                p.style.display = show ? '' : 'none';
            });

            /* Lazy initial load on first activation of tab */
            if (!_loaded) this._loadAll();
        },

        /* ── Forms ───────────────────────────────────────────────── */
        _bindForms: function () {
            var self = this;

            /* Receitas form */
            var incomeForm = G.$('#cfinIncomeForm');
            if (incomeForm) {
                G.on(incomeForm, 'submit', function (e) {
                    e.preventDefault();
                    self._submitIncome();
                });
            }

            /* Ingressos (ticket) form — calls same income endpoint with cat=ingressos */
            var ticketForm = G.$('#cfinTicketForm');
            if (ticketForm) {
                G.on(ticketForm, 'submit', function (e) {
                    e.preventDefault();
                    self._submitTicket();
                });
            }
        },

        _submitIncome: function () {
            var eventId  = (window.GestorStore && window.GestorStore.currentEventId) || 0;
            var cat      = (G.$('#cfinIncomeCat')  || {}).value || 'outros';
            var desc     = ((G.$('#cfinIncomeDesc') || {}).value || '').trim();
            var amt      = parseFloat((G.$('#cfinIncomeAmt')  || {}).value) || 0;
            var date     = (G.$('#cfinIncomeDate') || {}).value || '';

            if (!desc || amt <= 0) {
                G.toast('Preencha descrição e valor', 'error');
                return;
            }

            var self = this;
            G.ajax('create_income', {
                event_id:    eventId,
                category:    cat,
                description: desc,
                amount:      amt,
                date:        date
            }).then(function (res) {
                var item = res && res.data && res.data.item;
                if (item) {
                    _incomeItems.push(item);
                    self._renderReceitas();
                    self._renderIngressos();
                    self._renderResumo();
                    G.$('#cfinIncomeForm') && G.$('#cfinIncomeForm').reset();
                    G.toast('Receita adicionada', 'success');
                }
            }).catch(function () {
                G.toast('Erro ao salvar receita', 'error');
            });
        },

        _submitTicket: function () {
            var eventId  = (window.GestorStore && window.GestorStore.currentEventId) || 0;
            var desc     = ((G.$('#cfinTicketDesc') || {}).value || '').trim();
            var qty      = parseInt((G.$('#cfinTicketQtd')  || {}).value)  || 1;
            var unit     = parseFloat((G.$('#cfinTicketUnit') || {}).value) || 0;
            var date     = (G.$('#cfinTicketDate') || {}).value || '';

            if (!desc || unit <= 0) {
                G.toast('Preencha descrição e valor unitário', 'error');
                return;
            }

            var totalAmt = qty * unit;
            var self = this;

            G.ajax('create_income', {
                event_id:    eventId,
                category:    'ingressos',
                description: desc,
                amount:      totalAmt,
                qty:         qty,
                unit:        unit,
                date:        date
            }).then(function (res) {
                var item = res && res.data && res.data.item;
                if (item) {
                    _incomeItems.push(item);
                    self._renderReceitas();
                    self._renderIngressos();
                    self._renderResumo();
                    G.$('#cfinTicketForm') && G.$('#cfinTicketForm').reset();
                    G.toast('Lote registrado', 'success');
                }
            }).catch(function () {
                G.toast('Erro ao salvar lote', 'error');
            });
        },

        /* ── Render: Receitas sub-panel ──────────────────────────── */
        _renderReceitas: function () {
            var tbody = G.$('#cfinReceitasBody');
            var empty = G.$('#cfinReceitasEmpty');
            if (!tbody) return;

            var self    = this;
            var catTots = { ingressos: 0, bar: 0, patrocinio: 0, cobertura: 0, outros: 0 };
            var total   = 0;

            tbody.innerHTML = '';

            _incomeItems.forEach(function (item) {
                total += item.amount || 0;
                var cat = item.category || 'outros';
                if (catTots[cat] !== undefined) catTots[cat] += item.amount || 0;
                else catTots.outros += item.amount || 0;

                var tr = document.createElement('tr');
                tr.dataset.incomeId = item.id || '';

                tr.innerHTML =
                    '<td>' + G.escapeHtml(item.date || '—') + '</td>' +
                    '<td><span class="status-pill ' + G.escapeHtml(cat) + '">' + G.escapeHtml(CAT_LABELS[cat] || cat) + '</span></td>' +
                    '<td>' + G.escapeHtml(item.description || '') + '</td>' +
                    '<td style="font-family:var(--ff-mono)">' + G.formatCurrency(item.amount || 0) + '</td>' +
                    '<td></td>';

                var delBtn = document.createElement('button');
                delBtn.className = 'btn btn-icon btn-danger';
                delBtn.setAttribute('aria-label', 'Excluir receita');
                delBtn.innerHTML = '<i class="ri-delete-bin-line"></i>';
                delBtn.addEventListener('click', function () {
                    self._deleteIncome(tr, item.id);
                });
                tr.lastElementChild.appendChild(delBtn);

                tbody.appendChild(tr);
            });

            /* KPI strip */
            this._setText('cfinReceitasTotal',    G.formatCurrency(total));
            this._setText('cfinReceitasIngressos', G.formatCurrency(catTots.ingressos));
            this._setText('cfinReceitasBar',       G.formatCurrency(catTots.bar));
            this._setText('cfinReceitasOutros',    G.formatCurrency(catTots.patrocinio + catTots.cobertura + catTots.outros));

            /* Empty state */
            if (empty) empty.style.display = _incomeItems.length ? 'none' : '';
        },

        /* ── Render: Ingressos sub-panel ─────────────────────────── */
        _renderIngressos: function () {
            var tbody = G.$('#cfinTicketsBody');
            var empty = G.$('#cfinTicketsEmpty');
            if (!tbody) return;

            var self    = this;
            var tickets = _incomeItems.filter(function (i) { return i.category === 'ingressos'; });
            var total   = 0;
            var totalQ  = 0;

            tbody.innerHTML = '';

            tickets.forEach(function (item) {
                total  += item.amount || 0;
                var qty  = (item.qty && item.qty > 0) ? item.qty : 1;
                var unit = item.unit || item.amount || 0;
                totalQ  += qty;

                var tr = document.createElement('tr');
                tr.dataset.incomeId = item.id || '';

                tr.innerHTML =
                    '<td>' + G.escapeHtml(item.date || '—') + '</td>' +
                    '<td>' + G.escapeHtml(item.description || '') + '</td>' +
                    '<td style="font-family:var(--ff-mono)">' + qty + '</td>' +
                    '<td style="font-family:var(--ff-mono)">' + G.formatCurrency(unit) + '</td>' +
                    '<td style="font-family:var(--ff-mono)">' + G.formatCurrency(item.amount || 0) + '</td>' +
                    '<td></td>';

                var delBtn = document.createElement('button');
                delBtn.className = 'btn btn-icon btn-danger';
                delBtn.setAttribute('aria-label', 'Excluir lote');
                delBtn.innerHTML = '<i class="ri-delete-bin-line"></i>';
                delBtn.addEventListener('click', function () {
                    self._deleteIncome(tr, item.id);
                });
                tr.lastElementChild.appendChild(delBtn);

                tbody.appendChild(tr);
            });

            var medio = (tickets.length > 0) ? total / tickets.length : 0;

            this._setText('cfinTicketsTotal', G.formatCurrency(total));
            this._setText('cfinTicketsQtd',   String(totalQ));
            this._setText('cfinTicketsMedio', G.formatCurrency(medio));

            if (empty) empty.style.display = tickets.length ? 'none' : '';
        },

        /* ── Render: Staff sub-panel ─────────────────────────────── */
        _renderStaff: function () {
            var body  = G.$('#cfinStaffBody');
            var empty = G.$('#cfinStaffEmpty');
            if (!body) return;

            var self = this;
            var staffPayments = _payments.filter(function (p) { return p.payee_type === 'staff'; });
            var total = 0, paid = 0, pending = 0;

            body.innerHTML = '';

            staffPayments.forEach(function (p) {
                var amt     = parseFloat(p.amount)      || 0;
                var paidAmt = parseFloat(p.paid_amount) || 0;
                total += amt;
                if (p.status === 'paid') paid    += amt;
                else                     pending += amt;

                var pct        = amt > 0 ? Math.min(Math.round((paidAmt / amt) * 100), 100) : 0;
                var status     = p.status || 'pending';
                var pillClass  = STATUS_PILLS[status]  || 'ongoing';
                var statusLabel = STATUS_LABELS[status] || status;
                var avatar     = p.payee_avatar || '';
                var name       = p.payee_name || p.description || '—';
                var email      = p.payee_email || '';

                var row = document.createElement('div');
                row.className = 'stf-row';
                row.dataset.paymentId = p.id || '';

                /* Member cell */
                var memberCell = document.createElement('div');
                memberCell.className = 'stf-cell stf-cell--member';
                var link = document.createElement('a');
                link.className = 'stf-member-link';
                link.href = '#';
                link.setAttribute('role', 'button');
                link.innerHTML =
                    (avatar ? '<img class="stf-avatar" src="' + G.escapeHtml(avatar) + '" alt="">' : '<span class="stf-avatar stf-avatar--placeholder"><i class="ri-user-line"></i></span>') +
                    '<span class="stf-member-data">' +
                        '<span class="stf-member-name">' + G.escapeHtml(name) + '</span>' +
                        (email ? '<span class="stf-member-email">' + G.escapeHtml(email) + '</span>' : '') +
                    '</span>';
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    self._openStaffPayModal(p);
                });
                memberCell.appendChild(link);

                /* Role cell */
                var roleCell = document.createElement('div');
                roleCell.className = 'stf-cell stf-cell--role';
                roleCell.textContent = p.category || '—';

                /* Progress cell */
                var progCell = document.createElement('div');
                progCell.className = 'stf-cell stf-cell--progress';
                progCell.innerHTML =
                    '<div class="stf-progress-bar"><span style="width:' + pct + '%"></span></div>' +
                    '<span class="stf-progress-value">' + pct + '%</span>';

                /* PIX cell — masked for security */
                var pixCell = document.createElement('div');
                pixCell.className = 'stf-cell stf-cell--pix';
                pixCell.innerHTML = '<code>' + G.escapeHtml(self._maskPix(p.pix_key) || '—') + '</code>';

                /* Status cell */
                var statusCell = document.createElement('div');
                statusCell.className = 'stf-cell stf-cell--status';
                statusCell.innerHTML = '<span class="status-pill ' + G.escapeHtml(pillClass) + '">' + G.escapeHtml(statusLabel) + '</span>';

                /* Amount cell */
                var amtCell = document.createElement('div');
                amtCell.className = 'stf-cell stf-cell--amount';
                amtCell.innerHTML = '<span style="font-family:var(--ff-mono)">' + G.formatCurrency(amt) + '</span>';

                row.appendChild(memberCell);
                row.appendChild(roleCell);
                row.appendChild(progCell);
                row.appendChild(pixCell);
                row.appendChild(statusCell);
                row.appendChild(amtCell);
                body.appendChild(row);
            });

            this._setText('cfinStaffTotal',    G.formatCurrency(total));
            this._setText('cfinStaffPago',     G.formatCurrency(paid));
            this._setText('cfinStaffPendente', G.formatCurrency(pending));
            this._setText('cfinStaffCount', String(staffPayments.length));

            if (empty) empty.style.display = staffPayments.length ? 'none' : '';

            /* Bind search once */
            if (!this._staffSearchBound) {
                this._staffSearchBound = true;
                var searchInput = G.$('#cfinStaffSearch');
                if (searchInput) {
                    G.on(searchInput, 'input', function () {
                        self._filterStaffRows(searchInput.value);
                    });
                }
            }
        },

        /* ── Staff: search filter ────────────────────────────────── */
        _filterStaffRows: function (term) {
            var rows   = G.$$('#cfinStaffBody .stf-row');
            var needle = (term || '').toLowerCase();
            var count  = 0;

            rows.forEach(function (row) {
                var nameEl = row.querySelector('.stf-member-name');
                var name   = nameEl ? nameEl.textContent.toLowerCase() : '';
                var show   = !needle || name.indexOf(needle) !== -1;
                row.style.display = show ? '' : 'none';
                if (show) count++;
            });

            this._setText('cfinStaffCount', String(count));
        },

        /* ── Staff: open payment modal ───────────────────────────── */
        _openStaffPayModal: function (p) {
            var modal = G.$('#staffPayModal');
            if (!modal) return;

            var amt     = parseFloat(p.amount)      || 0;
            var paidAmt = parseFloat(p.paid_amount) || 0;
            var pct     = amt > 0 ? Math.min(Math.round((paidAmt / amt) * 100), 100) : 0;
            var remain  = Math.max(amt - paidAmt, 0);
            var eventId = (window.GestorStore && window.GestorStore.currentEventId) || 0;

            /* Populate fields */
            var avatarEl = G.$('#stfmAvatar');
            if (avatarEl) {
                avatarEl.src = p.payee_avatar || '';
                avatarEl.style.display = p.payee_avatar ? '' : 'none';
            }
            this._setText('stfmName', p.payee_name || p.description || '—');
            this._setText('stfmRole', p.category || '');
            this._setText('stfmPix',  this._maskPix(p.pix_key) || '—');

            /* Store real PIX on modal for Copy button */
            if (modal) modal.dataset.pixReal = p.pix_key || '';

            /* Progress */
            this._setText('stfmProgressPct', pct + '%');
            var bar = G.$('#stfmProgressBar');
            if (bar) bar.style.width = pct + '%';
            this._setText('stfmPaidSoFar',  G.formatCurrency(paidAmt));
            this._setText('stfmTotalAmount', G.formatCurrency(amt));

            /* Input */
            var payInput = G.$('#stfmPayValue');
            if (payInput) payInput.value = paidAmt > 0 ? paidAmt : '';
            this._setText('stfmRemaining', G.formatCurrency(remain));

            /* Hidden IDs */
            var idField = G.$('#stfmPaymentId');
            if (idField) idField.value = p.id || '';
            var evField = G.$('#stfmEventId');
            if (evField) evField.value = eventId;

            /* Status buttons */
            var status = p.status || 'pending';
            G.$$('.stfm-status-btn').forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.stfmStatus === status);
            });

            /* Store reference to live-update modal amounts */
            this._modalPayment = { amount: amt, paid_amount: paidAmt, status: status };

            modal.classList.add('on');

            /* Bind modal interactions once */
            if (!this._modalBound) {
                this._modalBound = true;
                this._bindStaffPayModal();
            }
        },

        /* ── Staff: bind all modal events ────────────────────────── */
        _bindStaffPayModal: function () {
            var self  = this;
            var modal = G.$('#staffPayModal');

            /* Close buttons */
            var close = function () {
                if (modal) modal.classList.remove('on');
            };
            var closeBtn = G.$('#staffPayModalClose');
            var cancelBtn = G.$('#stfmCancel');
            if (closeBtn)  G.on(closeBtn,  'click', close);
            if (cancelBtn) G.on(cancelBtn, 'click', close);

            /* Click overlay to close */
            if (modal) {
                G.on(modal, 'click', function (e) {
                    if (e.target === modal) close();
                });
            }

            /* Status buttons */
            G.$$('.stfm-status-btn').forEach(function (btn) {
                G.on(btn, 'click', function () {
                    G.$$('.stfm-status-btn').forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    if (self._modalPayment) self._modalPayment.status = btn.dataset.stfmStatus;
                });
            });

            /* Live paid_amount input → update remaining + progress */
            var payInput = G.$('#stfmPayValue');
            if (payInput) {
                G.on(payInput, 'input', function () {
                    if (!self._modalPayment) return;
                    var val    = parseFloat(payInput.value) || 0;
                    var amt    = self._modalPayment.amount || 0;
                    var remain = Math.max(amt - val, 0);
                    var pct    = amt > 0 ? Math.min(Math.round((val / amt) * 100), 100) : 0;

                    self._setText('stfmRemaining',   G.formatCurrency(remain));
                    self._setText('stfmPaidSoFar',   G.formatCurrency(val));
                    self._setText('stfmProgressPct', pct + '%');
                    var bar = G.$('#stfmProgressBar');
                    if (bar) bar.style.width = pct + '%';

                    /* Auto-set status to paid when 100% */
                    if (val >= amt && amt > 0) {
                        G.$$('.stfm-status-btn').forEach(function (b) {
                            b.classList.toggle('active', b.dataset.stfmStatus === 'paid');
                        });
                        self._modalPayment.status = 'paid';
                    }
                });
            }

            /* Copy PIX — copies the REAL key (stored on modal data attr), with confirmation */
            var copyBtn = G.$('#stfmCopyPix');
            if (copyBtn) {
                G.on(copyBtn, 'click', function () {
                    var realPix = modal.dataset.pixReal || '';
                    if (!realPix) {
                        G.toast('Nenhuma chave PIX cadastrada', 'error');
                        return;
                    }
                    if (!confirm('Copiar chave PIX para a área de transferência?')) return;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(realPix).then(function () {
                            G.toast('PIX copiado!', 'success');
                        });
                    }
                });
            }

            /* Save */
            var saveBtn = G.$('#stfmSave');
            if (saveBtn) {
                G.on(saveBtn, 'click', function () {
                    self._saveStaffPayment();
                });
            }
        },

        /* ── Staff: persist payment update via AJAX ──────────────── */
        _saveStaffPayment: function () {
            var paymentId = (G.$('#stfmPaymentId') || {}).value;
            var eventId   = (G.$('#stfmEventId')   || {}).value;
            var paidAmt   = parseFloat((G.$('#stfmPayValue') || {}).value) || 0;

            /* Determine active status */
            var status = 'pending';
            G.$$('.stfm-status-btn').forEach(function (btn) {
                if (btn.classList.contains('active')) status = btn.dataset.stfmStatus;
            });

            if (!paymentId) {
                G.toast('Erro: pagamento não identificado', 'error');
                return;
            }

            var self = this;
            G.ajax('update_payment', {
                event_id:    eventId,
                payment_id:  paymentId,
                paid_amount: paidAmt,
                status:      status
            }).then(function () {
                /* Update local cache */
                _payments.forEach(function (p) {
                    if (String(p.id) === String(paymentId)) {
                        p.paid_amount = paidAmt;
                        p.status      = status;
                    }
                });

                var modal = G.$('#staffPayModal');
                if (modal) modal.classList.remove('on');

                self._renderStaff();
                self._renderResumo();
                G.toast('Pagamento atualizado', 'success');
            }).catch(function () {
                G.toast('Erro ao salvar pagamento', 'error');
            });
        },

        /* ── Render: Despesas sub-panel ──────────────────────────── */
        _renderDespesas: function () {
            var tbody = G.$('#cfinDespesasBody');
            var empty = G.$('#cfinDespesasEmpty');
            if (!tbody) return;

            /* Non-staff payments (suppliers, production, others) */
            var despesas = _payments.filter(function (p) { return p.payee_type !== 'staff'; });
            var total = 0, paid = 0, pending = 0;

            tbody.innerHTML = '';

            despesas.forEach(function (p) {
                total   += parseFloat(p.amount) || 0;
                if (p.status === 'paid')  paid    += parseFloat(p.amount) || 0;
                else                       pending += parseFloat(p.amount) || 0;

                var status      = p.status || 'pending';
                var pillClass   = STATUS_PILLS[status]  || 'ongoing';
                var statusLabel = STATUS_LABELS[status] || status;

                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + G.escapeHtml(p.description || '—') + '</td>' +
                    '<td>' + G.escapeHtml(p.category || '—') + '</td>' +
                    '<td>' + G.escapeHtml(p.payee_name || '—') + '</td>' +
                    '<td style="font-family:var(--ff-mono)">' + G.formatCurrency(parseFloat(p.amount) || 0) + '</td>' +
                    '<td><span class="status-pill ' + G.escapeHtml(pillClass) + '">' + G.escapeHtml(statusLabel) + '</span></td>';

                tbody.appendChild(tr);
            });

            /* Budget saldo from budget data */
            var budget  = parseFloat(_budgetData.budget || 0);
            var saldo   = budget - total;

            this._setText('cfinDespesasTotal',   G.formatCurrency(total));
            this._setText('cfinDespesasPago',     G.formatCurrency(paid));
            this._setText('cfinDespesasPendente', G.formatCurrency(pending));
            this._setText('cfinDespesasSaldo',    G.formatCurrency(saldo));

            if (empty) empty.style.display = despesas.length ? 'none' : '';
        },

        /* ── Render: Resumo Geral ────────────────────────────────── */
        _renderResumo: function () {
            /* Aggregate totals */
            var receitasTotal = _incomeItems.reduce(function (s, i) { return s + (parseFloat(i.amount) || 0); }, 0);

            var catTots = { ingressos: 0, bar: 0, patrocinio: 0, cobertura: 0, outros: 0 };
            _incomeItems.forEach(function (i) {
                var c = i.category || 'outros';
                if (catTots[c] !== undefined) catTots[c] += parseFloat(i.amount) || 0;
                else catTots.outros += parseFloat(i.amount) || 0;
            });

            var staffTotal  = 0;
            var supplierTotal = 0;
            _payments.forEach(function (p) {
                var amt = parseFloat(p.amount) || 0;
                if (p.payee_type === 'staff') staffTotal    += amt;
                else                           supplierTotal += amt;
            });

            var totalSaidas  = staffTotal + supplierTotal;
            var saldoLiquido = receitasTotal - totalSaidas;

            /* KPIs */
            this._setText('cfinResEntradas', G.formatCurrency(receitasTotal));
            this._setText('cfinResSaidas',   G.formatCurrency(totalSaidas));

            /* Balance hero */
            var heroEl = G.$('#cfinSaldoLiquido');
            if (heroEl) {
                heroEl.textContent = G.formatCurrency(saldoLiquido);
                heroEl.style.color = saldoLiquido >= 0 ? 'var(--s-delivered)' : 'var(--s-delayed)';
            }

            var subEl = G.$('#cfinSaldoSub');
            if (subEl) {
                subEl.textContent = saldoLiquido >= 0 ? 'Evento superavitário' : 'Evento deficitário';
                subEl.style.color = saldoLiquido >= 0 ? 'var(--s-delivered)' : 'var(--s-delayed)';
            }

            /* Breakdown */
            this._setText('resBdIngressos',  G.formatCurrency(catTots.ingressos));
            this._setText('resBdBar',        G.formatCurrency(catTots.bar));
            this._setText('resBdPatrocinio', G.formatCurrency(catTots.patrocinio + catTots.cobertura));
            this._setText('resBdOutros',     G.formatCurrency(catTots.outros));
            this._setText('resBdStaff',      G.formatCurrency(staffTotal));
            this._setText('resBdFornecedores', G.formatCurrency(supplierTotal));
            this._setText('resBdBudget',     G.formatCurrency(supplierTotal));

            /* Detail table */
            this._setText('resDetReceitas', G.formatCurrency(receitasTotal));
            this._setText('resDetDespesas', G.formatCurrency(supplierTotal));
            this._setText('resDetStaff',    G.formatCurrency(staffTotal));
            this._setText('resDetSaldo',    G.formatCurrency(saldoLiquido));

            /* Bar comparison */
            var maxVal = Math.max(receitasTotal, totalSaidas, 1);
            var inPct  = Math.round((receitasTotal / maxVal) * 100);
            var outPct = Math.round((totalSaidas   / maxVal) * 100);

            var barIn  = G.$('#cfinBarIn');
            var barOut = G.$('#cfinBarOut');
            if (barIn)  barIn.style.width  = inPct  + '%';
            if (barOut) barOut.style.width = outPct + '%';

            this._setText('cfinBarInPct',  inPct + '%');
            this._setText('cfinBarOutPct', outPct + '%');
        },

        /* ── Delete income item ──────────────────────────────────── */
        _deleteIncome: function (tr, incomeId) {
            if (!incomeId) { tr.remove(); return; }

            var eventId = (window.GestorStore && window.GestorStore.currentEventId) || 0;
            var self    = this;

            G.ajax('delete_income', { event_id: eventId, income_id: incomeId }).then(function () {
                _incomeItems = _incomeItems.filter(function (i) { return i.id !== incomeId; });
                tr.remove();
                self._renderReceitas();
                self._renderIngressos();
                self._renderResumo();
                G.toast('Receita removida', 'success');
            }).catch(function () {
                G.toast('Erro ao remover receita', 'error');
            });
        },

        /* ── Utility ─────────────────────────────────────────────── */
        _setText: function (id, text) {
            var el = G.$('#' + id);
            if (el) el.textContent = text;
        },

        /** Public refresh (callable from gestor.app.js on event change) */
        refresh: function () {
            _loaded      = false;
            _incomeItems = [];
            _payments    = [];
            _budgetData  = {};
            this._loadAll();
        }
    };

    /* ── React to event change ────────────────────────────────── */
    document.addEventListener('gestor:event-changed', function () {
        if (window.GestorFinanceCtrl) {
            window.GestorFinanceCtrl.refresh();
        }
    });

})();
