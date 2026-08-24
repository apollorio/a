/*!
 * Apollo Text FX — v1.0.0
 * ═══════════════════════════════════════════════════════════
 * Centralises every GSAP-powered text animation across the
 * Apollo ecosystem: typing effects, loading text cycles,
 * animated counters, and live time-ago refresh.
 *
 * Requires: GSAP 3 (via Apollo CDN core.js) +
 *           TextPlugin (via gsap-TextPlugin.chat.min.js CDN)
 *
 * Exposes: window.ApolloTextFX
 *
 * Usage:
 *   ApolloTextFX.typing(inputEl, phrases, opts)  → { start, stop, kill }
 *   ApolloTextFX.loading(textEl, messages, opts) → { stop }
 *   ApolloTextFX.counter(el, newVal, opts)
 *   ApolloTextFX.timeAgo(dateStr)                → { n, u }
 *   ApolloTextFX.initTimeAgoRefresh(interval?)
 *
 * @package Apollo\Chat
 * @since   2.0.0
 */
(function (global) {
    'use strict';

    /* ── Guard: register TextPlugin once GSAP is ready ── */
    var _pluginReady = false;
    function guardPlugin() {
        if (_pluginReady) return true;
        if (typeof gsap === 'undefined') return false;
        if (typeof TextPlugin !== 'undefined') {
            gsap.registerPlugin(TextPlugin);
            _pluginReady = true;
            return true;
        }
        return false;
    }

    /* ═══════════════════════════════════════════════════════
       1.  TYPING EFFECT — animated placeholder for inputs
       ═══════════════════════════════════════════════════════
       el      : HTMLInputElement | HTMLTextAreaElement
       phrases : string[]
       opts    : {
         typeSpeed     : number   (seconds / char,  default 0.08)
         deleteSpeed   : number   (seconds / char,  default 0.04)
         pauseAtEnd    : number   (seconds at full,  default 1.6)
         pauseBeforeNext: number  (seconds after del, default 0.5)
         placeholderClass: string (css class while animating)
         easeIn        : string   (GSAP ease for typing,  default 'none')
         easeOut       : string   (GSAP ease for delete, default 'power1.in')
       }
       returns : { start(), stop(), kill() }
    */
    function typing(el, phrases, opts) {
        if (!el || !phrases || !phrases.length) {
            return { start: noop, stop: noop, kill: noop };
        }
        opts = opts || {};
        var typeSpeed      = opts.typeSpeed       !== undefined ? opts.typeSpeed       : 0.08;
        var deleteSpeed    = opts.deleteSpeed      !== undefined ? opts.deleteSpeed     : 0.04;
        var pauseAtEnd     = opts.pauseAtEnd       !== undefined ? opts.pauseAtEnd      : 1.6;
        var pauseBeforeNext= opts.pauseBeforeNext  !== undefined ? opts.pauseBeforeNext : 0.5;
        var pClass         = opts.placeholderClass !== undefined ? opts.placeholderClass : 'apollo-typing-active';

        var tl      = null;
        var stopped = false;

        function build() {
            var master = gsap.timeline({ repeat: -1, paused: true });
            phrases.forEach(function (phrase) {
                var state = { n: 0 };
                var len   = phrase.length || 1;

                /* Type in */
                master.to(state, {
                    n:        len,
                    duration: len * typeSpeed,
                    ease:     opts.easeIn || 'none',
                    onUpdate: function () {
                        if (!stopped) {
                            el.value = phrase.substring(0, Math.round(state.n));
                            el.classList.add(pClass);
                        }
                    }
                })
                /* Pause at end */
                .to({}, { duration: pauseAtEnd })
                /* Delete */
                .to(state, {
                    n:        0,
                    duration: len * deleteSpeed,
                    ease:     opts.easeOut || 'power1.in',
                    onUpdate: function () {
                        if (!stopped) {
                            el.value = phrase.substring(0, Math.round(state.n));
                        }
                    }
                })
                /* Pause before next */
                .to({}, { duration: pauseBeforeNext });
            });
            return master;
        }

        function start() {
            stopped = false;
            if (tl) tl.kill();
            tl = build();
            tl.play();
        }

        function stop() {
            stopped = true;
            if (tl) tl.pause();
            el.value = '';
            el.classList.remove(pClass);
        }

        function kill() {
            stopped = true;
            if (tl) { tl.kill(); tl = null; }
        }

        return { start: start, stop: stop, kill: kill };
    }


    /* ═══════════════════════════════════════════════════════
       2.  LOADING TEXT — cycling messages during fetch/load
       ═══════════════════════════════════════════════════════
       textEl   : HTMLElement whose textContent will be animated
       messages : string[]   — cycling loading lines
       opts     : {
         interval  : number   (seconds between swaps, default 1.4)
         fadeTime  : number   (crossfade duration,    default 0.2)
         className : string   (class on the text span,default 'atfx-txt')
         endMessage: string|null
       }
       returns : { stop() }
    */
    function loading(textEl, messages, opts) {
        if (!textEl || !messages || !messages.length) {
            return { stop: noop };
        }
        opts = opts || {};
        var interval   = opts.interval   !== undefined ? opts.interval   : 1.4;
        var fadeTime   = opts.fadeTime   !== undefined ? opts.fadeTime   : 0.2;
        var endMessage = opts.endMessage !== undefined ? opts.endMessage : null;

        /* Seed first message */
        textEl.textContent = messages[0];
        gsap.set(textEl, { opacity: 1 });

        if (messages.length === 1) return { stop: noop };

        var tl = gsap.timeline({ repeat: -1 });

        messages.forEach(function (msg, i) {
            /* First message is already shown — just hold it */
            if (i === 0) {
                tl.to({}, { duration: interval });
                return;
            }
            tl
                .to(textEl, {
                    duration: fadeTime,
                    opacity:  0,
                    ease:     'power2.in',
                    onComplete: function () { textEl.textContent = msg; }
                })
                .to(textEl, {
                    duration: fadeTime,
                    opacity:  1,
                    ease:     'power2.out'
                })
                .to({}, { duration: Math.max(0.01, interval - fadeTime * 2) });
        });

        function stop() {
            tl.kill();
            if (endMessage !== null) {
                gsap.set(textEl, { opacity: 1 });
                textEl.textContent = endMessage;
            }
        }

        return { stop: stop };
    }


    /* ═══════════════════════════════════════════════════════
       3.  COUNTER — animated numeric / text update
       ═══════════════════════════════════════════════════════
       el     : HTMLElement
       newVal : number | string
       opts   : {
         duration : number (default 0.55)
         ease     : string (default 'power3.out')
         hideZero : bool   (empty textContent when val===0, default true)
         bounce   : bool   (micro-bounce animation, default true)
       }
    */
    function counter(el, newVal, opts) {
        if (!el) return;
        opts = opts || {};
        var duration = opts.duration !== undefined ? opts.duration : 0.55;
        var ease     = opts.ease     !== undefined ? opts.ease     : 'power3.out';
        var hideZero = opts.hideZero !== undefined ? opts.hideZero : true;
        var bounce   = opts.bounce   !== undefined ? opts.bounce   : true;

        var curr  = parseFloat(el.textContent) || 0;
        var next  = parseFloat(newVal)         || 0;
        if (curr === next) return;

        var state = { n: curr };

        gsap.to(state, {
            n:        next,
            duration: duration,
            ease:     ease,
            onUpdate: function () {
                el.textContent = Math.round(state.n);
            },
            onComplete: function () {
                if (hideZero && next <= 0) el.textContent = '';
            }
        });

        if (bounce) {
            /* Flash scale + colour pulse on the parent btn / container */
            var target = el.closest('button') || el;
            gsap.timeline()
                .to(target, { scale: 1.18, duration: 0.12, ease: 'power2.out' })
                .to(target, { scale: 1,    duration: 0.38, ease: 'elastic.out(1.2, 0.5)' });
        }
    }


    /* ═══════════════════════════════════════════════════════
       4.  TIME-AGO — compute relative timestamp string
       ═══════════════════════════════════════════════════════
       dateStr : ISO-ish string ('2025-03-14 22:00:00' or full ISO)
       returns : { n: string, u: string }  e.g. { n:'5', u:'min' }
    */
    function timeAgo(dateStr) {
        var d    = new Date(String(dateStr).replace(' ', 'T') + (dateStr.indexOf('Z') === -1 ? 'Z' : ''));
        var diff = Math.floor((Date.now() - d.getTime()) / 1000);
        if (diff <        60) return { n: String(diff),                      u: 's'   };
        if (diff <      3600) return { n: String(Math.floor(diff / 60)),     u: 'min' };
        if (diff <     86400) return { n: String(Math.floor(diff / 3600)),   u: 'h'   };
        if (diff <   2592000) return { n: String(Math.floor(diff / 86400)),  u: 'd'   };
        if (diff <  31536000) return { n: String(Math.floor(diff / 2592000)),u: 'm'   };
        return { n: String(Math.floor(diff / 31536000)), u: 'a' };
    }


    /* ═══════════════════════════════════════════════════════
       5.  TIME-AGO AUTO-REFRESH (MutationObserver + setInterval)
       ═══════════════════════════════════════════════════════
       Scans DOM for [data-timestamp] every `intervalMs` ms.
       Each element should have child .time-ago (number) and
       .when-ago (unit).  Uses TextPlugin to animate changes.
    */
    function initTimeAgoRefresh(intervalMs) {
        /* Guard: only ever run one refresh cycle per page */
        if (initTimeAgoRefresh._started) return;
        initTimeAgoRefresh._started = true;
        intervalMs = intervalMs || 15000;

        function refresh() {
            document.querySelectorAll('[data-timestamp]').forEach(function (root) {
                var ts = root.dataset.timestamp;
                if (!ts) return;
                var parsed  = timeAgo(ts);
                var numEl   = root.querySelector('.time-ago');
                var unitEl  = root.querySelector('.when-ago');

                if (numEl && numEl.textContent !== parsed.n) {
                    if (guardPlugin()) {
                        gsap.to(numEl, { duration: 0.3, text: parsed.n, ease: 'none' });
                    } else {
                        numEl.textContent = parsed.n;
                    }
                }
                if (unitEl && unitEl.textContent !== parsed.u) {
                    if (guardPlugin()) {
                        gsap.to(unitEl, { duration: 0.25, text: parsed.u, ease: 'none' });
                    } else {
                        unitEl.textContent = parsed.u;
                    }
                }
            });
        }

        setInterval(refresh, intervalMs);
        /* Also refresh when new content lands in the DOM — debounced 500 ms to
           prevent excessive calls during fast DOM mutations (chat renders, etc.) */
        if (typeof MutationObserver !== 'undefined') {
            var _moDebounce = null;
            var mo = new MutationObserver(function (mutations) {
                var added = mutations.some(function (m) { return m.addedNodes.length > 0; });
                if (!added) return;
                clearTimeout(_moDebounce);
                _moDebounce = setTimeout(refresh, 500);
            });
            mo.observe(document.body || document.documentElement, {
                childList: true,
                subtree:   true
            });
        }
    }


    /* ── Helpers ── */
    function noop() {}


    /* ── Bootstrap: register TextPlugin as soon as we can ── */
    (function bootstrap() {
        if (guardPlugin()) return;
        /* Retry up to 40 × 50 ms = 2 s after page load */
        var attempts = 0;
        var iv = setInterval(function () {
            if (guardPlugin() || ++attempts >= 40) clearInterval(iv);
        }, 50);
    }());


    /* ── Export ── */
    var ApolloTextFX = {
        typing:            typing,
        loading:           loading,
        counter:           counter,
        timeAgo:           timeAgo,
        initTimeAgoRefresh: initTimeAgoRefresh
    };

    global.ApolloTextFX = ApolloTextFX;

}(window));
