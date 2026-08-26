<?php

/**
 * Apollo Ecosystem Integrations
 *
 * Bridges to: apollo-fav, apollo-wow, apollo-notif, apollo-social, apollo-chat,
 * apollo-admin (dashboard), apollo-templates, apollo-shortcodes, apollo-users.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

/*
========================================================================
 * 1. APOLLO FAV — Favorite Classifieds
 * Adapted from apollo-fav integration pattern
 * ======================================================================== */

/**
 * Register classified as favoriteable post type
 */
function apollo_adverts_fav_register(): void
{
    if (! has_filter('apollo/fav/supported_types')) {
        return;
    }

    add_filter(
        'apollo/fav/supported_types',
        function (array $types): array {
            $types[] = APOLLO_CPT_CLASSIFIED;
            return array_unique($types);
        }
    );
}
add_action('init', 'apollo_adverts_fav_register', 15);

/**
 * Show fav button on classified single
 */
function apollo_adverts_fav_button(int $post_id): string
{
    if (! function_exists('apollo_fav_button') || ! apollo_adverts_config('fav_enabled', true)) {
        return '';
    }
    return apollo_fav_button($post_id);
}

/*
========================================================================
 * 2. APOLLO WOW — Reactions on Classifieds
 * Adapted from apollo-wow integration pattern
 * ======================================================================== */

/**
 * Register classified as reactable post type
 */
function apollo_adverts_wow_register(): void
{
    if (! has_filter('apollo/wow/supported_types')) {
        return;
    }

    add_filter(
        'apollo/wow/supported_types',
        function (array $types): array {
            $types[] = APOLLO_CPT_CLASSIFIED;
            return array_unique($types);
        }
    );
}
add_action('init', 'apollo_adverts_wow_register', 15);

/**
 * Show wow reactions on classified single
 */
function apollo_adverts_wow_reactions(int $post_id): string
{
    if (! function_exists('apollo_wow_reactions') || ! apollo_adverts_config('wow_enabled', true)) {
        return '';
    }
    return apollo_wow_reactions($post_id);
}

/*
========================================================================
 * 3. APOLLO NOTIF — Notifications
 * Adapted from apollo-notif integration pattern
 * ======================================================================== */

/**
 * Notify admin on new classified
 */
function apollo_adverts_notif_new_ad(int $post_id, array $values): void
{
    if (! function_exists('apollo_notif_send') || ! apollo_adverts_config('notify_admin_new', true)) {
        return;
    }

    $post   = get_post($post_id);
    $author = get_userdata($post->post_author);

    apollo_notif_send(
        array(
            'type'        => 'classified_new',
            'user_id'     => 0, // admin
            'actor_id'    => $post->post_author,
            'object_id'   => $post_id,
            'object_type' => APOLLO_CPT_CLASSIFIED,
            'message'     => sprintf(
                /* translators: 1: user name, 2: ad title */
                __('%1$s criou um novo anúncio: %2$s', 'apollo-adverts'),
                $author ? $author->display_name : __('Usuário', 'apollo-adverts'),
                $post->post_title
            ),
        )
    );
}
add_action('apollo/classifieds/created', 'apollo_adverts_notif_new_ad', 10, 2);

/**
 * Notify author on expiration
 */
function apollo_adverts_notif_expired(int $post_id): void
{
    if (! function_exists('apollo_notif_send') || ! apollo_adverts_config('notify_author_expire', true)) {
        return;
    }

    $post = get_post($post_id);
    if (! $post) {
        return;
    }

    apollo_notif_send(
        array(
            'type'        => 'classified_expired',
            'user_id'     => $post->post_author,
            'actor_id'    => 0,
            'object_id'   => $post_id,
            'object_type' => APOLLO_CPT_CLASSIFIED,
            'message'     => sprintf(
                /* translators: %s: ad title */
                __('Seu anúncio "%s" expirou. Renove-o para mantê-lo ativo.', 'apollo-adverts'),
                $post->post_title
            ),
        )
    );
}
add_action('apollo/classifieds/expired', 'apollo_adverts_notif_expired');

/**
 * Notify author on upcoming expiration
 */
function apollo_adverts_notif_expiring_soon(int $post_id, int $days_left): void
{
    if (! function_exists('apollo_notif_send')) {
        return;
    }

    $post = get_post($post_id);
    if (! $post) {
        return;
    }

    apollo_notif_send(
        array(
            'type'        => 'classified_expiring',
            'user_id'     => $post->post_author,
            'actor_id'    => 0,
            'object_id'   => $post_id,
            'object_type' => APOLLO_CPT_CLASSIFIED,
            'message'     => sprintf(
                /* translators: 1: ad title, 2: days remaining */
                __('Seu anúncio "%1$s" expira em %2$d dia(s).', 'apollo-adverts'),
                $post->post_title,
                $days_left
            ),
        )
    );
}

/*
========================================================================
 * 4. APOLLO SOCIAL — Activity Feed
 * Adapted from apollo-social activity posting pattern
 * ======================================================================== */

/**
 * Post activity on new classified — uses Apollo Social's apollo_log_activity()
 */
function apollo_adverts_social_new_ad(int $post_id): void
{
    if (! function_exists('apollo_log_activity')) {
        return;
    }

    $post = get_post($post_id);
    if (! $post) {
        return;
    }
    $author = get_userdata($post->post_author);
    if (! $author) {
        return;
    }

    $price      = apollo_adverts_get_the_price($post_id);
    $price_text = $price ? sprintf(__('Valor: %s', 'apollo-adverts'), $price) : '';

    apollo_log_activity(
        array(
            'user_id'      => $post->post_author,
            'component'    => 'classifieds',
            'type'         => 'classified_created',
            'action_text'  => sprintf('publicou o anúncio "%s"', $post->post_title),
            'content'      => $price_text,
            'item_id'      => $post_id,
            'primary_link' => get_permalink($post_id),
        )
    );
}
add_action('apollo/classifieds/created', 'apollo_adverts_social_new_ad');
add_action(
    'publish_classified',
    function (int $post_id, \WP_Post $post) {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        apollo_adverts_social_new_ad($post_id);
    },
    10,
    2
);

/*
========================================================================
 * 5. APOLLO CHAT — Contact via Chat
 * Adapted from apollo-chat integration pattern
 * ======================================================================== */

/**
 * Render "Contatar via Chat" button
 */
/**
 * Render "Contatar via Chat" button with full AJAX integration.
 * Creates thread on-the-fly via REST + redirects to /mensagens/{thread_id}.
 * If apollo-chat not active, renders a fallback disabled button.
 */
function apollo_adverts_chat_button(int $post_id): string
{
    $post = get_post($post_id);
    if (! $post) {
        return '';
    }

    // Don't show chat button to the ad owner
    if (is_user_logged_in() && (int) $post->post_author === get_current_user_id()) {
        return '';
    }

    // Not logged in: show button that redirects to login
    if (! is_user_logged_in()) {
        return sprintf(
            '<a href="%s" class="button apollo-adverts-chat-btn apollo-adverts-chat-login">' .
                '<i class="ri-chat-3-line"></i> %s</a>',
            esc_url(home_url('/acesso?redirect=' . rawurlencode((string) get_permalink($post_id)))),
            esc_html__('Faça login para contatar', 'apollo-adverts')
        );
    }

    // ── HOSTEL: the CTA was never a chat ────────────────────────────────────
    if (function_exists('apollo_adverts_is_hostel_listing') && apollo_adverts_is_hostel_listing($post_id)) {
        $hostel_url = (string) get_post_meta($post_id, '_classified_hostel_url', true);

        if ($hostel_url) {
            return sprintf(
                '<a href="%s" class="button apollo-adverts-chat-btn apollo-adverts-hostel-btn"' .
                    ' target="_blank" rel="noopener noreferrer nofollow">' .
                    '<i class="ri-calendar-check-line"></i> %s</a>',
                esc_url($hostel_url),
                esc_html__('Reservar no site do hostel', 'apollo-adverts')
            );
        }
    }

    // ── EVERY OTHER ADVERT: the gate, and it is a real page ─────────────────
    if (
        function_exists('apollo_safety_applies') && apollo_safety_applies($post_id)
        && function_exists('apollo_adverts_safety_cleared') && ! apollo_adverts_safety_cleared($post_id)
    ) {
        $gate = function_exists('apollo_adverts_contact_url')
            ? apollo_adverts_contact_url($post_id)
            : apollo_safety_url($post_id, (string) get_permalink($post_id) . '#contato');

        return sprintf(
            '<a href="%s" class="button apollo-adverts-chat-btn apollo-adverts-safety-btn">' .
                '<i class="ri-shield-check-line"></i> <span>%s</span></a>',
            esc_url($gate),
            esc_html__('Falar com o vendedor', 'apollo-adverts')
        );
    }

    // Chat plugin active: gate already cleared for this member/advert pair.
    if (function_exists('apollo_chat_thread_url') || function_exists('apollo_chat_find_or_create_thread')) {
        $author      = get_userdata($post->post_author);
        $author_name = $author ? $author->display_name : __('Anunciante', 'apollo-adverts');

        wp_enqueue_style('apollo-adverts-marketplace');
        wp_enqueue_script('apollo-adverts-marketplace');

        add_action(
            'wp_footer',
            function () use ($post_id) {
                static $modal_rendered = false;
                if ($modal_rendered) {
                    return;
                }
                $modal_rendered = true;
                $tmpl = APOLLO_ADVERTS_DIR . 'templates/marketplace/parts/modal-disclaimer.php';
                if (file_exists($tmpl)) {
                    load_template($tmpl, false, array('post_id' => $post_id));
                }
            },
            99
        );

        return sprintf(
            '<button type="button" class="button apollo-adverts-chat-btn btn-open-modal"
				data-a-user="%d" data-classified-id="%d"
				data-rest-url="%s" data-nonce="%s"
				aria-label="%s">
				<i class="ri-chat-3-line"></i>
				<span>%s</span>
				<span class="chat-author-name">%s</span>
			</button>',
            (int) $post->post_author,
            (int) $post_id,
            esc_url(rest_url('apollo/v1/chat/thread-for-context')),
            esc_attr(is_user_logged_in() ? wp_create_nonce('wp_rest') : ''),
            esc_attr(sprintf(__('Contatar %s via Chat', 'apollo-adverts'), $author_name)),
            esc_html__('Conversar com', 'apollo-adverts'),
            esc_html($author_name)
        );
    }

    // Chat plugin not active: fallback
    return sprintf(
        '<span class="button apollo-adverts-chat-btn disabled" style="opacity:.5;cursor:not-allowed;">' .
            '<i class="ri-chat-3-line"></i> %s</span>',
        esc_html__('Chat indisponível', 'apollo-adverts')
    );
}

/**
 * Shortcode wrapper — [apollo_adverts_chat_button classified_id="123"]
 *
 * @param array $atts Shortcode attributes.
 */
function apollo_adverts_chat_button_shortcode($atts = array()): string
{
    $atts = shortcode_atts(
        array(
            'classified_id' => 0,
            'id'            => 0,
        ),
        $atts,
        'apollo_adverts_chat_button'
    );
    $id = absint($atts['classified_id'] ?: $atts['id']);
    return $id ? apollo_adverts_chat_button($id) : '';
}
add_shortcode('apollo_adverts_chat_button', 'apollo_adverts_chat_button_shortcode');

/*
========================================================================
 * 6. APOLLO ADMIN — Dashboard Tab
 * Adapted from apollo-admin dashboard tab pattern
 * ======================================================================== */

/**
 * Register dashboard tab with apollo-admin
 */
function apollo_adverts_admin_dashboard_tab(array $tabs): array
{
    $tabs['classifieds'] = array(
        'label'    => __('Anúncios', 'apollo-adverts'),
        'icon'     => 'dashicons-megaphone',
        'callback' => 'apollo_adverts_admin_dashboard_tab_content',
        'order'    => 40,
    );
    return $tabs;
}
add_filter('apollo/admin/dashboard_tabs', 'apollo_adverts_admin_dashboard_tab', 10);

/**
 * Dashboard tab content: quick stats
 */
function apollo_adverts_admin_dashboard_tab_content(): void
{
    $total   = wp_count_posts(APOLLO_CPT_CLASSIFIED);
    $active  = $total->publish ?? 0;
    $pending = $total->pending ?? 0;
    $expired = isset($total->expired) ? $total->expired : 0;

?>
    <div class="apollo-admin-stats-grid">
        <div class="stat-card">
            <span class="stat-number"><?php echo esc_html((string) $active); ?></span>
            <span class="stat-label"><?php esc_html_e('Ativos', 'apollo-adverts'); ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo esc_html((string) $pending); ?></span>
            <span class="stat-label"><?php esc_html_e('Pendentes', 'apollo-adverts'); ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo esc_html((string) $expired); ?></span>
            <span class="stat-label"><?php esc_html_e('Expirados', 'apollo-adverts'); ?></span>
        </div>
    </div>
<?php
}

/*
========================================================================
 * 7. APOLLO TEMPLATES — Template Override Support
 * ======================================================================== */

/**
 * Register classifieds templates with apollo-templates
 */
function apollo_adverts_templates_register(array $templates): array
{
    $templates['apollo-adverts'] = array(
        'list.php'      => __('Lista de Anúncios', 'apollo-adverts'),
        'list-item.php' => __('Item da Lista', 'apollo-adverts'),
        'single.php'    => __('Anúncio Individual', 'apollo-adverts'),
        'form.php'      => __('Formulário', 'apollo-adverts'),
        'manage.php'    => __('Gerenciar Anúncios', 'apollo-adverts'),
    );
    return $templates;
}
add_filter('apollo/templates/registered', 'apollo_adverts_templates_register');

/*
========================================================================
 * 8. APOLLO SHORTCODES — Register with shortcodes registry
 * ======================================================================== */

/**
 * Register classifieds shortcodes with apollo-shortcodes
 */
function apollo_adverts_shortcodes_register(array $shortcodes): array
{
    $shortcodes['apollo_classifieds']     = array(
        'label'       => __('Lista de Anúncios', 'apollo-adverts'),
        'description' => __('Exibe uma lista de anúncios classificados com filtros', 'apollo-adverts'),
        'atts'        => array('limit', 'domain', 'intent', 'orderby', 'order', 'featured'),
    );
    $shortcodes['apollo_classified']      = array(
        'label'       => __('Anúncio Individual', 'apollo-adverts'),
        'description' => __('Exibe um único anúncio', 'apollo-adverts'),
        'atts'        => array('id'),
    );
    $shortcodes['apollo_classified_form'] = array(
        'label'       => __('Formulário de Anúncio', 'apollo-adverts'),
        'description' => __('Formulário para criar/editar anúncios', 'apollo-adverts'),
        'atts'        => array('edit_id'),
    );
    return $shortcodes;
}
add_filter('apollo/shortcodes/registered', 'apollo_adverts_shortcodes_register');

/*
========================================================================
 * 9. APOLLO USERS — User profile ad count
 * ======================================================================== */

/**
 * Add ad count to user profile data
 */
function apollo_adverts_users_profile_data(array $data, int $user_id): array
{
    $data['classifieds_count'] = apollo_adverts_user_ad_count($user_id);
    return $data;
}
add_filter('apollo/users/profile_data', 'apollo_adverts_users_profile_data', 10, 2);
