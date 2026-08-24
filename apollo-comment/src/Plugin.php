<?php

/**
 * Plugin orchestrator — singleton that wires every subsystem.
 *
 * @package Apollo\Comment
 */

namespace Apollo\Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {


	/** @var self|null */
	private static ?self $instance = null;

	/**
	 * Singleton accessor.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire everything.
	 */
	private function __construct() {
		// 1. Relabel "Comment" → "Depoimento" globally
		CommentLabels::init();

		// 2. Depoimento rendering + query helpers
		Depoimento::init();

		// 3. Shortcodes
		Shortcodes::init();

		// 4. REST API
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// 5. Enqueue frontend assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// 6. Frontend depoimento form (panel-forms.php hook)
		new FrontendForm();

		// 7. Fire ecosystem hook
		do_action( 'apollo/comment/initialized', $this );
	}

	/**
	 * Register REST routes.
	 */
	public function register_rest_routes(): void {
		$controller = new API\DepoimentoController();
		$controller->register_routes();
	}

	/**
	 * Enqueue frontend CSS (only when needed).
	 */
	public function enqueue_assets(): void {
		if ( ! is_singular() && ! has_shortcode( get_post()->post_content ?? '', 'apollo_depoimentos' ) ) {
			// Only load on singular or pages with shortcode
			if ( ! is_singular() ) {
				return;
			}
		}

		wp_enqueue_style(
			'apollo-depoimento',
			APOLLO_COMMENT_URL . 'assets/css/depoimento.css',
			array(),
			APOLLO_COMMENT_VERSION
		);

		wp_enqueue_script(
			'apollo-depoimento',
			APOLLO_COMMENT_URL . 'assets/js/depoimento.js',
			array(),
			APOLLO_COMMENT_VERSION,
			true
		);

		wp_localize_script(
			'apollo-depoimento',
			'apolloDepoimento',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'rest_url'  => rest_url( 'apollo/v1/depoimentos' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'logged_in' => is_user_logged_in(),
				'i18n'      => array(
					'submit'       => 'Enviar Depoimento',
					'placeholder'  => 'Deixe seu depoimento…',
					'login_needed' => 'Faça login para deixar um depoimento.',
					'success'      => 'Depoimento enviado!',
					'error'        => 'Erro ao enviar depoimento.',
				),
			)
		);
	}

	/**
	 * Resolve template path (allows theme overrides).
	 */
	public static function template_path( string $name ): string {
		$theme_path = get_stylesheet_directory() . '/apollo-comment/' . $name;
		if ( file_exists( $theme_path ) ) {
			return $theme_path;
		}
		return APOLLO_COMMENT_PATH . 'templates/' . $name;
	}

	private function __clone() {}
	public function __wakeup() {}
}
