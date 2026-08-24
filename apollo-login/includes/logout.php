<?php

/**
 * Logout flow — WP-style `?action=logout` for every Apollo front-end route.
 *
 * WordPress answers a missing/stale `log-out` nonce with an error interstitial.
 * Apollo renders its logout link inside long-lived navbar markup, so the nonce is
 * routinely older than the visitor's current session and members used to land on a
 * dead end. Every branch here ends in a redirect: `redirect_to` when it is safe,
 * the apollo.rio.br main page otherwise.
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Destination when the request carries no usable `redirect_to`.
 */
function apollo_login_default_logout_redirect(): string
{
    /**
     * Filters the fallback logout destination.
     *
     * @param string $url Absolute URL (site home by default).
     */
    return (string) apply_filters('apollo/login/logout_redirect_default', home_url('/')); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
}

/**
 * Requested `redirect_to`, sanitized but not yet host-validated.
 */
function apollo_login_requested_logout_redirect(): string
{
    if (empty($_REQUEST['redirect_to'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return '';
    }

    $raw = wp_unslash($_REQUEST['redirect_to']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    return is_string($raw) ? wp_sanitize_redirect($raw) : '';
}

/**
 * `action` query/body value as a slug ('' when absent or non-scalar).
 *
 * @param array<string, mixed> $source `$_GET` or `$_REQUEST`.
 */
function apollo_login_request_action(array $source): string
{
    if (! isset($source['action'])) {
        return '';
    }

    $raw = wp_unslash($source['action']);

    return is_string($raw) ? sanitize_key($raw) : '';
}

/**
 * Whether the logout request originated from Apollo itself.
 *
 * Consulted only when the `log-out` nonce is stale. It keeps drive-by cross-site
 * logout requests from taking effect while still letting a real member out on the
 * first click, without an error page in either case.
 */
function apollo_login_logout_request_is_first_party(): bool
{
    $fetch_site = isset($_SERVER['HTTP_SEC_FETCH_SITE'])
        ? strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_SEC_FETCH_SITE'])))
        : '';

    if ($fetch_site !== '') {
        // "none" means no initiating document: typed URL, bookmark or restored tab.
        return in_array($fetch_site, array('same-origin', 'same-site', 'none'), true);
    }

    $referer = wp_get_raw_referer();
    if (is_string($referer) && $referer !== '') {
        $referer_host = strtolower((string) wp_parse_url($referer, PHP_URL_HOST));
        $site_host    = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));

        if ($referer_host === '' || $site_host === '') {
            return false;
        }

        return $referer_host === $site_host || str_ends_with($referer_host, '.' . $site_host);
    }

    // No Sec-Fetch-Site and no Referer (old browser, no-referrer policy): members get
    // the friendly path, privileged accounts still need a valid nonce.
    return ! current_user_can('manage_options');
}

/**
 * Process a WP-style logout request (`action=logout` + optional `redirect_to` / `_wpnonce`).
 *
 * Never renders an error page. A stale nonce on a cross-site request degrades to
 * "redirect without ending the session" instead of the WordPress dead end.
 *
 * @param string $default_redirect Fallback when `redirect_to` is missing or unsafe.
 */
function apollo_login_process_logout_request(string $default_redirect = ''): void
{
    // Several routes point at this handler (/acesso, /sair, wp-login.php, catch-all).
    if (defined('APOLLO_LOGIN_LOGOUT_IN_PROGRESS')) {
        return;
    }
    define('APOLLO_LOGIN_LOGOUT_IN_PROGRESS', true);

    nocache_headers();

    $user      = wp_get_current_user();
    $requested = apollo_login_requested_logout_redirect();

    if ($default_redirect === '') {
        $default_redirect = apollo_login_default_logout_redirect();
    }

    $redirect_to = $requested !== ''
        ? wp_validate_redirect($requested, $default_redirect)
        : $default_redirect;

    // Session already gone — double click, refresh, back button, logout in another tab.
    if (! is_user_logged_in()) {
        apollo_login_finish_logout($redirect_to, $requested, $user, $default_redirect);
    }

    $nonce = isset($_REQUEST['_wpnonce']) && is_string($_REQUEST['_wpnonce']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        : '';

    $nonce_valid = $nonce !== '' && false !== wp_verify_nonce($nonce, 'log-out');

    if ($nonce_valid || apollo_login_logout_request_is_first_party()) {
        wp_logout();
    }

    apollo_login_finish_logout($redirect_to, $requested, $user, $default_redirect);
}

/**
 * Apply `logout_redirect` and leave the request.
 *
 * @param string    $redirect_to      Validated destination.
 * @param string    $requested        Raw `redirect_to` from the request.
 * @param \WP_User  $user             User as of before logout.
 * @param string    $default_redirect Fallback destination.
 */
function apollo_login_finish_logout(string $redirect_to, string $requested, \WP_User $user, string $default_redirect): void
{
    /** @var string $redirect_to */
    $redirect_to = (string) apply_filters('logout_redirect', $redirect_to, $requested, $user);

    // A crafted redirect_to must not bounce the visitor back into the logout route.
    if ($redirect_to === '' || str_contains($redirect_to, 'action=logout')) {
        $redirect_to = $default_redirect;
    }

    wp_safe_redirect($redirect_to);
    exit;
}

/**
 * Whether the current request is REST, and therefore not a logout navigation.
 *
 * REST_REQUEST is only defined during parse_request, i.e. after wp_loaded.
 */
function apollo_login_request_is_rest(): bool
{
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return true;
    }

    if (isset($_GET['rest_route'])) {
        return true;
    }

    $uri  = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = (string) wp_parse_url($uri, PHP_URL_PATH);

    return $path !== '' && str_contains(trailingslashit($path), '/' . rest_get_url_prefix() . '/');
}

/**
 * Catch `?action=logout` on any public URL.
 *
 * `wp_logout_url()` is rewritten to /acesso by Security\URLRewriter, but stale links
 * and hand-typed URLs reach the home page and other routes too. Runs on wp_loaded so
 * it lands before routing — no template or 404 handler can swallow the request first.
 */
function apollo_login_maybe_handle_logout_action(): void
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || apollo_login_request_is_rest()) {
        return;
    }

    if ('logout' !== apollo_login_request_action($_GET)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    apollo_login_process_logout_request();
}
add_action('wp_loaded', __NAMESPACE__ . '\\apollo_login_maybe_handle_logout_action', PHP_INT_MIN);

/**
 * wp-login.php?action=logout — same flow instead of core's nonce interstitial.
 */
function apollo_login_handle_login_page_logout(): void
{
    if ('logout' !== apollo_login_request_action($_REQUEST)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    apollo_login_process_logout_request();
}
add_action('login_init', __NAMESPACE__ . '\\apollo_login_handle_login_page_logout', 5);

/**
 * Keep every logout inside Apollo: core's default lands on wp-login.php, which
 * Security\URLRewriter hides behind a 404 for non-administrators.
 *
 * @param mixed $redirect_to           Destination chosen so far.
 * @param mixed $requested_redirect_to Raw `redirect_to` from the request.
 * @param mixed $user                  User being logged out.
 */
function apollo_login_filter_logout_redirect($redirect_to, $requested_redirect_to, $user): string
{
    unset($user);

    $default   = apollo_login_default_logout_redirect();
    $requested = is_string($requested_redirect_to) ? wp_sanitize_redirect($requested_redirect_to) : '';

    if ($requested !== '' && ! str_contains($requested, 'action=logout')) {
        return (string) wp_validate_redirect($requested, $default);
    }

    if (! is_string($redirect_to) || $redirect_to === '') {
        return $default;
    }

    if (
        str_contains($redirect_to, 'wp-login.php')
        || str_contains($redirect_to, 'loggedout=')
        || str_contains($redirect_to, 'action=logout')
    ) {
        return $default;
    }

    return $redirect_to;
}
add_filter('logout_redirect', __NAMESPACE__ . '\\apollo_login_filter_logout_redirect', 5, 3);
