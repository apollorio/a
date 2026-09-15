<?php

/**
 * New Home — Acomoda::Rio
 *
 * Same expandable rt-card as Classificados. Hostels stay public.
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}

$ids = array();

$accom_classified = new WP_Query(
    array(
        'post_type'              => 'classified',
        'posts_per_page'         => 8,
        'post_status'            => 'publish',
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'meta_query'             => array(
            array(
                'key'     => '_classified_type',
                'value'   => defined('APOLLO_ADVERTS_ACCOMMODATION_TYPES')
                    ? APOLLO_ADVERTS_ACCOMMODATION_TYPES
                    : array('accommodation', 'rent_space'),
                'compare' => 'IN',
            ),
        ),
    )
);
if (! empty($accom_classified->posts)) {
    $ids = array_map('intval', $accom_classified->posts);
}

if (count($ids) < 8) {
    $locals = new WP_Query(
        array(
            'post_type'              => 'local',
            'posts_per_page'         => 8 - count($ids),
            'post_status'            => 'publish',
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'post__not_in'           => $ids,
            'tax_query'              => array(
                array(
                    'taxonomy' => 'local_type',
                    'field'    => 'slug',
                    'terms'    => array('crash', 'hospedagem', 'acomoda'),
                    'operator' => 'IN',
                ),
            ),
        )
    );
    if (! empty($locals->posts)) {
        $ids = array_merge($ids, array_map('intval', $locals->posts));
    }
}

$mkplace_url = home_url('/anuncios/');
$rt_css_file = defined('APOLLO_ADVERTS_DIR') ? APOLLO_ADVERTS_DIR . 'assets/css/rt-card.css' : '';
$rt_js_file  = defined('APOLLO_ADVERTS_DIR') ? APOLLO_ADVERTS_DIR . 'assets/js/rt-card.js' : '';
if ($rt_css_file && is_readable($rt_css_file) && ! defined('APOLLO_CASA_RT_CSS')) {
    define('APOLLO_CASA_RT_CSS', true);
    echo '<style id="casa-rt-card">' . file_get_contents($rt_css_file) . '</style>'; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>
<section class="section" id="crash" aria-labelledby="crash-title">
    <div class="container">
        <div class="nh-section-head ai nh-section-head--toolbar">
            <h2 id="crash-title" split-chars>Acomoda::Rio</h2>
            <a class="nh-section-more" href="<?php echo esc_url($mkplace_url); ?>"><?php esc_html_e('Ver todos', 'apollo-templates'); ?></a>
        </div>
        <p class="nh-resale-intro ai">
            <?php esc_html_e('Hospedagens da rede. Confirme pessoas em comum antes do chat.', 'apollo-templates'); ?>
        </p>

        <?php if (! empty($ids) && function_exists('apollo_adverts_render_rt_card')) : ?>
            <div class="nh-rt-rail ai" data-lenis-prevent aria-label="<?php esc_attr_e('Acomodações', 'apollo-templates'); ?>">
                <?php foreach ($ids as $lid) : ?>
                    <?php apollo_adverts_render_rt_card((int) $lid, array('kind' => 'accommodation', 'variant' => 'rail')); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="nh-empty-state nh-empty-state--quiet ai">
                <p><?php esc_html_e('Em breve', 'apollo-templates'); ?></p>
            </div>
        <?php endif; ?>

        <aside class="nh-disclaimer nh-disclaimer--quiet ai" role="note">
            <i class="ri-shield-check-line" aria-hidden="true"></i>
            <p><?php esc_html_e('Hostels oficiais abrem o site de reserva. Estadias entre membros passam pelo aviso de segurança.', 'apollo-templates'); ?></p>
        </aside>
    </div>
</section>
<?php
if (! empty($rt_js_file) && is_readable($rt_js_file) && ! defined('APOLLO_CASA_RT_JS')) {
    define('APOLLO_CASA_RT_JS', true);
    echo '<script id="casa-rt-card-js">' . file_get_contents($rt_js_file) . '</script>'; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>
