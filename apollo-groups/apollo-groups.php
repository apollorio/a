<?php
/**
 * Plugin Name: Apollo Groups
 * Plugin URI: https://apollo.rio.br
 * Description: Comunas (public communities) — adapted from BuddyPress bp-groups. Public-only, flat, no hierarchy.
 * Version: 1.2.0
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-groups
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Groups
 */

/*
 * ARCH: apollo-groups
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-groups   55 arquivos PHP, 7245 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (8); chrome é do apollo-templates
 * META      0 chaves tocadas
 * REST      namespace não literal no código — 24 rotas (0 públicas)
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

define( 'APOLLO_GROUPS_VERSION', '1.2.0' );
define( 'APOLLO_GROUPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_GROUPS_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_GROUPS_FILE', __FILE__ );

spl_autoload_register(
	function ( $class ) {
		$prefix = 'Apollo\\Groups\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = APOLLO_GROUPS_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Apollo\\Groups\\Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Apollo\\Groups\\Deactivation', 'deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
			return;
		}
		require_once APOLLO_GROUPS_PATH . 'includes/functions.php';
		\Apollo\Groups\Plugin::instance();
	},
	15
);
