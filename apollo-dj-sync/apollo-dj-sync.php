<?php
/**
 * Plugin Name: Apollo DJ Sync
 * Plugin URI:  https://apollo.rio.br
 * Description: Per-user feature gates, global config, and session logging for apolloDJ.exe. Thin REST layer on top of apollo-core.
 * Version:     1.0.1
 * Author:      Apollo
 * Text Domain: apollo-dj-sync
 * Requires PHP: 8.1
 *
 * Diamond Rule 1: All meta keys registered via apollo-core MetaRegistry.
 * Diamond Rule 5: All endpoints under apollo/v1, every write has permission_callback.
 * Diamond Rule 7: apolloDJ.exe authenticates via /app/auth only. No direct DB access.
 */

/*
 * ARCH: apollo-dj-sync
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-dj-sync   5 arquivos PHP, 769 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        não emite HTML
 * META      0 chaves tocadas
 * REST      namespace não literal no código — 1 rotas (0 públicas)
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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'APOLLO_DJ_SYNC_VERSION', '1.0.1' );
define( 'APOLLO_DJ_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_DJ_SYNC_APP_ID', 'apollodj' );

// PSR-4 autoloader
spl_autoload_register( function ( string $class ): void {
    $prefix   = 'Apollo\\DJSync\\';
    $base_dir = APOLLO_DJ_SYNC_PATH . 'src/';

    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }

    $relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
    $file     = $base_dir . $relative . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Boot only after apollo-core has initialized.
 * Diamond Rule 6: hook into apollo/core/initialized.
 */
add_action( 'apollo/core/initialized', function (): void {
    ( new Apollo\DJSync\Core\DJMetaRegistry() )->register();
    ( new Apollo\DJSync\Admin\DJUserAdmin() )->init();

    // REST routes must wait for rest_api_init; $wp_rewrite is null at plugins_loaded.
    add_action( 'rest_api_init', function (): void {
        ( new Apollo\DJSync\API\DJPermissionsController() )->register_routes();
    } );
}, 20 );
