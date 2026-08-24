<?php

/**
 * Portal de Eventos — styles: Lightbox (full-viewport single event)
 *
 * PORTAL-SCOPED OVERRIDES for the shared `.ev-lb` shell that
 * apollo_event_lightbox_shell() prints. The base rules live in
 * apollo-events/assets/css/apollo-single-event.css and are shared by every
 * surface; this cell changes only what the PORTAL needs and nothing else.
 *
 * Two defects it fixes (2026-08-01):
 *
 *  1 · SIZE — the base sheet is `--ev-lb-w: min(100vw, 560px)` with
 *      `height: min(94dvh, 980px)` on desktop, i.e. a centred 560px card. The
 *      spec for the portal is the FULL single-event page taking the whole
 *      viewport, with an ✕ to close. A 560px column also silently breaks the
 *      single page's own layout: `--ev-vw` is the instance's viewport width,
 *      so every full-bleed block inside the fragment was being asked to lay
 *      out inside a phone-width column on a 1440px screen.
 *
 *  2 · STACKING — `.ev-lb` sits at z-index 9900. The Apollo+ shell puts
 *      `.ax-top` at 9901 and `.ax-aside` at 9902 (topbar-styles.php /
 *      aside-styles.php), so the topbar and the drawer painted OVER a
 *      "full-screen" overlay. Anything meant to cover the shell has to clear
 *      the shell's own ceiling, so this goes above both.
 *
 * DS language is preserved, not re-invented: the ✕ uses the same glass panel
 * recipe as the topbar (`rgba(var(--rgb-theme),.25)` + blur(20px)
 * saturate(180%)` + the etched bevel), the same `--r-pill`, the same
 * `var(--ease)` / `var(--ease-snappy)` easings.
 *
 * OWNERSHIP — READ BEFORE EDITING (added 2026-08-17)
 * -------------------------------------------------
 * This cell is the DECLARED LAST-WORD OWNER of the reader surface colour:
 *
 *   · `.ev-lb-panel`  background + the local `--ev-*` token pin
 *   · `.ev-lb-bd`     backdrop background
 *
 * The base sheet (apollo-single-event.css:1033, :1042) still declares its own
 * values for both. That is correct and deliberate — it is the standalone-page
 * default, and this cell out-specifies it via the doubled selector. DO NOT
 * "fix" the duplication by editing both: two cells declaring the same property
 * with different intents is the cardinal sin that put the month title on top of
 * the hero on /eventos. Change the colour HERE, in one block, or nowhere.
 *
 * @package Apollo\Event
 * @since   1.5.5
 * @see     assets/css/apollo-single-event.css  base `.ev-lb` shell
 * @see     _inventory/PLAN-surface-lightbox-global.md §2  the #fff mandate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * PRINT-ONCE LEDGER (2026-08-07) — this cell is now reachable from two paths.
 *
 * Historically only styles.php's cascade required it, so "runs once" was a
 * property of the portal. It is not portal-specific CSS: every selector below
 * is `.ev-lb.ev-lb …`, describing the shared shell, and without it the
 * lightbox falls back to the base sheet's 560px centred card. So
 * apollo_event_lightbox_styles() (render-single.php) now also captures it for
 * the public enqueue API, which ships the lightbox to screens that never load
 * the portal cascade.
 *
 * Whichever path arrives first emits; the second gets nothing. The cell claims
 * the ledger rather than the caller, because the cascade requires this file
 * directly and never passes through the capture function.
 */
if ( function_exists( 'apollo_event_lightbox_state' ) ) {
	$pev_lb_state = apollo_event_lightbox_state();
	if ( $pev_lb_state['css'] ) {
		return;
	}
	apollo_event_lightbox_state( 'css' );
}
?>
<style id="apollo-pev-lightbox">
/* ═══ 0 · WHY EVERY SELECTOR BELOW IS DOUBLED ═════════════════════════════
   LOAD ORDER IS AGAINST US. This cell ships in <head> (archive-event.php
   buffers portal/styles.php into $extra_head), but the base sheet it overrides,
   assets/css/apollo-single-event.css, is printed by apollo_event_lightbox_boot()
   near the END OF <body>. Later in document order wins a specificity tie, so a
   plain `.ev-lb { z-index: 10500 }` here would be silently beaten by the base
   `.ev-lb { z-index: 9900 }` — the override would look correct in the source
   and do nothing on screen.

   So overrides are written one class heavier (`.ev-lb.ev-lb`, `.ev-lb .ev-lb-x`)
   which wins on SPECIFICITY and is therefore immune to load order. This is
   deliberate, not a typo — do not "clean up" the repetition. Anything that
   must beat the base sheet needs the doubled form; anything genuinely new
   (no base rule to fight) can stay single.
   ═══════════════════════════════════════════════════════════════════════════ */

/* ═══ 1 · FULL VIEWPORT ═══════════════════════════════════════════════════
   MEASURED Z-INDEX MAP of everything this must clear (2026-08-01 audit):

     .panel-r        9800   topbar profile drawer   ┐
     .apps-pop       9801   apps popup              │ apollo-plus/
     .ax-overlay     9899   shell scrim             │ topbar-styles.php
     .ax-top-blur    9900   topbar blur veil        │ + aside-styles.php
     .ax-top         9901   topbar                  │
     .ax-aside       9902   nav drawer  ← ceiling   ┘
     .pev-rail-modal 9965   portal rail lightbox      styles-rails.php
     .alh-ov--filter 9970   listing-header filter     apollo-templates
     .alh-ov--search 9975   listing-header search     listing-header/kernel-styles.php

   REVISED 2026-08-08: the old top of this table was `.pev-masthead 10070
   (only while filter open)`, from styles-masthead.php. That file and that
   markup are gone — /eventos now uses the apollo_listing_header() block, whose
   two wipe overlays sit at 9970/9975. They are full-screen panels and must
   clear the shell chrome, but they are still page controls, so they stay under
   the single-event reader.

   Highest competitor is now 9975, so 10500 clears the ecosystem with headroom.
   BUT THE NUMBER WAS NEVER THE PROBLEM: the shell is printed inside
   <main class="ax-main">, whose `overflow-x: clip` both clips fixed
   descendants and scopes their stacking context — so .ev-lb was competing
   against .ax-main's children, not against .ax-top. apollo-event-lightbox.js
   now re-parents the shell to <body> on first open (see the PORTAL THE SHELL
   comment there); this z-index only decides the outcome once that is true. */
.ev-lb.ev-lb {
  z-index: 10500;
  align-items: stretch;
  justify-content: stretch;
  --ev-lb-w: 100vw;
  --ev-vw: 100vw;
  /* Root-context overlay: nothing inside may leak out of it either. */
  isolation: isolate;
}
.ev-lb.ev-lb .ev-lb-panel {
  width: 100vw;
  max-width: none;
  height: 100dvh;
  max-height: 100dvh;
  border-radius: 0;
  /* Entry is a lift + settle, not a slide-up sheet: at full-bleed a large
     translate reads as the page lurching. Short, damped, deliberate. */
  transform: scale(.985);
  opacity: 0;
  transition:
    transform .52s var(--ease-smooth, cubic-bezier(.16, 1, .3, 1)),
    opacity .3s var(--ease, ease);
  will-change: transform, opacity;

  /* ── SOLID WHITE READER, BOTH THEMES (2026-08-17) ───────────────────────
     REQUIREMENT: the surface behind a fragment is always solid #fff. Never a
     token that can flip, never rgba with alpha, never a blur that lets the
     page behind read through.

     WHY A LITERAL AND NOT var(--ev-bg). Traced through the live cascade:

       .ev-lb-panel{background:var(--ev-bg)}   apollo-single-event.css:1042
       --ev-bg: var(--white-1)                 apollo-single-event.css:43
       html.dark-mode{--white-1:#0b0b0d}       theme
       html.dark-mode{--rgb-diff:255,255,255}  apollo-auth-uni.css:121
       --ev-ink: rgba(var(--rgb-diff),1)       apollo-single-event.css:45

     So in dark mode the panel was #0b0b0d — near black — and the requirement
     was silently violated on half the installs.

     THE TRAP THIS BLOCK EXISTS TO AVOID: pinning `background` alone gives
     WHITE TEXT ON WHITE, because --ev-ink inverts independently of --ev-bg.
     That renders as a blank panel with a working ✕ — the exact symptom the
     2026-08-07 session chased through two rounds of "verified on disk" fixes
     before finding the stale-asset cause. The whole local --ev-* block has to
     be pinned together or not at all.

     These are COMPONENT-SCOPED custom properties, which the design system
     explicitly blesses (`.pev { --pev-band: … }`). This is not a :root entry
     and must never become one — :root belongs to core.js.

     Values are the light-theme literals from apollo-single-event.css:43-49,
     resolved (--rgb-diff → 10,10,10) rather than referenced, so a theme flip
     cannot reach them.

     TRADE-OFF, STATED: dark-mode readers get a light document panel. That is
     deliberate — the fragment is a document, not chrome. Revisit here, in one
     block, if it ever changes. */
  --ev-bg:    #fff;
  --ev-ink:   rgba(10, 10, 10, 1);
  --ev-mute:  rgba(10, 10, 10, .48);
  --ev-faint: rgba(10, 10, 10, .28);
  --ev-line:  rgba(10, 10, 10, .07);
  --ev-line2: rgba(10, 10, 10, .12);
  --ev-soft:  rgba(10, 10, 10, .035);
  background: #fff;
}
.ev-lb.ev-lb.is-open .ev-lb-panel { transform: none; opacity: 1; }

/* The base sheet's own @media (min-width:720px) re-centres and re-rounds the
   panel into a 560px card. At full-bleed neither applies — and this must
   out-specify it, not merely follow it. */
@media (min-width: 720px) {
  .ev-lb.ev-lb { align-items: stretch; }
  .ev-lb.ev-lb .ev-lb-panel {
    height: 100dvh;
    max-height: 100dvh;
    border-radius: 0;
    box-shadow: none;
  }
}

/* Backdrop: OPAQUE WHITE, no alpha, no blur.
   The old value was rgba(var(--rgb-diff),.72). The comment above it claimed
   "nothing shows through a full-bleed panel" — true once the panel is open,
   false for the ~520ms it is still transitioning from opacity:0. During those
   frames the backdrop IS the surface, and at .72 alpha the page underneath
   read through it. Same requirement as the panel, same reason, same literal. */
.ev-lb.ev-lb .ev-lb-bd {
  background: #fff;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
}

/* ═══ 2 · CLOSE CONTROL — bare glyph, auto-inverting ══════════════════════
   Your call, and it is the right one. `mix-blend-mode: difference` with a
   white glyph subtracts the backdrop from white, so the ✕ renders as the exact
   photographic inverse of whatever is behind it: black on the white DJ section,
   white on the dark hero, always separated — with no plate, no scrim and no
   drop-shadow. It cannot be low-contrast, because "same colour as the
   backdrop" would require the backdrop to be mid-grey in every channel at
   once, and even then grayscale(1) keeps the result neutral rather than
   letting a saturated cover tint the glyph.

   Two requirements this depends on, both satisfied:
     · Blending needs a stacking context to blend WITHIN, or it would reach
       past the panel — .ev-lb now sets `isolation: isolate`.
     · `filter` and `mix-blend-mode` on one element compose in that order
       (filter first, then blend), which is what we want. The old
       drop-shadow is gone — a shadow under an inverting glyph fights it. */
.ev-lb.ev-lb .ev-lb-x {
  top: calc(14px + var(--ev-st, 0px) + var(--safe-top, 0px));
  right: clamp(12px, 2.4vw, 22px);
  width: 44px;
  height: 44px;
  padding: 0;
  border: 0;
  border-radius: 0;
  background: none;
  background-color: transparent;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
  box-shadow: none;
  color: #fff;
  font-size: 28px;
  line-height: 1;
  mix-blend-mode: difference;
  filter: grayscale(1);
  transition:
    transform .28s var(--ease-snappy, cubic-bezier(.34, 1.16, .64, 1)),
    opacity .2s var(--ease, ease);
}
/* Safari < 15.4 ignores mix-blend-mode on a filtered element; the glyph would
   render plain white. A hairline text-shadow keeps it legible there without
   affecting browsers where the blend works (difference swallows it). */
@supports not (mix-blend-mode: difference) {
  .ev-lb.ev-lb .ev-lb-x { text-shadow: 0 1px 6px rgba(0, 0, 0, .6); }
}
.ev-lb.ev-lb .ev-lb-x i,
.ev-lb.ev-lb .ev-lb-x svg { font-size: inherit; color: inherit; fill: currentColor; }
.ev-lb.ev-lb .ev-lb-x:hover { transform: scale(1.1); opacity: .82; background: none; }
.ev-lb.ev-lb .ev-lb-x:active { transform: scale(.92); }
.ev-lb.ev-lb .ev-lb-x:focus-visible {
  outline: 2px solid color-mix(in srgb, var(--accent) 75%, transparent);
  outline-offset: 4px;
  border-radius: var(--r-sm);
}

/* ═══ 3 · SCROLL SURFACE ══════════════════════════════════════════════════ */
/* The panel is its OWN scroll container and carries data-lenis-prevent (see
   apollo_event_lightbox_shell()), so Lenis leaves its wheel/touch events alone
   and the browser scrolls it natively.
   · overscroll-behavior:contain — reaching the end must not chain out and
     scroll the locked document behind.
   · touch-action:pan-y — iOS Safari otherwise hands vertical drags to the
     page once Lenis has released them.
   · scroll-behavior:auto — a CSS smooth-scroll here would fight both the
     native momentum and ScrollTrigger's measurements. */
.ev-lb.ev-lb .ev-lb-scroll {
  overflow-y: auto;
  overflow-x: hidden;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
  touch-action: pan-y;
  scroll-behavior: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.ev-lb.ev-lb .ev-lb-scroll::-webkit-scrollbar { width: 0; height: 0; display: none; }
.ev-lb.ev-lb .ev-lb-scroll:focus { outline: none; }
/* Nothing inside may trap the gesture with its own scroll container. */
.ev-lb.ev-lb .ev-lb-body { min-height: 100%; }

/* ── Content entry ───────────────────────────────────────────────────────────
   Pairs with the two-rAF class flip in apollo-event-lightbox.js's inject().
   The PANEL glides in empty while the fragment is still in flight; without
   this the fragment then paints in one frame and the eye reads a hard cut
   mid-glide ("stairs"). A short rise+fade on the filled body turns the seam
   into one continuous movement.

   Deliberately SHORT (.34s) and small (10px): the panel is already moving, so
   a long content animation would read as two competing motions rather than
   one. filter/blur is avoided — it would repaint the whole fragment every
   frame on a panel that may hold a map and a slider. */
.ev-lb.ev-lb .ev-lb-body {
  opacity: 0;
  transform: translate3d(0, 10px, 0);
  transition:
    opacity .34s var(--ease, ease),
    transform .34s var(--ease-smooth, cubic-bezier(.16, 1, .3, 1));
  will-change: opacity, transform;
}
.ev-lb.ev-lb .ev-lb-body.is-entered {
  opacity: 1;
  transform: none;
}
/* Once settled, drop the compositor hint — the panel can hold a Leaflet map
   and a GSAP rail, and a permanent will-change on their container keeps a
   layer alive for the whole session. */
.ev-lb.ev-lb .ev-lb-body.is-entered { will-change: auto; }

@media (prefers-reduced-motion: reduce) {
  .ev-lb.ev-lb .ev-lb-body { transition: none; opacity: 1; transform: none; }
}

/* ═══ 4 · NO TEXT SELECTION ANYWHERE INSIDE ══════════════════════════════ */
/* The lightbox is an app surface, not a document: drag-select produced blue
   highlight smears across the hero and the fact rows during scroll gestures.
   Inputs and textareas are deliberately exempt — a form the user cannot type
   into is a worse bug than the one being fixed. */
.ev-lb.ev-lb,
.ev-lb.ev-lb * {
  -webkit-user-select: none !important;
  -moz-user-select: none !important;
  -ms-user-select: none !important;
  user-select: none !important;
  -webkit-touch-callout: none;
}
.ev-lb.ev-lb input,
.ev-lb.ev-lb textarea,
.ev-lb.ev-lb [contenteditable="true"],
.ev-lb.ev-lb [contenteditable="true"] * {
  -webkit-user-select: text !important;
  -moz-user-select: text !important;
  -ms-user-select: text !important;
  user-select: text !important;
}

/* ═══ 5 · REVEALS MUST NEVER STRAND CONTENT ══════════════════════════════ */
/* .ev-reveal ships opacity:0 + translateY(28px) and depends on ScrollTrigger
   to clear it. Inside the panel the scroller is not the window, so a trigger
   that never resolves used to leave whole sections permanently invisible —
   content present in the DOM, unreadable on screen.
   The panel gets a CSS floor: once .is-open settles, reveals are visible by
   default. GSAP still animates them (inline styles win over this rule), so the
   motion is unchanged when it works — this only guarantees the end state. */
/* GENERATED FROM APOLLO_EVENT_REVEAL_FLOOR (P3, 2026-08-07).
   This block used to be a hand-maintained copy of a selector list that also
   lived in apollo-single-event.js and bootstrap.php, each carrying a "keep in
   sync" comment. It is now emitted from the PHP constant those two also read
   at runtime, so adding a part to the single-event template cannot leave the
   floor behind. Contract + rationale: includes/render-single.php.

   NOTE this is the pre-mount / belt-and-braces layer only. The root fix is
   apollo-single-event.js passing `scroller` to every ScrollTrigger it creates
   so reveals resolve against this panel instead of the window. GSAP writes
   inline styles, which outrank this rule, so enter/leave motion is unaffected
   — this only guarantees the element is never stranded unreadable. */
<?php
/*
 * function_exists() rather than a bare call: a theme may render this cell in
 * isolation, and a fatal here would take the whole portal down. The literal
 * fallback is the 2026-08-01 list, deliberately kept short of the constant's
 * so a drift shows up as a missing floor, never as a white screen.
 */
echo function_exists( 'apollo_event_reveal_css_selector' )
	? apollo_event_reveal_css_selector( '.ev-lb.ev-lb.is-open ' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selector list from a PHP constant.
	: '.ev-lb.ev-lb.is-open .ev-reveal';
?> { opacity: 1; transform: none; }
/* Belt and braces: bootstrap.php adds this after mount if anything is still
   stranded, and it outranks a stuck inline opacity. */
.ev-lb.ev-lb .ev-force-visible,
.ev-lb.ev-lb .ev-force-visible * {
  opacity: 1 !important;
  visibility: visible !important;
  transform: none !important;
}

/* At full-bleed the embedded hero can use the real viewport again, instead of
   the 78dvh compromise the 560px sheet needed. */
.ev-lb.ev-lb .ev-root.is-embed .ev-hero { height: 92dvh; min-height: 520px; }
.ev-lb.ev-lb .ev-root.is-embed .ev-hero-top { padding-right: 76px; }

/* ═══ 6 · READING COLUMN — the "old mobile" fix ═══════════════════════════
   THE WHITE BAND BELOW THE DJ BLOCK WAS NOT A BUG, IT WAS THE DESIGN — seen
   at the wrong size. apollo-single-event.css is a mobile-first document:

     :root { --ev-max: 440px }                  ·· 500px above 540px wide
     .ev-wrap { width: min(100%, var(--ev-max)); margin-inline: auto }

   On /evento/{slug} at phone width that column IS the page. Inside a
   full-viewport lightbox on a 1440px screen the same rule pins every section
   to a 500px strip centred on --ev-bg (which is white in the light theme), so
   the DJ block down to the footer reads as a narrow phone layout stranded in a
   white void. Nothing was broken; it was a 500px page in a 1440px window.

   --ev-max is a plain custom property, so re-declaring it on .ev-lb re-scopes
   it for everything inside WITHOUT touching :root and without affecting the
   real single page. Component scope, exactly like --pev-band. The column grows
   with the viewport and stops at a sane measure — past ~900px running text
   becomes hard to track, so this widens the layout without wrecking legibility.

   Full-bleed blocks (.v-slider, .ev-map) key off --ev-vw, already set to 100vw
   above, so they still break out edge-to-edge over the wider column. */
@media (min-width: 720px) {
  .ev-lb.ev-lb { --ev-max: clamp(560px, 62vw, 900px); --ev-pad: clamp(28px, 3.4vw, 44px); }
}
@media (min-width: 1200px) {
  .ev-lb.ev-lb { --ev-max: clamp(720px, 58vw, 980px); }
}

/* ═══ 7 · THE "SPARK" ON SCROLL ══════════════════════════════════════════
   `.ev-wrap > * { transition: all .66s ease }` in the base sheet animates
   EVERY animatable property on every direct child. Combined with the reveal
   pass and a late-decoding hero, any layout change — width, height, colour,
   transform, background — cross-fades over 0.66s at once, which is the flash
   of white and the "spark then content appears" during scroll.

   `transition: all` is also a per-frame cost: the engine must diff every
   computed property on every child. Narrowed here to the two properties the
   reveal actually animates. Scoped to the lightbox so /evento/{slug} keeps
   its current behaviour until this is proven there too. */
.ev-lb.ev-lb .ev-wrap > * {
  transition:
    opacity .5s var(--ease, cubic-bezier(.16, 1, .3, 1)),
    transform .5s var(--ease, cubic-bezier(.16, 1, .3, 1));
}
/* A section whose media has not decoded must not collapse to zero and snap
   back — that snap is the other half of the flicker. */
.ev-lb.ev-lb .ev-wrap > * { backface-visibility: hidden; }
.ev-lb.ev-lb img { background: var(--ev-soft, rgba(0, 0, 0, .04)); }

@media (prefers-reduced-motion: reduce) {
  .ev-lb.ev-lb .ev-lb-panel { transition: opacity .2s linear; transform: none; }
  .ev-lb.ev-lb.is-open .ev-lb-panel { transform: none; }
  .ev-lb.ev-lb .ev-lb-x { transition: none; }
}
</style>
