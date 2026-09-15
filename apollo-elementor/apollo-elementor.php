<?php
/**
 * Plugin Name: Apollo Elementor
 * Plugin URI:  https://apollo.rio.br
 * Description: Server-rendered Elementor widgets with cache-first data helpers for the Apollo ecosystem. Premium singles, planners, charts, and gestor dashboards.
 * Version:     1.0.1
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author:      Apollo Rio
 * License:     GPL-2.0-or-later
 * Text Domain: apollo-elementor
 * Domain Path: /languages
 *
 * @package Apollo\ElementorAE
 */

/*
 * ARCH: apollo-elementor
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-elementor   49 arquivos PHP, 3941 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (1); chrome é do apollo-templates
 * META      16 chaves tocadas, 16 SEM definição governante
 * REST      nenhuma rota
 * REQUIRES  nenhuma dependência confirmada
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

define( 'APOLLO_AE_VERSION', '1.0.1' );
define( 'APOLLO_AE_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_AE_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_AE_FILE', __FILE__ );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'Apollo\\ElementorAE\\';
	if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
		return;
	}
	$relative = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, strlen( $prefix ) ) );
	$file     = APOLLO_AE_DIR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';
	if ( is_file( $file ) ) {
		require_once $file;
	}
} );

require_once APOLLO_AE_DIR . 'src/helpers.php';

add_action( 'plugins_loaded', static function (): void {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', static function (): void {
			echo '<div class="notice notice-warning"><p><strong>Apollo Elementor</strong>: ';
			esc_html_e( 'Elementor must be installed and active.', 'apollo-elementor' );
			echo '</p></div>';
		} );
		return;
	}

	\Apollo\ElementorAE\Plugin::get_instance()->init();
}, 20 );

register_uninstall_hook( __FILE__, [ \Apollo\ElementorAE\Plugin::class, 'uninstall' ] );
