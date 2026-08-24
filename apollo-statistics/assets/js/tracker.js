/**
 * Apollo Tracker v2 — PostHog-inspired analytics engine.
 *
 * ┌────────────────────────── PIPELINE ──────────────────────────┐
 * │  [Page Load] → detectPageType() → start/resume session       │
 * │      ├── IntersectionObserver → scroll milestones 25/50/75/100
 * │      ├── Visibility API → time on page (active tab only)     │
 * │      ├── click delegate → a[href] → queue click event        │
 * │      ├── setInterval(30s) → heartbeat → /track/session       │
 * │      ├── apollo:radio:play → start radio session             │
 * │      ├── apollo:radio:pause → end radio session              │
 * │      └── pagehide → sendBeacon → final session data          │
 * │                                                               │
 * │  [Batch Queue] → flush every 5s OR 10 events                 │
 * │                → POST /apollo/v1/track/batch                  │
 * │                                                               │
 * │  [Session] → 30min idle = new session                         │
 * │            → 24h max = force new session                      │
 * │            → localStorage persistence                         │
 * └───────────────────────────────────────────────────────────────┘
 *
 * Global: window.ApolloTrack
 *
 * @package Apollo\Statistics
 * @since   2.0.0
 */
;(function (w, d) {
    'use strict';

    // ── Guard: skip admin, customizer, preview ──
    if (w.wp && w.wp.customize) return;
    if (d.body && d.body.classList.contains('wp-admin')) return;

    /* ═══════════════════════════════════════════════
       CONSTANTS
       ═══════════════════════════════════════════════ */

    var LS_SID          = 'apollo_sid';       // session id
    var LS_TS           = 'apollo_st';        // last activity timestamp
    var LS_START        = 'apollo_ss';        // session start timestamp
    var LS_PAGES        = 'apollo_pv';        // pages viewed count
    var IDLE_MS         = 30 * 60 * 1000;     // 30 min
    var MAX_MS          = 24 * 60 * 60 * 1000;// 24h
    var FLUSH_MS        = 5000;               // flush every 5s
    var FLUSH_SIZE      = 10;                 // or every 10 events
    var HEARTBEAT_MS    = 30000;              // heartbeat every 30s
    var MAX_BATCH       = 50;                 // max events per POST
    var SCROLL_MARKS    = [25, 50, 75, 100];  // IntersectionObserver milestones
    var API             = '/wp-json/apollo/v1/track/';

    /* ═══════════════════════════════════════════════
       STATE
       ═══════════════════════════════════════════════ */

    var queue           = [];
    var flushTid        = null;
    var heartbeatTid    = null;
    var pageEnterTs     = Date.now();
    var scrollMax       = 0;
    var scrollFired     = {};                 // {25:true, 50:true, ...}
    var visibleMs       = 0;                  // accumulated visible time
    var lastVisibleTs   = Date.now();
    var isVisible       = !d.hidden;
    var currentUrl      = location.href;
    var pushOrig        = history.pushState;
    var replOrig        = history.replaceState;

    // Radio
    var radioOn         = false;
    var radioStartTs    = 0;
    var radioTracks     = 0;
    var radioPauses     = 0;

    /* ═══════════════════════════════════════════════
       UTILITIES
       ═══════════════════════════════════════════════ */

    function uuid4() {
        if (crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
        });
    }

    var ts = Date.now;

    // localStorage for cross-tab persistence (plan spec)
    function ls(k, v) {
        try {
            if (v === undefined) return localStorage.getItem(k);
            localStorage.setItem(k, String(v));
        } catch (_) { return null; }
    }
    function lsNum(k, v) {
        if (v === undefined) { var n = ls(k); return n ? parseInt(n, 10) || 0 : 0; }
        ls(k, String(v));
    }
    function lsDel(k) { try { localStorage.removeItem(k); } catch (_) {} }

    function detectDevice() {
        var u = navigator.userAgent || '';
        if (/Mobi|Android/i.test(u)) return 'mobile';
        if (/Tablet|iPad/i.test(u)) return 'tablet';
        return 'desktop';
    }

    function detectBrowser() {
        var u = navigator.userAgent || '';
        if (/Firefox\//i.test(u))  return 'firefox';
        if (/Edg\//i.test(u))      return 'edge';
        if (/OPR\//i.test(u))      return 'opera';
        if (/Chrome\//i.test(u))   return 'chrome';
        if (/Safari\//i.test(u))   return 'safari';
        return 'other';
    }

    function utm(k) {
        try { return new URLSearchParams(location.search).get(k) || ''; }
        catch (_) { return ''; }
    }

    /**
     * Detect page type from WP body classes + Apollo route patterns.
     */
    function pageType() {
        var b = d.body;
        if (!b) return 'page';
        var c = b.className || '';
        // Apollo CPT singles
        if (c.indexOf('single-event') !== -1)       return 'event';
        if (c.indexOf('single-hub') !== -1)          return 'hub';
        if (c.indexOf('single-dj') !== -1)           return 'dj';
        if (c.indexOf('single-loc') !== -1)          return 'loc';
        if (c.indexOf('single-classified') !== -1)   return 'classified';
        if (c.indexOf('single-achievement') !== -1)  return 'achievement';
        if (c.indexOf('single-rank') !== -1)         return 'rank';
        if (c.indexOf('single-group') !== -1)        return 'group';
        if (c.indexOf('single-post') !== -1)         return 'post';
        if (c.indexOf('single-') !== -1)             return 'single';
        // Archives
        if (c.indexOf('post-type-archive') !== -1)   return 'archive';
        // Apollo special pages (URL-based detection)
        var p = location.pathname;
        if (/^\/id\/[^/]+\/?$/.test(p))              return 'profile';
        if (/^\/hub\/[^/]+\/?$/.test(p))             return 'hub';
        if (/^\/painel/.test(p))                     return 'dashboard';
        if (/^\/mensagens/.test(p))                  return 'chat';
        if (/^\/feed/.test(p))                       return 'feed';
        if (/^\/grupos|\/comunas|\/nucleos/.test(p)) return 'groups';
        if (/^\/conquistas|\/pontos|\/niveis|\/placar/.test(p)) return 'membership';
        if (/^\/acesso|\/registre|\/reset/.test(p))  return 'auth';
        if (/^\/apollo-gestor|\/projeto/.test(p))    return 'gestor';
        if (/^\/documentos/.test(p))                 return 'docs';
        // WP classics
        if (c.indexOf('home') !== -1)                return 'home';
        if (c.indexOf('archive') !== -1)             return 'archive';
        if (c.indexOf('search') !== -1)              return 'search';
        if (c.indexOf('error404') !== -1)            return '404';
        return 'page';
    }

    /**
     * Extract WP post ID from body classes.
     */
    function objectId() {
        if (!d.body) return 0;
        var m = d.body.className.match(/(?:postid|page-id)-(\d+)/);
        return m ? parseInt(m[1], 10) : 0;
    }

    /* ═══════════════════════════════════════════════
       SESSION (localStorage — persists across tabs)
       ═══════════════════════════════════════════════ */

    function getSession() {
        var sid     = ls(LS_SID);
        var lastTs  = lsNum(LS_TS);
        var startTs = lsNum(LS_START);
        var idle    = ts() - lastTs;
        var total   = ts() - startTs;

        if (sid && lastTs && idle < IDLE_MS && total < MAX_MS) {
            lsNum(LS_TS, ts());
            return sid;
        }

        // Close stale session.
        if (sid) closeSession(sid);

        // Start new.
        sid = uuid4();
        ls(LS_SID, sid);
        lsNum(LS_TS, ts());
        lsNum(LS_START, ts());
        lsNum(LS_PAGES, 0);
        openSession(sid);
        return sid;
    }

    function openSession(sid) {
        post('session', {
            session_id:   sid,
            action:       'start',
            entry_url:    location.href,
            referrer:     d.referrer || '',
            utm_source:   utm('utm_source'),
            utm_medium:   utm('utm_medium'),
            utm_campaign: utm('utm_campaign'),
            device_type:  detectDevice(),
            browser:      detectBrowser()
        });
    }

    function closeSession(sid) {
        var start = lsNum(LS_START);
        var dur   = start ? Math.round((ts() - start) / 1000) : 0;
        var pages = lsNum(LS_PAGES);
        post('session', {
            session_id:   sid,
            action:       'end',
            duration:     dur,
            pages_viewed: pages,
            exit_url:     location.href
        });
    }

    function heartbeat() {
        var sid = ls(LS_SID);
        if (!sid) return;
        var start = lsNum(LS_START);
        var dur   = start ? Math.round((ts() - start) / 1000) : 0;
        lsNum(LS_TS, ts());
        enqueue({
            type: 'session', session_id: sid, action: 'heartbeat',
            duration: dur, pages_viewed: lsNum(LS_PAGES), exit_url: location.href
        });
    }

    /* ═══════════════════════════════════════════════
       QUEUE & FLUSH (5s / 10 events)
       ═══════════════════════════════════════════════ */

    function enqueue(evt) {
        queue.push(evt);
        if (queue.length >= FLUSH_SIZE) flush();
    }

    function flush() {
        if (!queue.length) return;
        var batch = queue.splice(0, MAX_BATCH);
        send('batch', { events: batch }, true);
    }

    /** Immediate POST (session start/end, radio). */
    function post(endpoint, data) {
        send(endpoint, data, false);
    }

    /**
     * Low-level POST. Uses sendBeacon for keepalive batches.
     */
    function send(endpoint, data, keepalive) {
        var url = API + endpoint;
        var json = JSON.stringify(data);

        // sendBeacon for unload / background flush
        if (keepalive && navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([json], { type: 'application/json' }));
            return;
        }

        var headers = { 'Content-Type': 'application/json' };
        if (w.wpApiSettings && w.wpApiSettings.nonce) {
            headers['X-WP-Nonce'] = w.wpApiSettings.nonce;
        }

        try {
            fetch(url, {
                method: 'POST', headers: headers, body: json,
                credentials: 'same-origin', keepalive: !!keepalive
            }).catch(function () {});
        } catch (_) {}
    }

    /* ═══════════════════════════════════════════════
       PAGEVIEW AUTO-CAPTURE
       ═══════════════════════════════════════════════ */

    function capturePageview() {
        var sid = getSession();
        lsNum(LS_PAGES, lsNum(LS_PAGES) + 1);

        // Reset per-page state.
        scrollMax    = 0;
        scrollFired  = {};
        pageEnterTs  = ts();
        visibleMs    = 0;
        lastVisibleTs = ts();

        enqueue({
            type: 'pageview', session_id: sid,
            url: location.href, page_type: pageType(),
            object_id: objectId(), time_on_page: 0, scroll_depth: 0
        });

        // Re-setup IntersectionObserver for new page content.
        setupScrollObserver();
    }

    /**
     * On page leave: enqueue final time + scroll for current page.
     */
    function captureLeave() {
        var sid = ls(LS_SID);
        if (!sid) return;
        // Accumulate final visible time.
        if (isVisible) visibleMs += (ts() - lastVisibleTs);
        enqueue({
            type: 'pageview', session_id: sid,
            url: currentUrl, page_type: pageType(),
            object_id: objectId(),
            time_on_page: Math.round(visibleMs),
            scroll_depth: scrollMax
        });
    }

    /* ═══════════════════════════════════════════════
       CLICK AUTO-CAPTURE
       ═══════════════════════════════════════════════ */

    function onDocClick(e) {
        // Walk up to find closest <a>.
        var el = e.target, n = 0;
        while (el && n < 6) {
            if (el.tagName === 'A' && el.href) break;
            el = el.parentElement; n++;
        }
        if (!el || el.tagName !== 'A' || !el.href) return;

        var href = el.href;
        if (/^(javascript|mailto|tel):/i.test(href) || href === '#') return;

        var sid = ls(LS_SID);
        if (!sid) return;

        enqueue({
            type: 'click', session_id: sid,
            source_url: location.href, target_url: href,
            element_type: el.getAttribute('data-track-type') || (el.closest && el.closest('[data-hub-block]') ? 'hub-block' : 'link'),
            source_page_type: pageType(), source_object_id: objectId()
        });
    }

    /* ═══════════════════════════════════════════════
       SCROLL DEPTH — IntersectionObserver milestones
       ═══════════════════════════════════════════════ */

    var scrollObserver = null;
    var scrollSentinels = [];

    function setupScrollObserver() {
        // Cleanup previous sentinels.
        teardownScrollObserver();

        // Need IntersectionObserver support.
        if (!w.IntersectionObserver) {
            // Fallback: classic scroll listener.
            w.addEventListener('scroll', onScrollFallback, { passive: true });
            return;
        }

        // Create sentinel elements at 25%, 50%, 75%, 100% of body height.
        // We insert invisible divs and observe them.
        var docH = Math.max(d.documentElement.scrollHeight, d.body.scrollHeight);
        if (docH <= w.innerHeight) {
            scrollMax = 100;
            return; // Content fits viewport — 100% already.
        }

        scrollObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var mark = parseInt(entry.target.getAttribute('data-scroll-mark'), 10);
                if (mark > scrollMax) scrollMax = mark;
                if (!scrollFired[mark]) {
                    scrollFired[mark] = true;
                    var sid = ls(LS_SID);
                    if (sid) {
                        enqueue({
                            type: 'event', session_id: sid,
                            event_type: 'scroll_depth', object_id: objectId(),
                            value: mark
                        });
                    }
                }
                // Unobserve once triggered.
                scrollObserver.unobserve(entry.target);
            });
        }, { threshold: 0 });

        SCROLL_MARKS.forEach(function (pct) {
            var sentinel = d.createElement('div');
            sentinel.style.cssText = 'position:absolute;left:0;width:1px;height:1px;pointer-events:none;opacity:0;';
            sentinel.style.top = Math.min(Math.round(docH * pct / 100), docH - 1) + 'px';
            sentinel.setAttribute('data-scroll-mark', String(pct));
            sentinel.setAttribute('aria-hidden', 'true');
            d.body.appendChild(sentinel);
            scrollSentinels.push(sentinel);
            scrollObserver.observe(sentinel);
        });
    }

    function teardownScrollObserver() {
        if (scrollObserver) {
            scrollObserver.disconnect();
            scrollObserver = null;
        }
        scrollSentinels.forEach(function (el) {
            if (el.parentNode) el.parentNode.removeChild(el);
        });
        scrollSentinels = [];
        w.removeEventListener('scroll', onScrollFallback);
    }

    /** Fallback for browsers without IntersectionObserver. */
    function onScrollFallback() {
        var h = d.documentElement;
        var dH = Math.max(h.scrollHeight, h.offsetHeight, h.clientHeight);
        var vH = w.innerHeight || h.clientHeight;
        var sT = w.pageYOffset || h.scrollTop;
        if (dH <= vH) { scrollMax = 100; return; }
        var pct = Math.min(100, Math.round(((sT + vH) / dH) * 100));
        if (pct > scrollMax) scrollMax = pct;
    }

    /* ═══════════════════════════════════════════════
       VISIBILITY API — accurate time-on-page
       ═══════════════════════════════════════════════ */

    function onVisibility() {
        if (d.hidden) {
            // Tab went hidden — accumulate time so far.
            if (isVisible) visibleMs += (ts() - lastVisibleTs);
            isVisible = false;
            flush(); // flush on background
        } else {
            isVisible = true;
            lastVisibleTs = ts();
            // Touch session.
            var sid = ls(LS_SID);
            if (sid) lsNum(LS_TS, ts());
        }
    }

    /* ═══════════════════════════════════════════════
       SPA — history.pushState / replaceState
       ═══════════════════════════════════════════════ */

    function onUrlChange() {
        var u = location.href;
        if (u === currentUrl) return;
        captureLeave();
        currentUrl = u;
        capturePageview();
    }

    function patchHistory() {
        history.pushState = function () {
            pushOrig.apply(history, arguments);
            onUrlChange();
        };
        history.replaceState = function () {
            replOrig.apply(history, arguments);
            onUrlChange();
        };
        w.addEventListener('popstate', onUrlChange);
    }

    /* ═══════════════════════════════════════════════
       RADIO — CustomEvent listeners
       ═══════════════════════════════════════════════ */

    function onRadioPlay() {
        if (radioOn) return;
        radioOn = true;
        radioStartTs = ts();
        radioTracks++;
        post('radio', { session_id: ls(LS_SID) || getSession(), action: 'start' });
    }

    function onRadioPause() {
        if (!radioOn) return;
        radioOn = false;
        radioPauses++;
        var dur = radioStartTs ? Math.round((ts() - radioStartTs) / 1000) : 0;
        var sid = ls(LS_SID);
        if (!sid) return;
        post('radio', {
            session_id: sid, action: 'end',
            duration: dur, track_count: radioTracks, pause_count: radioPauses
        });
    }

    /* ═══════════════════════════════════════════════
       PAGE UNLOAD — final flush via sendBeacon
       ═══════════════════════════════════════════════ */

    function onUnload() {
        var sid = ls(LS_SID);
        if (!sid) return;

        // Final visible time accumulation.
        if (isVisible) visibleMs += (ts() - lastVisibleTs);

        enqueue({
            type: 'pageview', session_id: sid,
            url: location.href, page_type: pageType(), object_id: objectId(),
            time_on_page: Math.round(visibleMs), scroll_depth: scrollMax
        });

        if (radioOn) onRadioPause();

        flush(); // uses sendBeacon
    }

    /* ═══════════════════════════════════════════════
       PUBLIC API — window.ApolloTrack
       ═══════════════════════════════════════════════ */

    var AT = {
        _ready: false,

        /**
         * Initialize. Auto-called on DOMContentLoaded.
         * Idempotent — safe to call multiple times.
         */
        init: function () {
            if (AT._ready) return;
            AT._ready = true;

            // Session.
            getSession();

            // Initial pageview.
            capturePageview();

            // Click delegation.
            d.addEventListener('click', onDocClick, true);

            // Visibility.
            d.addEventListener('visibilitychange', onVisibility);

            // SPA navigation.
            patchHistory();

            // Radio custom events.
            w.addEventListener('apollo:radio:play', onRadioPlay);
            w.addEventListener('apollo:radio:pause', onRadioPause);

            // Flush timer — every 5s.
            flushTid = setInterval(function () {
                if (isVisible) flush();
            }, FLUSH_MS);

            // Heartbeat — every 30s.
            heartbeatTid = setInterval(function () {
                if (isVisible) heartbeat();
            }, HEARTBEAT_MS);

            // Unload.
            w.addEventListener('pagehide', onUnload);
            w.addEventListener('beforeunload', onUnload);
        },

        /**
         * Track a custom event manually.
         * @param {string} type   e.g. 'wow', 'fav', 'share', 'signup_cta'
         * @param {number} [id]   Related post/user ID.
         * @param {number} [val]  Numeric value (default 1).
         */
        track: function (type, id, val) {
            var sid = ls(LS_SID) || getSession();
            enqueue({
                type: 'event', session_id: sid,
                event_type: String(type || ''),
                object_id: parseInt(id, 10) || 0,
                value: parseInt(val, 10) || 1
            });
        },

        /** Force-flush the queue now. */
        flush: flush,

        /** Get current session ID. */
        getSessionId: function () { return ls(LS_SID); },

        /** Get scroll depth of current page. */
        getScrollDepth: function () { return scrollMax; },

        /** Get accumulated visible time on current page (ms). */
        getTimeOnPage: function () {
            var t = visibleMs;
            if (isVisible) t += (ts() - lastVisibleTs);
            return Math.round(t);
        }
    };

    // Expose globally.
    w.ApolloTrack = AT;

    /* ═══════════════════════════════════════════════
       BOOTSTRAP
       ═══════════════════════════════════════════════ */

    if (d.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', AT.init);
    } else {
        AT.init();
    }

})(window, document);
