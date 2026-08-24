<?php

/**
 * Main Plugin Class (Singleton)
 *
 * Adapted from BadgeOS main class + Apollo Core pattern.
 * Handles script registration, REST API init, admin menus.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{


    private static ?Plugin $instance = null;

    /** @var string Plugin version */
    public static string $version = '1.0.1';

    /** @var string Plugin directory path */
    public string $directory_path;

    /** @var string Plugin directory URL */
    public string $directory_url;

    /** @var float Start time (for trigger calculations) */
    public float $start_time;

    /** @var array Achievement types registry */
    public array $achievement_types = array();

    /** @var array Point trigger registry */
    public array $award_points_activity_triggers = array();

    /** @var array Point deduct trigger registry */
    public array $deduct_points_activity_triggers = array();

    /** @var array Award IDs tracking (prevent duplicates) */
    public array $award_ids = array();

    public static function get_instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->directory_path = APOLLO_MEMBERSHIP_DIR;
        $this->directory_url  = APOLLO_MEMBERSHIP_URL;
        $this->start_time     = microtime(true);
    }

    /**
     * Initialize plugin
     * Adapted from BadgeOS constructor hooks
     */
    public function init(): void
    {
        // Register scripts and styles (adapted from BadgeOS register_scripts_and_styles)
        add_action('init', array($this, 'register_scripts_and_styles'));

        // Handle settings save BEFORE any output (prevents "headers already sent")
        add_action('admin_init', array($this, 'handle_settings_save'));

        // Admin menu (adapted from BadgeOS plugin_menu)
        add_action('admin_menu', array($this, 'admin_menu'));

        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        // Frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Database upgrade check
        $this->maybe_upgrade_db();
    }

    /**
     * Register all scripts and styles
     * Adapted from BadgeOS::register_scripts_and_styles()
     */
    public function register_scripts_and_styles(): void
    {
        $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $v   = self::$version;

        // Admin
        wp_register_script('apollo-membership-admin', $this->directory_url . "assets/js/admin{$min}.js", array('jquery', 'jquery-ui-tabs'), $v, true);
        wp_register_style('apollo-membership-admin', $this->directory_url . "assets/css/admin{$min}.css", array(), $v);

        // Frontend
        wp_register_script('apollo-membership', $this->directory_url . "assets/js/membership{$min}.js", array('jquery'), $v, true);

        $front_css = file_exists(get_stylesheet_directory() . '/apollo-membership.css')
            ? get_stylesheet_directory_uri() . '/apollo-membership.css'
            : $this->directory_url . "assets/css/membership{$min}.css";
        wp_register_style('apollo-membership', $front_css, array(), $v);

        // Localize admin JS
        wp_localize_script(
            'apollo-membership-admin',
            'apolloMembershipAdmin',
            array(
                'ajax_url'    => admin_url('admin-ajax.php'),
                'rest_url'    => esc_url_raw(rest_url(APOLLO_MEMBERSHIP_REST_NAMESPACE . '/')),
                'rest_nonce'  => wp_create_nonce('wp_rest'),
                'loading_img' => admin_url('images/spinner.gif'),
                'nonce'       => wp_create_nonce('apollo_membership_admin'),
                'i18n'        => array(
                    'no_achievements' => __('Nenhuma conquista encontrada', 'apollo-membership'),
                    'revoke'          => __('Revogar', 'apollo-membership'),
                    'confirm_revoke'  => __('Tem certeza?', 'apollo-membership'),
                ),
            )
        );
    }

    /**
     * Admin menu
     * Adapted from BadgeOS::plugin_menu()
     */
    public function admin_menu(): void
    {
        $cap = 'manage_options';

        add_menu_page(
            __('Membership', 'apollo-membership'),
            __('Membership', 'apollo-membership'),
            $cap,
            'apollo-membership',
            array($this, 'render_settings_page'),
            'dashicons-awards',
            72
        );

        add_submenu_page(
            'apollo-membership',
            __('Configurações', 'apollo-membership'),
            __('Configurações', 'apollo-membership'),
            $cap,
            'apollo-membership',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'apollo-membership',
            __('Badges', 'apollo-membership'),
            __('Badges', 'apollo-membership'),
            $cap,
            'apollo-membership-badges',
            array($this, 'render_badges_page')
        );

        add_submenu_page(
            'apollo-membership',
            __('Ferramentas', 'apollo-membership'),
            __('Ferramentas', 'apollo-membership'),
            $cap,
            'apollo-membership-tools',
            array($this, 'render_tools_page')
        );
    }

    /**
     * Admin scripts
     * Adapted from BadgeOS::admin_scripts()
     */
    public function admin_scripts(): void
    {
        $screen = get_current_screen();
        if (! $screen || strpos($screen->id, 'apollo-membership') === false) {
            return;
        }

        // Prevent WP fonts from interfering with admin pages
        wp_dequeue_style('wp-fonts');
        wp_dequeue_style('wp-fonts-css');

        // Load Apollo CDN for RemixIcon + Apollo icons (core.js bundles jQuery v4)
        $cdn_url = defined('APOLLO_CDN_CORE_JS')
            ? APOLLO_CDN_CORE_JS
            : apollo_cdn_core_js_url();
        wp_enqueue_script('apollo-cdn', $cdn_url, array(), '1.0.0', false);

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('apollo-membership-admin');
        wp_enqueue_style('apollo-membership-admin');
    }

    /**
     * Frontend scripts
     * Adapted from BadgeOS::frontend_scripts()
     */
    public function frontend_scripts(): void
    {
        wp_localize_script(
            'apollo-membership',
            'apolloMembershipData',
            array(
                'ajax_url' => esc_url(admin_url('admin-ajax.php', 'relative')),
                'nonce'    => wp_create_nonce('apollo_membership_front'),
            )
        );
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes(): void
    {
        if (class_exists('\Apollo\Membership\API\AchievementsController')) {
            (new API\AchievementsController())->register_routes();
        }
        if (class_exists('\Apollo\Membership\API\PointsController')) {
            (new API\PointsController())->register_routes();
        }
        if (class_exists('\Apollo\Membership\API\RanksController')) {
            (new API\RanksController())->register_routes();
        }
        if (class_exists('\Apollo\Membership\API\LeaderboardController')) {
            (new API\LeaderboardController())->register_routes();
        }
        if (class_exists('\Apollo\Membership\API\TriggersController')) {
            (new API\TriggersController())->register_routes();
        }
        if (class_exists('\Apollo\Membership\API\ReportController')) {
            (new API\ReportController())->register_routes();
        }
    }

    /**
     * Database upgrade check
     * Adapted from BadgeOS::db_upgrade()
     */
    private function maybe_upgrade_db(): void
    {
        $installed = get_option('apollo_membership_db_version', '0');
        if (version_compare($installed, self::$version, '<')) {
            Activation::create_tables();
            Activation::migrate_triggers_table();
            update_option('apollo_membership_db_version', self::$version);
        }
    }

    /**
     * Handle settings form save BEFORE any output.
     * Runs on admin_init to prevent "headers already sent" errors from wp_redirect.
     */
    public function handle_settings_save(): void
    {
        // Only process on our settings page
        if (! isset($_GET['page']) || $_GET['page'] !== 'apollo-membership') {
            return;
        }
        if (! current_user_can('manage_options')) {
            return;
        }
        if (! isset($_POST['apollo_membership_save_settings'])) {
            return;
        }
        if (! wp_verify_nonce($_POST['_wpnonce'] ?? '', 'apollo_membership_settings')) {
            return;
        }

        $new_settings = array(
            'manager_capability' => sanitize_text_field($_POST['manager_capability'] ?? 'manage_options'),
            'image_width'        => max(16, min(256, (int) ($_POST['image_width'] ?? 50))),
            'image_height'       => max(16, min(256, (int) ($_POST['image_height'] ?? 50))),
            'debug_mode'         => isset($_POST['debug_mode']) ? 'on' : 'off',
        );
        update_option('apollo_membership_settings', $new_settings);

        // Save trigger point values
        $trigger_points     = (array) get_option('apollo_membership_trigger_points', array());
        $new_trigger_points = array();

        if (isset($_POST['trigger_points']) && is_array($_POST['trigger_points'])) {
            foreach ($_POST['trigger_points'] as $trigger => $value) {
                $new_trigger_points[sanitize_text_field($trigger)] = (int) $value;
            }
        }

        // Preserve cooldowns
        foreach ($trigger_points as $key => $val) {
            if (str_contains($key, '_cooldown')) {
                $new_trigger_points[$key] = $val;
            }
        }
        update_option('apollo_membership_trigger_points', $new_trigger_points);

        wp_safe_redirect(admin_url('admin.php?page=apollo-membership&saved=1'));
        exit;
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        include APOLLO_MEMBERSHIP_DIR . 'templates/admin/settings.php';
    }

    /**
     * Render badges management page (admin-only badge assignment)
     */
    public function render_badges_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        include APOLLO_MEMBERSHIP_DIR . 'templates/admin/badges.php';
    }

    /**
     * Render tools page
     */
    public function render_tools_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        include APOLLO_MEMBERSHIP_DIR . 'templates/admin/tools.php';
    }
}
