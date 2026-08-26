<?php

/**
 * Security Headers
 *
 * Sends HTTP security headers on every frontend response:
 * X-Content-Type-Options, X-Frame-Options, X-XSS-Protection,
 * Strict-Transport-Security, Content-Security-Policy (with dynamic nonces),
 * Referrer-Policy, Permissions-Policy, X-Permitted-Cross-Domain-Policies,
 * and removes PHP/WP server signature headers.
 *
 * @package Apollo\Login\Security
 */

declare(strict_types=1);

namespace Apollo\Login\Security;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SecurityHeaders class
 */
class SecurityHeaders
{

    /**
     * Per-request CSP nonce (base64, 16 bytes entropy)
     *
     * @var string
     */
    private string $csp_nonce = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Generate nonce once per request, before any output
        $this->csp_nonce = base64_encode(random_bytes(16));

        // Expose nonce globally for templates
        $GLOBALS['apollo_csp_nonce'] = $this->csp_nonce;

        add_action('send_headers', array($this, 'send_security_headers'));
        add_action('login_init', array($this, 'send_security_headers'));  // also fires on wp-login.php
        add_filter('wp_headers', array($this, 'filter_headers'));

        // Inject <meta> nonce tag for core.js dynamic script loading
        add_action('wp_head', array($this, 'print_nonce_meta'), 1);

        // Add nonce attribute to WP-enqueued scripts
        add_filter('script_loader_tag', array($this, 'add_nonce_to_script'), 10, 3);

        // Register CSP violation report endpoint
        add_action('rest_api_init', array($this, 'register_csp_report_endpoint'));

        // Remove X-Powered-By (PHP disclosure)
        $this->remove_php_exposure();
    }

    /**
     * Get the current request's CSP nonce
     *
     * @return string
     */
    public function get_nonce(): string
    {
        return $this->csp_nonce;
    }

    /**
     * Print <meta> tag with CSP nonce for core.js to read
     *
     * @return void
     */
    public function print_nonce_meta(): void
    {
        printf(
            '<meta name="apollo-csp-nonce" content="%s">' . "\n",
            esc_attr($this->csp_nonce)
        );
    }

    /**
     * Add nonce attribute to WP-enqueued script tags
     *
     * @param string $tag    The <script> tag HTML.
     * @param string $handle The script handle.
     * @param string $src    The script source URL.
     * @return string
     */
    public function add_nonce_to_script(string $tag, string $handle, string $src): string
    {
        // Skip if already has nonce
        if (str_contains($tag, 'nonce=')) {
            return $tag;
        }

        return str_replace('<script ', '<script nonce="' . esc_attr($this->csp_nonce) . '" ', $tag);
    }

    /**
     * Send security headers via WordPress hooks
     *
     * @return void
     */
    public function send_security_headers(): void
    {
        if (headers_sent()) {
            return;
        }

        // Prevent MIME-type sniffing
        header('X-Content-Type-Options: nosniff');

        // Prevent clickjacking — allow only same-origin iframes
        header('X-Frame-Options: SAMEORIGIN');

        // Referrer policy — don't leak URL path to third parties
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Prevent Adobe cross-domain policy loading
        header('X-Permitted-Cross-Domain-Policies: none');

        // HSTS — only if HTTPS (matches htaccess: 2 years + preload)
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
        }

        // Permissions Policy — restrict sensitive browser APIs
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        // Content Security Policy — strict nonce + strict-dynamic for normal theme pages;
        // relaxed policy for virtual auth templates (no wp_head / inline scripts).
        $nonce = $this->csp_nonce;

        $blank_canvas = $this->is_apollo_blank_canvas_request();

        /**
         * Blank-canvas Apollo pages (auth, events, hubs, …) skip wp_head() and emit
         * parser scripts without WP nonces. `strict-dynamic` ignores host allowlists
         * and blocks those tags — including mandatory cdn.apollo.rio.br/v1.0.0/core.js.?versao=bb
         * Use the relaxed script-src (hosts + unsafe-inline) for every blank canvas.
         *
         * @param array  $directives Associative array of directive => value
         * @param string $nonce     The per-request CSP nonce
         */
        if ($blank_canvas) {
            $directives = apply_filters(
                'apollo/security/csp_directives',
                array(
                    'default-src' => "'self'",
                    /* No strict-dynamic: allow same-origin plugin scripts + inline bootstraps. */
                    'script-src'  => "'self' 'unsafe-inline' https://cdn.apollo.rio.br http://cdn.apollo.rio.br https://cdn.jsdelivr.net https://apollo.rio.br https://www.google.com https://www.gstatic.com https://www.recaptcha.net",
                    'style-src'   => "'self' 'unsafe-inline' https://cdn.apollo.rio.br http://cdn.apollo.rio.br https://cdn.jsdelivr.net https://fonts.googleapis.com",
                    'font-src'    => "'self' https://cdn.apollo.rio.br http://cdn.apollo.rio.br https://cdn.jsdelivr.net https://assets.apollo.rio.br http://assets.apollo.rio.br https://fonts.gstatic.com data:",
                    'img-src'     => "'self' data: blob: https: http:",
                    'connect-src' => "'self' https://cdn.apollo.rio.br http://cdn.apollo.rio.br https://cdn.jsdelivr.net https://assets.apollo.rio.br http://assets.apollo.rio.br",
                    'media-src'   => "'self' https://assets.apollo.rio.br http://assets.apollo.rio.br blob: data:",
                    'frame-src'   => "'self' https://assets.apollo.rio.br http://assets.apollo.rio.br https://www.google.com https://www.recaptcha.net https://www.youtube.com https://www.youtube-nocookie.com",
                    'object-src'  => "'none'",
                    'base-uri'    => "'self'",
                    'form-action' => "'self'",
                ),
                $nonce
            );
        } else {
            $directives = apply_filters(
                'apollo/security/csp_directives',
                array(
                    'default-src' => "'self'",
                    'script-src'  => "'self' 'nonce-{$nonce}' 'strict-dynamic' https://cdn.apollo.rio.br https://www.google.com https://www.gstatic.com https://www.recaptcha.net",
                    'style-src'   => "'self' 'unsafe-inline' https://cdn.apollo.rio.br https://fonts.googleapis.com",
                    'font-src'    => "'self' https://cdn.apollo.rio.br https://fonts.gstatic.com data:",
                    'img-src'     => "'self' data: blob: https:",
                    'connect-src' => "'self' https://cdn.apollo.rio.br https://assets.apollo.rio.br",
                    'media-src'   => "'self' https://assets.apollo.rio.br blob: data:",
                    'frame-src'   => "'self' https://assets.apollo.rio.br https://www.google.com https://www.recaptcha.net https://www.youtube.com https://www.youtube-nocookie.com",
                    'object-src'  => "'none'",
                    'base-uri'    => "'self'",
                    'form-action' => "'self'",
                ),
                $nonce
            );
        }

        $csp_parts = array();
        foreach ($directives as $directive => $value) {
            $csp_parts[] = "{$directive} {$value}";
        }
        $csp = implode('; ', $csp_parts);

        header("Content-Security-Policy: {$csp}");

        // Remove server info headers
        header_remove('X-Powered-By');
        header_remove('Server');
    }

    /**
     * Filter WP headers array (adds security headers at wp_send_headers stage)
     *
     * @param array $headers
     * @return array
     */
    public function filter_headers(array $headers): array
    {
        $headers['X-Content-Type-Options']            = 'nosniff';
        $headers['Referrer-Policy']                   = 'strict-origin-when-cross-origin';
        $headers['X-Permitted-Cross-Domain-Policies'] = 'none';

        // Remove server leakage
        unset($headers['X-Powered-By']);

        return $headers;
    }

    /**
     * Remove PHP version exposure from headers at PHP level
     *
     * @return void
     */
    private function remove_php_exposure(): void
    {
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }
        // Also via INI if available
        if (function_exists('ini_set')) {
            @ini_set('expose_php', '0');
        }
    }

    /**
     * Register CSP violation report REST endpoint
     */
    public function register_csp_report_endpoint(): void
    {
        $namespace = defined('APOLLO_REST_NAMESPACE') ? APOLLO_REST_NAMESPACE : 'apollo/v1';

        register_rest_route($namespace, '/csp-report', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_csp_report'),
            'permission_callback' => '__return_true', // Public by design and a nonce is IMPOSSIBLE here — browsers generate CSP reports themselves and cannot attach X-WP-Nonce. Not rate-limited; writes no row, error_log() only under WP_DEBUG.
        ));
    }

    /**
     * Handle CSP violation reports — log and discard
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_csp_report(\WP_REST_Request $request): \WP_REST_Response
    {
        $body = $request->get_body();
        $report = json_decode($body, true);

        if (! empty($report) && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Apollo CSP Report] ' . wp_json_encode($report['csp-report'] ?? $report));
        }

        return new \WP_REST_Response(null, 204);
    }

    /**
     * Blank-canvas Apollo pages skip wp_head() — strict CSP nonce mode blocks core.js.
     *
     * Covers auth virtual routes, CPT singles (evento/dj/local/…), and
     * apollo_is_blank_canvas_request() query-var canvas pages.
     *
     * @return bool
     */
    private function is_apollo_blank_canvas_request(): bool
    {
        if ( function_exists( 'apollo_is_blank_canvas_request' ) && apollo_is_blank_canvas_request() ) {
            return true;
        }

        if ( function_exists( 'get_query_var' ) && is_string( get_query_var( 'apollo_login_page', '' ) ) && get_query_var( 'apollo_login_page', '' ) !== '' ) {
            return true;
        }

        // CPT singles / archives that use blank-canvas plugin templates (no wp_head).
        $blank_post_types = array( 'evento', 'event', 'dj', 'local', 'loc', 'hub', 'classified' );
        if ( function_exists( 'is_singular' ) && is_singular( $blank_post_types ) ) {
            return true;
        }
        if ( function_exists( 'is_post_type_archive' ) && is_post_type_archive( $blank_post_types ) ) {
            return true;
        }

        $path = '';
        if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
            $path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
        }
        $path = trim( (string) $path, '/' );

        $candidates = array(
            'registre',
            'verificar-email',
            'sair',
            'reset',
            'telegram',
            'evento',
            'eventos',
            'dj',
            'djs',
            'local',
            'locais',
            'hub',
            'classificados',
            'novo-evento',
            'editar-evento',
            'meus-eventos',
        );
        if ( defined( 'APOLLO_LOGIN_CUSTOM_LOGIN_SLUG' ) ) {
            $candidates[] = (string) constant( 'APOLLO_LOGIN_CUSTOM_LOGIN_SLUG' );
        }
        if ( defined( 'APOLLO_LOGIN_LOGIN_SLUG_ALIASES' ) ) {
            $aliases = constant( 'APOLLO_LOGIN_LOGIN_SLUG_ALIASES' );
            if ( is_array( $aliases ) ) {
                foreach ( $aliases as $slug ) {
                    $candidates[] = (string) $slug;
                }
            }
        }

        foreach ( array_unique( $candidates ) as $slug ) {
            $slug = sanitize_title( $slug );
            if ( $slug === '' ) {
                continue;
            }
            if ( $path === $slug || str_starts_with( $path, $slug . '/' ) ) {
                return true;
            }
        }

        return (bool) apply_filters( 'apollo/security/is_blank_canvas_csp', false, $path );
    }
}
