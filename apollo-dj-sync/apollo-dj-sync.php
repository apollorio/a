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
