<?php
/**
 * Plugin Name: Apollo Local
 * Plugin URI: https://apollo.rio.br/plugins/apollo-local
 * Description: Locations CPT (local). Geocoding, maps, nearby search, area zones. Style: apollo-v1.
 * Version: 1.0.4
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-local
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Local
 */

declare(strict_types=1);

namespace Apollo\Local;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_LOCAL_VERSION', '1.0.4' ); /* 1.0.4 — debug beacon stripped from GalleryMetabox; src/Registry.php and src/Admin/Metabox.php retired as tombstones (dead duplicate CPT/taxonomy/metabox registrations). */
define( 'APOLLO_LOCAL_FILE', __FILE__ );
define( 'APOLLO_LOCAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_LOCAL_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_LOCAL_BASENAME', plugin_basename( __FILE__ ) );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK — apollo-core é OBRIGATÓRIO
// ═══════════════════════════════════════════════════════════════════════════

function apollo_local_check_dependencies(): void {
	$active = get_option( 'active_plugins', array() );

	if ( ! in_array( 'apollo-core/apollo-core.php', $active, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo '<strong>Apollo Local:</strong> ';
				esc_html_e( 'Requer Apollo Core ativo.', 'apollo-local' );
				echo '</p></div>';
			}
		);
		deactivate_plugins( APOLLO_LOCAL_BASENAME );
		return;
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_local_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER — PSR-4: Apollo\Local\ → src/
// ═══════════════════════════════════════════════════════════════════════════

if ( file_exists( APOLLO_LOCAL_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_LOCAL_DIR . 'vendor/autoload.php';
}

spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Local\\';
		$base_dir = APOLLO_LOCAL_DIR . 'src/';
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

require_once APOLLO_LOCAL_DIR . 'includes/constants.php';
require_once APOLLO_LOCAL_DIR . 'includes/functions.php';
/* Apollo Surface declaration — see includes/surface.php. Inert until this
   plugin grows a render_single(); registers no CPT, meta, table or template. */
require_once APOLLO_LOCAL_DIR . 'includes/surface.php';

// Frontend Editor field definitions (shared system via apollo-templates)
if ( file_exists( APOLLO_LOCAL_DIR . 'includes/frontend-fields.php' ) ) {
	require_once APOLLO_LOCAL_DIR . 'includes/frontend-fields.php';
}

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION — após apollo-core (priority 15)
// ═══════════════════════════════════════════════════════════════════════════

function apollo_local_init(): void {
	$GLOBALS['apollo_local'] = new Plugin();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_local_init', 15 );

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
		Deactivation::deactivate();
		flush_rewrite_rules();
	}
);
