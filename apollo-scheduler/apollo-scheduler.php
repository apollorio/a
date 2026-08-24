<?php
/**
 * Plugin Name: Apollo Scheduler
 * Plugin URI:  https://apollo.rio.br/plugins/apollo-scheduler
 * Description: Luxury Apollo-native appointment scheduling — nucleo-scoped agents, rooms, services, availability engine, dual calendar bridge.
 * Version:     1.0.1
 * Author:      Apollo::Rio
 * Author URI:  https://apollo.rio.br
 * License:     Proprietary
 * Text Domain: apollo-scheduler
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_SCHEDULER_VERSION', '1.0.1' );
define( 'APOLLO_SCHEDULER_FILE', __FILE__ );
define( 'APOLLO_SCHEDULER_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_SCHEDULER_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_SCHEDULER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Dependency check — apollo-core is mandatory.
 */
function apollo_scheduler_check_dependencies(): void {
	$active = get_option( 'active_plugins', array() );

	if ( ! in_array( 'apollo-core/apollo-core.php', $active, true ) ) {
		add_action(
			'admin_notices',
			static function (): void {
				echo '<div class="notice notice-error"><p>';
				echo '<strong>Apollo Scheduler:</strong> ';
				esc_html_e( 'Requires Apollo Core active.', 'apollo-scheduler' );
				echo '</p></div>';
			}
		);
		deactivate_plugins( APOLLO_SCHEDULER_BASENAME );
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_scheduler_check_dependencies', 5 );

if ( file_exists( APOLLO_SCHEDULER_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_SCHEDULER_DIR . 'vendor/autoload.php';
}

spl_autoload_register(
	static function ( string $class ): void {
		$prefix   = 'Apollo\\Scheduler\\';
		$base_dir = APOLLO_SCHEDULER_DIR . 'src/';
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

require_once APOLLO_SCHEDULER_DIR . 'includes/constants.php';
require_once APOLLO_SCHEDULER_DIR . 'includes/functions.php';

/**
 * Boot after apollo-core initializes (Diamond Rule 6).
 */
add_action(
	'apollo/core/initialized',
	static function (): void {
		if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
			return;
		}

		Plugin::get_instance()->init();
	},
	20
);

register_activation_hook(
	__FILE__,
	static function (): void {
		if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
			return;
		}
		Activation::activate();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		Deactivation::deactivate();
		flush_rewrite_rules();
	}
);
