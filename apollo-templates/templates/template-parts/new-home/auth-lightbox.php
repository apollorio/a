<?php

/**
 * New Home — Auth Lightbox ("Opa, seu nome não tá na lista...")
 *
 * Shared gate overlay for guest-restricted content on /casa. Anything
 * carrying [data-auth-required] opens THIS instead of hard-navigating to
 * /acesso, so the visitor keeps their place on the page and sees why the
 * content is locked before deciding to log in.
 *
 * Currently gated by this lightbox:
 *   1. Out Now!  — DJ track releases          (tracks.php)
 *   2. Resell    — ticket classifieds          (classifieds.php)
 *   3. Acomoda   — accommodation listings      (crash.php, non-hostel only)
 *
 * Hostels are deliberately NOT gated — they're public, worldwide-listed
 * businesses, so their card links straight out to the hostel's own URL.
 * See _classified_hostel in apollo-core's MetaRegistry.
 *
 * Markup is the hub.rio screen's own .hub-hero / .hub-tag / .hub-notice
 * contract (screen/_official_layout/js/view.hub.js), reproduced verbatim so
 * the gate reads as the same surface users meet on Hub.rio. Styles below are
 * ported from that file's STYLE array, re-scoped from `.hub-screen` to
 * `.nh-authbox` (this is an overlay, not the hub screen itself).
 *
 * Guests only — for logged-in users this whole part renders nothing.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (is_user_logged_in()) {
    return;
}
?>

<div class="nh-authbox" id="nhAuthBox" aria-hidden="true">
    <div class="nh-authbox-backdrop" data-authbox-close></div>
    <div class="nh-authbox-card" role="dialog" aria-modal="true" aria-labelledby="nhAuthBoxTitle">
        <button type="button" class="nh-authbox-close" data-authbox-close
            aria-label="<?php esc_attr_e('Fechar', 'apollo-templates'); ?>">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>

        <div class="hub-hero">
            <i class="ri-body-scan-line" aria-hidden="true"></i>
            <h1 id="nhAuthBoxTitle">Opa, seu nome não tá na lista...</h1>
            <p class="hub-tag">Entre para ter acesso</p>
            <div class="hub-notice">
                <p>
                    Pra entrar na lista, faça seu login e garanta acesso a este conteúdo e muito mais!
                    <br>
                    <a href="<?php echo esc_url(home_url('/acesso')); ?>">
                        <button type="button" class="btn-primary">Entrar</button>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    /* ── Overlay shell ──────────────────────────────────────────────────────
       The gate itself. Sits above every /casa layer including the topbar
       (z-9901) and the FAB menu sheet, because it's a hard stop: nothing
       behind it is actionable while it's open. */
    .nh-authbox {
        position: fixed;
        inset: 0;
        z-index: 10090;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .32s var(--ease, ease), visibility .32s;
    }

    .nh-authbox.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .nh-authbox-backdrop {
        position: absolute;
        inset: 0;
        cursor: pointer;
        background: rgba(var(--rgb-diff), .42);
        backdrop-filter: blur(20px) saturate(140%);
        -webkit-backdrop-filter: blur(20px) saturate(140%);
    }

    .nh-authbox-card {
        position: relative;
        width: 100%;
        max-width: 640px;
        max-height: min(88dvh, 720px);
        overflow-y: auto;
        border-radius: var(--r-lg, 18px);
        background: var(--bg);
        border: 1px solid rgba(var(--rgb-diff), .04);
        box-shadow:
            inset 0 1px 0 0 rgba(var(--rgb-theme), .9),
            inset 0 0 0 1px rgba(var(--rgb-theme), .4),
            0 32px 80px -24px rgba(var(--rgb-diff), .5);
        transform: translateY(18px) scale(.985);
        transition: transform .38s var(--ease, cubic-bezier(.22, 1, .36, 1));
    }

    .nh-authbox.is-open .nh-authbox-card {
        transform: translateY(0) scale(1);
    }

    .nh-authbox-close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: var(--r-pill, 999px);
        background: var(--surface);
        color: var(--muted);
        font-size: 18px;
        cursor: pointer;
        z-index: 2;
        transition: color .3s var(--ease, ease), background .3s var(--ease, ease);
    }

    .nh-authbox-close:hover {
        color: var(--txt-heading);
        background: color-mix(in srgb, var(--txt-heading) 8%, transparent);
    }

    /* ── hub.rio contract ───────────────────────────────────────────────────
       Ported from view.hub.js's STYLE array, `.hub-screen` → `.nh-authbox`.
       Values unchanged so the gate is visually identical to the Hub.rio
       screen users already know. */
    .nh-authbox .hub-hero {
        text-align: center;
        padding: 44px 16px 28px;
        max-width: 640px;
        margin: 0 auto;
    }

    .nh-authbox .hub-hero>i {
        font-size: 44px;
        color: var(--accent);
        display: block;
        margin-bottom: 14px;
        line-height: 1;
        vertical-align: 0;
    }

    .nh-authbox .hub-hero h1 {
        font-size: calc(var(--fsx, 1) * 28px);
        font-weight: 700;
        letter-spacing: -.03em;
        color: var(--txt-heading);
        margin: 0 0 6px;
    }

    .nh-authbox .hub-hero>p.hub-tag {
        font-family: var(--ff-mono);
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: .16em;
        color: var(--muted);
        margin: 0 0 18px;
    }

    .nh-authbox .hub-notice {
        margin: 0 auto;
        max-width: 520px;
        padding: 16px 18px;
        border-radius: var(--r);
        background: var(--surface);
        border: 1px solid rgba(var(--rgb-diff), .04);
        box-shadow:
            inset 0 1px 0 0 rgba(var(--rgb-theme), .9),
            inset 0 0 0 1px rgba(var(--rgb-theme), .4);
        text-align: left;
    }

    .nh-authbox .hub-notice p {
        font-size: calc(var(--fs-r, 1) * 13px);
        line-height: 1.55;
        color: var(--txt-color);
        margin: 0;
    }

    /* The supplied markup nests the CTA button inside its own <a>; give that
       pair a real hit area and right-alignment instead of leaving it inline. */
    .nh-authbox .hub-notice a {
        display: block;
        margin-top: 14px;
        text-align: right;
        text-decoration: none;
    }

    .nh-authbox .hub-notice .btn-primary {
        min-height: 42px;
        padding: 0 26px;
        border: 0;
        border-radius: var(--r-pill, 999px);
        font-family: var(--ff-main);
        font-size: calc(var(--fs-r, 1) * 13px);
        font-weight: 600;
        color: var(--bg);
        background: var(--accent);
        cursor: pointer;
        transition: transform .2s var(--ease-snappy, ease), opacity .25s var(--ease, ease);
    }

    .nh-authbox .hub-notice .btn-primary:hover {
        opacity: .88;
    }

    .nh-authbox .hub-notice .btn-primary:active {
        transform: scale(.97);
    }

    body.nh-authbox-lock {
        overflow: hidden;
    }

    /* Gated surfaces read as pressable — a card that opens a dialog should
       not look inert. Applies to the marquee cards in classifieds.php /
       crash.php and the track cards in tracks.php. */
    [data-auth-required] {
        cursor: pointer;
    }

    [data-auth-required]:focus-visible {
        outline: 2px solid color-mix(in srgb, var(--accent) 70%, transparent);
        outline-offset: 3px;
    }

    @media (prefers-reduced-motion: reduce) {

        .nh-authbox,
        .nh-authbox-card {
            transition: none;
        }
    }
</style>

<script>
    (function () {
        'use strict';

        var box = document.getElementById('nhAuthBox');
        if (!box) return;

        var lastFocus = null;

        function open() {
            lastFocus = document.activeElement;
            box.classList.add('is-open');
            box.setAttribute('aria-hidden', 'false');
            document.body.classList.add('nh-authbox-lock');
            var close = box.querySelector('.nh-authbox-close');
            if (close) close.focus();
        }

        function close() {
            box.classList.remove('is-open');
            box.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('nh-authbox-lock');
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        }

        /* Exposed so page-home.php's global [data-auth-required] handler (and
           any future gated surface) can trigger the gate without re-querying
           the DOM or duplicating open/close logic. */
        window.apolloAuthBox = { open: open, close: close };

        box.addEventListener('click', function (e) {
            if (e.target.closest('[data-authbox-close]')) {
                e.preventDefault();
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && box.classList.contains('is-open')) {
                close();
                return;
            }
            /* Gated cards are role="button" but aren't real buttons, so the
               browser won't fire a click for Enter/Space — do it here, or the
               gate would be mouse-only. */
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var t = e.target.closest && e.target.closest('[data-auth-required]');
            if (!t || box.classList.contains('is-open')) return;
            e.preventDefault();
            open();
        });
    })();
</script>
