<?php

/**
 * Portal de Eventos — styles: Rails
 *
 * Compact .event-row listings, the day-window rails, the 'Ver todos' pill and the event-card grid rules.
 *
 * PHASE 002: split out of the single 979-line portal-styles.php. Values are
 * unchanged from the mockup's portal-eventos.css -- this is a split, not a
 * restyle. NEVER redeclare :root; core.js owns the tokens.
 *
 * OWNS (added with the month-aware rails): `.pev .pev-rail[hidden]`,
 * `.pev .pev-rail.is-exiting` and `.pev [data-rail-title]` -- the enter/exit
 * state of the default rail pair and the target of its title mask. app.php
 * DRIVES them; nothing else may DECLARE them. Adding a second owner is how
 * `.pev-chrome` once printed the month title over the hero.
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-pev-rails">
/* Compact event rows — recent, prox rail, lightbox */
/* Hover translateX must never spawn a horizontal scrollbar — clip the parent.
   `.pev-recent` and `.pev-recent .event-list-container` were ALSO listed here;
   they moved to styles-hero.php, which owns the whole hero band including its
   sibling column. `.pev .event-list-container` below already covers the recent
   list, since .pev-recent lives inside .pev — the extra selector was redundant
   AND it made three cells co-own one box. */
.pev .event-list-container,
.pev .pev-rail-rows {
  overflow-x: hidden !important;
  max-width: 100%;
}
.pev .event-row {
  gap: 8px !important;
  padding: 6px 4px !important;
  align-items: center;
  max-width: 100%;
  overflow: hidden;
  box-sizing: border-box;
}
.pev .event-row:hover { transform: translateX(2px); }
.pev .date-box {
  width: 42px !important;
  height: 42px !important;
  min-width: 42px;
}
.pev .date-box .date-day {
  font-size: calc(15px * var(--fs-u, 1));
  line-height: 1;
}
.pev .date-box .date-month {
  font-size: calc(9px * var(--fs-u, 1));
  line-height: 1;
  margin-top: 1px;
}
.pev .event-details {
  min-width: 0;
  flex: 1;
}
.pev .event-details h4 {
  font-size: calc(13px * var(--fs-u, 1));
  line-height: 1.15;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pev .event-meta {
  display: flex;
  flex-wrap: nowrap !important;
  gap: 6px !important;
  margin-top: 1px !important;
  overflow: hidden;
  min-width: 0;
}
.pev .event-meta span {
  gap: 3px !important;
  font-size: calc(10.5px * var(--fs-u, 1));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}
.pev .event-meta span:last-child { min-width: 0; flex: 1; }
.pev .event-action { margin-left: 4px; }
.pev-rail-rows {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* ── Dynamic + legacy rails (no arrow nav) ── */
.pev-rails {
  display: flex;
  flex-direction: column;
  gap: 22px;
  padding: 8px 16px 40px;
  margin: 35px 0 0;
  box-sizing: border-box;
}
/* v3: a grade não sangra pra fora como o trilho fazia — o padding lateral
   fica no container e vale pra cabeçalho e cartões de uma vez só. */

.pev-rail { position: relative; }

/* ── TROCA DE MÊS — o par padrão entra e sai ───────────────────────────────
   Dono único destas três regras: este cell. `renderRails()` em app.php as
   dirige, mas quem as declara é aqui — 'neste-fds' e 'prox-fds' são trilhos, e
   trilho é assunto de styles-rails.php.

   `[hidden]` é o estado de REPOUSO, não a animação. O `.pev-rails` é uma coluna
   flex com `gap: var(--pev-band)`: uma seção de altura 0 ainda cobra a banda
   inteira de espaçamento, então animar só a altura deixaria exatamente o buraco
   que se queria evitar. `display: none` é o que tira o item do flex — e com ele
   o gap. A altura anima antes; isto só encerra.

   `.is-exiting` existe pela mesma razão: a altura só clipa o conteúdo se a
   caixa clipar, e `.pev-rail` é `position: relative` sem overflow. Vale nos
   dois sentidos — sair e voltar usam a mesma clipagem. */
.pev .pev-rail[hidden] { display: none; }
.pev .pev-rail.is-exiting {
  overflow: hidden;
  will-change: height, opacity;
}

/* Alvo da máscara de troca de título (morphRailTitle, mesma receita do mês no
   cabeçalho). `inline-block` é obrigatório: `clip-path` num box inline recorta
   contra as caixas de linha, não contra o texto, e o rótulo pisca em vez de
   varrer. O <span> é filho de um flex container, então não há reflow. */
.pev [data-rail-title] {
  display: inline-block;
  will-change: clip-path;
}

/* ── "Ver todos" — a ÚLTIMA LINHA da lista, não uma laje embaixo dela ───────
   O rodapé é filho da .event-list-container (app.php::skeleton), que é uma
   coluna flex: `align-self: flex-end` faz a caixa encolher até o tamanho do
   botão em vez de ocupar a largura toda, e o padding lateral repete o das
   .event-row pra ação nascer alinhada com o trilho de conteúdo. Sem margem
   de topo: quem separa é o `gap` da própria coluna, senão a ação descola e
   volta a parecer um bloco à parte. ── */
.pev-recent-footer {
  display: flex;
  align-self: flex-end;
  align-items: center;
  max-width: 100%;
  padding: 0 4px;
}
.pev-rail-more {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  width: auto;
  /* Continua um alvo de toque honesto mesmo sem largura cheia. */
  min-height: 40px;
  margin-top: 0;
  padding: 0;
  border: 0;
  border-radius: var(--r-pill);
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 9px);
  letter-spacing: .1em;
  text-transform: uppercase;
  font-weight: 600;
  color: var(--txt-heading);
  background: none;
  box-shadow: none;
  cursor: pointer;
  transition: color .25s var(--ease), opacity .25s var(--ease), transform .2s var(--ease-snappy);
}
/* chevron desenhado em CSS — nada de codepoint de fonte chutado aqui */
.pev-rail-more::after {
  content: "";
  width: 6px;
  height: 6px;
  border-right: 1.5px solid currentColor;
  border-bottom: 1.5px solid currentColor;
  transform: rotate(-45deg);
  opacity: .5;
  transition: transform .25s var(--ease), opacity .25s var(--ease);
}
.pev-rail-more:hover::after { transform: translateX(2px) rotate(-45deg); opacity: .8; }
.pev-rail-more:hover {
  background: none;
  box-shadow: none;
  opacity: .85;
}
.pev-rail-more:active { transform: scale(.985); }
.pev-recent-footer:empty { display: none; }

/* ── Reveal-up dos itens carregados em rolagem infinita ─────────────────────
   Estado inicial só existe entre a inserção e o fim da transição: o JS remove
   as classes no transitionend, então nada de estilo residual grudado no
   cartão depois que ele já apareceu. ── */
.pev-reveal {
  opacity: 0;
  transform: translate3d(0, 18px, 0);
  transition: opacity .6s var(--ease), transform .6s var(--ease);
  will-change: opacity, transform;
}
.pev-reveal.is-in {
  opacity: 1;
  transform: none;
}
.pev-sentinel {
  width: 100%;
  min-height: 1px;
  grid-column: 1 / -1; /* dentro da .grid-layout não pode virar uma célula */
}
@media (prefers-reduced-motion: reduce) {
  .pev-reveal { opacity: 1; transform: none; transition: none; }
}

.pev-rail-modal { z-index: 9965; }
.pev-rail-modal-body {
  padding: 22px 18px 12px;
}
.pev-rail-modal-body h2 {
  font-family: var(--ff-heading);
  font-size: calc(20px * var(--fs-u, 1));
  font-weight: 800;
  letter-spacing: -.03em;
  color: var(--txt-heading);
  margin: 0 0 4px;
}
.pev-rail-modal-sub {
  margin: 0 0 14px;
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 11px);
  color: var(--muted-txt, var(--muted));
}
.pev-rail-modal .event-list-container {
  max-height: min(60dvh, 520px);
  overflow-y: auto;
}

/* ── EVENTOS EM GRADE · MOBILE APP FIRST ────────────────────────────────────
   Todo cartão de evento do portal é o componente OFICIAL .a-eve-card
   (ds-components.css), dentro do stub <article class="app-market eve"> —
   mesmo contrato de markup do showcase. A grade é .grid-layout (2 por linha
   no mobile, 4 no ≥1000px, já definida em market.css); as regras abaixo são
   cópia byte-a-byte do bloco "#eveGrid.grid-layout" do uni.theme.v2.1.css,
   só re-escopadas para a classe .eve-grid (o portal tem várias grades, não
   um único #eveGrid). Nada de trilho horizontal: no celular ele empurrava o
   conteúdo pra fora da tela. ── */
.eve-grid.grid-layout .app-market.eve { min-width: 0; }
.eve-grid.grid-layout .a-eve-card { max-width: none; }
.eve-grid.grid-layout .a-eve-media { height: clamp(160px, 46vw, 240px); border-radius: var(--r); --x: 40px; --y: 34px; }
/* ── Escala de tipo do cartão (2026-08-01) ──────────────────────────────────
   Bloco de data 1.65x e conteúdo 1.18x, conforme pedido. Os multiplicadores
   ficam EXPLÍCITOS em vez de virarem números já calculados, pra que a próxima
   pessoa veja a intenção ("1.65x do original") e não um 28.05px sem história.
   A caixa da data cresce junto, senão o dia estoura a moldura. ── */
.eve-grid.grid-layout .a-eve-date { width: 76px; height: 68px; }
.eve-grid.grid-layout .a-eve-date-day { font-size: calc(17px * 1.65 * var(--fs-u, 1)); }
.eve-grid.grid-layout .a-eve-date-month { font-size: calc(9px * 1.65 * var(--fs-u, 1)); }
.eve-grid.grid-layout .a-eve-title { font-size: calc(13px * 1.18 * var(--fs-u, 1)); }
.eve-grid.grid-layout .a-eve-meta { font-size: calc(10.5px * 1.18 * var(--fs-u, 1)); margin-bottom: .25rem; }
.eve-grid.grid-layout .a-eve-content { font-size: calc(1em * 1.18); }
.eve-grid.grid-layout .a-eve-tag { font-size: calc(7px * 1.18 * var(--fs-u, 1)); padding: 3px 7px; }
@media (min-width: 1000px) { .eve-grid.grid-layout .a-eve-media { height: 280px; } }
/* wrapper do showcase — contém qualquer sangramento sem virar scroll-x */
.pev .eve-body { position: relative; overflow-x: clip; overflow-y: visible; }
/* a grade já traz margin-bottom 60px do DS; dentro do portal o espaçamento
   entre seções é do .pev-rails, então zeramos pra não dobrar */
.pev .eve-body .grid-layout { margin-bottom: 0 !important; }

.pev-empty {
  flex: 0 0 auto;
  min-width: 200px;
  padding: 28px 18px;
  color: var(--muted);
  font-size: calc(var(--fs-r, 1) * 12px);
  text-align: center;
}
</style>
