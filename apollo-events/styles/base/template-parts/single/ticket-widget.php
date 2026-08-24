<?php
/**
 * Single Event — Ticket Widget
 *
 * Buy ticket button (with hover fill), coupon code box, "Lista Amiga" secondary link.
 *
 * Expected variables: $ticket_url, $ticket_price, $coupon_code, $list_url, $is_gone
 *
 * @package Apollo\Event
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Nothing to show if no ticket info and no list url
if ( empty( $ticket_url ) && empty( $ticket_price ) && empty( $list_url ) ) {
    return;
}
?>

<div class="ticket-widget" id="ingressos">
    <span class="ticket-header"><?php esc_html_e( 'Acessos Oficiais', 'apollo-events' ); ?></span>

    <?php if ( $ticket_url && ! $is_gone ) : ?>
        <a href="<?php echo esc_url( $ticket_url ); ?>" target="_blank" rel="noopener" class="ticket-btn">
            <span>
                <?php
                if ( $ticket_price ) {
                    /* translators: %s: ticket price */
                    printf( esc_html__( 'Comprar Ingresso — %s', 'apollo-events' ), esc_html( $ticket_price ) );
                } else {
                    esc_html_e( 'Comprar Ingresso', 'apollo-events' );
                }
                ?>
            </span>
        </a>
    <?php elseif ( $ticket_price ) : ?>
        <div class="ticket-btn" style="opacity:.5;pointer-events:none">
            <span><?php echo esc_html( $ticket_price ); ?></span>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $coupon_code ) && ! $is_gone ) : ?>
        <div class="coupon-box"
             title="<?php esc_attr_e( 'Clique para copiar', 'apollo-events' ); ?>"
             data-coupon="<?php echo esc_attr( $coupon_code ); ?>">
            <?php esc_html_e( 'Cupom oficial:', 'apollo-events' ); ?>
            <span class="coupon-on-event"><?php echo esc_html( $coupon_code ); ?></span>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $list_url ) && ! $is_gone ) : ?>
        <a href="<?php echo esc_url( $list_url ); ?>" target="_blank" rel="noopener" class="ticket-btn secondary">
            <span><?php esc_html_e( 'Lista Amiga', 'apollo-events' ); ?></span>
        </a>
    <?php endif; ?>
</div>
