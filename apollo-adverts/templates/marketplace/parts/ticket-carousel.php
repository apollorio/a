<?php
/**
 * Template Part: Ticket Carousel
 *
 * WP_Query wrapper — renders ticket classifieds inside a CSS-variable carousel.
 * Requires carousel JS modules (carousel-state, drag, wheel, click).
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tickets_query = new WP_Query( array(
    'post_type'      => 'classified',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'meta_query'     => array(
        array(
            'key'   => '_classified_type',
            'value' => 'ticket',
        ),
    ),
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

$ticket_count = $tickets_query->found_posts;
?>

<?php
load_template(
    dirname( __DIR__ ) . '/parts/section-header.php',
    false,
    array(
        'icon'        => 'ri-ticket-2-line',
        'title'       => 'Ingressos',
        'count'       => $ticket_count . ' disponíveis',
        'view_toggle' => true,
        'carousel_id' => 'ticketCarousel',
    )
);
?>

<?php if ( $tickets_query->have_posts() ) : ?>
    <div class="carousel" id="ticketCarousel">
        <?php
        while ( $tickets_query->have_posts() ) :
            $tickets_query->the_post();
            load_template(
                dirname( __DIR__ ) . '/parts/card-ticket.php',
                false
            );
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
<?php else : ?>
    <p class="marketplace-empty"><?php esc_html_e( 'Nenhum ingresso disponível no momento.', 'apollo-adverts' ); ?></p>
<?php endif; ?>
