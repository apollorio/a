<?php

/**
 * Main Plugin Class (Singleton)
 *
 * Orchestrates all components: CPT registration, admin, frontend, REST, shortcodes.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

use Apollo\Sheets\DashboardWidget;
use Apollo\Sheets\Settings;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{



    private static ?Plugin $instance = null;

    public static function get_instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Initialize all plugin components
     */
    public function init(): void
    {
        // Template tag helpers for theme developers
        require_once APOLLO_SHEETS_DIR . 'includes/template-tags.php';

        // Register CPT
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_assets'));
        add_action('init', array($this, 'register_block'));

        // Shortcodes
        add_action('init', array($this, 'register_shortcodes'));

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Admin
        if (is_admin()) {
            $admin = new Admin\Controller();
            $admin->init();

            // Bulk Editor — admin + mod only
            $bulk_manager = Bulk\Manager::get_instance();
            $bulk_manager->init();

            $bulk_admin = new Admin\BulkController($bulk_manager);
            $bulk_admin->init();

            // Dashboard widget
            $dashboard_widget = new DashboardWidget();
            $dashboard_widget->register();

            // Admin Bar: add "Nova Sheet" link
            add_action('admin_bar_menu', array($this, 'admin_bar_menu'), 999);

            // Persist Screen Options per_page value
            add_filter('set-screen-option', array($this, 'save_screen_option'), 10, 3);
        }

        // Custom CSS output in <head>
        add_action('wp_head', array($this, 'output_custom_css'), 99);

        // WP Search integration
        add_filter('posts_search', array($this, 'wp_search_integration'), 10, 2);

        // WXR (WordPress eXtended RSS) export/import support
        add_filter('wxr_export_skip_postmeta', array($this, 'wxr_export_meta'), 10, 3);
        add_action('import_post_meta', array($this, 'wxr_import_meta'), 10, 3);

        // Frontend DataTables JS footer output
        add_action('wp_footer', array($this, 'print_datatables_init'), 50);

        // Hooks
        do_action('apollo/sheets/initialized');
    }

    /**
     * Register the apollo_sheet CPT
     *
     * Data stored as JSON in post_content, options in post meta.
     * Not public — accessed only via shortcode, REST, or admin UI.
     *
     * If apollo-core already registered this CPT as fallback, skip.
     */
    public function register_post_type(): void
    {
        // Skip if apollo-core already registered as fallback
        if (post_type_exists(APOLLO_SHEETS_CPT)) {
            return;
        }

        register_post_type(
            APOLLO_SHEETS_CPT,
            array(
                'labels'          => array(
                    'name'          => __('Sheets', 'apollo-sheets'),
                    'singular_name' => __('Sheet', 'apollo-sheets'),
                    'add_new'       => __('Nova Sheet', 'apollo-sheets'),
                    'add_new_item'  => __('Nova Sheet', 'apollo-sheets'),
                    'edit_item'     => __('Editar Sheet', 'apollo-sheets'),
                    'view_item'     => __('Ver Sheet', 'apollo-sheets'),
                    'search_items'  => __('Buscar Sheets', 'apollo-sheets'),
                ),
                'public'          => false,
                'show_ui'         => false,
                'show_in_rest'    => false,   // Custom REST controller instead
                'supports'        => array('title', 'editor', 'excerpt', 'revisions', 'author'),
                'can_export'      => true,
                'map_meta_cap'    => true,
                'capability_type' => 'post',
            )
        );
    }

    /**
     * Register CSS/JS assets (only enqueued when needed)
     */
    public function register_assets(): void
    {
        // Frontend table styles
        wp_register_style(
            'apollo-sheets',
            APOLLO_SHEETS_URL . 'assets/css/sheets.css',
            array(),
            APOLLO_SHEETS_VERSION
        );

        // DataTables CSS
        wp_register_style(
            'apollo-sheets-datatables',
            APOLLO_SHEETS_URL . 'assets/css/datatables.css',
            array('apollo-sheets'),
            APOLLO_SHEETS_VERSION
        );

        // DataTables JS
        wp_register_script(
            'apollo-sheets-datatables',
            APOLLO_SHEETS_URL . 'assets/js/datatables.min.js',
            array('jquery'),
            APOLLO_SHEETS_VERSION,
            true
        );

        // Frontend init script
        wp_register_script(
            'apollo-sheets-frontend',
            APOLLO_SHEETS_URL . 'assets/js/sheets-frontend.js',
            array('apollo-sheets-datatables'),
            APOLLO_SHEETS_VERSION,
            true
        );

        // Admin editor
        wp_register_style(
            'apollo-sheets-admin',
            APOLLO_SHEETS_URL . 'assets/css/sheets-admin.css',
            array(),
            APOLLO_SHEETS_VERSION
        );

        wp_register_script(
            'apollo-sheets-admin',
            APOLLO_SHEETS_URL . 'assets/js/sheets-admin.js',
            array('jquery', 'wp-util'),
            APOLLO_SHEETS_VERSION,
            true
        );
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes(): void
    {
        $shortcodes = new Shortcodes();
        $shortcodes->register();
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes(): void
    {
        $controller = new API\SheetsController();
        $controller->register_routes();
    }

    /**
     * Print DataTables initialization scripts in footer
     *
     * Collected during render phase, output once in wp_footer.
     */
    public function print_datatables_init(): void
    {
        $inits = Render::get_datatables_inits();
        if (empty($inits)) {
            return;
        }

        wp_enqueue_style('apollo-sheets-datatables');
        wp_enqueue_script('apollo-sheets-frontend');

        echo '<script id="apollo-sheets-dt-init">' . "\n";
        echo 'jQuery(function($){' . "\n";
        foreach ($inits as $init) {
            echo $init . "\n";
        }
        echo '});' . "\n";
        echo '</script>' . "\n";
    }

	// ─── New wired features ─────────────────────────────────────────

    /**
     * Register the Gutenberg block
     */
    public function register_block(): void
    {
        if (! function_exists('register_block_type')) {
            return;
        }
        $block_json = APOLLO_SHEETS_DIR . 'blocks/apollo-sheet/block.json';
        if (file_exists($block_json)) {
            register_block_type($block_json);

            // Localize nonce so the editor JS can call ajax_preview
            add_action(
                'enqueue_block_editor_assets',
                function () {
                    wp_localize_script(
                        'apollo-sheets-sheet-editor-script',
                        'apolloSheetsBlock',
                        array(
                            'nonce' => wp_create_nonce('apollo_sheets_admin'),
                        )
                    );
                }
            );
        }
    }

    /**
     * Output custom CSS in <head>
     */
    public function output_custom_css(): void
    {
        $settings = new Settings();
        $css      = $settings->get_custom_css();
        if (! $css) {
            return;
        }
        echo '<style id="apollo-sheets-custom-css">' . "\n";
        echo wp_strip_all_tags($css) . "\n";
        echo '</style>' . "\n";
    }

    /**
     * Add "Nova Sheet" to the WP Admin Bar
     *
     * @param \WP_Admin_Bar $wp_admin_bar
     */
    public function admin_bar_menu(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (! current_user_can('edit_posts')) {
            return;
        }
        $wp_admin_bar->add_node(
            array(
                'id'     => 'apollo-sheets-new',
                'title'  => __('Nova Sheet', 'apollo-sheets'),
                'href'   => admin_url('admin.php?page=apollo-sheets-add'),
                'parent' => 'new-content',
            )
        );
    }

    /**
     * Persist Screen Options value for apollo_sheets_per_page
     *
     * @param mixed  $status
     * @param string $option
     * @param mixed  $value
     * @return mixed
     */
    public function save_screen_option($status, string $option, $value)
    {
        if ('apollo_sheets_per_page' === $option) {
            return absint($value);
        }
        return $status;
    }

    /**
     * WP Search integration — include sheet data in search results
     *
     * Appends an OR clause so wp_posts rows whose post_content contains the
     * search term are surfaced (only for apollo_sheet CPT).
     *
     * @param string    $search  Current SQL search clause.
     * @param \WP_Query $query   Current query.
     * @return string
     */
    public function wp_search_integration(string $search, \WP_Query $query): string
    {
        if (! $query->is_search() || is_admin()) {
            return $search;
        }

        $settings = new Settings();
        if (! $settings->get('wp_search_integration')) {
            return $search;
        }

        global $wpdb;

        $term         = $query->get('s');
        $like         = '%' . $wpdb->esc_like($term) . '%';
        $table        = $wpdb->posts;
        $sheet_search = $wpdb->prepare(
            " OR ( {$table}.post_type = %s AND {$table}.post_content LIKE %s )",
            APOLLO_SHEETS_CPT,
            $like
        );

        // Append with balanced parentheses
        if ($search) {
            $search = preg_replace('/\)$/', $sheet_search . ')', $search, 1);
        }

        return $search;
    }

    /**
     * WXR Export — never skip apollo_sheets_data meta (include in WXR export)
     *
     * @param bool   $skip     Whether to skip.
     * @param string $meta_key Meta key.
     * @param mixed  $post     Post object or ID.
     * @return bool
     */
    public function wxr_export_meta(bool $skip, string $meta_key, $post): bool
    {
        if (str_starts_with($meta_key, 'apollo_sheets_')) {
            return false; // Never skip — always export
        }
        return $skip;
    }

    /**
     * WXR Import — re-link table IDs after WXR import
     *
     * @param int    $post_id  Newly-inserted post ID.
     * @param string $meta_key Meta key.
     * @param mixed  $value    Meta value.
     */
    public function wxr_import_meta(int $post_id, string $meta_key, $value): void
    {
        if ('apollo_sheets_id' !== $meta_key) {
            return;
        }
        // Re-register the logical ID → new post_id in the index option
        $index                    = get_option('apollo_sheets_tables', array());
        $index[(string) $value] = $post_id;
        update_option('apollo_sheets_tables', $index);
    }
}
