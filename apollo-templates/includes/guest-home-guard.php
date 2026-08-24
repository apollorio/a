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
 */
function apollo_templates_ensure_guest_home_htaccess(): void {
    $htaccess = ABSPATH . '.htaccess';
    $contents = is_readable($htaccess) ? (string) file_get_contents($htaccess) : '';

    $block = <<<'HTA'
# BEGIN Apollo guest home
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/$
RewriteCond %{QUERY_STRING} ^$
RewriteCond %{HTTP_COOKIE} !wordpress_logged_in [NC]
RewriteRule ^$ /casa [R=301,L]
RewriteCond %{REQUEST_URI} ^/home/?$
RewriteCond %{HTTP_COOKIE} !wordpress_logged_in [NC]
RewriteRule ^home/?$ /casa [R=301,L]
</IfModule>
# END Apollo guest home

HTA;

    if (str_contains($contents, '# BEGIN Apollo guest home')) {
        return;
    }

    if (str_contains($contents, '# BEGIN NFD EPC')) {
        $new = preg_replace('/(# BEGIN NFD EPC)/', $block . "\n$1", $contents, 1);
    } else {
        $new = $block . "\n" . ltrim($contents);
    }

    if (! is_string($new) || $new === $contents) {
        return;
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    @file_put_contents($htaccess, $new);
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
