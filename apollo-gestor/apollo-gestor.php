<?php

/**
 * Plugin Name: Apollo Gestor
 * Plugin URI: https://apollo.rio.br/plugins/apollo-gestor
 * Description: Gestor de Projetos — tarefas, time, financeiro, fornecedores, cronograma, kanban. Gestão de produção para a indústria cultural do Rio.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-gestor
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_GESTOR_VERSION', '1.0.1' );
define( 'APOLLO_GESTOR_DB_VERSION', 4 );
define( 'APOLLO_GESTOR_FILE', __FILE__ );
define( 'APOLLO_GESTOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_GESTOR_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_GESTOR_BASENAME', plugin_basename( __FILE__ ) );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

function apollo_gestor_check_dependencies(): void {
	$active = get_option( 'active_plugins', array() );

	if ( ! in_array( 'apollo-core/apollo-core.php', $active, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>Apollo Gestor:</strong> Requer Apollo Core ativo.</p></div>';
			}
		);
		deactivate_plugins( APOLLO_GESTOR_BASENAME );
		return;
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_gestor_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER (PSR-4)
// ═══════════════════════════════════════════════════════════════════════════

spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Gestor\\';
		$len      = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative = substr( $class, $len );
		$relative = str_replace( '\\', '/', $relative );

		// Core module classes: Apollo\Gestor\Core\* → includes/core/
		if ( str_starts_with( $relative, 'Core/' ) ) {
			$file = APOLLO_GESTOR_DIR . 'includes/core/' . substr( $relative, 5 ) . '.php';
		}
		// Module classes: Apollo\Gestor\Modules\* → includes/modules/
		elseif ( str_starts_with( $relative, 'Modules/' ) ) {
			$mod_relative = substr( $relative, 8 );
			$file         = APOLLO_GESTOR_DIR . 'includes/modules/' . $mod_relative . '.php';
		}
		// Default PSR-4: Apollo\Gestor\* → src/
		else {
			$file = APOLLO_GESTOR_DIR . 'src/' . $relative . '.php';
		}

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

function apollo_gestor_init(): void {
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		return;
	}

	$plugin = Plugin::get_instance();
	$plugin->init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_gestor_init', 15 );
// Initialization bridge: also respond when apollo-core fires its ready hook.
add_action( 'apollo/core/initialized', __NAMESPACE__ . '\\apollo_gestor_init' );

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
	__FILE__,
	function () {
		if ( ! is_plugin_active( 'apollo-core/apollo-core.php' ) ) {
			wp_die( 'Apollo Gestor requer Apollo Core ativo.' );
		}
		Database::install();
		Plugin::ensure_defaults();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		$timestamp = wp_next_scheduled( Cron\TaskReminderCron::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, Cron\TaskReminderCron::HOOK );
		}
	}
);
