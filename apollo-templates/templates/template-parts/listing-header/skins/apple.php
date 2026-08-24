<?php

/**
 * Apollo Listing Header — skin: "apple" (lab Header 02, Kinetic Mask · Apple).
 *
 * Port of `<style id="header-02">` from the approved lab file
 * screen/portal/header listing/header-of-listing-events.html (lines 939-1036),
 * with `.Header02*` rewritten to the block's own `.alh--apple .alh__*`
 * contract. The lab's own note applies here too: this is Header 01's skeleton
 * on the white canvas. No brand mark, no black band, transparent icon buttons.
 *
 * TWO ROWS — REVISED 2026-08-08
 * ─────────────────────────────────────────────────────────────────────────────
 * An intermediate revision collapsed the header into a single row with the
 * month cut to ~1.5rem. Rejected, and rightly: at that size the month name
 * stops being the identity of the screen and becomes a label. The approved
 * shape keeps the lab's 3rem display type and puts the controls BESIDE it
 * instead of above it, leaving row two to the scrubber alone:
 *
 *   ┌──────────────────────────────────────────────────────────┐
 *   │                                                     ›    │ ← arrow, lifted
 *   │  Setembro ’26                                       ⛭    │   out of flow
 *   │  ▬▬ ▬▬ ▬▬ ▬▬ ▬▬ ▬▬ ▬▬ ▬▬ ▬▬ ▬▬ ▬▬ ▬▬                     │
 *   └──────────────────────────────────────────────────────────┘
 *
 * It is a two-column grid, and the placement is deliberate rather than left to
 * source order: only `.alh__top` is pinned (row 1, column 2). Everything else
 * auto-places down column 1, which is what lets the optional `.alh__mark`
 * become a kicker row above the month without an always-present empty track —
 * an unused grid row still costs a row-gap — when no caller passes one.
 *
 * THREE VALUES THAT ARE LOAD-BEARING, NOT TASTE
 * ─────────────────────────────────────────────────────────────────────────────
 * · `.alh__seg { display: block }`. The segment is a <span>; the lab styled it
 *   `height:3px` with no display, and height does not apply to an inline box.
 *   All twelve bars computed to zero and the scrubber rendered as an invisible
 *   strip of hit areas. THIS was the bug — the height value never was.
 * · The active segment differs by COLOUR ONLY. No scaleY, no taller bar. Twelve
 *   bars of one height read as a rail with a position marked on it; a bar that
 *   grows reads as a chart, and the header would be claiming a magnitude the
 *   scrubber does not measure.
 * · The filter's quietness is in its COLOUR, not in `opacity`. An opacity below
 *   1 makes the button a group, and the count badge — which has to stay legible
 *   the moment a filter is on — would fade with it.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<style id="apollo-listing-header-skin-apple">
/* ═══════════════════════════════════════════════════════════════════════════
   THE GRID — row 1: month | controls.  row 2: the twelve segments.
   Column 1 is minmax(0, 1fr) rather than 1fr so a long month name shrinks the
   track instead of blowing the grid out past the viewport.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh--apple {
  --alh-gutter: 18px;
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: end;
  column-gap: 14px;
  row-gap: 2px;
  background: transparent;
  color: var(--txt-heading);
  padding: 22px var(--alh-gutter) 12px;
  box-shadow: none;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.alh--apple::-webkit-scrollbar { display: none; width: 0; height: 0; }

/* Optional caller mark — auto-places as a kicker row above the month. */
.alh--apple .alh__mark {
  grid-column: 1;
  font-family: var(--ff-mono);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .2em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 2px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   ROW 1 · CONTROLS — the only explicitly placed item in the grid.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh--apple .alh__top {
  grid-column: 2;
  grid-row: 1;
  align-self: end;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}
/* Positioned: it is the frame the arrow is lifted out of. */
.alh--apple .alh__icons {
  position: relative;
  display: flex;
  align-items: center;
  gap: 2px;
}

.alh--apple .alh__ic {
  position: relative;
  width: 24px;
  height: 24px;
  padding: 0;
  border: none;
  border-radius: var(--r-pill);
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  color: var(--txt-heading);
  opacity: .72;
  cursor: pointer;
  box-shadow: none;
  transition: color .28s var(--ease), opacity .28s var(--ease);
}
/* ONE glyph size for the whole action cluster. It was declared twice — a flat
   1rem here and a second 1rem on the filter — which meant a change to the
   cluster's type had to be made in two places to take effect. The size is now
   in the SAME unit as the rest of the header's type (`calc(px * --fs-u)`), so
   it tracks the user's scale preference instead of the root font size. */
.alh--apple .alh__ic i,
.alh--apple .alh__ic--filter i {
  font-size: calc(17px * var(--fs-u, 1));
  z-index: 100;
  line-height: 1;
}
.alh--apple .alh__ic:hover,
.alh--apple .alh__ic:focus-visible { opacity: 1; color: var(--txt-heading); outline: none; }

/* ── The month arrow, lifted 25px clear of the icon row ────────────────────
   Out of flow, so it neither widens column 2 nor deepens row 1: it occupies
   the empty air beside the top of the display type, where nothing else is.
   ONLY `prev` can reach these rules now, and it is off by default — the `next`
   control was removed from parts/actions.php entirely (2026-08-09); month
   navigation lives in the twelve-segment scrubber, which is a map of the year
   rather than a one-step nudge. The `--prev` offset is kept for a caller that
   turns the arrow on beside a future second step control. ── */
.alh--apple .alh__step {
  position: absolute;
  top: -25px;
  right: 0;
  width: 24px;
  height: 24px;
  padding: 0;
  border: none;
  background: transparent;
  border-radius: var(--r-pill);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  cursor: pointer;
  box-shadow: none;
  transition: color .28s var(--ease);
}
.alh--apple .alh__step--prev { right: 26px; }
.alh--apple .alh__step:hover,
.alh--apple .alh__step:focus-visible { color: var(--txt-heading); outline: none; }
/* The step glyph is a WATERMARK, not a control label: oversized, at .1 alpha,
   bleeding out of its 24px box and sitting under the icon cluster (z-index 1
   against the cluster's 100). The negative margins are what let it overhang
   without widening column 2 or deepening row 1. */
.alh--apple .alh__step i {
  font-size: 5rem;
  line-height: 1;
  margin: -5px -25px 10px 10px;
  opacity: .1;
  z-index: 1;
}

/* ── Filter: the shared glyph size above, quieted by colour alpha ─────────── */
.alh--apple .alh__ic--filter {
  opacity: 1;
  color: rgba(var(--rgb-diff), .2);
  transition: color .5s var(--ease);
}
.alh--apple .alh__ic--filter:hover,
.alh--apple .alh__ic--filter:focus-visible { opacity: 1; color: var(--txt-heading); }
/* An active filter is state, not decoration: it stops whispering. */
.alh--apple .alh__ic--filter:has(.alh__badge.is-visible) { color: var(--txt-heading); }
.alh--apple .alh__ic--filter .alh__badge {
  top: -2px;
  right: -3px;
  min-width: 13px;
  height: 13px;
  padding: 0 3px;
  font-size: 9px;
  line-height: 13px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   ROW 1 · KINETIC MASK — the lab's display type, at the lab's size.
   `min()` only bites under ~384px, where a flat 3rem would push "Setembro ’26"
   past the gutter and the mask's own overflow would eat the last letters. On
   every mainstream handset the first term wins and this IS 3rem.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh--apple .alh__mask {
  grid-column: 1;
  position: relative;
  z-index: 2;
  overflow: hidden;
  display: flex;
  align-items: baseline;
}
.alh--apple .alh__month,
.alh--apple .alh__year {
  display: inline-block;
  font-family: var(--ff-heading);
  font-weight: 900;
  font-size: min(3rem, 12.5vw);
  letter-spacing: -.02em;
  line-height: .9;
  white-space: nowrap;
  color: var(--txt-heading);
}
.alh--apple .alh__month { clip-path: inset(0 0 0 0); }
.alh--apple .alh__year { opacity: .28; margin-left: 8px; }

/* ═══════════════════════════════════════════════════════════════════════════
   ROW 2 · 12-SEGMENT SCRUBBER — full width, twelve bars of ONE height.
   The bar is a BLOCK: as an inline <span> its height was ignored and all
   twelve computed to zero, which is what made the scrubber invisible.
   ═══════════════════════════════════════════════════════════════════════════ */
.alh--apple .alh__scrub {
  grid-column: 1 / -1;
  position: relative;
  z-index: 2;
  min-width: 0;
  display: flex;
  align-items: center;
}
.alh--apple .alh__seg-hit {
  flex: 1 1 0;
  min-width: 0;
  /* The bar is 5px; the button is 25px. The difference is the touch target —
     invisible, contiguous with its neighbours, and the reason a thumb can hit
     a month on a phone at all. */
  padding: 10px 2px;
  border: none;
  background: transparent;
  cursor: pointer;
  line-height: 0;
}
.alh--apple .alh__seg-hit:focus-visible { outline: none; }
.alh--apple .alh__seg {
  display: block;
  width: 100%;
  height: 5px;
  border-radius: var(--r-pill);
  background: rgba(var(--rgb-diff), .12);
  transition: background .45s var(--ease), opacity .45s var(--ease);
}
/* A month with nothing in it says so, faintly. The count is real data the
   scrubber already carries — this is what turns twelve dashes into a map.
   It is an alpha, never a size: the bars stay one height. */
.alh--apple .alh__seg-hit[data-alh-count="0"] .alh__seg { opacity: .45; }
.alh--apple .alh__seg-hit:focus-visible .alh__seg,
.alh--apple .alh__seg-hit:hover .alh__seg {
  background: rgba(var(--rgb-diff), .3);
  opacity: 1;
}
/* THE SELECTED MONTH — colour only. Never a transform, never a taller bar. */
.alh--apple .alh__seg-hit.is-active .alh__seg {
  background: var(--accent);
  opacity: 1;
}

/* ── Desktop: .ax-main already owns the gutters ───────────────────────────── */
@media (min-width: 1000px) {
  .alh--apple {
    --alh-gutter: 0px;
    column-gap: 24px;
    padding: 26px 0 14px;
  }
  .alh--apple .alh__month,
  .alh--apple .alh__year { font-size: 3rem; }
}

/* ── Mobile: the search button stands down ─────────────────────────────────
   Under 768px row one has to carry 3rem of display type and the controls
   across ~320px. Search is the one affordance with a full-screen panel of its
   own and no inline representation, so it is the one that yields. The button
   is still in the DOM with its id, and ApolloListingHeader.get(id).openSearch()
   still opens the panel — a screen that wants search on a phone can surface it
   anywhere. ── */
@media (max-width: 767px) {
  .alh--apple .alh__ic--search { display: none; }
}

@media (prefers-reduced-motion: reduce) {
  .alh--apple .alh__seg { transition: none; }
  .alh--apple .alh__month { clip-path: none !important; }
}
</style>
