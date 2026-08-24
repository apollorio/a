<?php
/**
 * Apollo Pane Engine — Fragment Helper
 *
 * Handles X-PANE-TARGET header for AJAX fragment responses.
 * When a request arrives with X-PANE-TARGET:1, only the inner
 * content fragment is returned (no full shell), wrapped with CSP nonce.
 *
 * @package Apollo\PaneEngine
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Check if current request is a pane fragment request.
 *
 * @return bool
 */
function apollo_pane_is_fragment_request(): bool
{
    return ! empty($_SERVER['HTTP_X_PANE_TARGET'])
        && sanitize_text_field($_SERVER['HTTP_X_PANE_TARGET']) === '1';
}

/**
 * Generate a CSP nonce for inline scripts within fragments.
 *
 * @return string 22-char base64 nonce.
 */
function apollo_pane_csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

/**
 * Send CSP header with nonce for fragment responses.
 */
function apollo_pane_send_csp_header(): void
{
    if (headers_sent()) {
        return;
    }
    $nonce = apollo_pane_csp_nonce();
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'nonce-{$nonce}' https://cdn.apollo.rio.br; "
        . "style-src 'self' 'unsafe-inline' https://cdn.apollo.rio.br; "
        . "img-src 'self' data: https:; "
        . "connect-src 'self' https://cdn.apollo.rio.br; "
        . "font-src 'self' https://cdn.apollo.rio.br"
    );
}

/**
 * Wrap fragment HTML with CSP-safe container.
 *
 * @param string $html   Raw HTML content.
 * @param string $pane_id Target pane identifier.
 * @return string Wrapped HTML.
 */
function apollo_pane_wrap_fragment(string $html, string $pane_id = 'casa'): string
{
    $nonce    = esc_attr(apollo_pane_csp_nonce());
    $safe_id  = esc_attr(sanitize_key($pane_id));
    $safe_html = wp_kses_post($html);

    return '<div class="pane-fragment" data-pane="' . $safe_id . '" data-nonce="' . $nonce . '">'
        . $safe_html
        . '</div>';
}

/**
 * Map of every Apollo plugin to its main plugin file (relative to plugins dir).
 * Used by /pane/plugins for zero-touch active detection — no external plugin
 * needs to know about pane-engine.
 *
 * @return array<string, array{file: string, label: string, layer: string}>
 */
function apollo_pane_plugin_map(): array
{
    return [
        'apollo-core'       => ['file' => 'apollo-core/apollo-core.php',               'label' => 'Core',          'layer' => 'L0'],
        'apollo-login'      => ['file' => 'apollo-login/apollo-login.php',             'label' => 'Login',         'layer' => 'L1'],
        'apollo-users'      => ['file' => 'apollo-users/apollo-users.php',             'label' => 'Users',         'layer' => 'L1'],
        'apollo-membership' => ['file' => 'apollo-membership/apollo-membership.php',   'label' => 'Membership',    'layer' => 'L1'],
        'apollo-events'     => ['file' => 'apollo-events/apollo-events.php',           'label' => 'Events',        'layer' => 'L2'],
        'apollo-djs'        => ['file' => 'apollo-djs/apollo-djs.php',                 'label' => 'DJs',           'layer' => 'L2'],
        'apollo-loc'        => ['file' => 'apollo-loc/apollo-loc.php',                 'label' => 'Loc',           'layer' => 'L2'],
        'apollo-adverts'    => ['file' => 'apollo-adverts/apollo-adverts.php',         'label' => 'Adverts',       'layer' => 'L2'],
        'apollo-suppliers'  => ['file' => 'apollo-suppliers/apollo-suppliers.php',     'label' => 'Suppliers',     'layer' => 'L2'],
        'apollo-maps'       => ['file' => 'apollo-maps/apollo-maps.php',               'label' => 'Maps',          'layer' => 'L2'],
        'apollo-social'     => ['file' => 'apollo-social/apollo-social.php',           'label' => 'Social',        'layer' => 'L3'],
        'apollo-groups'     => ['file' => 'apollo-groups/apollo-groups.php',           'label' => 'Groups',        'layer' => 'L3'],
        'apollo-wow'        => ['file' => 'apollo-wow/apollo-wow.php',                 'label' => 'Wow',           'layer' => 'L3'],
        'apollo-fav'        => ['file' => 'apollo-fav/apollo-fav.php',                 'label' => 'Fav',           'layer' => 'L3'],
        'apollo-comment'    => ['file' => 'apollo-comment/apollo-comment.php',         'label' => 'Comment',       'layer' => 'L3'],
        'apollo-notif'      => ['file' => 'apollo-notif/apollo-notif.php',             'label' => 'Notif',         'layer' => 'L4'],
        'apollo-email'      => ['file' => 'apollo-email/apollo-email.php',             'label' => 'Email',         'layer' => 'L4'],
        'apollo-chat'       => ['file' => 'apollo-chat/apollo-chat.php',               'label' => 'Chat',          'layer' => 'L4'],
        'apollo-templates'  => ['file' => 'apollo-templates/apollo-templates.php',     'label' => 'Templates',     'layer' => 'L6'],
        'apollo-dashboard'  => ['file' => 'apollo-dashboard/apollo-dashboard.php',     'label' => 'Dashboard',     'layer' => 'L6'],
        'apollo-hub'        => ['file' => 'apollo-hub/apollo-hub.php',                 'label' => 'Hub',           'layer' => 'L6'],
        'apollo-radio'      => ['file' => 'apollo-radio/apollo-radio.php',             'label' => 'Radio',         'layer' => 'L6'],
        'apollo-statistics' => ['file' => 'apollo-statistics/apollo-statistics.php',   'label' => 'Statistics',    'layer' => 'L7'],
        'apollo-mod'        => ['file' => 'apollo-mod/apollo-mod.php',                 'label' => 'Mod',           'layer' => 'L7'],
        'apollo-seo'        => ['file' => 'apollo-seo/apollo-seo.php',                 'label' => 'SEO',           'layer' => 'L10'],
        'apollo-sheets'     => ['file' => 'apollo-sheets/apollo-sheets.php',           'label' => 'Sheets',        'layer' => 'L11'],
        'apollo-runtime'    => ['file' => 'apollo-runtime/apollo-runtime.php',         'label' => 'Runtime',       'layer' => 'L12'],
    ];
}

/**
 * REST API endpoint for active plugin detection.
 * Returns which Apollo plugins are currently active.
 * Self-contained — never requires changes in external plugins.
 *
 * GET /pane/plugins → { plugins: [{slug, label, layer, active}], count_active, count_total }
 */
add_action('rest_api_init', function (): void {
    register_rest_route('apollo/v1', '/pane/plugins', [
        'methods'             => 'GET',
        'callback'            => function (): \WP_REST_Response {
            if (! function_exists('is_plugin_active')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $map    = apollo_pane_plugin_map();
            $result = [];

            foreach ($map as $slug => $meta) {
                $active = is_plugin_active($meta['file']);
                $result[] = [
                    'slug'   => $slug,
                    'label'  => $meta['label'],
                    'layer'  => $meta['layer'],
                    'active' => $active,
                ];
            }

            $count_active = count(array_filter($result, fn($p) => $p['active']));

            return new \WP_REST_Response([
                'plugins'      => $result,
                'count_active' => $count_active,
                'count_total'  => count($result),
            ]);
        },
        'permission_callback' => function (): bool {
            return is_user_logged_in();
        },
    ]);
});

/**
 * REST API endpoint for fragment rendering.
 * Renders a named pane section as HTML fragment.
 * Supports HTMX requests (Accept: text/html) returning raw HTML.
 */
add_action('rest_api_init', function (): void {
    register_rest_route('apollo/v1', '/pane/fragment', [
        'methods'             => 'GET',
        'callback'            => function (\WP_REST_Request $request): \WP_REST_Response {
            $section = sanitize_key($request->get_param('section') ?? 'casa');
            $allowed = ['casa', 'gigs', 'sounds', 'spots', 'social', 'tools', 'chat-inbox'];

            if (! in_array($section, $allowed, true)) {
                return new \WP_REST_Response(['error' => 'Invalid section'], 400);
            }

            apollo_pane_send_csp_header();

            $nonce = apollo_pane_csp_nonce();
            $html  = '<div class="pane-section pane-section--' . esc_attr($section) . '">'
                . '<p class="pane-placeholder">' . esc_html(ucfirst($section)) . ' — carregando módulo…</p>'
                . '</div>';

            // HTMX requests prefer raw HTML
            $accept = $request->get_header('accept') ?? '';
            if (str_contains($accept, 'text/html')) {
                $response = new \WP_REST_Response($html);
                $response->set_status(200);
                $response->header('Content-Type', 'text/html; charset=UTF-8');
                return $response;
            }

            return new \WP_REST_Response([
                'html'    => $html,
                'section' => $section,
                'nonce'   => $nonce,
            ]);
        },
        'permission_callback' => function (): bool {
            return is_user_logged_in();
        },
        'args' => [
            'section' => [
                'required'          => false,
                'default'           => 'casa',
                'sanitize_callback' => 'sanitize_key',
            ],
        ],
    ]);
});
