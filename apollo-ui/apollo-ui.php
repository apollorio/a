<?php
/**
 * Plugin Name: Apollo UI
 * Description: Card type system (type-1 … type-6) and the standard full-viewport lightbox binding for every Apollo CPT. Chooses card style per post type from wp-admin, exposes one global shortcode, and reuses apollo-core's card contract rather than competing with it.
 * Version:     1.0.0
 * Author:      apollo::rio
 * Text Domain: apollo-ui
 *
 * ARCH
 * ----
 * WHY THIS PLUGIN EXISTS
 *   apollo-core/includes/card-contract.php shipped a real card registry in
 *   2026-08-17 and, one year on, had exactly ONE consumer
 *   (apollo-djs/includes/card-track.php, which calls itself "THE FIRST
 *   CONSUMER … the contract shipped with zero"). Meanwhile 30+ card files
 *   across 12 plugins each carry their own markup and CSS. apollo-ui does not
 *   add a second registry: it registers named STYLES (type-1 … type-6) into the
 *   existing contract, so a surface picks a look instead of forking markup.
 *
 * WHAT IT DELIBERATELY DOES NOT DO
 *   It does not implement a lightbox. apollo-events already ships a complete
 *   one — apollo_event_lightbox_shell() + [data-ev-open] triggers + the REST
 *   fragment at /wp-json/apollo/v1/eventos/{id}/fragmento — and it is live and
 *   working on /eventos today (verified: 16 triggers, shell present, fragment
 *   returns HTTP 200). Re-implementing it would recreate exactly the duplication
 *   the card contract exists to end. apollo-ui BINDS to that runtime and
 *   generalises it to any post type through window.APOLLO_SURFACES, which
 *   apollo-core already publishes for `event` and `dj`.
 *
 * BOOT
 *   Registers on init:20 — after apollo-core's card contract (loaded at
 *   apollo-core.php:179) and after apollo-djs registers `track` at init:20, so
 *   a first-registration-wins collision can never take a live card away from
 *   its owner.
 *
 * SAFETY
 *   Every cross-plugin call is function_exists()-guarded. If apollo-core or
 *   apollo-events is inactive this plugin degrades to nothing rather than
 *   fataling — see the 2 unguarded Plus-API call sites in apollo-events for
 *   the failure mode being avoided.
 *
 * @package Apollo\UI
 */

/*
 * ARCH: apollo-ui / estilos de card type-1..type-6 (sem CPT)
 * ARCH-MANUAL: escrito a mao (2026-09-09). Ver nota em apollo-core.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-ui   7 arquivos PHP — apresentacao, nada mais
 * BOOT      init:20 (apollo-ui.php:167). NAO tem binding em
 *           plugins_loaded. E o unico plugin do ecossistema assim.
 * RUNTIME   nao regista CPT, taxonomia, meta nem tabela. Regista os
 *           estilos type-1..type-6 no card-contract do apollo-core.
 * UI        emite apollo-ui.css/js por tres caminhos paralelos:
 *           wp_enqueue_scripts:20 (classico), apollo/canvas/head:20 +
 *           apollo/{canvas,plus}/before_close:20 (Blank Canvas, onde
 *           wp_head() NUNCA e chamado) e apollo/ui/emit (escotilha).
 * REQUIRES  apollo-core (card-contract)
 *
 * ESTADO   Admitido por instrucao do operador em 2026-09-05. A regra
 *   anterior "apollo-ui nao existe (nao inventar)" esta SUPERSEDIDA;
 *   doctrine-audit.js:182 regista a mudanca. Este plugin conta como o 43o.
 *
 * NAO FACA
 *   - confiar na ordem em wp_footer:20. O wp_print_footer_scripts do
 *     proprio WordPress esta na MESMA prioridade. A guarda
 *     wp_script_is(...,'done') so funciona porque o core registou
 *     primeiro. Se precisar de garantia, mude a prioridade, nao a guarda.
 *   - registar CPT, taxonomia ou meta aqui. Se isto crescer para dominio,
 *     deixa de ser apollo-ui.
 *   - registar uma chave de card que outro plugin ja tenha. O contrato e
 *     primeira-registacao-vence (card-contract.php:124). apollo-djs regista
 *     `track` tambem em init:20 — hoje nao colide porque as chaves diferem.
 *   - misturar estilos de nomes de filtro: apollo/ui/enabled usa barras,
 *     apollo_ui_surfaces usa underscores. Padronizar exige deprecacao.
 *
 * VERIFICAR   node D:/dev/_cos/verify/doctrine-audit.js
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_UI_VERSION', '1.0.0' );
define( 'APOLLO_UI_FILE', __FILE__ );
define( 'APOLLO_UI_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_UI_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_UI_OPTION', 'apollo_ui_settings' );

/**
 * Default settings.
 *
 * `types` maps a post type to a card style key. Anything not listed falls back
 * to APOLLO_UI_DEFAULT_TYPE, so a new CPT renders instead of vanishing.
 *
 * @return array<string,mixed>
 */
function apollo_ui_defaults(): array {
	return array(
		'enabled'        => true,
		'lightbox'       => true,
		'default_type'   => 'type-1',
		'types'          => array(
			'event'      => 'type-1',
			'dj'         => 'type-4',
			'track'      => 'type-3',
			'classified' => 'type-2',
			'local'      => 'type-2',
			'hub'        => 'type-4',
			'doc'        => 'type-6',
		),
		'lightbox_cpts'  => array( 'event', 'dj' ),
	);
}

/**
 * Merged settings. Never returns a partial array.
 *
 * @return array<string,mixed>
 */
function apollo_ui_settings(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$saved = get_option( APOLLO_UI_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$d = apollo_ui_defaults();
	$cache = array_merge( $d, $saved );
	// types must merge key-wise, not replace wholesale.
	$cache['types'] = array_merge( $d['types'], is_array( $saved['types'] ?? null ) ? $saved['types'] : array() );
	return $cache;
}

/** Reset the settings cache. Used by the settings screen after a save. */
function apollo_ui_flush_settings(): void {
	// Cheap and explicit: the static above is per-request, so a save in the
	// admin screen must not read its own stale copy on the redirect.
	wp_cache_delete( APOLLO_UI_OPTION, 'options' );
}

/**
 * Card style key for a post type.
 */
function apollo_ui_type_for( string $post_type ): string {
	$s = apollo_ui_settings();
	$t = $s['types'][ $post_type ] ?? $s['default_type'];
	return apollo_ui_is_type( (string) $t ) ? (string) $t : 'type-1';
}

require_once APOLLO_UI_DIR . 'includes/types.php';
require_once APOLLO_UI_DIR . 'includes/render.php';
require_once APOLLO_UI_DIR . 'includes/surfaces.php';
require_once APOLLO_UI_DIR . 'includes/shortcode.php';
require_once APOLLO_UI_DIR . 'includes/enqueue.php';

if ( is_admin() ) {
	require_once APOLLO_UI_DIR . 'includes/settings.php';
}

/**
 * Register every card style into apollo-core's contract.
 *
 * init:20 — see BOOT note in the file docblock. Guarded: with no contract this
 * is a no-op and surfaces keep their own markup.
 */
function apollo_ui_boot(): void {
	if ( ! apollo_ui_settings()['enabled'] ) {
		return;
	}
	if ( ! function_exists( 'apollo_card_register' ) ) {
		return; // apollo-core absent or too old — degrade silently.
	}
	/*
	 * One NAMED renderer per style, not one shared callback.
	 * apollo_card_render() invokes the renderer as ($post_id, $args) and does
	 * not pass the type back, so a single shared function could not tell
	 * type-1 from type-4. Named wrappers keep it explicit and debuggable;
	 * they are generated in includes/render.php.
	 */
	foreach ( array_keys( apollo_ui_types() ) as $key ) {
		$fn = 'apollo_ui_render_' . str_replace( '-', '_', $key );
		if ( ! function_exists( $fn ) ) {
			continue;
		}
		apollo_card_register(
			$key,
			array(
				'renderer'  => $fn,
				'styles'    => 'apollo_ui_card_styles',
				'post_type' => '',                    // a style, not a post type
				'owner'     => 'apollo-ui',
				'variants'  => array( 'default', 'grid', 'rail', 'compact' ),
			)
		);
	}
}
add_action( 'init', 'apollo_ui_boot', 20 );
