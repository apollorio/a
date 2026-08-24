<?php

/**
 * WordPress Hardening
 *
 * Removes WordPress version/path disclosure vectors:
 * - WP Generator meta tag
 * - Version query strings on CSS/JS
 * - RSD (Really Simple Discovery) links
 * - WLW Manifest links
 * - oEmbed / Embed scripts
 * - DNS prefetch link
 * - atom+xml meta
 * - XML-RPC access
 * - WP-login page language switcher meta
 * - readme.html, license.txt, wp-config paths
 *
 * @package Apollo\Login\Security
 */

declare(strict_types=1);

namespace Apollo\Login\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPHardening class
 */
class WPHardening {


	/**
	 * Constructor
	 */
	public function __construct() {
		// ── Meta tags / header links ─────────────────────────────────────────
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_resource_hints', 2 );

		// ── Removed from template redirect and REST ──────────────────────────
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'template_redirect', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );

		// ── Atom feed meta ───────────────────────────────────────────────────
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );

		// ── Version from URLs ────────────────────────────────────────────────
		add_filter( 'style_loader_src', array( $this, 'remove_version_from_url' ), 10, 2 );
		add_filter( 'script_loader_src', array( $this, 'remove_version_from_url' ), 10, 2 );

		// Keep WordPress feeds but remove version from meta
		add_filter( 'the_generator', '__return_empty_string' );

		// ── XML-RPC ──────────────────────────────────────────────────────────
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_xmlrpc_server_class', '__return_false' );
		add_action( 'init', array( $this, 'block_xmlrpc_requests' ) );

		// ── Embeds / oEmbed ──────────────────────────────────────────────────
		add_action( 'init', array( $this, 'disable_embeds' ) );

		// ── Disable DB Debug in frontend ─────────────────────────────────────
		add_action( 'init', array( $this, 'disable_db_debug_frontend' ) );

		// ── Block access to sensitive files ──────────────────────────────────
		add_action( 'init', array( $this, 'block_sensitive_files' ) );

		// ── Login page: remove language switcher ─────────────────────────────
		add_filter( 'login_display_language_dropdown', '__return_false' );

		// ── Disable WLW Manifest in headers (in addition to head) ────────────
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}

	/**
	 * Remove ?ver= from CSS/JS URLs so the WordPress version isn't revealed
	 *
	 * @param string $src    Asset URL.
	 * @param string $handle Asset handle.
	 * @return string
	 */
	public function remove_version_from_url( string $src, string $handle ): string {
		if ( str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	/**
	 * Block direct XML-RPC POST requests with 403
	 *
	 * @return void
	 */
	public function block_xmlrpc_requests(): void {
		$path = $_SERVER['REQUEST_URI'] ?? '';

		if ( str_contains( $path, '/xmlrpc.php' ) ) {
			status_header( 403 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo 'XML-RPC disabled.';
			exit;
		}
	}

	/**
	 * Disable oEmbed embed scripts and actions
	 *
	 * @return void
	 */
	public function disable_embeds(): void {
		global $wp;

		// Remove embed query var
		if ( isset( $wp->public_query_vars ) ) {
			$wp->public_query_vars = array_diff(
				$wp->public_query_vars,
				array( 'embed' )
			);
		}

		// Remove embed-related actions
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'rewrite_rules_array', array( $this, 'disable_embed_rewrite' ) );
	}

	/**
	 * Remove embed rewrite rules
	 *
	 * @param array $rules
	 * @return array
	 */
	public function disable_embed_rewrite( array $rules ): array {
		foreach ( $rules as $rule => $rewrite ) {
			if ( str_contains( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}
		return $rules;
	}

	/**
	 * Disable WP_DEBUG DB errors in frontend (prevent data leakage)
	 *
	 * @return void
	 */
	public function disable_db_debug_frontend(): void {
		if ( ! is_admin() ) {
			global $wpdb;
			if ( isset( $wpdb ) ) {
				$wpdb->show_errors( false );
				$wpdb->suppress_errors( true );
			}
		}
	}

	/**
	 * Block direct HTTP access to sensitive WordPress files
	 *
	 * @return void
	 */
	public function block_sensitive_files(): void {
		$uri = $_SERVER['REQUEST_URI'] ?? '';

		$blocked_files = array(
			'readme.html',
			'readme.txt',
			'license.txt',
			'wp-config.php',
			'wp-config-sample.php',
			'wp-load.php',
			'wp-settings.php',
			'wp-blog-header.php',
			'install.php',
			'php.ini',
			'debug.log',
			'error_log',
		);

		$path = strtolower( basename( parse_url( $uri, PHP_URL_PATH ) ?? '' ) );

		if ( in_array( $path, $blocked_files, true ) ) {
			status_header( 404 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo 'Not found.';
			exit;
		}
	}
}
