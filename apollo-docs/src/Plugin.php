<?php

namespace Apollo\Docs;

use Apollo\Core\Traits\BlankCanvasTrait;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin singleton — orchestrates modules.
 */
final class Plugin
{

    use BlankCanvasTrait;

    private static ?self $instance = null;

    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Initialize all components.
     */
    public function init(): void
    {
        $this->maybe_upgrade_db();

        /* CPT + Taxonomies (only if core doesn't register them as fallback) */
        new Core\Registrar();

        /* REST API */
        add_action(
            'rest_api_init',
            function (): void {
                (new API\DocsController())->register_routes();
                (new API\FoldersController())->register_routes();
            }
        );

        /* Admin */
        if (is_admin()) {
            new Admin\Controller();
            new Admin\Metabox();
        }

        /* Virtual page: /documentos */
        add_action('init', array($this, 'register_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_virtual_page'));

        do_action('apollo/docs/initialized');
    }

    /* ── Virtual page: /documentos ── */

    public function register_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^documentos/?$',
            'index.php?apollo_docs_page=1',
            'top'
        );
        add_rewrite_tag('%apollo_docs_page%', '([0-9]+)');
    }

    public function handle_virtual_page(): void
    {
        $is_docs_page = get_query_var('apollo_docs_page');
        if (! $is_docs_page) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_redirect(home_url('/acesso'));
            exit;
        }

        /* Enqueue docs assets for frontend */
        wp_enqueue_style(
            'apollo-docs',
            APOLLO_DOCS_URL . 'assets/css/docs.css',
            array(),
            APOLLO_DOCS_VERSION
        );

        wp_enqueue_script(
            'apollo-docs',
            APOLLO_DOCS_URL . 'assets/js/docs.js',
            array('jquery'),
            APOLLO_DOCS_VERSION,
            true
        );

        wp_localize_script(
            'apollo-docs',
            'ApolloDocs',
            array(
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('apollo_docs_nonce'),
                'restUrl'   => rest_url('apollo/v1/'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'assetsUrl' => defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/',
                'userId'    => get_current_user_id(),
                'isAdmin'   => current_user_can('manage_options'),
            )
        );

        /* Load frontend Canvas template via BlankCanvasTrait */
        $this->render_blank_canvas(APOLLO_DOCS_DIR . 'templates/frontend-documents.php');
    }

    /**
     * DB upgrade check.
     */
    private function maybe_upgrade_db(): void
    {
        $installed = (int) get_option('apollo_docs_db_version', 0);
        if ($installed < APOLLO_DOCS_DB_VERSION) {
            Database::install();
        }
    }
}
