<?php
/**
 * Plugin Name: Apollo Calendar
 * Plugin URI:  https://apollo.rio.br/plugins/apollo-calendar
 * Description: Per-user personal calendar — aggregates private appointments, event RSVPs, scheduler bookings and BR/Rio holidays into one diamond-level planner at /agenda.
 * Version:     1.1.0
 * Author:      Apollo::Rio
 * Author URI:  https://apollo.rio.br
 * License:     Proprietary
 * Text Domain: apollo-calendar
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Calendar
 */

/*
 * ARCH: apollo-calendar
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-calendar   16 arquivos PHP, 2893 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (5); chrome é do apollo-templates
 * META      8 chaves tocadas, 4 SEM definição governante
 * REST      namespace não literal no código — 5 rotas (1 públicas)
 * REQUIRES  apollo-core, apollo-remind
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

namespace Apollo\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_CALENDAR_VERSION',  '1.1.0' );
define( 'APOLLO_CALENDAR_FILE',     __FILE__ );
define( 'APOLLO_CALENDAR_DIR',      plugin_dir_path( __FILE__ ) );
define( 'APOLLO_CALENDAR_URL',      plugin_dir_url( __FILE__ ) );
define( 'APOLLO_CALENDAR_BASENAME', plugin_basename( __FILE__ ) );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK — apollo-core is REQUIRED
// ═══════════════════════════════════════════════════════════════════════════

function apollo_calendar_check_dependencies(): void {
	$active = get_option( 'active_plugins', array() );

	if ( ! in_array( 'apollo-core/apollo-core.php', $active, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo '<strong>Apollo Calendar:</strong> ';
				esc_html_e( 'Requer Apollo Core ativo.', 'apollo-calendar' );
				echo '</p></div>';
			}
		);
		deactivate_plugins( APOLLO_CALENDAR_BASENAME );
		return;
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_calendar_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER — PSR-4: Apollo\Calendar\ → src/
// ═══════════════════════════════════════════════════════════════════════════

if ( file_exists( APOLLO_CALENDAR_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_CALENDAR_DIR . 'vendor/autoload.php';
}

spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Calendar\\';
		$base_dir = APOLLO_CALENDAR_DIR . 'src/';
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
// INITIALIZATION — after apollo-core (priority 20)
// ═══════════════════════════════════════════════════════════════════════════

function apollo_calendar_init(): void {
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		return;
	}
	Plugin::get_instance()->init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_calendar_init', 20 );

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
	__FILE__,
	function () {
		Activation::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		Deactivation::deactivate();
	}
);
