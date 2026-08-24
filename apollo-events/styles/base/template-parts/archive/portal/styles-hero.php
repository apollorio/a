<?php

/**
 * Portal de Eventos — styles: Hero
 *
 * Highlighted-events hero slider: stacked cross-fading slides, Ken Burns drift, and the progress-bar dots.
 *
 * PHASE 002: split out of the single 979-line portal-styles.php. Values are
 * unchanged from the mockup's portal-eventos.css -- this is a split, not a
 * restyle. NEVER redeclare :root; core.js owns the tokens.
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-pev-hero">
/* ── Layout: mobile column → desktop hero+sidebar ──────────────────────────
   Gaps and gutters come from the `--pev-*` rhythm contract in
   styles-rhythm.php (era styles-masthead.php até 1.7.0) — do not hard-code
   them here. ── */
.pev-layout {
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
}

.pev-hero {
  position: relative;
  border-radius: var(--r-lg);
  overflow: hidden;
  min-height: clamp(232px, 54vw, 292px);
  background: var(--surface);
  border: 1px solid rgba(var(--rgb-diff), .04);
  box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .9), inset 0 0 0 1px rgba(var(--rgb-theme), .4);
  cursor: pointer;
  isolation: isolate;
}
.pev-hero-cover {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: grayscale(.35);
  transition: filter .5s var(--ease), transform .6s var(--ease-smooth);
}
.pev-hero:hover .pev-hero-cover { filter: grayscale(0); transform: scale(1.03); }

/* ── FALLBACK · última camada quando não há destaque ────────────────────────
   Ordem de empilhamento explícita. Antes, o <iframe> e o véu compartilhavam
   z-index:0 e dependiam da ordem do DOM; qualquer reordenação silenciosa do
   markup escondia a mensagem atrás do próprio véu. Agora:
       0 · fundo degradê (sobrevive se o iframe não carregar)
       1 · iframe do CDN
       2 · véu
       3 · corpo da mensagem
   O fundo é a REDE DE SEGURANÇA final: se assets.apollo.rio.br estiver fora do
   ar, o cartão continua legível em vez de virar um retângulo vazio. ── */
.pev-hero.is-fallback {
  cursor: default;
  background:
    radial-gradient(120% 90% at 12% 0%, rgba(255, 255, 255, .05) 0%, transparent 62%),
    linear-gradient(168deg, #1b1b1b 0%, #131313 46%, #0d0d0d 100%);
}
.pev-hero-fallback {
  position: absolute;
  inset: 0;
  z-index: 1;
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
  pointer-events: none;
  color-scheme: dark;
  /* Starts invisible, fades in once 'load' fires (app.php's onload handler).
     This cannot detect an X-Frame-Options block — see the KNOWN LIMITATION
     note in app.php's heroFallbackHTML() — it only removes the flash of blank
     white on first paint while the frame is still resolving. */
  opacity: 0;
  transition: opacity .5s var(--ease);
}
.pev-hero-fallback.is-ready { opacity: 1; }
.pev-hero-fallback-shade {
  position: absolute;
  inset: 0;
  z-index: 2;
  /* Top stop is NOT fully transparent (2026-08-01): if the iframe is blocked
     by the CDN's own X-Frame-Options header (see app.php), the browser paints
     it plain white top-to-bottom. A shade that only darkens the bottom 80%
     left that white showing through the top — this floor keeps the whole card
     toned even when layer 1 never actually renders anything. */
  background:
    linear-gradient(rgba(var(--rgb-diff), .16) 0%, rgba(var(--rgb-diff), .70) 100%),
    radial-gradient(120% 80% at 50% 120%, rgba(0, 0, 0, .34) 0%, transparent 70%);
  pointer-events: none;
}
.pev-hero.is-fallback .pev-hero-body { z-index: 3; }

/* ── HERO SLIDER · destaques (highlight:true) ───────────────────────────────
   Slides empilhados no mesmo box: só o .is-on aparece, com cross-fade + um
   leve zoom-out de entrada (Ken Burns curto) que dá vida sem chamar atenção
   pra si. O clique/foco continua indo pro evento do slide ativo. ── */
.pev-hero.is-slider { display: grid; }
.pev-hero-slide {
  grid-area: 1 / 1;
  position: relative;
  min-height: inherit;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity .7s var(--ease), visibility .7s var(--ease);
}
.pev-hero-slide.is-on {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}
.pev-hero-slide.is-on .pev-hero-cover { animation: pevHeroDrift 7s var(--ease-smooth) both; }
@keyframes pevHeroDrift {
  from { transform: scale(1.07); }
  to   { transform: scale(1); }
}
.pev-hero-kicker {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  align-self: flex-start;
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 8.5px);
  text-transform: uppercase;
  letter-spacing: .16em;
  color: rgba(var(--rgb-theme), .78);
  margin-bottom: 2px;
}
.pev-hero-kicker i { color: var(--accent); font-size: 1.15em; }

/* Bolinhas = barras de progresso. O trilho é a bolinha; o ::after/​.fill enche
   no ritmo exato do --pev-hero-dwell. Slide já visto fica cheio (is-done). */
.pev-hero-dots {
  position: absolute;
  left: 18px;
  right: 18px;
  bottom: 12px;
  z-index: 3;
  display: flex;
  align-items: center;
  gap: 6px;
}
.pev-hero-dot {
  position: relative;
  flex: 1 1 0;
  height: 3px;
  max-width: 46px;
  padding: 0;
  border: 0;
  border-radius: var(--r-pill);
  background: rgba(var(--rgb-theme), .28);
  overflow: hidden;
  cursor: pointer;
  transition: background .3s var(--ease), max-width .45s var(--ease);
}
.pev-hero-dot::before {
  /* alvo de toque de 24px sem engordar o traço visível */
  content: "";
  position: absolute;
  inset: -11px -3px;
}
.pev-hero-dot:hover { background: rgba(var(--rgb-theme), .45); }
.pev-hero-dot.is-on { max-width: 76px; }
.pev-hero-dot-fill {
  display: block;
  height: 100%;
  width: 100%;
  border-radius: inherit;
  background: var(--white-1, #fff);
  transform-origin: left center;
  transform: scaleX(0);
}
.pev-hero-dot.is-done .pev-hero-dot-fill { transform: scaleX(1); }
.pev-hero-dot.is-on .pev-hero-dot-fill {
  animation: pevHeroFill var(--pev-hero-dwell, 6200ms) linear both;
}
.pev-hero.is-paused .pev-hero-dot.is-on .pev-hero-dot-fill { animation-play-state: paused; }
@keyframes pevHeroFill {
  from { transform: scaleX(0); }
  to   { transform: scaleX(1); }
}
.pev-hero-dot:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 70%, transparent);
}
/* com slider, o corpo abre espaço pras barras não encostarem no texto */
.pev-hero.is-slider .pev-hero-body { padding-bottom: 30px; }
@media (prefers-reduced-motion: reduce) {
  .pev-hero-slide.is-on .pev-hero-cover { animation: none; }
  .pev-hero-dot.is-on .pev-hero-dot-fill { animation: none; transform: scaleX(1); }
}
.pev-hero-fade {
  position: absolute;
  inset: 0;
  background: linear-gradient(transparent 20%, rgba(var(--rgb-diff), .72) 100%);
  pointer-events: none;
}
.pev-hero-body {
  position: relative;
  z-index: 1;
  padding: 24px 20px 20px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  min-height: inherit;
  height: 100%;
  gap: 8px;
  box-sizing: border-box;
}
.pev-hero-body h1 {
  font-family: var(--ff-heading);
  font-size: calc(var(--fs-r, 1) * clamp(1.35rem, 5vw, 2rem));
  font-weight: 800;
  color: var(--white-1);
  letter-spacing: -.03em;
  line-height: 1.05;
  margin: 0;
  text-shadow: 0 2px 16px rgba(0, 0, 0, .45);
}
.pev-hero-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  font-size: calc(var(--fs-r, 1) * 12px);
  color: rgba(var(--rgb-theme), .85);
}
.pev-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: var(--r-pill);
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 9px);
  text-transform: uppercase;
  letter-spacing: .06em;
  background: rgba(var(--rgb-theme), .14);
  color: var(--white-1);
  backdrop-filter: blur(8px);
}
.pev-chip.is-hot { background: rgba(var(--rgb-accent), .85); color: var(--bg); }
.pev-chip.is-gone { background: rgba(var(--rgb-diff), .55); }

/* ── Coluna irmã do hero · "Últimos eventos registrados" ────────────────────
   SINGLE OWNER (2026-08-01). This box used to be declared by three cells at
   once — hero (flex column), rails (overflow guard) and responsive (scroll
   height). Consolidated here, with the hero, because it is the hero band's
   sibling column and its height is a function of the hero's. ── */
.pev-recent {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  overflow-x: hidden !important;
}
.pev-recent .event-list-container {
  overflow-x: hidden !important;
  max-width: 100%;
}
.pev-recent-title {
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 10px);
  text-transform: uppercase;
  letter-spacing: .12em;
  color: var(--muted);
  margin: 20px 0 4px;
}
.pev-recent .event-list-container {
  max-height: none;
}
.pev-recent .event-action {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

/* ── Empty hero ──────────────────────────────────────────────────────────────
   BUGFIX (mantido): .pev-hero-body's type is designed to sit OVER a cover image
   behind the .pev-hero-fade scrim, so it uses var(--white-1) plus a dark
   text-shadow. The empty state has no photograph — but it DOES sit on the dark
   fallback canvas, so the over-image treatment is correct here; what was wrong
   was inheriting it onto the light --surface panel. The `.is-fallback` canvas
   above guarantees the dark ground, so the white type is now always legible.

   Tone: this is a normal, honest state — no event is marked highlight:true for
   this month — not an error. It reads as a quiet editorial note, not an alert. */
.pev-hero-body.is-empty {
  justify-content: flex-end;
  align-items: flex-start;
  text-align: left;
  gap: 10px;
}
.pev-hero-body.is-empty .pev-hero-kicker {
  color: rgba(var(--rgb-theme), .55);
  letter-spacing: .18em;
  margin-bottom: 0;
}
.pev-hero-body.is-empty .pev-hero-kicker i { color: rgba(var(--rgb-theme), .42); }
.pev-hero-body.is-empty h1 {
  color: var(--white-1);
  font-size: calc(var(--fs-r, 1) * clamp(1.2rem, 4.4vw, 1.7rem));
  letter-spacing: -.028em;
  opacity: .92;
  text-shadow: 0 2px 16px rgba(0, 0, 0, .45);
}
.pev-hero-body.is-empty .pev-chip {
  background: rgba(var(--rgb-theme), .10);
  color: rgba(var(--rgb-theme), .82);
  box-shadow: inset 0 0 0 1px rgba(var(--rgb-theme), .14);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

/* ── Coluna "Últimos eventos registrados" ───────────────────────────────────
   No desktop ela é irmã do hero numa grade `align-items:stretch`, então precisa
   ser uma coluna de altura total com a lista rolando por dentro — senão a linha
   inteira cresce junto com a lista e o hero estica sem motivo. ── */
@media (min-width: 1000px) {
  .pev-hero { min-height: clamp(300px, 26vw, 348px); }
  .pev-hero-body { padding: 32px; }
  .pev-recent {
    min-height: 0;
    padding: 4px 2px 4px 0;
  }
  .pev-recent .event-list-container { min-height: 0; flex: 1 1 auto; }
}
</style>
