<?php
/**
 * Plugin Name: Apollo Users
 * Plugin URI: https://apollo.rio.br/plugins/apollo-users
 * Description: Users: Roles, Capabilities, Profile page (/id/username), Preferences, Matchmaking, Advanced Fields, Author Protection
 * Version: 1.1.0
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-users
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Users
 */

/*
 * ARCH: apollo-users
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-users   30 arquivos PHP, 13259 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (5); chrome é do apollo-templates
 * META      1 chaves tocadas, 1 SEM definição governante
 * REST      namespace não literal no código — 23 rotas (5 públicas)
 * REQUIRES  apollo-core
 *
 * NÃO FAÇA
 *   - registrar CPT direto: apollo-core é o dono do init:5.
 *     Fallback do owner só com post_type_exists().
 *   - gravar meta de outro domínio (hoje 227 chaves não têm dono).
 *   - registrar um segundo namespace REST. Só apollo/v1.
 *     Já existe um namespace fora do padrão no apollo-telegram.
 *   - add_shortcode() sem shortcode_exists(): o último a registrar
 *     vence em silêncio e quem roda vira acidente de ordem de carga.
 *
 * VERIFICAR   node D:/dev/_cos/verify/plugin-audit.js
 */

declare(strict_types=1);

namespace Apollo\Users;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // plan-001 · G07 — file was directly reachable over HTTP
}

$old_error_reporting = error_reporting();
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/radar') !== false) {
    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_COMPILE_ERROR | E_COMPILE_WARNING | E_RECOVERABLE_ERROR);
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define('APOLLO_USERS_VERSION', '1.1.0');
define('APOLLO_USERS_FILE', __FILE__);
define('APOLLO_USERS_DIR', plugin_dir_path(__FILE__));
define('APOLLO_USERS_URL', plugin_dir_url(__FILE__));
define('APOLLO_USERS_BASENAME', plugin_basename(__FILE__));

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if apollo-core and apollo-login are active
 */
function apollo_users_check_dependencies(): void
{
    $active_plugins = get_option('active_plugins', array());

    // Check apollo-core
    if (! in_array('apollo-core/apollo-core.php', $active_plugins, true)) {
        add_action(
            'admin_notices',
            function () {
                echo '<div class="notice notice-error"><p><strong>Apollo Users:</strong> Requires Apollo Core plugin to be active.</p></div>';
            }
        );
        deactivate_plugins(APOLLO_USERS_BASENAME);
        return;
    }

    // Check apollo-login
    if (! in_array('apollo-login/apollo-login.php', $active_plugins, true)) {
        add_action(
            'admin_notices',
            function () {
                echo '<div class="notice notice-error"><p><strong>Apollo Users:</strong> Requires Apollo Login plugin to be active.</p></div>';
            }
        );
        deactivate_plugins(APOLLO_USERS_BASENAME);
        return;
    }
}
add_action('plugins_loaded', __NAMESPACE__ . '\\apollo_users_check_dependencies', 5);

// ═══════════════════════════════════════════════════════════════════════════
// TEXTDOMAIN LOADING
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Load plugin textdomain for translations
 */
function apollo_users_load_textdomain(): void
{
    if (! is_textdomain_loaded('apollo-users')) {
        load_plugin_textdomain(
            'apollo-users',
            false,
            dirname(APOLLO_USERS_BASENAME) . '/languages/'
        );
    }
}
add_action('init', __NAMESPACE__ . '\\apollo_users_load_textdomain', 1);

/**
 * Suppress textdomain loading notices for this plugin
 */
function apollo_users_suppress_textdomain_notice($message, $error_type): string
{
    if (strpos($message, '_load_textdomain_just_in_time') !== false) {
        return ''; // Suppress this specific notice
    }
    return $message;
}
add_filter('wp_php_error_message', __NAMESPACE__ . '\\apollo_users_suppress_textdomain_notice', 10, 2);


// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER
// ═══════════════════════════════════════════════════════════════════════════

// Composer autoloader
if (file_exists(APOLLO_USERS_DIR . 'vendor/autoload.php')) {
    require_once APOLLO_USERS_DIR . 'vendor/autoload.php';
}

// Manual PSR-4 autoloader fallback
spl_autoload_register(
    function (string $class) {
        $prefix   = 'Apollo\\Users\\';
        $base_dir = APOLLO_USERS_DIR . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
);

// ═══════════════════════════════════════════════════════════════════════════
// INCLUDES
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_USERS_DIR . 'includes/constants.php';
require_once APOLLO_USERS_DIR . 'includes/functions.php';

// ═══════════════════════════════════════════════════════════════════════════
// VIRTUAL PAGES - PROFILE /id/{username}
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Register query vars
 */
function apollo_users_register_query_vars($vars)
{
    $vars[] = 'apollo_user_page';
    $vars[] = 'apollo_username';
    $vars[] = 'apollo_flush_rewrites';
    return $vars;
}
add_filter('query_vars', __NAMESPACE__ . '\\apollo_users_register_query_vars');

/**
 * Add custom rewrite rules
 */
function apollo_users_add_rewrite_rules(): void
{
    // Radar page: /radar
    add_rewrite_rule('^radar/?$', 'index.php?apollo_user_page=radar', 'top');

    // Profile pages: /id/{username}
    add_rewrite_rule('^id/([^/]+)/?$', 'index.php?apollo_user_page=profile&apollo_username=$matches[1]', 'top');

    // Edit profile: /editar-perfil
    add_rewrite_rule('^editar-perfil/?$', 'index.php?apollo_user_page=edit-profile', 'top');
}
add_action('init', __NAMESPACE__ . '\\apollo_users_add_rewrite_rules', 1);

/**
 * Handle virtual pages using wp hook
 */
function apollo_users_handle_virtual_pages($wp)
{
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

    // Radar page: /radar (user directory)
    if ($path === 'radar') {
        // Load the template directly
        $template_file = APOLLO_USERS_DIR . 'templates/user-radar.php';
        if (file_exists($template_file)) {
            // Set up WordPress environment
            global $wp_query;
            $wp_query->is_404 = false;
            status_header(200);
            $wp_query->query_vars['apollo_user_page'] = 'radar';

            // Enqueue assets
            apollo_users_enqueue_page_assets('radar');

            // Load template
            include $template_file;
            exit;
        }
    }

    // Profile pages: /id/{username}
    if (preg_match('#^id/([a-zA-Z0-9_.-]+)/?$#', $path, $matches)) {
        $username = sanitize_user($matches[1]);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Apollo Users: Looking up user '{$username}'"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        $user = get_user_by('login', $username);
        if (! $user) {
            $user = get_user_by('slug', $username);
        }

        if ($user && $user->ID > 0) {
            global $wp_query, $apollo_profile_user;
            $wp_query->is_404 = false;
            status_header(200);
            $apollo_profile_user = $user;

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Apollo Users: Found user '{$username}' with ID {$user->ID}"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }

            // Helper functions loaded from includes/functions.php (apollo_get_user_avatar_url, etc.)

            // Enqueue assets
            apollo_users_enqueue_page_assets('profile');

            include APOLLO_USERS_DIR . 'templates/single-profile.php';
            exit;
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Apollo Users: User '{$username}' not found"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
    }

    // Edit profile: /editar-perfil
    if ($path === 'editar-perfil') {
        if (! is_user_logged_in()) {
            wp_redirect(home_url('/acesso/'));
            exit;
        }

        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);

        // Helper functions loaded from includes/functions.php (apollo_get_user_avatar_url, etc.)

        apollo_users_enqueue_page_assets('edit-profile');

        include APOLLO_USERS_DIR . 'templates/edit-profile.php';
        exit;
    }
}
add_action('template_redirect', __NAMESPACE__ . '\\apollo_users_handle_virtual_pages', 1);

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Initialize plugin after apollo-core and apollo-login load
 */
function apollo_users_init(): void
{
    // Don't initialize on login/register pages to avoid conflicts
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($current_url, '/acesso') !== false || strpos($current_url, '/registre') !== false) {
        return;
    }

    // Check dependencies but don't fail completely
    $missing_deps = array();
    if (! defined('APOLLO_CORE_VERSION')) {
        $missing_deps[] = 'Apollo Core';
    }
    if (! defined('APOLLO_LOGIN_VERSION')) {
        $missing_deps[] = 'Apollo Login';
    }

    if (! empty($missing_deps)) {
        error_log('Apollo Users: Missing dependencies: ' . implode(', ', $missing_deps));
        // Continue anyway for testing
    }

    // Initialize main plugin class
    try {
        $plugin = Plugin::get_instance();
        $plugin->init();
    } catch (Exception $e) {
        error_log('Apollo Users: Failed to initialize plugin: ' . $e->getMessage());
    }
}
add_action('plugins_loaded', __NAMESPACE__ . '\\apollo_users_init', 20);

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
    __FILE__,
    function () {
        // Verify dependencies
        if (! is_plugin_active('apollo-core/apollo-core.php')) {
            wp_die('Apollo Users requires Apollo Core to be active.');
        }
        if (! is_plugin_active('apollo-login/apollo-login.php')) {
            wp_die('Apollo Users requires Apollo Login to be active.');
        }

        // Run activation tasks
        Activation::activate();

        // Add rewrite rules and flush
        apollo_users_add_rewrite_rules();
        flush_rewrite_rules();
    }
);

register_deactivation_hook(
    __FILE__,
    function () {
        Deactivation::deactivate();
        flush_rewrite_rules();
    }
);
