<?php

/**
 * Template: Single — Classified Post
 *
 * Canvas template. Renderiza um anúncio individual (ingresso ou hospedagem).
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

// Carrega assets do marketplace
wp_enqueue_style('apollo-adverts-marketplace');
wp_enqueue_script('apollo-adverts-marketplace');

// Carrega assets do chat (se disponível) para o botão modal
if (wp_script_is('apollo-chat', 'registered')) {
    wp_enqueue_script('apollo-chat');
}

global $post;
$classified_id   = get_the_ID();
$type            = get_post_meta($classified_id, '_classified_type', true);
$price           = get_post_meta($classified_id, '_classified_price', true);
$currency        = get_post_meta($classified_id, '_classified_currency', true) ?: 'BRL';
$currency_symbol = $currency === 'USD' ? '$' : ($currency === 'EUR' ? '€' : 'R$');
$negotiable      = get_post_meta($classified_id, '_classified_negotiable', true);
$intention       = get_post_meta($classified_id, '_classified_intention', true);
$condition       = get_post_meta($classified_id, '_classified_condition', true);
$location        = get_post_meta($classified_id, '_classified_loc', true);
$contact_phone   = get_post_meta($classified_id, '_classified_contact_phone', true);
$contact_wa      = get_post_meta($classified_id, '_classified_contact_whatsapp', true);
$event_title     = get_post_meta($classified_id, '_classified_event_title', true);
$event_date      = get_post_meta($classified_id, '_classified_event_date', true);
$event_location  = get_post_meta($classified_id, '_classified_event_loc', true);
$rating          = get_post_meta($classified_id, '_classified_rating', true);
$badge           = get_post_meta($classified_id, '_classified_badge', true);
$seller_id       = get_post_field('post_author', $classified_id);
$seller          = get_userdata($seller_id);
// Seller identity + direct contact are member-only — never rendered for guests.
$is_logged_in_viewer = is_user_logged_in();

$is_ticket  = in_array($type, array('ticket_sell', 'ticket'), true);
$is_accom   = in_array($type, array('rent_space', 'accommodation'), true);

$adverts_v  = defined('APOLLO_ADVERTS_VERSION') ? APOLLO_ADVERTS_VERSION : '1.0.1';
$adverts_base = defined('APOLLO_ADVERTS_URL') ? APOLLO_ADVERTS_URL : plugin_dir_url(__DIR__);

ob_start();
?>
<link rel="stylesheet" href="<?php echo esc_url($adverts_base . 'assets/css/marketplace.css?v=' . $adverts_v); ?>">
<script src="<?php echo esc_url($adverts_base . 'assets/js/marketplace.js?v=' . $adverts_v); ?>" defer></script>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => get_the_title() . ' — Apollo::Rio',
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>
<body>
<main class="apl-single-classified" itemscope itemtype="https://schema.org/Product">

    <div class="apl-single-container">

        <!-- HEADER -->
        <div class="apl-single-header">
            <nav class="apl-breadcrumb">
                <a href="<?php echo esc_url(home_url('/classifieds/')); ?>"><i class="ri-arrow-left-line"></i> <?php esc_html_e('Todos os anúncios', 'apollo-adverts'); ?></a>
            </nav>

            <?php if ($type) : ?>
                <span class="apl-card-badge">
                    <?php if ($is_ticket) : ?>
                        <i class="ri-ticket-2-line"></i> <?php esc_html_e('Ingresso', 'apollo-adverts'); ?>
                    <?php elseif ($is_accom) : ?>
                        <i class="ri-home-heart-line"></i> <?php esc_html_e('Hospedagem', 'apollo-adverts'); ?>
                    <?php else : ?>
                        <i class="ri-exchange-box-line"></i> <?php esc_html_e('Anúncio', 'apollo-adverts'); ?>
                    <?php endif; ?>
                </span>
            <?php endif; ?>

            <h1 class="apl-single-title" itemprop="name"><?php the_title(); ?></h1>

            <?php if ($price) : ?>
                <div class="apl-single-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                    <span itemprop="priceCurrency" content="<?php echo esc_attr($currency); ?>"></span>
                    <strong itemprop="price" content="<?php echo esc_attr((string) $price); ?>">
                        <?php echo esc_html($currency_symbol . ' ' . number_format((float) $price, 2, ',', '.')); ?>
                    </strong>
                    <?php if ($negotiable) : ?>
                        <small><?php esc_html_e('(negociável)', 'apollo-adverts'); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- GRID PRINCIPAL -->
        <div class="apl-single-grid">

            <!-- CONTEÚDO ESQUERDO -->
            <div class="apl-single-main">

                <?php if (has_post_thumbnail()) : ?>
                    <div class="apl-single-thumb">
                        <?php the_post_thumbnail('large', array('itemprop' => 'image')); ?>
                    </div>
                <?php endif; ?>

                <!-- Detalhes do evento (tipo ingresso) -->
                <?php if ($is_ticket && ($event_title || $event_date || $event_location)) : ?>
                    <div class="apl-info-box">
                        <h3><i class="ri-calendar-event-line"></i> <?php esc_html_e('Dados do Evento', 'apollo-adverts'); ?></h3>
                        <?php if ($event_title) : ?>
                            <p><strong><?php esc_html_e('Evento:', 'apollo-adverts'); ?></strong> <?php echo esc_html($event_title); ?></p>
                        <?php endif; ?>
                        <?php if ($event_date) : ?>
                            <p><strong><?php esc_html_e('Data:', 'apollo-adverts'); ?></strong> <?php echo esc_html($event_date); ?></p>
                        <?php endif; ?>
                        <?php if ($event_location) : ?>
                            <p><i class="ri-map-pin-line"></i> <?php echo esc_html($event_location); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Detalhes de hospedagem -->
                <?php if ($is_accom && $location) : ?>
                    <div class="apl-info-box">
                        <h3><i class="ri-map-pin-line"></i> <?php esc_html_e('Localização', 'apollo-adverts'); ?></h3>
                        <p><?php echo esc_html($location); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Regras da estadia — mínimo/máximo e janela de disponibilidade.
                     Hostel não tem janela (funciona o ano todo), por isso as datas
                     só aparecem para hospedagens que não são hostel. -->
                <?php
                if ($is_accom) :
                    $accom_is_hostel  = function_exists('apollo_adverts_is_hostel')
                        ? apollo_adverts_is_hostel($classified_id)
                        : '1' === (string) get_post_meta($classified_id, '_classified_hostel', true);
                    $accom_min_nights = (int) get_post_meta($classified_id, '_classified_min_nights', true);
                    $accom_max_days   = (int) get_post_meta($classified_id, '_classified_max_days', true);
                    $accom_start      = (string) get_post_meta($classified_id, '_classified_avail_start', true);
                    $accom_end        = (string) get_post_meta($classified_id, '_classified_avail_end', true);
                    $accom_has_rules  = $accom_min_nights || $accom_max_days || (! $accom_is_hostel && ($accom_start || $accom_end));
                    ?>
                    <?php if ($accom_has_rules) : ?>
                        <div class="apl-info-box">
                            <h3><i class="ri-calendar-check-line"></i> <?php esc_html_e('Regras da estadia', 'apollo-adverts'); ?></h3>
                            <?php if ($accom_is_hostel) : ?>
                                <p><i class="ri-community-line"></i> <?php esc_html_e('Hostel — disponível o ano todo.', 'apollo-adverts'); ?></p>
                            <?php endif; ?>
                            <?php if ($accom_min_nights) : ?>
                                <p><strong><?php esc_html_e('Mínimo:', 'apollo-adverts'); ?></strong>
                                    <?php
                                    printf(
                                        esc_html(
                                            /* translators: %d: number of nights. */
                                            _n('%d noite', '%d noites', $accom_min_nights, 'apollo-adverts')
                                        ),
                                        (int) $accom_min_nights
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($accom_max_days) : ?>
                                <p><strong><?php esc_html_e('Máximo:', 'apollo-adverts'); ?></strong>
                                    <?php
                                    printf(
                                        esc_html(
                                            /* translators: %d: number of days. */
                                            _n('%d dia', '%d dias', $accom_max_days, 'apollo-adverts')
                                        ),
                                        (int) $accom_max_days
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                            <?php if (! $accom_is_hostel && ($accom_start || $accom_end)) : ?>
                                <p><strong><?php esc_html_e('Disponibilidade:', 'apollo-adverts'); ?></strong>
                                    <?php
                                    echo esc_html(
                                        ($accom_start ? date_i18n('d/m/Y', strtotime($accom_start)) : '—')
                                        . ' → '
                                        . ($accom_end ? date_i18n('d/m/Y', strtotime($accom_end)) : '—')
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Descrição -->
                <?php if ($post->post_content) : ?>
                    <div class="apl-single-content" itemprop="description">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>

                <!-- Depoimentos — hospedagens apenas (comments_open() já restringe
                     via apollo_adverts_depoimentos_open() em includes/cpt.php). -->
                <?php
                if ($is_accom) {
                    $depo_part = APOLLO_ADVERTS_DIR . 'templates/parts/depoimentos.php';
                    if (is_readable($depo_part)) {
                        $post_id = $classified_id;
                        include $depo_part;
                    }
                }
                ?>

            </div>

            <!-- SIDEBAR DIREITA -->
            <div class="apl-single-sidebar">

                <!-- Dados do anunciante — identidade do vendedor SÓ para membros logados.
                     Guest markup deliberately contains NO avatar/display_name/badge —
                     not CSS-hidden, genuinely not rendered — matches the site-wide
                     "Identidade do vendedor visível apenas para membros" promise. -->
                <?php if ($is_logged_in_viewer && $seller) : ?>
                    <div class="apl-seller-card">
                        <?php $avatar = get_avatar($seller->ID, 64); ?>
                        <?php if ($avatar) echo wp_kses_post($avatar); ?>
                        <div class="apl-seller-info">
                            <strong><?php echo esc_html($seller->display_name); ?></strong>
                            <?php if ($badge) : ?>
                                <span class="apl-seller-badge"><?php echo esc_html($badge); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($seller) : ?>
                    <div class="apl-seller-card apl-seller-card--locked">
                        <div class="apl-seller-locked-icon" aria-hidden="true"><i class="ri-lock-2-line"></i></div>
                        <div class="apl-seller-info">
                            <span><?php esc_html_e('Identidade do vendedor visível apenas para membros', 'apollo-adverts'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Contato — telefone/WhatsApp SÓ para membros logados. Not rendered
                     at all (not even in a data-* attribute) for guests. -->
                <?php if ($is_logged_in_viewer && ($contact_wa || $contact_phone)) : ?>
                    <div class="apl-contact-box">
                        <h3><?php esc_html_e('Contato', 'apollo-adverts'); ?></h3>
                        <?php if ($contact_wa) : ?>
                            <a class="apl-btn-wa" href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/', '', $contact_wa)); ?>" target="_blank" rel="noopener noreferrer">
                                <i class="ri-whatsapp-line"></i> WhatsApp
                            </a>
                        <?php endif; ?>
                        <?php if ($contact_phone) : ?>
                            <a class="apl-btn-tel" href="tel:<?php echo esc_attr($contact_phone); ?>">
                                <i class="ri-phone-line"></i> <?php echo esc_html($contact_phone); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($contact_wa || $contact_phone) : ?>
                    <div class="apl-contact-box apl-contact-box--locked">
                        <a class="apl-btn-locked" href="<?php echo esc_url(home_url('/acesso?redirect=' . rawurlencode(get_permalink($classified_id)))); ?>">
                            <i class="ri-lock-2-line"></i> <?php esc_html_e('Entre para ver o contato', 'apollo-adverts'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Chat inline (se plugin chat ativo) -->
                <?php echo do_shortcode('[apollo_adverts_chat_button classified_id="' . (int) $classified_id . '"]'); ?>

            </div><!-- .apl-single-sidebar -->

        </div><!-- .apl-single-grid -->

    </div><!-- .apl-single-container -->

</main>

<?php
load_template(plugin_dir_path(__FILE__) . 'marketplace/parts/modal-disclaimer.php', true);
if (function_exists('apollo_render_document_close')) {
    apollo_render_document_close();
} else {
    ?>
</body>
</html>
    <?php
}