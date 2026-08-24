/* ═══════════════════════════════════════════════════════════════
   gestor.budget.js — Budget CRUD (Add items, recalculate)
   Persists via AJAX: create_payment / delete_payment
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorBudget = {
        init: function () {
            var self = this;
            var form = G.$('#budgetAddForm');
            if (!form) return;

            G.on(form, 'submit', function (e) {
                e.preventDefault();

                var desc   = (G.$('#bDesc')  || {}).value || '';
                var val    = parseFloat((G.$('#bVal') || {}).value) || 0;
                var status = (G.$('#bStatus') || {}).value || 'Planejado';
                var resp   = (G.$('#bResp')   || {}).value || '-';

                desc = desc.trim();
                resp = resp.trim();

                if (!desc || !val) {
                    G.toast('Preencha descrição e valor', 'error');
                    return;
                }

                /* Persist to backend */
                var store = window.GestorStore;
                var eventId = store && store.currentEventId ? store.currentEventId : 0;

                G.ajax('create_payment', {
                    event_id:    eventId,
                    description: desc,
                    amount:      val,
                    status:      status,
                    responsible: resp
                }).then(function (res) {
                    var tbody = G.$('#budgetBody');
                    if (!tbody) return;

                    var tr = document.createElement('tr');
                    var payId      = (res && res.data && res.data.id) || 0;
                    var safeDesc   = G.escapeHtml(desc);
                    var safeResp   = G.escapeHtml(resp);
                    var safePill   = G.escapeHtml(status.toLowerCase());
                    var safeStatus = G.escapeHtml(status);

                    tr.dataset.paymentId = payId;
                    tr.innerHTML =
                        '<td>' + safeDesc + '</td>' +
                        '<td style="font-family:var(--ff-mono)">' + G.formatCurrency(val) + '</td>' +
                        '<td><span class="status-pill ' + safePill + '">' + safeStatus + '</span></td>' +
                        '<td>' + safeResp + '</td>' +
                        '<td></td>';

                    /* Delete button */
                    var delBtn = document.createElement('button');
                    delBtn.className = 'btn btn-icon btn-danger';
                    delBtn.innerHTML = '<i class="ri-delete-bin-line"></i>';
                    delBtn.addEventListener('click', function () {
                        self._deleteRow(tr);
                    });
                    tr.lastElementChild.appendChild(delBtn);

                    tbody.appendChild(tr);
                    G.toast('Item adicionado ao budget', 'success');
                    form.reset();
                    self.recalc();
                }).catch(function () {
                    G.toast('Erro ao salvar pagamento', 'error');
                });
            });

            /* Attach delete handlers to existing rows */
            G.$$('#budgetBody .btn-danger').forEach(function (btn) {
                G.on(btn, 'click', function () {
                    var row = btn.closest('tr');
                    if (row) self._deleteRow(row);
                });
            });
        },

        _deleteRow: function (tr) {
            var self = this;
            var payId = tr.dataset.paymentId;
            if (payId) {
                G.ajax('delete_payment', { payment_id: payId }).then(function () {
                    tr.remove();
                    self.recalc();
                    G.toast('Item removido', 'success');
                }).catch(function () {
                    G.toast('Erro ao remover', 'error');
                });
            } else {
                tr.remove();
                self.recalc();
                G.toast('Item removido', 'success');
            }
        },

        recalc: function () {
            var total = 0;
            G.$$('#budgetBody tr').forEach(function (tr) {
                var cells = G.$$('td', tr);
                var text  = cells[1] ? cells[1].textContent : '0';
                var val   = parseFloat(text.replace(/[^\d,.\-]/g, '').replace(',', '.')) || 0;
                total += val;
            });
            var el = G.$('#budgetCommitted');
            if (el) el.textContent = G.formatCurrency(total);
        }
    };
})();
