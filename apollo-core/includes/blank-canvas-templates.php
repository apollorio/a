<?php

/**
 * Apollo Core — Blank Canvas page templates (Apollo / Apollo+).
 *
 * Two mandatory page templates for the whole ecosystem:
 *
 *  · Blank Canvas "Apollo"   — bare canvas. Only this head + page content.
 *                              No aside, no topbar, zero layout imposed.
 *                              (e.g. apollo-login, single-event, chat)
 *
 *  · Blank Canvas "Apollo+"  — this head + the fixed shell chrome: a
 *                              <div class="ax-top-blur"> + <header class="ax-top">
 *                              topbar and an <aside class="ax-aside"> nav drawer.
 *                              Consumers render the shell parts themselves
 *                              (ids below are the contract), core.js's
 *                              built-in shell behaviour wires them up with
 *                              zero extra JS. (e.g. /modera)
 *
 * Both variants MUST open with the exact fixed head below — only per-page
 * inputs (title/OG/description/robots/canonical) vary. This is additive,
 * standalone infrastructure: it does NOT alter apollo_render_document_open()
 * / apollo_render_document_head() (still used as-is by 29+ existing
 * templates) — those keep delegating OG/Twitter tags to apollo-seo's
 * `apollo/seo/head` action, which is not safe to rely on here because its
 * virtual-route branch forces `robots: index, follow` (wrong for private,
 * authenticated panels like /modera). This helper prints its own
 * deterministic, self-contained OG/Twitter/robots block instead.
 *
 * Shell contract (ids core.js's script.theme.js already binds to — DO NOT
 * rename, DO NOT re-bind them in a supplemental script):
 *   #burger #ax-aside #ax-overlay #ic-act #ic-apps #ic-pf #apps-pop
 *   #aside-pill [data-close] [data-close-apps] .panel-tab .panel-tab-pane
 *   [data-modal] [data-modal-close] .modal-backdrop
 *
 * @package Apollo\Core
 * @since   6.3.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'apollo_blank_canvas_seo_tags' ) ) {
    /**
     * Print the mandatory fixed <head> contents for Blank Canvas Apollo / Apollo+.
     *
     * @param array<string, mixed> $args {
     *     @type string $title           <title>. Default 'apollo::rio'.
     *     @type string $og_title        Falls back to $title.
     *     @type string $og_description  Falls back to site tagline.
     *     @type string $og_image        Falls back to the standard thumb.
     *     @type string $url             Falls back to the current request URL.
     *     @type string $og_type         Default 'website'.
     *     @type string $robots          Default 'index, follow'. Private panels MUST pass 'noindex, nofollow'.
     *     @type string $twitter_site    Default '@apolloriobr'.
     *     @type string $apple_title     apple-mobile-web-app-title. Default 'apollo::rio'.
     * }
     */
    function apollo_blank_canvas_seo_tags( array $args = array() ): void {
        $site_name = 'apollo::rio';
        $defaults  = array(
            'title'          => $site_name,
            'og_title'       => '',
            'og_description' => get_bloginfo( 'description' ),
            'og_image'       => 'https://assets.apollo.rio.br/img/thumb/thumb.jpg',
            'url'            => ( function_exists( 'apollo_normalize_request_path' ) ? home_url( '/' . apollo_normalize_request_path() ) : home_url( add_query_arg( array() ) ) ),
            'og_type'        => 'website',
            'robots'         => 'index, follow',
            'twitter_site'   => '@apolloriobr',
            'apple_title'    => $site_name,
        );
        $opts = wp_parse_args( $args, $defaults );
        if ( '' === $opts['og_title'] ) {
            $opts['og_title'] = $opts['title'];
        }

        $core_url  = function_exists( 'apollo_cdn_core_js_url' ) ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb';
        $csp_nonce = isset( $GLOBALS['apollo_csp_nonce'] ) ? (string) $GLOBALS['apollo_csp_nonce'] : '';
        $nonce_attr = '' !== $csp_nonce ? ' nonce="' . esc_attr( $csp_nonce ) . '"' : '';
        ?>
<meta charset="UTF-8">
<link rel="preconnect" href="https://assets.apollo.rio.br">
<link rel="preconnect" href="https://cdn.apollo.rio.br">

<!--
Mandatory Apollo CORE.JS: global standard of theme and design to all apollo pages!

Loaded on core.js are:
> ICONS: 'Remixicon' + 'apolloIcons', comply with remixicons ´ri-{structure}-line´;
> ANIMATIONS: entire bundle `GSAP v3.15.0` + `Lenis`
> much more loaded injected by cdn.apollo.rio.br/v1.0.0/js/core.js
-->

<script<?php echo $nonce_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url( $core_url ); ?>" fetchpriority="high"></script>

<style>
:root {
/* ALL TOKENS VIA MAIN :root { must be from core.js injected, ONLY LOCAL WEBPAGE EXTRA :root IF  NEEDED then allowed to do only :root for extra element to the page!!! And same: respct is mandatory to injected stylesheets of:  html {} body {} and *{} !important */
}</style>

<!-- Viewport for mobile-first, lock zoom.. -->
<!-- Pinch-zoom restored 2026-08-26. This tag used to carry maximum-scale=1 and
     user-scalable=no. Safari has ignored both since iOS 10; Android Chrome obeys
     them, so on Android the page could not be magnified at all — a WCAG 2.1 SC 1.4.4
     failure, and the one line most at odds with the "ahead of Apple" bar. The
     reference layout never had it. viewport-fit=cover stays, because every
     env(safe-area-inset-*) rule in the tree is dead without it, and
     interactive-widget=overlays-content stays so the on-screen keyboard overlays
     the page instead of resizing the viewport under a fixed topbar. -->
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, viewport-fit=cover, interactive-widget=overlays-content">

<!-- PWA metas for app-like behavior -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="format-detection" content="telephone=no">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( (string) $opts['apple_title'] ); ?>">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#101010" media="(prefers-color-scheme: dark)">
<meta name="robots" content="<?php echo esc_attr( (string) $opts['robots'] ); ?>">

<!-- Open Graph (OG) tags -->
<meta property="og:title" content="<?php echo esc_attr( (string) $opts['og_title'] ); ?>">
<meta property="og:type" content="<?php echo esc_attr( (string) $opts['og_type'] ); ?>">
<meta property="og:image" content="<?php echo esc_url( (string) $opts['og_image'] ); ?>">
<meta property="og:url" content="<?php echo esc_url( (string) $opts['url'] ); ?>">
<meta property="og:description" content="<?php echo esc_attr( (string) $opts['og_description'] ); ?>">
<meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>">

<!-- Twitter Cards (falls back to OG) -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr( (string) $opts['og_title'] ); ?>">
<meta name="twitter:description" content="<?php echo esc_attr( (string) $opts['og_description'] ); ?>">
<meta name="twitter:image" content="<?php echo esc_url( (string) $opts['og_image'] ); ?>">
<meta name="twitter:site" content="<?php echo esc_attr( (string) $opts['twitter_site'] ); ?>">

<title><?php echo esc_html( (string) $opts['title'] ); ?></title>
<?php
    }
}

if ( ! function_exists( 'apollo_render_blank_canvas_open' ) ) {
    /**
     * Open <!DOCTYPE html><html><head>…</head> for a Blank Canvas Apollo /
     * Apollo+ page. Caller continues with <body> and, for Apollo+, the
     * topbar/aside shell parts.
     *
     * @param array<string, mixed> $args Passed to apollo_blank_canvas_seo_tags(),
     *     plus: lang, theme, html_class, html_attrs, extra_head (trusted HTML,
     *     appended after the fixed block — page-local <link rel=stylesheet> etc.)
     */
    function apollo_render_blank_canvas_open( array $args = array() ): void {
        $lang       = isset( $args['lang'] ) ? (string) $args['lang'] : 'pt-BR';
        $theme      = isset( $args['theme'] ) ? (string) $args['theme'] : 'light';
        $html_class = isset( $args['html_class'] ) ? (string) $args['html_class'] : '';
        $html_attrs = isset( $args['html_attrs'] ) ? (string) $args['html_attrs'] : '';
        $extra_head = isset( $args['extra_head'] ) ? (string) $args['extra_head'] : '';
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $lang ); ?>" data-theme="<?php echo esc_attr( $theme ); ?>"<?php
        if ( '' !== $html_class ) {
            echo ' class="' . esc_attr( $html_class ) . '"';
        }
        if ( '' !== $html_attrs ) {
            echo ' ' . $html_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller-controlled attrs.
        }
        ?>>
<head>
<?php
        apollo_blank_canvas_seo_tags( $args );
        if ( '' !== $extra_head ) {
            echo $extra_head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted HTML from plugin templates.
        }
        ?>
</head>
<?php
    }
}

// apollo_render_document_close() (this same file's sibling in document-head.php)
// already does exactly `</body></html>` + optional trusted $extra — reused
// as-is to close BOTH Blank Canvas Apollo and Apollo+, no need to duplicate it.

/* ═══════════════════════════════════════════════════════════════════════════
   APOLLO+ SHELL CONTRACT
   Apollo+ is NOT a bare canvas: it MUST carry the layout chrome (burger +
   topbar with activities / apps / profile panels, or the login control when
   logged out). Previously each consumer re-implemented that shell, which is
   how four divergent copies appeared. These helpers make the shell a single
   guaranteed call so no page can silently render headless.
   ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'apollo_blank_canvas_variant' ) ) {
    /**
     * Current canvas variant: 'plus' (full shell) or 'apollo' (bare).
     *
     * Set by BlankCanvasTrait::render_blank_canvas() via APOLLO_CANVAS_VARIANT.
     * Defaults to 'apollo' so every legacy caller keeps its exact behaviour.
     */
    function apollo_blank_canvas_variant(): string {
        return defined( 'APOLLO_CANVAS_VARIANT' ) ? (string) APOLLO_CANVAS_VARIANT : 'apollo';
    }
}

if ( ! function_exists( 'apollo_blank_canvas_is_plus' ) ) {
    /** True when the page is a Blank Canvas Apollo+ (shell required). */
    function apollo_blank_canvas_is_plus(): bool {
        return 'plus' === apollo_blank_canvas_variant();
    }
}

if ( ! function_exists( 'apollo_render_blank_canvas_shell' ) ) {
    /**
     * Print the COMPLETE Apollo+ shell chrome: topbar + overlay + panels,
     * AND the aside drawer the topbar's #burger opens.
     *
     * MARKUP IS NOT DUPLICATED HERE. The canonical markup lives in
     * apollo-templates — `apollo_render_app_shell()` for the topbar,
     * `apollo-plus/aside.php` for the drawer; core only guarantees both are
     * called, in that order. If apollo-templates is unavailable, a minimal
     * contract-safe topbar is printed so an Apollo+ page is never left without
     * chrome — same ids core.js's shell behaviour already binds to.
     *
     * @see apollo_render_blank_canvas_topbar()  topbar half
     * @see apollo_render_blank_canvas_aside()   aside half (why it has no fallback)
     */
    function apollo_render_blank_canvas_shell(): void {
        /* ─────────────────────────────────────────────────────────────────
           THE APOLLO+ SHELL IS TWO HALVES, NOT ONE (fixed 2026-08-25).

           This function used to render ONLY the topbar and return. But the
           topbar contains #burger, and #burger does nothing on its own — the
           element it opens (#ax-aside) and the script that binds it BOTH live
           in apollo-templates' apollo-plus/aside.php. Rendering half the shell
           produced a visible, focusable, permanently dead hamburger on every
           Apollo+ page that came through this path:

             · apollo-dashboard  render_blank_canvas_plus()  → /painel
             · any future caller of render_blank_canvas( …, 'plus' )

           18-canvas-shell.json's $unification_2026_08_05 records these shells
           as collapsed into apollo_plus_open(). They were not — this function
           survived, and kept diverging, which is why the burger works on
           /eventos (path B, apollo_plus_open → renders the aside) and is dead
           on path A. Emitting the aside here closes that gap at the ONE place
           core guarantees the Apollo+ contract, instead of patching each
           screen.

           ORDER IS LOAD-BEARING: topbar first (it owns #burger), aside second
           (its inline script does getElementById('burger') at parse time and
           binds defensively if absent — bind it before the button exists and
           the drawer is dead again, silently).
           ───────────────────────────────────────────────────────────────── */

        /* Topbar half. Guarded independently of the aside half: another caller
           having already emitted the topbar must NOT also suppress the aside,
           which is exactly the bug the single combined guard used to cause. */
        if ( ! defined( 'APOLLO_APP_SHELL_LOADED' ) ) {
            apollo_render_blank_canvas_topbar();
        }

        /* Aside half — the drawer #burger actually opens. */
        apollo_render_blank_canvas_aside();
    }
}

if ( ! function_exists( 'apollo_render_blank_canvas_aside' ) ) {
    /**
     * Print the Apollo+ aside (the drawer #burger opens).
     *
     * Markup is NOT duplicated here, for the same reason the topbar is not:
     * apollo-templates owns it (`apollo-plus/aside.php`), and that file also
     * carries the inline script binding #burger → .ax-aside.open plus its own
     * APOLLO_PLUS_ASIDE_LOADED idempotency guard.
     *
     * If apollo-templates is unavailable there is deliberately NO fallback: a
     * hand-rolled stand-in would be a second owner of .ax-aside — the cardinal
     * sin — and an empty drawer is worse than no drawer. The topbar's own
     * fallback exists only because core must guarantee *chrome*; navigation is
     * apollo-templates' contract.
     */
    function apollo_render_blank_canvas_aside(): void {
        if ( defined( 'APOLLO_PLUS_ASIDE_LOADED' ) ) {
            return;
        }
        if ( function_exists( 'apollo_plus_part' ) ) {
            apollo_plus_part( 'apollo-plus/aside' );
        } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            echo '<!-- apollo+: aside unavailable (apollo-templates inactive) — #burger will not open -->';
        }
    }
}

if ( ! function_exists( 'apollo_render_blank_canvas_topbar' ) ) {
    /**
     * Print the Apollo+ topbar only.
     *
     * Split out of apollo_render_blank_canvas_shell() so the two halves of the
     * shell can be guarded — and reasoned about — separately.
     */
    function apollo_render_blank_canvas_topbar(): void {
        if ( defined( 'APOLLO_APP_SHELL_LOADED' ) ) {
            return;
        }

        if ( function_exists( 'apollo_render_app_shell' ) ) {
            apollo_render_app_shell();
            return;
        }

        // Fallback — contract ids only, no invented UI.
        $logged = is_user_logged_in();
        ?>
<div class="ax-top-blur" aria-hidden="true"></div>
<header class="ax-top" role="banner">
    <div class="ax-top-l">
        <button class="ax-burger" id="burger" aria-label="Menu"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M3 5h18v2H3V5Zm0 6h18v2H3v-2Zm0 6h18v2H3v-2Z"/></svg></button>
        <a class="ax-top-brand" href="<?php echo esc_url( home_url( '/casa' ) ); ?>" aria-label="apollo::rio"><i class="apollo"></i></a>
    </div>
    <div class="ax-top-r">
        <?php if ( $logged ) : ?>
            <button class="ax-ic" id="ic-apps" aria-label="Apps"><i class="ri-apps-2-line"></i></button>
            <button class="ax-avb" id="ic-pf" aria-label="Usuá::rio"><span class="ax-avb-init"></span></button>
        <?php else : ?>
            <a class="ax-ic ax-login" href="<?php echo esc_url( home_url( '/acesso' ) ); ?>" aria-label="Entrar"><i class="ri-login-circle-line"></i></a>
        <?php endif; ?>
    </div>
</header>
<div class="ax-overlay" id="ax-overlay" aria-hidden="true"></div>
        <?php
    }
}

if ( ! function_exists( 'apollo_render_blank_canvas_body' ) ) {
    /**
     * Open <body> for a Blank Canvas page and, for Apollo+, print the shell.
     *
     * Apollo  → `<body class="ax-body">` and nothing else (bare canvas).
     * Apollo+ → same body, then the mandatory shell chrome.
     *
     * @param array<string,mixed> $args { @type string $body_class Extra body classes.
     *                                    @type string $variant    'plus'|'apollo' override. }
     */
    function apollo_render_blank_canvas_body( array $args = array() ): void {
        $extra_class = isset( $args['body_class'] ) ? trim( (string) $args['body_class'] ) : '';
        $variant     = isset( $args['variant'] ) ? (string) $args['variant'] : apollo_blank_canvas_variant();
        $class       = trim( 'ax-body ' . $extra_class );
        ?>
<body class="<?php echo esc_attr( $class ); ?>">
        <?php
        if ( 'plus' === $variant ) {
            apollo_render_blank_canvas_shell();
        }
    }
}
