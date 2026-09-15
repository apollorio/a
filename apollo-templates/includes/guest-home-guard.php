<?php

/**
 * Brutal guest-home guard — / must never serve stale EPC JSON; guests go to /casa.
 *
 * @package Apollo\Templates
 */

defined('ABSPATH') || exit;

const APOLLO_TEMPLATES_GUEST_HOME_GUARD_OPTION = 'apollo_templates_guest_home_guard_v';

/**
 * One-shot repair on version bump: purge poisoned root cache + inject .htaccess redirect.
 */
add_action(
    'plugins_loaded',
    static function (): void {
        if (! defined('APOLLO_TEMPLATES_VERSION')) {
            return;
        }

        if (get_option(APOLLO_TEMPLATES_GUEST_HOME_GUARD_OPTION) === APOLLO_TEMPLATES_VERSION) {
            return;
        }

        apollo_templates_brutal_guest_home_repair();
        update_option(APOLLO_TEMPLATES_GUEST_HOME_GUARD_OPTION, APOLLO_TEMPLATES_VERSION, false);
    },
    1
);

/**
 * Never cache guest landing URLs in Endurance Page Cache (prevents poisoned JSON/HTML).
 *
 * @param array<int, string> $exempt URI fragments.
 * @return array<int, string>
 */
add_filter(
    'epc_exempt_uri_contains',
    static function (array $exempt): array {
        foreach (array('/', '/home', '/casa') as $fragment) {
            if (! in_array($fragment, $exempt, true)) {
                $exempt[] = $fragment;
            }
        }

        return $exempt;
    }
);

/**
 * Last-line defense: if root cache file reappears, delete it on shutdown.
 */
add_action(
    'shutdown',
    static function (): void {
        $cache = WP_CONTENT_DIR . '/endurance-page-cache/_index.html';
        if (! is_file($cache)) {
            return;
        }

        $sample = (string) file_get_contents($cache, false, null, 0, 120);
        if (
            str_contains($sample, '{"exists"')
            || str_contains($sample, '"pass":true')
            || strlen($sample) < 200
        ) {
            wp_delete_file($cache);
        }
    },
    0
);

/**
 * Delete endurance root/home cache files that bypass WordPress.
 */
function apollo_templates_purge_guest_home_cache(): void {
    $targets = array(
        WP_CONTENT_DIR . '/endurance-page-cache/_index.html',
        WP_CONTENT_DIR . '/endurance-page-cache/home/_index.html',
        WP_CONTENT_DIR . '/endurance-page-cache/casa/_index.html',
    );

    foreach ($targets as $file) {
        if (is_file($file)) {
            wp_delete_file($file);
        }
    }

    if (function_exists('do_action')) {
        do_action('epc_purge');
    }
}

/**
 * Ensure Apache redirects guest / and /home to /casa BEFORE NFD EPC serves static cache.
 * Never mutate Apollo production .htaccess (v3.1.0 already has §1.10.5).
 */
function apollo_templates_ensure_guest_home_htaccess(): void {
    $htaccess = ABSPATH . '.htaccess';
    $contents = is_readable($htaccess) ? (string) file_get_contents($htaccess) : '';

    $is_apollo = str_contains($contents, 'APOLLO  ·  .htaccess')
        || str_contains($contents, 'Version: 3.1.0')
        || str_contains($contents, 'ErrorDocument 500 /erro/500/')
        || str_contains($contents, '# ── END OF APOLLO .htaccess');

    $already_has_casa = str_contains($contents, '/casa [R=301')
        || str_contains($contents, '# BEGIN Apollo guest home')
        || str_contains($contents, '§1.10.5');

    // #region agent log
    $log = (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '') . '/debug-161c5c.log';
    @file_put_contents(
        $log,
        wp_json_encode(
            array(
                'sessionId'    => '161c5c',
                'runId'        => 'htaccess-guard',
                'hypothesisId' => 'H2',
                'location'     => 'guest-home-guard.php:ensure_htaccess',
                'message'      => 'guest home htaccess gate',
                'data'         => array(
                    'bytes'           => strlen($contents),
                    'is_apollo'       => $is_apollo,
                    'already_has_casa'=> $already_has_casa,
                    'will_write'      => false,
                ),
                'timestamp'    => (int) round(microtime(true) * 1000),
            )
        ) . "\n",
        FILE_APPEND
    );
    // #endregion

    // Root .htaccess is owned by apollo-core (canonical v3.1.0). Never mutate it here.
    if ($is_apollo || $already_has_casa) {
        return;
    }
    if (function_exists('apollo_core_htaccess_enforce')) {
        apollo_core_htaccess_enforce();
    }
}

/**
 * Run cache purge + htaccess repair.
 */
function apollo_templates_brutal_guest_home_repair(): void {
    apollo_templates_purge_guest_home_cache();
    apollo_templates_ensure_guest_home_htaccess();

    // #region agent log
    $log = WP_CONTENT_DIR . '/plugins/debug-e031aa.log';
    @file_put_contents(
        $log,
        wp_json_encode(
            array(
                'sessionId'    => 'e031aa',
                'runId'        => 'guest-home-brutal',
                'hypothesisId' => 'H1',
                'location'     => 'guest-home-guard.php:repair',
                'message'      => 'brutal guest home repair executed',
                'data'         => array(
                    'version'        => defined('APOLLO_TEMPLATES_VERSION') ? APOLLO_TEMPLATES_VERSION : '?',
                    'root_cache_gone'=> ! is_file(WP_CONTENT_DIR . '/endurance-page-cache/_index.html'),
                    'htaccess_has_block' => str_contains(
                        (string) @file_get_contents(ABSPATH . '.htaccess'),
                        '# BEGIN Apollo guest home'
                    ),
                ),
                'timestamp'    => (int) round(microtime(true) * 1000),
            )
        ) . "\n",
        FILE_APPEND
    );
    // #endregion
}

/**
 * PHP fallback: redirect guests before template_redirect (when WP boots).
 */
add_action(
    'parse_request',
    static function (): void {
        if (is_user_logged_in() || ! function_exists('apollo_normalize_request_path')) {
            return;
        }

        $path = apollo_normalize_request_path();
        if ($path !== '' && $path !== 'home') {
            return;
        }

        // #region agent log
        $log = WP_CONTENT_DIR . '/plugins/debug-e031aa.log';
        @file_put_contents(
            $log,
            wp_json_encode(
                array(
                    'sessionId'    => 'e031aa',
                    'runId'        => 'guest-home-brutal',
                    'hypothesisId' => 'H2',
                    'location'     => 'guest-home-guard.php:parse_request',
                    'message'      => 'PHP guest redirect to /casa',
                    'data'         => array('path' => $path),
                    'timestamp'    => (int) round(microtime(true) * 1000),
                )
            ) . "\n",
            FILE_APPEND
        );
        // #endregion

        wp_safe_redirect(home_url('/casa'), 301);
        exit;
    },
    0
);
