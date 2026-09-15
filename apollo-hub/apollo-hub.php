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

/*
 * ARCH: apollo-hub / APOLLO_HUB_CPT
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-hub   35 arquivos PHP, 5391 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (24); chrome é do apollo-templates
 * META      11 chaves tocadas, 3 SEM definição governante
 * REST      namespace não literal no código — 6 rotas (5 públicas)
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
