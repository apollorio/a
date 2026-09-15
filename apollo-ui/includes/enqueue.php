<?php
/**
 * Apollo UI — assets.
 *
 * WHY THIS IS NOT JUST wp_enqueue_scripts
 *
 * Apollo's blank-canvas templates DO NOT CALL wp_head(). Proven against the
 * live site: /eventos returns no `wp-emoji`, no `wp-includes/js`, no
 * `dns-prefetch`, no `generator` — none of wp_head()'s output — and
 * apollo-core/includes/document-head.php contains no wp_head() call at all.
 * It prints a curated head instead.
 *
 * So a plugin that only calls wp_enqueue_style() emits NOTHING on /casa,
 * /eventos, /anuncios, /hub … which is every screen that matters. apollo-ui
 * shipped exactly that mistake and printed zero bytes on /eventos.
 *
 * The real extension points, published by document-head.php:
 *   apollo/canvas/head           :185   inside <head>, every blank canvas
 *   apollo/canvas/before_close   :287   before </body>, every blank canvas
 *   apollo/plus/before_close     apollo-plus-api.php:165, the Apollo+ shell
 *
 * The house pattern is apollo-templates/includes/mobile-runtime.php:48 —
 * a `static $done` ledger plus the same callback on several hooks. That is why
 * mobile-premium.css is one of only two stylesheets that load on /eventos.
 * This file follows it.
 *
 * Double-printing is prevented two ways: the static ledgers below, and
 * wp_style_is()/wp_script_is( …, 'done' ) so a CLASSIC page that really did run
 * wp_head() never gets a second raw <link>.
 *
 * @package Apollo\UI
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Should apollo-ui emit on this request? */
function apollo_ui_assets_enabled(): bool {
	$s = apollo_ui_settings();
	if ( empty( $s['enabled'] ) ) {
		return false;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	return (bool) apply_filters( 'apollo/ui/enabled', true );
}

/** Absolute URL of an asset, cache-busted by plugin version. */
function apollo_ui_asset( string $rel ): string {
	return APOLLO_UI_URL . ltrim( $rel, '/' ) . '?ver=' . rawurlencode( APOLLO_UI_VERSION );
}

/**
 * The boot payload handed to the browser.
 *
 * @return array<string,mixed>
 */
function apollo_ui_boot_data(): array {
	return array(
		'surfaces' => apollo_ui_surfaces(),
		'types'    => array_keys( apollo_ui_types() ),
		'strings'  => array(
			'close'   => __( 'Fechar', 'apollo-ui' ),
			'loading' => __( 'Carregando…', 'apollo-ui' ),
			'error'   => __( 'Não foi possível carregar.', 'apollo-ui' ),
		),
	);
}

/* ── classic path: pages that really do run wp_head() ─────────────────── */

function apollo_ui_enqueue(): void {
	if ( ! apollo_ui_assets_enabled() ) {
		return;
	}
	wp_enqueue_style( 'apollo-ui', APOLLO_UI_URL . 'assets/css/apollo-ui.css', array(), APOLLO_UI_VERSION );

	if ( empty( apollo_ui_settings()['lightbox'] ) ) {
		return;
	}
	wp_enqueue_script( 'apollo-ui', APOLLO_UI_URL . 'assets/js/apollo-ui.js', array(), APOLLO_UI_VERSION, true );
	wp_add_inline_script(
		'apollo-ui',
		'window.APOLLO_UI = ' . wp_json_encode( apollo_ui_boot_data() ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'apollo_ui_enqueue', 20 );

/* ── blank-canvas path: where wp_head() never runs ────────────────────── */

/**
 * Print the stylesheet once per document.
 *
 * Skipped when WordPress already printed the handle, so a classic page cannot
 * end up with two <link> tags for the same file.
 */
function apollo_ui_emit_head(): void {
	static $done = false;
	if ( $done || ! apollo_ui_assets_enabled() ) {
		return;
	}
	if ( function_exists( 'wp_style_is' ) && wp_style_is( 'apollo-ui', 'done' ) ) {
		$done = true;
		return;
	}
	$done = true;
	printf(
		'<link rel="stylesheet" id="apollo-ui-css" href="%s" media="all">' . "\n",
		esc_url( apollo_ui_asset( 'assets/css/apollo-ui.css' ) )
	);
}
add_action( 'apollo/canvas/head', 'apollo_ui_emit_head', 20 );
add_action( 'wp_head', 'apollo_ui_emit_head', 20 );

/**
 * Print the runtime once per document.
 *
 * The boot JSON goes inline before the script so the runtime never has to guess
 * a REST path. apollo-core's CSP nonce helper is used when present — the site
 * sends a content-security-policy header, and an un-nonced inline script is
 * silently dropped by the browser with no error in PHP.
 */
function apollo_ui_emit_footer(): void {
	static $done = false;
	if ( $done || ! apollo_ui_assets_enabled() ) {
		return;
	}
	if ( empty( apollo_ui_settings()['lightbox'] ) ) {
		return;
	}
	if ( function_exists( 'wp_script_is' ) && wp_script_is( 'apollo-ui', 'done' ) ) {
		$done = true;
		return;
	}
	$done = true;

	$nonce = function_exists( 'apollo_csp_nonce_attr' ) ? (string) apollo_csp_nonce_attr() : '';

	printf(
		'<script%s>window.APOLLO_UI = %s;</script>' . "\n",
		$nonce, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attribute built by apollo-core.
		wp_json_encode( apollo_ui_boot_data() )
	);
	printf(
		'<script%s src="%s" defer></script>' . "\n",
		$nonce, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attribute built by apollo-core.
		esc_url( apollo_ui_asset( 'assets/js/apollo-ui.js' ) )
	);
}
add_action( 'apollo/canvas/before_close', 'apollo_ui_emit_footer', 20 );
add_action( 'apollo/plus/before_close', 'apollo_ui_emit_footer', 20 );
add_action( 'wp_footer', 'apollo_ui_emit_footer', 20 );

/** Manual escape hatch, mirroring apollo/mobile/emit. */
add_action( 'apollo/ui/emit', 'apollo_ui_emit_head', 10 );
add_action( 'apollo/ui/emit', 'apollo_ui_emit_footer', 11 );

/* ── admin ────────────────────────────────────────────────────────────── */

function apollo_ui_admin_enqueue( string $hook ): void {
	if ( false === strpos( $hook, 'apollo-ui' ) ) {
		return;
	}
	wp_enqueue_style( 'apollo-ui-admin', APOLLO_UI_URL . 'assets/css/apollo-ui.css', array(), APOLLO_UI_VERSION );
}
add_action( 'admin_enqueue_scripts', 'apollo_ui_admin_enqueue' );
