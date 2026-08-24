<?php

/**
 * Plugin — Singleton orquestrador
 *
 * @package Apollo\DJs
 */

namespace Apollo\DJs;

if (! \defined('ABSPATH')) {
    exit;
}

class Plugin
{

    private static ?Plugin $instance = null;

    private Registry $registry;
    private Shortcodes $shortcodes;
    private TemplateLoader $template_loader;
    private Integrations $integrations;

    public function __construct()
    {
        if (null !== self::$instance) {
            return;
        }
        self::$instance = $this;

        $this->registry        = new Registry();
        $this->shortcodes      = new Shortcodes();
        $this->template_loader = new TemplateLoader();
        $this->integrations    = new Integrations();

        if (is_admin()) {
            new Admin\Dashboard();

            // Admin\Metabox() superseded 2026-07-28 by apollo-lux-panels' DjPanel,
            // which now covers every field it registered (parity closed same day —
            // see plugins/_inventory/registry/20-meta-schemas.json), rendered on
            // the Apollo dark design system instead of plain wp-admin white boxes.
            // Running both at once showed two competing "tracks" editors on
            // /wp-admin/post.php?post={dj}&action=edit, writing conflicting
            // schemas to the same _dj_tracks key. Guarded rather than removed
            // outright: if apollo-lux-panels is ever deactivated, fall back to
            // this plain metabox so DJ posts are never left with zero admin UI.
            if (! \class_exists('\Apollo\LuxPanels\DjPanel')) {
                new Admin\Metabox();
            }
        }

        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Registra assets CSS/JS
     */
    public function register_assets(): void
    {
        // RemixIcon: loaded by CDN core.js — no separate registration needed

        // CSS principal
        wp_register_style(
            'apollo-djs',
            APOLLO_DJ_URL . 'assets/css/apollo-djs.css',
            array(),
            APOLLO_DJ_VERSION
        );

        // CSS estilo apollo-v1
        wp_register_style(
            'apollo-djs-v1',
            APOLLO_DJ_URL . 'styles/apollo-v1/style.css',
            array('apollo-djs'),
            APOLLO_DJ_VERSION
        );

        // CSS estilo apollo-v3
        wp_register_style(
            'apollo-djs-v3',
            APOLLO_DJ_URL . 'styles/apollo-v3/style.css',
            array('apollo-djs'),
            APOLLO_DJ_VERSION
        );

        // JS principal
        wp_register_script(
            'apollo-djs',
            APOLLO_DJ_URL . 'assets/js/apollo-djs.js',
            array(),
            APOLLO_DJ_VERSION,
            true
        );

        wp_localize_script(
            'apollo-djs',
            'apolloDJs',
            array(
                'rest_url'   => esc_url_raw(rest_url(APOLLO_DJ_REST_NAMESPACE)),
                'nonce'      => wp_create_nonce('wp_rest'),
                'plugin_url' => APOLLO_DJ_URL,
                'i18n'       => array(
                    'verified'  => __('Verificado', 'apollo-djs'),
                    'upcoming'  => __('Próximos eventos', 'apollo-djs'),
                    'no_events' => __('Sem eventos agendados', 'apollo-djs'),
                    'loading'   => __('Carregando...', 'apollo-djs'),
                    'search'    => __('Buscar DJs...', 'apollo-djs'),
                ),
            )
        );
    }

    /**
     * Registra rotas REST
     */
    public function register_rest_routes(): void
    {
        $controller = new API\DJsController();
        $controller->register_routes();
    }

    /**
     * Getters
     */
    public function registry(): Registry
    {
        return $this->registry;
    }

    public function shortcodes(): Shortcodes
    {
        return $this->shortcodes;
    }

    public function template_loader(): TemplateLoader
    {
        return $this->template_loader;
    }

    public static function instance(): ?Plugin
    {
        return self::$instance;
    }
}
