<?php

/**
 * Integrations — Apollo Hub
 *
 * Integra com:
 * - apollo-users: auto-provisionar hub quando usuário é criado
 * - apollo-events: exibir eventos do usuário no hub
 * - apollo-social: botões de compartilhamento nos posts
 * - apollo-core: fire hooks no ciclo de vida do hub
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if (! defined('ABSPATH')) {
    exit;
}

class Integrations
{


    public function __construct()
    {
        // Apollo frontend registration: use namespaced hook (has full profile meta available).
        // This fires AFTER user_register, so we track which users were already provisioned.
        add_action('apollo/login/registered', array($this, 'auto_provision_hub'), 20);

        // WP admin-created users: late-fired user_register fallback.
        // Must be at priority 99 so it runs after apollo/login/registered would have fired.
        // The auto_provision_hub method checks for existing hub, so double-fire is safe,
        // but we skip it entirely when apollo/login/registered already ran for this user.
        add_action('user_register', array($this, 'auto_provision_hub_fallback'), 99);

        // Adiciona link "Ver Hub" na barra de admin
        add_action('admin_bar_menu', array($this, 'admin_bar_link'), 100);

        // Compartilhamento de eventos: adiciona share buttons no single event
        add_action('apollo/events/after_content', array($this, 'render_event_share'), 10, 1);

        // Integração com apollo-social: share no feed
        add_filter('apollo/social/share_networks', array($this, 'add_hub_share_networks'));

        // Adicionar permalink do hub no perfil do usuário
        add_filter('apollo/users/profile_links', array($this, 'add_hub_profile_link'), 10, 2);
    }

    /**
     * Fallback: creates hub for admin-created WP users.
     *
     * Skipped when `apollo/login/registered` already provisioned the hub
     * for the same user (avoids double-fire during Apollo front-end registration).
     *
     * @param int $user_id User ID.
     */
    public function auto_provision_hub_fallback(int $user_id): void
    {
        // apollo/login/registered fires BEFORE user_register (priority 20 vs 99),
        // so if it ran, did_action > 0 and the hub already exists.
        if (did_action('apollo/login/registered')) {
            return; // Already handled by the Apollo hook.
        }
        $this->auto_provision_hub($user_id);
    }

    /**
     * Cria post hub para novo usuário (opcional — sem bio/links por padrão).
     *
     * @param int $user_id User ID.
     */
    public function auto_provision_hub(int $user_id): void
    {
        $user = get_userdata($user_id);
        if (! $user) {
            return;
        }

        // Verifica se já tem hub
        $existing = apollo_hub_get_by_username($user->user_login);
        if ($existing) {
            return;
        }

        // Só cria hub se configuração permitir auto-provision
        if (! apply_filters('apollo/hub/auto_provision', true, $user_id)) {
            return;
        }

        $post_id = wp_insert_post(
            array(
                'post_type'   => APOLLO_HUB_CPT,
                'post_title'  => $user->display_name ?: $user->user_login,
                'post_name'   => $user->user_login,
                'post_status' => 'publish',
                'post_author' => $user_id,
            )
        );

        if ($post_id && ! is_wp_error($post_id)) {
            update_post_meta($post_id, '_hub_theme', 'dark');

            // Herda avatar do instagram se existir (apollo-login)
            $avatar_url = get_user_meta($user_id, '_apollo_avatar_url', true);
            if ($avatar_url) {
                update_post_meta($post_id, '_hub_avatar', 0); // 0 = usar avatar do usuário
            }

            do_action('apollo/hub/created', $post_id, $user_id);
        }
    }

    /**
     * Adiciona link "Ver Hub" na barra de admin para usuário logado.
     *
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar.
     */
    public function admin_bar_link(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (! is_user_logged_in()) {
            return;
        }

        $user    = wp_get_current_user();
        $hub_url = home_url('/hub/' . $user->user_login);

        $wp_admin_bar->add_node(
            array(
                'id'    => 'apollo-hub-view',
                'title' => '<i class="ri-connector-line"></i> ' . __('Meu Hub', 'apollo-hub'),
                'href'  => $hub_url,
                'meta'  => array('target' => '_blank'),
            )
        );

        $wp_admin_bar->add_node(
            array(
                'id'     => 'apollo-hub-edit',
                'title'  => __('Editar Hub', 'apollo-hub'),
                'href'   => home_url('/' . APOLLO_HUB_EDIT_SLUG),
                'parent' => 'apollo-hub-view',
            )
        );
    }

    /**
     * Renderiza botões de compartilhamento após conteúdo de evento.
     *
     * @param int $event_id Post ID do evento.
     */
    public function render_event_share(int $event_id): void
    {
        $share_urls = apollo_hub_event_share_urls($event_id);
        if (empty($share_urls)) {
            return;
        }

        echo '<div class="apollo-hub-share apollo-hub-share--event">';
        echo '<span class="apollo-hub-share__label">' . esc_html__('Compartilhar evento:', 'apollo-hub') . '</span>';
        echo '<div class="apollo-hub-share__buttons">';

        $icons = array(
            'whatsapp' => 'ri-whatsapp-line',
            'telegram' => 'ri-telegram-line',
            'twitter'  => 'ri-twitter-x-line',
            'facebook' => 'ri-facebook-line',
        );

        foreach ($share_urls as $network => $url) {
            $icon = $icons[$network] ?? 'ri-share-line';
            printf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" class="apollo-hub-share__btn apollo-hub-share__btn--%s" aria-label="%s"><i class="%s"></i></a>',
                esc_url($url),
                esc_attr($network),
                esc_attr(ucfirst($network)),
                esc_attr($icon)
            );
        }

        // Botão copiar link
        printf(
            '<button class="apollo-hub-share__btn apollo-hub-share__btn--copy" data-url="%s" aria-label="%s"><i class="ri-link-m"></i></button>',
            esc_url(get_permalink($event_id)),
            esc_attr__('Copiar link', 'apollo-hub')
        );

        echo '</div></div>';
    }

    /**
     * Adiciona redes de compartilhamento padrão para apollo-social.
     *
     * @param  array $networks Redes atuais.
     * @return array
     */
    public function add_hub_share_networks(array $networks): array
    {
        $networks['whatsapp'] = 'WhatsApp';
        $networks['telegram'] = 'Telegram';
        $networks['twitter']  = 'X (Twitter)';
        return $networks;
    }

    /**
     * Adiciona link do hub no perfil do usuário (apollo-users).
     *
     * @param  array $links   Links do perfil.
     * @param  int   $user_id User ID.
     * @return array
     */
    public function add_hub_profile_link(array $links, int $user_id): array
    {
        $user = get_userdata($user_id);
        if (! $user) {
            return $links;
        }

        $hub     = apollo_hub_get_by_username($user->user_login);
        $hub_url = $hub ? get_permalink($hub->ID) : home_url('/hub/' . $user->user_login);

        $links['hub'] = array(
            'label' => __('Hub', 'apollo-hub'),
            'url'   => $hub_url,
            'icon'  => 'ri-connector-line',
        );

        return $links;
    }
}
