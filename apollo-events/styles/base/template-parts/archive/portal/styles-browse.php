<?php

/**
 * Portal de Eventos — styles: Browse
 *
 * Taxonomy/genre chips and the browse grid section headers.
 *
 * PHASE 002: split out of the single 979-line portal-styles.php. Values are
 * unchanged from the mockup's portal-eventos.css -- this is a split, not a
 * restyle. NEVER redeclare :root; core.js owns the tokens.
 *
 * 2026-08-09: tax chips carry `btn btn-secondary`. The button SURFACE (padding,
 * radius, type, card bg, inset shadow, hover lift) is owned by
 * styles-btn-force.php — not this cell. Here: scroll rail, label truncation,
 * and `.is-on` selected paint only. Do not reintroduce idle chip chrome.
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-pev-browse">
/* ── Fileira de taxonomia / gênero ───────────────────────────────────────────
   Os chips SÃO `.btn.btn-secondary` (app.php). A superfície do botão mora em
   styles-btn-force.php. Este bloco declara APENAS: scroller, truncamento e
   estado `.is-on`. NÃO redeclare padding/raio/tipo/fundo de idle chip. */
.pev-tax {
  display: flex;
  align-items: center;
  gap: 8px;
  overflow-x: auto;
  overflow-y: visible;
  scrollbar-width: none;
  -ms-overflow-style: none;
  /* O padding-block é folga de rolagem: `.btn-secondary:hover` levanta o chip
     1px e o :focus-visible do core desenha um anel para fora da caixa; sem essa
     folga o overflow-x cortaria os dois. As margens negativas devolvem os
     mesmos 16px, então a folga não ocupa espaço no fluxo: a fileira mede a
     altura dos chips.

     Uma linha só, de propósito. Até 2026-08-09 isto era `margin:-4px 0 -16px`
     mais um `.pev-browse .pev-tax{margin-top:-16px}` logo abaixo, que vencia
     por especificidade — o -4px nunca foi aplicado e o comentário descrevia um
     espaçamento que a página não tinha. */
  margin-block: -16px;
  padding-block: 16px;
  padding-inline: 4px;
  -webkit-overflow-scrolling: touch;
}
.pev-tax::-webkit-scrollbar { display: none; }

/* Delta 1 · a fileira é um scroller: um chip não pode encolher para caber. */
.pev-tax-btn { flex: 0 0 auto; }

/* Delta 2 · o rótulo trunca em vez de esticar a fileira. `.btn` já é
   white-space:nowrap e o span herda; `overflow`/`text-overflow` precisam estar
   no elemento que tem o texto, e é só por isso que este seletor existe. */
.pev-tax-btn .pev-tax-label {
  max-width: 22ch;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Delta 3 · selected chip: never accent. Smooth invert to black / white.
   Escrito sob `.pev-tax` de propósito: `.btn.btn-secondary:hover` do core
   pesa 0-3-0, então um `.pev-tax-btn.is-on` solto perderia o fundo no hover. */
.pev-tax .pev-tax-btn {
  transition:
    background .65s var(--ease, ease),
    color .65s var(--ease, ease),
    border-color .65s var(--ease, ease),
    box-shadow .65s var(--ease, ease),
    transform .25s var(--ease-snappy, ease);
}
.pev-tax .pev-tax-btn.is-on {
  background: var(--black, var(--black-1, #0a0a0a)) !important;
  color: var(--white, var(--white-1, #fff)) !important;
  border-color: var(--black, var(--black-1, #0a0a0a));
  box-shadow: none;
}
.pev-tax .pev-tax-btn.is-on:hover {
  background: var(--black, var(--black-1, #0a0a0a)) !important;
  color: var(--white, var(--white-1, #fff)) !important;
  border-color: var(--black, var(--black-1, #0a0a0a));
}
.pev-tax .pev-tax-btn.is-on .pev-tax-label {
  color: var(--white, var(--white-1, #fff)) !important;
}

/* ── Chip de busca ativa ─────────────────────────────────────────────────────
   Quando o usuário busca pelo cabeçalho (apollo_listing_header), o termo entra
   como o primeiro chip desta fileira, com um X. Ele é o mesmo botão dos chips
   de temporada/gênero de propósito — mas ganha um teto de largura maior,
   porque um termo digitado é mais longo que "Underground" e truncar em 22ch
   esconderia justamente o que o usuário precisa reconhecer. O `gap` entre
   rótulo e X é o de `.btn`. */
.pev-tax-btn[data-pev-search-clear] .pev-tax-label { max-width: 26ch; }
/* Só opacidade. O X é uma affordance secundária dentro do chip, não um segundo
   tamanho de tipo: `font-size`/`line-height` saíram daqui em 2026-08-09 porque
   o ícone herda a escala do botão e o core já normaliza `i[class*="ri-"]` — dois
   donos para o tamanho do glifo é como o chip volta a divergir de `.btn`. */
.pev-tax-btn[data-pev-search-clear] i { opacity: .7; }
.pev-tax-btn[data-pev-search-clear]:hover i { opacity: 1; }

.pev-browse {
  padding: 0 16px 8px;
  box-sizing: border-box;
}
.pev-dynamic {
  margin-top: 16px;
}
/* cabeçalho de seção — título + badge numérico + toggle grade/lista (inline). */
.pev .section-title {
  font-family: var(--ff-heading);
  color: var(--txt-heading);
  font-size: calc(17px * var(--fs-u, 1));
  margin: 25px 0 10px 0;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  letter-spacing: -.02em;
}
/* O ícone do título é uma marca d'água, não um rótulo: o traço praticamente
   some (alpha .15 sobre --rgb-diff) e o preenchimento fica no cinza de apoio,
   então quem carrega a hierarquia é o texto. `stroke-width: 0` é obrigatório —
   o runtime de ícones do core.js hidrata o <i> num <svg> que herda o traço e,
   sem isso, o contorno reapareceria por cima do fill apagado. */
.pev .section-title > i,
.pev .section-title > i svg {
  color: rgba(var(--rgb-diff), .15) !important;
  fill: var(--muted-txt) !important;
  font-weight: 100 !important;
  stroke-width: 0px !important;
}
.pev .section-title small.pev-section-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 1px 5px;
  border-radius: var(--r-pill, 999px);
  font-family: var(--ff-mono);
  font-size: calc(9.5px * var(--fs-u, 1));
  font-weight: 600;
  letter-spacing: .04em;
  line-height: 1.2;
  background: var(--white-3);
  color: var(--white-10);
  transition: font-size .35s var(--ease), color .35s var(--ease), background .35s var(--ease);
}
.pev-view-toggle {
  display: inline-flex;
  align-items: center;
  margin-left: auto;
}
.pev .section-title .pev-view-toggle > i,
.pev .section-title .pev-view-toggle > i svg {
  color: var(--muted-txt) !important;
  fill: var(--muted-txt) !important;
  cursor: pointer;
  font-size: 19px !important;
  width: 19px !important;
  height: 19px !important;
  transition: color .2s var(--ease);
}
.pev .section-title .pev-view-toggle > i:hover,
.pev .section-title .pev-view-toggle > i:hover svg {
  color: var(--accent) !important;
  fill: var(--accent) !important;
}
/* Custom Apollo icon fallbacks for view-toggle icons */
.ri-list-check-2::before {
  content: "";
  display: inline-block;
  width: 1em;
  height: 1em;
  background-color: currentColor;
  -webkit-mask-image: url('https://assets.apollo.rio.br/i/list-check-2.svg');
  mask-image: url('https://assets.apollo.rio.br/i/list-check-2.svg');
  -webkit-mask-size: contain;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
}
.ri-gallery-view::before {
  content: "";
  display: inline-block;
  width: 1em;
  height: 1em;
  background-color: currentColor;
  -webkit-mask-image: url('https://assets.apollo.rio.br/i/function-v.svg');
  mask-image: url('https://assets.apollo.rio.br/i/function-v.svg');
  -webkit-mask-size: contain;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
}

.pev .eve-body.is-list-view .pev-list-feed {
  display: flex;
  flex-direction: column;
  gap: 0;
}
@media (min-width: 1000px) {
  .pev .section-title { font-size: calc(22px * var(--fs-u, 1)); }
}
</style>
