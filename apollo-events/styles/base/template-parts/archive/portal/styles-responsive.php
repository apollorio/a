<?php

/**
 * Portal de Eventos — styles: Responsive
 *
 * Desktop >=1000px: hero + sidebar two-column layout and chrome adjustments.
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
<style id="apollo-pev-responsive">
/* Desktop ≥1000px — hero + sidebar; modes absolute 45vw */
@media (min-width: 1000px) {
  /* Nenhum override de cabeçalho aqui, e isso é uma regra, não uma omissão.
     O cabeçalho de /eventos é o bloco apollo_listing_header() (apollo-templates,
     skin 'apple'), que traz o próprio breakpoint. Antes de 2026-08-08 esta
     célula mexia em .pev-chrome/.pev-month-row enquanto styles-masthead.php
     mexia nos mesmos seletores — dois donos, cascata decidida por ordem de
     carga, e foi assim que o mês foi parar em cima do hero.

     `.pev-tax{margin-top:14px}` saiu daqui em 2026-08-09 pelo mesmo motivo: a
     fileira sempre nasce dentro de `.pev-browse`, e styles-browse.php declara
     `.pev-browse .pev-tax{margin-top:-16px}` (0-2-0). Media query não soma
     especificidade, então este seletor (0-1-0) nunca ganhou em nenhuma largura
     — era um segundo dono que só existia para ser ignorado. A margem da fileira
     tem um dono só, e ele é styles-browse.php. */
  .pev-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.62fr) minmax(272px, .78fr);
    align-items: stretch;
  }
  /* `.pev-recent .event-list-container` REMOVED — styles-hero.php owns the
     hero band and its sibling column at every breakpoint. Gutters and gaps come
     from the rhythm contract in styles-rhythm.php (.ax-main already supplies
     the page margins). */
}

/* ── WP host page offsets — MOVED, do not re-add here ────────────────────────
   This file used to end with .pev-host{padding-top:78px} + .pev-chrome{top:64px}
   under the note "this page has no SPA aside/topbar … sits directly under
   apollo_get_navbar()'s fixed top bar". Both statements went stale at PHASE
   002, when /eventos became a Blank Canvas Apollo+ mount point (it now has
   .ax-aside and the canonical fixed .ax-top).

   Worse, styles.php's own #apollo-pev-shell-fit block — loaded AFTER this
   file — declared the same two selectors with different values, so whatever
   was written here was dead CSS that silently lost the cascade. Two owners for
   one offset is how the 70px topbar compensation survived long past the day it
   was needed.

   Both values now live in exactly one place: the shell-fit block at the bottom
   of styles.php. Edit them there. */
</style>
