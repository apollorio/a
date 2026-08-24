/**
 * Apollo Pane Engine — Alpine.js Component (v2.0)
 *
 * Single Alpine component replacing paneEngine.js + panel-base.js + fetcher.js.
 * Uses HTMX for server-driven content loading and Alpine for reactive state.
 *
 * @version 2.0.0
 * @package Apollo\PaneEngine
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('paneApp', () => ({
        /* ── State ────────────────────────────────────────── */
        currentRoute: 'casa',
        currentTitle: 'Casa',
        activePanel: null,
        isLoading: false,

        /**
         * Non-null when a CPT detail view is displayed.
         * @type {{ backRoute: string, title: string } | null}
         */
        cptView: null,

        /** @type {Record<string, boolean>} prefetch tracking */
        _prefetched: {},

        /**
         * CPT URL slug → pane section slug map.
         * Mirrors PaneModeAdapter::$cpt_map on the PHP side.
         */
        _cptUrlMap: {
            evento: 'evento',
            dj:     'dj',
            local:  'local',
        },

        /* ── Route titles (from manifest navigation) ──────── */
        _titles: {
            casa: 'Casa',
            gigs: 'Gigs',
            sounds: 'Sounds',
            spots: 'Spots',
            social: 'Social',
            tools: 'Tools',
            'chat-inbox': 'Chat',
        },

        /* ── Init ─────────────────────────────────────────── */
        init() {
            const cfg = window.ApolloPane || {};

            // Resolve initial route from URL
            this.currentRoute = this._routeFromPath(window.location.pathname);
            this.currentTitle = this._titles[this.currentRoute] || 'Casa';

            // Preload detection — PHP pre-rendered CPT content into #casa-root
            if (cfg.preloadSection && cfg.preloadId) {
                this.cptView = {
                    backRoute: cfg.preloadBackRoute || 'casa',
                    title:     cfg.preloadTitle || this.currentTitle,
                };
                this.currentTitle = cfg.preloadTitle || this.currentTitle;
                // Content already in DOM — skip initial HTMX load
            } else {
                // Load initial section via HTMX
                this._loadSection(this.currentRoute, false);
            }

            // History API — back/forward
            window.addEventListener('popstate', (e) => {
                this.cptView = null;

                const pathname = window.location.pathname;

                // Detect CPT URLs: /{slug}/{id} e.g. /evento/42
                const cptMatch = pathname.match(/^\/([a-z][a-z0-9-]*)\/(\d+)\/?$/);
                if (cptMatch) {
                    const urlSlug  = cptMatch[1];
                    const postId   = cptMatch[2];
                    const paneSlug = this._cptUrlMap[urlSlug];
                    if (paneSlug) {
                        this._loadCptDetail(paneSlug, postId);
                        return;
                    }
                }

                const route = e.state?.route || this._routeFromPath(pathname);
                this._loadSection(route, false);
                this.currentRoute = route;
                this.currentTitle = this._titles[route] || route;
            });

            // HTMX lifecycle listeners
            document.addEventListener('htmx:beforeRequest', () => {
                this.isLoading = true;
            });

            document.addEventListener('htmx:afterSwap', () => {
                this.isLoading = false;
                // Detect if swapped content is a CPT detail view
                const root    = document.getElementById('casa-root');
                const cptEl   = root ? root.querySelector('[data-pane-view="cpt"]') : null;
                if (cptEl) {
                    const backRoute = cptEl.getAttribute('data-pane-back-route') || 'casa';
                    const title     = cptEl.getAttribute('data-pane-title') || this.currentTitle;
                    this.cptView    = { backRoute, title };
                    this.currentTitle = title;
                } else {
                    this.cptView = null;
                }
            });

            document.addEventListener('htmx:responseError', (e) => {
                this.isLoading = false;
                const target = document.getElementById('casa-root');
                if (target) {
                    const status = e.detail.xhr?.status || 0;
                    target.innerHTML = this._errorCard(
                        this.currentRoute,
                        status === 401
                            ? 'Sessão expirada — faça login novamente.'
                            : 'Erro ao carregar seção (' + status + ')'
                    );
                }

                // 401 → redirect to /acesso
                if (e.detail.xhr?.status === 401) {
                    const cfg = window.ApolloPane || {};
                    setTimeout(() => {
                        window.location.href = (cfg.homeUrl || '/') + 'acesso';
                    }, 1500);
                }
            });
        },

        /* ── Navigation ───────────────────────────────────── */

        /**
         * Navigate to a route section via HTMX.
         * @param {string} key — route key (casa, gigs, sounds, spots, social, tools)
         */
        navigate(key) {
            if (key === this.currentRoute && !this.activePanel && !this.cptView) {
                return; // already on this route
            }

            // Close any open overlay first
            if (this.activePanel) {
                this.closePanel();
            }

            // Clear CPT view when navigating to a section
            this.cptView = null;

            this.currentRoute = key;
            this.currentTitle = this._titles[key] || key;

            // Push history state
            const cfg = window.ApolloPane || {};
            const url = (cfg.casaUrl || '/casa') + (key === 'casa' ? '' : '/' + key);
            history.pushState({ route: key }, this.currentTitle, url);

            this._loadSection(key, true);
        },

        /**
         * Navigate back from a CPT detail view to the previous section.
         */
        navigateBack() {
            const back = this.cptView?.backRoute || 'casa';
            this.cptView = null;

            if (window.history.length > 1) {
                window.history.back();
            } else {
                this.navigate(back);
            }
        },

        /* ── Panel Overlays ───────────────────────────────── */

        /**
         * Show an overlay panel (left, up, down).
         * @param {string} id — panel identifier
         */
        showPanel(id) {
            if (['left', 'up', 'down'].includes(id)) {
                this.activePanel = id;
            }
        },

        /**
         * Close the active overlay panel.
         */
        closePanel() {
            this.activePanel = null;
        },

        /* ── Prefetch ─────────────────────────────────────── */

        /**
         * Prefetch a section on hover (fire-and-forget, warms HTMX cache).
         * @param {string} key
         */
        prefetchSection(key) {
            if (this._prefetched[key] || key === this.currentRoute) {
                return;
            }
            this._prefetched[key] = true;

            const cfg = window.ApolloPane || {};
            const url = (cfg.sectionBase || '/wp-json/apollo/v1/pane/section/') + encodeURIComponent(key);

            // Use HTMX's ajax method for prefetch (target: false = don't swap, just cache)
            if (typeof htmx !== 'undefined' && htmx.ajax) {
                htmx.ajax('GET', url, { target: 'none', swap: 'none' }).catch(() => {});
            }
        },

        /* ── Internal Helpers ─────────────────────────────── */

        /**
         * Load a CPT detail section via HTMX into #casa-root.
         * @param {string} paneSlug  e.g. 'evento', 'dj', 'local'
         * @param {string|number} postId
         */
        _loadCptDetail(paneSlug, postId) {
            const target = document.getElementById('casa-root');
            if (!target) return;

            target.innerHTML = this._skeletonHtml();

            const cfg = window.ApolloPane || {};
            const base = cfg.sectionBase || '/wp-json/apollo/v1/pane/section/';
            const url  = base + encodeURIComponent(paneSlug) + '/' + encodeURIComponent(postId);

            if (typeof htmx !== 'undefined' && htmx.ajax) {
                htmx.ajax('GET', url, {
                    target: '#casa-root',
                    swap: 'innerHTML',
                    headers: { 'Accept': 'text/html' },
                }).catch(() => {
                    target.innerHTML = this._errorCard(paneSlug + '/' + postId, 'Falha ao carregar detalhe');
                    this.isLoading = false;
                });
            }
        },

        /**
         * Load a section via HTMX into #casa-root.
         * @param {string} key
         * @param {boolean} showSkeleton
         */
        _loadSection(key, showSkeleton) {
            const target = document.getElementById('casa-root');
            if (!target) return;

            if (showSkeleton) {
                target.innerHTML = this._skeletonHtml();
            }

            const cfg = window.ApolloPane || {};
            const url = (cfg.sectionBase || '/wp-json/apollo/v1/pane/section/') + encodeURIComponent(key);

            if (typeof htmx !== 'undefined' && htmx.ajax) {
                htmx.ajax('GET', url, {
                    target: '#casa-root',
                    swap: 'innerHTML',
                    headers: {
                        'Accept': 'text/html',
                    },
                }).catch(() => {
                    target.innerHTML = this._errorCard(key, 'Falha na requisição HTMX');
                    this.isLoading = false;
                });
            }
        },

        /**
         * Resolve a pathname to a route key.
         * @param {string} pathname
         * @returns {string}
         */
        _routeFromPath(pathname) {
            const clean = pathname.replace(/\/$/, '').toLowerCase();
            const segments = clean.split('/');
            const last = segments[segments.length - 1];

            if (last && this._titles[last]) {
                return last;
            }
            return 'casa';
        },

        /**
         * Generate skeleton loading HTML.
         * @returns {string}
         */
        _skeletonHtml() {
            return '<div class="pane-skeleton-wrap">'
                + '<div class="pane-skeleton pane-skeleton--card"></div>'
                + '<div class="pane-skeleton pane-skeleton--card"></div>'
                + '<div class="pane-skeleton pane-skeleton--text"></div>'
                + '<div class="pane-skeleton pane-skeleton--text"></div>'
                + '<div class="pane-skeleton pane-skeleton--text"></div>'
                + '<div class="pane-skeleton pane-skeleton--card"></div>'
                + '</div>';
        },

        /**
         * Generate an error card HTML string (safe — no user input interpolated).
         * @param {string} route
         * @param {string} message
         * @returns {string}
         */
        _errorCard(route, message) {
            const safeRoute = this._esc(route);
            const safeMsg = this._esc(message);
            return '<div class="pane-content">'
                + '<div class="pane-card" style="border-color: var(--pane-error);">'
                + '<div class="pane-card__title">'
                + '<i class="ri-error-warning-line" style="color:var(--pane-error)"></i> '
                + 'Erro — ' + safeRoute
                + '</div>'
                + '<div class="pane-card__body">' + safeMsg + '</div>'
                + '</div></div>';
        },

        /**
         * Minimal HTML escape.
         * @param {string} str
         * @returns {string}
         */
        _esc(str) {
            const el = document.createElement('span');
            el.textContent = str;
            return el.innerHTML;
        },
    }));
});
