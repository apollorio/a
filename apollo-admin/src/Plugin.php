<?php

/**
 * Main Plugin Class (Singleton)
 *
 * Centralizes all Apollo plugin administration in a single
 * unified panel with tabs for each plugin.
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin;

// Prevent direct access.
if (! \defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin class
 */
final class Plugin
{


    /**
     * Plugin instance
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Get plugin instance (Singleton)
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct()
    {
        // Singleton — use get_instance()
    }

    /**
     * Initialize plugin
     *
     * @return void
     */
    public function init(): void
    {
        // Load translations on init action (not plugins_loaded)
        add_action('init', function () {
            load_plugin_textdomain('apollo-admin', false, dirname(APOLLO_ADMIN_BASENAME) . '/languages');
        }, 1);

        // Initialize singletons
        Registry::get_instance()->init();
        Settings::get_instance()->init();

        // Admin-only components
        if (is_admin()) {
            AdminPage::get_instance()->init();
            AdminAssets::get_instance()->init();
        }

        // Frontend admin panel (admin-only access on frontend)
        FrontendPanel::get_instance()->init();

        // Gutenberg + Elementor editor support for all CPTs
        EditorSupport::get_instance()->init();

        // UiPress-adapted components (admin menu, toolbar, error log, user prefs).
        AdminMenuCustomizer::get_instance()->init();
        ToolbarCustomizer::get_instance()->init();
        ErrorLogViewer::get_instance()->init();
        UserPreferences::get_instance()->init();

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Plugin action links
        add_filter('plugin_action_links_' . APOLLO_ADMIN_BASENAME, array($this, 'add_action_links'));
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_rest_routes(): void
    {
        $controller = new Rest\SettingsController();
        $controller->register_routes();
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @return array
     */
    public function add_action_links(array $links): array
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=apollo') . '">Configurações</a>';
        \array_unshift($links, $settings_link);
        return $links;
    }
}
