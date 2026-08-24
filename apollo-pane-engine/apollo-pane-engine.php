<?php
/**
 * Plugin Name: Apollo Pane Engine
 * Plugin URI:  https://apollo.rio.br
 * Description: Isolated test harness — read-only "aquarium bubble" that probes all 220+ Apollo REST endpoints via a side-by-slide panel UI at /casa.
 * Version:     2.0.1
 * Author:      Apollo Team
 * Author URI:  https://apollo.rio.br
 * License:     GPL-2.0-or-later
 * Text Domain: apollo-pane-engine
 * Requires PHP: 8.1
 * Requires at least: 6.4
 *
 * @package Apollo\PaneEngine
 */

if (! defined('ABSPATH')) {
    exit;
}

/* ── Constants ────────────────────────────────────────────────────── */
define('APOLLO_PANE_ENGINE_VERSION', '2.0.1');
define('APOLLO_PANE_ENGINE_PATH', plugin_dir_path(__FILE__));
define('APOLLO_PANE_ENGINE_URL', plugin_dir_url(__FILE__));
define('APOLLO_PANE_ENGINE_MANIFEST', APOLLO_PANE_ENGINE_PATH . 'pane-engine-casa.json');

/* ── Includes ─────────────────────────────────────────────────────── */
require_once APOLLO_PANE_ENGINE_PATH . 'includes/functions.php';
require_once APOLLO_PANE_ENGINE_PATH . 'includes/fragment-helper.php';
require_once APOLLO_PANE_ENGINE_PATH . 'includes/section-renderer.php';

/* ── Activation: flush rewrites ───────────────────────────────────── */
register_activation_hook(__FILE__, function (): void {
    apollo_pane_engine_add_rewrite_rules();
    flush_rewrite_rules();
    update_option('apollo_pane_engine_version', APOLLO_PANE_ENGINE_VERSION);
});

/* ── Deactivation: flush rewrites ─────────────────────────────────── */
register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});
