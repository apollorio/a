<?php

/**
 * New Home — Classifieds / Resale (tickets) Section
 *
 * On /casa this is a NON-CLICKABLE image marquee of ticket listings.
 * No card links out (nothing opens on /casa). The only control is a
 * Chat/Message button that is LOCKED for guests — only logged users can
 * reach the seller. Marquee = images scrolling; hover pauses.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$is_logged_in = is_user_logged_in();
$login_url    = home_url('/acesso');

$classifieds_query = new WP_Query(
    array(
        'post_type'      => 'classified',
        'posts_per_page' => 8,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    )
);

/* Build the item set once; the marquee prints it twice for a seamless loop. */
$mq_items = array();
if ($classifieds_query->have_posts()) {
    while ($classifieds_query->have_posts()) {
        $classifieds_query->the_post();
        $cid   = get_the_ID();
        $price = get_post_meta($cid, '_classified_price', true);
        $mq_items[] = array(
            'img'   => get_the_post_thumbnail_url($cid, 'medium') ?: 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=400&q=75',
            'title' => get_the_title(),
            'sub'   => $price ? 'R$ ' . number_format_i18n((float) $price, 0) : '—',
            'chat'  => $is_logged_in ? get_permalink() : '',
        );
    }
    wp_reset_postdata();
}
?>

<section class="section" id="resell" aria-labelledby="resell-title">
    <div class="container">
        <div class="nh-section-head ai">
            <h2 id="resell-title">Classificados</h2>
        </div>
        <p class="nh-resale-intro ai">Marketplace peer-to-peer. Identidade do vendedor visível apenas para membros.</p>

        <?php if (! empty($mq_items)) : ?>
            <div class="nh-mq ai" aria-label="<?php esc_attr_e('Ingressos à venda', 'apollo-templates'); ?>">
                <div class="nh-mq-track">
                    <?php for ($pass = 0; $pass < 2; $pass++) : ?>
                        <?php foreach ($mq_items as $it) : ?>
                            <figure class="nh-mq-card" <?php echo $pass ? 'aria-hidden="true"' : ''; ?>>
                                <img src="<?php echo esc_url($it['img']); ?>" alt="<?php echo esc_attr($it['title']); ?>" loading="lazy" />
                                <div class="nh-mq-veil" aria-hidden="true"></div>
                                <figcaption class="nh-mq-body">
                                    <span class="nh-mq-meta">
                                        <span class="nh-mq-title"><?php echo esc_html($it['title']); ?></span>
                                        <span class="nh-mq-sub"><?php echo $is_logged_in ? esc_html($it['sub']) : '● ● ●'; ?></span>
                                    </span>
                                    <?php if ($is_logged_in && $it['chat']) : ?>
                                        <a class="nh-mq-chat" href="<?php echo esc_url($it['chat']); ?>"><i class="ri-chat-3-line"></i> Chat</a>
                                    <?php else : ?>
                                        <button type="button" class="nh-mq-chat is-locked" disabled aria-label="<?php esc_attr_e('Entre para conversar', 'apollo-templates'); ?>"><i class="ri-lock-2-line"></i> Chat</button>
                                    <?php endif; ?>
                                </figcaption>
                            </figure>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>
        <?php else : ?>
            <div class="nh-empty-state ai" style="padding:40px 28px;text-align:center">
                <i class="ri-exchange-box-line" aria-hidden="true"></i>
                <p>Em breve..</p>
                <span class="nh-empty-sub">O marketplace abrirá em breve com ingressos e itens da comunidade.</span>
            </div>
        <?php endif; ?>

        <aside class="nh-disclaimer ai" role="note">
            <div class="nh-disclaimer-accent" aria-hidden="true"></div>
            <div class="nh-disclaimer-body">
                <div class="nh-disclaimer-header">
                    <i class="ri-shield-check-fill" aria-hidden="true"></i>
                    <h4><?php esc_html_e('Segurança em Primeiro Lugar', 'apollo-templates'); ?></h4>
                </div>
                <p><?php esc_html_e('Apollo é uma ponte de conexão. Não processamos pagamentos. Sempre verifique a reputação do vendedor. Encontre-se em local público.', 'apollo-templates'); ?>
                </p>
            </div>
        </aside>
    </div>
</section>
