/**
 * Apollo Events — Lineup Builder
 *
 * Drag-reorder DJ cards, click up/down arrows, time-in/time-out per slot.
 * Outputs hidden JSON for _event_dj_slots and hidden CSV for _event_dj_ids.
 *
 * Usage: new ApolloLineupBuilder('#lineup-builder', djList, existingSlots)
 *
 * @package Apollo\Event
 * @since   2.1.0
 */
(function () {
    'use strict';

    window.ApolloLineupBuilder = function (containerSel, djOptions, existingSlots) {
        var container = document.querySelector(containerSel);
        if (!container) return;

        var searchInput = container.querySelector('.lu-search');
        var optionsList = container.querySelector('.lu-options');
        var selectedList = container.querySelector('.lu-selected');
        var hiddenIds = container.querySelector('.lu-hidden-ids');
        var hiddenSlots = container.querySelector('.lu-hidden-slots');

        var slots = []; // {dj_id, name, thumb, time_in, time_out}
        var dragSrcIdx = null;

        // ── Init from existing data ──
        if (existingSlots && existingSlots.length) {
            existingSlots.forEach(function (s) {
                var match = djOptions.find(function (d) { return d.id === s.dj_id; });
                if (match) {
                    slots.push({
                        dj_id: match.id,
                        name: match.name,
                        thumb: match.thumb || '',
                        time_in: s.start_time || s.time_in || s.time || '',
                        time_out: s.end_time || s.time_out || '',
                        badge: s.badge || ''
                    });
                }
            });
        }

        render();

        // ── Search/filter DJ options ──
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = this.value.toLowerCase().trim();
                var items = optionsList.querySelectorAll('.lu-opt');
                items.forEach(function (item) {
                    var name = item.getAttribute('data-name').toLowerCase();
                    item.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }

        // ── Click to add DJ from options ──
        optionsList.addEventListener('click', function (e) {
            var opt = e.target.closest('.lu-opt');
            if (!opt) return;
            var djId = parseInt(opt.getAttribute('data-id'), 10);
            if (slots.some(function (s) { return s.dj_id === djId; })) return; // already added
            slots.push({
                dj_id: djId,
                name: opt.getAttribute('data-name'),
                thumb: opt.getAttribute('data-thumb') || '',
                time_in: '',
                time_out: '',
                badge: ''
            });
            render();
        });

        // ── Delegated events on selected list ──
        selectedList.addEventListener('click', function (e) {
            var card = e.target.closest('.lu-card');
            if (!card) return;
            var idx = parseInt(card.getAttribute('data-idx'), 10);

            // Remove
            if (e.target.closest('.lu-remove')) {
                slots.splice(idx, 1);
                render();
                return;
            }
            // Move up
            if (e.target.closest('.lu-up') && idx > 0) {
                var t = slots[idx];
                slots[idx] = slots[idx - 1];
                slots[idx - 1] = t;
                render();
                return;
            }
            // Move down
            if (e.target.closest('.lu-down') && idx < slots.length - 1) {
                var t2 = slots[idx];
                slots[idx] = slots[idx + 1];
                slots[idx + 1] = t2;
                render();
                return;
            }
        });

        // ── Time input changes ──
        selectedList.addEventListener('change', function (e) {
            if (!e.target.matches('.lu-time') && !e.target.matches('.lu-badge')) return;
            var card = e.target.closest('.lu-card');
            var idx = parseInt(card.getAttribute('data-idx'), 10);
            var field = e.target.getAttribute('data-field');
            if (field === 'time_in') slots[idx].time_in = e.target.value;
            if (field === 'time_out') slots[idx].time_out = e.target.value;
            if (field === 'badge') slots[idx].badge = e.target.value;
            syncHidden();
        });

        // ── Badge input (typed) ──
        selectedList.addEventListener('input', function (e) {
            if (!e.target.matches('.lu-badge')) return;
            var card = e.target.closest('.lu-card');
            var idx = parseInt(card.getAttribute('data-idx'), 10);
            slots[idx].badge = e.target.value;
            syncHidden();
        });

        // ── Drag & Drop ──
        selectedList.addEventListener('dragstart', function (e) {
            var card = e.target.closest('.lu-card');
            if (!card) return;
            dragSrcIdx = parseInt(card.getAttribute('data-idx'), 10);
            card.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        selectedList.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var card = e.target.closest('.lu-card');
            if (card) card.classList.add('is-dragover');
        });

        selectedList.addEventListener('dragleave', function (e) {
            var card = e.target.closest('.lu-card');
            if (card) card.classList.remove('is-dragover');
        });

        selectedList.addEventListener('drop', function (e) {
            e.preventDefault();
            var card = e.target.closest('.lu-card');
            if (!card) return;
            card.classList.remove('is-dragover');
            var dropIdx = parseInt(card.getAttribute('data-idx'), 10);
            if (dragSrcIdx === null || dragSrcIdx === dropIdx) return;
            var moved = slots.splice(dragSrcIdx, 1)[0];
            slots.splice(dropIdx, 0, moved);
            dragSrcIdx = null;
            render();
        });

        selectedList.addEventListener('dragend', function () {
            dragSrcIdx = null;
            selectedList.querySelectorAll('.is-dragging').forEach(function (el) {
                el.classList.remove('is-dragging');
            });
        });

        // ── Render ──
        function render() {
            // Selected cards
            var html = '';
            slots.forEach(function (s, i) {
                html += '<div class="lu-card" data-idx="' + i + '" draggable="true">'
                    + '<div class="lu-card__grip"><i class="ri-draggable"></i></div>'
                    + '<span class="lu-card__order">' + (i + 1) + '</span>';
                if (s.thumb) {
                    html += '<img src="' + escAttr(s.thumb) + '" class="lu-card__avatar" alt="' + escAttr(s.name) + '">';
                } else {
                    html += '<span class="lu-card__avatar lu-card__avatar--empty"><i class="ri-disc-line"></i></span>';
                }
                html += '<span class="lu-card__name">' + escHtml(s.name) + '</span>'
                    + '<div class="lu-card__times">'
                    + '  <label class="lu-card__time-label">IN</label>'
                    + '  <input type="time" class="lu-time" data-field="time_in" value="' + escAttr(s.time_in) + '">'
                    + '  <label class="lu-card__time-label">OUT</label>'
                    + '  <input type="time" class="lu-time" data-field="time_out" value="' + escAttr(s.time_out) + '">'
                    + '</div>'
                    + '<div class="lu-card__badge">'
                    + '  <input type="text" class="lu-badge" data-field="badge" maxlength="24" placeholder="Badge (ex: B2B, LIVE, Headliner)" value="' + escAttr(s.badge || '') + '">'
                    + '</div>'
                    + '<div class="lu-card__actions">'
                    + '  <button type="button" class="lu-up" title="Subir"' + (i === 0 ? ' disabled' : '') + '><i class="ri-arrow-up-s-line"></i></button>'
                    + '  <button type="button" class="lu-down" title="Descer"' + (i === slots.length - 1 ? ' disabled' : '') + '><i class="ri-arrow-down-s-line"></i></button>'
                    + '  <button type="button" class="lu-remove" title="Remover"><i class="ri-close-line"></i></button>'
                    + '</div>'
                    + '</div>';
            });
            selectedList.innerHTML = html;

            // Mark selected in options
            optionsList.querySelectorAll('.lu-opt').forEach(function (opt) {
                var djId = parseInt(opt.getAttribute('data-id'), 10);
                var isSelected = slots.some(function (s) { return s.dj_id === djId; });
                opt.classList.toggle('is-selected', isSelected);
            });

            syncHidden();
        }

        function syncHidden() {
            // _event_dj_ids = ordered array of ints
            var ids = slots.map(function (s) { return s.dj_id; });
            if (hiddenIds) hiddenIds.value = JSON.stringify(ids);

            // _event_dj_slots = structured array
            var slotsData = slots.map(function (s) {
                return {
                    dj_id: s.dj_id,
                    start_time: s.time_in,
                    end_time: s.time_out,
                    badge: s.badge || ''
                };
            });
            if (hiddenSlots) hiddenSlots.value = JSON.stringify(slotsData);
        }

        function escAttr(s) { return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
        function escHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

        // Public API
        this.getSlots = function () { return slots; };
        this.getIds = function () { return slots.map(function (s) { return s.dj_id; }); };
    };
})();
