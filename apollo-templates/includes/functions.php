<?php
/**
 * Helper Functions — Apollo Template Engine
 *
 * Architecture derived from open-source reference analysis:
 *   - UsersWP:            locate_template hierarchy + extract($args) + hooks
 *   - Shortcodes Ultimate: ob_start + load_template + on-demand asset enqueue
 *   - Bright Nucleus:      theme override via Gamajo_Template_Loader
 *
 * Result: 3 core functions that power every shortcode in the Apollo ecosystem.
 *
 * @package Apollo\Templates
 */

declare(strict_types=1);

namespace Apollo\Templates;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
═══════════════════════════════════════════════════════════════════════════
	CORE TEMPLATE ENGINE — 3 FUNCTIONS
	═══════════════════════════════════════════════════════════════════════════ */

/**
 * Locate a template file with theme-override support.
 *
 * Search order:
 *   1. {child-theme}/apollo-templates/{template_name}
 *   2. {child-theme}/apollo/{template_name}
 *   3. {parent-theme}/apollo-templates/{template_name}
 *   4. {parent-theme}/apollo/{template_name}
 *   5. {calling-plugin}/templates/{template_name}   (via $plugin_dir)
 *   6. apollo-templates/templates/{template_name}    (this plugin)
 *
 * @param string $template_name  Relative path inside templates/ (e.g. 'event/card-style-01.php').
 * @param string $plugin_dir     Absolute path to a calling plugin's templates dir (optional fallback).
 * @return string                Resolved absolute path, or empty string.
 */
function apollo_locate_template( string $template_name, string $plugin_dir = '' ): string {

	// 1–4. Try theme directories (child-theme first, parent-theme second).
	$theme_paths = array(
		'apollo-templates/' . $template_name,
		'apollo/' . $template_name,
	);

	$located = locate_template( $theme_paths ); // WP native — searches child+parent.

	// 5. Calling plugin's own templates dir.
	if ( empty( $located ) && ! empty( $plugin_dir ) && file_exists( $plugin_dir . $template_name ) ) {
		$located = $plugin_dir . $template_name;
	}

	// 6. This plugin's templates dir (final fallback).
	if ( empty( $located ) && file_exists( APOLLO_TEMPLATES_DIR . 'templates/' . $template_name ) ) {
		$located = APOLLO_TEMPLATES_DIR . 'templates/' . $template_name;
	}

	/**
	 * Filter the located template path.
	 *
	 * @param string $located       Resolved path.
	 * @param string $template_name Requested template name.
	 * @param string $plugin_dir    Plugin templates directory.
	 */
	return (string) apply_filters( 'apollo_locate_template', $located, $template_name, $plugin_dir );
}

/**
 * Load a template file, injecting variables via extract().
 *
 * @param string $template_name Relative template path.
 * @param array  $args          Variables to make available inside the template.
 * @param string $plugin_dir    Calling plugin templates dir (optional).
 * @return void
 */
function apollo_get_template( string $template_name, array $args = array(), string $plugin_dir = '' ): void {
	$located = apollo_locate_template( $template_name, $plugin_dir );

	if ( empty( $located ) ) {
		return;
	}

	/** @hook apollo_before_template_part — fires before include */
	do_action( 'apollo_before_template_part', $template_name, $located, $args );

	if ( ! empty( $args ) && is_array( $args ) ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract — intentional, scoped
		extract( $args, EXTR_SKIP );
	}

	include $located;

	/** @hook apollo_after_template_part — fires after include */
	do_action( 'apollo_after_template_part', $template_name, $located, $args );
}

/**
 * Return rendered HTML of a template (the function shortcodes call).
 *
 * @param string $template_name Relative template path.
 * @param array  $args          Variables to pass.
 * @param string $plugin_dir    Calling plugin templates dir (optional).
 * @return string               Rendered HTML.
 */
function apollo_get_template_html( string $template_name, array $args = array(), string $plugin_dir = '' ): string {
	ob_start();
	apollo_get_template( $template_name, $args, $plugin_dir );
	return (string) ob_get_clean();
}

/*
═══════════════════════════════════════════════════════════════════════════
	LEGACY HELPERS  (kept for backward-compat)
	═══════════════════════════════════════════════════════════════════════════ */

/**
 * Get template path (legacy — use apollo_locate_template instead).
 *
 * @param string $template Template name.
 * @return string|false
 */
function get_template_path( string $template ): string|false {
	$path = APOLLO_TEMPLATES_DIR . 'templates/' . $template;

	if ( file_exists( $path ) ) {
		return $path;
	}
	if ( file_exists( $path . '.php' ) ) {
		return $path . '.php';
	}
	if ( file_exists( $path . '.html' ) ) {
		return $path . '.html';
	}

	return false;
}

/**
 * Load a template (legacy — use apollo_get_template instead).
 *
 * @param string $template Template name.
 * @param array  $args     Arguments.
 * @return void
 */
function load_template( string $template, array $args = array() ): void {
	$path = get_template_path( $template );

	if ( $path ) {
		extract( $args, EXTR_SKIP );
		include $path;
	}
}

/**
 * Get all available templates.
 *
 * @return array
 */
function get_available_templates(): array {
	$templates_dir = APOLLO_TEMPLATES_DIR . 'templates/';
	$templates     = array();

	if ( is_dir( $templates_dir ) ) {
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $templates_dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $iterator as $file ) {
			if ( in_array( $file->getExtension(), array( 'php', 'html' ), true ) ) {
				$relative    = str_replace( $templates_dir, '', $file->getPathname() );
				$templates[] = str_replace( '\\', '/', $relative );
			}
		}
	}

	sort( $templates );
	return $templates;
}
