<?php

/**
 * Plugin Name: Apollo Email
 * Plugin URI:  https://apollo.rio.br
 * Description: Motor de email transacional e marketing integrado ao ecossistema Apollo — templates, fila de envio, logging, SMTP/SES/SendGrid, tracking, merge tags, automação de gatilhos e administração completa via wp-admin.
 * Version:     1.0.1
 * Author:      Apollo Rio
 * Author URI:  https://apollo.rio.br
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: apollo-email
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 *
 * @package Apollo\Email
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// ─── Plugin Constants ───────────────────────────────────────────────
define('APOLLO_EMAIL_VERSION', '1.0.1');
define('APOLLO_EMAIL_FILE', __FILE__);
define('APOLLO_EMAIL_PATH', plugin_dir_path(__FILE__));
define('APOLLO_EMAIL_URL', plugin_dir_url(__FILE__));
define('APOLLO_EMAIL_BASENAME', plugin_basename(__FILE__));
define('APOLLO_EMAIL_SLUG', 'apollo-email');
define('APOLLO_EMAIL_DB_VERSION', '1.0.0');
define('APOLLO_EMAIL_MIN_PHP', '8.1');
define('APOLLO_EMAIL_MIN_WP', '6.4');
define('APOLLO_EMAIL_CRON_HOOK', 'apollo_email_process_queue');
define('APOLLO_EMAIL_BATCH_SIZE', 50);
define('APOLLO_EMAIL_MAX_RETRIES', 1); // Initial + 1 retry, then fail

// ─── Environment Checks ────────────────────────────────────────────
if (version_compare(PHP_VERSION, APOLLO_EMAIL_MIN_PHP, '<')) {
    add_action(
        'admin_notices',
        function () {
            printf(
                '<div class="notice notice-error"><p><strong>Apollo Email</strong> requer PHP %s ou superior. Versão atual: %s</p></div>',
                esc_html(APOLLO_EMAIL_MIN_PHP),
                esc_html(PHP_VERSION)
            );
        }
    );
    return;
}

// ─── PSR-4 Autoloader ───────────────────────────────────────────────
spl_autoload_register(
    function (string $class): void {
        $prefix   = 'Apollo\\Email\\';
        $len      = strlen($prefix);
        $base_dir = APOLLO_EMAIL_PATH . 'src/';

        if (strncmp($class, $prefix, $len) !== 0) {
            return;
        }

        $relative = substr($class, $len);
        $file     = $base_dir . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
);

// ─── Activation / Deactivation ─────────────────────────────────────
register_activation_hook(__FILE__, array('Apollo\\Email\\Activation', 'activate'));
register_deactivation_hook(__FILE__, array('Apollo\\Email\\Deactivation', 'deactivate'));

// ─── Bootstrap ──────────────────────────────────────────────────────
add_action(
    'plugins_loaded',
    function (): void {
        // Guard: apollo-core must be loaded first
        if (! defined('APOLLO_CORE_BOOTSTRAPPED')) {
            add_action(
                'admin_notices',
                function () {
                    echo '<div class="notice notice-error"><p><strong>Apollo Email</strong> requer <code>apollo-core</code> ativo.</p></div>';
                }
            );
            return;
        }

        // Load textdomain on init action
        add_action('init', function () {
            load_plugin_textdomain('apollo-email', false, dirname(APOLLO_EMAIL_BASENAME) . '/languages');
        }, 1);

        // Load global helper functions
        require_once APOLLO_EMAIL_PATH . 'includes/functions.php';

        // Initialize plugin singleton
        \Apollo\Email\Plugin::instance();

        /**
         * Fires after Apollo Email is fully loaded.
         *
         * @since 1.0.0
         * @param \Apollo\Email\Plugin $plugin Plugin instance.
         */
        do_action('apollo/email/loaded', \Apollo\Email\Plugin::instance());
    },
    15
);

// ─── WP-CLI Support ────────────────────────────────────────────────
if (defined('WP_CLI') && WP_CLI) {
    add_action(
        'apollo/email/loaded',
        function () {
            \WP_CLI::add_command('apollo email', 'Apollo\\Email\\CLI\\EmailCommand');
        }
    );
}
