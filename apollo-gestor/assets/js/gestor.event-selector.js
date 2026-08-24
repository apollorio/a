/* ═══════════════════════════════════════════════════════════════
   gestor.event-selector.js — AS3 Glass Event Selector
   Header dropdown to switch between Overview and individual events
   Loads dynamically from GestorStore.events via AJAX
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    window.GestorEventSelector = {
        _wrap: null,

        init: function () {
            var self    = this;
            self._wrap  = G.$('#evSelect');
            if (!self._wrap) return;

            var trigger = G.$('.as3-trigger', self._wrap);
            var drop    = G.$('.as3-drop',    self._wrap);
            var search  = G.$('.as3-search',  self._wrap);

            /* Toggle dropdown */
            G.on(trigger, 'click', function (e) {
                e.stopPropagation();
                self._wrap.classList.toggle('open');
                if (search) search.focus();
            });

            /* Close on outside click */
            G.on(document, 'click', function (e) {
                if (!self._wrap.contains(e.target)) self._wrap.classList.remove('open');
            });

            /* Search filtering */
            G.on(search, 'input', function () {
                var q    = search.value.trim().toLowerCase();
                var opts = G.$$('.as3-opt', self._wrap);
                var vis  = 0;
                var emptyMsg = G.$('.as3-empty', self._wrap);
                opts.forEach(function (o) {
                    var match = (o.dataset.name || '').toLowerCase().indexOf(q) !== -1 ||
                                (o.dataset.desc || '').toLowerCase().indexOf(q) !== -1;
                    o.style.display = match ? '' : 'none';
                    if (match) vis++;
                });
                if (emptyMsg) emptyMsg.style.display = vis === 0 ? 'block' : 'none';
            });

            /* Populate with data when ready */
            window.GestorStore.onReady(function (store) {
                self._populateOptions(store.events || []);
            });
        },

        /**
         * Create option elements from events data
         * @param {Array} events
         */
        _populateOptions: function (events) {
            var self    = this;
            var optsWrap = G.$('.as3-opts', self._wrap);
            if (!optsWrap) return;

            var trigger = G.$('.as3-trigger', self._wrap);
            var statusMap = (G.cfg && G.cfg.statusMap) || {};

            /* Keep the "Overview" option at the top */
            var overviewOpt = G.$('.as3-opt[data-value="overview"]', optsWrap);

            /* Clear existing options except overview */
            G.$$('.as3-opt:not([data-value="overview"])', optsWrap).forEach(function (o) { o.remove(); });

            events.forEach(function (ev) {
                var opt   = document.createElement('div');
                var color = (statusMap[ev.status] && statusMap[ev.status].color) || '#94a3b8';
                opt.className    = 'as3-opt';
                opt.dataset.value = ev.id;
                opt.dataset.name  = ev.title;
                opt.dataset.desc  = (ev.start_fmt || '') + ' · ' + (ev.loc_name || '');
                opt.innerHTML =
                    '<span class="as3-opt-sw" style="background:' + G.escapeHtml(color) + '"></span>' +
                    '<span class="as3-opt-info">' +
                        '<span class="as3-opt-name">' + G.escapeHtml(ev.title) + '</span>' +
                        '<span class="as3-opt-desc">' + G.escapeHtml(opt.dataset.desc) + '</span>' +
                    '</span>' +
                    '<i class="ri-check-line as3-opt-check"></i>';

                optsWrap.appendChild(opt);
                self._bindOption(opt);
            });
        },

        /**
         * Bind click on an option
         */
        _bindOption: function (opt) {
            var self    = this;
            var trigger = G.$('.as3-trigger', self._wrap);

            G.on(opt, 'click', function () {
                G.$$('.as3-opt', self._wrap).forEach(function (x) { x.classList.remove('is-selected'); });
                opt.classList.add('is-selected');

                var label  = G.$('.as3-lbl-name', trigger);
                var swatch = G.$('.as3-swatch', trigger);
                if (label)  label.textContent     = opt.dataset.name;
                if (swatch) swatch.style.background = (G.$('.as3-opt-sw', opt) || {}).style.background || '';

                self._wrap.classList.remove('open');
                trigger.classList.add('is-selected');
                trigger.dataset.value = opt.dataset.value;

                /* Toggle overview vs single event */
                var ov = G.$('#overviewContent');
                var sv = G.$('#singleContent');
                if (opt.dataset.value === 'overview') {
                    if (ov) ov.classList.add('on');
                    if (sv) sv.classList.remove('on');
                } else {
                    if (ov) ov.classList.remove('on');
                    if (sv) sv.classList.add('on');
                    var h2 = sv ? G.$('h2', sv) : null;
                    if (h2) h2.textContent = opt.dataset.name;

                    /* Load event-specific data */
                    window.GestorStore.loadEvent(parseInt(opt.dataset.value, 10)).then(function () {
                        G.toast('Evento: ' + G.escapeHtml(opt.dataset.name), 'success');
                        /* Dispatch custom event for other modules to react */
                        document.dispatchEvent(new CustomEvent('gestor:event-changed', {
                            detail: { eventId: parseInt(opt.dataset.value, 10), name: opt.dataset.name }
                        }));
                    });
                }
            });
        }
    };
})();
