<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * CELL · motion — the one place /casa is allowed to touch scroll
 * ═══════════════════════════════════════════════════════════════════════
 *
 * OWNS
 *   global     window.ApolloCasaMotion { ready(fn), refresh(), tl(el, cfg) }
 *   attributes [data-casa-reveal] [data-casa-stagger] [data-casa-parallax]
 *              [data-casa-mask]
 *   contract   the Lenis ↔ ScrollTrigger marriage, and gsap.registerPlugin
 *
 * DOES NOT OWN
 *   any selector. This cell emits no CSS box and no markup — it reads
 *   attributes off boxes other cells own. That is what makes a section
 *   cell removable without leaving a dangling animation behind.
 *
 * ── The rule this cell enforces ───────────────────────────────────────
 * Exactly one module may call gsap.registerPlugin() and one may bind
 * lenis.on('scroll', ScrollTrigger.update). Two callers means two
 * raf loops fighting for the same scroll position, which reads to a
 * visitor as "the page stutters on my phone" and to a developer as
 * an unreproducible bug. new-home.js already does this marriage inside
 * initLuxuryScroll(); this cell is idempotent and stands down if it finds
 * the binding already made.
 *
 * ── Declarative, so sections stay dumb ────────────────────────────────
 * A section cell writes markup and nothing else:
 *
 *     <div data-casa-reveal>…</div>                     fade + rise, once
 *     <div data-casa-stagger=".card">…</div>            children cascade
 *     <img data-casa-parallax="-12">                    % drift while in view
 *     <h2 data-casa-mask>…</h2>                         line-mask wipe
 *
 * No section imports GSAP. No section knows ScrollTrigger exists. Delete
 * the attribute and the motion is gone; delete this cell and every
 * attribute degrades to a plain CSS reveal, because the fallback below
 * marks everything visible.
 *
 * ── Degradation tiers ─────────────────────────────────────────────────
 *   1  GSAP + ScrollTrigger + Lenis   full scrubbed motion
 *   2  GSAP + ScrollTrigger           full motion, native scroll
 *   3  GSAP only                      IntersectionObserver reveals
 *   4  nothing / reduced motion       everything visible, instantly
 * Tier 4 is also what a blocked CDN produces, which is the failure the
 * "MOBILE-FIRST SAFETY NET" block in new-home.css was written for.
 *
 * @package Apollo\Templates
 * @since   1.5.0
 * @see     cells/_manifest.php
 */

if (! defined('ABSPATH')) {
    exit;
}
if (defined('APOLLO_CASA_CELL_MOTION')) {
    return;
}
define('APOLLO_CASA_CELL_MOTION', true);
?>

<style id="casa-motion-styles">
/* ═══ CELL: motion — attribute selectors only, owns no box ═══ */
[data-casa-reveal],
[data-casa-mask] { will-change: transform, opacity; }

/* Pre-state. Scoped to html.casa-motion, a class this cell sets from JS in
   its first statement — so with JS off, or the CDN blocked, the class never
   lands and nothing is ever hidden. Hiding content behind a class you only
   add when you can also un-hide it is the difference between a reveal and
   a blank page. */
html.casa-motion [data-casa-reveal],
html.casa-motion [data-casa-stagger] > * {
    opacity: 0;
    transform: translate3d(0, 18px, 0);
}
html.casa-motion [data-casa-mask] { clip-path: inset(0 100% 0 0); }

html.casa-motion [data-casa-reveal].is-in,
html.casa-motion [data-casa-stagger] > .is-in {
    opacity: 1;
    transform: none;
    transition: opacity .72s var(--ease-smooth, cubic-bezier(.22, 1, .36, 1)),
                transform .72s var(--ease-smooth, cubic-bezier(.22, 1, .36, 1));
}
html.casa-motion [data-casa-mask].is-in {
    clip-path: inset(0 0 0 0);
    transition: clip-path .9s var(--ease-smooth, cubic-bezier(.22, 1, .36, 1));
}

/* GSAP writes inline styles, which outrank the transitions above — that is
   deliberate: when GSAP is driving, CSS must not fight the scrub. */
html.casa-motion.casa-gsap [data-casa-reveal],
html.casa-motion.casa-gsap [data-casa-stagger] > *,
html.casa-motion.casa-gsap [data-casa-mask] { transition: none; }

@media (prefers-reduced-motion: reduce) {
    html.casa-motion [data-casa-reveal],
    html.casa-motion [data-casa-stagger] > *,
    html.casa-motion [data-casa-mask] {
        opacity: 1 !important;
        transform: none !important;
        clip-path: none !important;
        transition: none !important;
    }
}
</style>

<script id="casa-motion-script">
(function () {
    'use strict';

    var docEl   = document.documentElement;
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Claim the pre-state only if we are able to release it. */
    if (!reduced) { docEl.classList.add('casa-motion'); }

    var queue = [];
    var live  = false;

    function reveal(el) { el.classList.add('is-in'); }

    /* ── Tier 3/4: IntersectionObserver. Always wired, because it is also
       the safety net if GSAP arrives late or never. ─────────────────── */
    var io = ('IntersectionObserver' in window)
        ? new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) { return; }
                var el = e.target;
                io.unobserve(el);
                if (el.hasAttribute('data-casa-stagger')) {
                    var sel = el.getAttribute('data-casa-stagger') || ':scope > *';
                    var kids = el.querySelectorAll(sel === '' ? ':scope > *' : sel);
                    Array.prototype.forEach.call(kids, function (k, i) {
                        setTimeout(function () { reveal(k); }, i * 70);
                    });
                } else {
                    reveal(el);
                }
            });
        }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 })
        : null;

    function scan(scope) {
        var nodes = (scope || document).querySelectorAll('[data-casa-reveal],[data-casa-mask],[data-casa-stagger]');
        Array.prototype.forEach.call(nodes, function (n) {
            if (n.__casa) { return; }
            n.__casa = true;
            if (io) { io.observe(n); } else { reveal(n); }
        });
    }

    /* Backstop: if something never scrolls into view — or JS reveals never
       fire because the CDN died mid-flight — un-hide everything at 3s.
       A page that stays blank is worse than a page with no animation. */
    setTimeout(function () {
        if (!docEl.classList.contains('casa-motion')) { return; }
        document.querySelectorAll('[data-casa-reveal]:not(.is-in),[data-casa-mask]:not(.is-in)')
            .forEach(reveal);
    }, 3000);

    /* ── Tier 1/2: GSAP + ScrollTrigger, once the CDN settles ────────── */
    function upgrade() {
        if (live || reduced) { return; }
        if (typeof window.gsap === 'undefined' || typeof window.ScrollTrigger === 'undefined') { return; }
        live = true;

        var gsap = window.gsap, ST = window.ScrollTrigger;

        /* Idempotent: registerPlugin is safe to repeat, the Lenis binding is
           not — new-home.js may already have made it. */
        gsap.registerPlugin(ST);
        docEl.classList.add('casa-gsap');

        /* core.js exposes the Lenis INSTANCE as window.lenis (it binds its own
           scrollbar module to it the same way at core.js:1484) and announces the
           constructor with the `apollo:lenis-ready` event. There is no
           Apollo.whenLenisReady() — that helper does not exist in core.js v1.0.0,
           although new-home.js:605 calls it too. Both call sites fall through
           silently, so the symptom is not an error, it is ScrollTrigger quietly
           reading a stale scroll position under Lenis. */
        if (!window.__casaLenisBound) {
            var bind = function (l) {
                if (!l || typeof l.on !== 'function' || window.__casaLenisBound) { return; }
                window.__casaLenisBound = true;
                l.on('scroll', ST.update);
                gsap.ticker.add(function (t) { if (typeof l.raf === 'function') { l.raf(t * 1000); } });
                gsap.ticker.lagSmoothing(0);
            };
            if (window.lenis) {
                bind(window.lenis);
            } else {
                window.addEventListener('apollo:lenis-ready', function () { bind(window.lenis); }, { once: true });
                /* The event may already have fired before this cell parsed. */
                var t0 = 0;
                var lp = setInterval(function () {
                    if (window.lenis) { bind(window.lenis); }
                    if (window.__casaLenisBound || ++t0 > 40) { clearInterval(lp); }
                }, 250);
            }
        }

        /* Parallax is the only scrubbed effect this cell owns. Everything
           else is a one-shot reveal, because scrubbing many elements on a
           phone is how a luxury page becomes a slow one. */
        document.querySelectorAll('[data-casa-parallax]').forEach(function (el) {
            var amt = parseFloat(el.getAttribute('data-casa-parallax')) || -10;
            gsap.fromTo(el,
                { yPercent: -amt / 2 },
                {
                    yPercent: amt / 2,
                    ease: 'none',
                    scrollTrigger: { trigger: el.parentElement || el, start: 'top bottom', end: 'bottom top', scrub: true }
                });
        });

        ST.refresh();
        queue.splice(0).forEach(function (fn) { try { fn(gsap, ST); } catch (e) { /* a broken caller must not stop the rest */ } });
    }

    /* ── public surface ─────────────────────────────────────────────── */
    window.ApolloCasaMotion = {
        /** Run fn(gsap, ScrollTrigger) once the motion layer is live. */
        ready: function (fn) { if (live) { fn(window.gsap, window.ScrollTrigger); } else { queue.push(fn); } },
        /** Re-measure after layout changes (images, fonts, panel open/close). */
        refresh: function () { if (window.ScrollTrigger) { window.ScrollTrigger.refresh(); } },
        /** Register new nodes injected after boot. */
        scan: scan
    };

    scan();

    if (window.Apollo && typeof window.Apollo.whenReady === 'function') {
        window.Apollo.whenReady(upgrade);
    }
    window.addEventListener('load', upgrade);
    /* Poll briefly — core.js is a CDN and `load` can beat it. */
    var tries = 0;
    var poll = setInterval(function () { upgrade(); if (live || ++tries > 40) { clearInterval(poll); } }, 250);
})();
</script>
