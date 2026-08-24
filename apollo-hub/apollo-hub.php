<?php

/**
 * Plugin Name: Apollo Hub
 * Plugin URI: https://apollo.rio.br/plugins/apollo-hub
 * Description: Hub público estilo Linktree para usuários Apollo — links, redes sociais, eventos e compartilhamento nativo. Rota /hub/{username}.
 * Version: 1.0.2
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-hub
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_HUB_VERSION', '1.0.2' );
define( 'APOLLO_HUB_FILE', __FILE__ );
define( 'APOLLO_HUB_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_HUB_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_HUB_BASENAME', plugin_basename( __FILE__ ) );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK — apollo-core é OBRIGATÓRIO
// ═══════════════════════════════════════════════════════════════════════════

function apollo_hub_check_dependencies(): void {
	$active = get_option( 'active_plugins', array() );

	if ( ! in_array( 'apollo-core/apollo-core.php', $active, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo '<strong>Apollo Hub:</strong> ';
				esc_html_e( 'Requer Apollo Core ativo.', 'apollo-hub' );
				echo '</p></div>';
			}
		);
		deactivate_plugins( APOLLO_HUB_BASENAME );
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_hub_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER — PSR-4: Apollo\Hub\ → src/
// ═══════════════════════════════════════════════════════════════════════════

if ( file_exists( APOLLO_HUB_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_HUB_DIR . 'vendor/autoload.php';
}

spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Hub\\';
		$base_dir = APOLLO_HUB_DIR . 'src/';
		$len      = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative = substr( $class, $len );
		$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// ═══════════════════════════════════════════════════════════════════════════
// INCLUDES
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_HUB_DIR . 'includes/constants.php';
require_once APOLLO_HUB_DIR . 'includes/functions.php';

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION — após apollo-core (priority 15)
// ═══════════════════════════════════════════════════════════════════════════

function apollo_hub_init(): void {
	$GLOBALS['apollo_hub'] = new Plugin();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_hub_init', 15 );

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
	__FILE__,
	function () {
		Activation::activate();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);
