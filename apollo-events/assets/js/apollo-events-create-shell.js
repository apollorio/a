    (function () {
        const $ = (s, c) => (c || document).querySelector(s);
        const $$ = (s, c) => Array.prototype.slice.call((c || document).querySelectorAll(s));
        const focusOpts = { preventScroll: true };

        /* ── DRAWER ── */
        const aside = $('#ax-aside'), ov = $('#ax-overlay'), burger = $('#burger');
        function closeDrawer() { if (aside) aside.classList.remove('open'); if (ov) ov.classList.remove('on'); }
        if (burger) burger.addEventListener('click', function (e) {
            e.stopPropagation();
            aside.classList.toggle('open');
            ov.classList.toggle('on', aside.classList.contains('open'));
        });
        $$('.ax-aside .ni, .ax-aside .si').forEach(function (a) {
            a.addEventListener('click', function () { if (window.innerWidth <= 999) closeDrawer(); });
        });

        /* ── PANELS + APPS ── */
        let _openPanel = null;
        const appsPop = $('#apps-pop'), icApps = $('#ic-apps');
        function appsClose() { if (appsPop) { appsPop.classList.remove('open'); appsPop.setAttribute('aria-hidden', 'true'); } }
        function closeSelects(except) {
            $$('.as2.is-open').forEach(function (s) {
                if (!except || s !== except) { s.classList.remove('is-open'); const inp = $('.as2-input', s); if (inp) inp.setAttribute('aria-expanded', 'false'); }
            });
        }
        function prepFloatingOpen(exceptSelect) { appsClose(); if (_openPanel) closePanel(_openPanel, true); closeSelects(exceptSelect || null); }
        function dismissFloatingUI() { appsClose(); if (_openPanel) closePanel(_openPanel); closeSelects(null); }
        function openPanel(id) {
            prepFloatingOpen();
            if (_openPanel && _openPanel !== id) closePanel(_openPanel, true);
            const p = document.getElementById(id); if (!p) return;
            p.classList.add('open'); p.setAttribute('aria-hidden', 'false');
            ov.classList.add('on'); _openPanel = id;
        }
        function closePanel(id, skipOv) {
            const p = document.getElementById(id); if (!p) return;
            const f = p.querySelector(':focus'); if (f) f.blur();
            p.classList.remove('open'); p.setAttribute('aria-hidden', 'true');
            if (!skipOv) ov.classList.remove('on');
            if (_openPanel === id) _openPanel = null;
        }
        const icAct = $('#ic-act'); if (icAct) icAct.addEventListener('click', function (e) { e.stopPropagation(); _openPanel === 'panel-act' ? closePanel('panel-act') : openPanel('panel-act'); });
        const icPf = $('#ic-pf'); if (icPf) icPf.addEventListener('click', function (e) { e.stopPropagation(); _openPanel === 'panel-profile' ? closePanel('panel-profile') : openPanel('panel-profile'); });
        const userBtn = $('#aside-user-btn'); if (userBtn) userBtn.addEventListener('click', function (e) { e.stopPropagation(); openPanel('panel-profile'); if (window.innerWidth <= 999) closeDrawer(); });
        $$('[data-close]').forEach(function (b) { b.addEventListener('click', function () { closePanel(b.dataset.close); }); });
        if (icApps) icApps.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = !appsPop.classList.contains('open');
            prepFloatingOpen();
            appsPop.classList.toggle('open', open);
            appsPop.setAttribute('aria-hidden', open ? 'false' : 'true');
        });
        if (appsPop) appsPop.addEventListener('click', function (e) { e.stopPropagation(); });
        if (ov) ov.addEventListener('click', function () { closeDrawer(); dismissFloatingUI(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeDrawer(); dismissFloatingUI(); $$('.modal-backdrop.is-open').forEach(function (m) { m.classList.remove('is-open'); }); } });
        document.addEventListener('pointerdown', function (e) {
            if (e.target.closest('#ic-act, #ic-pf, #ic-apps, .panel-r, .apps-pop, .as2, #burger, .ax-aside')) return;
            dismissFloatingUI();
        }, true);

        /* ── PANEL TABS ── */
        $$('.panel-tab').forEach(function (t) {
            t.addEventListener('click', function () {
                const row = t.closest('.panel-tabs-row');
                $$('.panel-tab', row).forEach(function (x) { x.classList.remove('active'); x.setAttribute('aria-selected', 'false'); });
                t.classList.add('active'); t.setAttribute('aria-selected', 'true');
                const pane = document.getElementById(t.dataset.ptab); if (!pane) return;
                const body = t.closest('.panel-r').querySelector('.panel-r-body');
                $$('.panel-tab-pane', body).forEach(function (p) { p.classList.remove('active'); p.setAttribute('hidden', ''); });
                pane.classList.add('active'); pane.removeAttribute('hidden');
            });
        });

        /* ── SIDEBAR COLLAPSIBLES ── */
        $$('.sh[data-col]').forEach(function (h) {
            h.addEventListener('click', function () {
                const col = document.getElementById(h.dataset.col); if (!col) return;
                const s = col.classList.toggle('shut'); h.classList.toggle('shut', s);
            });
        });

        /* ── THEME PILL (dark/light) ── */
        const pill = $('#aside-pill');
        function isDark() { return document.documentElement.getAttribute('data-theme') !== 'light'; }
        function setDark(on) {
            document.documentElement.setAttribute('data-theme', on ? 'dark' : 'light');
            if (pill) { pill.classList.toggle('on', on); pill.setAttribute('aria-pressed', String(on)); }
        }
        if (pill) { setDark(isDark()); pill.addEventListener('click', function (e) { e.stopPropagation(); setDark(!isDark()); }); }

        /* ── PALETTE SELECT (.as2) — combobox com filtro + teclado (idêntico ao showcase) ── */
        $$('.as2').forEach(function (root) {
            const input = $('.as2-input', root), opts = $$('.as2-opt', root), empty = $('.as2-empty', root),
                sw = $('.as2-swatch', root), search = $('.as2-search', root), arrowBtn = $('.as2-arrow-btn', root);
            let focusIdx = -1;
            function visible() { return opts.filter(function (o) { return !o.hidden; }); }
            function open() { prepFloatingOpen(root); root.classList.add('is-open'); input.setAttribute('aria-expanded', 'true'); }
            function close() { root.classList.remove('is-open'); input.setAttribute('aria-expanded', 'false'); focusIdx = -1; opts.forEach(function (o) { o.classList.remove('is-focused'); }); if (search) search.value = ''; filter(''); }
            function filter(q) {
                q = (q || '').toLowerCase().trim(); let v = 0;
                opts.forEach(function (o) { const t = ((o.dataset.name || '') + ' ' + (o.dataset.desc || '')).toLowerCase(); o.hidden = !((!q) || t.indexOf(q) > -1); if (!o.hidden) v++; });
                if (empty) empty.classList.toggle('is-visible', v === 0);
                focusIdx = -1; opts.forEach(function (o) { o.classList.remove('is-focused'); });
            }
            function setFocus(idx) {
                const vis = visible(); if (!vis.length) return;
                focusIdx = ((idx % vis.length) + vis.length) % vis.length;
                opts.forEach(function (o) { o.classList.remove('is-focused'); });
                vis[focusIdx].classList.add('is-focused'); vis[focusIdx].scrollIntoView({ block: 'nearest' });
            }
            function pick(o) {
                if (!o) return;
                opts.forEach(function (x) { x.classList.remove('is-selected'); });
                o.classList.add('is-selected');
                input.value = o.dataset.name || '';
                root.classList.add('has-value');
                if (sw) sw.style.background = o.dataset.value || '';
                close(); input.focus(focusOpts);
            }
            input.addEventListener('focus', function () { open(); });
            input.addEventListener('input', function () { open(); filter(input.value); });
            input.addEventListener('keydown', function (e) {
                const vis = visible();
                if (e.key === 'ArrowDown') { e.preventDefault(); open(); setFocus(focusIdx + 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); open(); setFocus(focusIdx < 0 ? vis.length - 1 : focusIdx - 1); }
                else if (e.key === 'Enter') { if (focusIdx >= 0 && vis[focusIdx]) { e.preventDefault(); pick(vis[focusIdx]); } }
                else if (e.key === 'Escape') { e.preventDefault(); close(); }
            });
            if (arrowBtn) arrowBtn.addEventListener('click', function (e) { e.stopPropagation(); root.classList.contains('is-open') ? close() : open(); input.focus(focusOpts); });
            if (search) {
                search.addEventListener('input', function () { filter(search.value); });
                search.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowDown') { e.preventDefault(); setFocus(focusIdx + 1); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); setFocus(focusIdx < 0 ? visible().length - 1 : focusIdx - 1); }
                    else if (e.key === 'Enter' && focusIdx >= 0) { e.preventDefault(); pick(visible()[focusIdx]); }
                    else if (e.key === 'Escape') { e.preventDefault(); close(); input.focus(focusOpts); }
                });
            }
            opts.forEach(function (o) {
                o.addEventListener('mousedown', function (e) { e.preventDefault(); });
                o.addEventListener('click', function (e) { e.stopPropagation(); pick(o); });
            });
        });
    })();