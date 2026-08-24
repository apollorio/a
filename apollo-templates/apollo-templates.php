<?php

/**
 * Plugin Name: Apollo Templates
 * Plugin URI: https://apollo.rio.br/plugins/apollo-templates
 * Description: Templates: Page builder canvas, calendar types (01, 02...), PWA templates, all template variations
 * Version: 1.5.3
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-templates
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Templates
 */

declare(strict_types=1);

namespace Apollo\Templates;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

/* 1.4.5 — /casa mobile x-axis pass: F-02 safe-area fallbacks, F-03 month
   drop-up anchor, F-04 tracks-rail bleed, F-09 correctness one-liners.
   1.5.0 — /casa cell architecture (template-parts/new-home/cells/): a manifest
   plus two plug-n-play cells, `motion` (the single owner of Lenis↔ScrollTrigger
   and the declarative [data-casa-*] attributes) and `lightbox-event` (the
   single-event reader that closes F-10 — every guest event card previously
   carried data-to="event-page" href="#" and was bound by nobody).
   The bump is load-bearing: new-home.css is cache-busted by this constant
   (page-home.php), so nothing reaches a returning visitor without it.
   See _inventory/CASA-MAP-2026-08-08.md
   1.5.1 — Listing header block (includes/listing-header-api.php +
   template-parts/listing-header/): the approved lab "Header 02" (Kinetic Mask ·
   Apple) as a reusable, one-line archive header. First consumer is /eventos,
   which retired its own .pev-masthead for it. */
define('APOLLO_TEMPLATES_VERSION', '1.5.3');
define('APOLLO_TEMPLATES_FILE', __FILE__);
define('APOLLO_TEMPLATES_DIR', plugin_dir_path(__FILE__));
define('APOLLO_TEMPLATES_URL', plugin_dir_url(__FILE__));
define('APOLLO_TEMPLATES_BASENAME', plugin_basename(__FILE__));

if (defined('APOLLO_TEMPLATES_BOOTSTRAPPED')) {
    return;
}
define('APOLLO_TEMPLATES_BOOTSTRAPPED', true);

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if apollo-core is active (required).
 *
 * Shortcodes such as [apollo_events] are registered by this plugin
 * (includes/class-shortcodes.php). A separate "Apollo Shortcodes" plugin
 * is optional and not required for activation.
 */
function apollo_templates_check_dependencies(): void
{
    $active_plugins = get_option('active_plugins', array());

    // Check apollo-core
    if (! in_array('apollo-core/apollo-core.php', $active_plugins, true)) {
        add_action(
            'admin_notices',
            function () {
                echo '<div class="notice notice-error"><p><strong>Apollo Templates:</strong> Requires Apollo Core plugin to be active.</p></div>';
            }
        );
        deactivate_plugins(APOLLO_TEMPLATES_BASENAME);
        return;
    }
}
add_action('plugins_loaded', __NAMESPACE__ . '\\apollo_templates_check_dependencies', 5);

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER
// ═══════════════════════════════════════════════════════════════════════════

// Composer autoloader
if (file_exists(APOLLO_TEMPLATES_DIR . 'vendor/autoload.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'vendor/autoload.php';
}

// Manual PSR-4 autoloader fallback
spl_autoload_register(
    function (string $class) {
        $prefix   = 'Apollo\\Templates\\';
        $base_dir = APOLLO_TEMPLATES_DIR . 'src/';

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

if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/constants.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/constants.php';
}
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/functions.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/functions.php';
}
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/guest-home-guard.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/guest-home-guard.php';
}
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/landing-router.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/landing-router.php';
}

if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/mural-router.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/mural-router.php';
}
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/pages.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/pages.php';
}
/* Blank Canvas Apollo+ shell API — apollo_plus_open()/close()/part().
   Every phase's screen mounts through this, so the shell (topbar + aside +
   .ax-main) lives in exactly one place. */
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/apollo-plus-api.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/apollo-plus-api.php';
}
/* Listing header block — apollo_listing_header(). Archive-screen chrome
   (month scrubber + search + filter) shared by every listing surface, mounted
   through apollo_plus_part() like the rest of the Apollo+ system. */
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/listing-header-api.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/listing-header-api.php';
}
/* Feed screen data providers (phase 001) — real WP data standing in for the
   mockup's simulated.data.js globals, which are deliberately NOT ported. */
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/feed-data.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/feed-data.php';
}
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/weather-helpers.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/weather-helpers.php';
}
/* Mapa screen data provider (phase 006) — real events + loc CPT posts via
   apollo-maps' own REST logic, dispatched in-process. */
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/mapa-data.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/mapa-data.php';
}
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/class-persistent-ui.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/class-persistent-ui.php';
}
if (file_exists(APOLLO_TEMPLATES_DIR . 'includes/class-navbar-settings.php')) {
    require_once APOLLO_TEMPLATES_DIR . 'includes/class-navbar-settings.php';
}

// ═══════════════════════════════════════════════════════════════════════════
// FRONTEND EDITOR SYSTEM
// ═══════════════════════════════════════════════════════════════════════════
// Shared frontend editing engine for all Apollo CPTs.
// Plugins register fields via: add_filter( 'apollo_frontend_fields_{cpt}', ... )
// Plugins register CPTs via:   add_filter( 'apollo_editable_post_types', ... )
// URL pattern: /editar/{cpt}/{post_id}/

require_once APOLLO_TEMPLATES_DIR . 'src/FrontendFields.php';
require_once APOLLO_TEMPLATES_DIR . 'src/FrontendEditor.php';
require_once APOLLO_TEMPLATES_DIR . 'src/FrontendRouter.php';

// ═══════════════════════════════════════════════════════════════════════════
// GLOBAL NAVBAR API
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_TEMPLATES_DIR . 'includes/navbar-api.php';

// ═══════════════════════════════════════════════════════════════════════════
// ASSETS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Enqueue navbar assets on frontend
 */
function apollo_templates_enqueue_navbar_assets(): void
{
    // Skip if in admin
    if (is_admin()) {
        return;
    }

    // Global Apollo V2 Design System tokens + base components
    wp_enqueue_style(
        'apollo-v2-design-system',
        APOLLO_TEMPLATES_URL . 'assets/css/av2-design-system.css',
        array(),
        APOLLO_TEMPLATES_VERSION
    );

    // Enqueue navbar v2 CSS (pill navbar + FAB — replaces navbar.v1.css globally)
    wp_enqueue_style(
        'apollo-navbar',
        APOLLO_TEMPLATES_URL . 'assets/css/navbar.v2.css',
        array('apollo-v2-design-system'),
        APOLLO_TEMPLATES_VERSION
    );

    // Enqueue navbar v2 JS (FAB toggle, scroll detection, dropdowns)
    wp_enqueue_script(
        'apollo-navbar',
        APOLLO_TEMPLATES_URL . 'assets/js/navbar.v2.js',
        array(),
        APOLLO_TEMPLATES_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\apollo_templates_enqueue_navbar_assets', 20);

/**
 * Inject navbar HTML into wp_footer on regular WP pages.
 * Canvas pages (login, register, mural, profile, etc) include navbar.php manually.
 * This ensures the navbar DOM exists whenever navbar.js is enqueued.
 */
function apollo_templates_inject_navbar_footer(): void
{
    if (is_admin()) {
        return;
    }

    // Skip on apollo virtual pages that use Blank Canvas (they include navbar manually)
    $page = get_query_var('apollo_login_page', '');
    if (! empty($page)) {
        return;
    }

    if (function_exists('apollo_is_reserved_virtual_path') && apollo_is_reserved_virtual_path()) {
        return;
    }

    // Skip on chat / mensagens pages (apollo-chat owns that UI)
    if (is_page(array('mensagens', 'chat', 'inbox'))) {
        return;
    }

    // Skip if navbar was already included by a canvas template
    if (defined('APOLLO_NAVBAR_LOADED')) {
        return;
    }

    $navbar_path = APOLLO_TEMPLATES_DIR . 'templates/template-parts/navbar.v2.php';
    if (file_exists($navbar_path)) {
        include $navbar_path;
    }
}
add_action('wp_footer', __NAMESPACE__ . '\\apollo_templates_inject_navbar_footer', 5);

// ═══════════════════════════════════════════════════════════════════════════
// PAGE TEMPLATE REGISTRATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Register plugin-provided page templates in the WP admin Page Template dropdown.
 * This makes "Apollo Home" and future templates selectable from any page.
 */
function apollo_templates_register_page_templates(array $templates): array
{
    $plugin_templates = array(
        'templates/page-home.php'          => __('Apollo Home', 'apollo-templates'),
        'templates/page-feed.php'          => __('Apollo Feed', 'apollo-templates'),
        'templates/page-sobre.php'         => __('Apollo Sobre', 'apollo-templates'),
        'templates/page-mural.php'         => __('Apollo Mural', 'apollo-templates'),
        'templates/page-mapa.php'          => __('Apollo Mapa', 'apollo-templates'),
    );

    return array_merge($templates, $plugin_templates);
}
add_filter('theme_page_templates', __NAMESPACE__ . '\\apollo_templates_register_page_templates', 10);

/**
 * Unified template_redirect handler (P10).
 * Handles all canvas page overrides and virtual routes for apollo-templates.
 * Fires AFTER apollo-login (P1) and before theme-level fallbacks.
 */
function apollo_templates_template_redirect(): void
{
    global $wp_query;

    // Guard: apollo-login is already handling this virtual page.
    if (! empty(get_query_var('apollo_login_page', ''))) {
        return;
    }

    // Path fallbacks when rewrite rules are stale (LocalWP / nginx).
    if (function_exists('apollo_normalize_request_path')) {
        $apollo_path = apollo_normalize_request_path();
        if ($apollo_path === 'feed' && empty(get_query_var('apollo_feed_page', ''))) {
            set_query_var('apollo_feed_page', '1');
        }
        if ($apollo_path === 'sobre' && empty(get_query_var('apollo_sobre_page', ''))) {
            set_query_var('apollo_sobre_page', '1');
        }
        if ($apollo_path === 'casa' && empty(get_query_var('apollo_home_page', ''))) {
            set_query_var('apollo_home_page', '1');
        }
    }

    // Virtual pages set by rewrite rules / parse_request fallback.
    if (get_query_var('apollo_home_redirect')) {
        wp_safe_redirect(home_url('/casa'), 301);
        exit;
    }

    if (get_query_var('apollo_about_redirect')) {
        wp_safe_redirect(home_url('/sobre'), 301);
        exit;
    }

    // /explore and /mural → canonical /feed (301)
    if (get_query_var('apollo_explore_redirect') || get_query_var('apollo_mural_redirect')) {
        wp_safe_redirect(home_url('/feed'), 301);
        exit;
    }

    if (get_query_var('apollo_home_page')) {
        $wp_query->is_404  = false;
        $wp_query->is_home = false;
        status_header(200);
        // Logged users are redirected to /feed by mural-router (P5)
        $tpl = APOLLO_TEMPLATES_DIR . 'templates/page-home.php';
        if (file_exists($tpl)) {
            include $tpl;
            exit;
        }
        return;
    }

    if (get_query_var('apollo_feed_page')) {
        $wp_query->is_404  = false;
        $wp_query->is_feed = false;
        $wp_query->is_home = false;
        if (! is_user_logged_in()) {
            wp_safe_redirect(home_url('/acesso'));
            exit;
        }
        status_header(200);
        $tpl = APOLLO_TEMPLATES_DIR . 'templates/page-feed.php';
        if (file_exists($tpl)) {
            // #region agent log
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('Apollo\Login\apollo_login_session_log')) {
                \Apollo\Login\apollo_login_session_log(
                    'apollo-templates.php:template_redirect',
                    'serving_feed_template',
                    array('template' => $tpl),
                    'F1'
                );
            }
            // #endregion
            include $tpl;
            exit;
        }
        return;
    }

    if (get_query_var('apollo_sobre_page')) {
        $tpl = APOLLO_TEMPLATES_DIR . 'templates/page-sobre.php';
        if (file_exists($tpl)) {
            $wp_query->is_404  = false;
            $wp_query->is_home = false;
            status_header(200);
            include $tpl;
            exit;
        }
    }

    if (get_query_var('apollo_test_page')) {
        $tpl = APOLLO_TEMPLATES_DIR . 'templates/page-test.php';
        if (file_exists($tpl)) {
            $wp_query->is_404  = false;
            $wp_query->is_home = false;
            status_header(200);
            include $tpl;
            exit;
        }
    }

    // /mapa page.
    if (is_page('mapa')) {
        $tpl = APOLLO_TEMPLATES_DIR . 'templates/page-mapa.php';
        if (file_exists($tpl)) {
            include $tpl;
            exit;
        }
    }

    // Assigned WP page template (from Page Template dropdown).
    // Skip when URL is a reserved Apollo virtual slug (e.g. WP Page "acesso" + Apollo Home template).
    if (is_page() && function_exists('apollo_is_reserved_virtual_path') && apollo_is_reserved_virtual_path()) {
        return;
    }

    if (is_page()) {
        $page_template_slug = get_page_template_slug();
        $our_templates      = array(
            'templates/page-home.php',
            'templates/page-sobre.php',
            'templates/page-mural.php',
            'templates/page-mapa.php',
        );
        if (! empty($page_template_slug) && in_array($page_template_slug, $our_templates, true)) {
            $tpl = APOLLO_TEMPLATES_DIR . $page_template_slug;
            if (file_exists($tpl)) {
                include $tpl;
                exit;
            }
        }
    }

}
add_action('template_redirect', __NAMESPACE__ . '\\apollo_templates_template_redirect', 10);

/**
 * Hide WordPress Admin Bar on frontend (show only in wp-admin)
 */
add_filter(
    'show_admin_bar',
    function ($show) {
        return is_admin() ? $show : false;
    },
    999
);

/**
 * AJAX Login Handler for Navbar
 */
function apollo_navbar_login(): void
{
    // Verify nonce
    if (! isset($_POST['apollo_login_nonce']) || ! wp_verify_nonce($_POST['apollo_login_nonce'], 'apollo_login_action')) {
        wp_send_json_error(array('message' => 'Nonce inválido'), 403);
    }

    $username = sanitize_text_field(wp_unslash($_POST['user'] ?? ''));
    $password = wp_unslash($_POST['pass'] ?? '');
    $remember = isset($_POST['remember']) && sanitize_text_field(wp_unslash($_POST['remember'])) === '1';

    if (empty($username) || empty($password)) {
        wp_send_json_error(array('message' => 'Preencha todos os campos'));
    }

    $credentials = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    );

    $user = wp_signon($credentials, is_ssl());

    if (is_wp_error($user)) {
        wp_send_json_error(array('message' => 'Usuário ou senha incorretos'));
    }

    wp_send_json_success(
        array(
            'message'  => 'Login realizado com sucesso',
            'redirect' => home_url(),
        )
    );
}
add_action('wp_ajax_nopriv_apollo_navbar_login', __NAMESPACE__ . '\\apollo_navbar_login');

/**
 * AJAX Login Handler for Panel Acesso (guest-only panel)
 */
function apollo_panel_login(): void
{
    if (! isset($_POST['acesso_login_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['acesso_login_nonce'])), 'apollo_acesso_login')) {
        wp_send_json_error('Nonce inválido', 403);
    }

    $username = sanitize_text_field(wp_unslash($_POST['log'] ?? ''));
    $password = wp_unslash($_POST['pwd'] ?? '');
    $remember = ! empty($_POST['rememberme']);

    if (empty($username) || empty($password)) {
        wp_send_json_error('Preencha todos os campos');
    }

    $user = wp_signon(
        array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ),
        is_ssl()
    );

    if (is_wp_error($user)) {
        wp_send_json_error('Usuário ou senha incorretos');
    }

    wp_send_json_success('Login realizado');
}
add_action('wp_ajax_nopriv_apollo_panel_login', __NAMESPACE__ . '\\apollo_panel_login');

/**
 * AJAX Handler for Public Event Suggestion (no auth required)
 *
 * Saves as a pending 'event' CPT draft for admin review.
 * Required: event_day, event_month, event_year, event_name, event_ticket_url
 * Optional: event_loc, event_djs
 */
function apollo_suggest_event(): void
{
    if (! isset($_POST['suggest_event_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['suggest_event_nonce'])), 'apollo_suggest_event')) {
        wp_send_json_error('Nonce inválido', 403);
    }

    $day   = absint($_POST['event_day'] ?? 0);
    $month = absint($_POST['event_month'] ?? 0);
    $year  = absint($_POST['event_year'] ?? 0);
    $name  = sanitize_text_field(wp_unslash($_POST['event_name'] ?? ''));
    $url   = esc_url_raw(wp_unslash($_POST['event_ticket_url'] ?? ''));
    $loc   = sanitize_text_field(wp_unslash($_POST['event_loc'] ?? ''));
    $djs   = sanitize_text_field(wp_unslash($_POST['event_djs'] ?? ''));

    // Validate mandatory fields
    if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $year < (int) gmdate('Y')) {
        wp_send_json_error('Data inválida');
    }
    if (empty($name)) {
        wp_send_json_error('Nome do evento é obrigatório');
    }
    if (empty($url)) {
        wp_send_json_error('Link dos ingressos é obrigatório');
    }

    // Build event date
    $event_date = sprintf('%04d-%02d-%02d', $year, $month, $day);

    // Create pending event post
    $post_id = wp_insert_post(
        array(
            'post_type'   => 'event',
            'post_title'  => $name,
            'post_status' => 'pending',
            'post_author' => 0,
            'meta_input'  => array(
                '_event_start_date' => $event_date,
                '_event_ticket_url' => $url,
                '_event_loc_id'     => absint($loc),
                '_event_dj_ids'     => $djs,
                '_event_source'     => 'public_suggestion',
            ),
        ),
        true
    );

    if (is_wp_error($post_id)) {
        wp_send_json_error('Erro ao salvar sugestão');
    }

    /**
     * Fires after a public event suggestion is saved.
     *
     * @param int    $post_id   Created post ID
     * @param string $event_date Formatted date YYYY-MM-DD
     */
    do_action('apollo/events/suggestion_created', $post_id, $event_date);

    wp_send_json_success('Sugestão enviada! Nossa equipe vai revisar.');
}
add_action('wp_ajax_nopriv_apollo_suggest_event', __NAMESPACE__ . '\\apollo_suggest_event');
add_action('wp_ajax_apollo_suggest_event', __NAMESPACE__ . '\\apollo_suggest_event');

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Initialize plugin after apollo-core loads
 */
function apollo_templates_init(): void
{
    // Verify apollo-core is loaded
    if (! defined('APOLLO_CORE_VERSION')) {
        return;
    }

    // Initialize main plugin class
    $plugin = Plugin::get_instance();
    $plugin->init();

    // Initialize Frontend Editor system
    $frontend_editor = FrontendEditor::get_instance();
    $frontend_editor->init();

    // Initialize Frontend Router (virtual edit pages)
    $frontend_router = FrontendRouter::get_instance();
    $frontend_router->init();
}
add_action('plugins_loaded', __NAMESPACE__ . '\\apollo_templates_init', 15);

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
    __FILE__,
    function () {
        // Verify dependencies
        if (! is_plugin_active('apollo-core/apollo-core.php')) {
            wp_die('Apollo Templates requires Apollo Core to be active.');
        }

        // Run activation tasks
        if (class_exists(__NAMESPACE__ . '\\Activation')) {
            Activation::activate();
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
);

register_deactivation_hook(
    __FILE__,
    function () {
        if (class_exists(__NAMESPACE__ . '\\Deactivation')) {
            Deactivation::deactivate();
        }
        flush_rewrite_rules();
    }
);
