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
    <!-- Apollo Forms — minimalist underline design system -->
    <script src="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" defer></script>

    <?php if ($css) : ?>
        <link rel="stylesheet" href="<?php echo esc_url($css); ?>">
    <?php endif; ?>

    <!-- Leaflet CSS — required for map section tiles.
         cdn.jsdelivr.net, not unpkg.com (unpkg isn't CSP-allowlisted). -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="">

    <style id="apollo-home-styles">
        /* ── OWNERSHIP ────────────────────────────────────────────────────
           This cell OWNS the /casa `landing` topbar variant — transparent
           over the hero, the scroll-dim colour ramp, and the login-only
           right cluster — plus the two legacy `.a-v2-*` list/card blocks.

           It does NOT own the topbar's geometry. `.ax-top`, `.ax-top-blur`,
           `.ax-burger`, `.ax-top-l/r/brand` and the `.ax-ic` em box belong to
           template-parts/apollo-plus/topbar-styles.php, which /casa does not
           load — so what follows is a partial private copy that has already
           drifted (DS top:2px / right:13px vs 13px / 0 here, and --fs-r vs
           --fsx for the icon scale). Tracked as F-01 in
           _inventory/CASA-MAP-2026-08-08.md, ADR-001. Do not extend the copy.

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
           TOPBAR — showcase ax-top (tokens from core.js only)
           landing variant: fixed, transparent over hero, login-only
        ══════════════════════════════════════════════════════════ */
        .ax-top-blur {
            position: fixed; top: 0; left: 0; width: 100%; height: 60px; z-index: 9900;
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            background: linear-gradient(to bottom, rgba(var(--rgb-theme), .35) 0%, rgba(var(--rgb-theme), .10) 50%, transparent 100%);
            pointer-events: none;
        }
        .ax-top {
            position: fixed; top: 13px; left: 0; right: 0; z-index: 9901;
            height: var(--s-6, 56px); display: flex; align-items: center; justify-content: space-between;
            gap: 6px; padding: 0 clamp(12px, 3vw, 24px); padding-top: var(--safe-top, 0px);
        }
        .ax-top-l, .ax-top-r { display: flex; align-items: center; }
        .ax-top-l { flex-shrink: 0; min-width: 0; }
        .ax-top-r { margin-left: auto; gap: 2px; flex-shrink: 0; }
        .ax-burger {
            display: flex; width: 44px; height: 44px; border-radius: var(--r-pill, 999px);
            align-items: center; justify-content: center; color: rgba(var(--rgb-diff), .75);
            cursor: pointer; background: none; border: 0; transition: background .4s var(--ease, ease);
        }
        .ax-burger:hover { background: var(--surface); }

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
               aside never pins to replace it. */
            body[data-apollo-page="landing"] .ax-burger { display: flex; }
            /* The overlay is the drawer's dismiss target, so it must stay live —
               aside-styles.php switches it off at this breakpoint on the
               assumption that a pinned aside needs no scrim. */
            body[data-apollo-page="landing"] .ax-overlay { display: block; }
        }

        /* Topbar nav icon glyphs — 22px em box (header only) */
        .ax-top .ax-burger,
        .ax-top .ax-top-r .ax-ic,
        .ax-top .ax-top-r .ax-login { font-size: calc(var(--fsx, 1) * 22px); }
        .ax-top .ax-burger > svg,
        .ax-top .ax-burger > i,
        .ax-top .ax-top-r .ax-ic > svg,
        .ax-top .ax-top-r .ax-ic > i,
        .ax-top .ax-top-r .ax-login > svg,
        .ax-top .ax-top-r .ax-login > i { width: 1em; height: 1em; font-size: inherit; flex-shrink: 0; display: block; line-height: 1; }
        .ax-top-brand { display: flex; align-items: center; gap: 9px; text-decoration: none; margin-left: 4px; }
        .ax-top-brand .apollo { font-size: 26px; line-height: 1; color: var(--txt-heading); display: inline-flex; }
        /* login = bare icon button — NO background, NO border (showcase icon-btn rule) */
        /* color intentionally NOT set here — owned by the topbar color layer below */
        .ax-login {
            display: inline-flex; align-items: center; gap: 6px; height: 44px; padding: 0 6px;
            background: none; border: 0;
            /* icon size inherited from .ax-top .ax-ic 22px em box above */
            font-family: var(--ff-main); font-weight: 600; text-decoration: none; white-space: nowrap;
        }
        @media (max-width: 560px) { .ax-login span { display: none; } }

        /* ══════════════════════════════════════════════════════════
           TOPBAR ICON COLOR + SCROLL DIM  (/casa only)
           rest → scrolled past 1.25vh. currentColor drives both <i>
           glyphs and inline <svg fill="currentColor">.
        ══════════════════════════════════════════════════════════ */
        /* ── REST STATE — every topbar glyph, both markups (ax-top + legacy nh-nav) ── */
        .ax-top-r > *,
        .ax-top-r > * i,
        .ax-top-r > * svg,
        .ax-top-r > * span,
        .nh-nav-actions > *,
        .nh-nav-actions > * i,
        .nh-nav-actions > * svg,
        .nh-nav-btn,
        .nh-nav-btn i,
        .nh-nav-btn svg,
        .ax-top .ax-burger,
        .ax-top .ax-burger i,
        .ax-top .ax-burger svg,
        .ax-top .ax-ic,
        .ax-top .ax-ic > i,
        .ax-top .ax-ic svg:not(.ax-dot svg),
        .ax-top .ax-avb-init,
        .ax-top-brand,
        .ax-top-brand .apollo,
        .ax-top-brand svg,
        .ax-top-brand .apollo-logo-wrapper svg {
            color: #dedede !important;
            fill: #dedede !important;
            stroke: #dedede !important;
            transition: all .65s ease !important;
        }
        /* login icon = showcase .ax-ic treatment */
        .ax-login,
        .ax-login i,
        .ax-login svg,
        .ax-login span {
            color: #666666CC !important;
            fill: #666666CC !important;
            transition: all .65s ease !important;
        }

        /* ── SCROLLED STATE (> 1.25vh) ── */
        body.ax-scrolled .ax-top-r > *,
        body.ax-scrolled .ax-top-r > * i,
        body.ax-scrolled .ax-top-r > * svg,
        body.ax-scrolled .ax-top-r > * span,
        body.ax-scrolled .nh-nav-actions > *,
        body.ax-scrolled .nh-nav-actions > * i,
        body.ax-scrolled .nh-nav-actions > * svg,
        body.ax-scrolled .nh-nav-btn,
        body.ax-scrolled .nh-nav-btn i,
        body.ax-scrolled .nh-nav-btn svg,
        body.ax-scrolled .ax-top .ax-burger,
        body.ax-scrolled .ax-top .ax-burger i,
        body.ax-scrolled .ax-top .ax-burger svg,
        body.ax-scrolled .ax-top .ax-ic,
        body.ax-scrolled .ax-top .ax-ic > i,
        body.ax-scrolled .ax-top .ax-ic svg:not(.ax-dot svg),
        body.ax-scrolled .ax-top .ax-avb-init,
        body.ax-scrolled .ax-top-brand,
        body.ax-scrolled .ax-top-brand .apollo,
        body.ax-scrolled .ax-top-brand svg,
        body.ax-scrolled .ax-top-brand .apollo-logo-wrapper svg {
            color: #aaa !important;
            fill: currentColor !important;
            stroke: currentColor !important;
            transition: all .65s ease !important;
        }
        body.ax-scrolled .ax-login,
        body.ax-scrolled .ax-login i,
        body.ax-scrolled .ax-login svg,
        body.ax-scrolled .ax-login span {
            color: #66666680 !important;
            fill: currentColor !important;
            transition: all .65s ease !important;
        }

        /* ══ Hero text ALWAYS white over the video (cache-proof — inline beats
              any stale ?v= new-home.css). Never token/dark. ══ */
        #apollo-home .nh-hero-title { color: #fff !important; }
        #apollo-home .nh-hero-sub { color: rgba(255, 255, 255, .58) !important; }

        /* Mobile topbar + x-axis containment (inline wins over new-home.css link order) */
        @media (max-width: 767px) {
            .ax-top {
                top: calc(8px + env(safe-area-inset-top, 0px));
                padding-left: max(12px, env(safe-area-inset-left, 0px));
                padding-right: max(16px, env(safe-area-inset-right, 0px));
            }
            .ax-top-blur { height: calc(58px + env(safe-area-inset-top, 0px)); }
        }
        html, body.ax-body {
            width: 100%;
            max-width: 100%;
            overflow-x: clip;
        }
        #apollo-home {
            width: 100%;
            max-width: 100%;
            overflow-x: clip;
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
    <?php if ($js) : ?>
        <script src="<?php echo esc_url($js); ?>"></script>
    <?php endif; ?>

    <script>
        (function() {
            'use strict';

            /* Topbar icon dim — fires once past 1.25vh of scroll (/casa only). */
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
    /* Canvas Mode — NO wp_footer() to prevent theme interference */
    ?>

</body>

</html>