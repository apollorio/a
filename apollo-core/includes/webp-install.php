<?php
/**
 * Apollo WebP — MU-plugin installer.
 *
 * The WebP layer has to live in wp-content/mu-plugins/, but only
 * wp-content/plugins/ is mirrored to this host. So apollo-core carries the
 * source and copies it up, exactly as it already does for the .htaccess lock
 * — see apollo_core_htaccess_install_mu_plugin() in includes/htaccess-lock.php,
 * which this deliberately mirrors line for line rather than inventing a second
 * way of doing the same thing.
 *
 * Content-diffed, not timestamped: editing the source under
 * plugins/apollo-core/mu-plugins/ is what triggers a re-copy, and an identical
 * file is never rewritten. The copy lands on the request AFTER the deploy, so
 * a change to the WebP layer takes two page loads to go live, not one.
 *
 * @package Apollo\Core
 * @since   6.6.0
 * @see     _inventory/registry/22-mu-plugins.json  the cost of the MU layer
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_core_webp_install_mu_plugin')) {
    /**
     * Install / refresh the WebP MU-plugin so it runs before regular plugins.
     *
     * @return void
     */
    function apollo_core_webp_install_mu_plugin(): void
    {
        if (! defined('WPMU_PLUGIN_DIR')) {
            return;
        }

        $src = dirname(__DIR__) . '/mu-plugins/apollo-webp-force.php';
        if (! is_readable($src)) {
            return;
        }

        if (! is_dir(WPMU_PLUGIN_DIR)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
            @mkdir(WPMU_PLUGIN_DIR, 0755, true);
        }

        $dest      = WPMU_PLUGIN_DIR . '/apollo-webp-force.php';
        $src_body  = (string) file_get_contents($src);
        $dest_body = is_readable($dest) ? (string) file_get_contents($dest) : '';

        if ('' === $src_body || $src_body === $dest_body) {
            return;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        $ok = @file_put_contents($dest, $src_body, LOCK_EX);

        if (function_exists('apollo_debug_log')) {
            apollo_debug_log(
                'mu-plugin install',
                array(
                    'ok'   => false !== $ok,
                    'dest' => $dest,
                ),
                'apollo-webp'
            );
        }
    }
}

add_action('plugins_loaded', 'apollo_core_webp_install_mu_plugin', 0);
