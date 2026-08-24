/**
 * Apollo Auth — genre immersion picker + universe filters.
 * Requires: window.apolloAuthConfig.soundsCatalog, GSAP (defer).
 */
(function (window, document) {
    'use strict';

    var MAX_GENRES = 5;
    var MIN_GENRES = 3;
    var GROUPS = ['underground', 'both', 'mainstream'];

    var instances = new WeakMap();

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function getCatalog() {
        var cfg = window.apolloAuthConfig || {};
        if (Array.isArray(cfg.soundsCatalog) && cfg.soundsCatalog.length) {
            return cfg.soundsCatalog;
        }
        var flat = cfg.availableSounds || {};
        return Object.keys(flat).map(function (slug) {
            return { slug: slug, label: flat[slug], group: 'both' };
        });
    }

    function deriveUniverse(activeFilters) {
        var on = GROUPS.filter(function (g) { return activeFilters[g]; });
        if (on.length === 1 && on[0] === 'underground') return 'underground';
        if (on.length === 1 && on[0] === 'mainstream') return 'mainstream';
        if (on.length === 1 && on[0] === 'both') return 'both';
        return 'both';
    }

    function activeFilterLabel(activeFilters) {
        return GROUPS.filter(function (g) { return activeFilters[g]; }).join(' · ');
    }

    function mount(container) {
        if (!container) return null;

        var tpl = document.getElementById('apollo-genre-explosion-tpl');
        if (!tpl || !tpl.content) return null;

        container.innerHTML = '';
        var root = tpl.content.firstElementChild.cloneNode(true);
        container.appendChild(root);

        var catalog = getCatalog();
        var activeFilters = { underground: true, both: true, mainstream: true };
        var selected = new Set();
        var activePills = new Map();
        var immersiveOpen = false;
        var tlImmersive = null;
        var stagePlaceholder = null;

        var pillsHost = root.querySelector('[data-genre-pills]');
        var stage = root.querySelector('[data-genre-immersion]');
        var tagsHost = root.querySelector('[data-genre-tags]');
        var backBtn = root.querySelector('[data-genre-back]');
        var confirmBtn = root.querySelector('[data-genre-confirm]');
        var immersionLabel = root.querySelector('[data-genre-immersion-label]');
        var nativeSelect = root.querySelector('[data-genre-native]');
        var countEl = root.querySelector('[data-genre-count]');
        var countImmersionEl = root.querySelector('[data-genre-count-immersion]');
        var filterBtns = root.querySelectorAll('.universe-filter-btn');

        function escapeAttr(s) {
            return String(s).replace(/"/g, '&quot;');
        }

        function buildNativeOptions() {
            nativeSelect.innerHTML = '';
            catalog.forEach(function (item) {
                var opt = document.createElement('option');
                opt.value = item.slug;
                opt.textContent = item.label;
                nativeSelect.appendChild(opt);
            });
        }

        function syncNativeSelect() {
            Array.from(nativeSelect.options).forEach(function (opt) {
                opt.selected = selected.has(opt.value);
            });
        }

        function filteredCatalog() {
            return catalog.filter(function (item) {
                return activeFilters[item.group];
            });
        }

        function updateCount() {
            var n = String(selected.size);
            if (countEl) countEl.textContent = n;
            if (countImmersionEl) countImmersionEl.textContent = n;
        }

        function checkPlaceholder() {
            var ph = pillsHost.querySelector('.genre-placeholder');
            if (selected.size === 0) {
                if (!ph) {
                    ph = document.createElement('span');
                    ph.className = 'genre-placeholder';
                    ph.textContent = 'Escolha gêneros...';
                    pillsHost.appendChild(ph);
                }
            } else if (ph) {
                ph.remove();
            }
        }

        function addPill(item) {
            checkPlaceholder();
            var pill = document.createElement('div');
            pill.className = 'genre-pill';
            pill.innerHTML = '<span>' + item.label + '</span><span class="genre-pill-remove" role="button" tabindex="0" aria-label="Remover">&times;</span>';
            pill.querySelector('.genre-pill-remove').addEventListener('click', function (e) {
                e.stopPropagation();
                selected.delete(item.slug);
                var tag = tagsHost.querySelector('.genre-tag[data-value="' + escapeAttr(item.slug) + '"]');
                if (tag) tag.classList.remove('selected');
                removePill(item.slug);
                syncNativeSelect();
                updateCount();
                if (api.onChange) api.onChange();
            });
            pillsHost.appendChild(pill);
            activePills.set(item.slug, pill);
            if (typeof gsap !== 'undefined' && !prefersReducedMotion()) {
                gsap.fromTo(pill, { scale: 0.6, opacity: 0, x: -10 }, { scale: 1, opacity: 1, x: 0, duration: 0.4, ease: 'back.out(2)' });
            }
        }

        function removePill(slug) {
            var pill = activePills.get(slug);
            if (!pill) return;
            var done = function () {
                pill.remove();
                activePills.delete(slug);
                checkPlaceholder();
            };
            if (typeof gsap !== 'undefined' && !prefersReducedMotion()) {
                gsap.to(pill, { scale: 0.6, opacity: 0, duration: 0.3, ease: 'power3.inOut', onComplete: done });
            } else {
                done();
            }
        }

        function toggleGenre(slug, tagEl) {
            var item = catalog.find(function (c) { return c.slug === slug; });
            if (!item) return;

            if (selected.has(slug)) {
                selected.delete(slug);
                if (tagEl) tagEl.classList.remove('selected');
                removePill(slug);
            } else if (selected.size < MAX_GENRES) {
                selected.add(slug);
                if (tagEl) tagEl.classList.add('selected');
                addPill(item);
            } else {
                document.dispatchEvent(new CustomEvent('apollo-auth-notify', {
                    detail: { message: 'Máximo de 5 gêneros musicais.', type: 'warning' }
                }));
            }
            syncNativeSelect();
            updateCount();
            if (api.onChange) api.onChange();
        }

        function renderTags(animate) {
            var visible = filteredCatalog();
            tagsHost.innerHTML = '';
            visible.forEach(function (item) {
                var tag = document.createElement('button');
                tag.type = 'button';
                tag.className = 'genre-tag' + (selected.has(item.slug) ? ' selected' : '');
                tag.textContent = item.label;
                tag.dataset.value = item.slug;
                tag.setAttribute('role', 'option');
                tag.setAttribute('aria-selected', selected.has(item.slug) ? 'true' : 'false');
                tag.addEventListener('click', function (e) {
                    e.stopPropagation();
                    toggleGenre(item.slug, tag);
                    tag.setAttribute('aria-selected', selected.has(item.slug) ? 'true' : 'false');
                });
                tagsHost.appendChild(tag);
            });

            if (animate && immersiveOpen && typeof gsap !== 'undefined' && !prefersReducedMotion()) {
                var tags = tagsHost.querySelectorAll('.genre-tag');
                gsap.fromTo(tags,
                    { scale: 0.5, opacity: 0, y: 30 },
                    { scale: 1, opacity: 1, y: 0, duration: 0.5, stagger: { amount: 0.35, from: 'center' }, ease: 'back.out(1.6)' }
                );
            }
        }

        function updateImmersionLabel() {
            if (immersionLabel) {
                immersionLabel.textContent = activeFilterLabel(activeFilters) || 'gêneros';
            }
        }

        function lockScroll(lock) {
            document.documentElement.classList.toggle('is-genre-immersed', lock);
            document.body.classList.toggle('is-genre-immersed', lock);
            var overlay = document.getElementById('aptitude-overlay');
            if (overlay) overlay.classList.toggle('is-genre-immersed', lock);
        }

        function openImmersive(fromBtn) {
            if (!stage || immersiveOpen) {
                renderTags(true);
                updateImmersionLabel();
                return;
            }

            updateImmersionLabel();
            renderTags(false);

            stagePlaceholder = document.createComment('genre-immersion-anchor');
            root.insertBefore(stagePlaceholder, root.firstChild);
            document.body.appendChild(stage);

            stage.hidden = false;
            stage.setAttribute('aria-hidden', 'false');
            lockScroll(true);
            immersiveOpen = true;

            if (prefersReducedMotion() || typeof gsap === 'undefined') {
                stage.classList.add('is-open');
                return;
            }

            var rect = fromBtn ? fromBtn.getBoundingClientRect() : { left: window.innerWidth / 2, top: window.innerHeight / 2, width: 0, height: 0 };
            var tags = tagsHost.querySelectorAll('.genre-tag');

            gsap.set(stage, {
                autoAlpha: 0,
                scale: 0.92,
                transformOrigin: rect.left + rect.width / 2 + 'px ' + (rect.top + rect.height / 2) + 'px'
            });

            tlImmersive = gsap.timeline()
                .to(stage, { autoAlpha: 1, scale: 1, duration: 0.55, ease: 'power3.out' })
                .fromTo(tags,
                    { scale: 0.5, opacity: 0, y: 40 },
                    { scale: 1, opacity: 1, y: 0, duration: 0.6, stagger: { amount: 0.4, from: 'center' }, ease: 'back.out(1.6)' },
                    '-=0.25'
                )
                .fromTo([backBtn, confirmBtn], { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.35, ease: 'power2.out' }, '-=0.3');

            stage.classList.add('is-open');
        }

        function closeImmersive() {
            if (!stage || !immersiveOpen) return;

            var finish = function () {
                stage.classList.remove('is-open');
                stage.hidden = true;
                stage.setAttribute('aria-hidden', 'true');
                if (stagePlaceholder && stagePlaceholder.parentNode) {
                    stagePlaceholder.parentNode.insertBefore(stage, stagePlaceholder);
                    stagePlaceholder.remove();
                    stagePlaceholder = null;
                } else if (!root.contains(stage)) {
                    root.appendChild(stage);
                }
                lockScroll(false);
                immersiveOpen = false;
                tlImmersive = null;
            };

            if (prefersReducedMotion() || typeof gsap === 'undefined' || !tlImmersive) {
                finish();
                return;
            }

            gsap.to(stage, {
                autoAlpha: 0,
                scale: 0.96,
                duration: 0.35,
                ease: 'power2.inOut',
                onComplete: finish
            });
        }

        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.getAttribute('data-filter-group');
                if (!group || !GROUPS.includes(group)) return;

                var activeCount = GROUPS.filter(function (g) { return activeFilters[g]; }).length;
                if (activeFilters[group] && activeCount <= 1) {
                    openImmersive(btn);
                    return;
                }

                activeFilters[group] = !activeFilters[group];
                btn.classList.toggle('is-active', activeFilters[group]);
                btn.setAttribute('aria-pressed', activeFilters[group] ? 'true' : 'false');

                if (immersiveOpen) {
                    updateImmersionLabel();
                    renderTags(true);
                } else {
                    openImmersive(btn);
                }

                if (api.onChange) api.onChange();
            });
        });

        pillsHost.addEventListener('click', function () {
            if (selected.size > 0 || immersiveOpen) return;
            openImmersive(filterBtns[0]);
        });

        backBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeImmersive();
        });

        confirmBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeImmersive();
        });

        buildNativeOptions();
        updateCount();
        checkPlaceholder();

        var api = {
            root: root,
            getSelected: function () { return Array.from(selected); },
            getActiveFilters: function () { return Object.assign({}, activeFilters); },
            getUniverseValue: function () { return deriveUniverse(activeFilters); },
            isValid: function () {
                return selected.size >= MIN_GENRES && selected.size <= MAX_GENRES;
            },
            setSelected: function (slugs) {
                selected.clear();
                activePills.forEach(function (_, slug) { removePill(slug); });
                activePills.clear();
                (slugs || []).forEach(function (slug) {
                    var item = catalog.find(function (c) { return c.slug === slug; });
                    if (item && selected.size < MAX_GENRES) {
                        selected.add(slug);
                        addPill(item);
                    }
                });
                syncNativeSelect();
                updateCount();
            },
            closeImmersive: closeImmersive,
            onChange: null
        };

        instances.set(root, api);
        return api;
    }

    window.ApolloAuthGenres = {
        mount: mount,
        getInstance: function (root) {
            return instances.get(root) || null;
        },
        deriveUniverse: deriveUniverse
    };
})(window, document);
