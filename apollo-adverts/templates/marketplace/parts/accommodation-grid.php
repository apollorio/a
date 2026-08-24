<?php
/**
 * Template Part: Accommodation Grid
 *
 * WP_Query wrapper — renders accommodation classifieds in a responsive grid.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$accom_query = new WP_Query( array(
    'post_type'      => 'classified',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'meta_query'     => array(
        array(
            'key'   => '_classified_type',
            'value' => 'accommodation',
        ),
    ),
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

$accom_count = $accom_query->found_posts;
?>

<?php
load_template(
    dirname( __DIR__ ) . '/parts/section-header.php',
    false,
    array(
        'icon'        => 'ri-home-heart-line',
        'title'       => 'Hospedagem',
        'count'       => $accom_count . ' disponíveis',
        'view_toggle' => false,
    )
);
?>

<?php if ( $accom_query->have_posts() ) : ?>
    <div class="grid-layout">
        <?php
        while ( $accom_query->have_posts() ) :
            $accom_query->the_post();
            load_template(
                dirname( __DIR__ ) . '/parts/card-accommodation.php',
                false
            );
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
<?php else : ?>
    <p class="marketplace-empty"><?php esc_html_e( 'Nenhuma hospedagem disponível no momento.', 'apollo-adverts' ); ?></p>
<?php endif; ?>
