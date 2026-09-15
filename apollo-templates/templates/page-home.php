<?php

/**
 * Template Name: Apollo Home
 * Template Post Type: page
 *
 * Apollo Home — Scrollable landing page for all visitors.
 * Sections: Hero → Marquee → Tracks → Events → Classifieds → Crash → Map → Footer
 * Blank Canvas (no wp_head/wp_footer — CDN loads everything).
 *
 * Template Parts (template-parts/new-home/):
 *   ┌─ navbar.php ............ Persistent pill navbar (fixed top)
 *   ├─ menu-fab.php .......... Floating action button + sheet
 *   ├─ hero.php .............. Video hero + CTA
 *   ├─ marquee.php ........... Infinite scroll marquee
 *   ├─ tracks.php ............ DJ tracks / SoundCloud embeds
 *   ├─ events.php ............ Upcoming events grid (a-v2-eve-C cards)
 *   ├─ classifieds.php ....... Ticket re-sell list (a-v2-list-B rows)
 *   ├─ crash.php ............. CTA crash section
 *   ├─ map.php ............... Leaflet map with event loc markers
 *   └─ footer.php ............ Site footer
 *
 * @package Apollo\Templates
 * @since   1.0.x
 */

if (! defined('ABSPATH')) {
    exit;
}

$parts     = plugin_dir_path(__FILE__) . 'template-parts/new-home/';
$is_logged = is_user_logged_in();

/* Cell catalogue — the lego socket board. Sections stay dumb markup; the
   manifest decides what plugs in and in what order. See cells/_manifest.php. */
require_once $parts . 'cells/_manifest.php';

/* /casa owns its topbar (showcase .ax-top). Claim the navbar slot so
   apollo_templates_inject_navbar_footer() early-returns and never injects the
   legacy <nav class="nh-navbar"> on top of it — that double topbar is what
   broke the logged-in header and escaped the scroll-dim rules. */
if (! defined('APOLLO_NAVBAR_LOADED')) {
    define('APOLLO_NAVBAR_LOADED', true);
}

do_action('apollo/home/before_content');

$ver = defined('APOLLO_TEMPLATES_VERSION') ? APOLLO_TEMPLATES_VERSION : '1.0.x';
$css = defined('APOLLO_TEMPLATES_URL') ? APOLLO_TEMPLATES_URL . 'assets/css/new-home.css?ver=' . $ver : '';
$js  = defined('APOLLO_TEMPLATES_URL') ? APOLLO_TEMPLATES_URL . 'assets/js/new-home.js?ver=' . $ver : '';

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title' => get_bloginfo('name') . ' — Underground Culture Guide',
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <?php
}
?>
    <?php
    // #region agent log
    $apollo_dbg_logged = $is_logged ? 1 : 0;
    $apollo_dbg_path   = function_exists('apollo_normalize_request_path')
        ? apollo_normalize_request_path()
        : '';
    ?>
    <script>
    (function () {
      var logged = <?php echo (int) $apollo_dbg_logged; ?>;
      var path = <?php echo wp_json_encode($apollo_dbg_path); ?>;
      var hdr = '';
      try {
        /* visible only if server set X-Apollo-Debug-Mural on this response */
        hdr = document.currentScript && document.currentScript.getAttribute('data-mural') || '';
      } catch (e) {}
      fetch('http://127.0.0.1:7754/ingest/da9d552b-a038-4061-bf95-e47d2c529b38', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '161c5c' },
        body: JSON.stringify({
          sessionId: '161c5c',
          runId: 'pre-fix',
          hypothesisId: logged ? 'A' : 'B',
          location: 'page-home.php:head',
          message: logged
            ? 'LOGGED user rendered /casa (mural redirect FAILED)'
            : 'guest rendered /casa (expected)',
          data: { loggedIn: !!logged, path: path, href: location.href },
          timestamp: Date.now()
        })
      }).catch(function () {});
    })();
    </script>
    <?php
    // #endregion
    ?>
    <?php
    /* forms.js is NOT on the CDN (live 302 → /erro/404/). Chrome then tries to
       execute the HTML 404 as a script (MIME block). /casa has no forms. */
    ?>

    <?php if ($css) : ?>
        <link rel="stylesheet" href="<?php echo esc_url($css); ?>">
    <?php endif; ?>

    <?php
    /* F-01 close: load canonical topbar CSS (was only pulled by apollo_plus_open).
       Landing keeps full-bleed hero via scoped overrides below — not a private
       geometry fork of .ax-top. */
    if (function_exists('apollo_plus_part')) {
        apollo_plus_part('apollo-plus/topbar-styles');
    }
    ?>

    <?php
    /* Leaflet CSS is loaded with the map cell (below-fold, IntersectionObserver)
       so it does not compete with core.js on first paint. */
    ?>

    <!-- casa-shell 1.6.4 dead-asset-cut -->
    <style id="apollo-home-styles">
        /* ── OWNERSHIP ────────────────────────────────────────────────────
           Landing-only deltas on top of apollo-plus/topbar-styles.php:
           full-bleed hero (no .ax-body pad), transparent topbar over video,
           scroll-dim colour ramp, drawer-only aside. Geometry of .ax-top
           belongs to topbar-styles.php — do not redeclare it here.

           Tokens come from core.js #cdn-apollo. Page-local extras only. */

        /* ══════════════════════════════════════════════════════════
           Event Card (a-v2-eve-C) — inset surface, rare accent on hover
        ══════════════════════════════════════════════════════════ */
        .a-v2-eve-C {
            background: var(--surface);
            border-radius: var(--radius, 16px);
            padding: 20px;
            transition: transform .35s cubic-bezier(.22, 1, .36, 1), box-shadow .35s cubic-bezier(.22, 1, .36, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: block;
            text-decoration: none;
            color: inherit;
            box-shadow: inset 0 0 0 1px var(--border), inset 0 1px 0 rgba(var(--rgb-t), .04);
        }

        .a-v2-eve-C:hover {
            transform: translateY(-2px);
            box-shadow: inset 0 0 0 1px var(--border-hover, var(--border)), inset 0 0 0 1px rgba(var(--rgb-t), .06), 0 8px 28px rgba(var(--rgb-d), .04);
        }

        .a-v2-eve-C__media {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
            aspect-ratio: 16/9
        }

        .a-v2-eve-C__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .5s var(--ease-default, ease)
        }

        .a-v2-eve-C:hover .a-v2-eve-C__media img {
            transform: scale(1.04)
        }

        .a-v2-eve-C__tags {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap
        }

        .a-v2-eve-C__tag {
            font: 500 10px/1 var(--ff-mono);
            color: #fff;
            background: rgba(var(--rgb-d), .55);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            padding: 5px 10px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: .04em
        }

        .a-v2-eve-C__date {
            position: absolute;
            bottom: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(var(--rgb-d), .65);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border-radius: 10px;
            padding: 8px 12px;
            min-width: 48px
        }

        .a-v2-eve-C__date-day {
            font: 700 20px/1 var(--ff-mono);
            color: #fff
        }

        .a-v2-eve-C__date-month {
            font: 500 10px/1 var(--ff-mono);
            color: rgba(var(--rgb-t), .7);
            text-transform: uppercase;
            margin-top: 2px
        }

        .a-v2-eve-C__body {
            display: flex;
            flex-direction: column;
            gap: 6px
        }

        .a-v2-eve-C__title {
            font: 600 15px/1.3 var(--ff-main);
            color: var(--txt-heading, var(--black-1));
            margin: 0;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap
        }

        .a-v2-eve-C__meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font: 400 12px/1.4 var(--ff-main);
            color: var(--txt-muted)
        }

        .a-v2-eve-C__meta i {
            font-size: 13px;
            color: var(--txt-muted);
            flex-shrink: 0
        }

        /* ── Events Grid ── */
        .a-v2-eve-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px
        }

        @media(max-width:640px) {
            .a-v2-eve-grid {
                grid-template-columns: 1fr;
                gap: 16px
            }
        }

        /* ── Explore CTA card inside grid ── */
        .a-v2-eve-C--explore {
            display: grid;
            place-items: center;
            min-height: 180px;
            border: 2px dashed var(--border);
            background: transparent
        }

        .a-v2-eve-C--explore:hover {
            box-shadow: inset 0 0 0 1px var(--accent), inset 0 1px 0 rgba(var(--rgb-t), .06);
            background: var(--card-hover, var(--surface));
        }

        .a-v2-eve-C--explore .xp-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-align: center
        }

        .a-v2-eve-C--explore .xp-icon {
            font-size: 28px;
            color: var(--txt-muted);
            transition: color .3s
        }

        .a-v2-eve-C--explore:hover .xp-icon {
            color: var(--accent);
        }

        .a-v2-eve-C--explore .xp-all {
            font: 700 24px/1 var(--ff-mono);
            color: var(--txt-heading, var(--black-1));
            letter-spacing: -.02em
        }

        .a-v2-eve-C--explore .xp-label {
            font: 400 12px/1 var(--ff-main);
            color: var(--txt-muted);
            text-transform: uppercase;
            letter-spacing: .06em
        }

        /* ══════════════════════════════════════════════════════════
           Apollo v2 — List Row (a-v2-list-B)
           Re-sell tickets / Classifieds / CPT listings
        ══════════════════════════════════════════════════════════ */
        .a-v2-list-B {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            align-items: center;
            padding: 24px 28px;
            border-bottom: 1px solid var(--border);
            transition: all .2s var(--ease-default, ease);
            gap: 20px;
            text-decoration: none;
            color: inherit;
            cursor: pointer
        }

        .a-v2-list-B:last-child {
            border-bottom: none
        }

        .a-v2-list-B:hover {
            background: var(--surface, #fff);
            padding-left: 36px;
            padding-right: 36px
        }

        .a-v2-list-B__time {
            font: 500 13px/1 var(--ff-mono);
            color: var(--txt-muted);
            letter-spacing: .02em
        }

        .a-v2-list-B__info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0
        }

        .a-v2-list-B__title {
            font: 600 14px/1.3 var(--ff-main);
            color: var(--txt-color-hover); /* F-09 — was four hyphens */
            margin: 0;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap
        }

        .a-v2-list-B__seller {
            font: 400 12px/1 var(--ff-main);
            color: var(--txt-muted)
        }

        .a-v2-list-B__seller--hidden {
            font: 400 12px/1 var(--ff-mono);
            color: var(--border);
            letter-spacing: .15em
        }

        .a-v2-list-B__price-wrap {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            flex-shrink: 0
        }

        .a-v2-list-B__price {
            font: 700 14px/1 var(--ff-mono);
            color: var(--txt-color-hover) /* F-09 — was four hyphens */
        }

        .a-v2-list-B__badge {
            font: 500 9px/1 var(--ff-mono);
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 4px 8px;
            border-radius: 100px;
            background: var(--card);
            color: var(--txt-muted)
        }

        .a-v2-list-B__badge--verified {
            background: rgba(34, 197, 94, .1);
            color: #16a34a
        }

        .a-v2-list-B--cta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 28px;
            border-bottom: none;
            background: var(--black-1);
            color: var(--white-1, #fff);
            transition: background .35s var(--ease-default, ease), padding-left .35s var(--ease-default, ease);
            box-shadow: inset 0 0 0 1px rgba(var(--rgb-t), .06);
        }

        .a-v2-list-B--cta:hover {
            background: var(--black-2, var(--black-1));
            color: var(--white-1, #fff);
            padding-left: 36px;
            box-shadow: inset 0 0 0 1px var(--accent);
        }

        .a-v2-list-B--cta .nh-rall-label {
            font: 400 11px/1 var(--ff-mono);
            color: rgba(var(--rgb-t), 0.45);
            text-transform: uppercase;
            letter-spacing: .14em;
            margin: 0 0 5px
        }

        .a-v2-list-B--cta .nh-rall-title {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1;
            text-transform: uppercase;
            color: #fff;
            margin: 0
        }

        .a-v2-list-B--cta .nh-rall-icon {
            font-size: 2.2rem;
            opacity: 0.65;
            color: #fff;
            transition: transform .4s var(--ease-default, ease), opacity .3s
        }

        .a-v2-list-B--cta:hover .nh-rall-icon {
            transform: rotate(-45deg) scale(1.2);
            opacity: 1
        }

        /* ── List container ── */
        .a-v2-list-wrap {
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            background: var(--card, transparent)
        }

        /* Footer + intro gate — external new-home.css (no inline overrides) */

        /* ══════════════════════════════════════════════════════════
           LANDING TOPBAR DELTAS (geometry owned by topbar-styles.php)
           Full-bleed hero: kill shell padding. Softer blur over video.
           Every override is body[data-apollo-page="landing"]-scoped so
           B2 never sees a bare DS selector redeclared.
        ══════════════════════════════════════════════════════════ */
        body[data-apollo-page="landing"].ax-body { padding-top: 0; }
        body[data-apollo-page="landing"] .ax-top-blur {
            height: 60px;
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            background: linear-gradient(to bottom, rgba(var(--rgb-theme), .35) 0%, rgba(var(--rgb-theme), .10) 50%, transparent 100%);
            -webkit-mask: none; mask: none;
        }

        /* ═══ APOLLO+ ASIDE — DRAWER-ONLY ON /casa (2026-08-25) ═══════════════
           aside-styles.php pins .ax-aside at >=1000px and hides .ax-burger,
           which is correct for every screen whose content well is .ax-main and
           therefore absorbs the 225px offset. /casa's <main id="apollo-home">
           is a deliberately full-bleed landing with no such offset, so a pinned
           aside would cover the hero.

           Scoped to body[data-apollo-page="landing"] so ONLY /casa is affected —
           this does not touch the pinned behaviour anywhere else. Specificity
           (body[attr] + class) beats aside-styles.php's bare .ax-aside inside
           the same media query without needing !important on the transform.

           OWNERSHIP: aside-styles.php remains the owner of .ax-aside/.ax-burger.
           This is a declared, page-scoped override of two of its properties,
           not a second declaration of the component. */
        @media (min-width: 1000px) {
            body[data-apollo-page="landing"] .ax-aside {
                width: min(320px, 86vw);
                transform: translateX(-100%);
            }
            body[data-apollo-page="landing"] .ax-aside.open { transform: translateX(0); }
            /* Burger stays the menu affordance at every width here, because the
               aside never pins to replace it. Brand stays visible too — DS
               hides .ax-top-brand when the aside pins; /casa never pins. */
            body[data-apollo-page="landing"] .ax-burger { display: flex; }
            body[data-apollo-page="landing"] .ax-top-brand { display: flex; }
            /* The overlay is the drawer's dismiss target, so it must stay live —
               aside-styles.php switches it off at this breakpoint on the
               assumption that a pinned aside needs no scrim. */
            body[data-apollo-page="landing"] .ax-overlay { display: block; }
        }

        /* login = bare icon button — NO background, NO border (showcase icon-btn rule) */
        body[data-apollo-page="landing"] .ax-login {
            display: inline-flex; align-items: center; gap: 6px; height: 44px; padding: 0 6px;
            background: none; border: 0;
            font-family: var(--ff-main); font-weight: 600; text-decoration: none; white-space: nowrap;
        }
        @media (max-width: 560px) { body[data-apollo-page="landing"] .ax-login span { display: none; } }

        /* ══════════════════════════════════════════════════════════
           TOPBAR ICON COLOR + SCROLL DIM  (/casa only)
           rest → scrolled past 1.25vh. currentColor drives both <i>
           glyphs and inline <svg fill="currentColor">.
           Selectors stay landing-scoped — never bare DS forks.
        ══════════════════════════════════════════════════════════ */
        body[data-apollo-page="landing"] .ax-top-r > *,
        body[data-apollo-page="landing"] .ax-top-r > * i,
        body[data-apollo-page="landing"] .ax-top-r > * svg,
        body[data-apollo-page="landing"] .ax-top-r > * span,
        body[data-apollo-page="landing"] .ax-top .ax-burger,
        body[data-apollo-page="landing"] .ax-top .ax-burger i,
        body[data-apollo-page="landing"] .ax-top .ax-burger svg,
        body[data-apollo-page="landing"] .ax-top .ax-ic,
        body[data-apollo-page="landing"] .ax-top .ax-ic > i,
        body[data-apollo-page="landing"] .ax-top .ax-ic svg:not(.ax-dot svg),
        body[data-apollo-page="landing"] .ax-top .ax-avb-init,
        body[data-apollo-page="landing"] .ax-top-brand,
        body[data-apollo-page="landing"] .ax-top-brand .apollo,
        body[data-apollo-page="landing"] .ax-top-brand svg,
        body[data-apollo-page="landing"] .ax-top-brand .apollo-logo-wrapper svg {
            color: #dedede !important;
            fill: #dedede !important;
            stroke: #dedede !important;
            transition: color .2s ease, fill .2s ease, stroke .2s ease !important;
        }
        body[data-apollo-page="landing"] .ax-login,
        body[data-apollo-page="landing"] .ax-login i,
        body[data-apollo-page="landing"] .ax-login svg,
        body[data-apollo-page="landing"] .ax-login span {
            color: #666666CC !important;
            fill: #666666CC !important;
            transition: color .2s ease, fill .2s ease !important;
        }

        body[data-apollo-page="landing"].ax-scrolled .ax-top-r > *,
        body[data-apollo-page="landing"].ax-scrolled .ax-top-r > * i,
        body[data-apollo-page="landing"].ax-scrolled .ax-top-r > * svg,
        body[data-apollo-page="landing"].ax-scrolled .ax-top-r > * span,
        body[data-apollo-page="landing"].ax-scrolled .ax-top .ax-burger,
        body[data-apollo-page="landing"].ax-scrolled .ax-top .ax-burger i,
        body[data-apollo-page="landing"].ax-scrolled .ax-top .ax-burger svg,
        body[data-apollo-page="landing"].ax-scrolled .ax-top .ax-ic,
        body[data-apollo-page="landing"].ax-scrolled .ax-top .ax-ic > i,
        body[data-apollo-page="landing"].ax-scrolled .ax-top .ax-ic svg:not(.ax-dot svg),
        body[data-apollo-page="landing"].ax-scrolled .ax-top .ax-avb-init,
        body[data-apollo-page="landing"].ax-scrolled .ax-top-brand,
        body[data-apollo-page="landing"].ax-scrolled .ax-top-brand .apollo,
        body[data-apollo-page="landing"].ax-scrolled .ax-top-brand svg,
        body[data-apollo-page="landing"].ax-scrolled .ax-top-brand .apollo-logo-wrapper svg {
            color: #aaa !important;
            fill: currentColor !important;
            stroke: currentColor !important;
        }
        body[data-apollo-page="landing"].ax-scrolled .ax-login,
        body[data-apollo-page="landing"].ax-scrolled .ax-login i,
        body[data-apollo-page="landing"].ax-scrolled .ax-login svg,
        body[data-apollo-page="landing"].ax-scrolled .ax-login span {
            color: #66666680 !important;
            fill: currentColor !important;
        }

        /* ══ Hero text ALWAYS white over the video (cache-proof — inline beats
              any stale ?v= new-home.css). Never token/dark. ══ */
        #apollo-home .nh-hero-title { color: #fff !important; }
        #apollo-home .nh-hero-sub { color: rgba(255, 255, 255, .58) !important; }

        /* Mobile topbar geometry lives in new-home.css (landing-scoped) —
           one owner; do not redeclare here. */

        /* ══ Diamond CSS bridge (asset sync can lag behind PHP) ═══════════
           Toolbar + discreet month control. Scoped so B1 never sees bare
           duplicates of new-home.css selectors. Drop when assets catch up. */
        body[data-apollo-page="landing"] .nh-section-head--toolbar { align-items: center; gap: 16px; }
        body[data-apollo-page="landing"] .nh-section-tools {
            display: inline-flex; align-items: center; gap: 14px;
            margin-left: auto; flex-shrink: 0; transform: translateY(1px);
        }
        body[data-apollo-page="landing"] .nh-section-more {
            font-family: var(--ff-mono); font-size: 0.68rem; font-weight: 500;
            letter-spacing: .04em; text-transform: uppercase; color: var(--muted);
            text-decoration: none; opacity: .72;
            transition: color .18s ease, opacity .18s ease;
        }
        @media (hover: hover) {
            body[data-apollo-page="landing"] .nh-section-more:hover { color: var(--txt-heading, var(--black-1)); opacity: 1; }
        }
        body[data-apollo-page="landing"] .nh-month-trigger {
            display: inline-flex; align-items: center; gap: 6px; height: 32px;
            background: transparent !important; border: none !important; box-shadow: none !important;
            padding: 0 2px; cursor: pointer; font-family: var(--ff-mono); font-size: 0.72rem;
            font-weight: 500; letter-spacing: 0.04em; text-transform: uppercase;
            color: var(--muted); -webkit-text-stroke: 0; opacity: .85;
            transition: color .18s ease, opacity .18s ease;
        }
        @media (hover: hover) {
            body[data-apollo-page="landing"] .nh-month-trigger:hover { color: var(--txt-heading, var(--black-1)); opacity: 1; }
        }
        body[data-apollo-page="landing"] .nh-month-trigger svg { width: 12px; height: 12px; color: var(--muted); flex-shrink: 0; }
        body[data-apollo-page="landing"] .nh-month-row {
            position: absolute; bottom: calc(100% + 10px); left: 0; z-index: 9999;
            display: flex; flex-direction: column; align-items: flex-start; gap: 8px;
            list-style: none; padding: 12px 18px; margin: 0; min-width: 170px;
            background: rgba(var(--rgb-theme), 0.92) !important; isolation: isolate;
            -webkit-backdrop-filter: blur(12px) saturate(140%) !important;
            backdrop-filter: blur(12px) saturate(140%) !important;
            border: 1px solid rgba(var(--rgb-diff), 0.08) !important;
            border-radius: var(--r-sm, 10px);
            box-shadow: 0 8px 28px rgba(var(--rgb-diff), 0.08);
            opacity: 0; pointer-events: none; transform: translateY(6px);
            transition: opacity .2s var(--ease-out, ease), transform .2s var(--ease-out, ease);
        }
        body[data-apollo-page="landing"] .nh-month-row.is-visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
        body[data-apollo-page="landing"] .nh-month-row li a {
            background: transparent !important; border: none !important; box-shadow: none !important;
            color: var(--muted); font-family: var(--ff-mono); font-size: 0.78rem; font-weight: 500;
            letter-spacing: .02em; text-decoration: none; text-transform: none;
            transition: color .18s ease; cursor: pointer; white-space: nowrap;
        }
        body[data-apollo-page="landing"] .nh-month-row li a.active { color: var(--txt-heading, var(--black-1)); font-weight: 600; }
        body[data-apollo-page="landing"] .nh-track-guest-lbl,
        body[data-apollo-page="landing"] .nh-mq-guest-lbl { opacity: .72; font-weight: 500; }
        body[data-apollo-page="landing"] .nh-disclaimer--quiet { display: flex; gap: 10px; align-items: flex-start; margin-top: 22px; }
        body[data-apollo-page="landing"] .nh-disclaimer--quiet p { font-size: 0.72rem; line-height: 1.55; color: var(--muted); margin: 0; }
        body[data-apollo-page="landing"] .nh-empty-state--quiet { padding: 28px 16px !important; min-height: 0; }
        body[data-apollo-page="landing"] .nh-empty-state--quiet i { display: none; }
        body[data-apollo-page="landing"] .nh-resale-intro { font-size: 0.78rem; line-height: 1.55; max-width: 42rem; margin-bottom: 22px; }
        /* Classificados edge-to-edge (asset sync bridge) */
        body[data-apollo-page="landing"] #resell .nh-mq {
            -webkit-mask-image: none !important;
            mask-image: none !important;
            width: calc(100vw + 16px) !important;
            max-width: none !important;
            margin-left: calc(50% - 50vw - 8px) !important;
            margin-right: calc(50% - 50vw - 8px) !important;
            overflow-x: hidden;
        }
        /* BRUTAL card sizes — tracks 120/128/132, mq 120 mobile / 168 desktop. Events untouched. */
        html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > .nh-track-card,
        html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > .nh-explore-card,
        html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > article.nh-track-card,
        html body[data-apollo-page="landing"] .nh-tracks-rail > .nh-track-card,
        html body[data-apollo-page="landing"] .nh-tracks-rail > article.nh-track-card,
        html body[data-apollo-page="landing"] #tracks .nh-tracks-rail > .nh-track-card,
        html body[data-apollo-page="landing"] #tracks .nh-tracks-rail > .nh-explore-card {
            width: 120px !important;
            max-width: 120px !important;
            min-width: 120px !important;
            flex: 0 0 120px !important;
            flex-basis: 120px !important;
            height: auto !important;
            box-sizing: border-box !important;
        }
        html body[data-apollo-page="landing"] #tracks .nh-track-artwork {
            width: 100% !important;
            aspect-ratio: 1 / 1 !important;
            height: auto !important;
        }
        html body[data-apollo-page="landing"] #tracks .nh-track-info { padding: 6px 8px 8px !important; }
        html body[data-apollo-page="landing"] #tracks .nh-track-info h4 { font-size: 0.78rem !important; line-height: 1.2 !important; margin: 0 0 2px !important; }
        html body[data-apollo-page="landing"] #tracks .nh-track-artist { font-size: 0.66rem !important; margin: 0 0 2px !important; }
        html body[data-apollo-page="landing"] #tracks .nh-track-meta { font-size: 0.58rem !important; gap: 4px !important; }
        html body[data-apollo-page="landing"] #tracks .nh-track-play-btn { width: 32px !important; height: 32px !important; font-size: 14px !important; }
        html body[data-apollo-page="landing"] #tracks .apsc-transport,
        html body[data-apollo-page="landing"] #tracks .apsc.is-fallback .apsc-transport,
        html body[data-apollo-page="landing"] #tracks .nh-track-sc-transport {
            position: absolute !important; width: 1px !important; height: 1px !important;
            overflow: hidden !important; clip: rect(0,0,0,0) !important;
            opacity: 0 !important; pointer-events: none !important;
            display: block !important; margin: 0 !important;
        }
        html body[data-apollo-page="landing"] #tracks .nh-track-card.is-expanded {
            height: auto !important;
            align-self: flex-start !important;
        }
        html body[data-apollo-page="landing"] #resell .nh-mq-card,
        html body[data-apollo-page="landing"] #crash .nh-mq-card,
        html body[data-apollo-page="landing"] .nh-mq-card {
            width: 120px !important;
            max-width: 120px !important;
            min-width: 120px !important;
            height: 160px !important;
            max-height: 160px !important;
            min-height: 160px !important;
            flex: 0 0 120px !important;
            aspect-ratio: unset !important;
            border-radius: 12px !important;
            box-sizing: border-box !important;
        }
        html body[data-apollo-page="landing"] .nh-mq-track { gap: 10px !important; }
        html body[data-apollo-page="landing"] .nh-mq-body { padding: 8px !important; }
        html body[data-apollo-page="landing"] .nh-mq-title { font: 600 11px/1.15 var(--ff-main) !important; }
        html body[data-apollo-page="landing"] .nh-mq-seller,
        html body[data-apollo-page="landing"] .nh-mq-sub { font: 500 9px/1.2 var(--ff-mono) !important; }
        html body[data-apollo-page="landing"] .nh-mq-chat { height: 26px !important; padding: 0 8px !important; font: 600 10px/1 var(--ff-main) !important; }
        html body[data-apollo-page="landing"] .nh-mq-lock-av { width: 32px !important; height: 32px !important; margin: -16px 0 0 -16px !important; font-size: 16px !important; }
        html body[data-apollo-page="landing"] .rt-card,
        html body[data-apollo-page="landing"] .rt-card--rail {
            width: min(340px, 82vw) !important;
            max-width: 340px !important;
            min-width: 0 !important;
            height: auto !important;
            max-height: none !important;
            min-height: 0 !important;
            flex: 0 0 auto !important;
            aspect-ratio: unset !important;
        }
        @media (min-width: 768px) {
            html body[data-apollo-page="landing"] #resell .nh-mq-card,
            html body[data-apollo-page="landing"] #crash .nh-mq-card,
            html body[data-apollo-page="landing"] .nh-mq-card {
                width: 168px !important;
                max-width: 168px !important;
                min-width: 168px !important;
                height: 224px !important;
                max-height: 224px !important;
                min-height: 224px !important;
                flex: 0 0 168px !important;
            }
            html body[data-apollo-page="landing"] .nh-mq-body { padding: 10px !important; }
            html body[data-apollo-page="landing"] .nh-mq-title { font: 600 12px/1.15 var(--ff-main) !important; }
            html body[data-apollo-page="landing"] .nh-mq-seller,
            html body[data-apollo-page="landing"] .nh-mq-sub { font: 500 10px/1.2 var(--ff-mono) !important; }
            html body[data-apollo-page="landing"] .nh-mq-chat { height: 28px !important; padding: 0 10px !important; font: 600 11px/1 var(--ff-main) !important; }
        }
        @media (min-width: 1024px) {
            html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > .nh-track-card,
            html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > .nh-explore-card,
            html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > article.nh-track-card,
            html body[data-apollo-page="landing"] .nh-tracks-rail > .nh-track-card,
            html body[data-apollo-page="landing"] .nh-tracks-rail > article.nh-track-card,
            html body[data-apollo-page="landing"] #tracks .nh-tracks-rail > .nh-track-card,
            html body[data-apollo-page="landing"] #tracks .nh-tracks-rail > .nh-explore-card {
                width: 128px !important;
                max-width: 128px !important;
                min-width: 128px !important;
                flex: 0 0 128px !important;
                flex-basis: 128px !important;
            }
            html body[data-apollo-page="landing"] #tracks .nh-track-info h4 { font-size: 0.82rem !important; }
            html body[data-apollo-page="landing"] #tracks .nh-track-artist { font-size: 0.7rem !important; }
            html body[data-apollo-page="landing"] #tracks .nh-track-meta { font-size: 0.62rem !important; }
        }
        @media (min-width: 1400px) {
            html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > .nh-track-card,
            html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > .nh-explore-card,
            html body[data-apollo-page="landing"] #apollo-home .nh-tracks-rail > article.nh-track-card,
            html body[data-apollo-page="landing"] .nh-tracks-rail > .nh-track-card,
            html body[data-apollo-page="landing"] .nh-tracks-rail > article.nh-track-card,
            html body[data-apollo-page="landing"] #tracks .nh-tracks-rail > .nh-track-card,
            html body[data-apollo-page="landing"] #tracks .nh-tracks-rail > .nh-explore-card {
                width: 132px !important;
                max-width: 132px !important;
                min-width: 132px !important;
                flex: 0 0 132px !important;
                flex-basis: 132px !important;
            }
            html body[data-apollo-page="landing"] #tracks .nh-track-info h4 { font-size: 0.84rem !important; }
        }
        @media (max-width: 767px) {
            body[data-apollo-page="landing"] #resell .nh-mq {
                width: calc(100vw + 20px) !important;
                margin-left: calc(50% - 50vw - 10px) !important;
                margin-right: calc(50% - 50vw - 10px) !important;
            }
            body[data-apollo-page="landing"] .nh-month-dropdown { position: static; }
            body[data-apollo-page="landing"] .nh-month-row {
                left: 0; right: auto;
                max-width: calc(100vw - 36px - env(safe-area-inset-left, 0px) - env(safe-area-inset-right, 0px));
            }
        }
    </style>

    <?php do_action('apollo/home/head'); ?>
</head>

<body class="ax-body" data-apollo-page="landing">

    <!-- ═══════════════════════════════════════════════════════════════
         PERSISTENT UI — showcase ax-top (login-only: no panels to open)
    ═══════════════════════════════════════════════════════════════ -->
    <?php
    /* Canonical showcase topbar — auth-aware, single source of truth.
       (was a private copy of .ax-top here; now shared by every Apollo page) */
    if (function_exists('apollo_render_app_shell')) {
        apollo_render_app_shell();
    }

    /* APOLLO+ ASIDE ON /casa (2026-08-25) — shell unification, drawer-only.
       -----------------------------------------------------------------
       /casa was the ONLY screen still on the legacy hand-rolled path: it calls
       apollo_render_app_shell() directly instead of apollo_plus_open(), so it
       rendered the topbar (including #burger) but never included the Apollo+
       aside. 18-canvas-shell.json's $unification_2026_08_05 claims all three
       shells were collapsed into one — /casa was missed, and the divergence
       survived here.

       The visible symptom: every other screen's burger opens the real nav
       (Feed / Eventos / Marketplace / Comuna / Hub.rio / Redução de Danos /
       Mapa + the auth-gated Gestor group), while /casa's opened a private
       6-link sheet whose "Classificados" entry 404'd to /erro/404/.

       Included here rather than converting /casa to apollo_plus_open(): that
       function owns <head>/<body>/<main>, and /casa legitimately needs its own
       landing topbar variant, theme handling and cell system. A BLOCK may be
       included by anyone; only the SHELL is exclusive. Adding the aside part
       gives /casa the same nav without a document rewrite.

       DRAWER-ONLY: aside-styles.php pins .ax-aside at >=1000px and hides
       .ax-burger, which assumes a .ax-main sibling to absorb the 225px offset.
       /casa's <main id="apollo-home"> is a full-bleed landing with no such
       offset, so a pinned aside would sit ON TOP of the hero. The scoped
       override below (body[data-apollo-page="landing"]) keeps it a slide-in
       drawer at every width and keeps the burger visible. Landing layout is
       untouched; only the menu contents change.

       Guarded exactly like apollo_render_app_shell() above: this is the site's
       homepage on a live-sync deploy, and an unguarded call to a function whose
       include order ever changes is a site-wide fatal, not a missing menu. */
    if (function_exists('apollo_plus_part')) {
        apollo_plus_part('apollo-plus/aside');
    }

    require $parts . 'menu-fab.php';

    /* Motion layer first — a ScrollTrigger bound before the motion layer
       exists measures a layout the motion layer is about to change. */
    apollo_casa_render_cells('motion');
    ?>

    <!-- ═══════════════════════════════════════════════════════════════
         HOME CONTENT — scrollable sections for all visitors
    ═══════════════════════════════════════════════════════════════ -->
    <main id="apollo-home">
        <?php include $parts . 'hero.php'; ?>
        <?php include $parts . 'marquee.php'; ?>
        <?php include $parts . 'tracks.php'; ?>
        <?php include $parts . 'events.php'; ?>
        <?php include $parts . 'classifieds.php'; ?>
        <?php include $parts . 'crash.php'; ?>
        <?php include $parts . 'map.php'; ?>
        <?php include $parts . 'footer.php'; ?>
    </main>

    <!-- Guest gate overlay — self-skips entirely for logged-in users.
         Everything [data-auth-required] on this page opens it (see the
         handler further down) instead of hard-navigating to /acesso. -->
    <?php include $parts . 'auth-lightbox.php'; ?>

    <?php
    /* Overlay layer last — fixed panels stack in DOM order instead of
       fighting over z-index. Currently: the single-event reader. */
    apollo_casa_render_cells('overlay');
    ?>


    <!-- ═══════════════════════════════════════════════════════════════
         PAGE SCRIPTS
    ═══════════════════════════════════════════════════════════════ -->
    <?php
    $adverts_v = defined('APOLLO_ADVERTS_VERSION') ? APOLLO_ADVERTS_VERSION : '1.1.5';
    $share_js  = defined('APOLLO_ADVERTS_URL')
        ? APOLLO_ADVERTS_URL . 'assets/js/share-advert.js?ver=' . $adverts_v
        : '';
    $share_css = defined('APOLLO_ADVERTS_URL')
        ? APOLLO_ADVERTS_URL . 'assets/css/share-advert.css?ver=' . $adverts_v
        : '';
    if ($share_css) :
        ?>
        <link rel="stylesheet" href="<?php echo esc_url($share_css); ?>">
    <?php endif; ?>
    <?php if ($js) : ?>
        <?php if ($share_js) : ?>
            <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($share_js); ?>"></script>
        <?php endif; ?>
        <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($js); ?>"></script>
        <?php
        $reveal_fix = defined('APOLLO_TEMPLATES_URL')
            ? APOLLO_TEMPLATES_URL . 'assets/js/casa-reveal-fix.js?ver=' . $ver
            : '';
        $scroll_lite = defined('APOLLO_TEMPLATES_URL')
            ? APOLLO_TEMPLATES_URL . 'assets/js/casa-scroll-lite.js?ver=' . $ver
            : '';
        if ($reveal_fix) :
            ?>
        <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($reveal_fix); ?>"></script>
        <?php endif; ?>
        <?php if ($scroll_lite) : ?>
        <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($scroll_lite); ?>"></script>
        <?php endif; ?>
        <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        /* Diamond polish bridge - runs AFTER new-home.js so a stale cached
           asset (English months / empty menu wipe) cannot win the last paint.
           sync-probe-reveal-837565 */
        (function () {
            var PT = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho',
                      'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
            var idx = new Date().getMonth();
            var names = [PT[idx], PT[(idx + 1) % 12], PT[(idx + 2) % 12]];
            var text = document.querySelector('.nh-month-text');
            var menu = document.getElementById('nhMonthMenu');
            if (text) text.textContent = names[0];
            if (!menu) return;
            var monthLinks = menu.querySelectorAll('a[data-type="month"]');
            if (monthLinks.length >= 3) {
                for (var i = 0; i < 3; i++) monthLinks[i].textContent = names[i];
                return;
            }
            menu.innerHTML =
                '<li><a href="#" class="active" data-type="month">' + names[0] + '</a></li>' +
                '<li><a href="#" data-type="month">' + names[1] + '</a></li>' +
                '<li><a href="#" data-type="month">' + names[2] + '</a></li>' +
                '<li><a class="nh-portal-link" data-type="link" href="/portal/eventos"><i class="ri-calendar-2-line"></i> Ver todos</a></li>' +
                '<li><a class="nh-portal-link" data-type="link" href="/novo-evento"><i class="ri-calendar-schedule-line"></i> Incluir evento</a></li>';
            menu.removeAttribute('hidden');
        })();
        /* sync-probe-scroll-lite-837565 — lighter scroll: snappier Lenis, kill scrub circus, batch reveals */
        (function () {
            var done = false;
            function go() {
                var g = window.gsap, ST = window.ScrollTrigger, home = document.getElementById('apollo-home');
                if (done || !g || !ST || !home) return;
                var l = window.lenis;
                if (l && l.options) { l.options.lerp = 0.22; l.options.wheelMultiplier = 1.12; l.options.touchMultiplier = 1.15; }
                try { ST.config({ limitCallbacks: true, ignoreMobileResize: true }); } catch (e) {}
                try { if (g.ticker && g.ticker.lagSmoothing) g.ticker.lagSmoothing(500, 33); } catch (e2) {}
                var kill = { 'casa-events': 1, 'casa-events-title': 1, 'casa-crash': 1, 'casa-tracks-title': 1, 'casa-resell': 1, 'casa-resell-title': 1, 'casa-map': 1 };
                var phone = !!(window.matchMedia && window.matchMedia('(max-width:719px)').matches);
                ST.getAll().forEach(function (t) {
                    var id = t.vars && t.vars.id;
                    if (!id) return;
                    if (kill[id] || (phone && id === 'casa-hero') || /^(casa-copy-|casa-track-|casa-resell-card-|casa-fix-)/.test(String(id))) t.kill();
                });
                home.querySelectorAll('#events-title .split-char,#tracks-title .split-char,#resell-title .split-char,#crash-title .split-char,.nh-section-head,#nhMap,.nh-map-wrap').forEach(function (el) {
                    el.classList.add('nh-st-drive', 'is-visible', 'ap-skip');
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                });
                var nodes = [];
                home.querySelectorAll('.a-eve-card.ai,.nh-track-card,#resell .rt-card:not([aria-hidden="true"]),#resell .nh-mq-card:not([aria-hidden="true"]), .reveal-up.ai,.ai.reveal-up').forEach(function (el) {
                    if (el.closest('.nh-hero') || el.closest('.nh-mq-track') || nodes.indexOf(el) !== -1) return;
                    nodes.push(el);
                    el.classList.add('nh-st-drive', 'is-visible', 'ap-skip');
                    g.set(el, { y: 22, opacity: 0, force3D: true });
                });
                if (nodes.length && typeof ST.batch === 'function') {
                    ST.batch(nodes, {
                        start: 'top 92%', end: 'bottom top', interval: 0.12, batchMax: 6,
                        onEnter: function (b) { g.to(b, { y: 0, opacity: 1, duration: 0.45, stagger: 0.04, ease: 'power2.out', overwrite: 'auto', force3D: true }); },
                        onLeave: function (b) { g.to(b, { y: -12, opacity: 0, duration: 0.28, stagger: 0.02, ease: 'power1.in', overwrite: 'auto', force3D: true }); },
                        onEnterBack: function (b) { g.to(b, { y: 0, opacity: 1, duration: 0.4, stagger: 0.03, ease: 'power2.out', overwrite: 'auto', force3D: true }); },
                        onLeaveBack: function (b) { g.to(b, { y: 18, opacity: 0, duration: 0.28, stagger: 0.02, ease: 'power1.in', overwrite: 'auto', force3D: true }); }
                    });
                }
                try { ST.refresh(); } catch (e3) {}
                done = true;
            }
            function boot() {
                go();
                if (window.Apollo && Apollo.whenReady) Apollo.whenReady(function () { setTimeout(go, 100); setTimeout(go, 700); setTimeout(go, 1600); });
                var n = 0, iv = setInterval(function () { go(); if (done || ++n > 22) clearInterval(iv); }, 350);
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
        })();
        </script>
    <?php endif; ?>
    <?php
    /* Always mount listen player on /casa — never wait for can_play.
       Stale cards used <a target=_blank>; the player intercepts those too. */
    $listen_assets = isset($GLOBALS['apollo_casa_listen_assets']) && is_array($GLOBALS['apollo_casa_listen_assets'])
        ? $GLOBALS['apollo_casa_listen_assets']
        : array();
    $player_js = (string) ($listen_assets['player_js'] ?? '');
    if ('' === $player_js && defined('APOLLO_TEMPLATES_URL')) {
        $player_js = APOLLO_TEMPLATES_URL . 'assets/js/apollo-track-player.js?ver=' . $ver;
    }
    $sc_js  = (string) ($listen_assets['sc_js'] ?? '');
    $sc_css = (string) ($listen_assets['sc_css'] ?? '');
    if ('' === $sc_js && defined('APOLLO_SC_URL') && defined('APOLLO_SC_VERSION')) {
        $sc_js  = APOLLO_SC_URL . 'assets/js/apollo-sc.js?ver=' . APOLLO_SC_VERSION;
        $sc_css = APOLLO_SC_URL . 'assets/css/apollo-sc.css?ver=' . APOLLO_SC_VERSION;
    }
    if ('' !== $sc_css) :
        ?>
    <link rel="stylesheet" href="<?php echo esc_url($sc_css); ?>">
    <?php endif; ?>
    <?php if ('' !== $sc_js) : ?>
    <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    window.APOLLO_SC = <?php echo wp_json_encode(
        array(
            'sdk'        => 'https://w.soundcloud.com/player/api.js',
            'provider'   => function_exists('apollo_sc_provider') ? apollo_sc_provider() : 'widget',
            'startPct'   => defined('APOLLO_SC_PREVIEW_START_PCT') ? (int) APOLLO_SC_PREVIEW_START_PCT : 20,
            'endPct'     => defined('APOLLO_SC_PREVIEW_END_PCT') ? (int) APOLLO_SC_PREVIEW_END_PCT : 65,
            'minSeconds' => defined('APOLLO_SC_PREVIEW_MIN_SECONDS') ? (int) APOLLO_SC_PREVIEW_MIN_SECONDS : 20,
            'vol'        => 20,
            'fadeInMs'   => 420,
            'fadeOutMs'  => 280,
            'i18n'       => array(
                'play'  => __('Tocar', 'apollo-soundcloud'),
                'pause' => __('Pausar', 'apollo-soundcloud'),
            ),
        )
    ); ?>;
    </script>
    <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($sc_js); ?>"></script>
    <?php endif; ?>
    <?php if ('' !== $player_js) : ?>
    <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    window.APOLLO_TRACK_PLAYER = <?php echo wp_json_encode(
        array(
                'restStream'  => (string) ($listen_assets['restStream'] ?? (function_exists('rest_url') ? rest_url('apollo/v1/radio/stream') : '/wp-json/apollo/v1/radio/stream')),
                'targetVol'   => 0.2,
                'holdSeconds' => 60,
                'fadeInMs'    => 420,
                'fadeOutMs'   => 280,
        )
    ); ?>;
    </script>
    <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($player_js); ?>"></script>
    <?php endif; ?>
    <!-- apollo-listen forced player=<?php echo '' !== $player_js ? '1' : '0'; ?> sc=<?php echo '' !== $sc_js ? '1' : '0'; ?> -->

    <script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        (function() {
            'use strict';

            /* BRUTAL: Out Now track cards never navigate. Kill target=_blank
               and default <a> navigation before anything else. Preview only. */
            (function () {
                function disarm(root) {
                    (root || document).querySelectorAll('#tracks .nh-track-card a, .nh-track-card[data-casa-track-rail] a').forEach(function (a) {
                        a.removeAttribute('target');
                        a.setAttribute('data-apollo-nav-killed', '1');
                    });
                }
                document.addEventListener('click', function (e) {
                    var card = e.target.closest('#tracks .nh-track-card, .nh-track-card[data-casa-track-rail]');
                    if (!card) { return; }
                    if (e.target.closest('[data-track-plat]') && card.classList.contains('is-expanded')) { return; }
                    var a = e.target.closest('a');
                    if (a && card.contains(a)) {
                        e.preventDefault();
                        a.removeAttribute('target');
                    }
                }, true);
                disarm(document);
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function () { disarm(document); });
                }
            })();

            /* PT months — last paint wins over stale new-home.js English labels. */
            (function () {
                var PT = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                          'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                var idx = new Date().getMonth();
                var names = [PT[idx], PT[(idx + 1) % 12], PT[(idx + 2) % 12]];
                var text = document.querySelector('.nh-month-text');
                var menu = document.getElementById('nhMonthMenu');
                if (text) text.textContent = names[0];
                if (!menu) return;
                var links = menu.querySelectorAll('a[data-type="month"]');
                if (links.length >= 3) {
                    for (var i = 0; i < 3; i++) links[i].textContent = names[i];
                    return;
                }
                menu.innerHTML =
                    '<li><a href="#" class="active" data-type="month">' + names[0] + '</a></li>' +
                    '<li><a href="#" data-type="month">' + names[1] + '</a></li>' +
                    '<li><a href="#" data-type="month">' + names[2] + '</a></li>' +
                    '<li><a class="nh-portal-link" data-type="link" href="/portal/eventos"><i class="ri-calendar-2-line"></i> Ver todos</a></li>' +
                    '<li><a class="nh-portal-link" data-type="link" href="/novo-evento"><i class="ri-calendar-schedule-line"></i> Incluir evento</a></li>';
                menu.removeAttribute('hidden');
            })();

            /* Topbar icon dim - fires once past 1.25vh of scroll (/casa only). sync-probe-reveal-837565 */
            (function () {
                var t = false;
                function sync() {
                    var thr = window.innerHeight * 0.0125; /* 1.25vh */
                    document.body.classList.toggle('ax-scrolled', window.scrollY > thr);
                    t = false;
                }
                window.addEventListener('scroll', function () {
                    if (t) return;
                    t = true;
                    window.requestAnimationFrame(sync);
                }, { passive: true });
                sync();
            })();

            /* BRUTAL card clamp — inline styles beat stale CSS/asset cache */
            (function () {
                function trackW() {
                    var vw = window.innerWidth || 1200;
                    if (vw >= 1400) return 132;
                    if (vw >= 1024) return 128;
                    return 120;
                }
                function mqSize() {
                    var vw = window.innerWidth || 1200;
                    return vw >= 768 ? { w: 168, h: 224 } : { w: 120, h: 160 };
                }
                function brutalCards() {
                    var tw = trackW();
                    var mq = mqSize();
                    document.querySelectorAll('#tracks .nh-track-card, #tracks .nh-explore-card, [data-casa-track-rail]').forEach(function (el) {
                        el.style.setProperty('width', tw + 'px', 'important');
                        el.style.setProperty('max-width', tw + 'px', 'important');
                        el.style.setProperty('min-width', tw + 'px', 'important');
                        el.style.setProperty('flex', '0 0 ' + tw + 'px', 'important');
                        el.style.setProperty('flex-basis', tw + 'px', 'important');
                        if (el.classList.contains('is-expanded')) {
                            el.style.setProperty('height', 'auto', 'important');
                            el.style.setProperty('align-self', 'flex-start', 'important');
                        }
                    });
                    document.querySelectorAll('#resell .nh-mq-card, #crash .nh-mq-card, [data-casa-mq]').forEach(function (el) {
                        if (el.closest('.rt-card') || el.classList.contains('rt-card')) return;
                        el.style.setProperty('width', mq.w + 'px', 'important');
                        el.style.setProperty('max-width', mq.w + 'px', 'important');
                        el.style.setProperty('min-width', mq.w + 'px', 'important');
                        el.style.setProperty('height', mq.h + 'px', 'important');
                        el.style.setProperty('max-height', mq.h + 'px', 'important');
                        el.style.setProperty('min-height', mq.h + 'px', 'important');
                        el.style.setProperty('flex', '0 0 ' + mq.w + 'px', 'important');
                        el.style.setProperty('aspect-ratio', 'unset', 'important');
                    });
                }
                brutalCards();
                window.addEventListener('resize', brutalCards);
                window.addEventListener('load', brutalCards);
                setTimeout(brutalCards, 100);
                setTimeout(brutalCards, 500);
            })();

            /* BURGER → APOLLO+ ASIDE (2026-08-25).
               Was: burger proxied a click to #nhMenuFab, opening /casa's private
               6-link sheet. Now that the real aside is included above, aside.php's
               OWN inline script already binds #burger → .ax-aside.open, exactly as
               on every other Apollo+ screen — so this proxy is deleted rather than
               re-pointed. Leaving it would have opened BOTH menus on one click.

               #nhMenuFab / #nhMenuSheet are intentionally still rendered: the FAB
               is /casa's mobile quick-action affordance and carries entries the
               aside does not (Plano creative studio, Documentos, Gestor). It is no
               longer reachable from the burger, only from the FAB itself. */

            <?php if (! $is_logged) : ?>
                /* ── Auth gate: restricted elements → hub.rio lightbox ──
                   Was a hard redirect to /acesso, which threw the visitor off
                   the page with no explanation. Now it opens the shared gate
                   overlay (auth-lightbox.php) in place; the overlay's own
                   "Entrar" button is what actually goes to /acesso. Falls back
                   to the old redirect if the overlay somehow didn't render. */
        document.addEventListener('click', function(e) {
                    var t = e.target.closest('[data-auth-required]');
                    if (!t) return;
                    if (e.target.closest('[data-advert-share], .ap-advert-share-pop')) return;
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.apolloAuthBox && window.apolloAuthBox.open) {
                        window.apolloAuthBox.open();
                    } else {
                        window.location.href = '<?php echo esc_url(home_url('/acesso')); ?>';
                    }
                }, true);
            <?php endif; ?>
        })();
    </script>

    <?php
    do_action('apollo/home/after_content');
    /* Close through the canvas helper so mobile cells (supervisor/unlock/
       gestures/boot) actually emit. Raw </body></html> skipped that hook,
       so /casa never applied the mobile helpers even though the manifest
       and connectors existed. NO wp_footer() — theme must stay out. */
    if (function_exists('apollo_render_document_close')) {
        apollo_render_document_close();
    } else {
        echo '</body></html>';
    }