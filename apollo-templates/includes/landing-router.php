<?php

/**
 * Guest landing redirects — canonical public home is /casa.
 *
 * @package Apollo\Templates
 */

defined('ABSPATH') || exit;

/**
 * Redirect guests from / and /home to /casa (301).
 */
add_action(
    'template_redirect',
    function (): void {
        if (is_user_logged_in()) {
            return;
        }

        if (get_query_var('apollo_home_redirect')) {
            wp_safe_redirect(home_url('/casa'), 301);
            exit;
        }

        if (! function_exists('apollo_normalize_request_path')) {
            return;
        }

        $path = apollo_normalize_request_path();

        if ($path === 'home') {
            wp_safe_redirect(home_url('/casa'), 301);
            exit;
        }

        if ($path === '' && (is_front_page() || is_home())) {
            wp_safe_redirect(home_url('/casa'), 301);
            exit;
        }
    },
    4
);
