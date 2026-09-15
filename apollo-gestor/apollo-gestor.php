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

/*
 * ARCH: apollo-gestor
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-gestor   46 arquivos PHP, 6890 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (1); chrome é do apollo-templates
 * META      11 chaves tocadas, 7 SEM definição governante
 * REST      nenhuma rota
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
/*
 * BOOT  apollo/core/initialized  (apollo-core fires it at plugins_loaded:1)
 *
 * This plugin used to bind the same callback twice: here, and again at
 * plugins_loaded:15. The second one never did anything. apollo-core fires
 * apollo/core/initialized from inside apollo_core_bootstrap(), which runs at
 * plugins_loaded:1 -- so the "bridge" was not a later fallback, it was the
 * EARLIER of the two, and Plugin::init()'s $this->initialized guard
 * (src/Plugin.php:52) made the :15 pass a silent no-op on every request.
 *
 * The :15 line was removed rather than this one, deliberately: dropping the
 * call that already no-ops changes nothing at runtime, while dropping this
 * one would move the plugin's real boot 14 priority levels later.
 *
 * Consequence to keep in mind: gestor boots at plugins_loaded:1, BEFORE the
 * ~21 plugins that boot at :15 and the batch at :20. Every plugin FILE has
 * been required by then, but their plugins_loaded callbacks have not run.
 * Do not reach into another Apollo plugin's initialised state from
 * Plugin::init() -- use that plugin's own hook, or defer to init.
 */
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
