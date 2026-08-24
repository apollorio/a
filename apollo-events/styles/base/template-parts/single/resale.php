<?php
/**
 * Single Event — Resale Section
 *
 * Ticket resale cards from apollo-adverts classifieds linked to event.
 *
 * Expected variables: $post_id
 *
 * @package Apollo\Event
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! post_type_exists( 'classified' ) ) {
    return;
}

$resale_query = new WP_Query(
    array(
        'post_type'      => 'classified',
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        'meta_query'     => array(
            array(
                'key'   => '_classified_event_id',
                'value' => $post_id,
                'type'  => 'NUMERIC',
            ),
        ),
    )
);

if ( ! $resale_query->have_posts() ) {
    return;
}
?>

<div class="a-eve-single__section a-eve-resale">
    <div class="a-eve-resale__header">
        <h3 class="a-eve-resale__title">
            <i class="ri-ticket-line"></i>
            <?php esc_html_e( 'Ingressos à venda por usuários', 'apollo-events' ); ?>
        </h3>
        <span class="a-eve-resale__count">
            <?php echo esc_html( $resale_query->found_posts ); ?>
            <?php esc_html_e( 'ofertas', 'apollo-events' ); ?>
        </span>
    </div>

    <div class="a-eve-resale__grid">
        <?php while ( $resale_query->have_posts() ) :
            $resale_query->the_post();
            $r_id       = get_the_ID();
            $r_price    = get_post_meta( $r_id, '_classified_price', true );
            $r_currency = get_post_meta( $r_id, '_classified_currency', true ) ?: 'BRL';
            $r_neg      = get_post_meta( $r_id, '_classified_negotiable', true );
            $r_author   = get_the_author();
            $r_avatar   = get_avatar_url( get_the_author_meta( 'ID' ), array( 'size' => 80 ) );
        ?>
            <a href="<?php the_permalink(); ?>" class="a-eve-resale__card">
                <div class="a-eve-resale__card-top">
                    <img src="<?php echo esc_url( $r_avatar ); ?>"
                         alt="<?php echo esc_attr( $r_author ); ?>"
                         class="a-eve-resale__avatar" />
                    <div class="a-eve-resale__seller">
                        <span class="a-eve-resale__seller-name"><?php echo esc_html( $r_author ); ?></span>
                    </div>
                </div>
                <h4 class="a-eve-resale__card-title"><?php the_title(); ?></h4>
                <div class="a-eve-resale__card-bottom">
                    <?php if ( $r_price ) : ?>
                        <span class="a-eve-resale__price">
                            <?php echo esc_html( $r_currency . ' ' . number_format( (float) $r_price, 0, ',', '.' ) ); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ( $r_neg ) : ?>
                        <span class="a-eve-resale__neg"><?php esc_html_e( 'Negociável', 'apollo-events' ); ?></span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>
