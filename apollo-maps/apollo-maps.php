<?php

/**
 * Plugin Name: Apollo Maps
 * Plugin URI: https://apollo.rio.br/plugins/apollo-maps
 * Description: Leaflet-powered map explorer for Apollo events and locs.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-maps
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Maps
 */

/*
 * ARCH: apollo-maps
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-maps   9 arquivos PHP, 824 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (1); chrome é do apollo-templates
 * META      6 chaves tocadas
 * REST      namespace não literal no código — 1 rotas (1 públicas)
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

namespace Apollo\Maps;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Load plugin functions from WordPress core.
if (! function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define('APOLLO_MAPS_VERSION', '1.0.1');
define('APOLLO_MAPS_FILE', __FILE__);
define('APOLLO_MAPS_DIR', plugin_dir_path(__FILE__));
define('APOLLO_MAPS_URL', plugin_dir_url(__FILE__));
define('APOLLO_MAPS_BASENAME', plugin_basename(__FILE__));

define('APOLLO_MAPS_REST_NAMESPACE', 'apollo/v1');

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Ensure apollo-core is active (required)
 */
function apollo_maps_check_dependencies(): void
{
    $active_plugins = get_option('active_plugins', []);

    if (! in_array('apollo-core/apollo-core.php', $active_plugins, true)) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Apollo Maps:</strong> Requires Apollo Core plugin to be active.</p></div>';
        });
        deactivate_plugins(APOLLO_MAPS_BASENAME);
        return;
    }
}
add_action('plugins_loaded', __NAMESPACE__ . '\\apollo_maps_check_dependencies', 5);

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER
// ═══════════════════════════════════════════════════════════════════════════

if (file_exists(APOLLO_MAPS_DIR . 'vendor/autoload.php')) {
    require_once APOLLO_MAPS_DIR . 'vendor/autoload.php';
}

spl_autoload_register(function (string $class) {
    $prefix   = 'Apollo\\Maps\\';
    $base_dir = APOLLO_MAPS_DIR . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ═══════════════════════════════════════════════════════════════════════════
// INCLUDES
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_MAPS_DIR . 'includes/constants.php';
require_once APOLLO_MAPS_DIR . 'includes/functions.php';

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

function apollo_maps_init(): void
{
    if (! defined('APOLLO_CORE_VERSION')) {
        return;
    }

    $plugin = Plugin::get_instance();
    $plugin->init();
}
add_action('plugins_loaded', __NAMESPACE__ . '\\apollo_maps_init', 15);

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(__FILE__, function () {
    if (! is_plugin_active('apollo-core/apollo-core.php')) {
        wp_die('Apollo Maps requires Apollo Core to be active.');
    }

    Activation::activate();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    Deactivation::deactivate();
    flush_rewrite_rules();
});
