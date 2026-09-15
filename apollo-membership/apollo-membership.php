<?php

/**
 * Apollo Membership
 *
 * Gamification system with achievements, badges, points, ranks.
 * Adapted from BadgeOS v3.7.0 architecture.
 * TWO SEPARATE SYSTEMS:
 *   1) Membership Badges — ADMIN-ONLY assignment (VERIFIED, DJ, PRODUCER, etc.)
 *   2) Gamification Points — automatic trigger-based (hours, interactions, etc.)
 *
 * @package Apollo\Membership
 *
 * Plugin Name: Apollo Membership
 * Plugin URI: https://apollo.rio.br/plugins/apollo-membership
 * Description: Gamification: achievements, badges, points, ranks. Membership badges (admin-only) + Points system (automatic triggers). Adapted from BadgeOS v3.7.0.
 * Version: 1.0.3
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-membership
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 */

/*
 * ARCH: apollo-membership
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-membership   42 arquivos PHP, 7989 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (23); chrome é do apollo-templates
 * META      13 chaves tocadas, 13 SEM definição governante
 * REST      namespace não literal no código — 23 rotas (11 públicas)
 * REQUIRES  apollo-core
 *
 * NÃO FAÇA
 *   - registrar CPT direto: apollo-core é o dono do init:5.
 *     Fallback do owner só com post_type_exists().
 *   - gravar meta de outro domínio (hoje 227 chaves não têm dono).
 *   - registrar um segundo namespace REST. Só apollo/v1.
 *     Já existe um namespace fora do padrão no apollo-telegram.
 *   - add_shortcode() sem shortcode_exists(): o último a registrar
 *     vence em silêncio e quem roda vira acidente de ordem de carga.
 *
 * VERIFICAR   node D:/dev/_cos/verify/plugin-audit.js
 */

declare(strict_types=1);

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define('APOLLO_MEMBERSHIP_VERSION', '1.0.3');
define('APOLLO_MEMBERSHIP_FILE', __FILE__);
define('APOLLO_MEMBERSHIP_DIR', plugin_dir_path(__FILE__));
define('APOLLO_MEMBERSHIP_URL', plugin_dir_url(__FILE__));
define('APOLLO_MEMBERSHIP_BASENAME', plugin_basename(__FILE__));

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check required dependencies
 * Requires: apollo-core, apollo-users
 */
function apollo_membership_check_dependencies(): void
{
    $active_plugins = get_option('active_plugins', array());

    if (! in_array('apollo-core/apollo-core.php', $active_plugins, true)) {
        add_action(
            'admin_notices',
            function () {
                echo '<div class="notice notice-error"><p><strong>Apollo Membership:</strong> Requer Apollo Core ativo.</p></div>';
            }
        );
        deactivate_plugins(APOLLO_MEMBERSHIP_BASENAME);
        return;
    }
}
add_action('plugins_loaded', 'apollo_membership_check_dependencies', 5);

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER (PSR-4 for src/)
// ═══════════════════════════════════════════════════════════════════════════

if (file_exists(APOLLO_MEMBERSHIP_DIR . 'vendor/autoload.php')) {
    require_once APOLLO_MEMBERSHIP_DIR . 'vendor/autoload.php';
}

spl_autoload_register(
    function (string $class) {
        $prefix   = 'Apollo\\Membership\\';
        $base_dir = APOLLO_MEMBERSHIP_DIR . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
);

// ═══════════════════════════════════════════════════════════════════════════
// INCLUDES (procedural — adapted from BadgeOS pattern)
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_MEMBERSHIP_DIR . 'includes/constants.php';
require_once APOLLO_MEMBERSHIP_DIR . 'includes/functions.php';
require_once APOLLO_MEMBERSHIP_DIR . 'bridge-pmpro.php';

/**
 * Load all include files after init
 * Adapted from BadgeOS::includes()
 */
function apollo_membership_load_includes(): void
{

    $dir = APOLLO_MEMBERSHIP_DIR . 'includes/';

    // Achievement system
    require_once $dir . 'achievement-functions.php';

    // Triggers & rules engine
    require_once $dir . 'triggers.php';
    require_once $dir . 'rules-engine.php';

    // Points system
    require_once $dir . 'points/point-functions.php';
    require_once $dir . 'points/triggers.php';
    require_once $dir . 'points/point-rules-engine.php';

    // Ranks system
    require_once $dir . 'ranks/rank-functions.php';
    require_once $dir . 'ranks/triggers.php';

    // User functions
    require_once $dir . 'user.php';

    // Shortcodes
    require_once $dir . 'shortcodes.php';

    // Widgets
    require_once $dir . 'widgets.php';

    // AJAX handlers (admin tools)
    require_once $dir . 'ajax-handlers.php';

    // Canonical hook bridges — apollo/membership/* namespaced aliases
    require_once $dir . 'bridges.php';
}
add_action('init', 'apollo_membership_load_includes', 5);

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Initialize plugin after apollo-core loads
 */
function apollo_membership_init(): void
{
    if (! defined('APOLLO_CORE_VERSION')) {
        return;
    }

    $plugin = \Apollo\Membership\Plugin::get_instance();
    $plugin->init();
}
add_action('plugins_loaded', 'apollo_membership_init', 15);

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
    __FILE__,
    function () {
        \Apollo\Membership\Activation::activate();
        flush_rewrite_rules();
    }
);

register_deactivation_hook(
    __FILE__,
    function () {
        \Apollo\Membership\Deactivation::deactivate();
        flush_rewrite_rules();
    }
);
