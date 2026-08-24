<?php

/**
 * Disable Template Conflicts
 *
 * Prevents apollo-templates from overriding apollo-login blank canvas pages.
 * This file is loaded early (before plugins_loaded) to ensure auth page
 * templates are never intercepted by other plugins.
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
 * Hook into apollo-templates route dispatcher to prevent it from
 * intercepting login/auth page requests.
 *
 * Priority 0 ensures we run before apollo-templates (priority 10).
 */
add_action(
    'template_redirect',
    function (): void {
        // If apollo-login has already matched a page (via parse_request), 
        // prevent other template redirect handlers from overriding.
        $page = get_query_var('apollo_login_page', '');
        if (! empty($page)) {
            // Signal to apollo-templates that this is an auth page
            add_filter(
                'apollo_templates_should_intercept',
                function (bool $should_intercept): bool {
                    $page = get_query_var('apollo_login_page', '');
                    if (! empty($page)) {
                        return false;
                    }
                    return $should_intercept;
                },
                0
            );
        }
    },
    0
);

/**
 * Prevent template_include filters from changing auth page templates.
 * Priority 0 ensures this runs before any other template filter.
 */
add_filter(
    'template_include',
    function (string $template): string {
        $page = get_query_var('apollo_login_page', '');
        if (! empty($page)) {
            // Our template_redirect handler already includes the correct template
            // and exits. This filter just prevents any late-stage overrides.
            return $template;
        }
        return $template;
    },
    0
);
