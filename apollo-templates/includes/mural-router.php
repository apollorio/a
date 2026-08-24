<?php

/**
 * Mural Router — logged-in users skip landing and go to /feed.
 *
 * @package Apollo\Templates
 * @since   1.1.0
 */

defined('ABSPATH') || exit;

/**
 * Redirect logged-in users from landing URLs to /feed.
 *
 * Hooks into `template_redirect` at priority 5 (before apollo-templates P10).
 */
add_action(
    'template_redirect',
    function (): void {
        if (! is_user_logged_in()) {
            return;
        }

        // Never redirect away from /feed (WP sets is_home on this slug — caused infinite 302 loop).
        if (get_query_var('apollo_feed_page')) {
            return;
        }

        // CRITICAL: never hijack a reserved Apollo virtual route. Unmatched/unflushed
        // virtual URLs (e.g. /portal, /painel/eventos, /novo-evento) resolve to the
        // posts index → is_home() === true. Without this guard, mural-router fires
        // and 302-redirects every one of them to /feed before their owning plugin
        // can render on template_redirect. Any Apollo virtual query var, or a path
        // whose first segment is reserved, must be left for its handler.
        if (
            get_query_var('apollo_event_page')
            || get_query_var('apollo_dashboard_page')
            || get_query_var('apollo_user_page')
            || get_query_var('apollo_hub_page')
            || get_query_var('apollo_group_page')
        ) {
            return;
        }
        if (function_exists('apollo_is_reserved_virtual_path') && apollo_is_reserved_virtual_path()) {
            return;
        }

        if (function_exists('apollo_normalize_request_path')) {
            $path = apollo_normalize_request_path();
            if ('feed' === $path) {
                return;
            }
        }

        $on_landing = false;

        if (get_query_var('apollo_home_page')) {
            $on_landing = true;
        }

        if (function_exists('apollo_normalize_request_path')) {
            $path = apollo_normalize_request_path();
            if (in_array($path, array('', 'casa', 'home'), true)) {
                $on_landing = true;
            }
        }

        if (! $on_landing && ! is_front_page() && ! is_home() && ! is_page(array('home', 'casa'))) {
            return;
        }

        wp_safe_redirect(home_url('/feed'));
        exit;
    },
    5
);
