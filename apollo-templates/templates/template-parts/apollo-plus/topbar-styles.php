<?php

/**
 * Apollo+ Shell — Topbar / overlay / panel styles.
 *
 * WHY THIS FILE EXISTS (root-cause fix, 2026-07-30)
 * -------------------------------------------------
 * app-shell.php has always PRINTED the canonical topbar markup
 * (.ax-top-blur, header.ax-top, .ax-overlay, #apps-pop, #panel-profile) on
 * every Blank Canvas Apollo+ screen — but the CSS for those selectors lived
 * in exactly two places, neither of which an Apollo+ screen loads:
 *
 *   1. apollo-events/styles/base/template-parts/shared/shell-styles.php
 *      — required only by create-event.php and dashboard-event.php (the two
 *        legacy render_blank_canvas_plus() templates).
 *   2. apollo-templates/templates/page-home.php (inline) + assets/css/
 *      new-home.css — enqueued only by page-home.php, i.e. /casa.
 *
 * apollo_plus_open() loaded aside-styles.php (the .ax-aside drawer) but NOT
 * the topbar CSS. Consequence on /feed, /eventos, /portal, /anuncios,
 * /comunas, /hub, /mapa, /eventos/meus, /anuncios/meus:
 *
 *   · <header class="ax-top"> was NOT position:fixed, so it sat in normal
 *     flow at the top of <body>, taking real layout height and scrolling
 *     away with the page;
 *   · html.ax-body never got its padding-top:56px topbar offset;
 *   · #panel-profile (.panel-r) and #apps-pop, which are only hidden BY
 *     their CSS (transform + opacity:0 + pointer-events:none), rendered as
 *     fully-visible in-flow blocks — so for a logged-in user the whole
 *     profile action list (Meu perfil / Editar Perfil / Mensagens /
 *     Notificações / Dashboard / Suporte / Sair) plus the 8-cell Apollo
 *     Suite grid were dumped into the top of the document, pushing the real
 *     screen content far down.
 *
 * That is the "content is cut in half / can't scroll to the end on mobile"
 * report: the page was not overflow-hidden, it was preceded by hundreds of
 * pixels of chrome that was never meant to occupy flow.
 *
 * Every rule below is copied VERBATIM from shell-styles.php (the approved
 * showcase source, itself a copy of apollo.theme.showcase.html) — nothing is
 * re-invented or re-tuned. Only the topbar/overlay/panel selectors are
 * copied: .ax-aside / .ax-main / .s are deliberately NOT included, because
 * aside-styles.php already owns those and its values are the current,
 * intentional ones (--aside-w clamp, .ax-main full-bleed).
 *
 * @package Apollo\Templates
 * @since   1.5.0
 * @see     template-parts/app-shell.php     markup this styles
 * @see     template-parts/apollo-plus/aside-styles.php  owns .ax-aside/.ax-main
 */

if (! defined('ABSPATH')) {
    exit;
}

/* Idempotent: a screen that renders the shell twice, or a legacy template
   that already emitted shell-styles.php, can never duplicate this CSS. */
if (defined('APOLLO_PLUS_TOPBAR_STYLES')) {
    return;
}
define('APOLLO_PLUS_TOPBAR_STYLES', true);
?>
<style id="apollo-plus-topbar">
/* ═══ APP SHELL — estrutura EXATA de apollo.theme.showcase.html ═══ */
/* ── --safe-top now has a fallback, added 2026-08-25.
   The bare var(--safe-top) was correct but unguarded. --safe-top IS defined — by the
   ~31 KB token block that cdn.apollo.rio.br/v1.0.0/core.js injects (verified against the
   live CDN on 2026-08-25: it declares --safe-top/-bottom/-left/-right among 160+ tokens).
   But core.js is an EXTERNAL, render-blocking fetch. Until it lands, var(--safe-top)
   resolves to nothing and this fixed topbar — burger, avatar, icons — sits flush under
   the Dynamic Island on every cold load and every slow network.
   env(safe-area-inset-top) needs no JavaScript and no network. It costs nothing when
   core.js is fast and saves the header when it is not. navbar.v2.css:336 and
   new-home.css:2114 already use exactly this defensive form; the shell core did not.
   Requires viewport-fit=cover, which document-head.php:141 already emits. ── */

.ax-body { padding-top: 56px; }
.ax-top-blur { position: fixed; inset: 0 0 auto 0; height: 78px; z-index: 9900; pointer-events: none; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); background: linear-gradient(to bottom, rgba(var(--rgb-theme),1) 0%, rgba(var(--rgb-theme),.4) 50%, transparent 100%); -webkit-mask: linear-gradient(to bottom, #000 0%, rgba(0,0,0,.7) 45%, transparent 100%); mask: linear-gradient(to bottom, #000 0%, rgba(0,0,0,.7) 45%, transparent 100%); }
.ax-top { position: fixed; top: 2px; left: 0; right: 13px; z-index: 9901; height: 56px; display: flex; align-items: center; gap: 6px; padding: 0 clamp(8px,2vw,16px); padding-top: var(--safe-top, env(safe-area-inset-top, 0px)); }
.ax-top-l, .ax-top-r { display: flex; align-items: center; }
.ax-top-r { margin-left: auto; gap: 2px; }
.ax-burger { display: flex; width: 44px; height: 44px; border-radius: var(--r-pill); align-items: center; justify-content: center; color: var(--txt-heading); cursor: pointer; }
.ax-burger:hover { background: var(--surface); }
.ax-burger i { font-size: calc(var(--fs-r, 1) * 1.4rem); }
.ax-brand { display: flex; align-items: center; gap: 7px; font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * .75rem); font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: var(--txt-heading); }
.ax-brand i { color: var(--txt-heading); font-size: calc(var(--fs-r, 1) * 1.1rem); }
.ax-brand b { color: var(--muted); }
.ax-ic { width: calc(var(--fs-r, 1) * 1.75rem); aspect-ratio: 1/1; height: auto; border-radius: var(--r-pill); display: flex; align-items: center; justify-content: center; color: var(--txt-color); cursor: pointer; position: relative; font-size: calc(var(--fs-r, 1) * 1.15rem); transition: all .35s var(--ease); }
#ic-apps { margin: 0 8px 0 0px!important; }
#ic-pf { transform: scale(1.2)!important; }
.ax-ic:hover { color: var(--txt-heading); background: var(--surface); }
/* Topbar nav icon glyphs — 22px em box (header only; .ax-ic in panels untouched) */
.ax-top .ax-burger,
.ax-top .ax-top-r .ax-ic,
.ax-top .ax-top-r .ax-login { font-size: calc(var(--fs-r, 1) * 22px); }
.ax-top .ax-burger > svg,
.ax-top .ax-burger > i,
.ax-top .ax-top-r .ax-ic > svg,
.ax-top .ax-top-r .ax-ic > i,
.ax-top .ax-top-r .ax-login > svg,
.ax-top .ax-top-r .ax-login > i { width: 1em; height: 1em; font-size: inherit; flex-shrink: 0; display: block; line-height: 1; }
.ax-dot { position: absolute; top: calc(var(--fs-r, 1) * .73rem); right: calc(var(--fs-r, 1) * .755rem); width: 6px; height: 6px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 0 0 rgba(255,122,0,.8); animation: pulse 5.5s infinite; }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255,122,0,.5); filter: brightness(1.35); } 35% { box-shadow: 0 0 0 13px rgba(255,122,0,0); } 100% { box-shadow: 0 0 0 13px rgba(255,122,0,0); filter: brightness(1); } }
.ax-avb { width: 34px; height: 34px; border-radius: 50%; overflow: hidden; margin-left: 4px; cursor: pointer; background: var(--card); flex-shrink: 0; border: 1px solid rgba(5,5,5,.04); box-shadow: rgba(0,0,0,.12) 0px 0px 0px 1px inset, rgba(0,0,0,.25) 0px 1px 0px 0px inset; display: flex; align-items: center; justify-content: center; }
.ax-avb img { width: 100%; height: 100%; object-fit: cover; filter: grayscale(1); box-shadow: none; }
.ax-avb:hover img { filter: grayscale(0); }
.ax-avb-init { font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 10px); font-weight: 700; color: var(--txt-heading); text-transform: uppercase; letter-spacing: .04em; }
.ax-top-brand { display: flex; align-items: center; gap: 9px; }
.ax-top-logo { width: 20px; height: 20px; border-radius: 50%; background: var(--black-1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ax-top-logo i { font-size: calc(var(--fs-r, 1) * 12px); color: var(--bg); }
.ax-top-wm { font-size: calc(var(--fs-r, 1) * 18.5px); font-weight: 500; color: rgba(var(--rgb-diff),.90); letter-spacing: .01em; font-family: var(--ff-main); }
.ax-top-wm b { color: var(--muted); font-weight: 700; }
@media (min-width: 1000px) { .ax-top-brand { display: none; } }

.ax-shell { position: relative; }
.ax-overlay { position: fixed; inset: 0; z-index: 9899; background: rgba(var(--rgb-diff),.18); backdrop-filter: blur(6px); opacity: 0; visibility: hidden; pointer-events: none; transition: opacity .4s var(--ease), visibility .4s; }
.ax-overlay.on { opacity: 1; visibility: visible; pointer-events: auto; }

/* ═══ TOPBAR PANELS · APPS · PROFILE (exatos do showcase) ═══ */
.panel-r { position: fixed; top: 56px; right: 0; bottom: 0; width: clamp(300px,92vw,396px); z-index: 9800; background: var(--bg); display: flex; flex-direction: column; transform: translateX(100%); transition: transform .48s var(--ease); box-shadow: -4px 0 32px rgba(var(--rgb-diff),.06); }
.panel-r.open { transform: translateX(0); }
.panel-r-hd { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px 0px; flex-shrink: 0; }
.panel-r-title { font-family: var(--ff-heading); font-size: calc(var(--fs-r, 1) * var(--fs-h6)); color: var(--txt-heading); font-weight: 700; }
.panel-r-body { flex: 1; overflow-y: auto; padding: 0px 8px 40px; scrollbar-width: none; -ms-overflow-style: none; }
.panel-r-body::-webkit-scrollbar { display: none; width: 0; height: 0; }
.apps-pop { position: fixed; top: calc(56px + 6px); right: 6px; width: 420px; max-width: calc(100vw - 12px); background: rgba(var(--rgb-theme),.25)!important; backdrop-filter: blur(20px) saturate(180%) !important; -webkit-backdrop-filter: blur(20px) saturate(180%) !important; box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); border: 1px solid rgba(var(--rgb-diff),.04); border-radius: var(--r); z-index: 9801; padding: 10px 8px 12px; transform: translateY(-8px) scale(.97); opacity: 0; pointer-events: none; transition: transform .28s var(--ease), opacity .22s var(--ease); }
.apps-pop.open { transform: translateY(0) scale(1); opacity: 1; pointer-events: auto; }
.apps-pop-hd { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.apps-pop-close { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: 0; border-radius: var(--r-pill); background: transparent; color: var(--muted); cursor: pointer; font-size: calc(var(--fs-r, 1) * 15px); }
.apps-pop-close:hover { background: var(--surface); color: var(--txt-heading); }
.apps-pop-title { font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .14em; color: var(--muted); padding: 2px 6px 9px; }
.apps-grid { display: grid; grid-template-columns: repeat(4,24.9%); gap: 3px; }
.app-cell { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 9px 4px; border-radius: var(--r-sm); cursor: pointer; text-decoration: none; color: var(--txt-color); transition: background .25s var(--ease), color .2s var(--ease); }
.app-cell:hover > .app-icon { background: var(--surface-hover); color: var(--txt-heading); }
.app-icon { aspect-ratio: 1/1; width: 100%; height: auto; border-radius: var(--r-sm); background: var(--surface); display: flex; align-items: center; justify-content: center; font-size: calc(var(--fs-r, 1) * 17px); color: var(--txt-heading); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); border: 1px solid rgba(var(--rgb-diff),.04); }
.app-cell span { font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 9.5px); text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
.profile-panel-top { padding: 0px 18px 16px; text-align: center; }
.profile-panel-av { width: 120px; height: 120px; border-radius: 50%; background: var(--surface); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 16px); font-weight: 700; color: var(--txt-heading); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); border: 1px solid rgba(var(--rgb-diff),.04); }
.profile-panel-name { font-family: var(--ff-heading); font-size: calc(var(--fs-r, 1) * 16px); color: var(--txt-heading); font-weight: 700; }
.profile-panel-role { font-size: calc(var(--fs-r, 1) * 12px); color: var(--muted); margin-top: 2px; }
.profile-panel-actions { display: flex; flex-direction: column; gap: 2px; padding: 10px 10px 28px; }
.profile-action { display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: var(--r-sm); cursor: pointer; color: var(--txt-color); font-size: calc(var(--fs-r, 1) * 12.5px); transition: background .28s var(--ease), color .28s var(--ease); }
.profile-action i, .profile-action svg { font-size: calc(var(--fs-r, 1) * 17px); width: calc(var(--fs-r, 1) * 17px); color: var(--muted); flex-shrink: 0; transition: color .28s var(--ease); }
.profile-action:hover { background: var(--surface-hover); color: var(--txt-heading); }
.profile-action:hover i { color: var(--txt-heading); }
.profile-action.danger:hover { background: rgba(255,107,107,.07); color: var(--alert-red); }
.profile-action.danger:hover i { color: var(--alert-red); }
/* .profile-action is a <button> for the Suporte trigger — strip UA chrome so it
   sits flush with its <a> siblings (showcase renders them identically). */
button.profile-action { width: 100%; border: 0; background: none; font-family: inherit; text-align: left; }
</style>
