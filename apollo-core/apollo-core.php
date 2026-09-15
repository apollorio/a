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
 * Version: 6.6.0
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

/*
 * ARCH: apollo-core / L0 — registo, contratos e arranque
 * ARCH-MANUAL: escrito a mao (2026-09-09). gen-arch-blocks.js nao pode
 *   escrever aqui: recusa ficheiros dirty no git e 41 de 42 estao dirty.
 *   Nao apagar por "regeneracao" — os NAO FACA abaixo sao especificos.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-core   77 arquivos PHP, 23726 LOC
 * BOOT      plugins_loaded:1  (apollo-core.php:392) — o PRIMEIRO do
 *           ecossistema; dispara apollo/core/initialized em :364
 * RUNTIME   dono do registo: CPT/taxonomia/meta em init:5/4/9
 * UI        define 10 funcoes apollo_render_* — ver NAO FACA
 * META      MetaRegistry e o governante; 227 chaves no ecossistema
 *           continuam sem definicao governante
 * REST      22 rotas (13 leituras publicas)
 * REQUIRES  nenhuma — e a raiz. 31 plugins dependem deste.
 *
 * ESTE FICHEIRO E O PONTO DE ENTRADA DE TRES CONTRATOS
 *   includes/surface-contract.php   apollo_surface_register()
 *   includes/card-contract.php      apollo_card_register()
 *   includes/safety-contract.php    apollo_safety_register()
 *   Adocao medida em 2026-09-09: surface 3, card 2, safety 1, de 43
 *   plugins. Sao contratos reais com quase nenhum consumidor — antes de
 *   criar um quarto contrato, pergunte porque os tres nao pegaram.
 *   Os nomes de hook vivem em src/Config/ApolloHook.php + config/hooks.php.
 *
 * NAO FACA
 *   - adicionar uma 11a funcao apollo_render_*. As 10 que existem sao uma
 *     violacao de L0 conhecida, atras de 31 dependentes; e o item de maior
 *     raio de impacto do quadro (Fase 6). Nao aumente a divida.
 *   - assumir que apollo/canvas/head tem um so produtor: tem DOIS —
 *     includes/document-head.php:192 e apollo-templates/includes/
 *     class-persistent-ui.php:233. Quem escuta tem de ser idempotente.
 *   - renomear APOLLO_TELEGRAM/v1 "para arrumar". A string vai para o
 *     setWebhook do Telegram; renomear so as rotas perde mensagens de
 *     entrada por ate 24h.
 *   - registar um segundo namespace REST. So apollo/v1.
 *
 * VERIFICAR   node D:/dev/_cos/verify/doctrine-audit.js
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
   6.4.5 — Apollo+ shell split into _topbar() + _aside(). See the note below.
   6.4.6 — the CDN cache-bust actually reaches the browser. apollo_cdn_core_js_url()
          computed APOLLO_CDN_Q and the suffix and then discarded both, so core.js —
          which injects the whole design system, ~31 KB of CSS and 160+ tokens — was
          requested at one fixed URL forever, under a root .htaccess that caches JS
          for a year. Bumping APOLLO_CDN_Q now does what constants.php has always
          claimed it does. New helper: apollo_cdn_append_query() in includes/functions.php.
   6.4.7 — blank-canvas detector covers /anuncios + reserved virtual slugs
          (CSP connector); apollo_csp_nonce_attr() for parser-inserted scripts.
   See _inventory/CPT-REGISTRATION-MAP-2026-08-11.md */
define('APOLLO_CORE_VERSION', '6.6.0');
// 6.4.8 — 2026-08-25: registered _local_hours + _local_amenities. apollo-loc's
// MetaboxSaver had been WRITING both since the Details metabox shipped while
// neither was declared in MetaRegistry, so both bypassed this registry's
// sanitize_callback/auth_callback and stayed invisible to REST regardless of
// what a consumer asked for. The write path now matches the read path.
//
// 6.4.7 — 2026-08-25: Apollo+ shell was rendering only HALF of itself.
// apollo_render_blank_canvas_shell() emitted the topbar (which owns #burger)
// and returned — never the aside (#ax-aside) that #burger opens, nor the
// script that binds it. Result: a dead hamburger on every path-A Apollo+ page.
// Split into apollo_render_blank_canvas_topbar() + _aside(), each guarded
// independently so one already being emitted cannot suppress the other.
// See includes/blank-canvas-templates.php for the full rationale.
define('APOLLO_CORE_PATH', plugin_dir_path(__FILE__));
define('APOLLO_CORE_URL', plugin_dir_url(__FILE__));
define('APOLLO_CORE_FILE', __FILE__);

// Lock root .htaccess to Apollo v3.1.0 BEFORE anything else can rewrite it.
require_once APOLLO_CORE_PATH . 'includes/htaccess-lock.php';

/* WebP layer — apollo-core carries the MU source under mu-plugins/ and copies
   it to WPMU_PLUGIN_DIR, because only wp-content/plugins/ is mirrored to this
   host. Same mechanism as the .htaccess lock directly above. */
require_once APOLLO_CORE_PATH . 'includes/webp-install.php';

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
