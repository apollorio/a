<?php
/**
 * Apollo Pane Engine — Functions (enqueue, rewrite, query vars)
 *
 * /casa is owned by apollo-templates (guest landing). Pane lab SPA uses /painel-lab
 * unless APOLLO_PANE_PRIMARY is true (legacy: pane hijacks /casa).
 *
 * @package Apollo\PaneEngine
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Whether pane engine should register /casa (overrides templates landing).
 */
function apollo_pane_engine_owns_casa(): bool
{
    return defined('APOLLO_PANE_PRIMARY') && APOLLO_PANE_PRIMARY;
}

/* ── Rewrite Rules ────────────────────────────────────────────────── */

/**
 * Register pane rewrite rules.
 */
function apollo_pane_engine_add_rewrite_rules(): void
{
    if (apollo_pane_engine_owns_casa()) {
        add_rewrite_rule('^casa/?$', 'index.php?apollo_casa_page=1', 'top');
        add_rewrite_rule('^casa/([^/]+)/?$', 'index.php?apollo_casa_page=1', 'top');
    } else {
        add_rewrite_rule('^painel-lab/?$', 'index.php?apollo_casa_page=1', 'top');
        add_rewrite_rule('^painel-lab/([^/]+)/?$', 'index.php?apollo_casa_page=1', 'top');
    }
}
add_action('init', 'apollo_pane_engine_add_rewrite_rules', 20);

/**
 * Register query var.
 */
add_filter(
    'query_vars',
    function (array $vars): array {
        $vars[] = 'apollo_casa_page';
        return $vars;
    }
);

/**
 * Parse request fallback (nginx compatibility).
 */
add_action(
    'parse_request',
    function (\WP $wp): void {
        $raw_uri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
            : '';
        $path = trim((string) parse_url($raw_uri, PHP_URL_PATH), '/');

        if (function_exists('apollo_normalize_request_path')) {
            $path = apollo_normalize_request_path();
        }

        if (apollo_pane_engine_owns_casa() && $path === 'casa') {
            $wp->query_vars['apollo_casa_page'] = '1';
            return;
        }

        if (! apollo_pane_engine_owns_casa() && ($path === 'painel-lab' || str_starts_with($path, 'painel-lab/'))) {
            $wp->query_vars['apollo_casa_page'] = '1';
        }
    },
    1
);

/**
 * Template redirect — serve page-casa.php (pane lab SPA).
 */
add_action(
    'template_redirect',
    function (): void {
        if (! get_query_var('apollo_casa_page')) {
            return;
        }

        global $wp_query;
        $wp_query->is_404  = false;
        $wp_query->is_home = false;

        if (! is_user_logged_in()) {
            wp_safe_redirect(home_url('/acesso'));
            exit;
        }

        status_header(200);
        nocache_headers();
        $tpl = APOLLO_PANE_ENGINE_PATH . 'templates/page-casa.php';
        if (file_exists($tpl)) {
            include $tpl;
            exit;
        }
    },
    apollo_pane_engine_owns_casa() ? 5 : 15
);

/**
 * Prevent WordPress redirect_canonical from interfering with pane routes.
 */
add_filter(
    'redirect_canonical',
    function ($redirect_url, string $requested_url) {
        if (get_query_var('apollo_casa_page')) {
            return false;
        }
        return $redirect_url;
    },
    10,
    2
);

/* ── Flush rewrites once on admin_init if pane rules missing ──────── */
add_action(
    'admin_init',
    function (): void {
        if (get_transient('apollo_pane_engine_rewrite_v2')) {
            return;
        }
        $rules = get_option('rewrite_rules', []);
        $key   = apollo_pane_engine_owns_casa() ? 'casa' : 'painel-lab';
        $has   = isset($rules[ $key . '/?$' ]) || isset($rules[ '^' . $key . '/?$' ]);
        if (! $has) {
            flush_rewrite_rules(false);
        }
        set_transient('apollo_pane_engine_rewrite_v2', '1', WEEK_IN_SECONDS);
    }
);

/* Post-login redirect: /feed (apollo-templates). Pane /casa auth overrides removed. */
