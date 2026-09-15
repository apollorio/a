<?php
/**
 * Apollo Adverts
 *
 * Classifieds/Marketplace system for the Apollo ecosystem.
 * Adapted from WPAdverts architecture — form system, gallery, listings.
 * Integrated with BuddyPress, Apollo Fav, WOW, Notif, Social.
 *
 * @package Apollo\Adverts
 *
 * Plugin Name: Apollo Adverts
 * Plugin URI: https://apollo.rio.br/plugins/apollo-adverts
 * Description: Classificados/Marketplace: CPT classified, formulários frontend, galeria, busca, gerenciamento. Integrado com BuddyPress, Fav, WOW, Notif.
 * Version: 1.2.2
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-adverts
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 */

/*
 * ARCH: apollo-adverts / APOLLO_CPT_CLASSIFIED
 * ARCH-MANUAL: escrito a mao (2026-09-09). gen-arch-blocks.js recusa
 *   ficheiros dirty no git e 41 de 42 estao dirty. Ver nota em apollo-core.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-adverts   88 arquivos PHP, 16593 LOC
 * BOOT      plugins_loaded:15 (:163); includes carregados em init:5 (:147)
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (38); chrome e do apollo-templates
 * META      32 chaves tocadas
 * REST      apollo/v1 - 9 rotas (4 publicas)
 * REQUIRES  apollo-chat, apollo-core, apollo-fav, apollo-login,
 *           apollo-notif, apollo-social, apollo-templates, apollo-wow
 *           - o maior fan-out de dependencias do ecossistema
 *
 * CONTRATO  Unico consumidor de apollo_safety_register()
 *           (includes/safety-gate.php:87). O contrato e default-deny.
 *
 * NAO FACA
 *   - adicionar isencoes ao safety gate sem nomear a razao. A isencao
 *     hostel_missing_url dispensa exatamente o portao que devia manter -
 *     defeito aberto, registado, nao corrigido.
 *   - registar CPT direto: apollo-core e o dono do init:5.
 *     Fallback do owner so com post_type_exists().
 *   - gravar meta de outro dominio.
 *
 * VERIFICAR   node D:/dev/_cos/verify/plugin-audit.js
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_ADVERTS_VERSION', '1.2.2' ); /* 1.2.2 — /anuncios mockup layout: market-screen.css + carousel stage. */
define( 'APOLLO_ADVERTS_FILE', __FILE__ );
define( 'APOLLO_ADVERTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_ADVERTS_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_ADVERTS_BASENAME', plugin_basename( __FILE__ ) );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

function apollo_adverts_check_dependencies(): void {
	$active_plugins = get_option( 'active_plugins', array() );
	if ( ! in_array( 'apollo-core/apollo-core.php', $active_plugins, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>Apollo Adverts:</strong> Requer Apollo Core ativo.</p></div>';
			}
		);
		deactivate_plugins( APOLLO_ADVERTS_BASENAME );
		return;
	}
}
add_action( 'plugins_loaded', 'apollo_adverts_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER (PSR-4 for src/)
// ═══════════════════════════════════════════════════════════════════════════

spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Adverts\\';
		$base_dir = APOLLO_ADVERTS_DIR . 'src/';
		$len      = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
		}
		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// ═══════════════════════════════════════════════════════════════════════════
// INCLUDES (procedural — adapted from WPAdverts pattern)
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_ADVERTS_DIR . 'includes/constants.php';
require_once APOLLO_ADVERTS_DIR . 'includes/functions.php';
/* Hostel admin panel — declared through apollo_panel_register(), no subclass.
   Closes the gap where `hostel` had 5 registered meta keys and zero inputs. */
require_once APOLLO_ADVERTS_DIR . 'includes/panel-hostel.php';

/**
 * Load all include files after init
 * Adapted from WPAdverts includes loading
 */
function apollo_adverts_load_includes(): void {

	$dir = APOLLO_ADVERTS_DIR . 'includes/';

	// CPT & Taxonomy fallback registration
	require_once $dir . 'cpt.php';

	// Form field definitions — adapted from WPAdverts defaults.php
	require_once $dir . 'form-fields.php';

	// Gallery/upload handling — adapted from WPAdverts gallery.php
	require_once $dir . 'gallery.php';

	// Shortcodes
	require_once $dir . 'shortcodes.php';

	// Widgets
	require_once $dir . 'widgets.php';

	// AJAX handlers
	require_once $dir . 'ajax-handlers.php';

	// Cron events — adapted from WPAdverts events.php
	require_once $dir . 'cron.php';

	// User integration (profile fields)
	require_once $dir . 'buddypress.php';

	// Ecosystem integrations (Fav, WOW, Notif, Social, Chat)
	require_once $dir . 'integrations.php';

	// Share affordance — [data-advert-share] on cards + /casa marquees.
	require_once $dir . 'share.php';

	// Expandable resale/accommodation ticket — /casa + /anuncios + list.
	require_once $dir . 'rt-card.php';

	// Safety gate — answers apollo-core's safety contract. Loads AFTER
	// integrations.php because it guards the chat CTA that file renders, and
	// registers the exemption that keeps hostel adverts (booking link, never a
	// chat) out of the gate. Every other advert is gated by default.
	require_once $dir . 'safety-gate.php';

	// Witness vouch inbox + deep-link notify (after safety-gate helpers exist).
	require_once $dir . 'safety-notify.php';

	// Event selector for ticket resale classifieds + Solicitar Evento popup
	require_once $dir . 'event-selector.php';

	// Meus Anúncios data provider (PHASE 008) — real query, no fixtures.
	require_once $dir . 'my-listings-data.php';
}
add_action( 'init', 'apollo_adverts_load_includes', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Initialize plugin after apollo-core loads
 */
function apollo_adverts_init(): void {
	if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
		return;
	}
	$plugin = \Apollo\Adverts\Plugin::get_instance();
	$plugin->init();
}
add_action( 'plugins_loaded', 'apollo_adverts_init', 15 );

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
	__FILE__,
	function () {
		\Apollo\Adverts\Activation::activate();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		\Apollo\Adverts\Deactivation::deactivate();
		flush_rewrite_rules();
	}
);
