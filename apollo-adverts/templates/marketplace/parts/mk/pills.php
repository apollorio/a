<?php
/**
 * Marketplace — type pills always, plus classified_domain terms when present.
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$tax   = defined( 'APOLLO_TAX_CLASSIFIED_DOMAIN' ) ? APOLLO_TAX_CLASSIFIED_DOMAIN : 'classified_domain';
$terms = taxonomy_exists( $tax ) ? get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) ) : array();
if ( is_wp_error( $terms ) ) {
	$terms = array();
}
?>
<div class="filters-row reveal-up mk-pills" id="filterPills" role="group" aria-label="<?php esc_attr_e( 'Filtrar anúncios', 'apollo-adverts' ); ?>">
	<button type="button" class="mk-pill filter-pill is-active active" data-mk-filter="all"><?php esc_html_e( 'Todos', 'apollo-adverts' ); ?></button>
	<button type="button" class="mk-pill filter-pill" data-mk-filter="ticket"><?php esc_html_e( 'Repasses', 'apollo-adverts' ); ?></button>
	<button type="button" class="mk-pill filter-pill" data-mk-filter="accommodation"><?php esc_html_e( 'Hospedagem', 'apollo-adverts' ); ?></button>
	<?php foreach ( $terms as $t ) : ?>
		<button type="button" class="mk-pill filter-pill" data-mk-filter="<?php echo esc_attr( $t->slug ); ?>"><?php echo esc_html( $t->name ); ?></button>
	<?php endforeach; ?>
</div>
