/* ═══════════════════════════════════════════════════════════════
   gestor.kanban.js — Kanban Board with Drag & Drop
   HTML5 native DnD + Touch polyfill for mobile/tablet
   Persists via AJAX: reorder_tasks + update_event_status
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /* ── Touch DnD Polyfill (long-press → drag) ─────────────── */
    var Touch = {
        HOLD_MS: 250,
        _timer: null,
        _dragging: false,
        _clone: null,
        _source: null,
        _offsetX: 0,
        _offsetY: 0,

        bind: function (card, kanban) {
            var self = this;

            card.addEventListener('touchstart', function (e) {
                if (e.touches.length !== 1) return;
                var t = e.touches[0];
                self._source = card;
                self._offsetX = t.clientX - card.getBoundingClientRect().left;
                self._offsetY = t.clientY - card.getBoundingClientRect().top;

                self._timer = setTimeout(function () {
                    self._startDrag(card, t, kanban);
                }, self.HOLD_MS);
            }, { passive: true });

            card.addEventListener('touchmove', function (e) {
                if (!self._dragging) {
                    clearTimeout(self._timer);
                    return;
                }
                e.preventDefault();
                var t = e.touches[0];
                self._moveClone(t);
                self._highlightColumn(t, kanban);
            }, { passive: false });

            card.addEventListener('touchend', function (e) {
                clearTimeout(self._timer);
                if (!self._dragging) return;
                self._drop(kanban);
            }, { passive: true });

            card.addEventListener('touchcancel', function () {
                clearTimeout(self._timer);
                self._cleanup();
            }, { passive: true });
        },

        _startDrag: function (card, touch, kanban) {
            this._dragging = true;
            card.classList.add('dragging');
            kanban._dragged = card;

            /* Ghost clone follows finger */
            this._clone = card.cloneNode(true);
            this._clone.classList.add('touch-ghost');
            this._clone.style.cssText = 'position:fixed;z-index:9999;pointer-events:none;opacity:.85;' +
                'width:' + card.offsetWidth + 'px;transform:rotate(2deg);' +
                'left:' + (touch.clientX - this._offsetX) + 'px;' +
                'top:' + (touch.clientY - this._offsetY) + 'px;';
            document.body.appendChild(this._clone);

            /* Haptic feedback if available */
            if (navigator.vibrate) navigator.vibrate(30);
        },

        _moveClone: function (touch) {
            if (!this._clone) return;
            this._clone.style.left = (touch.clientX - this._offsetX) + 'px';
            this._clone.style.top  = (touch.clientY - this._offsetY) + 'px';
        },

        _highlightColumn: function (touch, kanban) {
            var cols = G.$$('.kan-col');
            cols.forEach(function (col) {
                var r = col.getBoundingClientRect();
                if (touch.clientX >= r.left && touch.clientX <= r.right &&
                    touch.clientY >= r.top  && touch.clientY <= r.bottom) {
                    col.classList.add('drag-over');
                } else {
                    col.classList.remove('drag-over');
                }
            });
        },

        _drop: function (kanban) {
            var cols = G.$$('.kan-col');
            var target = null;

            /* Find which column the finger ended over */
            cols.forEach(function (col) {
                if (col.classList.contains('drag-over')) target = col;
                col.classList.remove('drag-over');
            });

            if (target && this._source) {
                target.appendChild(this._source);
                var newStatus = target.dataset.status || '';
                var cardId    = this._source.dataset.id || '';

                if (newStatus && cardId) {
                    kanban._persistMove(cardId, newStatus, target);
                }
                G.toast('Card movido!', 'success');
            }

            this._cleanup();
            kanban.updateCounts();
        },

        _cleanup: function () {
            this._dragging = false;
            if (this._clone && this._clone.parentNode) {
                this._clone.parentNode.removeChild(this._clone);
            }
            if (this._source) this._source.classList.remove('dragging');
            this._clone  = null;
            this._source = null;
        }
    };

    window.GestorKanban = {
        _dragged: null,

        init: function () {
            this._bindColumns();

            /* Re-bind when event changes */
            G.on(document, 'gestor:event-changed', function () {
                window.GestorKanban._bindColumns();
            });
        },

        _bindColumns: function () {
            var self  = this;
            var cols  = G.$$('.kan-col');
            var cards = G.$$('.ev-card');

            cards.forEach(function (card) {
                card.setAttribute('draggable', 'true');

                /* ── HTML5 DnD (desktop) ── */
                G.on(card, 'dragstart', function (e) {
                    self._dragged = card;
                    card.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', card.dataset.id || '');
                });

                G.on(card, 'dragend', function () {
                    card.classList.remove('dragging');
                    cols.forEach(function (c) { c.classList.remove('drag-over'); });
                    self._dragged = null;
                    self.updateCounts();
                });

                /* ── Touch DnD (mobile/tablet) ── */
                Touch.bind(card, self);

                G.on(card, 'dblclick', function () {
                    if (window.GestorSlideOver) window.GestorSlideOver.openFromCard(card);
                });
            });

            cols.forEach(function (col) {
                G.on(col, 'dragover', function (e) {
                    e.preventDefault();
                    col.classList.add('drag-over');
                });

                G.on(col, 'dragleave', function () {
                    col.classList.remove('drag-over');
                });

                G.on(col, 'drop', function (e) {
                    e.preventDefault();
                    col.classList.remove('drag-over');
                    if (!self._dragged) return;

                    col.appendChild(self._dragged);
                    var newStatus = col.dataset.status || '';
                    var cardId    = self._dragged.dataset.id || '';

                    if (newStatus && cardId) {
                        self._persistMove(cardId, newStatus, col);
                    }

                    G.toast('Card movido!', 'success');
                });
            });

            this.updateCounts();
        },

        /**
         * Persist drag to backend (debounced)
         */
        _persistMove: function (cardId, status, col) {
            clearTimeout(this._persistTimer);
            var self = this;
            this._persistTimer = setTimeout(function () {
                /* Build order array for the entire column */
                var order = [];
                G.$$('.ev-card', col).forEach(function (card, i) {
                    order.push({
                        id: card.dataset.id,
                        status: status,
                        position: i
                    });
                });

                var eventId = window.GestorStore._eventId;
                if (eventId) {
                    G.ajax('reorder_tasks', {
                        event_id: eventId,
                        'order': JSON.stringify(order)
                    }).catch(function () {
                        G.toast('Erro ao salvar posição', 'error');
                    });
                }
            }, 300);
        },

        updateCounts: function () {
            G.$$('.kan-col').forEach(function (col) {
                var count = G.$$('.ev-card', col).length;
                var badge = G.$('.kan-col-count', col);
                if (badge) badge.textContent = count;
            });
        }
    };
})();
