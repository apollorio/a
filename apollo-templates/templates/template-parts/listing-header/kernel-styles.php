<?php

/**
 * Apollo Listing Header — kernel stylesheet (skin-independent primitives).
 *
 * Ported from the approved lab file's `#fx-shared` block
 * (screen/portal/header listing/header-of-listing-events.html), which Headers
 * 01/02/03 all shared. Only two things changed, both deliberate:
 *
 *   1. NAMES. `.fx-*` / `.ApolloFilterOverlay` / `.ApolloSearchOverlay` became
 *      `.alh-fx__*` / `.alh-ov` so the block owns a single, greppable prefix
 *      and can never collide with a screen's own `.fx-` helpers.
 *   2. STACKING. The lab clipped both overlays inside a 402px device frame at
 *      z-index 950/960. On a real page they are full-viewport and must clear
 *      the Apollo+ chrome — .ax-top is 9901 and .ax-aside is 9902 — while
 *      staying UNDER the single-event lightbox (.ev-lb, 10500), which is a
 *      whole screen and legitimately outranks a filter panel.
 *
 * NEVER redeclare :root — core.js owns the tokens. Everything below reads
 * them (--surface, --txt-heading, --r-pill, --ease, --rgb-diff, --safe-top…).
 *
 * @package Apollo\Templates
 * @since   1.5.1
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<style id="apollo-listing-header-kernel">
/* ═══════════════════════════════════════════════════════════════════════════
   HOST — the header and its two overlays are siblings.
   The overlays must NOT be children of <header>: the header is a normal
   in-flow band and any transform/overflow on it would trap a fixed child.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh-host { position: relative; display: block; }

/* ═══════════════════════════════════════════════════════════════════════════
   MONTH LABEL — capitalised here, in the kernel, not in a skin.
   date_i18n('F') returns the month lowercase in pt_BR ("setembro"), which is
   correct Portuguese in a sentence and wrong as the display-type title of a
   screen. Doing it in CSS keeps the DOM text the locale's own string, so a
   screen reader and a copy-paste still get "setembro" — and every skin gets
   the same treatment without repeating the rule.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh__month { text-transform: capitalize; }

/* ═══════════════════════════════════════════════════════════════════════════
   OVERLAY BASE — circular wipe surface shared by search + filter
   ═══════════════════════════════════════════════════════════════════════════ */
.alh-ov {
  position: fixed;
  inset: 0;
  width: 100%;
  max-width: 100vw;
  height: 100%;
  height: 100dvh;
  background: var(--bg);
  clip-path: circle(0% at 50% 50%);
  display: flex;
  flex-direction: column;
  box-shadow: none;
  border: none;
  outline: none;
  overflow: hidden;
  visibility: hidden;
  pointer-events: none;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.alh-ov::-webkit-scrollbar { display: none; width: 0; height: 0; }
.alh-ov--filter { z-index: 9970; }
.alh-ov--search { z-index: 9975; justify-content: flex-start; }

.alh-ov__hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px;
  padding-top: calc(var(--safe-top, 0px) + 18px);
  flex-shrink: 0;
  box-shadow: none;
}
.alh-ov__hd h3 {
  font-family: var(--ff-heading);
  font-size: max(var(--fs-p1), 16px);
  font-weight: 700;
  color: var(--txt-heading);
  margin: 0;
}
.alh-ov__close {
  width: 34px;
  height: 34px;
  padding: 0;
  border: none;
  border-radius: var(--r-pill);
  background: var(--surface);
  color: var(--txt-heading);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: none;
}
.alh-ov__body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 4px 18px 18px;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.alh-ov__body::-webkit-scrollbar { display: none; width: 0; height: 0; }
.alh-ov__footer {
  padding: 0 18px calc(18px + var(--safe-bottom, 0px));
  flex-shrink: 0;
  box-shadow: none;
  margin-top: 0;
}

/* Wide viewports: the panel is a centred column, not a 1600px-wide form. */
@media (min-width: 1000px) {
  .alh-ov__hd,
  .alh-ov__body,
  .alh-ov__footer { width: min(100%, 720px); margin-inline: auto; }
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTER PRIMITIVES — groups, chips, footer buttons
   Markup is server-rendered (PHP owns the terms); these style it.

   TYPE SCALE — REVISED 2026-08-08. The chips, the empty state and the footer
   buttons were all on --fs-p4, which core.js defines as

       calc(var(--fs-u) * clamp(.5625rem, .5rem + .27vi, .75rem))

   — 9-12px at the default scale, and --fs-u is a USER PREFERENCE multiplier, so
   a reader who has scaled Apollo down was being served roughly 7px. That token
   is sized for a caption riding under something else, not for the only content
   of a full-screen panel: every readable thing in this overlay is now --fs-p2
   (13-16px) with a hard px floor under it. The floor is the point — bumping the
   token alone still hands --fs-u the power to shrink a primary control back
   into illegibility, and a filter nobody can read is a filter nobody uses.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh-fx__group + .alh-fx__group { margin-top: 22px; }
.alh-fx__title {
  font-family: var(--ff-mono);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .12em;
  color: var(--muted);
  font-weight: 700;
  margin-bottom: 10px;
  display: block;
}
.alh-fx__chips { display: flex; flex-wrap: wrap; gap: 8px; }
.alh-fx__chip { position: relative; }
.alh-fx__chip input { position: absolute; opacity: 0; width: 0; height: 0; }
.alh-fx__chip label {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 7px 13px;
  border-radius: var(--r-sm);
  background: var(--surface);
  color: var(--txt-color);
  font-size: max(var(--fs-p2), 13px);
  line-height: 1.25;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid rgba(var(--rgb-diff), .06);
  user-select: none;
  transition: background .22s var(--ease), color .22s var(--ease);
  box-shadow: none;
}
.alh-fx__chip label i {
  font-size: .9em;
  opacity: 0;
  width: 0;
  transition: width .18s var(--ease), opacity .18s var(--ease);
}
.alh-fx__chip input:checked + label {
  background: var(--txt-heading);
  color: var(--bg);
  border-color: transparent;
}
.alh-fx__chip input:checked + label i { opacity: 1; width: 1em; }
.alh-fx__chip input:focus-visible + label { outline: 2px solid var(--accent); outline-offset: 2px; }

.alh-fx__empty {
  padding: 12px 14px;
  border-radius: var(--r-sm);
  background: var(--surface);
  color: var(--muted);
  font-size: max(var(--fs-p2), 13px);
  line-height: 1.4;
}

.alh-fx__footer { display: flex; gap: 8px; margin-top: 18px; }
.alh-fx__btn-clear,
.alh-fx__btn-apply {
  padding: 13px 18px;
  border: none;
  border-radius: var(--r-pill);
  font-family: var(--ff-main);
  font-size: max(var(--fs-p2), 13px);
  line-height: 1.2;
  cursor: pointer;
  text-align: center;
  box-shadow: none;
}
.alh-fx__btn-clear {
  flex: 0 0 auto;
  background: var(--surface);
  color: var(--txt-heading);
  font-weight: 600;
}
.alh-fx__btn-apply {
  flex: 1;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: linear-gradient(135deg, var(--onyx-800), var(--onyx-950));
  color: #fff;
}
.alh-fx__n {
  background: rgba(255, 255, 255, .18);
  border-radius: var(--r-pill);
  padding: 1px 7px;
  font-family: var(--ff-mono);
  font-size: 11px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   SEARCH PRIMITIVES — input + the discreet "−" submit
   The input was already on --fs-p2 and so was never the 7px offender, but it
   shares the --fs-u multiplier that caused it; the same floor applies. On iOS
   it is also the difference between typing and Safari zooming the page.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh-search__panel {
  padding: calc(var(--safe-top, 0px) + 22px) 18px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
@media (min-width: 1000px) {
  .alh-search__panel { width: min(100%, 720px); margin-inline: auto; }
}
.alh-search__row { display: flex; align-items: center; gap: 8px; }
.alh-search__input {
  flex: 1;
  min-width: 0;
  height: 48px;
  padding: 0 16px;
  border: none;
  border-radius: var(--r-pill);
  background: var(--surface);
  color: var(--txt-heading);
  font-family: var(--ff-main);
  font-size: max(var(--fs-p2), 16px);
  outline: none;
  box-shadow: none;
  -webkit-appearance: none;
}
.alh-search__input::placeholder { color: var(--muted); }
.alh-search__go {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: var(--r-pill);
  background: transparent;
  color: var(--muted);
  font-family: var(--ff-mono);
  font-size: 22px;
  font-weight: 300;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: none;
  opacity: .55;
  transition: opacity .35s var(--ease), color .35s var(--ease);
}
.alh-search__go:hover,
.alh-search__go:focus-visible {
  opacity: 1;
  color: var(--txt-heading);
  outline: none;
}
.alh-search__hint {
  font-family: var(--ff-mono);
  font-size: 11px;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--muted);
  margin: 0;
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTER BADGE — hidden at zero, so a pristine header is byte-equal to the
   approved lab render; it only appears once the user has actually filtered.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh__badge {
  position: absolute;
  top: 4px;
  right: 3px;
  min-width: 15px;
  height: 15px;
  padding: 0 4px;
  border-radius: var(--r-pill);
  background: var(--accent);
  color: var(--bg);
  font-family: var(--ff-mono);
  font-size: 9px;
  font-weight: 700;
  line-height: 15px;
  text-align: center;
  opacity: 0;
  transform: scale(.6);
  pointer-events: none;
  transition: opacity .2s var(--ease), transform .2s var(--ease-snappy, var(--ease));
}
.alh__badge.is-visible { opacity: 1; transform: none; }

@media (prefers-reduced-motion: reduce) {
  .alh-fx__chip label,
  .alh__badge,
  .alh-search__go { transition: none; }
}
</style>
