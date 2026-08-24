<?php

/**
 * Portal de Eventos — cabeçalho da listagem (adaptador).
 *
 * Este arquivo NÃO desenha um header. Ele traduz o Portal de Eventos para o
 * contrato do bloco reutilizável apollo_listing_header() (apollo-templates,
 * includes/listing-header-api.php) — que é o "Header 02 · Kinetic Mask · Apple"
 * aprovado em screen/portal/header listing/header-of-listing-events.html.
 *
 * O QUE ISSO SUBSTITUIU
 * ─────────────────────────────────────────────────────────────────────────────
 * Até aqui o cabeçalho de /eventos era `.pev-masthead`, montado em JavaScript
 * dentro de app.php::skeleton() e estilizado por styles-masthead.php +
 * styles-chrome.php. Três problemas que o bloco novo resolve de origem:
 *
 *   · era markup dentro de uma string de JS — nada dele existia no primeiro
 *     paint, e o mês só aparecia depois que o runtime rodava;
 *   · não dava pra reaproveitar em nenhuma outra tela: qualquer listagem que
 *     quisesse o mesmo cabeçalho teria que copiar o skeleton() inteiro;
 *   · o menu de período era um dropdown de 13rem preso na masthead, enquanto o
 *     filtro por taxonomia simplesmente não existia na tela.
 *
 * FONTE DA VERDADE (registry 03-apollo-rule.$apollo_rule.data_flow)
 * ─────────────────────────────────────────────────────────────────────────────
 * Nada aqui é inventado. Os doze meses vêm de date_i18n(), as contagens vêm de
 * $portal_events (as mesmas linhas que o portal renderiza) e as opções de
 * filtro vêm de get_terms() com hide_empty — termo sem evento não vira chip.
 * O mock original montava os chips a partir de um literal APOLLO_TAXONOMY no
 * próprio JS; esse literal não foi portado e não deve ser.
 *
 * CONTRATO DE CHAVES DOS GRUPOS — lido por header-bridge.php:
 *   periodo   → radio, troca o `state.mode` do portal (Passados/Mês/…)
 *   tax_*     → checkbox, casa com `e.tags` (NOMES de termo, que é o que
 *               archive-event.php coloca na linha do evento)
 *   sound     → checkbox, casa com `e.genres` (SLUGS do taxonomy sound)
 *
 * Espera $portal_events em escopo (montado por archive-event.php).
 *
 * @package Apollo\Event
 * @since   1.7.1
 * @see     apollo-templates/includes/listing-header-api.php  API do bloco
 * @see     header-bridge.php                                 liga o bloco ao portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_listing_header' ) ) {
	/* apollo-templates desligado: o portal continua funcionando sem cabeçalho
	   (as setas de mês vivem no bloco, mas o mês corrente ainda renderiza).
	   Melhor uma tela sem cabeçalho do que um fatal. */
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		echo '<!-- portal: apollo_listing_header() indisponível (apollo-templates inativo) -->';
	}
	return;
}

$pev_events = isset( $portal_events ) && is_array( $portal_events ) ? $portal_events : array();
$pev_year   = (int) current_time( 'Y' );
$pev_month  = (int) current_time( 'n' ) - 1;

/* ── Contagem por mês do ano corrente ────────────────────────────────────────
   O scrubber de 12 segmentos deixa de ser doze traços iguais e vira um mapa do
   ano. header-bridge.php recalcula isso no cliente quando o usuário navega
   para outro ano, usando APOLLO_PORTAL.byMonth(). */
$pev_counts = array_fill( 0, 12, 0 );
foreach ( $pev_events as $pev_row ) {
	$pev_start = isset( $pev_row['startDate'] ) ? (string) $pev_row['startDate'] : '';
	if ( 10 > strlen( $pev_start ) ) {
		continue;
	}
	if ( (int) substr( $pev_start, 0, 4 ) !== $pev_year ) {
		continue;
	}
	$pev_i = (int) substr( $pev_start, 5, 2 ) - 1;
	if ( $pev_i >= 0 && $pev_i < 12 ) {
		++$pev_counts[ $pev_i ];
	}
}

$pev_months = apollo_listing_header_months( $pev_counts );

/* ── Grupos de filtro ────────────────────────────────────────────────────────
   1) Período: o mesmo conjunto de modos que o dropdown antigo oferecia, com os
      MESMOS valores (`data-pev-mode`), então o comportamento do portal não
      muda — só o lugar onde o usuário escolhe. */
$pev_groups = array(
	array(
		'key'     => 'periodo',
		'label'   => 'Período',
		'type'    => 'radio',
		'value'   => 'mes',
		'options' => array(
			array(
				'value' => 'passados',
				'label' => 'Passados',
			),
			array(
				/* Valor = contrato (`data-pev-mode`, filterByMode); rótulo é
				   só texto e acompanha o título do trilho. */
				'value' => 'neste-fds',
				'label' => 'Esse FDS',
			),
			array(
				'value' => 'mes',
				'label' => 'Mês',
			),
			array(
				'value' => 'prox-fds',
				'label' => 'Próximos eventos',
			),
			array(
				'value' => 'proximos',
				'label' => 'Próximos',
			),
		),
	),
);

/**
 * Monta um grupo de chips a partir de termos reais.
 *
 * @param string $taxonomy Slug da taxonomia.
 * @param string $label    Título do grupo.
 * @param string $key      Chave do grupo (contrato com header-bridge.php).
 * @param string $field    'name' (casa com e.tags) ou 'slug' (casa com e.genres).
 * @return array<string, mixed>|null
 */
$pev_term_group = static function ( string $taxonomy, string $label, string $key, string $field ): ?array {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return null;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			/* Termo sem evento publicado não vira chip: um filtro que só pode
			   devolver zero resultados é uma promessa falsa. */
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'number'     => 40,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return null;
	}

	$options = array();
	foreach ( $terms as $term ) {
		$options[] = array(
			'value' => 'slug' === $field ? $term->slug : $term->name,
			'label' => $term->name,
		);
	}

	return array(
		'key'     => $key,
		'label'   => $label,
		'type'    => 'checkbox',
		'options' => $options,
	);
};

$pev_tax_cat   = defined( 'APOLLO_EVENT_TAX_CATEGORY' ) ? APOLLO_EVENT_TAX_CATEGORY : 'event_category';
$pev_tax_type  = defined( 'APOLLO_EVENT_TAX_TYPE' ) ? APOLLO_EVENT_TAX_TYPE : 'event_type';
$pev_tax_tag   = defined( 'APOLLO_EVENT_TAX_TAG' ) ? APOLLO_EVENT_TAX_TAG : 'event_tag';
$pev_tax_sound = defined( 'APOLLO_EVENT_TAX_SOUND' ) ? APOLLO_EVENT_TAX_SOUND : 'sound';

foreach (
	array(
		array( $pev_tax_cat, 'Categorias', 'tax_categoria', 'name' ),
		array( $pev_tax_type, 'Tipos', 'tax_tipo', 'name' ),
		array( $pev_tax_tag, 'Tags', 'tax_tag', 'name' ),
		array( $pev_tax_sound, 'Sons', 'sound', 'slug' ),
	) as $pev_spec
) {
	$pev_group = $pev_term_group( $pev_spec[0], $pev_spec[1], $pev_spec[2], $pev_spec[3] );
	if ( null !== $pev_group ) {
		$pev_groups[] = $pev_group;
	}
}

apollo_listing_header(
	array(
		'id'     => 'pevHeader',
		'skin'   => 'apple',
		'label'  => 'Navegação do portal de eventos',
		'month'  => $pev_month,
		'year'   => $pev_year,
		'months' => $pev_months,
		'search' => array(
			'title'       => 'Buscar eventos',
			'placeholder' => 'Nome, loc, DJ, som…',
		),
		'filter' => array(
			'title'  => 'Filtrar eventos',
			'groups' => $pev_groups,
		),
	)
);

/* Rótulos dos meses para o bridge: quando o usuário atravessa dezembro o ano
   muda, e o scrubber precisa se rerrotular sem uma ida ao servidor. */
$pev_bridge = array(
	'header' => 'pevHeader',
	'months' => wp_list_pluck( $pev_months, 'label' ),
	'abbr'   => wp_list_pluck( $pev_months, 'abbr' ),
);
?>
<script>
	window.APOLLO_PEV_HEADER = <?php
	echo function_exists( 'apollo_json_for_script' )
		? apollo_json_for_script( $pev_bridge )
		: wp_json_encode( $pev_bridge, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	?>;
</script>
