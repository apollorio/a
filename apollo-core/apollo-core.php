<?php

/**
 * Apollo Core
 *
 * MASTER FOUNDATION for the entire Apollo ecosystem.
 * Central registry for CPTs, Taxonomies, Meta Keys.
 * Fallback system ensures all components exist even if owner plugins are inactive.
 *
 * @license GPL-2.0-or-later
 * Copyright (c) 2026 Apollo
 *
 * @package Apollo\Core
 *
 * Plugin Name: Apollo Core
 * Plugin URI: https://apollo.rio.br
 * Description: Core fundacional do ecossistema Apollo - MASTER REGISTRY de CPTs, Taxonomias e Meta Keys. Sistema de fallback para plugins inativos. Foundation com hooks, CDN e REST API.
 * Version: 6.4.4
 * Author: Apollo Team
 * Author URI: https://apollo.rio.br
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: apollo-core
 * Domain Path: /languages
 * Requires at least: 6.4
 * Tested up to: 6.9
 * Requires PHP: 8.1
 */

declare(strict_types=1);

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

/* 6.2.7 — 14 dj/local meta keys registered (5 orphans + 9 new from the mockup
          audit); apollo_core_kses_inline_em(); config/meta.php stamped
          non-authoritative; includes/form-schema.php — one schema, three surfaces.
   6.2.8 — includes/modelo/ — the "Hello world!" of the Apollo CPTs. One fully
          populated demo record per CPT at slug `modelo`, seeded from the schema
          so it reports its own coverage and cannot silently fall behind it.
   6.4.2 — _event_int_rank registered on `event` (0-10, INTERNAL ONLY, show_in_rest
          false, auth_callback manage_options). Feeds apollo-telegram event-selection.
          UI lives in apollo-events/src/Admin/RankMetabox.php, below Publish/Update.
   6.4.3 — 5 internal vibe-tag checkboxes on `event` (_event_tag_underground,
          _mainstream, _comercial, _lgbtqia, _sexparty — all INTERNAL, never
          frontend, same admin spot as _event_int_rank). Canonical slug=>key
          map: apollo_event_internal_tags() in apollo-events/includes/functions.php.
   6.4.4 — apollo_admin_parent_slug() in includes/functions.php: one answer to
          "where do Apollo admin screens hang?". Health, Shortcodes and Modelo
          were each guessing tools.php on their own, which is how the ecosystem's
          own health report ended up filed under WordPress Tools while every
          other Apollo screen sat under Apollo. All three now ask the helper and
          hook admin_menu at priority 20, after apollo-admin registers the root.
   See _inventory/CPT-REGISTRATION-MAP-2026-08-11.md */
define('APOLLO_CORE_VERSION', '6.4.4');
define('APOLLO_CORE_PATH', plugin_dir_path(__FILE__));
define('APOLLO_CORE_URL', plugin_dir_url(__FILE__));
define('APOLLO_CORE_FILE', __FILE__);

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER (must load BEFORE constants — ConfigLoader needs it)
// ═══════════════════════════════════════════════════════════════════════════

// Composer autoloader (includes PSR-4 for Apollo\Core namespace)
if (file_exists(APOLLO_CORE_PATH . 'vendor/autoload.php')) {
    require_once APOLLO_CORE_PATH . 'vendor/autoload.php';
}

// Manual autoloader fallback for src/ classes
spl_autoload_register(
    function (string $class) {
        $prefix   = 'Apollo\\Core\\';
        $base_dir = APOLLO_CORE_PATH . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $relative_path  = str_replace('\\', '/', $relative_class) . '.php';

        // Primary: src/<relative>.php  (covers Config\*, API\*, Traits\*)
        $file = $base_dir . $relative_path;
        if (file_exists($file)) {
            require $file;
            return;
        }

        // Secondary: src/Core/<relative>.php
        // Covers Apollo\Core\* classes physically located in src/Core/
        $core_file = $base_dir . 'Core/' . $relative_path;
        if (file_exists($core_file)) {
            require $core_file;
        }
    }
);

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS (loaded after autoloader so ConfigLoader is available)
// ═══════════════════════════════════════════════════════════════════════════

// Load additional constants
require_once APOLLO_CORE_PATH . 'includes/constants.php';

// ═══════════════════════════════════════════════════════════════════════════
// SANITIZATION HELPERS (safe wrappers — loaded first)
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_CORE_PATH . 'includes/apollo-sanitizers.php';

// ═══════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS (must load early)
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_CORE_PATH . 'includes/functions.php'; // pulls in document-head.php too.
/*
 * Cache-busting for every apollo-* asset, applied at the wp_enqueue chokepoint
 * so no plugin has to change a line. This folder deploys on save while plugins
 * stamp assets with hand-bumped version constants, so without it a CSS/JS fix
 * is correct on disk and stale in the browser behind Cloudflare — the defect
 * that made the event lightbox render blank on 2026-08-07. Loads with the
 * helpers because filters must be registered before anything enqueues.
 */
require_once APOLLO_CORE_PATH . 'includes/asset-version.php';
/*
 * Surface Contract — the shared wire for "open this content in place".
 * apollo-events grew the renderer/fragment/card/enqueue machinery for itself;
 * djs, locs and the rest had none of it. This holds the four contracts once so
 * a plugin opts in with a single apollo_surface_register() call instead of
 * inheriting a fourth divergent copy. Registers no CPT, meta or table.
 */
require_once APOLLO_CORE_PATH . 'includes/surface-contract.php';
/*
 * Card Contract — the sibling wire for "show this item in a list".
 * A surface is the page you open; a card is the item you click. The
 * accommodation card existed four times with different DOM, an Out Now section
 * five times in three shapes, apollo-dashboard redeclared apollo-adverts'
 * .accom-* selectors, and the live marketplace shipped cards with no card CSS
 * at all. One owner per card plus a print-once style ledger ends all four.
 * Registers no CPT, meta or table.
 */
require_once APOLLO_CORE_PATH . 'includes/card-contract.php';
/*
 * Safety Contract — the third wire in the same family. A surface is the page
 * you open, a card is the item you click, and a safety gate is what stands
 * between the click and a stranger. Handing one member to another is a
 * platform rule, not a marketplace feature, so the decision lives here and the
 * markup lives in whichever plugin owns the surface. Default-deny: a
 * registered post type is gated unless its own callback names a reason.
 * Registers no CPT, meta or table.
 */
require_once APOLLO_CORE_PATH . 'includes/safety-contract.php';
/*
 * Ecosystem Health — Tools → Apollo Health. Reads the contracts above plus the
 * panel registry and MetaRegistry, and reports where they disagree: meta keys
 * with no input, panel fields with no meta key, dormant surfaces and cards,
 * schemas failing validation.
 *
 * It REPORTS. It repairs nothing, deliberately: this folder deploys on save,
 * with no staging, no PHP binary and no test suite, so a layer that healed
 * itself would write unverified changes straight to production — and every
 * defect the 2026-08-17 audit found was invisible precisely because the system
 * kept running. Self-validating, not self-modifying. See
 * _inventory/STRATEGY-integration.md §4.
 */
require_once APOLLO_CORE_PATH . 'includes/ecosystem-health.php';
require_once APOLLO_CORE_PATH . 'includes/roles-map.php';
require_once APOLLO_CORE_PATH . 'includes/blank-canvas-templates.php';
require_once APOLLO_CORE_PATH . 'includes/route-helpers.php';
require_once APOLLO_CORE_PATH . 'includes/search-helpers.php';
require_once APOLLO_CORE_PATH . 'includes/channel-preferences.php';

// ═══════════════════════════════════════════════════════════════════════════
// REPORT MODAL (shared component — loaded on demand)
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_CORE_PATH . 'includes/report-modal.php';
require_once APOLLO_CORE_PATH . 'includes/color-input.php';
/* PHASE 2.1 — one schema, three surfaces (form / metabox / REST). Ends the
   hand-listed-form drift that left [apollo_add_dj] at 16%% coverage. */
require_once APOLLO_CORE_PATH . 'includes/form-schema.php';
/* MODELO — the "Hello world!" of the Apollo CPTs: one fully-populated demo
   record per CPT at slug `modelo`. Loads AFTER form-schema.php, which it reads
   to report its own coverage. */
require_once APOLLO_CORE_PATH . 'includes/modelo/modelo.php';

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION / UNINSTALL
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Activation Hook
 * Pattern: CHECK IF EXISTS → BUILD IF NOT
 */
register_activation_hook(
    __FILE__,
    function () {
        \Apollo\Core\ActivationHandler::activate();
    }
);

/**
 * Deactivation Hook
 * Keeps all data by default (soft deactivation)
 */
register_deactivation_hook(
    __FILE__,
    function () {
        \Apollo\Core\UninstallHandler::deactivate();
    }
);

/**
 * Add custom cron schedules
 */
add_filter('cron_schedules', array(\Apollo\Core\ActivationHandler::class, 'add_cron_schedules'));

// ═══════════════════════════════════════════════════════════════════════════
// BOOTSTRAP
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Bootstrap Apollo Core
 *
 * This function is called by plugins_loaded hook
 */
function apollo_core_bootstrap()
{
    // Check for database upgrade
    \Apollo\Core\ActivationHandler::upgrade();

    // ─────────────────────────────────────────────────────────────────────
    // CRITICAL: Initialize CPT, Taxonomy, Meta Registries
    // These MUST load early to provide fallback for missing plugins
    // ─────────────────────────────────────────────────────────────────────

    // Taxonomy Registry (priority 4 - before CPTs)
    \Apollo\Core\TaxonomyRegistry::init();

    // CPT Registry (priority 5)
    \Apollo\Core\CPTRegistry::init();

    // Meta Registry (priority 9 - after CPTs)
    \Apollo\Core\MetaRegistry::init();

    // Shortcode Registry — deferred to init (uses translated labels).
    add_action('init', array(\Apollo\Core\ShortcodeRegistry::class, 'init'), 5);

    // ─────────────────────────────────────────────────────────────────────
    // Initialize other core components
    // ─────────────────────────────────────────────────────────────────────

    // Initialize CDN helper
    if (class_exists('\Apollo\Core\CDN')) {
        \Apollo\Core\CDN::init();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Security & SEO components
    // ─────────────────────────────────────────────────────────────────────
    if (class_exists('\Apollo\Core\FieldEncryption')) {
        \Apollo\Core\FieldEncryption::init();
    }
    if (class_exists('\Apollo\Core\SRIManager')) {
        \Apollo\Core\SRIManager::init();
    }
    if (class_exists('\Apollo\Core\HMACVerifier')) {
        \Apollo\Core\HMACVerifier::init();
    }
    if (class_exists('\Apollo\Core\SEOTextBlock')) {
        \Apollo\Core\SEOTextBlock::init();
    }
    if (class_exists('\Apollo\Core\FrontendProtection')) {
        \Apollo\Core\FrontendProtection::init();
    }

    // Initialize REST API controllers
    add_action(
        'rest_api_init',
        function () {
            if (class_exists('\Apollo\Core\API\HealthController')) {
                new \Apollo\Core\API\HealthController();
            }
            if (class_exists('\Apollo\Core\API\RegistryController')) {
                new \Apollo\Core\API\RegistryController();
            }
            if (class_exists('\Apollo\Core\API\SoundController')) {
                new \Apollo\Core\API\SoundController();
            }
            if (class_exists('\Apollo\Core\API\SearchController')) {
                $search = new \Apollo\Core\API\SearchController();
                $search->register_routes();
            }
            if (class_exists('\Apollo\Core\API\ShortcodesController')) {
                new \Apollo\Core\API\ShortcodesController();
            }
            if (class_exists('\Apollo\Core\API\PaneModeController')) {
                new \Apollo\Core\API\PaneModeController();
            }
        }
    );

    // ─────────────────────────────────────────────────────────────────────
    // Front route dispatcher — P0 template_redirect (before pane + plugins)
    // ─────────────────────────────────────────────────────────────────────
    if (class_exists('\Apollo\Core\FrontRouteDispatcher')) {
        \Apollo\Core\FrontRouteDispatcher::init();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Pane Mode Adapter — must init AFTER registries, before template_redirect
    // ─────────────────────────────────────────────────────────────────────
    if (class_exists('\Apollo\Core\PaneModeAdapter')) {
        \Apollo\Core\PaneModeAdapter::init();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Initialize Admin Pages
    // ─────────────────────────────────────────────────────────────────────
    if (is_admin()) {
        require_once APOLLO_CORE_PATH . 'admin/SettingsPage.php';
    }

    // ─────────────────────────────────────────────────────────────────────
    // Fire initialized hook
    // ─────────────────────────────────────────────────────────────────────
    do_action(
        \Apollo\Core\Config\ApolloHook::CORE_INITIALIZED,
        array(
            'version'   => APOLLO_CORE_VERSION,
            'cpt_count' => count(\Apollo\Core\CPTRegistry::get_instance()->get_definitions()),
            'tax_count' => count(\Apollo\Core\TaxonomyRegistry::get_instance()->get_definitions()),
        )
    );

    // Define bootstrap constant
    if (! defined('APOLLO_CORE_BOOTSTRAPPED')) {
        define('APOLLO_CORE_BOOTSTRAPPED', true);
    }
}

/**
 * Initialize Apollo Core
 *
 * Priority: plugins_loaded with priority 1 (load first)
 */
add_action(
    'init',
    function () {
        load_plugin_textdomain('apollo-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
    },
    0
);

add_action('plugins_loaded', 'apollo_core_bootstrap', 1);

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN NOTICES
// ═══════════════════════════════════════════════════════════════════════════

add_action(
    'admin_notices',
    function () {
        // Show activation results
        $results = get_transient('apollo_activation_results');

        if ($results) {
            delete_transient('apollo_activation_results');

            $created_count = count($results['created'] ?? array());
            $error_count   = count($results['errors'] ?? array());

            if ($created_count > 0 && $error_count === 0) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>Apollo Core:</strong> ' . sprintf(
                    __('%d tabelas criadas com sucesso.', 'apollo-core'),
                    $created_count
                ) . '</p>';
                echo '</div>';
            } elseif ($error_count > 0) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>Apollo Core:</strong> ' . sprintf(
                    __('Erro ao criar %d tabelas. Verifique o log.', 'apollo-core'),
                    $error_count
                ) . '</p>';
                echo '</div>';
            }
        }
    }
);
