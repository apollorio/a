<?php
/**
 * Marketplace — filter pills.
 *
 * Built from the REAL classified_domain taxonomy, not a fixture list. Renders
 * nothing when no terms exist, rather than inventing categories.
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$tax   = defined( 'APOLLO_TAX_CLASSIFIED_DOMAIN' ) ? APOLLO_TAX_CLASSIFIED_DOMAIN : 'classified_domain';
$terms = taxonomy_exists( $tax ) ? get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) ) : array();
if ( is_wp_error( $terms ) || empty( $terms ) ) { return; }
?>
<div class="mk-pills" role="group" aria-label="<?php esc_attr_e( 'Filtrar anúncios', 'apollo-adverts' ); ?>">
    <button type="button" class="mk-pill is-active" data-mk-filter="all"><?php esc_html_e( 'Todos', 'apollo-adverts' ); ?></button>
    <?php foreach ( $terms as $t ) : ?>
        <button type="button" class="mk-pill" data-mk-filter="<?php echo esc_attr( $t->slug ); ?>"><?php echo esc_html( $t->name ); ?></button>
    <?php endforeach; ?>
</div>
