<?php

/**
 * Main Plugin Class (Singleton)
 *
 * @package Apollo\Dashboard
 */

declare(strict_types=1);

namespace Apollo\Dashboard;

use Apollo\Core\Traits\BlankCanvasTrait;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin class
 */
final class Plugin
{

    use BlankCanvasTrait;


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
        // Singleton - use get_instance()
    }

    /**
     * Initialize plugin
     *
     * @return void
     */
    public function init(): void
    {
        // Load text domain on init action (not plugins_loaded)
        add_action('init', array($this, 'load_textdomain'), 1);

        // Register hooks
        add_action('init', array($this, 'register_rewrite_rules'), 1);
        add_action('init', array($this, 'register_assets'));
        add_action('init', array($this, 'flush_rewrites_if_needed'), 99);
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Virtual pages — rewrite rule + template_redirect (same pattern as apollo-groups)
        add_filter('query_vars', array($this, 'register_query_vars'));
        add_action('template_redirect', array($this, 'handle_virtual_pages'), 5);

        // Shortcodes
        add_shortcode('apollo_dashboard', array($this, 'shortcode_dashboard'));
        add_shortcode('apollo_dashboard_menu', array($this, 'shortcode_dashboard_menu'));
    }

    /**
     * Load plugin textdomain
     *
     * @return void
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'apollo-dashboard',
            false,
            dirname(APOLLO_DASHBOARD_BASENAME) . '/languages'
        );
    }

    /**
     * Register plugin assets
     *
     * @return void
     */
    public function register_assets(): void
    {
        // Register styles
        wp_register_style(
            'apollo-dashboard',
            APOLLO_DASHBOARD_URL . 'assets/css/dashboard.css',
            array(),
            APOLLO_DASHBOARD_VERSION
        );

        // Register scripts
        wp_register_script(
            'apollo-dashboard',
            APOLLO_DASHBOARD_URL . 'assets/js/dashboard.js',
            array('jquery'),
            APOLLO_DASHBOARD_VERSION,
            true
        );
    }

    /**
     * Register query vars for virtual pages
     *
     * @param array $vars Query vars.
     * @return array
     */
    public function register_query_vars(array $vars): array
    {
        $vars[] = 'apollo_dashboard_page';
        $vars[] = 'apollo_dashboard_tab';
        return $vars;
    }

    /**
     * Register rewrite rules for /painel/* virtual pages
     *
     * @return void
     */
    public function register_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^painel/eventos/?$',
            'index.php?apollo_dashboard_page=dashboard&apollo_dashboard_tab=eventos',
            'top'
        );
        add_rewrite_rule(
            '^painel/favoritos/?$',
            'index.php?apollo_dashboard_page=dashboard&apollo_dashboard_tab=favoritos',
            'top'
        );
        add_rewrite_rule(
            '^painel/grupos/?$',
            'index.php?apollo_dashboard_page=dashboard&apollo_dashboard_tab=comunas',
            'top'
        );
        add_rewrite_rule(
            '^painel/configuracoes/?$',
            'index.php?apollo_dashboard_page=dashboard&apollo_dashboard_tab=configuracoes',
            'top'
        );
        add_rewrite_rule(
            '^painel/?$',
            'index.php?apollo_dashboard_page=dashboard&apollo_dashboard_tab=feed',
            'top'
        );
    }

    /**
     * Flush rewrite rules on plugin version change
     *
     * @return void
     */
    public function flush_rewrites_if_needed(): void
    {
        $stored = get_option('apollo_dashboard_version');
        if ($stored !== APOLLO_DASHBOARD_VERSION) {
            // Soft flush only — updates rewrite_rules option, does NOT touch
            // .htaccess. A hard flush firing unconditionally on every version
            // bump (across many plugins simultaneously) risks writing a bad/
            // incomplete .htaccess and breaking every pretty-permalink route.
            flush_rewrite_rules(false);
            update_option('apollo_dashboard_version', APOLLO_DASHBOARD_VERSION);
        }
    }

    /**
     * Handle virtual pages via template_redirect (same pattern as apollo-groups)
     *
     * @return void
     */
    public function handle_virtual_pages(): void
    {
        $page = get_query_var('apollo_dashboard_page');
        if (! $page) {
            return;
        }

        // Require login
        if (! is_user_logged_in()) {
            wp_redirect(home_url('/acesso'));
            exit;
        }

        // Blank Canvas APOLLO+ — logged-in app surface, carries the shell chrome.
        $template_file = APOLLO_DASHBOARD_DIR . 'templates/dashboard.php';
        $this->render_blank_canvas_plus($template_file);
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_rest_routes(): void
    {
        // GET /dashboard - Dashboard data
        register_rest_route(
            'apollo/v1',
            '/dashboard',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_dashboard'),
                'permission_callback' => array($this, 'check_logged_in'),
            )
        );

        // GET /dashboard/widgets - Available widgets (auth required — dashboard surface)
        register_rest_route(
            'apollo/v1',
            '/dashboard/widgets',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_list_widgets'),
                'permission_callback' => array($this, 'check_logged_in'),
            )
        );

        // GET/PUT /dashboard/settings - Dashboard settings
        register_rest_route(
            'apollo/v1',
            '/dashboard/settings',
            array(
                'methods'             => array('GET', 'PUT'),
                'callback'            => array($this, 'rest_dashboard_settings'),
                'permission_callback' => array($this, 'check_logged_in'),
            )
        );

        // GET/PUT /dashboard/layout - Widget layout
        register_rest_route(
            'apollo/v1',
            '/dashboard/layout',
            array(
                'methods'             => array('GET', 'PUT'),
                'callback'            => array($this, 'rest_dashboard_layout'),
                'permission_callback' => array($this, 'check_logged_in'),
            )
        );
    }

    /**
     * REST: Get dashboard data
     *
     * @return \WP_REST_Response
     */
    public function rest_get_dashboard(): \WP_REST_Response
    {
        $user_id = get_current_user_id();

        return new \WP_REST_Response(
            array(
                'success' => true,
                'data'    => array(
                    'user_id'  => $user_id,
                    'widgets'  => get_user_meta($user_id, '_apollo_dashboard_layout', true) ?: array(),
                    'settings' => get_user_meta($user_id, '_apollo_dashboard_settings', true) ?: array(),
                ),
            ),
            200
        );
    }

    /**
     * REST: List available widgets
     *
     * @return \WP_REST_Response
     */
    public function rest_list_widgets(): \WP_REST_Response
    {
        $widgets = array(
            array(
                'id'          => 'events',
                'name'        => 'Meus Eventos',
                'description' => 'Lista de eventos criados',
            ),
            array(
                'id'          => 'favs',
                'name'        => 'Favoritos',
                'description' => 'Posts favoritados',
            ),
            array(
                'id'          => 'groups',
                'name'        => 'Grupos',
                'description' => 'Grupos que participa',
            ),
            array(
                'id'          => 'notifications',
                'name'        => 'Notificações',
                'description' => 'Notificações recentes',
            ),
            array(
                'id'          => 'stats',
                'name'        => 'Estatísticas',
                'description' => 'Estatísticas do perfil',
            ),
        );

        return new \WP_REST_Response(
            array(
                'success' => true,
                'widgets' => $widgets,
            ),
            200
        );
    }

    /**
     * REST: Dashboard settings
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_dashboard_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        $user_id = get_current_user_id();

        if ($request->get_method() === 'PUT') {
            $raw = $request->get_json_params();
            $settings = array();
            $allowed_keys = array('theme', 'language', 'layout', 'notifications', 'privacy');
            foreach ($allowed_keys as $key) {
                if (isset($raw[$key])) {
                    $settings[$key] = sanitize_text_field(wp_unslash($raw[$key]));
                }
            }
            update_user_meta($user_id, '_apollo_dashboard_settings', $settings);
            return new \WP_REST_Response(
                array(
                    'success' => true,
                    'message' => 'Settings saved',
                ),
                200
            );
        }

        return new \WP_REST_Response(
            array(
                'success'  => true,
                'settings' => get_user_meta($user_id, '_apollo_dashboard_settings', true) ?: array(),
            ),
            200
        );
    }

    /**
     * REST: Dashboard layout
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_dashboard_layout(\WP_REST_Request $request): \WP_REST_Response
    {
        $user_id = get_current_user_id();

        if ($request->get_method() === 'PUT') {
            $raw = $request->get_json_params();
            $layout = array();
            if (isset($raw['widgets']) && is_array($raw['widgets'])) {
                foreach ($raw['widgets'] as $widget) {
                    if (is_array($widget) && isset($widget['id'])) {
                        $layout['widgets'][] = array(
                            'id'       => sanitize_key($widget['id']),
                            'position' => absint($widget['position'] ?? 0),
                            'visible'  => (bool) ($widget['visible'] ?? true),
                        );
                    }
                }
            }
            update_user_meta($user_id, '_apollo_dashboard_layout', $layout);
            return new \WP_REST_Response(
                array(
                    'success' => true,
                    'message' => 'Layout saved',
                ),
                200
            );
        }

        return new \WP_REST_Response(
            array(
                'success' => true,
                'layout'  => get_user_meta($user_id, '_apollo_dashboard_layout', true) ?: array(),
            ),
            200
        );
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function check_logged_in(): bool
    {
        return is_user_logged_in();
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu(): void
    {
        add_menu_page(
            __('Apollo Dashboard', 'apollo-dashboard'),
            __('Dashboard Settings', 'apollo-dashboard'),
            'manage_options',
            'apollo-dashboard',
            array($this, 'admin_page'),
            'dashicons-dashboard',
            32
        );
    }

    /**
     * Admin page callback
     *
     * @return void
     */
    public function admin_page(): void
    {
?>
        <div class="wrap">
            <h1><?php esc_html_e('Apollo Dashboard', 'apollo-dashboard'); ?></h1>
            <p><?php esc_html_e('User dashboard settings, template definitions, widgets.', 'apollo-dashboard'); ?></p>
            <div class="apollo-dashboard-admin">
                <h2><?php esc_html_e('Dashboard Pages', 'apollo-dashboard'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Page', 'apollo-dashboard'); ?></th>
                            <th><?php esc_html_e('URL', 'apollo-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Painel</td>
                            <td><code>/painel</code></td>
                        </tr>
                        <tr>
                            <td>Meus Eventos</td>
                            <td><code>/painel/eventos</code></td>
                        </tr>
                        <tr>
                            <td>Favoritos</td>
                            <td><code>/painel/favoritos</code></td>
                        </tr>
                        <tr>
                            <td>Grupos</td>
                            <td><code>/painel/grupos</code></td>
                        </tr>
                        <tr>
                            <td>Configurações</td>
                            <td><code>/painel/configuracoes</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
    }

    /**
     * Shortcode: apollo_dashboard
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function shortcode_dashboard(array $atts = array()): string
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to view your dashboard.', 'apollo-dashboard') . '</p>';
        }

        ob_start();
        include APOLLO_DASHBOARD_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: apollo_dashboard_menu
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function shortcode_dashboard_menu(array $atts = array()): string
    {
        if (! is_user_logged_in()) {
            return '';
        }

        ob_start();
    ?>
        <nav class="apollo-dashboard-menu">
            <ul>
                <li><a href="<?php echo esc_url(home_url('/painel')); ?>"><?php esc_html_e('Visão Geral', 'apollo-dashboard'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/painel/eventos')); ?>"><?php esc_html_e('Meus Eventos', 'apollo-dashboard'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/painel/favoritos')); ?>"><?php esc_html_e('Favoritos', 'apollo-dashboard'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/painel/grupos')); ?>"><?php esc_html_e('Grupos', 'apollo-dashboard'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/painel/configuracoes')); ?>"><?php esc_html_e('Configurações', 'apollo-dashboard'); ?></a></li>
            </ul>
        </nav>
<?php
        return ob_get_clean();
    }
}
