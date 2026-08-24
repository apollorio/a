<?php

/**
 * Plugin — Singleton orquestrador (extremamente enxuto)
 *
 * Instancia as classes responsáveis por cada subsistema.
 * Zero lógica de negócio aqui. Apenas cabeamento.
 *
 * @package Apollo\Local
 */

namespace Apollo\Local;

if (! \defined('ABSPATH')) {
    exit;
}

class Plugin
{

    private static ?Plugin $instance = null;

    public function __construct()
    {
        if (null !== self::$instance) {
            return;
        }
        self::$instance = $this;

        // ── CPT / Taxonomias / Meta ─────────────────────────────────────
        new CPT\CPTRegistrar();
        new CPT\TaxonomyRegistrar();
        new CPT\MetaRegistrar();

        // ── Assets ──────────────────────────────────────────────────────
        new Assets\AssetLoader();

        // ── Shortcodes ──────────────────────────────────────────────────
        new Shortcodes\ShortcodeRegistry();

        // ── Admin ───────────────────────────────────────────────────────
        if (is_admin()) {
            new Admin\Dashboard();
            new Admin\Metabox\MetaboxManager();
            new Admin\Columns();
        }

        // ── REST API ────────────────────────────────────────────────────
        add_action('rest_api_init', function () {
            $router = new API\Router();
            $router->register_routes();
        });

        // ── Template Loader ─────────────────────────────────────────────
        new TemplateLoader();

        // ── Integrações cross-plugin ─────────────────────────────────────
        new Integration\CoreIntegration();
        new Integration\TemplatesIntegration();
        new Integration\SocialIntegration();

        // ── Cross-plugin hooks ───────────────────────────────────────────
        Integrations::init();
    }

    public static function instance(): ?Plugin
    {
        return self::$instance;
    }
}

