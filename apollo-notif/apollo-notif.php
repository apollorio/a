<?php
/**
 * Plugin Name: Apollo Notifications
 * Plugin URI: https://apollo.rio.br
 * Description: In-app notifications, preferences, badge counts — adapted from BNFW engine patterns.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-notif
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Notif
 */

/*
 * ARCH: apollo-notif
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-notif   8 arquivos PHP, 3705 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (8); chrome é do apollo-templates
 * META      0 chaves tocadas
 * REST      namespace não literal no código — 13 rotas (1 públicas)
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants
define( 'APOLLO_NOTIF_VERSION', '1.0.1' );
define( 'APOLLO_NOTIF_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_NOTIF_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_NOTIF_FILE', __FILE__ );

// Autoload
spl_autoload_register(
	function ( $class ) {
		$prefix = 'Apollo\\Notif\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = APOLLO_NOTIF_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

// Activation / Deactivation
register_activation_hook( __FILE__, array( 'Apollo\\Notif\\Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Apollo\\Notif\\Deactivation', 'deactivate' ) );

// Bootstrap
add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
			return;
		}
		require_once APOLLO_NOTIF_PATH . 'includes/functions.php';
		\Apollo\Notif\Plugin::instance();
	},
	15
);
