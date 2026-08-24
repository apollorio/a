<?php

/**
 * Main Plugin Class (Singleton)
 *
 * @package Apollo\Templates
 */

declare(strict_types=1);

namespace Apollo\Templates;

// Prevent direct access.
if (! defined('ABSPATH')) {
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
        add_action('init', array($this, 'register_assets'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Navbar Apps Settings
        require_once APOLLO_TEMPLATES_DIR . 'includes/class-navbar-settings.php';
        NavbarSettings::get_instance()->init();

        // AJAX — reset navbar apps to defaults
        add_action('wp_ajax_apollo_navbar_reset_defaults', array($this, 'ajax_reset_navbar_defaults'));
    }

    /**
     * AJAX: Reset navbar apps to defaults
     */
    public function ajax_reset_navbar_defaults(): void
    {
        check_ajax_referer('apollo_navbar_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        delete_option(NavbarSettings::OPTION_KEY);
        wp_send_json_success();
    }

    /**
     * Register all Apollo shortcodes.
     *
     * @return void
     */
    public function register_shortcodes(): void
    {
        require_once APOLLO_TEMPLATES_DIR . 'includes/class-shortcodes.php';
        Shortcodes::get_instance()->register();
    }

    /**
     * Load plugin textdomain
     *
     * @return void
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'apollo-templates',
            false,
            dirname(APOLLO_TEMPLATES_BASENAME) . '/languages'
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
            'apollo-templates',
            APOLLO_TEMPLATES_URL . 'assets/css/templates.css',
            array(),
            APOLLO_TEMPLATES_VERSION
        );

        // Register scripts
        wp_register_script(
            'apollo-templates',
            APOLLO_TEMPLATES_URL . 'assets/js/templates.js',
            array('jquery'),
            APOLLO_TEMPLATES_VERSION,
            true
        );

        // Event card CSS — registered here, enqueued on-demand by shortcodes.
        wp_register_style(
            'apollo-event-card',
            APOLLO_TEMPLATES_URL . 'assets/css/event-card.css',
            array(),
            APOLLO_TEMPLATES_VERSION
        );
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_rest_routes(): void
    {
        // GET /templates - List templates
        register_rest_route(
            'apollo/v1',
            '/templates',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_list_templates'),
                'permission_callback' => '__return_true',
            )
        );

        // GET /templates/calendars - Calendar types
        register_rest_route(
            'apollo/v1',
            '/templates/calendars',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_list_calendar_templates'),
                'permission_callback' => '__return_true',
            )
        );

        // POST /canvas/save - Save canvas page
        register_rest_route(
            'apollo/v1',
            '/canvas/save',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_save_canvas'),
                'permission_callback' => array($this, 'check_edit_permission'),
            )
        );

        // GET /canvas/blocks - Available blocks
        register_rest_route(
            'apollo/v1',
            '/canvas/blocks',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_list_blocks'),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * REST: List all templates
     *
     * @return \WP_REST_Response
     */
    public function rest_list_templates(): \WP_REST_Response
    {
        $templates_dir = APOLLO_TEMPLATES_DIR . 'templates/';
        $templates     = array();

        if (is_dir($templates_dir)) {
            $files = glob($templates_dir . '*.{php,html}', GLOB_BRACE);
            foreach ($files as $file) {
                $templates[] = array(
                    'name' => basename($file),
                    // Absolute paths omitted from REST response for security.
                    'type' => pathinfo($file, PATHINFO_EXTENSION),
                );
            }
        }

        return new \WP_REST_Response(
            array(
                'success'   => true,
                'templates' => $templates,
                'total'     => count($templates),
            ),
            200
        );
    }

    /**
     * REST: List calendar templates
     *
     * @return \WP_REST_Response
     */
    public function rest_list_calendar_templates(): \WP_REST_Response
    {
        // Calendar types from registry
        $calendar_types = array(
            array(
                'id'          => 'calendar-01',
                'name'        => 'Calendar Type 01',
                'description' => 'Basic grid calendar',
            ),
            array(
                'id'          => 'calendar-02',
                'name'        => 'Calendar Type 02',
                'description' => 'List view calendar',
            ),
            array(
                'id'          => 'calendar-03',
                'name'        => 'Calendar Type 03',
                'description' => 'Timeline calendar',
            ),
        );

        return new \WP_REST_Response(
            array(
                'success'   => true,
                'calendars' => $calendar_types,
            ),
            200
        );
    }

    /**
     * REST: Save canvas page
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_save_canvas(\WP_REST_Request $request): \WP_REST_Response
    {
        $page_id     = absint($request->get_param('page_id'));
        // Sanitize canvas_data — allow safe HTML (shortcodes, divs, classes).
        $canvas_data = wp_kses_post($request->get_param('canvas_data') ?? '');

        if (empty($page_id) || empty($canvas_data)) {
            return new \WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Page ID and canvas data are required',
                ),
                400
            );
        }

        // Verify user can edit this specific post
        if (! current_user_can('edit_post', $page_id)) {
            return new \WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'You do not have permission to edit this page',
                ),
                403
            );
        }

        // Save canvas data as post meta
        update_post_meta($page_id, '_apollo_canvas_data', $canvas_data);
        update_post_meta($page_id, '_apollo_template', 'canvas');

        return new \WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Canvas saved successfully',
            ),
            200
        );
    }

    /**
     * REST: List available blocks
     *
     * @return \WP_REST_Response
     */
    public function rest_list_blocks(): \WP_REST_Response
    {
        $blocks = array(
            array(
                'id'       => 'hero',
                'name'     => 'Hero Section',
                'category' => 'layout',
            ),
            array(
                'id'       => 'events',
                'name'     => 'Events Grid',
                'category' => 'content',
            ),
            array(
                'id'       => 'calendar',
                'name'     => 'Calendar View',
                'category' => 'content',
            ),
            array(
                'id'       => 'text',
                'name'     => 'Text Block',
                'category' => 'basic',
            ),
            array(
                'id'       => 'image',
                'name'     => 'Image Block',
                'category' => 'basic',
            ),
        );

        return new \WP_REST_Response(
            array(
                'success' => true,
                'blocks'  => $blocks,
            ),
            200
        );
    }

    /**
     * Check edit permission for REST routes
     *
     * @return bool
     */
    public function check_edit_permission(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu(): void
    {
        add_menu_page(
            __('Apollo Templates', 'apollo-templates'),
            __('Templates', 'apollo-templates'),
            'manage_options',
            'apollo-templates',
            array($this, 'admin_page'),
            'dashicons-layout',
            30
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
            <h1><?php esc_html_e('Apollo Templates', 'apollo-templates'); ?></h1>
            <p><?php esc_html_e('Page builder canvas, calendar types, PWA templates, all template variations.', 'apollo-templates'); ?></p>
            <div class="apollo-templates-dashboard">
                <?php $this->render_templates_list(); ?>
            </div>
        </div>
<?php
    }

    /**
     * Render templates list in admin
     *
     * @return void
     */
    private function render_templates_list(): void
    {
        $templates_dir = APOLLO_TEMPLATES_DIR . 'templates/';

        if (! is_dir($templates_dir)) {
            echo '<p>' . esc_html__('Templates directory not found.', 'apollo-templates') . '</p>';
            return;
        }

        $files = get_available_templates();

        if (empty($files)) {
            echo '<p>' . esc_html__('No templates found.', 'apollo-templates') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Template</th><th>Type</th></tr></thead>';
        echo '<tbody>';
        foreach ($files as $file) {
            $type = pathinfo($file, PATHINFO_EXTENSION);
            echo '<tr>';
            echo '<td><code>' . esc_html($file) . '</code></td>';
            echo '<td>' . esc_html(strtoupper($type)) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
