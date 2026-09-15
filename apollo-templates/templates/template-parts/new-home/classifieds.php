<?php

/**
 * New Home — Classificados (resale tickets)
 *
 * Shared expandable rt-card (same as /anuncios). Guests: no seller PII.
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}

$classifieds_query = new WP_Query(
    array(
        'post_type'      => 'classified',
        'posts_per_page' => 8,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'     => '_classified_type',
                'value'   => defined('APOLLO_ADVERTS_TICKET_TYPES')
                    ? APOLLO_ADVERTS_TICKET_TYPES
                    : array('ticket', 'ticket_sell'),
                'compare' => 'IN',
            ),
        ),
    )
);

$mkplace_url = home_url('/anuncios/');
$ids         = array();
if ($classifieds_query->have_posts()) {
    while ($classifieds_query->have_posts()) {
        $classifieds_query->the_post();
        $ids[] = (int) get_the_ID();
    }
    wp_reset_postdata();
}
if (empty($ids)) {
    $classifieds_query = new WP_Query(
        array(
            'post_type'      => 'classified',
            'posts_per_page' => 8,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );
    if ($classifieds_query->have_posts()) {
        while ($classifieds_query->have_posts()) {
            $classifieds_query->the_post();
            $ids[] = (int) get_the_ID();
        }
        wp_reset_postdata();
    }
}
?>
<?php
$rt_css_file = defined('APOLLO_ADVERTS_DIR') ? APOLLO_ADVERTS_DIR . 'assets/css/rt-card.css' : '';
$rt_js_file  = defined('APOLLO_ADVERTS_DIR') ? APOLLO_ADVERTS_DIR . 'assets/js/rt-card.js' : '';
if ($rt_css_file && is_readable($rt_css_file) && ! defined('APOLLO_CASA_RT_CSS')) {
    define('APOLLO_CASA_RT_CSS', true);
    echo '<style id="casa-rt-card">' . file_get_contents($rt_css_file) . '</style>'; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>
<style id="casa-resell-edge">
#resell .nh-rt-rail {
  -webkit-mask-image: none !important;
  mask-image: none !important;
  width: calc(100vw + 16px) !important;
  max-width: none !important;
  margin-left: calc(50% - 50vw - 8px) !important;
  margin-right: calc(50% - 50vw - 8px) !important;
  padding-left: var(--nh-gutter, 20px);
  padding-right: var(--nh-gutter, 20px);
}
/* Expand contract — cache-proof (wins over stale rt-card.css) */
#resell .rt-details,
#crash .rt-details {
  display: grid !important;
  grid-template-rows: 0fr !important;
  opacity: 0 !important;
  height: auto !important;
  overflow: hidden !important;
  transition: grid-template-rows .45s cubic-bezier(.16,1,.3,1), opacity .35s ease !important;
}
#resell .rt-details-inner,
#crash .rt-details-inner { overflow: hidden !important; min-height: 0 !important; }
#resell .rt-card.is-open .rt-details,
#crash .rt-card.is-open .rt-details {
  grid-template-rows: 1fr !important;
  opacity: 1 !important;
}
@media (max-width: 767px) {
  #resell .nh-rt-rail {
    width: calc(100vw + 20px) !important;
    margin-left: calc(50% - 50vw - 10px) !important;
    margin-right: calc(50% - 50vw - 10px) !important;
  }
}
</style>
<section class="section" id="resell" aria-labelledby="resell-title">
    <div class="container">
        <div class="nh-section-head ai nh-section-head--toolbar">
            <h2 id="resell-title" split-chars>Classificados</h2>
            <a class="nh-section-more" href="<?php echo esc_url($mkplace_url); ?>"><?php esc_html_e('Ver todos', 'apollo-templates'); ?></a>
        </div>
        <p class="nh-resale-intro ai">
            <?php esc_html_e('Marketplace peer-to-peer. Identidade do vendedor só para membros.', 'apollo-templates'); ?>
        </p>

        <?php if (! empty($ids) && function_exists('apollo_adverts_render_rt_card')) : ?>
            <div class="nh-rt-rail ai" data-lenis-prevent aria-label="<?php esc_attr_e('Ingressos à venda', 'apollo-templates'); ?>">
                <?php foreach ($ids as $cid) : ?>
                    <?php apollo_adverts_render_rt_card((int) $cid, array('kind' => 'ticket', 'variant' => 'rail')); ?>
                <?php endforeach; ?>
            </div>
        <?php elseif (! empty($ids)) : ?>
            <div class="nh-empty-state ai" style="padding:40px 28px;text-align:center">
                <p><?php esc_html_e('Atualize o plugin Apollo Adverts para ver os cards.', 'apollo-templates'); ?></p>
            </div>
        <?php else : ?>
            <div class="nh-empty-state ai" style="padding:40px 28px;text-align:center">
                <i class="ri-exchange-box-line" aria-hidden="true"></i>
                <p>Em breve..</p>
                <span class="nh-empty-sub">O marketplace abrirá em breve com ingressos e itens da comunidade.</span>
            </div>
        <?php endif; ?>

        <aside class="nh-disclaimer nh-disclaimer--quiet ai" role="note">
            <i class="ri-shield-check-line" aria-hidden="true"></i>
            <p><?php esc_html_e('Não processamos pagamentos. Confirme pessoas em comum antes do chat.', 'apollo-templates'); ?></p>
        </aside>
    </div>
</section>
<?php
if (! empty($rt_js_file) && is_readable($rt_js_file) && ! defined('APOLLO_CASA_RT_JS')) {
    define('APOLLO_CASA_RT_JS', true);
    echo '<script id="casa-rt-card-js">' . file_get_contents($rt_js_file) . '</script>'; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>
