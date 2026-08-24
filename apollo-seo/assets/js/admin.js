/**
 * Apollo SEO — Admin JavaScript
 *
 * - Character counters for title/description fields
 * - SERP preview live update (post editor)
 * - Metabox tab switching
 */

(function () {
    'use strict';

    /* ═══════════════════════════════════════════════════════════════
       CHARACTER COUNTERS
       ═══════════════════════════════════════════════════════════════ */

    function initCounters() {
        const inputs = document.querySelectorAll('[data-aseo-counter]');

        inputs.forEach(function (input) {
            const max = parseInt(input.getAttribute('data-aseo-counter'), 10);
            const parent = input.closest('.aseo-field') || input.closest('td');
            if (!parent) return;

            const counter = parent.querySelector('.aseo-counter');
            if (!counter) return;

            const countEl = counter.querySelector('.aseo-count');
            if (!countEl) return;

            function update() {
                var len = input.value.length;
                countEl.textContent = len;

                counter.classList.remove('aseo-counter--warn', 'aseo-counter--over');
                if (len > max) {
                    counter.classList.add('aseo-counter--over');
                } else if (len > max * 0.85) {
                    counter.classList.add('aseo-counter--warn');
                }
            }

            input.addEventListener('input', update);
            input.addEventListener('change', update);
            update();
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       SERP PREVIEW (post editor metabox)
       ═══════════════════════════════════════════════════════════════ */

    function initSERPPreview() {
        var titleInput = document.getElementById('aseo-meta-title');
        var descInput = document.getElementById('aseo-meta-desc');
        var serpTitle = document.getElementById('aseo-serp-title');
        var serpDesc = document.getElementById('aseo-serp-desc');

        if (!serpTitle || !serpDesc) return;

        /* Get WP post title as fallback */
        var wpTitleEl = document.getElementById('title') || document.querySelector('[name="post_title"]');
        var defaultTitle = serpTitle.textContent || '';
        var defaultDesc = serpDesc.textContent || '';

        function updatePreview() {
            /* Title */
            var t = (titleInput && titleInput.value.trim()) ? titleInput.value.trim() : '';
            if (!t && wpTitleEl) {
                t = wpTitleEl.value || defaultTitle;
            }
            if (!t) t = defaultTitle;
            serpTitle.textContent = t;

            /* Description */
            var d = (descInput && descInput.value.trim()) ? descInput.value.trim() : '';
            if (!d) d = defaultDesc;
            serpDesc.textContent = d;
        }

        if (titleInput) {
            titleInput.addEventListener('input', updatePreview);
        }
        if (descInput) {
            descInput.addEventListener('input', updatePreview);
        }

        /* Also listen to WP title changes */
        if (wpTitleEl) {
            wpTitleEl.addEventListener('input', updatePreview);
        }

        updatePreview();
    }

    /* ═══════════════════════════════════════════════════════════════
       METABOX TABS
       ═══════════════════════════════════════════════════════════════ */

    function initMetaboxTabs() {
        var tabs = document.querySelectorAll('.aseo-mtab');
        var panels = document.querySelectorAll('.aseo-mtab-panel');

        if (!tabs.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var target = this.getAttribute('data-tab');

                /* Remove active */
                tabs.forEach(function (t) {
                    t.classList.remove('aseo-mtab--active');
                });
                panels.forEach(function (p) {
                    p.classList.remove('aseo-mtab-panel--active');
                });

                /* Set active */
                this.classList.add('aseo-mtab--active');
                var panel = document.querySelector('[data-panel="' + target + '"]');
                if (panel) {
                    panel.classList.add('aseo-mtab-panel--active');
                }
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       INIT
       ═══════════════════════════════════════════════════════════════ */

    function init() {
        initCounters();
        initSERPPreview();
        initMetaboxTabs();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
