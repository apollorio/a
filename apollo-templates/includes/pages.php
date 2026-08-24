<?php

/**
 * Apollo Templates - Page Creation & Rewrite Setup
 *
 * Creates required pages with specific templates.
 * Handles /classificados route mapping.
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Create pages on activation.
 * Classificados page creation moved to apollo-classifieds.
 */
function apollo_templates_create_pages(): void
{
    // No pages to create — classificados moved to apollo-classifieds.
}

/**
 * Add rewrite rules for custom pages
 */
function apollo_templates_add_rewrite_rules(): void
{
    // Classificados rewrite rules moved to apollo-classifieds.

    // /casa → page-home.php (guest landing — logged users redirected to /feed)
    add_rewrite_rule('^casa/?$', 'index.php?apollo_home_page=1', 'top');

    // /home → 301 to /casa (legacy slug)
    add_rewrite_rule('^home/?$', 'index.php?apollo_home_redirect=1', 'top');

    // /feed → page-feed.php (logged users only — guests redirected to /acesso)
    add_rewrite_rule('^feed/?$', 'index.php?apollo_feed_page=1', 'top');

    // /sobre → page-sobre.php (institutional / old home page)
    add_rewrite_rule('^sobre/?$', 'index.php?apollo_sobre_page=1', 'top');

    // /about-us → redirect to /sobre
    add_rewrite_rule('^about-us/?$', 'index.php?apollo_about_redirect=1', 'top');

    // /explore → redirect to /feed (deprecated slug)
    add_rewrite_rule('^explore/?$', 'index.php?apollo_explore_redirect=1', 'top');

    // /mural → redirect to /feed (deprecated slug)
    add_rewrite_rule('^mural/?$', 'index.php?apollo_mural_redirect=1', 'top');

    // /test → page-test.php (routes spreadsheet, admin only)
    add_rewrite_rule('^test/?$', 'index.php?apollo_test_page=1', 'top');
}
add_action('init', 'apollo_templates_add_rewrite_rules', 10);

// Register query vars for custom routes
add_filter(
    'query_vars',
    function (array $vars): array {
        $vars[] = 'apollo_home_page';
        $vars[] = 'apollo_home_redirect';
        $vars[] = 'apollo_feed_page';
        $vars[] = 'apollo_test_page';
        $vars[] = 'apollo_sobre_page';
        $vars[] = 'apollo_about_redirect';
        $vars[] = 'apollo_explore_redirect';
        $vars[] = 'apollo_mural_redirect';
        return $vars;
    }
);

/**
 * Fallback: Intercept custom routes via parse_request if rewrite rules fail (nginx compat)
 */
function apollo_templates_parse_request(\WP $wp): void
{
    $path = function_exists('apollo_normalize_request_path')
        ? apollo_normalize_request_path()
        : trim((string) parse_url(isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');

    if ($path === 'casa') {
        $wp->query_vars['apollo_home_page'] = '1';
    }

    if ($path === 'home') {
        $wp->query_vars['apollo_home_redirect'] = '1';
    }

    if ($path === 'feed') {
        $wp->query_vars['apollo_feed_page'] = '1';
        unset($wp->query_vars['feed']);
    }

    if ($path === 'test') {
        $wp->query_vars['apollo_test_page'] = '1';
    }

    if ($path === 'sobre') {
        $wp->query_vars['apollo_sobre_page'] = '1';
    }

    if ($path === 'about-us') {
        $wp->query_vars['apollo_about_redirect'] = '1';
    }

    if ($path === 'explore') {
        $wp->query_vars['apollo_explore_redirect'] = '1';
    }

    if ($path === 'mural') {
        $wp->query_vars['apollo_mural_redirect'] = '1';
    }
}
add_action('parse_request', 'apollo_templates_parse_request', 1);

/**
 * Neutralize WordPress core RSS on /feed — Apollo owns that slug.
 */
function apollo_templates_neutralize_core_feed_query(): void
{
    $path = function_exists('apollo_normalize_request_path')
        ? apollo_normalize_request_path()
        : '';

    if ($path !== 'feed' && ! get_query_var('apollo_feed_page')) {
        return;
    }

    if ($path === 'feed') {
        set_query_var('apollo_feed_page', '1');
    }

    global $wp_query;
    if ($wp_query instanceof \WP_Query) {
        $wp_query->is_feed = false;
        $wp_query->is_404   = false;
        $wp_query->is_home  = false;
        $wp_query->is_front_page = false;
    }

    // #region agent log
    if (defined('WP_DEBUG') && WP_DEBUG && function_exists('Apollo\Login\apollo_login_session_log')) {
        \Apollo\Login\apollo_login_session_log(
            'pages.php:neutralize_core_feed',
            'feed_route_claimed',
            array(
                'path'              => $path,
                'apollo_feed_page'  => get_query_var('apollo_feed_page', ''),
                'feed_var'          => get_query_var('feed', ''),
            ),
            'F1'
        );
    }
    // #endregion
}
add_action('wp', 'apollo_templates_neutralize_core_feed_query', 0);

/**
 * apollo_templates_load_template: logic migrated to apollo_templates_template_redirect()
 * in apollo-templates.php (template_redirect P10). Kept as no-op to avoid deprecated errors
 * if any external hook references it.
 *
 * @deprecated Removed in Wave 2.2 unification.
 */

/**
 * AJAX: Save test spreadsheet state
 */
function apollo_save_test_spreadsheet(): void
{
    check_ajax_referer('apollo_test_spreadsheet', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $state = json_decode(stripslashes($_POST['state'] ?? '{}'), true);

    if (! is_array($state)) {
        wp_send_json_error('Invalid data');
    }

    // Sanitize
    $clean = array();
    foreach ($state as $key => $data) {
        $clean[sanitize_key($key)] = array(
            'checked'         => ! empty($data['checked']),
            'comment'         => sanitize_textarea_field($data['comment'] ?? ''),
            'comment_checked' => ! empty($data['comment_checked']),
            'done'            => ! empty($data['done']),
        );
    }

    update_option('apollo_test_spreadsheet', $clean, false);
    wp_send_json_success();
}
add_action('wp_ajax_apollo_save_test_spreadsheet', 'apollo_save_test_spreadsheet');

/**
 * Flush rewrite rules on activation
 */
function apollo_templates_activate_pages(): void
{
    apollo_templates_create_pages();
    apollo_templates_add_rewrite_rules();
    flush_rewrite_rules();
}

/**
 * Run on plugin init
 */
add_action(
    'apollo/templates/initialized',
    function () {
        // Check if pages exist
        $classificados = get_page_by_path('classificados');
        if (! $classificados) {
            apollo_templates_create_pages();
            flush_rewrite_rules();
        }
    },
    10
);

/**
 * Flush rewrite rules once when the /casa route is missing from the ruleset.
 * Runs on admin_init to avoid performance impact on frontend requests.
 * Self-clears after one flush via transient.
 */
add_action(
    'admin_init',
    function (): void {
        if (get_transient('apollo_casa_rewrite_flushed')) {
            return;
        }

        $rules = get_option('rewrite_rules', array());

        if (! isset($rules['casa/?$']) && ! isset($rules['^casa/?$'])) {
            flush_rewrite_rules(false);
        }

        set_transient('apollo_casa_rewrite_flushed', '1', WEEK_IN_SECONDS);
    }
);
