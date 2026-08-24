<?php
/**
 * @partial numbers
 * @expects $ctx['stats'] — apollo_dj_compute_stats( past events )
 *          events / venues / cities / dawns (filter: apollo_dj_card_stats)
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$stats = $ctx['stats'] ?? array();
if ( empty( $stats ) || (int) ( $stats['events'] ?? 0 ) <= 0 ) {
	return;
}
?>
<section class="sec-tight band" id="numbers">
	<div class="wrap">
		<div class="sh"><div><span class="lbl"><?php esc_html_e( 'Retrospecto', 'apollo-djs' ); ?></span><h2 class="serif sh-t"><?php esc_html_e( 'Em números', 'apollo-djs' ); ?></h2></div></div>
		<div class="nums">
			<div class="num rv"><div class="num-v"><span data-stat="events" data-count="<?php echo esc_attr( (string) ( $stats['events'] ?? 0 ) ); ?>">0</span></div><p class="num-l"><?php esc_html_e( 'Eventos tocados no circuito Apollo', 'apollo-djs' ); ?></p></div>
			<div class="num rv"><div class="num-v"><span data-stat="venues" data-count="<?php echo esc_attr( (string) ( $stats['venues'] ?? 0 ) ); ?>">0</span></div><p class="num-l"><?php esc_html_e( 'Casas e promotoras diferentes que já bookaram', 'apollo-djs' ); ?></p></div>
			<div class="num rv"><div class="num-v"><span data-stat="cities" data-count="<?php echo esc_attr( (string) ( $stats['cities'] ?? 0 ) ); ?>">0</span></div><p class="num-l"><?php esc_html_e( 'Cidades — do Rio ao Brasil', 'apollo-djs' ); ?></p></div>
			<div class="num rv"><div class="num-v"><span data-stat="dawns" data-count="<?php echo esc_attr( (string) ( $stats['dawns'] ?? 0 ) ); ?>">0</span></div><p class="num-l"><?php esc_html_e( 'Amanheceres que a pista se recusou a soltar', 'apollo-djs' ); ?></p></div>
		</div>
	</div>
</section>
