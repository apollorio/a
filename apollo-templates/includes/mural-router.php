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
        $path = function_exists('apollo_normalize_request_path')
            ? apollo_normalize_request_path()
            : '';
        $is_reserved = function_exists('apollo_is_reserved_virtual_path')
            && apollo_is_reserved_virtual_path();
        $logged_in = is_user_logged_in();

        // #region agent log
        $apollo_mural_dbg = static function (string $hypothesis_id, string $message, array $data): void {
            $payload = array(
                'sessionId'    => '161c5c',
                'runId'        => 'pre-fix',
                'hypothesisId' => $hypothesis_id,
                'location'     => 'mural-router.php:template_redirect',
                'message'      => $message,
                'data'         => $data,
                'timestamp'    => (int) round(microtime(true) * 1000),
            );
            $line = wp_json_encode($payload) . "\n";
            foreach (
                array(
                    (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '') . '/debug-161c5c.log',
                    (defined('ABSPATH') ? ABSPATH : '') . 'debug-161c5c.log',
                ) as $log
            ) {
                if ($log !== '/debug-161c5c.log' && $log !== 'debug-161c5c.log') {
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                    @file_put_contents($log, $line, FILE_APPEND);
                }
            }
            if (! headers_sent()) {
                header('X-Apollo-Debug-Mural: ' . rawurlencode((string) wp_json_encode($payload)));
            }
        };
        // #endregion

        if (! $logged_in) {
            // #region agent log
            if (in_array($path, array('', 'casa', 'home'), true)) {
                $apollo_mural_dbg(
                    'C',
                    'mural skip: guest on landing path',
                    array(
                        'path'        => $path,
                        'is_reserved' => $is_reserved,
                        'logged_in'   => false,
                    )
                );
            }
            // #endregion
            return;
        }

        // Never redirect away from /feed (WP sets is_home on this slug — caused infinite 302 loop).
        if (get_query_var('apollo_feed_page')) {
            // #region agent log
            $apollo_mural_dbg('E', 'mural skip: already on feed qvar', array('path' => $path));
            // #endregion
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
            // #region agent log
            $apollo_mural_dbg('D', 'mural skip: apollo qvar reserved', array('path' => $path));
            // #endregion
            return;
        }
        if ($is_reserved) {
            // #region agent log
            $apollo_mural_dbg(
                'A',
                'mural EARLY RETURN: reserved path (blocks casa/home landing redirect)',
                array(
                    'path'              => $path,
                    'is_reserved'       => true,
                    'apollo_home_page'  => (string) get_query_var('apollo_home_page'),
                    'would_be_landing'  => in_array($path, array('', 'casa', 'home'), true),
                )
            );
            // #endregion
            return;
        }

        if ('feed' === $path) {
            return;
        }

        $on_landing = false;

        if (get_query_var('apollo_home_page')) {
            $on_landing = true;
        }

        if (in_array($path, array('', 'casa', 'home'), true)) {
            $on_landing = true;
        }

        if (! $on_landing && ! is_front_page() && ! is_home() && ! is_page(array('home', 'casa'))) {
            // #region agent log
            $apollo_mural_dbg(
                'D',
                'mural skip: not on landing',
                array(
                    'path'       => $path,
                    'on_landing' => false,
                    'is_home'    => is_home(),
                    'is_front'   => is_front_page(),
                )
            );
            // #endregion
            return;
        }

        // #region agent log
        $apollo_mural_dbg(
            'A',
            'mural REDIRECT to /feed',
            array(
                'path'       => $path,
                'on_landing' => true,
                'logged_in'  => true,
            )
        );
        // #endregion

        wp_safe_redirect(home_url('/feed'));
        exit;
    },
    5
);
