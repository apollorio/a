<?php

/**
 * Portal de Eventos — stylesheet loader.
 *
 * Puxa as células de estilo em ordem de cascata. A ordem importa: escopo base
 * primeiro, overrides responsivos depois, e o contrato de ritmo por último —
 * ele é o dono único dos gutters/bandas e precisa ganhar de qualquer célula
 * que tenha tocado nos mesmos seletores antes.
 *
 * Um tema pode substituir qualquer seção entregando
 * apollo-events/{style}/template-parts/archive/portal/styles-{slug}.php.
 *
 * MUDANÇA 1.7.1 — duas células saíram do loop:
 *   · 'chrome'   (styles-chrome.php)   → estilizava o stepper de mês e o
 *     dropdown de período da masthead. Todo aquele markup foi removido de
 *     app.php quando /eventos adotou o bloco apollo_listing_header().
 *   · 'masthead' (styles-masthead.php) → virou 'rhythm' (styles-rhythm.php).
 *     O trilho e o scroll dock morreram com a masthead; o contrato `--pev-*`,
 *     que só morava junto, sobreviveu com o nome do que realmente faz.
 * Ambos os arquivos foram apagados, não esvaziados: CSS órfão apontando para
 * seletores que não existem mais é exatamente o tipo de fantasma que volta a
 * disputar cascata com o dono novo.
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* 'btn-force' sits early (after base, before browse) so the mandatory
 * `.btn` / `.btn-secondary` stack wins over a broken/incomplete core.js type
 * scale, while browse/rails keep tax-only and layout deltas. */
foreach ( array( 'base', 'btn-force', 'ds', 'browse', 'hero', 'rails', 'browse-mini', 'modals', 'responsive', 'rhythm', 'lightbox' ) as $pev_slug ) {
	$pev_file = __DIR__ . '/styles-' . $pev_slug . '.php';
	if ( is_readable( $pev_file ) ) {
		require $pev_file;
	}
}

/**
 * Shell fit — Apollo+ .ax-main, não uma página autônoma.
 *
 * `.ax-body{padding-top:56px}` (apollo-plus/topbar-styles.php) já libera o
 * topbar fixo. O portal só precisa do próprio respiro.
 *
 * HISTÓRICO, porque cada linha aqui já foi um bug:
 *
 * · PHASE 002 — o arquivo pré-split cravava `.pev-host{padding-top:78px}` e
 *   `.pev-chrome{top:64px}` contra a navbar autônoma que esta tela renderizava
 *   para si mesma. Nada disso existe no shell Apollo+.
 * · 2026-07-30 — sobrou um `padding-top:70px` de quando NADA reservava espaço
 *   para o topbar. O shell passou a trazer `.ax-body{padding-top:56px}`, os
 *   dois empilharam, e o mês nascia ~126px abaixo do topo.
 * · 2026-08-01 — o bloco terminava com `.pev-chrome{position:absolute;top:58px}`,
 *   que tirava a linha do mês do fluxo e fazia o rótulo display cair dentro do
 *   hero. A correção foi dar a um único dono a geometria do cabeçalho.
 * · 2026-08-08 — esse dono deixou de ser CSS do portal: o cabeçalho é o bloco
 *   apollo_listing_header(), um elemento em fluxo normal renderizado antes de
 *   #apollo-portal-root. Não há mais geometria de cabeçalho para possuir aqui.
 *
 * O que resta é genuinamente de nível de shell.
 */
?>
<style id="apollo-pev-shell-fit">
.pev-host{padding-top:8px}
</style>
