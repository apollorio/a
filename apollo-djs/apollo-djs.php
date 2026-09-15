<?php
/**
 * Plugin Name: Apollo DJs
 * Plugin URI: https://apollo.rio.br/plugins/apollo-djs
 * Description: DJs CPT: Shared across all plugins. Profile pages, social links, sound genres, carousel/slider/grid views. Style: apollo-v1.
 * Version: 1.1.7
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-djs
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\DJs
 */

/*
 * ARCH: apollo-djs / APOLLO_DJ_CPT, track
 * ARCH-MANUAL: escrito a mao (2026-09-09). gen-arch-blocks.js recusa
 *   ficheiros dirty no git e 41 de 42 estao dirty. Ver nota em apollo-core.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-djs   87 arquivos PHP, 11358 LOC
 * BOOT      plugins_loaded:15 (:125); card `track` em init:20
 *           (includes/card-track.php:245)
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (9); chrome e do apollo-templates
 * META      60 chaves tocadas
 * REST      5 rotas (5 publicas) - controlador so para `dj`, nao `track`
 * REQUIRES  apollo-core
 *
 * CONTRATO  Primeiro consumidor de apollo_card_register(). Partilha o slot
 *           init:20 com apollo-ui; nao colidem porque as chaves diferem
 *           (`track` vs type-1..type-6). O contrato e
 *           primeira-registacao-vence, entao a chave e que importa.
 *
 * NAO FACA
 *   - assumir que `track` esta completo: track.map_meta_cap continua com o
 *     efetivo do core em false contra a intencao do owner em true. A mesma
 *     classe ja foi corrigida para event/classified; track ficou de fora.
 *   - construir para /tracks: e um UNCLAIMED_DOMAIN_SURFACE - has_archive
 *     existe so para um link morto nao dar 404, sem template nem REST dono.
 *   - registar CPT direto: apollo-core e o dono do init:5.
 *
 * VERIFICAR   node D:/dev/_cos/verify/plugin-audit.js
 */

declare(strict_types=1);

namespace Apollo\DJs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_DJ_VERSION', '1.1.7' ); /* 1.1.7 — hidden SC transport, 20% seek, 60s fade. */
define( 'APOLLO_DJ_FILE', __FILE__ );
define( 'APOLLO_DJ_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_DJ_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_DJ_BASENAME', plugin_basename( __FILE__ ) );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK — apollo-core é OBRIGATÓRIO
// ═══════════════════════════════════════════════════════════════════════════

function apollo_dj_check_dependencies(): void {
	$active = get_option( 'active_plugins', array() );

	if ( ! in_array( 'apollo-core/apollo-core.php', $active, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo '<strong>Apollo DJs:</strong> ';
				esc_html_e( 'Requer Apollo Core ativo.', 'apollo-djs' );
				echo '</p></div>';
			}
		);
		deactivate_plugins( APOLLO_DJ_BASENAME );
		return;
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_dj_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER — PSR-4: Apollo\DJs\ → src/
// ═══════════════════════════════════════════════════════════════════════════

if ( file_exists( APOLLO_DJ_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_DJ_DIR . 'vendor/autoload.php';
}

spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\DJs\\';
		$base_dir = APOLLO_DJ_DIR . 'src/';
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

require_once APOLLO_DJ_DIR . 'includes/constants.php';
require_once APOLLO_DJ_DIR . 'includes/functions.php';
/* Apollo Surface declaration — see includes/surface.php. Inert until this
   plugin grows a render_single(); registers no CPT, meta, table or template. */
/* DJ single SSOT — page, inline embed and REST fragment all render through
   apollo_dj_render_single(). Must load BEFORE surface.php, which points the
   surface's renderer at it. */
require_once APOLLO_DJ_DIR . 'includes/render-single.php';
require_once APOLLO_DJ_DIR . 'includes/surface.php';
/* Track admin panel — declared through apollo_panel_register(), no subclass.
   Closes the gap where `track` had 12 registered meta keys and zero inputs. */
require_once APOLLO_DJ_DIR . 'includes/panel-track.php';
/* Track releases — query + derivation (credits, preview, card data). Pure
   functions; replaces the unbounded N+1 in apollo_get_latest_dj_tracks(). */
require_once APOLLO_DJ_DIR . 'includes/tracks.php';
/* Unified listen resolver — SoundCloud chain + native audio for Out Now cards. */
require_once APOLLO_DJ_DIR . 'includes/track-listen.php';
/* The track card, registered against apollo-core's Card Contract. First
   consumer: one card on /casa, /tracks and /dj/{id}, styles carried by the
   contract's print-once ledger. */
require_once APOLLO_DJ_DIR . 'includes/card-track.php';

// Frontend Editor field definitions (shared system via apollo-templates)
if ( file_exists( APOLLO_DJ_DIR . 'includes/frontend-fields.php' ) ) {
	require_once APOLLO_DJ_DIR . 'includes/frontend-fields.php';
}

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION — após apollo-core (priority 15)
// ═══════════════════════════════════════════════════════════════════════════

function apollo_dj_init(): void {
	$GLOBALS['apollo_dj'] = new Plugin();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_dj_init', 15 );

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
		Deactivation::deactivate();
		flush_rewrite_rules();
	}
);
