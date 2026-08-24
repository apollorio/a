/* ═══════════════════════════════════════════════════════════════
   gestor.cmdpalette.js — Command Palette (Ctrl+K)
   Fuzzy search over GestorStore.commands, keyboard navigation
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorCmdPalette = {
        _commands: null,
        _overlay:  null,
        _input:    null,
        _results:  null,

        init: function () {
            var self    = this;
            self._overlay = G.$('#cmdPalette');
            if (!self._overlay) return;
            self._input   = G.$('.cmd-input', self._overlay);
            self._results = G.$('.cmd-results', self._overlay);

            /* Build commands from Store */
            self._commands = self._buildCommands();

            /* Global Ctrl+K */
            G.on(document, 'keydown', function (e) {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    self.open();
                }
                if (e.key === 'Escape') self.close();
            });

            /* Click overlay background */
            G.on(self._overlay, 'click', function (e) {
                if (e.target === self._overlay) self.close();
            });

            /* Search input */
            G.on(self._input, 'input', function () {
                self._render(self._input.value);
            });

            /* Bind global search bar to open palette */
            var searchInput = G.$('#globalSearch');
            G.on(searchInput, 'focus', function (e) {
                e.preventDefault();
                self.open();
                if (searchInput) searchInput.blur();
            });
        },

        _buildCommands: function () {
            var actions = {
                'newEvent':   function () { var m = G.$('#newEventModal'); if (m) m.classList.add('on'); },
                'calculator': function () { var m = G.$('#calcOverlay'); if (m) m.classList.add('on'); }
            };

            var store = window.GestorStore;
            if (store && store.commands && store.commands.commands) {
                return store.commands.commands.map(function (cmd) {
                    return {
                        icon:   cmd.icon,
                        label:  cmd.label,
                        action: actions[cmd.action] || (function () {
                            var numMatch = cmd.action.match(/^tab:(\d+)$/);
                            if (numMatch && window.GestorTabs) {
                                return function () {
                                    GestorTabs.openByIndex(parseInt(numMatch[1], 10));
                                };
                            }
                            var nameMatch = cmd.action.match(/^tab:(.+)$/);
                            if (nameMatch && window.openTab) {
                                return function () {
                                    openTab(nameMatch[1]);
                                };
                            }
                            return function () { G.toast(cmd.label); };
                        })()
                    };
                });
            }

            /* Hard fallback */
            return [
                { icon: 'ri-add-line',               label: 'Novo Evento',           action: actions.newEvent || function () {} },
                { icon: 'ri-team-line',               label: 'Ver Equipe',            action: function () { window.openTab && openTab('panel-equipe'); } },
                { icon: 'ri-money-dollar-box-line',   label: 'Budget',                action: function () { window.openTab && openTab('panel-budget'); } },
                { icon: 'ri-bar-chart-grouped-line',  label: 'Cronograma',            action: function () { window.openTab && openTab('panel-cronograma'); } },
                { icon: 'ri-kanban-view-2',           label: 'Kanban',                action: function () { window.openTab && openTab('panel-kanban'); } },
                { icon: 'ri-calculator-line',         label: 'Calculadora de Cachê',  action: actions.calculator || function () {} }
            ];
        },

        open: function () {
            this._overlay.classList.add('on');
            this._input.value = '';
            this._render('');
            this._input.focus();
        },

        close: function () {
            if (this._overlay) this._overlay.classList.remove('on');
        },

        _render: function (q) {
            var self     = this;
            var commands = self._commands;
            q = (q || '').toLowerCase();

            var filtered = q
                ? commands.filter(function (c) { return c.label.toLowerCase().indexOf(q) !== -1; })
                : commands;

            self._results.innerHTML = filtered.map(function (c, i) {
                return '<div class="cmd-item' + (i === 0 ? ' active' : '') + '" data-idx="' + i + '">' +
                       '<i class="' + G.escapeHtml(c.icon) + '"></i> ' + G.escapeHtml(c.label) +
                       '</div>';
            }).join('');

            G.$$('.cmd-item', self._results).forEach(function (el, i) {
                G.on(el, 'click', function () {
                    filtered[i].action();
                    self.close();
                });
            });
        }
    };
})();
