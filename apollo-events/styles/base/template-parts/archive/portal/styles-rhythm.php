<?php

/**
 * Portal de Eventos — styles: vertical rhythm contract.
 *
 * ANTECESSOR: styles-masthead.php (1.5.4 → 1.7.0).
 * ─────────────────────────────────────────────────────────────────────────────
 * Aquele arquivo era dono de TRÊS coisas: o contrato de ritmo `--pev-*`, o
 * trilho `.pev-masthead` e a pílula de vidro do scroll dock. Duas delas
 * morreram junto com o cabeçalho antigo: /eventos agora usa o bloco
 * apollo_listing_header() ("Header 02 · Kinetic Mask · Apple"), renderizado em
 * PHP fora de #apollo-portal-root, e não existe mais nenhum `.pev-masthead`,
 * `.pev-month-*` ou `.pev-filter` no DOM desta tela.
 *
 * O que sobrou — e é o motivo do arquivo continuar existindo — é a PRIMEIRA
 * das três: a escala única que as outras células do portal consomem em vez de
 * cada uma cravar 12/16/25/35px. Isso nunca teve nada a ver com o cabeçalho;
 * só morava no mesmo arquivo. Renomeado para dizer o que faz.
 *
 * REGRA QUE CONTINUA VALENDO: este arquivo é carregado POR ÚLTIMO pelo loop de
 * styles.php e é o ÚNICO dono dos gutters/bandas das faixas abaixo. Não
 * redeclare `.pev-layout`, `.pev-rails` ou `.pev-browse` em nenhuma outra
 * célula — foi exatamente essa disputa de dois donos que um dia imprimiu
 * "Agosto" em cima do hero.
 *
 * NUNCA redeclarar :root; core.js é dono dos tokens. As `--pev-*` abaixo são
 * de escopo de COMPONENTE (`.pev`), o mesmo padrão do `--pev-hero-dwell`.
 *
 * @package Apollo\Event
 * @since   1.7.1
 * @see     styles.php   loader; precisa exigir esta célula por último
 * @see     header.php   o cabeçalho que substituiu a masthead
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-pev-rhythm">
/* ═══════════════════════════════════════════════════════════════════════════
   1 · CONTRATO DE RITMO
   Uma escala para a tela inteira. Bandas são as batidas grandes (hero → rails
   → browse); stacks são as batidas pequenas dentro de uma banda.
   ═══════════════════════════════════════════════════════════════════════════ */
.pev {
  --pev-gutter: clamp(16px, 4vw, 20px);
  --pev-band:   clamp(26px, 5vw, 40px);
  --pev-stack:  clamp(12px, 2.4vw, 18px);
  --pev-hairline: rgba(var(--rgb-diff), .06);
}

/* ═══════════════════════════════════════════════════════════════════════════
   2 · RITMO APLICADO — as faixas abaixo do cabeçalho
   ═══════════════════════════════════════════════════════════════════════════ */
.pev-layout  { gap: var(--pev-stack); padding-left: var(--pev-gutter); padding-right: var(--pev-gutter); }
.pev-rails   { gap: var(--pev-band); padding: 0 var(--pev-gutter) var(--pev-band); margin: var(--pev-band) 0 0; }
.pev-browse  { padding: 0 var(--pev-gutter) var(--pev-stack); }
.pev-dynamic { margin-top: var(--pev-stack); }
.pev .section-title { margin: var(--pev-band) 0 var(--pev-stack); }
.pev-rails .pev-rail:first-child .section-title { margin-top: 0; }

/* O cabeçalho já tem o próprio respiro inferior (padding 22/18/16 da skin
   apple), então a primeira faixa não repete a distância. */
.pev > .pev-layout:first-child { margin-top: 0; }

/* ═══════════════════════════════════════════════════════════════════════════
   3 · DESKTOP ≥1000px — o aside está fixado e .ax-main é dono dos gutters
   ═══════════════════════════════════════════════════════════════════════════ */
@media (min-width: 1000px) {
  /* .ax-main já fornece os gutters horizontais neste breakpoint; o portal
     somar os dele dobraria a distância. */
  .pev-layout,
  .pev-browse,
  .pev-rails { padding-left: 0; padding-right: 0; }
  .pev-rails { padding-bottom: var(--pev-band); }
}
</style>
