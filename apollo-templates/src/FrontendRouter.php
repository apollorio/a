<?php
/**
 * Frontend Router — Virtual edit pages for all CPTs
 *
 * Registers rewrite rules and intercepts template_redirect to serve
 * the frontend editing canvas for any Apollo CPT.
 *
 * URL pattern: /editar/{cpt}/{post_id}/  → loads edit-post.php template
 *   Example:   /editar/dj/42/
 *              /editar/loc/18/
 *
 * Also supports: /{cpt_slug}/{post_slug}/editar/ (optional, via filter)
 *
 * Security: requires login + permission check via FrontendEditor::can_edit()
 *
 * @package Apollo\Templates
 */

declare(strict_types=1);

namespace Apollo\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Router for edit pages
 */
final class FrontendRouter {

	/**
	 * Singleton instance
	 *
	 * @var FrontendRouter|null
	 */
	private static ?FrontendRouter $instance = null;

	/**
	 * Get instance
	 *
	 * @return FrontendRouter
	 */
	public static function get_instance(): FrontendRouter {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_rewrite_rules' ), 20 );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_edit_page' ), 5 );
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		REWRITE RULES
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Register rewrite rules for edit pages.
	 *
	 * Pattern: /editar/{cpt}/{post_id}/
	 *
	 * @return void
	 */
	public function register_rewrite_rules(): void {
		// Get CPTs that support frontend editing
		$post_types = $this->get_editable_post_types();

		if ( empty( $post_types ) ) {
			return;
		}

		$cpt_regex = implode( '|', array_map( 'preg_quote', $post_types ) );

		// /editar/{cpt}/{post_id}/
		add_rewrite_rule(
			'^editar/(' . $cpt_regex . ')/([0-9]+)/?$',
			'index.php?apollo_edit_cpt=$matches[1]&apollo_edit_id=$matches[2]',
			'top'
		);

		// /editar/{cpt}/ (create mode — new post)
		add_rewrite_rule(
			'^editar/(' . $cpt_regex . ')/?$',
			'index.php?apollo_edit_cpt=$matches[1]&apollo_edit_id=0',
			'top'
		);
	}

	/**
	 * Get post types that support frontend editing.
	 *
	 * Plugins register via filter:
	 *   add_filter( 'apollo_editable_post_types', function( $types ) {
	 *       $types[] = 'dj';
	 *       return $types;
	 *   });
	 *
	 * @return array<string>
	 */
	private function get_editable_post_types(): array {
		/**
		 * Filter: apollo_editable_post_types
		 *
		 * @param array $post_types Default empty array.
		 */
		return apply_filters( 'apollo_editable_post_types', array() );
	}

	/**
	 * Register custom query vars.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'apollo_edit_cpt';
		$vars[] = 'apollo_edit_id';
		return $vars;
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		TEMPLATE REDIRECT
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Handle the edit page request.
	 *
	 * @return void
	 */
	public function handle_edit_page(): void {
		$post_type = get_query_var( 'apollo_edit_cpt', '' );
		$post_id   = (int) get_query_var( 'apollo_edit_id', 0 );

		if ( empty( $post_type ) ) {
			return;
		}

		// Must be logged in
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( home_url( '/editar/' . $post_type . '/' . $post_id . '/' ) ) );
			exit;
		}

		$editor = FrontendEditor::get_instance();

		// Create mode: auto-create draft post
		if ( $post_id === 0 ) {
			$post_id = $this->maybe_create_draft( $post_type );

			if ( ! $post_id ) {
				wp_die(
					esc_html__( 'Não foi possível criar o rascunho.', 'apollo-templates' ),
					esc_html__( 'Erro', 'apollo-templates' ),
					array( 'response' => 500 )
				);
			}

			// Redirect to edit URL with the new post ID
			wp_safe_redirect( home_url( '/editar/' . $post_type . '/' . $post_id . '/' ) );
			exit;
		}

		// Validate post exists and matches CPT
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== $post_type ) {
			wp_die(
				esc_html__( 'Post não encontrado.', 'apollo-templates' ),
				esc_html__( 'Não encontrado', 'apollo-templates' ),
				array( 'response' => 404 )
			);
		}

		// Permission check
		if ( ! $editor->can_edit( $post_id ) ) {
			wp_die(
				esc_html__( 'Você não tem permissão para editar este conteúdo.', 'apollo-templates' ),
				esc_html__( 'Acesso negado', 'apollo-templates' ),
				array( 'response' => 403 )
			);
		}

		// Load the edit template
		$this->load_edit_template( $post, $post_type );
		exit;
	}

	/**
	 * Create a draft post for the current user.
	 *
	 * @param string $post_type CPT slug.
	 * @return int|false Post ID or false on failure.
	 */
	private function maybe_create_draft( string $post_type ): int|false {
		// Check if user can create posts of this type
		$post_type_obj = get_post_type_object( $post_type );
		if ( ! $post_type_obj ) {
			return false;
		}

		// Permission check via filter
		$can_create = apply_filters(
			"apollo_editor_can_create_{$post_type}",
			current_user_can( $post_type_obj->cap->create_posts ),
			get_current_user_id()
		);

		if ( ! $can_create ) {
			return false;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => $post_type,
				'post_status' => 'draft',
				'post_author' => get_current_user_id(),
				'post_title'  => __( 'Novo rascunho', 'apollo-templates' ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Link user to post via meta
		$user_id_key = "_{$post_type}_user_id";
		update_post_meta( $post_id, $user_id_key, get_current_user_id() );

		/**
		 * Action: apollo_editor_draft_created_{cpt}
		 *
		 * @param int $post_id  New post ID.
		 * @param int $user_id  Current user ID.
		 */
		do_action( "apollo_editor_draft_created_{$post_type}", $post_id, get_current_user_id() );

		return $post_id;
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		TEMPLATE LOADING
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Load the edit page template (canvas mode — full HTML).
	 *
	 * Template hierarchy:
	 *   1. {theme}/apollo-templates/edit-{cpt}.php
	 *   2. {theme}/apollo/edit-{cpt}.php
	 *   3. {calling_plugin}/templates/edit-{cpt}.php  (via filter)
	 *   4. apollo-templates/templates/edit-post.php    (generic)
	 *
	 * @param \WP_Post $post      Post object.
	 * @param string   $post_type CPT slug.
	 * @return void
	 */
	private function load_edit_template( \WP_Post $post, string $post_type ): void {
		$editor = FrontendEditor::get_instance();
		$config = $editor->get_editor_config( $post_type );

		// Enqueue assets
		$editor->enqueue_assets( $post_type, $post->ID );

		// Try CPT-specific template first
		$template = apollo_locate_template( "edit-{$post_type}.php" );

		// Fallback to generic edit-post.php
		if ( empty( $template ) ) {
			$template = apollo_locate_template( 'edit-post.php' );
		}

		if ( empty( $template ) ) {
			wp_die(
				esc_html__( 'Template de edição não encontrado.', 'apollo-templates' ),
				'',
				array( 'response' => 500 )
			);
		}

		// Make variables available to the template
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		$template_vars = array(
			'post'       => $post,
			'post_id'    => $post->ID,
			'post_type'  => $post_type,
			'editor'     => $editor,
			'config'     => $config,
			'sections'   => $editor->get_fields_by_section( $post_type ),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'ajax_nonce' => wp_create_nonce( 'apollo_frontend_editor' ),
		);

		extract( $template_vars, EXTR_SKIP );

		include $template;
	}
}
