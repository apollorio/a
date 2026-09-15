<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * CELL · lightbox-event — the single-event reader for /casa
 * ═══════════════════════════════════════════════════════════════════════
 *
 * OWNS
 *   markup     #cevRoot and everything inside it
 *   selectors  .cev-*            (nothing else in the codebase uses this prefix)
 *   global     window.ApolloEventLightbox { open(id, fromEl), close() }
 *   triggers   [data-casa-event="{id}"]  — any element, anywhere on the page
 *
 * DOES NOT OWN
 *   tokens (core.js), the topbar, the auth gate (auth-lightbox.php owns
 *   window.apolloAuthBox and this cell defers to it for locked actions).
 *
 * ── Why this cell exists ──────────────────────────────────────────────
 * events.php printed, for guests:
 *
 *     <a data-to="event-page" data-dir="right" data-id="123" href="#">
 *
 * `data-to` is bound by nobody. `ApolloSlider`, which the six panel-*.php
 * files call, is defined nowhere in the repository. page-home.php includes
 * no panel at all. And mural-router.php 302s every logged-in visitor to
 * /feed, so the guest branch is 100% of /casa traffic — meaning the primary
 * call to action of the events section resolved to `href="#"` and scrolled
 * the visitor to the top of the page. This cell is the missing half.
 *
 * ── Data ──────────────────────────────────────────────────────────────
 * GET apollo/v1/eventos/{id} — EventsController::get_event, READABLE bound
 * to __return_true and verified as an intended public read on 2026-08-05
 * (registry 09-plugins/apollo-events.json). A public GET needs no nonce, so
 * this cell ships none — which also keeps it clear of TBD-005, the ten
 * nonces currently sitting in data-* attributes across nine plugins.
 *
 * ── Motion ────────────────────────────────────────────────────────────
 * GSAP Flip animates the tapped card's artwork into the modal's hero, so
 * the event does not "appear" — it grows out of the thing you touched.
 * Flip is optional: without it the panel springs from the card's centre,
 * and under prefers-reduced-motion it simply fades. Three tiers, one
 * visual language.
 *
 * @package Apollo\Templates
 * @since   1.5.0
 * @see     cells/_manifest.php
 * @see     _inventory/CASA-MAP-2026-08-08.md  §4 F-01…F-09
 */

if (! defined('ABSPATH')) {
    exit;
}
if (defined('APOLLO_CASA_CELL_LIGHTBOX_EVENT')) {
    return;
}
define('APOLLO_CASA_CELL_LIGHTBOX_EVENT', true);

/* Public read — no nonce, by design. wp_json_encode per registry
   03-apollo-rule (never raw json_encode). */
$cev_cfg = wp_json_encode(
    array(
        'rest'  => esc_url_raw(rest_url('apollo/v1/eventos/')),
        'guest' => ! is_user_logged_in(),
        'i18n'  => array(
            'err'   => __('Não foi possível carregar este evento.', 'apollo-templates'),
            'retry' => __('Tentar novamente', 'apollo-templates'),
            'close' => __('Fechar', 'apollo-templates'),
        ),
    )
);
?>

<div class="cev" id="cevRoot" aria-hidden="true">
    <div class="cev-scrim" data-cev-close aria-hidden="true"></div>

    <div class="cev-panel" role="dialog" aria-modal="true" aria-labelledby="cevTitle" tabindex="-1">

        <button type="button" class="cev-x" data-cev-close
            aria-label="<?php esc_attr_e('Fechar', 'apollo-templates'); ?>">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>

        <div class="cev-scroll" data-lenis-prevent data-lenis-prevent-wheel data-lenis-prevent-touch>

            <figure class="cev-hero" id="cevHero">
                <img class="cev-hero-img" id="cevImg" alt="" />
                <div class="cev-hero-veil" aria-hidden="true"></div>
            </figure>

            <div class="cev-body">
                <p class="cev-eyebrow" id="cevEyebrow"></p>
                <h2 class="cev-title" id="cevTitle"></h2>

                <dl class="cev-facts" id="cevFacts"></dl>

                <div class="cev-prose" id="cevProse"></div>

                <div class="cev-gate">
                    <p class="cev-gate-note">
                        <i class="ri-shining-2-line" aria-hidden="true"></i>
                        <?php esc_html_e('Entre para favoritar, marcar presença e falar com a produção.', 'apollo-templates'); ?>
                    </p>
                    <div class="cev-actions">
                        <a class="cev-btn cev-btn--ghost" id="cevOut" href="#" target="_blank" rel="noopener">
                            <i class="ri-external-link-line" aria-hidden="true"></i>
                            <?php esc_html_e('Página do evento', 'apollo-templates'); ?>
                        </a>
                        <button type="button" class="cev-btn cev-btn--solid" data-auth-required>
                            <i class="ri-shining-2-fill" aria-hidden="true"></i>
                            <?php esc_html_e('Entrar', 'apollo-templates'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="cev-state" id="cevBusy" aria-live="polite">
                <span class="cev-spin" aria-hidden="true"></span>
            </div>

            <div class="cev-state cev-state--err" id="cevErr" role="alert" hidden>
                <i class="ri-signal-wifi-error-line" aria-hidden="true"></i>
                <p></p>
                <button type="button" class="cev-btn cev-btn--ghost" id="cevRetry"></button>
            </div>

        </div>
    </div>
</div>

<style id="cev-styles">
/* ═══ CELL: lightbox-event ═══════════════════════════════════════════════
   OWNS every `.cev-*` selector and `body.cev-lock`. Nothing else in the
   codebase uses this prefix, so this block can never collide with another
   cell — which is the whole point of prefixing a cell.
   Component-scoped custom properties only. `:root` belongs to core.js.
   Every geometry var() carries a fallback: core.js is a CDN, and an
   unresolved token invalidates the whole declaration at computed-value
   time (see CASA-MAP F-02). */
.cev {
    --cev-pad: clamp(20px, 5vw, 44px);
    --cev-w: min(920px, 100%);
    --cev-r: var(--r, 20px);
    --cev-ease: var(--ease, cubic-bezier(.16, 1, .3, 1));

    position: fixed;
    inset: 0;
    z-index: 10000;
    display: grid;
    place-items: end center;
    /* clip, not hidden — hidden would force overflow-y:auto and kill any
       position:sticky inside the panel */
    overflow-x: clip;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .34s var(--cev-ease), visibility .34s;
}
.cev.is-open { opacity: 1; visibility: visible; pointer-events: auto; }

@media (min-width: 768px) { .cev { place-items: center; } }

.cev-scrim {
    position: absolute;
    inset: 0;
    background: rgba(var(--rgb-diff, 19, 21, 23), .52);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
    backdrop-filter: blur(14px) saturate(140%);
}

/* Glass panel — DS visual language, copied from the contract, not re-tuned */
.cev-panel {
    position: relative;
    width: var(--cev-w);
    max-width: 100%;
    max-height: min(92dvh, 100%);
    display: flex;
    flex-direction: column;
    border-radius: var(--cev-r) var(--cev-r) 0 0;
    background: rgba(var(--rgb-theme, 255, 255, 255), .25);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    backdrop-filter: blur(20px) saturate(180%);
    box-shadow:
        inset 0 1px 0 0 rgba(var(--rgb-theme, 255, 255, 255), .9),
        inset 0 0 0 1px rgba(var(--rgb-theme, 255, 255, 255), .4),
        0 40px 120px rgba(var(--rgb-diff, 19, 21, 23), .28);
    border: 1px solid rgba(var(--rgb-diff, 19, 21, 23), .04);
    transform: translateY(24px) scale(.985);
    transition: transform .42s var(--cev-ease);
    outline: none;
    overflow: hidden;
}
.cev.is-open .cev-panel { transform: none; }

@media (min-width: 768px) {
    .cev-panel { border-radius: var(--cev-r); max-height: min(88dvh, 100%); }
}

/* Grab handle — reads as a sheet on phones, invisible on desktop */
.cev-panel::before {
    content: '';
    position: absolute;
    top: 9px; left: 50%;
    width: 38px; height: 4px;
    transform: translateX(-50%);
    border-radius: var(--r-pill, 999px);
    background: rgba(var(--rgb-diff, 19, 21, 23), .16);
    z-index: 3;
    pointer-events: none;
}
@media (min-width: 768px) { .cev-panel::before { display: none; } }

.cev-x {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 4;
    width: 40px; height: 40px;
    display: grid; place-items: center;
    border: 0;
    border-radius: var(--r-pill, 999px);
    background: rgba(var(--rgb-diff, 19, 21, 23), .42);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    transition: background .3s var(--cev-ease), transform .3s var(--cev-ease);
}
@media (hover: hover) { .cev-x:hover { background: rgba(var(--rgb-diff, 19, 21, 23), .68); transform: rotate(90deg); } }

.cev-scroll {
    overflow-y: auto;
    overflow-x: clip;
    overscroll-behavior: contain;
    scrollbar-width: none;
    padding-bottom: calc(12px + var(--safe-bottom, 0px));
}
.cev-scroll::-webkit-scrollbar { display: none; }

.cev-hero {
    position: relative;
    margin: 0;
    aspect-ratio: 16 / 10;
    background: var(--onyx-700, #1b1b1e);
    overflow: hidden;
}
@media (min-width: 768px) { .cev-hero { aspect-ratio: 21 / 9; } }

.cev-hero-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cev-hero-veil {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, .28) 0%, rgba(0, 0, 0, 0) 38%, rgba(var(--rgb-theme, 255, 255, 255), .28) 100%);
}

.cev-body { padding: 22px var(--cev-pad) var(--cev-pad); }

/* mono eyebrow — DS type contract */
.cev-eyebrow {
    margin: 0 0 8px;
    font-family: var(--ff-mono, ui-monospace, monospace);
    font-size: 10px;
    letter-spacing: .13em;
    text-transform: uppercase;
    color: var(--muted, #8a8a8a);
}

/* display type — DS type contract */
.cev-title {
    margin: 0 0 20px;
    font-family: var(--ff-heading, var(--ff-main, system-ui));
    font-weight: 800;
    font-size: clamp(1.6rem, 5vw, 2.6rem);
    line-height: 1.04;
    letter-spacing: -.035em;
    color: var(--txt-heading, #0a0a0a);
    overflow-wrap: anywhere;
}

.cev-facts {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1px;
    margin: 0 0 24px;
    border-radius: var(--r-sm, 12px);
    overflow: hidden;
    background: rgba(var(--rgb-diff, 19, 21, 23), .06);
}
@media (min-width: 560px) { .cev-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.cev-fact {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    padding: 14px 16px;
    background: rgba(var(--rgb-theme, 255, 255, 255), .38);
}
.cev-fact i { font-size: 17px; color: var(--muted, #8a8a8a); flex-shrink: 0; }
.cev-fact-txt { min-width: 0; }
.cev-fact-k {
    display: block;
    font-family: var(--ff-mono, ui-monospace, monospace);
    font-size: 9px;
    letter-spacing: .13em;
    text-transform: uppercase;
    color: var(--muted, #8a8a8a);
}
.cev-fact-v {
    display: block;
    font-size: .92rem;
    font-weight: 600;
    color: var(--txt-heading, #0a0a0a);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cev-prose {
    font-size: .98rem;
    line-height: 1.72;
    color: var(--txt-body, #3a3a3a);
    overflow-wrap: anywhere;
}
.cev-prose > * + * { margin-top: 1em; }
.cev-prose img, .cev-prose iframe { max-width: 100%; height: auto; border-radius: var(--r-sm, 12px); }

.cev-gate { margin-top: 28px; padding-top: 22px; border-top: 1px solid rgba(var(--rgb-diff, 19, 21, 23), .08); }
.cev-gate-note {
    display: flex; align-items: flex-start; gap: 9px;
    margin: 0 0 16px;
    font-family: var(--ff-mono, ui-monospace, monospace);
    font-size: .74rem; line-height: 1.6;
    color: var(--muted, #8a8a8a);
}
.cev-gate-note i { font-size: 14px; flex-shrink: 0; margin-top: 2px; }

.cev-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.cev-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: 46px; padding: 0 20px;
    border: 0; border-radius: var(--r-pill, 999px);
    font-family: var(--ff-main, system-ui);
    font-size: .86rem; font-weight: 600;
    text-decoration: none; white-space: nowrap; cursor: pointer;
    transition: transform .28s var(--cev-ease), background .28s var(--cev-ease), box-shadow .28s var(--cev-ease);
    touch-action: manipulation;
}
.cev-btn--solid { background: var(--black-1, #131517); color: var(--white-1, #fff); }
.cev-btn--ghost {
    background: transparent;
    color: var(--txt-heading, #0a0a0a);
    box-shadow: inset 0 0 0 1px rgba(var(--rgb-diff, 19, 21, 23), .14);
}
@media (hover: hover) {
    .cev-btn--solid:hover { box-shadow: inset 0 0 0 1px var(--accent, #FF7A00); transform: translateY(-1px); }
    .cev-btn--ghost:hover { box-shadow: inset 0 0 0 1px rgba(var(--rgb-diff, 19, 21, 23), .3); transform: translateY(-1px); }
}
.cev-btn:active { transform: scale(.97); }

.cev-state {
    display: grid; place-items: center; gap: 12px;
    padding: 64px var(--cev-pad);
    text-align: center;
    font-family: var(--ff-mono, ui-monospace, monospace);
    font-size: .78rem;
    color: var(--muted, #8a8a8a);
}
.cev-state[hidden] { display: none; }
.cev-state--err i { font-size: 26px; color: var(--muted, #8a8a8a); }
.cev-state--err p { margin: 0; }

.cev-spin {
    width: 26px; height: 26px;
    border-radius: 50%;
    border: 2px solid rgba(var(--rgb-diff, 19, 21, 23), .12);
    border-top-color: var(--accent, #FF7A00);
    animation: cev-spin .72s linear infinite;
}
@keyframes cev-spin { to { transform: rotate(360deg); } }

/* Loading / error states own the panel exclusively */
.cev[data-cev-state="busy"] .cev-hero,
.cev[data-cev-state="busy"] .cev-body,
.cev[data-cev-state="err"]  .cev-hero,
.cev[data-cev-state="err"]  .cev-body,
.cev[data-cev-state="ready"] #cevBusy { display: none; }

body.cev-lock { overflow: hidden; }

@media (prefers-reduced-motion: reduce) {
    .cev, .cev-panel, .cev-btn, .cev-x { transition-duration: .01ms !important; }
    .cev-spin { animation-duration: 2s; }
}
</style>

<script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> id="cev-script">
(function () {
    'use strict';

    var CFG  = <?php echo $cev_cfg; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_json_encode output. ?>;
    var root = document.getElementById('cevRoot');
    if (!root) { return; }

    var panel  = root.querySelector('.cev-panel');
    var scroll = root.querySelector('.cev-scroll');
    var img    = document.getElementById('cevImg');
    var hero   = document.getElementById('cevHero');
    var elTtl  = document.getElementById('cevTitle');
    var elEye  = document.getElementById('cevEyebrow');
    var elFact = document.getElementById('cevFacts');
    var elPro  = document.getElementById('cevProse');
    var elOut  = document.getElementById('cevOut');
    var elErr  = document.getElementById('cevErr');
    var elRtry = document.getElementById('cevRetry');

    var cache   = Object.create(null);
    var lastAt  = null;   /* element that opened us — focus returns here */
    var lastReq = 0;      /* guards out-of-order responses */
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    elRtry.textContent = CFG.i18n.retry;
    elErr.querySelector('p').textContent = CFG.i18n.err;

    /* ── helpers ──────────────────────────────────────────────────── */

    function state(s) { root.setAttribute('data-cev-state', s); }

    function txt(v) { return (v === null || v === undefined) ? '' : String(v); }

    function fact(icon, key, value) {
        if (!value) { return ''; }
        var d = document.createElement('div');
        d.className = 'cev-fact';
        var i = document.createElement('i');
        i.className = icon;
        i.setAttribute('aria-hidden', 'true');
        var w = document.createElement('div');
        w.className = 'cev-fact-txt';
        var k = document.createElement('span');
        k.className = 'cev-fact-k';
        k.textContent = key;
        var v = document.createElement('span');
        v.className = 'cev-fact-v';
        v.textContent = value;      /* textContent, never innerHTML — no injection surface */
        v.title = value;
        w.appendChild(k); w.appendChild(v);
        d.appendChild(i); d.appendChild(w);
        return d;
    }

    /* The REST shape is not contractually frozen, so read defensively and
       fall back rather than render "undefined" at a visitor. */
    function pick(o) {
        for (var i = 1; i < arguments.length; i++) {
            var k = arguments[i];
            if (o && o[k] !== undefined && o[k] !== null && o[k] !== '') { return o[k]; }
        }
        return '';
    }

    function paint(ev) {
        var title = txt(pick(ev, 'title', 'post_title', 'name'));
        if (title && title.rendered) { title = title.rendered; }

        var cover = txt(pick(ev, 'cover', 'image', 'thumbnail', 'featured_image', 'hero_image'));
        var link  = txt(pick(ev, 'permalink', 'link', 'url'));
        var when  = txt(pick(ev, 'date_label', 'date_formatted', 'start_label', 'date', 'start'));
        var loc   = txt(pick(ev, 'local_name', 'loc_name', 'venue', 'local'));
        var line  = txt(pick(ev, 'lineup_text', 'lineup', 'artists'));
        var genre = txt(pick(ev, 'genres_text', 'genres', 'sound'));

        elTtl.textContent = title;
        elEye.textContent = when || '';

        if (cover) { img.src = cover; img.alt = title; hero.hidden = false; }
        else { hero.hidden = true; }

        elFact.textContent = '';
        [
            fact('ri-calendar-line',    'Quando',  when),
            fact('ri-map-pin-2-line',   'Onde',    loc),
            fact('ri-sound-module-fill', 'Line-up', line),
            fact('ri-music-2-line',     'Som',     genre)
        ].forEach(function (n) { if (n) { elFact.appendChild(n); } });

        /* Description arrives as trusted server HTML (wp_kses_post'd by the
           controller). Anything we did not author goes in via textContent. */
        var desc = pick(ev, 'excerpt', 'description', 'content');
        if (desc && desc.rendered) { desc = desc.rendered; }
        elPro.innerHTML = txt(desc);

        if (link) { elOut.href = link; elOut.hidden = false; }
        else { elOut.hidden = true; }

        state('ready');
    }

    function load(id) {
        var req = ++lastReq;

        if (cache[id]) { paint(cache[id]); return; }

        state('busy');
        elErr.hidden = true;

        fetch(CFG.rest + encodeURIComponent(id), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (r) {
                if (!r.ok) { throw new Error(String(r.status)); }
                return r.json();
            })
            .then(function (data) {
                if (req !== lastReq) { return; }        /* a newer open won */
                var ev = (data && data.data) ? data.data : data;
                cache[id] = ev;
                paint(ev);
            })
            .catch(function () {
                if (req !== lastReq) { return; }
                state('err');
                elErr.hidden = false;
                elRtry.onclick = function () { load(id); };
            });
    }

    /* ── Flip: the panel grows out of the card you touched ────────────
       Optional by design. Flip present → true FLIP off the card artwork.
       GSAP only → spring from the card's centre. Neither → CSS transition
       already declared in the stylesheet. */
    function enter(fromEl) {
        if (reduced || !window.gsap) { return; }

        var art = fromEl && (fromEl.querySelector('.a-eve-media, img') || fromEl);
        if (window.Flip && art && hero && !hero.hidden) {
            try {
                var st = window.Flip.getState(art);
                window.Flip.from(st, {
                    targets: hero,
                    duration: reduced ? 0 : .62,
                    ease: 'expo.out',
                    absolute: true,
                    scale: true,
                    onComplete: function () { window.gsap.set(hero, { clearProps: 'all' }); }
                });
                return;
            } catch (e) { /* fall through to the spring */ }
        }

        if (art) {
            var b = art.getBoundingClientRect();
            var p = panel.getBoundingClientRect();
            window.gsap.fromTo(
                panel,
                {
                    x: (b.left + b.width / 2) - (p.left + p.width / 2),
                    y: (b.top + b.height / 2) - (p.top + p.height / 2),
                    scale: Math.max(.22, Math.min(1, b.width / Math.max(p.width, 1))),
                    opacity: .4
                },
                { x: 0, y: 0, scale: 1, opacity: 1, duration: .58, ease: 'expo.out', clearProps: 'transform,opacity' }
            );
        }
    }

    /* ── open / close ─────────────────────────────────────────────── */

    function open(id, fromEl) {
        if (!id) { return; }
        lastAt = fromEl || document.activeElement;

        root.classList.add('is-open');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('cev-lock');
        if (scroll) { scroll.scrollTop = 0; }

        /* Lenis owns document scroll; ask it to stand down while we're up. */
        if (window.lenis && typeof window.lenis.stop === 'function') { window.lenis.stop(); }

        load(id);
        enter(fromEl);
        panel.focus({ preventScroll: true });
    }

    function close() {
        if (!root.classList.contains('is-open')) { return; }
        root.classList.remove('is-open');
        root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('cev-lock');

        if (window.lenis && typeof window.lenis.start === 'function') { window.lenis.start(); }
        if (lastAt && typeof lastAt.focus === 'function') { lastAt.focus({ preventScroll: true }); }
        lastAt = null;
    }

    /* ── wiring ───────────────────────────────────────────────────── */

    root.addEventListener('click', function (e) {
        if (e.target.closest('[data-cev-close]')) { close(); }
    });

    /* Delegated: any [data-casa-event] anywhere, now or added later. */
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-casa-event]');
        if (!t) { return; }
        e.preventDefault();
        open(t.getAttribute('data-casa-event'), t);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && root.classList.contains('is-open')) { close(); }
    });

    /* Focus trap — a modal that leaks focus to the page behind it is not a
       modal, it is a decoration. */
    panel.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab') { return; }
        var f = panel.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
        var list = Array.prototype.filter.call(f, function (n) { return n.offsetParent !== null; });
        if (!list.length) { return; }
        var first = list[0], last = list[list.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    window.ApolloEventLightbox = { open: open, close: close };
})();
</script>
