<?php
/**
 * Shared App-Shell — Page styles (exact from form.html)
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<style>
/*
   APOLLO::RIO · UNIVERSAL THEME — uni.theme.v2.css   ·   v2.3.0
   Replicated to every page. Mobile-first. Simple.
   ⚠ TOKENS ARE INJECTED BY core.js — this file NEVER re-declares :root.
*/
*, *::before, *::after { corner-shape: squircle; box-sizing: border-box; margin: 0; padding: 0; }
.btn, .btn-icon, .tag, .pill, .pill .th, .toggle-track, .toggle-track::after, .av, .ax-avb, .ax-ic, .ax-burger,
.radio-mark, .progress-h, .progress-h-fill, .apollo-sb-thumb, .date-box, .stat-icon,
.aon, .ax-dot, .progress-knob, .play-pause-btn, .ctrl-btn { corner-shape: round; }
@media (prefers-reduced-motion: no-preference) { html { scroll-behavior: smooth; } }
body { background: var(--bg); color: var(--txt-color); font-family: var(--ff-main, 'Inter', sans-serif); font-size: calc(13px * var(--fs-r, 1)); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility; overflow-x: clip; touch-action: manipulation; transition: background .5s var(--ease), color .5s var(--ease), font-size .35s var(--ease); }
html, body { width: 100%; min-width: 100%; max-width: 100%; min-height: 100dvh; overflow-x: hidden; overscroll-behavior: none; }
::selection { background: rgba(var(--rgb-accent),.18); color: var(--black-2); }
a { text-decoration: none; color: inherit; transition: color .35s var(--ease), opacity .35s var(--ease); }
button, .btn { border: none; background: none; cursor: pointer; font-family: inherit; }
input, textarea, select { border: none; background: none; font-family: inherit; outline: none; }
ul { list-style: none; }
img { display: block; max-width: 100%; image-rendering: crisp-edges; box-shadow: var(--img-border); }
figure { box-shadow: var(--img-border); }
i[class*="ri-"], i[class^="i-"], i[class*=" i-"], [data-apollo-icon] { font-style: normal; line-height: 1; vertical-align: -.125em; transition: color .3s var(--ease), opacity .3s var(--ease); }
[data-apollo-icon] svg, i[class*="ri-"] svg { stroke-width: .55px; vector-effect: non-scaling-stroke; shape-rendering: geometricPrecision; }
i, button, .btn, img, svg { -webkit-user-select: none; user-select: none; }
:focus-visible { outline: 2px solid var(--black-1); outline-offset: 2px; border-radius: 3px; }
input:focus-visible, textarea:focus-visible, select:focus-visible { outline: none; }
.display-text { font-family: var(--ff-heading, 'Inter', sans-serif); font-size: calc(var(--fs-r, 1) * var(--fs-h2, 24px)); color: var(--txt-heading); font-weight: 800; letter-spacing: -.03em; line-height: .98; }
.label-section, .tref-sec-lbl { display: inline-flex; align-items: center; gap: 10px; font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * var(--fs-caption, 11px)); text-transform: uppercase; letter-spacing: .14em; color: var(--muted); margin-bottom: 8px; }
.txt-mono { font-family: var(--ff-mono, monospace); }
.txt-secondary { color: var(--txt-color); font-size: calc(var(--fs-r, 1) * var(--fs-body-sm, 13px)); line-height: 1.65; text-wrap: pretty; }
.text-sm { font-size: calc(var(--fs-r, 1) * var(--fs-body-sm, 13px)); } .text-xs { font-size: calc(var(--fs-r, 1) * var(--fs-caption, 11px)); } .muted { color: var(--muted); } .accent { color: var(--accent); }
p { text-wrap: pretty; }
a, .btn, .ni, .si, .fr, .fni, .adj, .tag, .stat-card, .event-row, .gallery-card, .gallery-card img,
.apollo-select, .field-input, .dropdown-item, .acc-hd, .swatch, .ax-ic, .ax-burger, .ax-avb, .toggle-track, .toggle-track::after, .ctrl-btn, .play-pause-btn, .btn-more, .pill, .pill .th, .card-hover { transition: background-color .4s var(--ease), background .4s var(--ease), color .35s var(--ease), box-shadow .4s var(--ease), transform .4s var(--ease-snappy), opacity .4s var(--ease), filter .4s var(--ease);}
.tref-sec { padding: 48px 0; border-bottom: .5px solid var(--border); }
.tref-sec-lbl { display: flex; align-items: center; gap: 14px; font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * .5rem)!important; font-weight: 300; text-transform: uppercase; color: var(--primary); margin-bottom: 8px; }
.tref-sec-lbl::after { content: ''; flex: 1; height: .5px; background: var(--border); }

/* DEPTH (inset only) */
.ins, .vidro { border: 1px solid rgba(var(--rgb-diff),.04); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); }
.vidro { box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.85), inset 0 0 0 1px rgba(var(--rgb-theme),.35); }

.frame { background: var(--surface-1); box-shadow: var(--img-frame); border-radius: var(--r-xs, 8px); padding: var(--s-1, 4px); }
.frame > img { border-radius: calc(var(--r-xs, 8px) - var(--s-1, 4px)); width: 100%; height: 100%; object-fit: cover; }
.card { isolation: isolate; padding: var(--s-4, 24px); margin-bottom: 16px; }
.card.sh01, .card.sh02, .card.vidro, .card.ins { backdrop-filter: blur(3px); }

/* LAYOUT HELPERS */
.section { margin-bottom: var(--s-7, 48px); }
.flex-row { display: flex; flex-wrap: wrap; gap: var(--s-3, 16px); align-items: center; }
.flex-col { display: flex; flex-direction: column; gap: var(--s-3, 16px); }
.flex-between { display: flex; align-items: center; justify-content: space-between; gap: var(--s-3, 16px); }
.grid-2 { display: grid; grid-template-columns: 1fr; gap: var(--s-4, 24px); }
.grid-3 { display: grid; grid-template-columns: 1fr; gap: var(--s-4, 24px); }
@media (min-width: 720px) { .grid-2 { grid-template-columns: repeat(2,1fr); } }
@media (min-width: 1000px) { .grid-3 { grid-template-columns: repeat(3,1fr); } }
.align-start { align-items: flex-start; }

/* SPACING UTILITIES — used across the form (were previously undefined → gaps collapsed) */
.mt-1 { margin-top: 4px; } .mt-2 { margin-top: 8px; } .mt-3 { margin-top: 16px; } .mt-4 { margin-top: 24px; }
.mb-1 { margin-bottom: 4px; } .mb-2 { margin-bottom: 8px; } .mb-3 { margin-bottom: 16px; } .mb-4 { margin-bottom: 24px; }
.gap-1 { gap: 4px; } .gap-2 { gap: 8px; } .gap-3 { gap: 16px; }

/* TAGS */
.tags { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; }
.tag { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: var(--r-pill, 99px); font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .08em; font-weight: 700; background: var(--surface); color: var(--muted); border: 1px solid transparent; width: fit-content; line-height: 1.35; }
.tag i { font-size: calc(var(--fs-r, 1) * 12px); cursor: pointer; }
.tag-primary { background: var(--surface); color: var(--txt-heading); border-color: var(--white-5); }
.tag-accent { background: rgba(var(--rgb-accent),.10); color: var(--orange-700); border-color: var(--orange-400); }

/* BUTTONS */
.btn { corner-shape: squircle; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 20px; border-radius: var(--r-pill, 99px); font-family: var(--ff-main, sans-serif); font-size: calc(var(--fs-r, 1) * var(--fs-body-sm, 13px)); font-weight: 500; color: var(--txt-heading); background: var(--surface); cursor: pointer; position: relative; overflow: hidden; white-space: nowrap; }
.btn:active { transform: scale(.97); }
.btn-sm { padding: 7px 14px; font-size: calc(var(--fs-r, 1) * var(--fs-caption, 11px)); }
.btn-primary { background: var(--black-1); color: var(--bg)!important; corner-shape: squircle; }
.btn-primary:hover { background: var(--black-6)!important; transform: translateY(-1px); }
.btn-secondary { background: var(--card); color: var(--txt-heading); border: 1px solid rgba(var(--rgb-diff),.04); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); }
.btn-icon { width: 44px; height: 44px; padding: 0; border-radius: 50%; background: var(--surface); color: var(--txt-heading); }
.btn-icon-sm { width: 32px; height: 32px; padding: 0; border-radius: 50%; background: var(--surface); color: var(--txt-heading); display: inline-flex; align-items: center; justify-content: center; }

/* FORM FIELDS */
.field { margin-bottom: var(--s-4, 24px); width: 100%; }
.field-label { display: block; font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-bottom: 7px; font-weight: 600;}
.apollo-input, .apollo-select { width: 100%; padding: 11px 14px; font-size: calc(var(--fs-r, 1) * var(--fs-body-sm, 13px)); color: var(--txt-heading); background: var(--surface); border-radius: var(--r-sm, 8px); border: 1px solid rgba(var(--rgb-diff),.04); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); transition: box-shadow .35s var(--ease), background .35s var(--ease); }
.apollo-input::placeholder { color: var(--muted); opacity: 0.6; }
.apollo-input:focus, .apollo-select:focus { background: var(--card); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4), inset 0 0 0 1px var(--white-10); }
.apollo-select { appearance: none; -webkit-appearance: none; cursor: pointer; padding-right: 38px; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24'%3E%3Cpath d='M7 10l5 5 5-5' stroke='%23999' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
.apollo-select option { background: var(--bg); color: var(--txt-heading); }
textarea.apollo-input { resize: vertical; min-height: 80px; }

/* Title row — compact bg-color pill at end of event title input */
.ev-title-row { display: flex; align-items: stretch; gap: 10px; }
.ev-title-row .apollo-input { flex: 1; min-width: 0; }
.ev-color-pill {
  -webkit-appearance: none; appearance: none;
  flex-shrink: 0; align-self: center;
  width: 40px; height: 40px; padding: 0; margin: 0;
  border: none; border-radius: var(--r-pill, 99px);
  background: transparent; cursor: pointer;
  box-shadow: inset 0 0 0 1px rgba(var(--rgb-theme), .42), 0 0 0 1px rgba(var(--rgb-diff), .05);
  transition: box-shadow .35s var(--ease), transform .35s var(--ease-snappy);
}
.ev-color-pill:hover { box-shadow: inset 0 0 0 1px rgba(var(--rgb-theme), .55), 0 0 0 1px rgba(var(--rgb-diff), .1); }
.ev-color-pill:active { transform: scale(.94); }
.ev-color-pill:focus-visible { outline: none; box-shadow: inset 0 0 0 1px rgba(var(--rgb-theme), .55), 0 0 0 2px rgba(var(--rgb-accent), .35); }
.ev-color-pill::-webkit-color-swatch-wrapper { padding: 4px; }
.ev-color-pill::-webkit-color-swatch { border: none; border-radius: var(--r-pill, 99px); }
.ev-color-pill::-moz-color-swatch { border: none; border-radius: var(--r-pill, 99px); }

/* CHECKBOXES & TOGGLES */
.custom-checkbox { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: calc(var(--fs-r, 1) * var(--fs-body-sm, 13px)); color: var(--txt-color); user-select: none; }
.custom-checkbox input { position: absolute; opacity: 0; width: 0; height: 0; }
.custom-checkbox .checkmark { width: 20px; height: 20px; border-radius: var(--r-xs, 6px); flex-shrink: 0; background: var(--surface); border: 1px solid rgba(var(--rgb-diff),.04); box-shadow: rgba(0, 0, 0, 0.12) 0px 0px 0px 1px inset, rgba(0, 0, 0, 0.25) 0px 1px 0px 0px inset; display: flex; align-items: center; justify-content: center; transition: background .3s var(--ease), transform .35s var(--ease-spring); }
.custom-checkbox .checkmark::after { content: ''; width: 5px; height: 9px; margin-top: -1px; border: solid var(--bg); border-width: 0 2px 2px 0; transform: rotate(45deg) scale(0); transition: transform .28s var(--ease-spring); }
.custom-checkbox input:checked + .checkmark { background: var(--gray-20); }
.custom-checkbox input:checked + .checkmark::after { transform: rotate(45deg) scale(1); transition-delay: 0.1s; }

.toggle-wrap { display: inline-flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; }
.toggle-input { position: absolute; opacity: 0; width: 0; height: 0; }
.toggle-track { position: relative; width: 44px; height: 24px; flex-shrink: 0; background: var(--white-5); border-radius: var(--r-pill, 99px); transition: background .35s var(--ease);box-shadow: rgba(0, 0, 0, 0.12) 0px 0px 0px 1px inset, rgba(0, 0, 0, 0.25) 0px 1px 0px 0px inset; }
.toggle-track::after { content: ''; position: absolute; left: 3px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; border-radius: 50%; background: var(--bg); box-shadow: 0px 1px 0px 0px rgba(0, 0, 0, 0.12), 0px 0px 0px 1px rgba(0, 0, 0, 0.05); transition: transform .35s var(--ease-snappy); }
.toggle-input:checked + .toggle-track { background: var(--gray-20); }
.toggle-input:checked + .toggle-track::after { transform: translateY(-50%) translateX(20px); }
.toggle-label { font-size: calc(var(--fs-r, 1) * var(--fs-body-sm, 13px)); color: var(--txt-heading); font-weight: 500; }

/* MODALS */
.modal-backdrop { position: fixed; inset: 0; z-index: 9999; background: rgba(var(--rgb-diff),.4); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; padding: 24px; opacity: 0; visibility: hidden; transition: opacity .35s var(--ease), visibility .35s; }
.modal-backdrop.is-open { opacity: 1; visibility: visible; }
.modal { background: var(--bg); border-radius: var(--r, 16px); padding: 32px; width: 100%; max-width: 480px; transform: translateY(20px) scale(.98); transition: transform .35s var(--ease); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); border: 1px solid rgba(var(--rgb-diff),.04); }
.modal.modal--dj { max-width: 560px; max-height: min(90vh, 780px); overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; }
.modal-backdrop.is-open .modal { transform: translateY(0) scale(1); }
.modal-title { font-family: var(--ff-heading, sans-serif); font-size: 1.25rem; color: var(--txt-heading); margin-bottom: 24px; font-weight: 800; }

/* ═══ APP SHELL — estrutura EXATA de apollo.theme.showcase.html ═══
   ────────────────────────────────────────────────────────────────────────────
   ONE SHELL (2026-08-05). Everything from here to the end of the ASIDE block
   below is a SECOND, competing definition of the Apollo+ shell. It predates
   apollo-templates owning the shell and it actively fights the canonical one:

     .ax-main { padding: 50px 30px 180px; max-width: 1040px }   ← here
     .ax-main { padding: 0; width: 100%; max-width: none }      ← aside-styles.php

   Whichever loaded last won, which is precisely the class of bug the cardinal
   rule in CLAUDE.md forbids ("two cells declaring the same selector").

   These rules are KEPT, not deleted, because this file is still the shell for
   any consumer running without apollo-templates. They are simply skipped when
   the canonical shell is present — apollo-plus/topbar-styles.php defines
   APOLLO_PLUS_TOPBAR_STYLES the moment apollo_plus_open() runs.

   The component layer BELOW this block (buttons, cards, .field-input, the .as2
   combobox, forms) is always emitted — create-event.php and dashboard-event.php
   genuinely depend on it and it collides with nothing.
   ──────────────────────────────────────────────────────────────────────────── */
</style>
<?php if ( ! defined( 'APOLLO_PLUS_TOPBAR_STYLES' ) ) : ?>
<style id="apollo-events-legacy-shell">
.ax-body { padding-top: 56px; }
.ax-top-blur { position: fixed; inset: 0 0 auto 0; height: 78px; z-index: 9900; pointer-events: none; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); background: linear-gradient(to bottom, rgba(var(--rgb-theme),1) 0%, rgba(var(--rgb-theme),.4) 50%, transparent 100%); -webkit-mask: linear-gradient(to bottom, #000 0%, rgba(0,0,0,.7) 45%, transparent 100%); mask: linear-gradient(to bottom, #000 0%, rgba(0,0,0,.7) 45%, transparent 100%); }
.ax-top { position: fixed; top: 2px; left: 0; right: 13px; z-index: 9901; height: 56px; display: flex; align-items: center; gap: 6px; padding: 0 clamp(8px,2vw,16px); padding-top: var(--safe-top); }
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
.ax-top .ax-burger,.ax-top .ax-top-r .ax-ic,.ax-top .ax-top-r .ax-login { font-size: calc(var(--fs-r, 1) * 22px); }
.ax-top .ax-burger > svg,.ax-top .ax-burger > i,.ax-top .ax-top-r .ax-ic > svg,.ax-top .ax-top-r .ax-ic > i,.ax-top .ax-top-r .ax-login > svg,.ax-top .ax-top-r .ax-login > i { width: 1em; height: 1em; font-size: inherit; flex-shrink: 0; display: block; line-height: 1; }
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

/* ASIDE — glass drawer (mobile) → pinned (desktop) */
.ax-aside { position: fixed; top: 0; left: 0; width: 100vw; height: 100dvh; z-index: 9902; background: rgba(var(--rgb-theme),.8); backdrop-filter: blur(100px); -webkit-backdrop-filter: blur(100px); display: flex; transform: translateX(-100%); transition: transform .45s var(--ease-snappy); }
.ax-aside.open { transform: translateX(0); }
.ax-main { padding: 50px 30px 180px; max-width: 1040px; }
@media (min-width: 1000px) {
  .ax-burger { display: none; }
  .ax-aside { top: 0; left: 0; bottom: 0; height: 100dvh; width: clamp(184px, 15vw, 232px); transform: none; }
  .ax-overlay { display: none; }
  .ax-main { margin-left: calc(12px + clamp(184px, 15vw, 232px) + clamp(12px, 1.4vw, 20px)); padding-left: 0; margin-right: 30px; }
}
</style>
<?php endif; /* ! APOLLO_PLUS_TOPBAR_STYLES — end of the legacy-shell block */ ?>
<style id="apollo-events-components">
/* The aside INTERNALS (.s and below) stay unconditional: apollo-plus/
   aside-styles.php styles the same sidebar markup, and both files are ports of
   the same showcase source, so these are compatible rather than competing.
   Only the SHELL FRAME above (.ax-top*, .ax-aside, .ax-main, .panel-r,
   .apps-pop) had two owners, and that is what the guard removes. */
.s { display: flex; flex-direction: column; width: 100%; height: 100dvh; min-height: 100%; font-size: calc(var(--fs-r, 1) * 11px); color: rgba(var(--rgb-diff),.66); text-align: left; overflow: hidden; --aside-pad-x: 12px; --aside-row-gap: 9px; }
.s .ni, .s .si, .s .fr, .s .fni, .s .adj, .s .urow { min-width: 0; max-width: 100%; box-sizing: border-box; }
.s .sn, .s .fn, .s .un, .s .uro { display: block; min-width: 0; flex: 1 1 auto; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.si, .fr { width: 100%; max-width: 100%; }
.si .cnt, .fr .fd, .s .ni > i, .s .si > i, .s .fr > i, .s .fni > i, .s .um, .s .pill, .tog { flex-shrink: 0; }
.si .cnt { margin-left: auto; }
@media (max-width: 999px) { .s { border-radius: 0; } }
.s .hd { padding: 20px 14px 15px 20px; display: flex; align-items: center; gap: 9px; flex-shrink: 0; }
.s .lg { width: 18px; height: 18px; border-radius: 9px; background: var(--black-1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.s .lg i { font-size: calc(var(--fs-r, 1) * 12px); color: var(--bg); }
.s .wm { font-size: calc(var(--fs-r, 1) * 18.5px); font-weight: 500; color: rgba(var(--rgb-diff),.90); letter-spacing: .01em; }
.s .wm b { color: var(--muted); font-weight: 700; }
.ax-aside-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; scrollbar-width: none; -ms-overflow-style: none; }
.ax-aside-scroll::-webkit-scrollbar { display: none; width: 0; height: 0; }
.nb { padding: 5px 8px; }
.ni { display: flex; align-items: center; gap: var(--aside-row-gap); padding: 8px var(--aside-pad-x); cursor: pointer; margin-bottom: 2px; color: rgba(var(--rgb-diff),.60); font-size: calc(var(--fs-r, 1) * 13px); border-radius: var(--r-sm); position: relative; }
.ni .sn { font-size: inherit; color: inherit; font-weight: inherit; }
.ni i { font-size: calc(var(--fs-r, 1) * 16px); flex-shrink: 0; opacity: .46; }
.ni:hover { color: rgba(var(--rgb-diff),.90); background: rgba(var(--rgb-diff),.03); }
.ni:hover i { opacity: .9; }
.ni.on { color: rgba(var(--rgb-diff),.90); background: rgba(var(--rgb-diff),.04); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.35), inset 0 0 0 1px rgba(var(--rgb-theme),.085); }
.ni.on i { opacity: 1; }
.ni.on::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 16px; background: var(--accent); border-radius: 0 4px 4px 0; }
.s .dv { margin: 8px 8px; border-top: 1px solid rgba(var(--rgb-diff),.06); }
.sh { display: flex; align-items: center; justify-content: space-between; padding: 8px var(--aside-pad-x) 2px; cursor: pointer; user-select: none; }
.sh:hover { opacity: .72; }
.slbl { font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 9.5px); letter-spacing: .12em; text-transform: uppercase; color: rgba(var(--rgb-diff),.35); }
.tog { font-size: calc(var(--fs-r, 1) * 14px); color: rgba(var(--rgb-diff),.35); transition: transform .35s var(--ease); }
.sh.shut .tog { transform: rotate(-90deg); }
.col { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .38s var(--ease); overflow: hidden; }
.col.shut { grid-template-rows: 0fr; }
.inn { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .38s var(--ease); overflow: hidden; min-height: 0; }
.inn > * { min-height: 0; }
.si { display: flex; align-items: center; gap: var(--aside-row-gap); padding: 7px var(--aside-pad-x); cursor: pointer; font-size: calc(var(--fs-r, 1) * 12.5px); color: rgba(var(--rgb-diff),.60); border-radius: var(--r-xs); margin: 0 4px; }
.si i { font-size: calc(var(--fs-r, 1) * 15px); flex-shrink: 0; opacity: .46; }
.si:hover { color: rgba(var(--rgb-diff),.85); background: rgba(var(--rgb-diff),.03); }
.si.on, .si.active { color: rgba(var(--rgb-diff),.90); background: rgba(var(--rgb-diff),.04); }
.si.on i, .si.active i { opacity: 1; }
.cnt { font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 9px); padding: 2px 6px; border-radius: var(--r-pill); background: var(--white-3); color: rgba(var(--rgb-diff),.42); }
.col.shut .inn > .si { opacity: 0; transform: translateY(-4px); }
.ft { padding: 8px 8px calc(12px + var(--safe-bottom)); flex-shrink: 0; }
.fni .sn { font-size: inherit; color: inherit; }
.fni i, .adjl svg { font-size: calc(var(--fs-r, 1) * 16px); opacity: .46; }
.adj, .fni { padding: 4px var(--aside-pad-x); display: flex; align-items: center; gap: var(--aside-row-gap); cursor: pointer; font-size: calc(var(--fs-r, 1) * 13px); color: rgba(var(--rgb-diff),.60); border-radius: var(--r-sm); }
.fni:hover, .supportBTN:hover, .supportBTN:hover > i, .supportBTN:hover > svg { color: var(--orange-700)!important; background: rgba(var(--rgb-diff),.03); }
.adj:hover { color: rgba(var(--rgb-diff),.85); background: rgba(var(--rgb-diff),.03); }
.adjl { display: flex; align-items: center; gap: var(--aside-row-gap); min-width: 0; flex: 1 1 auto; overflow: hidden; border: none; background: none; padding: 0; margin: 0; font: inherit; color: inherit; cursor: pointer; text-align: left; }
.adjl .sn { font-size: inherit; color: inherit; }
.adj .pill { border: none; cursor: pointer; font: inherit; box-shadow: rgba(0,0,0,.12) 0px 0px 0px 1px inset, rgba(0,0,0,.25) 0px 1px 0px 0px inset; }
.adjl i { font-size: calc(var(--fs-r, 1) * 16px); opacity: .46; flex-shrink: 0; }
.pill { width: 30px; height: 16px; border-radius: var(--r-pill); corner-shape: squircle; display: flex; align-items: center; padding: 2px; flex-shrink: 0; background: var(--white-6); transition: background .35s var(--ease); }
.pill.on { background: var(--primary); }
.s .th { width: 12px; height: 12px; border-radius: 50%; corner-shape: squircle; background: var(--bg); box-shadow: 0px 1px 0px 0px rgba(0,0,0,.12), 0px 0px 0px 1px rgba(0,0,0,.05); transition: transform .35s var(--ease-snappy); }
.pill.on .th { transform: translateX(15px); }
.urow { display: flex; align-items: center; gap: var(--aside-row-gap); padding: 10px var(--aside-pad-x); margin-top: 4px; cursor: pointer; border-radius: var(--r-sm); }
.urow:hover { background: rgba(var(--rgb-diff),.03); }
.avw { position: relative; flex-shrink: 0; }
.av { width: 45px; height: 45px; border-radius: 50%; background: var(--surface-hover); display: flex; align-items: center; justify-content: center; font-size: calc(var(--fs-r, 1) * 11px); font-weight: 600; color: var(--txt-heading); overflow: hidden; position: relative; }
.av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity .3s; box-shadow: none; }
.aon { width: 8px; height: 8px; border-radius: 50%; background: #34C759; position: absolute; bottom: -1px; right: -1px; }
.ui { flex: 1 1 auto; min-width: 0; overflow: hidden; }
.ui > .un { font-size: calc(var(--fs-r, 1) * 12.5px); font-weight: 500; color: rgba(var(--rgb-diff),.85); }
.ui > .uro { font-size: calc(var(--fs-r, 1) * 10px); color: rgba(var(--rgb-diff),.35); }
.um i { font-size: calc(var(--fs-r, 1) * 14px); color: rgba(var(--rgb-diff),.35); }

/* EXTRA CUSTOM CLASSES FOR FORM UI */
.venue-images { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 12px; }
.venue-images .frame { aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; background: var(--surface); border: 1px dashed var(--border, rgba(255,255,255,0.1)); cursor: pointer; position: relative; overflow: hidden; }
.venue-images .frame:hover { background: var(--surface-hover); }
.venue-images .frame i { font-size: 24px; color: var(--muted); }
.venue-images .frame img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: calc(var(--r-xs, 8px) - 2px); }

.lineup-row { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 16px; align-items: center; padding: 12px; background: var(--surface); border-radius: var(--r-sm, 8px); margin-bottom: 8px; border: 1px solid rgba(var(--rgb-diff),.02); }
.lineup-row .dj-name { font-weight: 600; color: var(--txt-heading); display: flex; align-items: center; gap: 12px; }
.lineup-row .dj-name-col { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.lineup-row .dj-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--surface-2); display: flex; align-items: center; justify-content: center; overflow: hidden; }
.lineup-row .dj-avatar img { width: 100%; height: 100%; object-fit: cover; }
.dj-badge-select { height: 20px; width: fit-content; max-width: 100%; padding: 0 18px 0 6px; font-size: calc(var(--fs-r, 1) * 9px); font-family: var(--ff-mono, monospace); text-transform: uppercase; letter-spacing: .04em; background-size: 10px; background-position: right 4px center; border-radius: var(--r-xs, 6px); color: var(--muted); }
.dj-badge-select:not([value=""]) { color: var(--accent, #FFAA33); }

.cover-upload { width: 100%; height: 200px; border: 2px dashed rgba(var(--rgb-diff),.1); border-radius: var(--r, 12px); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; cursor: pointer; transition: background .3s ease; background: var(--surface); }
.cover-upload:hover { background: var(--surface-2); border-color: rgba(var(--rgb-diff),.2); }
.cover-upload i { font-size: 32px; color: var(--muted); }
.cover-upload span { font-size: 13px; color: var(--txt-heading); font-weight: 500; }

.coupon-section { display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(var(--rgb-diff),.05); }
.coupon-section.is-active { display: block; }

/* ═══ CO-AUTHORS (create/edit · not shown on public single) ═══ */
.ax-coauthors-card {
  border: 1px solid rgba(255, 92, 0, .28) !important;
  box-shadow: 0 0 0 1px rgba(255, 92, 0, .08), inset 0 1px 0 0 rgba(var(--rgb-theme),.9);
}
.ax-coauthors-req {
  margin-left: 8px;
  font-size: calc(var(--fs-r, 1) * 10px);
  font-family: var(--ff-mono, monospace);
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--accent, #FF5C00);
  font-weight: 600;
}
.ax-coauthors-hint {
  margin: 10px 0 0;
  font-size: 13px;
  color: var(--muted);
  line-height: 1.45;
}
.ax-coauthors-selected {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
  min-height: 8px;
}
.ax-coauthors-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px 6px 6px;
  border-radius: 999px;
  background: rgba(255, 92, 0, .12);
  border: 1px solid rgba(255, 92, 0, .28);
  color: var(--txt-heading);
  font-size: 12px;
  font-weight: 600;
}
.ax-coauthors-chip img {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  object-fit: cover;
}
.ax-coauthors-chip button {
  background: transparent;
  border: 0;
  color: var(--muted);
  cursor: pointer;
  padding: 0;
  line-height: 1;
  font-size: 16px;
}
.ax-coauthors-chip button:hover { color: var(--accent, #FF5C00); }
.ax-coauthors-list {
  margin-top: 12px;
  max-height: 280px;
  overflow: auto;
  border: 1px solid rgba(var(--rgb-diff),.08);
  border-radius: var(--r-sm, 8px);
  background: var(--surface);
}
.ax-coauthors-opt {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 10px 12px;
  border: 0;
  border-bottom: 1px solid rgba(var(--rgb-diff),.05);
  background: transparent;
  color: var(--txt-heading);
  text-align: left;
  cursor: pointer;
}
.ax-coauthors-opt:last-child { border-bottom: 0; }
.ax-coauthors-opt:hover { background: var(--surface-2); }
.ax-coauthors-opt.is-on {
  background: rgba(255, 92, 0, .1);
}
.ax-coauthors-opt img {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}
.ax-coauthors-opt .ax-ca-meta { min-width: 0; flex: 1; }
.ax-coauthors-opt .ax-ca-name { font-weight: 600; font-size: 13px; }
.ax-coauthors-opt .ax-ca-sub {
  font-size: 11px;
  color: var(--muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ax-coauthors-opt .ax-ca-check {
  color: var(--accent, #FF5C00);
  opacity: 0;
  font-size: 18px;
}
.ax-coauthors-opt.is-on .ax-ca-check { opacity: 1; }
.ax-coauthors-empty {
  padding: 20px;
  text-align: center;
  color: var(--muted);
  font-size: 13px;
}
.ax-coauthors-search:focus {
  outline: 2px solid rgba(255, 92, 0, .45);
  outline-offset: 1px;
}

</style>

<style>
/*
   ═══ MERGED WIDGET LAYER — unite-01 + unite-02 → Apollo theme ═══
   Additive only. Uses exclusively the theme tokens declared by core.js.
   The base theme block above is untouched (single source of truth).
*/

/* ═══ TOPBAR PANELS · APPS · PROFILE (exatos do showcase) ═══ */
.panel-r { position: fixed; top: 56px; right: 0; bottom: 0; width: clamp(300px,92vw,396px); z-index: 9800; background: var(--bg); display: flex; flex-direction: column; transform: translateX(100%); transition: transform .48s var(--ease); box-shadow: -4px 0 32px rgba(var(--rgb-diff),.06); }
.panel-r.open { transform: translateX(0); }
.panel-r-hd { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px 0px; flex-shrink: 0; }
.panel-r-title { font-family: var(--ff-heading); font-size: calc(var(--fs-r, 1) * var(--fs-h6)); color: var(--txt-heading); font-weight: 700; }
.panel-r-body { flex: 1; overflow-y: auto; padding: 0px 8px 40px; scrollbar-width: none; -ms-overflow-style: none; }
.panel-r-body::-webkit-scrollbar { display: none; width: 0; height: 0; }
.panel-tabs-row { display: flex; gap: 4px; padding: 10px 14px 8px; flex-shrink: 0; }
.panel-tab { flex: 1; padding: 7px 10px; border-radius: var(--r-sm); font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 9.5px); text-transform: uppercase; letter-spacing: .10em; color: var(--muted); cursor: pointer; transition: background .3s var(--ease), color .3s var(--ease); }
.panel-tab.active { background: var(--surface); color: var(--txt-heading); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); border: 1px solid rgba(var(--rgb-diff),.04); }
.panel-tab-pane { display: none; flex-direction: column; gap: 5px; padding: 4px 6px; }
.panel-tab-pane.active { display: flex; }
.notif-item { display: flex; gap: 11px; padding: 10px 12px; border-radius: var(--r-sm); cursor: pointer; transition: background .3s var(--ease); }
.notif-item:hover { background: var(--orange-200); }
.notif-item.unread { background: var(--orange-100); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); border: 1px solid rgba(var(--rgb-diff),.04); }
.notif-av { width: 34px; height: 34px; border-radius: 50%; background: var(--surface-hover); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-family: var(--ff-mono); font-weight: 700; font-size: calc(var(--fs-r, 1) * 11px); color: var(--muted); overflow: hidden; }
.notif-av--initials { font-size: calc(var(--fs-r, 1) * 11px); }
.unread > .notif-av { background: var(--orange-200); }
.unread .notif-av > i { color: var(--orange-600); }
.notif-body { flex: 1; min-width: 0; }
.notif-text { font-size: calc(var(--fs-r, 1) * 12.5px); color: var(--txt-color); line-height: 1.44; }
.notif-text strong { color: var(--txt-heading); font-weight: 600; }
.notif-time { font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 9.5px); color: var(--muted); margin-top: 2px; }
.notif-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); flex-shrink: 0; margin-top: 5px; }
.apps-pop { position: fixed; top: calc(56px + 6px); right: 6px; width: 420px; max-width: calc(100vw - 12px); background: rgba(var(--rgb-theme),.25)!important; backdrop-filter: blur(20px) saturate(180%) !important; -webkit-backdrop-filter: blur(20px) saturate(180%) !important; box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); border: 1px solid rgba(var(--rgb-diff),.04); border-radius: var(--r); z-index: 9801; padding: 10px 8px 12px; transform: translateY(-8px) scale(.97); opacity: 0; pointer-events: none; transition: transform .28s var(--ease), opacity .22s var(--ease); }
.apps-pop.open { transform: translateY(0) scale(1); opacity: 1; pointer-events: auto; }
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
.profile-panel-stats { display: flex; margin: 16px 14px 4px; background: var(--surface); border-radius: var(--r); border: 1px solid rgba(var(--rgb-diff),.04); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); overflow: hidden; }
.profile-stat { flex: 1; text-align: center; padding: 12px 6px; }
.profile-stat + .profile-stat { box-shadow: inset 1px 0 rgba(var(--rgb-diff),.04); }
.profile-stat-val { font-family: var(--ff-heading); font-size: calc(var(--fs-r, 1) * var(--fs-h5)); color: var(--txt-heading); font-weight: 700; letter-spacing: -.02em; }
.profile-stat-lbl { font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 8.5px); text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-top: 1px; }
.profile-panel-actions { display: flex; flex-direction: column; gap: 2px; padding: 10px 10px 28px; }
.profile-action { display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: var(--r-sm); cursor: pointer; color: var(--txt-color); font-size: calc(var(--fs-r, 1) * 12.5px); transition: background .28s var(--ease), color .28s var(--ease); }
.profile-action i, .profile-action svg { font-size: calc(var(--fs-r, 1) * 17px); width: calc(var(--fs-r, 1) * 17px); color: var(--muted); flex-shrink: 0; transition: color .28s var(--ease); }
.profile-action:hover { background: var(--surface-hover); color: var(--txt-heading); }
.profile-action:hover i { color: var(--txt-heading); }
.profile-action.danger:hover { background: rgba(255,107,107,.07); color: var(--alert-red); }
.profile-action.danger:hover i { color: var(--alert-red); }

/* ═══ FLOATING LABEL + PALETTE SELECT (.as2) — combobox nativo Apollo ═══ */
.input-group { position: relative; margin-bottom: var(--s-4); z-index: 1; }
.input-group:has(.is-open) { z-index: calc(var(--z-pop, 200) + 1); }
.apollo-label { position: absolute; top: 14px; left: 0; font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 10px); text-transform: uppercase; letter-spacing: .08em; color: var(--muted); pointer-events: none; transition: top .28s var(--ease), font-size .28s var(--ease), color .28s; }
.as2 { position: relative; z-index: 1; }
.as2.is-open { z-index: calc(var(--z-pop, 200) + 1); }
.as2-input { width: 100%; padding: 12px 44px 12px 0; font-family: var(--ff-main); font-size: calc(var(--fs-r, 1) * var(--fs-body-sm)); background: transparent; border: none; border-bottom: 1px solid var(--white-5); color: var(--txt-heading); outline: none; border-radius: 0; box-shadow: none; cursor: text; }
.as2-input::placeholder { color: transparent; }
.as2-line { position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: var(--txt-heading); transition: width .28s var(--ease), left .28s var(--ease); pointer-events: none; }
.as2-input:focus ~ .as2-line, .as2.is-open .as2-line { left: 0; width: 100%; }
.as2-input:focus, .as2.is-open .as2-input { border-bottom-color: transparent; box-shadow: none; background: transparent; }
.as2-input:focus ~ .apollo-label, .as2-input:not(:placeholder-shown) ~ .apollo-label, .as2.has-value .apollo-label { top: -10px; font-size: calc(var(--fs-r, 1) * 9px); color: var(--txt-heading); }
.as2-arrow-btn { position: absolute; right: -4px; bottom: 6px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: var(--r-pill); color: var(--muted); cursor: pointer; background: none; border: none; transition: background .2s var(--ease), color .2s var(--ease); }
.as2-arrow-btn:hover { background: var(--surface); color: var(--txt-heading); }
.as2-arrow { font-size: calc(var(--fs-r, 1) * 18px); pointer-events: none; transition: transform .28s var(--ease), color .28s; }
.as2.is-open .as2-arrow { transform: rotate(180deg); color: var(--txt-heading); }
.as2-drop { position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: var(--z-pop, 200); background: rgba(var(--rgb-theme),.94); backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%); border: 1px solid rgba(var(--rgb-diff),.04); border-radius: var(--r); padding: 8px; max-height: 280px; overflow: hidden; display: flex; flex-direction: column; overscroll-behavior: contain; opacity: 0; visibility: hidden; transform: translateY(-6px) scale(.985); transition: opacity .22s var(--ease), transform .22s var(--ease), visibility 0s .22s; box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); pointer-events: none; }
.as2.is-open .as2-drop { opacity: 1; visibility: visible; transform: translateY(0) scale(1); transition: opacity .22s var(--ease), transform .22s var(--ease); pointer-events: auto; }
.as2-search-wrap { position: relative; flex-shrink: 0; padding: 0 2px 8px; }
.as2-search-icon { position: absolute; left: 12px; top: 0; bottom: 8px; display: inline-flex !important; align-items: center; justify-content: center; z-index: 1; color: var(--muted); font-size: calc(var(--fs-r, 1) * 11px); opacity: .52; pointer-events: none; }
.as2-search { width: 100%; padding: 7px 12px 7px 34px; border: 1px solid rgba(var(--rgb-diff),.04); border-radius: var(--r-xs); background: var(--surface); font-size: calc(var(--fs-r, 1) * var(--fs-p3, 12px)); color: var(--txt-heading); outline: none; box-shadow: none; transition: box-shadow .25s var(--ease), background .25s var(--ease), border-color .25s var(--ease); }
.as2-search::placeholder { color: var(--muted); opacity: .62; }
.as2-search:focus { background: var(--card); border-color: rgba(var(--rgb-primary),.22); box-shadow: 0 0 0 3px rgba(var(--rgb-primary),.07); }
.as2-opts { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; display: flex; flex-direction: column; gap: 2px; scrollbar-width: none; -ms-overflow-style: none; }
.as2-opts::-webkit-scrollbar { display: none; width: 0; height: 0; }
.as2-opt { display: flex; align-items: center; gap: 11px; padding: 10px 10px; border-radius: var(--r-xs); cursor: pointer; transition: background .15s var(--ease), transform .15s var(--ease); position: relative; }
.as2-opt:hover, .as2-opt.is-focused { background: rgba(var(--rgb-primary),.07); }
.as2-opt.is-selected { background: rgba(var(--rgb-primary),.11); }
.as2-opt.is-selected::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 2px; height: 60%; background: var(--txt-heading); border-radius: 0 2px 2px 0; }
.as2-opt-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.as2-opt-name { font-size: calc(var(--fs-r, 1) * var(--fs-body-sm)); font-weight: 500; color: var(--txt-heading); line-height: 1.3; }
.as2-opt-desc { font-size: calc(var(--fs-r, 1) * 10px); font-family: var(--ff-mono); color: var(--muted); line-height: 1.35; }
.as2-opt-check { margin-left: auto; color: var(--txt-heading); opacity: 0; flex-shrink: 0; font-size: calc(var(--fs-r, 1) * 16px); transition: opacity .18s, transform .2s var(--ease-spring); }
.as2-opt.is-selected .as2-opt-check { opacity: 1; transform: scale(1); }
.as2-empty { display: none; padding: 18px 14px; text-align: center; font-family: var(--ff-mono); font-size: calc(var(--fs-r, 1) * 10px); text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
.as2-empty.is-visible { display: block; }
.as2-opt[hidden] { display: none !important; }
.dj-opt-av { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; flex-shrink: 0; box-shadow: none; filter: grayscale(1); transition: filter .2s var(--ease); }
.as2-opt:hover .dj-opt-av, .as2-opt.is-focused .dj-opt-av, .as2-opt.is-selected .dj-opt-av { filter: grayscale(0); }

/* ═══ MODAL DE DJ — abas + prévia ═══ */
.dj-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; background: var(--surface); border-radius: var(--r-sm, 8px); padding: 4px; margin-bottom: 6px; box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); }
.dj-tab { padding: 9px 10px; border-radius: var(--r-xs, 6px); font-size: calc(var(--fs-r, 1) * 12px); font-weight: 600; color: var(--muted); cursor: pointer; transition: background .3s var(--ease), color .3s var(--ease); }
.dj-tab.is-on { background: var(--black-1); color: var(--bg); }
.dj-picked { display: flex; align-items: center; gap: 12px; margin-top: 16px; padding: 12px; background: var(--surface); border-radius: var(--r-sm, 8px); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); }
.dj-picked img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; box-shadow: none; }
.dj-picked .n { font-weight: 700; color: var(--txt-heading); font-size: calc(var(--fs-r, 1) * 13px); }
.dj-picked .h { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); color: var(--muted); }
.btn[disabled] { opacity: .4; pointer-events: none; }

/* ── Date & Time picker (dtp) ── */
.dtp { width: 100%; }
.dtp-tz { float: right; letter-spacing: .05em; opacity: .7; }
.dtp-summary { display: grid; grid-template-columns: 1fr auto 1fr; gap: 10px; align-items: stretch; }
.dtp-chip { display: flex; flex-direction: column; align-items: flex-start; gap: 3px; text-align: left; min-height: 64px; padding: 11px 14px; border-radius: var(--r-sm, 8px); background: var(--surface); border: 1px solid rgba(var(--rgb-diff),.04); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); cursor: pointer; transition: background .35s var(--ease), box-shadow .35s var(--ease); }
.dtp-chip:hover { background: var(--card); }
.dtp-chip.is-active { background: var(--card); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-accent),.55); }
.dtp-chip-lbl { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .1em; color: var(--muted); }
.dtp-chip-date { font-weight: 700; color: var(--txt-heading); font-size: calc(var(--fs-r, 1) * 14px); letter-spacing: -.02em; }
.dtp-chip-time { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 12px); color: var(--accent, #FFAA33); }
.dtp-arrow { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; color: var(--muted); min-width: 56px; }
.dtp-arrow i { font-size: 16px; }
.dtp-dur { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .06em; color: var(--muted); white-space: nowrap; }
.dtp-dur.is-bad { color: #ff7b7b; }

/* painel: fechado por padrão; .is-open desliza para baixo (mobile-friendly) */
.dtp-panel { border-radius: var(--r-sm, 8px); background: var(--surface-1); border: 1px solid rgba(var(--rgb-diff),.04); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(var(--rgb-theme),.4); overflow: hidden; display: grid; grid-template-columns: 1fr; max-height: 0; opacity: 0; visibility: hidden; margin-top: 0; transform: translateY(-6px); transition: max-height .5s var(--ease), opacity .35s var(--ease), transform .4s var(--ease), margin-top .35s var(--ease), visibility .5s; }
.dtp-panel.is-open { max-height: 960px; opacity: 1; visibility: visible; margin-top: 12px; transform: none; }
@media (min-width: 720px) { .dtp-panel { grid-template-columns: 1.15fr 1fr; } }

.dtp-cal { padding: 16px; }
.dtp-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.dtp-cal-title { font-weight: 700; color: var(--txt-heading); font-size: calc(var(--fs-r, 1) * 13px); }
.dtp-nav { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--txt-color); background: var(--surface); cursor: pointer; transition: background .3s var(--ease), color .3s var(--ease); }
.dtp-nav:hover { color: var(--txt-heading); background: var(--card); }
.dtp-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.dtp-dow { text-align: center; font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .05em; color: var(--muted); padding: 6px 0; }
.dtp-day { aspect-ratio: 1; min-height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: calc(var(--fs-r, 1) * 12px); color: var(--txt-color); cursor: pointer; position: relative; transition: background .25s var(--ease), color .25s var(--ease), transform .3s var(--ease-snappy); }
.dtp-day:hover { background: var(--surface-2); color: var(--txt-heading); }
.dtp-day:active { transform: scale(.9); }
.dtp-day.is-out { visibility: hidden; pointer-events: none; }
.dtp-day.is-disabled { opacity: .25; pointer-events: none; }
.dtp-day.is-today::after { content: ''; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; border-radius: 50%; background: var(--accent, #FFAA33); }
.dtp-day.is-selected { background: var(--black-1); color: var(--bg); font-weight: 700; }
.dtp-day.is-other { box-shadow: inset 0 0 0 1px rgba(var(--rgb-accent),.5); color: var(--txt-heading); }

.dtp-time { padding: 16px; border-top: 1px solid rgba(var(--rgb-diff),.05); display: flex; flex-direction: column; gap: 14px; }
@media (min-width: 720px) { .dtp-time { border-top: none; border-left: 1px solid rgba(var(--rgb-diff),.05); } }
.dtp-quick { display: flex; flex-wrap: wrap; gap: 6px; }
.dtp-q { padding: 8px 13px; border-radius: var(--r-pill, 99px); font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); background: var(--surface); color: var(--txt-color); cursor: pointer; border: 1px solid transparent; transition: background .3s var(--ease), color .3s var(--ease), border-color .3s var(--ease); }
.dtp-q:hover { color: var(--txt-heading); background: var(--card); }
.dtp-q.is-on { background: rgba(var(--rgb-accent),.12); color: var(--accent, #FFAA33); border-color: rgba(var(--rgb-accent),.35); }
.dtp-stepper { display: grid; grid-template-columns: 44px 1fr 44px; gap: 8px; align-items: center; }
.dtp-step { height: 44px; border-radius: var(--r-sm, 8px); background: var(--surface); color: var(--txt-heading); font-size: 18px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .3s var(--ease), transform .3s var(--ease-snappy); }
.dtp-step:hover { background: var(--card); }
.dtp-step:active { transform: scale(.94); }
.dtp-time-input { text-align: center; font-family: var(--ff-mono, monospace); font-weight: 700; letter-spacing: .05em; }
.dtp-hint { font-size: calc(var(--fs-r, 1) * 11px); color: var(--muted); line-height: 1.55; }
.dtp-done { align-self: flex-end; margin-top: auto; }

/* ── Line Up: ordem primeiro (a ordem define a exibição na página do evento) ── */
.lineup-row { grid-template-columns: auto 2fr 1fr 1fr auto; }
.dj-order { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); font-weight: 700; color: var(--accent, #FFAA33); background: rgba(var(--rgb-accent),.1); border: 1px solid rgba(var(--rgb-accent),.25); border-radius: var(--r-pill, 99px); min-width: 30px; height: 22px; display: inline-flex; align-items: center; justify-content: center; }
.dj-actions { display: flex; gap: 4px; align-items: center; }
.dj-actions .btn-icon-sm { width: 28px; height: 28px; }
.lineup-hint { font-size: calc(var(--fs-r, 1) * 11px); color: var(--muted); margin: -2px 0 10px; }
@media (max-width: 719px) {
    .lineup-row { grid-template-columns: auto 1fr 1fr auto; gap: 10px; }
    .lineup-row .dj-order { grid-row: 1 / span 2; align-self: center; }
    .lineup-row .dj-name { grid-column: 2 / span 2; }
    .lineup-row .dj-actions { grid-column: 4; grid-row: 1 / span 2; flex-direction: column; }
    .lineup-row .dj-in { grid-column: 2; }
    .lineup-row .dj-out { grid-column: 3; }
}

/* ── Fechamento: recibo (65%) + clima (35%) + Gravar Dados ── */
.grid-rcpt { display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 16px; align-items: start; }
@media (min-width: 720px) { .grid-rcpt { grid-template-columns: 65fr 35fr; } }
.grid-rcpt .card { margin-bottom: 0; }
.rc-row { display: flex; gap: 12px; padding: 9px 0; border-bottom: 1px dashed rgba(var(--rgb-diff),.08); font-size: calc(var(--fs-r, 1) * 12px); }
.rc-row:last-child { border-bottom: none; }
.rc-k { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .08em; color: var(--muted); min-width: 92px; padding-top: 2px; flex-shrink: 0; }
.rc-v { color: var(--txt-heading); font-weight: 500; line-height: 1.55; overflow-wrap: anywhere; flex: 1; }
.rc-v .rc-sub { color: var(--muted); font-weight: 400; font-size: calc(var(--fs-r, 1) * 11px); display: block; }
.rc-lineup { display: flex; flex-direction: column; gap: 4px; }
.rc-lineup .rc-dj { display: flex; align-items: baseline; gap: 8px; }
.rc-lineup .rc-n { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); color: var(--accent, #FFAA33); min-width: 20px; }
.rc-lineup .rc-t { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); color: var(--muted); }

/* ── Cartão de clima (Open-Meteo) — compacto p/ coluna 35% ── */
.wx-main { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.wx-ico { width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0; background: var(--surface); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--accent, #FFAA33); }
.wx-temp { font-size: calc(var(--fs-r, 1) * 30px); font-weight: 800; color: var(--txt-heading); letter-spacing: -.03em; line-height: 1; }
.wx-desc { font-size: calc(var(--fs-r, 1) * 11.5px); color: var(--txt-color); margin-top: 4px; line-height: 1.45; }
.wx-meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; width: 100%; color: var(--muted); font-size: calc(var(--fs-r, 1) * 10px); margin-top: 12px; }
.wx-meta > div { background: var(--surface); border-radius: var(--r-sm, 8px); padding: 8px 10px; }
.wx-meta b { color: var(--txt-heading); font-weight: 700; display: block; font-size: calc(var(--fs-r, 1) * 13px); }
.wx-strip { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(54px, 1fr); gap: 6px; overflow-x: auto; margin-top: 12px; padding-bottom: 4px; -webkit-overflow-scrolling: touch; }
.wx-cell { background: var(--surface); border-radius: var(--r-sm, 8px); padding: 9px 4px; text-align: center; }
.wx-cell .h { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); color: var(--muted); }
.wx-cell i { font-size: 15px; color: var(--txt-color); display: block; margin: 5px auto 3px; }
.wx-cell .t { font-size: calc(var(--fs-r, 1) * 12px); font-weight: 700; color: var(--txt-heading); }
.wx-cell .r { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); color: var(--muted); margin-top: 2px; }
.wx-cell .r.hi { color: var(--accent, #FFAA33); font-weight: 700; }
.wx-note { font-size: calc(var(--fs-r, 1) * 12px); color: var(--muted); display: flex; align-items: center; gap: 8px; line-height: 1.5; }
.wx-note i { font-size: 16px; flex-shrink: 0; }

/* ── Validação de campos ── */
.apollo-input.is-invalid { box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme),.9), inset 0 0 0 1px rgba(255, 110, 110, .55); }
.field-err { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); color: #ff7b7b; margin-top: 5px; }

/* ── Prévia de capa no modo edição ── */
.cover-upload.has-cover { position: relative; border-style: solid; overflow: hidden; }
.cover-upload.has-cover img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.cover-upload.has-cover i, .cover-upload.has-cover span { position: relative; z-index: 1; text-shadow: 0 1px 8px rgba(0,0,0,.65); color: #fff; }

/* ── Toast ── */
.apx-toast { position: fixed; bottom: 24px; left: 50%; transform: translate(-50%, 16px); background: var(--black-1); color: var(--bg); padding: 10px 18px; border-radius: var(--r-pill, 99px); font-size: calc(var(--fs-r, 1) * 12px); font-weight: 600; opacity: 0; pointer-events: none; transition: opacity .35s var(--ease), transform .35s var(--ease); z-index: 10001; white-space: nowrap; max-width: 92vw; text-overflow: ellipsis; overflow: hidden; }
.apx-toast.show { opacity: 1; transform: translate(-50%, 0); }
</style>