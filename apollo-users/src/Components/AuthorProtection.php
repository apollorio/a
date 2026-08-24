<?php
/**
 * Author Protection Component
 *
 * Blocks WordPress native ?author=X enumeration and redirects to /id/username
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users\Components;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Author Protection class
 */
class AuthorProtection {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Block author enumeration via ?author=X
		add_action( 'template_redirect', array( $this, 'block_author_enumeration' ), 1 );

		// Remove author from REST API for non-admins
		add_filter( 'rest_endpoints', array( $this, 'restrict_user_endpoints' ) );

		// Disable author archives
		add_action( 'template_redirect', array( $this, 'redirect_author_archives' ) );

		// Remove author info from feeds
		add_filter( 'the_author', array( $this, 'hide_author_in_feed' ) );

		// Block username discovery via login errors
		add_filter( 'login_errors', array( $this, 'obscure_login_errors' ) );

		// Remove author sitemap
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'remove_author_sitemap' ), 10, 2 );
	}

	/**
	 * Block ?author=X enumeration
	 *
	 * @return void
	 */
	public function block_author_enumeration(): void {
		// Check for author parameter in URL
		if ( isset( $_GET['author'] ) || isset( $_GET['author_name'] ) ) {
			$author_id = isset( $_GET['author'] ) ? absint( $_GET['author'] ) : 0;

			if ( $author_id > 0 ) {
				$user = get_user_by( 'ID', $author_id );
			} elseif ( isset( $_GET['author_name'] ) ) {
				$user = get_user_by( 'slug', sanitize_user( $_GET['author_name'] ) );
			}

			// Redirect to Apollo profile or 404
			if ( $user && function_exists( 'apollo_get_profile_url' ) ) {
				wp_safe_redirect( apollo_get_profile_url( $user ), 301 );
				exit;
			}

			// Return 404 if user not found or function unavailable
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return;
		}
	}

	/**
	 * Redirect author archives to Apollo profiles
	 *
	 * @return void
	 */
	public function redirect_author_archives(): void {
		if ( is_author() ) {
			$author = get_queried_object();

			if ( $author instanceof \WP_User && function_exists( 'apollo_get_profile_url' ) ) {
				wp_safe_redirect( apollo_get_profile_url( $author ), 301 );
				exit;
			}

			// Fallback to home
			wp_safe_redirect( home_url(), 302 );
			exit;
		}
	}

	/**
	 * Restrict user REST endpoints for non-admins
	 *
	 * @param array $endpoints REST endpoints.
	 * @return array
	 */
	public function restrict_user_endpoints( array $endpoints ): array {
		// Only restrict for non-authenticated or non-admin users
		if ( ! is_user_logged_in() || ! current_user_can( 'list_users' ) ) {
			// Remove user listing endpoint
			if ( isset( $endpoints['/wp/v2/users'] ) ) {
				unset( $endpoints['/wp/v2/users'] );
			}

			// Keep single user endpoint but we'll filter it elsewhere
			// This allows profile lookups by logged-in users
		}

		return $endpoints;
	}

	/**
	 * Hide author name in RSS feeds
	 *
	 * @param string $author Author display name.
	 * @return string
	 */
	public function hide_author_in_feed( string $author ): string {
		if ( is_feed() ) {
			// Return site name instead of author
			return get_bloginfo( 'name' );
		}

		return $author;
	}

	/**
	 * Obscure login error messages
	 *
	 * @param string $error Error message.
	 * @return string
	 */
	public function obscure_login_errors( string $error ): string {
		global $errors;

		// List of error codes that reveal usernames
		$sensitive_codes = array(
			'invalid_username',
			'incorrect_password',
			'invalid_email',
		);

		if ( is_wp_error( $errors ) ) {
			foreach ( $sensitive_codes as $code ) {
				if ( $errors->get_error_message( $code ) ) {
					return __( 'Credenciais incorretas. Verifique seu usuário e senha.' );
				}
			}
		}

		// Generic message for any login error
		return __( 'Erro ao fazer login. Verifique seus dados.' );
	}

	/**
	 * Remove author from sitemap
	 *
	 * @param \WP_Sitemaps_Provider $provider Sitemap provider.
	 * @param string                $name     Provider name.
	 * @return \WP_Sitemaps_Provider|false
	 */
	public function remove_author_sitemap( $provider, string $name ) {
		if ( 'users' === $name ) {
			return false;
		}

		return $provider;
	}

	/**
	 * Get protected routes that bypass author protection
	 *
	 * @return array
	 */
	public static function get_allowed_routes(): array {
		return apply_filters(
			'apollo_author_protection_allowed_routes',
			array(
				'apollo/v1/users/me',
				'apollo/v1/users/profile',
			)
		);
	}
}
