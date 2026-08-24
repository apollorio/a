<?php
/**
 * URL Rewriter - WP Hide Ghost Security (NO CORE OVERWRITE)
 *
 * Hides wp-admin and wp-login.php showing 404 errors
 * Official login: /acesso
 * Smart protection without touching WordPress core files
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\Security;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL Rewriter class - WP Hide Ghost
 */
class URLRewriter {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Block wp-login.php and wp-admin (404) — after auth cookies are loaded.
		// Earliest possible priority so nothing else on wp_loaded can run first.
		add_action( 'wp_loaded', array( $this, 'ghost_mode_protection' ), PHP_INT_MIN );

		// Hide admin URLs in HTML output
		add_filter( 'site_url', array( $this, 'hide_admin_urls' ), 10, 4 );
		add_filter( 'admin_url', array( $this, 'hide_admin_urls' ), 10, 4 );
		add_filter( 'wp_redirect', array( $this, 'hide_admin_redirects' ), 10, 1 );

		// Remove wp-login hints
		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
	}

	/**
	 * Ghost Mode Protection - Show 404 for wp-login.php and wp-admin
	 * Allows access ONLY through /acesso
	 * ONLY ADMINISTRATORS can access wp-admin/wp-login when logged in
	 *
	 * @return void
	 */
	public function ghost_mode_protection(): void {
		global $pagenow;

		// LocalWP / dev: never hide wp-admin (statistics tests, plugin config).
		if (
			( defined( 'APOLLO_DEV_MODE' ) && APOLLO_DEV_MODE )
			|| ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() )
		) {
			return;
		}

		// Allow AJAX requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Allow REST API
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Allow cron
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

		// === PROTECTION 1: Block wp-login.php ===
		// Allow only administrators
		if ( 'wp-login.php' === $pagenow && ! $this->is_admin_user() && ! $this->is_allowed_login_action() ) {
			$this->show_404();
		}

		// === PROTECTION 2: Block /wp-admin/ ===
		// Allow only administrators
		if ( strpos( $request_uri, '/wp-admin' ) !== false && ! $this->is_admin_user() ) {
			$this->show_404();
		}

		// === PROTECTION 3: Block direct wp-login.php file access ===
		// Allow only administrators
		if ( strpos( $script_name, 'wp-login.php' ) !== false && ! $this->is_admin_user() && ! $this->is_allowed_login_action() ) {
			$this->show_404();
		}

		// === PROTECTION 4: Redirect logged users trying wp-login to /acesso ===
		// But allow administrators to access wp-login.php
		if ( 'wp-login.php' === $pagenow && is_user_logged_in() && ! $this->is_admin_user() && ! $this->is_allowed_login_action() ) {
			wp_safe_redirect( trailingslashit( home_url( '/' . trim( (string) \APOLLO_LOGIN_CUSTOM_LOGIN_SLUG, '/' ) ) ) );
			exit;
		}
	}

	/**
	 * Check if login action is allowed (logout, verify email, etc)
	 *
	 * @return bool
	 */
	private function is_allowed_login_action(): bool {
		$action         = $_GET['action'] ?? '';
		$request_method = $_SERVER['REQUEST_METHOD'] ?? '';

		// Allow specific GET actions that WP needs
		$allowed_actions = array(
			'logout',
			'rp',          // Reset password
			'resetpass',
			'lostpassword',
			'retrievepassword',
			'postpass',    // Password protected posts
		);

		// Allow POST requests for login authentication (from our custom login form)
		if ( $request_method === 'POST' && isset( $_POST['log'] ) && isset( $_POST['pwd'] ) ) {
			return true;
		}

		return in_array( $action, $allowed_actions, true );
	}

	/**
	 * Show 404 error page (NOT redirect - real 404)
	 *
	 * @return void
	 */
	private function show_404(): void {
		global $wp_query;

		status_header( 404 );
		nocache_headers();

		if ( $wp_query ) {
			$wp_query->set_404();
		}

		// Load 404 template if available
		if ( file_exists( get_template_directory() . '/404.php' ) ) {
			include get_template_directory() . '/404.php';
		} else {
			// Fallback 404 HTML
			?>
			<!DOCTYPE html>
			<html>
			<head>
				<meta charset="UTF-8">
				<title>404 Not Found</title>
				<meta name="robots" content="noindex,nofollow">
			</head>
			<body>
				<h1>404</h1>
				<p>Page not found.</p>
			</body>
			</html>
			<?php
		}

		exit;
	}

	/**
	 * Hide admin URLs in site_url() and admin_url() outputs
	 *
	 * @param string      $url     URL.
	 * @param string      $path    Path.
	 * @param string|null $scheme  Scheme.
	 * @param int|null    $blog_id Blog ID.
	 * @return string
	 */
	public function hide_admin_urls( string $url, string $path = '', $scheme = null, $blog_id = null ): string {
		// Don't hide for logged-in users in admin
		if ( is_admin() && is_user_logged_in() ) {
			return $url;
		}

		// Replace wp-login.php with /acesso
		if ( strpos( $url, 'wp-login.php' ) !== false ) {
			$url = str_replace( 'wp-login.php', (string) \APOLLO_LOGIN_CUSTOM_LOGIN_SLUG, $url );
		}

		return $url;
	}

	/**
	 * Hide admin URLs in wp_redirect()
	 *
	 * @param string $location Redirect location.
	 * @return string
	 */
	public function hide_admin_redirects( ?string $location ): ?string {
		if (null === $location || '' === $location) {
			return $location;
		}
		// Replace wp-login.php in redirects
		if ( strpos( $location, 'wp-login.php' ) !== false && ! is_user_logged_in() ) {
			$location = str_replace( 'wp-login.php', (string) \APOLLO_LOGIN_CUSTOM_LOGIN_SLUG, $location );
		}

		return $location;
	}

	/**
	 * Get custom login slug
	 *
	 * @return string
	 */
	public static function get_custom_login_slug(): string {
		return (string) \APOLLO_LOGIN_CUSTOM_LOGIN_SLUG;
	}

	/**
	 * Check if current user is an administrator.
	 * Uses manage_options (a real capability) rather than the role name
	 * 'administrator', which is unreliable on multi-site and in some timing contexts.
	 *
	 * @return bool
	 */
	private function is_admin_user(): bool {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}
}
