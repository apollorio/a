<?php
/**
 * Plugin Name: Apollo::Rio Reminders
 * Plugin URI:  https://apollo.rio.br
 * Description: Multi-channel personal calendar reminders — Telegram Bot, Web Push (VAPID), in-app (apollo-notif), email (apollo-email). Per-user reminder calendar with WP-Cron queue processor.
 * Version:     1.0.1
 * Requires PHP: 8.1
 * Author:      Apollo::Rio
 * Author URI:  https://apollo.rio.br
 * Text Domain: apollo-remind
 * Domain Path: /languages
 * License:     GPL-2.0-or-later
 */

/*
 * ARCH: apollo-remind
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-remind   16 arquivos PHP, 2109 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (1); chrome é do apollo-templates
 * META      2 chaves tocadas
 * REST      namespace não literal no código — 13 rotas (2 públicas)
 * REQUIRES  apollo-core, apollo-email, apollo-notif
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

/* ─── Constants ──────────────────────────────────────────────── */
define( 'APOLLO_REMIND_VERSION',    '1.0.1' );
define( 'APOLLO_REMIND_DB_VERSION', 3 );
define( 'APOLLO_REMIND_FILE',       __FILE__ );
define( 'APOLLO_REMIND_PATH',       plugin_dir_path( __FILE__ ) );
define( 'APOLLO_REMIND_URL',        plugin_dir_url( __FILE__ ) );
define( 'APOLLO_REMIND_BASENAME',   plugin_basename( __FILE__ ) );

/* ─── Dependency gate: apollo-core required ──────────────────── */
$active_plugins = get_option( 'active_plugins', array() );
$core_found     = false;
foreach ( $active_plugins as $p ) {
    if ( str_contains( $p, 'apollo-core' ) ) {
        $core_found = true;
        break;
    }
}
if ( ! $core_found ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Apollo Remind</strong> requires <code>apollo-core</code> to be active.</p></div>';
    } );
    return;
}

/* ─── Autoloader ─────────────────────────────────────────────── */
spl_autoload_register( function ( $class ) {
    $prefix = 'Apollo\\Remind\\';
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }
    $relative = substr( $class, strlen( $prefix ) );
    $file     = APOLLO_REMIND_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

/* ─── Activation / Deactivation ──────────────────────────────── */
// Register cron schedule early so it's available during activation
add_filter( 'cron_schedules', [ \Apollo\Remind\Cron\Scheduler::class, 'add_schedules' ] );

register_activation_hook( __FILE__, [ \Apollo\Remind\Core\Activation::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \Apollo\Remind\Core\Activation::class, 'deactivate' ] );

/* ─── Bootstrap ──────────────────────────────────────────────── */
add_action( 'plugins_loaded', function () {
    \Apollo\Remind\Core\Plugin::instance();
}, 20 );
