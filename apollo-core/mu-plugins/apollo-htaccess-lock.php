<?php
/**
 * Plugin Name: Apollo .htaccess Lock (MU)
 * Description: Restores and read-locks root .htaccess before regular plugins (NFD/WP) can truncate it.
 * Version: 1.0.0
 * Author: Apollo
 *
 * Auto-installed from apollo-core. Do not edit; change the source under
 * plugins/apollo-core/mu-plugins/apollo-htaccess-lock.php
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// Prefer active apollo-core copy; fall back to wt-p1 mirror naming if needed.
$apollo_lock = WP_PLUGIN_DIR . '/apollo-core/includes/htaccess-lock.php';
if (is_readable($apollo_lock)) {
    require_once $apollo_lock;
}
