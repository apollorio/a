<?php

/**
 * Template: Classifieds Marketplace Page
 *
 * Canvas template for full marketplace experience.
 * Displays tickets, accommodations, and other classifieds.
 *
 * @package Apollo\Classifieds
 */

if (! defined('ABSPATH')) {
    exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Classificados - Apollo::Rio</title>

    <!-- Apollo CDN - Mandatory for all pages -->
    <script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

    <!-- Page styles -->
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: var(--ff-main, 'Space Grotesk', system-ui, sans-serif);
            line-height: 1.5;
        }
    </style>
</head>

<body>

    <?php
    // Load page header
    get_template_part('wp-content/plugins/apollo-classifieds/templates/parts/page-header');
    ?>

    <main class="container">

        <?php
        // Security info box
        get_template_part('wp-content/plugins/apollo-classifieds/templates/parts/info-box');
        ?>

        <?php
        // SECTION: Tickets/Repasses
        $tickets_args  = array(
            'post_type'      => 'classified',
            'posts_per_page' => 12,
            'meta_query'     => array(
                array(
                    'key'     => '_classified_type',
                    'value'   => 'ticket',
                    'compare' => '=',
                ),
            ),
        );
        $tickets_query = new WP_Query($tickets_args);

        if ($tickets_query->have_posts()) :
            get_template_part(
                'wp-content/plugins/apollo-classifieds/templates/parts/section-header',
                null,
                array(
                    'icon'  => 'ri-ticket-2-fill',
                    'title' => 'Repasses',
                    'count' => $tickets_query->found_posts . ' Active',
                )
            );

            // Filters
            get_template_part(
                'wp-content/plugins/apollo-classifieds/templates/parts/filters-row',
                null,
                array(
                    'filters' => array('Todos', 'Sunset Theory', 'Industrial', 'After-hours'),
                )
            );
        ?>

            <div class="grid-layout">
                <?php
                while ($tickets_query->have_posts()) :
                    $tickets_query->the_post();
                    get_template_part('wp-content/plugins/apollo-classifieds/templates/parts/card-ticket');
                endwhile;
                ?>
            </div>

        <?php
        endif;
        wp_reset_postdata();
        ?>

        <?php
        // SECTION: Accommodations
        $accom_args  = array(
            'post_type'      => 'classified',
            'posts_per_page' => 12,
            'meta_query'     => array(
                array(
                    'key'     => '_classified_type',
                    'value'   => 'accommodation',
                    'compare' => '=',
                ),
            ),
        );
        $accom_query = new WP_Query($accom_args);

        if ($accom_query->have_posts()) :
            get_template_part(
                'wp-content/plugins/apollo-classifieds/templates/parts/section-header',
                null,
                array(
                    'icon'  => 'ri-hotel-bed-fill',
                    'title' => 'Acomodações',
                    'count' => $accom_query->found_posts . ' Spots',
                )
            );
        ?>

            <div class="grid-layout">
                <?php
                while ($accom_query->have_posts()) :
                    $accom_query->the_post();
                    get_template_part('wp-content/plugins/apollo-classifieds/templates/parts/card-accommodation');
                endwhile;
                ?>
            </div>

        <?php
        endif;
        wp_reset_postdata();
        ?>

    </main>

    <?php
    // Load disclaimer modal (global for all classified interactions)
    get_template_part('wp-content/plugins/apollo-classifieds/templates/parts/modal-disclaimer');
    ?>

    <?php if (is_user_logged_in()) { apollo_render_navbar(); } ?>

</body>

</html>