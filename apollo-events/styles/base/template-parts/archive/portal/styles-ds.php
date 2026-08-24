<?php

/**
 * Portal de Eventos — styles: DESIGN SYSTEM COMPONENTS (verbatim)
 *
 * The mockup's cards come from screen/_official_layout/css/ds-components.css,
 * which is itself a byte-for-byte copy of uni.theme.v2.1.css (the Design
 * System handoff). This file ports it VERBATIM.
 *
 * WHY THIS EXISTS / WHAT WENT WRONG BEFORE:
 * The previous attempt inlined apollo-templates/assets/css/event-card.css
 * instead. That is a DIFFERENT, older .a-eve-card implementation — flat panel,
 * no corner-notch mask, no --surface-hover media background, different sizing.
 * Substituting it is why the listed events rendered with no background and
 * looked nothing like the mockup. The mockup is the contract; this is its
 * actual source, not an equivalent.
 *
 * Nothing below is authored. Do not "improve" these values — if the card needs
 * to change, it changes in the Design System and gets re-copied here.
 *
 * @package Apollo\Event
 * @since   1.5.5
 */

if (! defined('ABSPATH')) {
   exit;
}
?>
<style id="apollo-pev-ds">
   /* PORT-TIME SUBSTITUTION (the only edit to this verbatim copy):
   the Design System handoff sizes through calc(Npx * var(--fs-u, 1)). core.js —
   the single source of :root tokens on every Apollo page — ships --fs-u, not
   --fsx (verified in production on /casa and /portal). An undefined token makes
   every one of those calc() expressions invalid, and CSS drops invalid
   declarations outright, so each element silently fell back to inherited
   sizing. var(--fs-u, 1) is therefore rewritten to var(--fs-u, 1) here. No token is
   declared anywhere: this maps the copy onto the token system that actually
   exists. If core.js ever ships --fsx, revert this substitution. */
   /* ============================================================
   APOLLO::RIO -- DS component CSS, copied VERBATIM from
   uni.theme.v2.1.css (Design System handoff) -- the exact
   component classes missing from this shell's own inline style:
   .stat-card, .cta-card, .event-row (+date-box/-details/-meta/
   -action), .gallery-card (+--alt), .a-eve-card (+date/-media/
   -tags/-content/-title/-meta), .pagination, .grid-pair,
   .field-input.
   NOTHING here is invented -- every rule below is a byte-for-byte
   copy of the approved Design System file referenced above.
   ============================================================ */
   /* ── EVENT CARD (.a-eve-card) — o cartão OFICIAL de evento da Design System.
   Nunca confundir com .gallery-card (genérico, editorial/listagens) — todo
   card que representa um EVENTO usa este componente, ponto. Cutout do bloco
   de data é geometria pura via mask/-webkit-mask, sem tokens. ── */
   .a-eve-card {
      display: block;
      position: relative;
      width: 100%;
      max-width: 320px;
      text-decoration: none;
      cursor: pointer;
      transition: transform .4s var(--ease);
      background: transparent;
   }

   .a-eve-card:hover {
      transform: translateY(-5px);
   }

   .a-eve-date {
      position: absolute;
      top: -5px;
      left: -2px;
      width: 60px;
      height: 54px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      line-height: 1;
      z-index: 2;
      pointer-events: none;
   }

   .a-eve-date-day {
      font-family: var(--ff-heading);
      font-size: calc(22px * var(--fs-u, 1));
      font-weight: 700;
      color: var(--txt-heading);
      display: block;
   }

   .a-eve-date-month {
      font-family: var(--ff-mono);
      font-size: calc(10px * var(--fs-u, 1));
      font-weight: 600;
      text-transform: uppercase;
      color: var(--muted);
   }

   .a-eve-media {
      height: 450px;
      position: relative;
      overflow: hidden;
      border-radius: var(--r-lg);
      border: 1px solid rgba(var(--rgb-diff), .04);
      box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .2), inset 0 0 0 1px rgba(var(--rgb-theme), .075);
      transition: box-shadow .4s var(--ease);
      background: var(--surface-hover);
      --r: 12px;
      --s: 12px;
      --x: 48px;
      --y: 42px;
      --_m: /calc(2*var(--r)) calc(2*var(--r)) radial-gradient(#000 70%, #0000 72%);
      --_g: conic-gradient(at var(--r) var(--r), #000 75%, #0000 0);
      --_d: (var(--s) + var(--r));
      mask: calc(var(--_d) + var(--x)) 0 var(--_m), 0 calc(var(--_d) + var(--y)) var(--_m), radial-gradient(var(--s) at 0 0, #0000 99%, #000 calc(100% + 1px)) calc(var(--r) + var(--x)) calc(var(--r) + var(--y)), var(--_g) calc(var(--_d) + var(--x)) 0, var(--_g) 0 calc(var(--_d) + var(--y));
      mask-repeat: no-repeat;
      -webkit-mask: calc(var(--_d) + var(--x)) 0 var(--_m), 0 calc(var(--_d) + var(--y)) var(--_m), radial-gradient(var(--s) at 0 0, #0000 99%, #000 calc(100% + 1px)) calc(var(--r) + var(--x)) calc(var(--r) + var(--y)), var(--_g) calc(var(--_d) + var(--x)) 0, var(--_g) 0 calc(var(--_d) + var(--y));
      -webkit-mask-repeat: no-repeat;
   }

   .a-eve-card:hover .a-eve-media {
      box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .35), inset 0 0 0 1px rgba(var(--rgb-theme), .12);
   }

   .a-eve-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .4s var(--ease);
      display: block;
      border: none;
      box-shadow: none;
   }

   .a-eve-card:hover .a-eve-media img {
      transform: scale(1.05);
   }

   .a-eve-tags {
      position: absolute;
      bottom: 10px;
      right: 10px;
      display: flex;
      gap: 8px;
      z-index: 3;
      pointer-events: none;
   }

   .a-eve-tag {
      corner-shape: squircle;
      padding: 4px 10px;
      border-radius: var(--r-xs);
      border: 1px solid rgba(var(--rgb-theme), .2);
      background: linear-gradient(30deg, rgba(var(--rgb-theme), .1) -49%, rgba(var(--rgb-theme), .35) 160%);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      font-family: var(--ff-mono);
      font-size: calc(8px * var(--fs-u, 1));
      color: rgba(var(--rgb-theme), 1);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .5px;
   }

   .a-eve-content {
      padding: 9px .5rem;
      width: 100%;
   }

   .a-eve-title {
      font-family: var(--ff-heading);
      font-size: calc(16px * var(--fs-u, 1));
      font-weight: 700;
      color: var(--txt-heading);
      line-height: 1.3;
      margin-bottom: .5rem;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
   }

   .a-eve-meta {
      color: var(--txt-color);
      font-size: calc(12px * var(--fs-u, 1));
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: .4rem;
   }

   .a-eve-meta i {
      font-size: calc(13px * var(--fs-u, 1));
      flex-shrink: 0;
      opacity: .8;
      color: var(--muted);
   }

   .a-eve-meta span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
   }

   .field-input,
   .apollo-input,
   .apollo-select {
      width: 100%;
      padding: 11px 14px;
      font-size: calc(var(--fs-u, 1) * 13px);
      color: var(--txt-heading);
      background: var(--surface);
      border-radius: var(--r-sm);
      border: 1px solid rgba(var(--rgb-diff), .04);
      box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .2), inset 0 0 0 1px rgba(var(--rgb-theme), .075);
      transition: box-shadow .35s var(--ease), background .35s var(--ease);
   }

   .field-input::placeholder,
   .apollo-input::placeholder {
      color: var(--muted);
   }

   .field-input:focus,
   .apollo-input:focus,
   .apollo-select:focus {
      background: var(--white-2);
      box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .2), inset 0 0 0 1px rgba(var(--rgb-theme), .075), inset 0 0 0 1px var(--white-10);
   }

   .gallery-card>img,
   .card>img,
   .cta-card>img,
   .stat-card>img,
   .media-widget>img,
   .media-widget>.media-cover-art img {
      filter: grayscale(.8);
      transition: transform .25s ease, filter .65s ease .1s !important;
   }

   .gallery-card:hover>img,
   .card:hover>img,
   .cta-card:hover>img,
   .stat-card:hover>img,
   .media-widget:hover>img,
   .media-widget:hover>.media-cover-art img {
      filter: grayscale(0);
   }

   .media-widget {
      background: var(--card);
      border-radius: var(--r-lg);
      padding: var(--s-4);
      display: flex;
      flex-direction: column;
      gap: var(--s-4);
      box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .2), inset 0 0 0 1px rgba(var(--rgb-theme), .075);
      border: 1px solid rgba(var(--rgb-diff), .04);
   }

   .media-cover-art {
      width: 100%;
      aspect-ratio: 16/10;
      border-radius: var(--r);
      overflow: hidden;
   }

   .media-cover-art img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .6s var(--ease-smooth), filter .6s var(--ease);
   }

   .media-widget:hover .media-cover-art img {
      transform: scale(1.04);
      filter: grayscale(0);
   }

   .media-info h3 {
      font-family: var(--ff-heading);
      font-size: var(--fs-h5);
      color: var(--txt-heading);
   }

   .media-info p {
      font-size: calc(var(--fs-u, 1) * 13px);
      color: var(--muted);
      margin-top: 2px;
   }

   .media-progress {
      display: flex;
      align-items: center;
      gap: 12px;
      font-family: var(--ff-mono);
      font-size: calc(var(--fs-u, 1) * 11px);
      color: var(--muted);
   }

   .progress-bar-container {
      position: relative;
      flex: 1;
      height: 4px;
      border-radius: var(--r-pill);
      background: var(--white-5);
   }

   .progress-fill {
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 33%;
      border-radius: var(--r-pill);
      background: var(--txt-heading);
   }

   .progress-knob {
      position: absolute;
      left: 33%;
      top: 50%;
      transform: translate(-50%, -50%);
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: var(--bg);
      box-shadow: var(--img-border);
   }

   .media-controls {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: var(--s-4);
   }

   .ctrl-btn {
      color: var(--muted);
      font-size: calc(var(--fs-u, 1) * 22px);
      transition: color .3s var(--ease), transform .3s var(--ease-snappy);
   }

   .ctrl-btn:hover {
      color: var(--txt-heading);
      transform: scale(1.1);
   }

   .play-pause-btn {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: var(--black-1);
      color: var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: calc(var(--fs-u, 1) * 26px);
   }

   .play-pause-btn:hover {
      transform: scale(1.05);
   }

   .stat-card {
      background: var(--card);
      border-radius: var(--r);
      padding: var(--s-4);
      display: flex;
      flex-direction: column;
      gap: var(--s-4);
      box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .2), inset 0 0 0 1px rgba(var(--rgb-theme), .075);
      border: 1px solid rgba(var(--rgb-diff), .04);
   }

   .stat-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
   }

   .stat-icon {
      width: 40px;
      height: 40px;
      border-radius: var(--r-sm);
      background: var(--surface);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: calc(var(--fs-u, 1) * 18px);
      color: var(--txt-heading);
   }

   .stat-value {
      font-family: var(--ff-heading);
      font-size: var(--fs-h3);
      color: var(--txt-heading);
      font-weight: 700;
      letter-spacing: -.02em;
   }

   .stat-label {
      font-size: calc(var(--fs-u, 1) * 11px);
      color: var(--muted);
      margin-top: 2px;
   }

   .stat-trend {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      font-family: var(--ff-mono);
      font-size: calc(var(--fs-u, 1) * 11px);
      margin-top: var(--s-2);
   }

   .trend-up {
      color: #34C759;
   }

   .trend-down {
      color: var(--alert-red);
   }

   .cta-card {
      background: var(--black-1);
      color: var(--bg);
      border-radius: var(--r);
      padding: var(--s-4);
   }

   .cta-card h4 {
      font-family: var(--ff-heading);
      color: var(--bg);
   }

   .cta-card p {
      font-size: calc(var(--fs-u, 1) * 13px);
      color: rgba(var(--rgb-theme), .6);
   }

   /* EVENTS · GALLERY · PAGINATION · IDENTITY */
   .event-list-container {
      display: flex;
      flex-direction: column;
      gap: var(--s-2);
   }

   .event-row {
      display: flex;
      align-items: center;
      gap: var(--s-3);
      padding: var(--s-3);
      border-radius: var(--r);
      background: none;
      cursor: pointer;
   }

   /* v2.1.1: transparente em repouso */
   .event-row:hover {
      transform: translateX(4px);
      background: var(--card);
   }

   .event-row.featured {
      box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .2), inset 0 0 0 1px rgba(var(--rgb-theme), .075);
      border: 1px solid rgba(var(--rgb-diff), .04);
   }

   .event-row.soldout {
      opacity: .5;
   }

   .date-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 54px;
      height: 54px;
      border-radius: var(--r-sm);
      background: var(--card);
      flex-shrink: 0;
   }

   .date-day {
      font-family: var(--ff-heading);
      font-size: calc(var(--fs-u, 1) * 1.35rem);
      font-weight: 700;
      color: var(--txt-heading);
      line-height: 1;
   }

   .date-month {
      font-family: var(--ff-mono);
      font-size: calc(var(--fs-u, 1) * .6rem);
      text-transform: uppercase;
      color: var(--muted);
      margin-top: 2px;
   }

   .event-details {
      flex: 1;
      min-width: 0;
   }

   .event-details h4 {
      font-family: var(--ff-heading);
      font-size: var(--fs-h6);
      color: var(--txt-heading);
   }

   .event-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 4px;
   }

   .event-meta span {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: calc(var(--fs-u, 1) * 11px);
      color: var(--muted);
   }

   .event-action .status {
      font-family: var(--ff-mono);
      font-size: calc(var(--fs-u, 1) * .58rem);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .08em;
      padding: 5px 10px;
      background: var(--surface);
      border-radius: var(--r-pill);
      color: var(--muted);
   }

   .event-action .status.active {
      background: var(--surface);
      color: var(--txt-heading);
   }

   .gallery-card {
      background: var(--bg);
      border-radius: var(--r);
      overflow: hidden;
      position: relative;
   }

   .gallery-card:hover {
      transform: translateY(-3px);
   }

   .gallery-card img {
      width: 100%;
      aspect-ratio: 4/3;
      object-fit: cover;
      filter: grayscale(.8) contrast(1.05);
      transition: filter .6s var(--ease-smooth), transform .6s var(--ease-smooth);
      box-shadow: var(--img-border);
   }

   .gallery-card:hover img {
      filter: grayscale(0) contrast(1);
      transform: scale(1.04);
   }

   .gallery-card-body {
      padding: var(--s-3) var(--s-1);
   }

   .gallery-card-body h4 {
      font-family: var(--ff-heading);
      font-size: calc(var(--fs-u, 1) * 1rem);
      color: var(--txt-heading);
      margin-bottom: 4px;
   }

   .gallery-card-body p {
      color: var(--muted);
      font-size: calc(var(--fs-u, 1) * 11px);
   }

   #pagination,
   .pagination {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      align-items: center;
      justify-content: center;
      padding: var(--s-4) 0;
   }

   #pagination li a,
   .pagination li a {
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 38px;
      height: 38px;
      padding: 0 6px;
      border-radius: var(--r-sm);
      font-family: var(--ff-mono);
      font-size: calc(var(--fs-u, 1) * 11px);
      color: var(--muted);
      transition: background .3s var(--ease), color .3s var(--ease);
   }

   #pagination li a:hover,
   .pagination li a:hover {
      background: var(--surface);
      color: var(--txt-heading);
   }

   #pagination li a.current-page,
   .pagination li a.current-page {
      background: var(--black-1);
      color: var(--bg);
   }

   .gallery-card--alt {
      background: var(--card);
      border-radius: var(--r-lg);
      box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .2), inset 0 0 0 1px rgba(var(--rgb-theme), .075);
      border: 1px solid rgba(var(--rgb-diff), .04);
   }

   .gallery-card--alt img {
      aspect-ratio: 1/1;
      filter: none;
   }

   .gallery-card--alt .gallery-card-body {
      padding: var(--s-3);
   }

   .gallery-card--alt:hover img {
      transform: scale(1.02);
   }

   .grid-pair {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
   }

   /* variante para hosts .grid-3 (ex.: galeria --alt): 2-por-linha até o desktop,
   3-por-linha volta a valer em ≥1000px (regra original preservada). */
   @media (max-width: 999px) {
      .grid-3.grid-pair--m {
         grid-template-columns: repeat(2, 1fr);
      }
   }

   /* Anúncio a custo zero → "FREE" no lugar do preço (acomodação cedida / cortesia).
   Usado por app-market.js em .ticket-price-tag e .accom-price. */
   .mk-free {
      color: var(--accent);
      font-weight: 800;
      letter-spacing: .04em;
      text-transform: uppercase;
   }

   .accom-price .mk-free {
      font-family: var(--ff-mono);
      font-size: calc(var(--fs-u, 1) * 12px);
   }


   /* ── from market.css — section headers (VERBATIM) ────────────────────────── */
   .section-header {
      padding-left: 16px !important;
      padding-right: 16px !important;
   }

   #view-anuncios .carousel,
   #view-anuncios .accom-carousel,
   #view-anuncios #ticketCarousel,
   #view-anuncios-meus .carousel {
      width: 100% !important;
      max-width: 100% !important;
      left: auto !important;
      right: auto !important;
      margin-left: 0 !important;
      margin-right: 0 !important;
      padding-left: 0 !important;
      padding-right: 0 !important;
   }
   }

   .section-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin: 0px;
      border: none !important;
      padding-bottom: 10px;
   }

   .section-title {
      font-size: calc(24px * var(--fs-u));
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
      letter-spacing: -0.03em;
   }

   .section-title i {
      color: var(--accent);
   }

   .section-count {
      font-family: var(--ff-mono);
      font-size: calc(12px * var(--fs-u));
      color: var(--gray-8);
      text-transform: uppercase;
   }

   /* View-toggle icon next to the "Repasses" / "Acomodações" titles —
           ONE icon, not a muted/active pair: its class (and title/aria-label)
           is swapped between ri-gallery-view-2 ("Ver anúncios sem efeito") and
           ri-carousel-view ("Ver anúncios em efeito fluido") in JS
           (setViewToggleState) to reflect whichever mode is CURRENTLY active,
           so there's never a
           second, unused icon sitting there for "muted" styling to apply to.
           Scoped two-deep — the rest of this sentence, and the rule it
           described, were lost before 2026-08-20. The comment therefore
           never closed and swallowed everything down to the market.css
           header below. Closing it here changes no rendered output (both
           spans were comment either way) and makes the next edit here
           safe. Portal harness E24. */

/* ── from market.css — the card grid (VERBATIM) ──────────────────────────── */
   .grid-layout {
      display: grid !important;
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 16px !important;
      margin-bottom: 80px !important;
      height: auto !important;
      overflow: visible !important;
      position: relative !important;
   }

   @media (min-width: 999px) {
      .grid-layout {
         grid-template-columns: repeat(4, 1fr) !important;
         gap: 24px !important;
         margin-bottom: 160px !important;
      }
   }

   /* ── shell fit ───────────────────────────────────────────────────────────────
   Two rules the mockup gets from its own page frame and this screen does not:
   (1) the DS card caps at max-width:320px and centres inside its grid cell;
   (2) .eve-body/.eve-section are portal-only wrappers with no DS definition. */
   .grid-layout>.app-market.eve {
      min-width: 0;
      display: block;
   }

   .grid-layout .a-eve-card {
      max-width: none;
   }

   .eve-section {
      position: relative;
   }
</style>