<?php

/**
 * Main Plugin Class (Singleton)
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users;

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
        // Ensure the administrator role always has every Apollo custom capability.
        // Runs on admin_init (not frontend) so it only writes to the DB when something
        // is genuinely missing — avoids unnecessary writes on every page load.
        add_action('admin_init', array($this, 'ensure_administrator_caps'));

        // Register hooks
        add_action('init', array($this, 'register_assets'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Author enumeration blocking delegated to Components\AuthorProtection
        add_filter('author_link', array($this, 'filter_author_link'), 10, 2);

        // Profile completion tracking
        add_action('profile_update', array($this, 'update_profile_completion'), 10, 1);
        add_action('updated_user_meta', array($this, 'update_profile_completion_on_meta'), 10, 4);
        add_action('user_register', array($this, 'init_new_user_meta'), 10, 1);

        // Keep apollo-users verification state synchronized with apollo-login flow.
        add_action('apollo/login/email_verified', array($this, 'sync_email_verified_from_login'), 10, 1);

        // Initialize components
        $this->init_components();
    }

    /**
     * Initialize plugin components
     *
     * @return void
     */
    private function init_components(): void
    {
        new Components\ProfileHandler();
        new Components\UserFields();
        new Components\AuthorProtection();
        new Components\RatingHandler();
        new Components\DepoimentoHandler();
        new Components\MatchmakingEngine();
    }

    /**
     * Legacy method kept for backward compatibility.
     *
     * User meta registration is centralized in apollo-core MetaRegistry.
     * This plugin must not register meta independently.
     *
     * @return void
     */
    public function register_user_meta(): void
    {
        // No-op by design.
    }

    /**
     * Register plugin assets
     *
     * @return void
     */
    public function register_assets(): void
    {
        // Profile styles
        wp_register_style(
            'apollo-users-profile',
            APOLLO_USERS_URL . 'assets/css/profile.css',
            array(),
            APOLLO_USERS_VERSION
        );

        // Radar styles
        wp_register_style(
            'apollo-users-radar',
            APOLLO_USERS_URL . 'assets/css/radar.css',
            array(),
            APOLLO_USERS_VERSION
        );

        // Profile scripts
        wp_register_script(
            'apollo-users-profile',
            APOLLO_USERS_URL . 'assets/js/profile.js',
            array('jquery'),
            APOLLO_USERS_VERSION,
            true
        );
    }

    /**
     * Register shortcodes
     *
     * @return void
     */
    public function register_shortcodes(): void
    {
        add_shortcode('apollo_profile', array($this, 'shortcode_profile'));
        add_shortcode('apollo_profile_edit', array($this, 'shortcode_profile_edit'));
        add_shortcode('apollo_radar', array($this, 'shortcode_radar'));
        add_shortcode('apollo_user_card', array($this, 'shortcode_user_card'));
        add_shortcode('apollo_matchmaking', array($this, 'shortcode_matchmaking'));
        add_shortcode('apollo_profile_fields', array($this, 'shortcode_profile_fields'));
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_rest_routes(): void
    {
        $controller = new API\UsersController();
        $controller->register_routes();

        $profile_controller = new API\ProfileController();
        $profile_controller->register_routes();
    }

    /**
     * Block author enumeration (security)
     *
     * @return void
     */
    public function block_author_enumeration(): void
    {
        // Block ?author=N enumeration
        if (isset($_GET['author']) && ! is_admin()) {
            wp_redirect(home_url(), 301);
            exit;
        }

        // Block /author/username if protection enabled
        if (is_author()) {
            $author = get_queried_object();
            if ($author && get_user_meta($author->ID, '_apollo_disable_author_url', true)) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
            }
        }
    }

    /**
     * Filter author links to use /id/username
     *
     * @param string $link Author link
     * @param int    $author_id Author ID
     * @return string
     */
    public function filter_author_link(string $link, int $author_id): string
    {
        return apollo_get_profile_url($author_id);
    }

	// ═══════════════════════════════════════════════════════════════════════
	// SHORTCODES
	// ═══════════════════════════════════════════════════════════════════════

    /**
     * Shortcode: User profile display
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function shortcode_profile($atts): string
    {
        $atts = shortcode_atts(
            array(
                'user_id' => 0,
            ),
            $atts
        );

        $user_id = (int) $atts['user_id'];
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return '';
        }

        ob_start();
        include APOLLO_USERS_DIR . 'templates/parts/profile-display.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Profile edit form
     *
     * @return string
     */
    public function shortcode_profile_edit(): string
    {
        if (! is_user_logged_in()) {
            return '<p>' . __('Você precisa estar logado para editar seu perfil.') . '</p>';
        }

        ob_start();
        include APOLLO_USERS_DIR . 'templates/parts/profile-edit-form.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: User radar (directory)
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function shortcode_radar($atts): string
    {
        $atts = shortcode_atts(
            array(
                'limit'   => 24,
                'role'    => '',
                'orderby' => 'registered',
                'layout'  => 'grid',
            ),
            $atts
        );

        ob_start();
        include APOLLO_USERS_DIR . 'templates/parts/user-radar.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: User card component
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function shortcode_user_card($atts): string
    {
        $atts = shortcode_atts(
            array(
                'user_id' => 0,
            ),
            $atts
        );

        $user_id = (int) $atts['user_id'];
        if (! $user_id) {
            return '';
        }

        ob_start();
        include APOLLO_USERS_DIR . 'templates/parts/user-card.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Matchmaking widget
     *
     * @return string
     */
    public function shortcode_matchmaking(): string
    {
        // Matchmaking disabled - all users are connected
        return '<div class="apollo-matchmaking-disabled"><p>' . esc_html__('Sistema de matchmaking desabilitado. Todos os usuários estão conectados.', 'apollo-users') . '</p></div>';
    }

    /**
     * Shortcode: Custom profile fields
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function shortcode_profile_fields($atts): string
    {
        $atts = shortcode_atts(
            array(
                'user_id' => 0,
                'edit'    => false,
            ),
            $atts
        );

        $user_id = (int) $atts['user_id'];
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        ob_start();
        include APOLLO_USERS_DIR . 'templates/parts/profile-fields.php';
        return ob_get_clean();
    }

    /**
     * Calculate and update profile completion percentage
     *
     * @param int $user_id User ID
     * @return int Completion percentage (0-100)
     */
    public function update_profile_completion(int $user_id): int
    {
        $fields_to_check = array(
            'first_name'         => get_user_meta($user_id, 'first_name', true),
            'last_name'          => get_user_meta($user_id, 'last_name', true),
            '_apollo_bio'        => get_user_meta($user_id, '_apollo_bio', true),
            'user_location'      => get_user_meta($user_id, 'user_location', true),
            'instagram'          => get_user_meta($user_id, 'instagram', true),
            '_apollo_website'    => get_user_meta($user_id, '_apollo_website', true),
            '_apollo_phone'      => get_user_meta($user_id, '_apollo_phone', true),
            '_apollo_birth_date' => get_user_meta($user_id, '_apollo_birth_date', true),
            'custom_avatar'      => get_user_meta($user_id, 'custom_avatar', true),
            'cover_image'        => get_user_meta($user_id, 'cover_image', true),
        );

        $filled_fields = 0;
        $total_fields  = count($fields_to_check);

        foreach ($fields_to_check as $value) {
            if (! empty($value)) {
                ++$filled_fields;
            }
        }

        $percentage = (int) round(($filled_fields / $total_fields) * 100);
        update_user_meta($user_id, '_apollo_profile_completed', $percentage);

        return $percentage;
    }

    /**
     * Update profile completion on meta update
     *
     * @param int    $meta_id    Meta ID
     * @param int    $user_id    User ID
     * @param string $meta_key   Meta key
     * @param mixed  $meta_value Meta value
     * @return void
     */
    public function update_profile_completion_on_meta(int $meta_id, int $user_id, string $meta_key, $meta_value): void
    {
        // Profile-related meta keys that should trigger recalculation
        $profile_keys = array(
            'first_name',
            'last_name',
            '_apollo_bio',
            'user_location',
            'instagram',
            '_apollo_website',
            '_apollo_phone',
            '_apollo_birth_date',
            'custom_avatar',
            'cover_image',
        );

        if (in_array($meta_key, $profile_keys, true)) {
            $this->update_profile_completion($user_id);
        }
    }

    /**
     * Sync verification state when apollo-login confirms email.
     *
     * @param int $user_id User ID.
     * @return void
     */
    public function sync_email_verified_from_login(int $user_id): void
    {
        update_user_meta($user_id, '_apollo_user_verified', true);
        do_action('apollo_user_verified', $user_id);
    }

    /**
     * Ensure the administrator role always has every Apollo-defined custom capability.
     *
     * Activation hooks do not run when plugins are installed by copying files.
     * This guard runs on admin_init and only writes to the DB if a capability is
     * actually absent, keeping the overhead to near-zero on normal page loads.
     *
     * @return void
     */
    public function ensure_administrator_caps(): void
    {
        $admin = get_role('administrator');
        if (! $admin) {
            return;
        }

        $apollo_caps = array(
            'apollo_view_profiles',
            'apollo_edit_own_profile',
            'apollo_edit_all_profiles',
            'apollo_rate_users',
            'apollo_create_events',
            'apollo_edit_own_events',
            'apollo_edit_all_events',
            'apollo_delete_own_events',
            'apollo_delete_all_events',
            'apollo_manage_dj_profile',
            'apollo_manage_venue',
            'apollo_create_events_at_venue',
            'apollo_moderate_content',
            'apollo_moderate_users',
            'apollo_view_reports',
        );

        foreach ($apollo_caps as $cap) {
            if (! isset($admin->capabilities[$cap])) {
                $admin->add_cap($cap);
            }
        }
    }

    /**
     * Initialize meta fields for new users
     *
     * @param int $user_id User ID
     * @return void
     */
    public function init_new_user_meta(int $user_id): void
    {
        // Set default meta fields for registry compliance
        add_user_meta($user_id, '_apollo_user_verified', false, true);
        if (function_exists('apollo_membership_assign')) {
            apollo_membership_assign($user_id, array('nao-verificado'));
        }
        add_user_meta($user_id, '_apollo_profile_completed', 0, true);
        add_user_meta($user_id, '_apollo_privacy_profile', 'public', true);
        add_user_meta($user_id, '_apollo_privacy_email', true, true);
        add_user_meta($user_id, '_apollo_disable_author_url', true, true);
        add_user_meta($user_id, '_apollo_profile_views', 0, true);

        // Calculate initial profile completion
        $this->update_profile_completion($user_id);

        /**
         * Fires after new user meta initialization
         *
         * @param int $user_id User ID
         */
        do_action('apollo_user_meta_initialized', $user_id);
    }
}
