<?php
/**
 * Plugin Name: Apollo Events
 * Plugin URI: https://apollo.rio.br/plugins/apollo-events
 * Description: Events CPT: Backend, listings, single event, multi-view calendar, card/list/map views, 4 style packs (base, apollo-v1, ui-thim, ui-lis), expiration system 30min. Adapted from WP Event Manager + Apollo Events Manager.
 * Version: 1.7.7
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-events
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

/* 1.7.1 — /eventos trocou a `.pev-masthead` (montada em JS por portal/app.php)
   pelo bloco reutilizável apollo_listing_header() de apollo-templates: o
   "Header 02 · Kinetic Mask · Apple" aprovado. Ver
   styles/base/template-parts/archive/portal/{header,header-bridge}.php.
   1.7.4 — _event_int_rank (0-10, INTERNAL, never frontend): src/Admin/RankMetabox.php
   renders the select right below Publish/Update; apollo_event_get_int_rank() /
   apollo_event_set_int_rank() / apollo_event_get_top_ranked() in includes/functions.php
   feed apollo-telegram's event-selection logic. Registered in apollo-core's
   MetaRegistry with show_in_rest=false.
   1.7.5 — 5 internal vibe-tag checkboxes (_event_tag_underground/_mainstream/
   _comercial/_lgbtqia/_sexparty), same admin spot below the int-rank select.
   apollo_event_internal_tags() is the canonical slug=>key map. New generic
   entry point apollo_event_find_best_match() — rank + tags + date proximity
   folded into one query, built for ANY Apollo plugin to consume, not just
   apollo-telegram.
   1.7.6 — apollo_event_vibe_quiz_definition()/classify_vibe_quiz()/
   matches_vibe_quiz(): the a/b/c/d "what party suits you" quiz table (single
   source for both the question text and the tag-match rule), consumed by
   apollo-telegram's EventsBotService + new VibeQuizCallback to gate event
   suggestions behind the quiz and filter results by answer.
   1.7.7 — apollo_event_vibe_quiz_definition() takes an optional $lang
   ('pt' default | 'en'); label/question now resolve per language while
   require/exclude (the actual match rule) stay the one language-independent
   copy. Backward compatible — no-arg callers keep getting pt. Consumed by
   apollo-telegram's new bilingual Lang service. */
define( 'APOLLO_EVENT_VERSION', '1.7.7' );
define( 'APOLLO_EVENT_FILE', __FILE__ );
define( 'APOLLO_EVENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_EVENT_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_EVENT_BASENAME', plugin_basename( __FILE__ ) );

/** Absolute CDN grain fallback — assets subdomain (never concat with APOLLO_EVENT_URL). */
define( 'APOLLO_ASSETS_GRAIN_URL', 'https://assets.apollo.rio.br/img/bg/grain-001.jpg' );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK — apollo-core é OBRIGATÓRIO
// ═══════════════════════════════════════════════════════════════════════════

function apollo_event_check_dependencies(): void {
	$active = (array) get_option( 'active_plugins', array() );
	$core_active = in_array( 'apollo-core/apollo-core.php', $active, true )
		|| defined( 'APOLLO_CORE_VERSION' )
		|| class_exists( '\\Apollo\\Core\\Plugin', false );

	if ( ! $core_active ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo '<strong>Apollo Events:</strong> ';
				esc_html_e( 'Requer Apollo Core ativo.', 'apollo-events' );
				echo '</p></div>';
			}
		);
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( APOLLO_EVENT_BASENAME );
		}
		return;
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_event_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER — PSR-4: Apollo\Event\ → src/
// ═══════════════════════════════════════════════════════════════════════════

if ( file_exists( APOLLO_EVENT_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_EVENT_DIR . 'vendor/autoload.php';
}

spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Event\\';
		$base_dir = APOLLO_EVENT_DIR . 'src/';
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

require_once APOLLO_EVENT_DIR . 'includes/constants.php';
require_once APOLLO_EVENT_DIR . 'includes/functions.php';
// Global aliases MUST load via bootstrap (no-namespace file) so templates
// can call apollo_event_*() without the Apollo\Event\ prefix.
require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION — após apollo-core (priority 15)
// ═══════════════════════════════════════════════════════════════════════════

function apollo_event_init(): void {
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		return;
	}

	Plugin::get_instance()->init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_event_init', 15 );

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
	__FILE__,
	function () {
		if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
			return;
		}
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
