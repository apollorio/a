<?php

/**
 * Template: Página Hub Pública — Estilo Linktree
 *
 * URL: /hub/{username}
 * Tipo: Canvas (usa wp_head/wp_footer + Apollo CDN)
 *
 * Variáveis disponíveis:
 * - $post         WP_Post do hub
 * - $hub_data     array  com bio, links, socials, theme, avatar, cover
 * - $hub_username string username
 * - $hub_owner    WP_User proprietário
 *
 * @package Apollo\Hub
 */

if (! defined('ABSPATH')) {
    exit;
}

global $post;

$hub_data     = apollo_hub_get_data($post->ID);
$hub_username = $post->post_name;
$hub_owner    = get_userdata($post->post_author);

// Avatar: prioridade attachment > user meta > gravatar
$avatar_url = '';
if ($hub_data['avatar']) {
    $avatar_url = wp_get_attachment_image_url($hub_data['avatar'], 'medium_large');
}
if (! $avatar_url && $hub_owner) {
    $avatar_url = get_user_meta($hub_owner->ID, '_apollo_avatar_url', true);
}
if (! $avatar_url && $hub_owner) {
    $avatar_url = get_avatar_url($hub_owner->ID, array('size' => 300));
}

// Cover
$cover_url = '';
if ($hub_data['cover']) {
    $cover_url = wp_get_attachment_image_url($hub_data['cover'], 'full');
}

// Theme
$theme = in_array($hub_data['theme'], array_keys(APOLLO_HUB_THEMES), true) ? $hub_data['theme'] : 'dark';

// Nome a exibir
$display_name = get_the_title($post->ID);

// Social Name via apollo-login (se disponível)
if ($hub_owner) {
    $social_name = get_user_meta($hub_owner->ID, '_apollo_social_name', true);
    if ($social_name) {
        $display_name = $social_name;
    }
}

// Enfileira assets (handles registered for dependency resolution)
wp_enqueue_style('apollo-hub');
wp_enqueue_script('apollo-hub');

// SEO básico via apollo-seo ou meta tags diretas
$hub_url     = get_permalink($post->ID);
$description = $hub_data['bio'] ?: sprintf(esc_html__('Hub de %s no Apollo.', 'apollo-hub'), $display_name);

$hub_v = defined('APOLLO_HUB_VERSION') ? APOLLO_HUB_VERSION : '1.0.0';

ob_start();
?>
    <meta name="description" content="<?php echo esc_attr($description); ?>">
    <meta property="og:title" content="<?php echo esc_attr($display_name); ?> — Apollo Hub">
    <meta property="og:description" content="<?php echo esc_attr($description); ?>">
    <meta property="og:url" content="<?php echo esc_url($hub_url); ?>">
    <?php if ($avatar_url) : ?>
        <meta property="og:image" content="<?php echo esc_url($avatar_url); ?>">
        <meta name="twitter:image" content="<?php echo esc_url($avatar_url); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo esc_attr($display_name); ?> — Apollo Hub">
    <link rel="stylesheet" href="<?php echo esc_url(APOLLO_HUB_URL . 'assets/css/hub.css?v=' . $hub_v); ?>">
    <script src="<?php echo esc_url(APOLLO_HUB_URL . 'assets/js/hub.js?v=' . $hub_v); ?>" defer></script>
    <?php if ($hub_data['custom_css'] && current_user_can('edit_post', $post->ID)) : ?>
        <style id="hub-custom-css">
            <?php
            $safe_css = wp_strip_all_tags($hub_data['custom_css']);
            $safe_css = preg_replace('/@import\b|expression\s*\(|javascript\s*:/i', '/* blocked */', $safe_css);
            echo $safe_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS block sanitized above, gated by current_user_can
            ?>
        </style>
    <?php endif; ?>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => $display_name . ' — Apollo Hub',
            'extra_head' => $extra_head,
            'html_attrs' => 'data-hub-theme="' . esc_attr($theme) . '"',
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-hub-theme="<?php echo esc_attr($theme); ?>">
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body class="apollo-hub-page apollo-hub-theme-<?php echo esc_attr($theme); ?>">

    <div class="hub-root" id="hub-root" data-username="<?php echo esc_attr($hub_username); ?>"
        data-a-user="<?php echo esc_attr((string) ($hub_owner->ID ?? 0)); ?>"
        data-a-component="hub">

        <!-- COVER -->
        <?php if ($cover_url) : ?>
            <div class="hub-cover" style="--hub-cover: url('<?php echo esc_url($cover_url); ?>');" aria-hidden="true">
                <div class="hub-cover__overlay"></div>
            </div>
        <?php endif; ?>

        <!-- CARD PRINCIPAL -->
        <main class="hub-card" role="main">

            <!-- CABEÇALHO: avatar + nome + bio -->
            <header class="hub-header">
                <div class="hub-avatar-wrap">
                    <?php
                    $avatar_type = $hub_data['avatar_type'] ?? 'normal';

                    if ($avatar_type === 'morphism' && $avatar_url) :
                    ?>
                        <div class="avatar-morphism-container">
                            <div class="avatar-morphism-box">
                                <div class="avatar-morphism-spin">
                                    <div class="avatar-morphism-shape" style="background-image: url(<?php echo esc_attr($avatar_url); ?>); background-size: cover; background-position: center;">
                                        <div class="avatar-morphism-image" style="background-image: url(<?php echo esc_attr($avatar_url); ?>);"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($avatar_url) : ?>
                        <img
                            class="hub-avatar"
                            src="<?php echo esc_url($avatar_url); ?>"
                            alt="<?php echo esc_attr($display_name); ?>"
                            width="96"
                            height="96"
                            loading="eager">
                    <?php else : ?>
                        <div class="hub-avatar hub-avatar--placeholder">
                            <i class="ri-user-3-line" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Badge de verificação (apollo-users integração)
                    if ($hub_owner) {
                        $is_verified = get_user_meta($hub_owner->ID, '_apollo_verified', true);
                        if ($is_verified) {
                            echo '<span class="hub-badge hub-badge--verified" title="' . esc_attr__('Verificado', 'apollo-hub') . '"><i class="ri-verified-badge-fill" aria-hidden="true"></i></span>';
                        }
                    }
                    ?>
                </div>

                <h1 class="hub-name"><?php echo esc_html($display_name); ?></h1>
                <?php if ($hub_username) : ?>
                    <p class="hub-username">@<?php echo esc_html($hub_username); ?></p>
                <?php endif; ?>

                <?php if ($hub_data['bio']) : ?>
                    <p class="hub-bio"><?php echo esc_html($hub_data['bio']); ?></p>
                <?php endif; ?>
            </header>

            <!-- BLOCOS DO HUB (block-based content) -->
            <?php
            $blocks = apollo_hub_get_blocks($post->ID);
            if (! empty($blocks)) :
            ?>
                <section class="hub-blocks" aria-label="<?php esc_attr_e('Conteúdo', 'apollo-hub'); ?>">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BlockRenderer handles escaping internally
                    echo \Apollo\Hub\BlockRenderer::render($blocks, $theme);
                    ?>
                </section>
            <?php else : ?>

                <!-- FALLBACK: Redes sociais (legacy _hub_socials) -->
                <?php if (! empty($hub_data['socials'])) : ?>
                    <nav class="hub-socials" aria-label="<?php esc_attr_e('Redes sociais', 'apollo-hub'); ?>">
                        <ul class="hub-socials__list">
                            <?php
                            foreach ($hub_data['socials'] as $social) :
                                $social_network = sanitize_key($social['network'] ?? '');
                                $social_url     = esc_url($social['url'] ?? '');
                                $social_icon    = esc_attr(APOLLO_HUB_SOCIAL_ICONS[$social_network] ?? 'ri-global-line');
                                if (! $social_url) {
                                    continue;
                                }
                            ?>
                                <li>
                                    <a
                                        href="<?php echo esc_url( $social_url ); ?>"
                                        class="hub-social-btn hub-social-btn--<?php echo esc_attr($social_network); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="<?php echo esc_attr(ucfirst($social_network)); ?>">
                                        <i class="<?php echo esc_attr( $social_icon ); ?>" aria-hidden="true"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- FALLBACK: Links (legacy _hub_links) -->
                <section class="hub-links" aria-label="<?php esc_attr_e('Links', 'apollo-hub'); ?>">
                    <?php
                    $links = array_filter($hub_data['links'], fn($l) => ! empty($l['url']) && ($l['active'] ?? true));
                    if (empty($links)) :
                    ?>
                        <p class="hub-links__empty"><?php esc_html_e('Nenhum link adicionado ainda.', 'apollo-hub'); ?></p>
                    <?php else : ?>
                        <ul class="hub-links__list">
                            <?php
                            foreach ($links as $link) :
                                $link_url   = esc_url($link['url']);
                                $link_title = esc_html($link['title'] ?? $link_url);
                                $link_icon  = esc_attr($link['icon'] ?? 'ri-link-m');
                                $is_event   = str_contains($link_url, '/event/') || str_contains($link_url, '/evento/');
                            ?>
                                <li class="hub-link-item<?php echo $is_event ? ' hub-link-item--event' : ''; ?>" data-reveal-up>
                                    <a
                                        href="<?php echo $link_url; ?>"
                                        class="hub-link-btn"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        data-apollo-track="hub_link_click"
                                        data-hub-username="<?php echo esc_attr($hub_username); ?>">
                                        <?php if ($link_icon) : ?>
                                            <span class="hub-link-btn__icon" aria-hidden="true"><i class="<?php echo $link_icon; ?>"></i></span>
                                        <?php endif; ?>
                                        <span class="hub-link-btn__title"><?php echo $link_title; ?></span>
                                        <span class="hub-link-btn__arrow" aria-hidden="true"><i class="ri-arrow-right-up-line"></i></span>
                                    </a>
                                    <div class="hub-link-share" aria-label="<?php esc_attr_e('Compartilhar este link', 'apollo-hub'); ?>">
                                        <button
                                            class="hub-link-share__toggle js-hub-share-toggle"
                                            data-url="<?php echo $link_url; ?>"
                                            data-text="<?php echo esc_attr($link_title); ?>"
                                            aria-label="<?php esc_attr_e('Compartilhar', 'apollo-hub'); ?>">
                                            <i class="ri-share-forward-line" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

            <?php endif; ?>

            <!-- EVENTOS DO USUÁRIO (apollo-events integração) -->
            <?php
            if ($hub_owner && post_type_exists('event')) :
                $user_events = get_posts(
                    array(
                        'post_type'      => 'event',
                        'post_status'    => 'publish',
                        'author'         => $hub_owner->ID,
                        'posts_per_page' => 3,
                        'orderby'        => 'meta_value',
                        'meta_key'       => '_event_date_start',
                        'order'          => 'DESC',
                    )
                );

                if (! empty($user_events)) :
            ?>
                    <section class="hub-events" aria-label="<?php esc_attr_e('Eventos', 'apollo-hub'); ?>">
                        <h2 class="hub-section-title">
                            <i class="ri-calendar-line" aria-hidden="true"></i>
                            <?php esc_html_e('Eventos', 'apollo-hub'); ?>
                        </h2>
                        <ul class="hub-events__list">
                            <?php
                            foreach ($user_events as $event) :
                                $event_url   = get_permalink($event->ID);
                                $event_title = get_the_title($event->ID);
                                $event_date  = get_post_meta($event->ID, '_event_date_start', true);
                                $event_thumb = get_the_post_thumbnail_url($event->ID, 'thumbnail');
                                $event_loc   = get_post_meta($event->ID, '_event_loc_name', true);
                                $share_urls  = apollo_hub_event_share_urls($event->ID);
                            ?>
                                <li class="hub-event-card" data-reveal-up>
                                    <?php if ($event_thumb) : ?>
                                        <a href="<?php echo esc_url($event_url); ?>" class="hub-event-card__thumb" target="_blank" rel="noopener noreferrer" tabindex="-1">
                                            <img src="<?php echo esc_url($event_thumb); ?>" alt="<?php echo esc_attr($event_title); ?>" loading="lazy">
                                        </a>
                                    <?php endif; ?>
                                    <div class="hub-event-card__info">
                                        <a href="<?php echo esc_url($event_url); ?>" class="hub-event-card__title" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html($event_title); ?>
                                        </a>
                                        <?php if ($event_date) : ?>
                                            <time class="hub-event-card__date" datetime="<?php echo esc_attr($event_date); ?>">
                                                <i class="ri-calendar-event-line" aria-hidden="true"></i>
                                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>
                                            </time>
                                        <?php endif; ?>
                                        <?php if ($event_loc) : ?>
                                            <span class="hub-event-card__loc">
                                                <i class="ri-map-pin-line" aria-hidden="true"></i>
                                                <?php echo esc_html($event_loc); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Share do evento -->
                                    <?php if (! empty($share_urls)) : ?>
                                        <div class="hub-event-card__share">
                                            <?php
                                            foreach ($share_urls as $network => $share_url) :
                                                $icons = array(
                                                    'whatsapp' => 'ri-whatsapp-line',
                                                    'telegram' => 'ri-telegram-line',
                                                    'twitter'  => 'ri-twitter-x-line',
                                                    'facebook' => 'ri-facebook-line',
                                                );
                                                $icon  = $icons[$network] ?? 'ri-share-line';
                                            ?>
                                                <a
                                                    href="<?php echo esc_url($share_url); ?>"
                                                    class="hub-share-btn hub-share-btn--<?php echo esc_attr($network); ?>"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    aria-label="<?php echo esc_attr(sprintf(__('Compartilhar via %s', 'apollo-hub'), ucfirst($network))); ?>">
                                                    <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                                                </a>
                                            <?php endforeach; ?>
                                            <button
                                                class="hub-share-btn hub-share-btn--copy js-hub-copy-link"
                                                data-url="<?php echo esc_url(get_permalink($event->ID)); ?>"
                                                aria-label="<?php esc_attr_e('Copiar link', 'apollo-hub'); ?>">
                                                <i class="ri-link-m" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php
                            endforeach;
                            wp_reset_postdata();
                            ?>
                        </ul>
                    </section>
            <?php
                endif;
            endif;
            ?>

            <!-- SHARE DO HUB (compartilhar a página do hub) -->
            <footer class="hub-footer">
                <div class="hub-footer__share">
                    <button class="hub-footer__share-toggle js-hub-page-share" data-url="<?php echo esc_url($hub_url); ?>" data-text="<?php echo esc_attr(sprintf(__('Confira o Hub de %s no Apollo', 'apollo-hub'), $display_name)); ?>">
                        <i class="ri-share-line" aria-hidden="true"></i>
                        <?php esc_html_e('Compartilhar Hub', 'apollo-hub'); ?>
                    </button>
                    <div class="hub-footer__share-panel js-hub-share-panel" aria-hidden="true">
                        <?php
                        $hub_share_text = sprintf(__('Confira o Hub de %s no Apollo', 'apollo-hub'), $display_name);
                        $share_networks = array(
                            'whatsapp' => array('ri-whatsapp-line', apollo_hub_share_url($hub_url, $hub_share_text, 'whatsapp')),
                            'telegram' => array('ri-telegram-line', apollo_hub_share_url($hub_url, $hub_share_text, 'telegram')),
                            'twitter'  => array('ri-twitter-x-line', apollo_hub_share_url($hub_url, $hub_share_text, 'twitter')),
                            'facebook' => array('ri-facebook-line', apollo_hub_share_url($hub_url, $hub_share_text, 'facebook')),
                        );
                        foreach ($share_networks as $network => list($icon, $share_url)) :
                        ?>
                            <a href="<?php echo esc_url($share_url); ?>" class="hub-share-btn hub-share-btn--<?php echo esc_attr($network); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(ucfirst($network)); ?>">
                                <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                            </a>
                        <?php endforeach; ?>
                        <button class="hub-share-btn hub-share-btn--copy js-hub-copy-link" data-url="<?php echo esc_url($hub_url); ?>">
                            <i class="ri-link-m" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <?php if (is_user_logged_in() && $hub_owner && get_current_user_id() === (int) $hub_owner->ID) : ?>
                    <a href="<?php echo esc_url(home_url('/' . APOLLO_HUB_EDIT_SLUG)); ?>" class="hub-edit-btn">
                        <i class="ri-edit-line" aria-hidden="true"></i>
                        <?php esc_html_e('Editar Hub', 'apollo-hub'); ?>
                    </a>
                <?php endif; ?>

                <p class="hub-footer__brand">
                    <a href="<?php echo esc_url(home_url()); ?>" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e('Apollo::Rio', 'apollo-hub'); ?>
                    </a>
                </p>
            </footer>

        </main><!-- .hub-card -->

    </div><!-- .hub-root -->

<?php
if (function_exists('apollo_render_document_close')) {
    apollo_render_document_close();
} else {
    ?>
</body>

</html>
    <?php
}