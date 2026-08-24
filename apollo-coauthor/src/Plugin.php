<?php
/**
 * Apollo CoAuthor — Main Plugin Class (Singleton).
 *
 * Orchestrates all components: taxonomy fallback, metabox, REST, shortcodes,
 * query integration, and content filters.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin singleton.
 *
 * @since 1.0.0
 */
final class Plugin {

	/** @var Plugin|null Singleton instance. */
	private static ?Plugin $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use get_instance().
	 */
	private function __construct() {}

	/**
	 * Initialize the plugin and all components.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		// Register assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Initialize components.
		$this->init_components();
	}

	/**
	 * Boot all feature components.
	 *
	 * @since 1.0.0
	 */
	private function init_components(): void {
		new Components\Taxonomy();
		new Components\MetaBox();
		new Components\Shortcodes();
		new Components\QueryIntegration();
		new Components\ContentFilter();

		// v1.1.0 — Adapted from Co-Authors Plus.
		new Components\GuestAuthors();
		new Components\NotifyCoauthors();
		new Components\BulkEdit();
		new Components\UserPostCount();
		// CoauthorIterator is instantiated on-demand in templates, not here.
	}

	/**
	 * Register REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes(): void {
		$controller = new API\CoauthorController();
		$controller->register_routes();
	}

	/**
	 * Enqueue admin scripts and styles for the metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, apollo_coauthor_get_supported_post_types(), true ) ) {
			return;
		}

		// Select2.
		wp_enqueue_style(
			'select2',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
			array(),
			'4.1.0'
		);
		wp_enqueue_script(
			'select2',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
			array( 'jquery' ),
			'4.1.0',
			true
		);

		// Plugin admin script.
		wp_enqueue_script(
			'apollo-coauthor-admin',
			APOLLO_COAUTHOR_URL . 'assets/js/admin-metabox.js',
			array( 'jquery', 'select2', 'jquery-ui-sortable' ),
			APOLLO_COAUTHOR_VERSION,
			true
		);

		wp_localize_script(
			'apollo-coauthor-admin',
			'apolloCoauthor',
			array(
				'restUrl'   => rest_url( APOLLO_COAUTHOR_REST_NAMESPACE . '/coauthors' ),
				'searchUrl' => rest_url( APOLLO_COAUTHOR_REST_NAMESPACE . '/coauthors/search' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'i18n'      => array(
					'search'      => __( 'Buscar usuários...', 'apollo-coauthor' ),
					'noResults'   => __( 'Nenhum usuário encontrado', 'apollo-coauthor' ),
					'addCoauthor' => __( 'Adicionar Co-Autor', 'apollo-coauthor' ),
					'remove'      => __( 'Remover', 'apollo-coauthor' ),
				),
			)
		);

		// Inline admin CSS.
		wp_add_inline_style(
			'select2',
			'
			.apollo-coauthor-list { list-style: none; margin: 0; padding: 0; }
			.apollo-coauthor-list li {
				display: flex; align-items: center; gap: 8px;
				padding: 6px 8px; margin: 4px 0; background: #f9f9f9;
				border: 1px solid #ddd; border-radius: 4px; cursor: grab;
			}
			.apollo-coauthor-list li:hover { background: #fff3e6; border-color: FF9820; }
			.apollo-coauthor-list li img { border-radius: 50%; width: 28px; height: 28px; }
			.apollo-coauthor-list .remove-coauthor {
				margin-left: auto; color: #b32d2e; cursor: pointer;
				background: none; border: none; font-size: 16px; line-height: 1;
			}
			.apollo-coauthor-list .remove-coauthor:hover { color: #dc3232; }
			.apollo-coauthor-list .drag-handle { cursor: grab; color: #999; }
			#apollo-coauthor-select { width: 100%; margin-top: 8px; }
			'
		);
	}
}
