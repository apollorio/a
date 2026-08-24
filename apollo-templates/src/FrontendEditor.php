<?php
/**
 * Frontend Editor — Core Engine
 *
 * Shared frontend post-editing system for ALL Apollo CPTs.
 * Inspired by WP User Frontend (WPUF) architecture:
 *   - Filter-based field registration per CPT
 *   - Per-type field rendering via FrontendFields
 *   - AJAX save with meta sanitization pipeline
 *   - Permission checks (author or admin)
 *
 * UI patterns replicated from apollo-users/edit-profile.php:
 *   - Canvas-mode edit page
 *   - Editable inputs with subtle borders → orange glow on focus
 *   - Hero section with cover/image upload
 *   - Fixed save bar at bottom
 *   - Toast notifications
 *
 * Usage by plugins:
 *   add_filter( 'apollo_frontend_fields_dj', function( $fields ) {
 *       $fields[] = [ 'name' => '_dj_bio_short', 'type' => 'textarea', ... ];
 *       return $fields;
 *   });
 *
 * @package Apollo\Templates
 */

declare(strict_types=1);

namespace Apollo\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Editor Engine
 */
final class FrontendEditor {

	/**
	 * Singleton instance
	 *
	 * @var FrontendEditor|null
	 */
	private static ?FrontendEditor $instance = null;

	/**
	 * Field renderer instance
	 *
	 * @var FrontendFields|null
	 */
	private ?FrontendFields $fields_renderer = null;

	/**
	 * Get singleton instance
	 *
	 * @return FrontendEditor
	 */
	public static function get_instance(): FrontendEditor {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	public function init(): void {
		// AJAX handlers
		add_action( 'wp_ajax_apollo_frontend_save', array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_apollo_frontend_upload', array( $this, 'handle_upload' ) );
		add_action( 'wp_ajax_apollo_frontend_delete_image', array( $this, 'handle_delete_image' ) );

		// Register assets
		add_action( 'init', array( $this, 'register_assets' ) );

		// Initialize field renderer
		$this->fields_renderer = FrontendFields::get_instance();
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		ASSETS
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Register editor assets (enqueued on-demand by the template)
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_style(
			'apollo-frontend-editor',
			APOLLO_TEMPLATES_URL . 'assets/css/frontend-editor.css',
			array(),
			APOLLO_TEMPLATES_VERSION
		);

		wp_register_script(
			'apollo-frontend-editor',
			APOLLO_TEMPLATES_URL . 'assets/js/frontend-editor.js',
			array( 'jquery' ),
			APOLLO_TEMPLATES_VERSION,
			true
		);
	}

	/**
	 * Enqueue editor assets and localize data
	 *
	 * @param string $post_type CPT slug.
	 * @param int    $post_id   Post being edited.
	 * @return void
	 */
	public function enqueue_assets( string $post_type, int $post_id ): void {
		wp_enqueue_style( 'apollo-frontend-editor' );
		wp_enqueue_script( 'apollo-frontend-editor' );

		wp_localize_script(
			'apollo-frontend-editor',
			'apolloEditor',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'restUrl'     => rest_url( 'apollo/v1/' ),
				'restNonce'   => wp_create_nonce( 'wp_rest' ),
				'ajaxNonce'   => wp_create_nonce( 'apollo_frontend_editor' ),
				'postId'      => $post_id,
				'postType'    => $post_type,
				'editUrl'     => home_url( '/editar/' . $post_type . '/' ),
				'viewUrl'     => get_permalink( $post_id ),
				'maxUpload'   => wp_max_upload_size(),
				'allowedMime' => array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ),
				'i18n'        => array(
					'saving'       => __( 'Salvando...', 'apollo-templates' ),
					'saved'        => __( 'Salvo com sucesso!', 'apollo-templates' ),
					'error'        => __( 'Erro ao salvar', 'apollo-templates' ),
					'uploading'    => __( 'Enviando imagem...', 'apollo-templates' ),
					'uploaded'     => __( 'Imagem enviada!', 'apollo-templates' ),
					'uploadError'  => __( 'Erro no upload', 'apollo-templates' ),
					'confirm'      => __( 'Descartar alterações?', 'apollo-templates' ),
					'required'     => __( 'Campo obrigatório', 'apollo-templates' ),
					'invalidUrl'   => __( 'URL inválida', 'apollo-templates' ),
					'invalidEmail' => __( 'E-mail inválido', 'apollo-templates' ),
					'tooLong'      => __( 'Texto muito longo', 'apollo-templates' ),
					'deleted'      => __( 'Imagem removida', 'apollo-templates' ),
				),
			)
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		FIELD REGISTRY
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Get registered fields for a CPT.
	 *
	 * Plugins register their fields via filter:
	 *   add_filter( 'apollo_frontend_fields_{cpt}', function( $fields ) { ... } );
	 *
	 * Each field is an array:
	 *   [
	 *       'name'        => '_dj_bio_short',     // meta_key
	 *       'type'        => 'textarea',           // text|textarea|url|email|tel|number|select|checkbox|image|taxonomy|coordinates|hidden|html|section
	 *       'label'       => 'Bio curta',
	 *       'icon'        => 'ri-quill-pen-line',  // Remix Icon class
	 *       'placeholder' => 'Escreva uma bio...',
	 *       'required'    => false,
	 *       'maxlength'   => 280,
	 *       'section'     => 'main',               // main|hero|sidebar|links|settings
	 *       'options'     => [],                    // for select/checkbox
	 *       'taxonomy'    => '',                    // for taxonomy type
	 *       'description' => '',                    // help text below field
	 *       'sanitize'    => 'sanitize_text_field', // custom sanitize callback
	 *       'readonly'    => false,
	 *   ]
	 *
	 * @param string $post_type CPT slug.
	 * @return array<int, array>
	 */
	public function get_fields( string $post_type ): array {
		/**
		 * Filter: apollo_frontend_fields_{cpt}
		 *
		 * @param array $fields Default empty array.
		 */
		$fields = apply_filters( "apollo_frontend_fields_{$post_type}", array() );

		// Always add core WP fields at the beginning
		$core_fields = $this->get_core_fields( $post_type );

		return array_merge( $core_fields, $fields );
	}

	/**
	 * Get core WordPress fields (title, content, thumbnail).
	 *
	 * @param string $post_type CPT slug.
	 * @return array
	 */
	private function get_core_fields( string $post_type ): array {
		$fields = array();

		// Post title
		if ( post_type_supports( $post_type, 'title' ) ) {
			$fields[] = array(
				'name'        => 'post_title',
				'type'        => 'text',
				'label'       => __( 'Nome', 'apollo-templates' ),
				'icon'        => 'ri-text',
				'placeholder' => __( 'Nome...', 'apollo-templates' ),
				'required'    => true,
				'maxlength'   => 200,
				'section'     => 'hero',
				'class'       => 'edit-name',
				'is_core'     => true,
			);
		}

		// Post content (editor)
		if ( post_type_supports( $post_type, 'editor' ) ) {
			$fields[] = array(
				'name'        => 'post_content',
				'type'        => 'textarea',
				'label'       => __( 'Descrição', 'apollo-templates' ),
				'icon'        => 'ri-file-text-line',
				'placeholder' => __( 'Descrição completa...', 'apollo-templates' ),
				'required'    => false,
				'maxlength'   => 5000,
				'section'     => 'main',
				'class'       => 'edit-bio',
				'rows'        => 8,
				'is_core'     => true,
			);
		}

		// Featured image
		if ( post_type_supports( $post_type, 'thumbnail' ) ) {
			$fields[] = array(
				'name'    => '_thumbnail_id',
				'type'    => 'image',
				'label'   => __( 'Imagem destaque', 'apollo-templates' ),
				'icon'    => 'ri-image-line',
				'section' => 'hero',
				'is_core' => true,
			);
		}

		return $fields;
	}

	/**
	 * Get fields grouped by section.
	 *
	 * @param string $post_type CPT slug.
	 * @return array<string, array>
	 */
	public function get_fields_by_section( string $post_type ): array {
		$fields   = $this->get_fields( $post_type );
		$sections = array();

		foreach ( $fields as $field ) {
			$section = $field['section'] ?? 'main';
			if ( ! isset( $sections[ $section ] ) ) {
				$sections[ $section ] = array();
			}
			$sections[ $section ][] = $field;
		}

		return $sections;
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		FORM RENDERING
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render the edit form for a post.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type CPT slug (auto-detected if empty).
	 * @return string HTML.
	 */
	public function render_form( int $post_id, string $post_type = '' ): string {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return '<div class="apollo-editor-error">' . esc_html__( 'Post não encontrado.', 'apollo-templates' ) . '</div>';
		}

		if ( empty( $post_type ) ) {
			$post_type = $post->post_type;
		}

		// Permission check
		if ( ! $this->can_edit( $post_id ) ) {
			return '<div class="apollo-editor-error">' . esc_html__( 'Você não tem permissão para editar este post.', 'apollo-templates' ) . '</div>';
		}

		// Enqueue assets
		$this->enqueue_assets( $post_type, $post_id );

		// Get fields by section
		$sections = $this->get_fields_by_section( $post_type );

		// Get editor config for this CPT
		$config = $this->get_editor_config( $post_type );

		ob_start();
		?>
		<form id="apollo-editor-form"
				class="apollo-editor-form"
				data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
				data-post-type="<?php echo esc_attr( $post_type ); ?>"
				method="post"
				enctype="multipart/form-data"
				novalidate>

			<?php wp_nonce_field( 'apollo_frontend_editor', '_apollo_editor_nonce' ); ?>
			<input type="hidden" name="action" value="apollo_frontend_save">
			<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
			<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>">

			<ul class="apollo-editor-fields">
				<?php
				foreach ( $sections as $section_key => $section_fields ) {
					$this->render_section( $section_key, $section_fields, $post, $config );
				}
				?>
			</ul>

		</form>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a section of fields.
	 *
	 * @param string   $section_key Section name.
	 * @param array    $fields      Fields in this section.
	 * @param \WP_Post $post        Post object.
	 * @param array    $config      Editor config.
	 * @return void
	 */
	private function render_section( string $section_key, array $fields, \WP_Post $post, array $config ): void {
		/**
		 * Filter: apollo_editor_before_section_{key}
		 *
		 * @param string   $section_key
		 * @param \WP_Post $post
		 */
		do_action( "apollo_editor_before_section_{$section_key}", $section_key, $post );

		foreach ( $fields as $field ) {
			$this->render_field( $field, $post );
		}

		do_action( "apollo_editor_after_section_{$section_key}", $section_key, $post );
	}

	/**
	 * Render a single field.
	 *
	 * @param array    $field Field config.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_field( array $field, \WP_Post $post ): void {
		$name = $field['name'] ?? '';
		$type = $field['type'] ?? 'text';

		if ( empty( $name ) ) {
			return;
		}

		// Get current value
		$value = $this->get_field_value( $field, $post );

		// Field visibility filter
		$visible = apply_filters( "apollo_editor_field_visible_{$name}", true, $field, $post );
		if ( ! $visible ) {
			return;
		}

		// Merge defaults
		$field = wp_parse_args(
			$field,
			array(
				'label'       => '',
				'icon'        => '',
				'placeholder' => '',
				'required'    => false,
				'maxlength'   => 0,
				'class'       => '',
				'section'     => 'main',
				'options'     => array(),
				'taxonomy'    => '',
				'description' => '',
				'readonly'    => false,
				'rows'        => 4,
				'min'         => '',
				'max'         => '',
				'step'        => '',
			)
		);

		// Hidden fields
		if ( $type === 'hidden' ) {
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
			return;
		}

		// Wrapper
		$li_classes = array(
			'apollo-editor-field',
			'apollo-field-' . $type,
			'apollo-field--' . sanitize_html_class( $name ),
		);
		if ( ! empty( $field['class'] ) ) {
			$li_classes[] = $field['class'];
		}
		if ( $field['required'] ) {
			$li_classes[] = 'apollo-field-required';
		}

		echo '<li class="' . esc_attr( implode( ' ', $li_classes ) ) . '">';

		// Label
		if ( ! empty( $field['label'] ) && $type !== 'section' ) {
			$this->render_label( $field, $value );
		}

		// Delegate to FrontendFields renderer
		$this->fields_renderer->render( $type, $field, $value, $post );

		// Help text
		if ( ! empty( $field['description'] ) ) {
			echo '<span class="apollo-field-help">' . esc_html( $field['description'] ) . '</span>';
		}

		echo '</li>';
	}

	/**
	 * Render field label (mono-style, with icon + optional counter).
	 *
	 * @param array  $field Field config.
	 * @param string $value Current value.
	 * @return void
	 */
	private function render_label( array $field, string $value ): void {
		$name      = $field['name'];
		$label     = $field['label'];
		$icon      = $field['icon'] ?? '';
		$maxlength = (int) ( $field['maxlength'] ?? 0 );
		$required  = $field['required'] ?? false;

		echo '<label class="field-label" for="field-' . esc_attr( $name ) . '">';

		if ( ! empty( $icon ) ) {
			echo '<i class="' . esc_attr( $icon ) . '"></i> ';
		}

		echo esc_html( $label );

		if ( $required ) {
			echo ' <span class="required-mark">*</span>';
		}

		if ( $maxlength > 0 && in_array( $field['type'], array( 'text', 'textarea' ), true ) ) {
			$current_len = mb_strlen( $value );
			echo ' <small class="char-counter" data-field="' . esc_attr( $name ) . '">(' . $current_len . '/' . $maxlength . ')</small>';
		}

		echo '</label>';
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		VALUES
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Get field value from post.
	 *
	 * @param array    $field Field config.
	 * @param \WP_Post $post  Post object.
	 * @return string
	 */
	private function get_field_value( array $field, \WP_Post $post ): string {
		$name    = $field['name'];
		$is_core = $field['is_core'] ?? false;

		// Core WP fields
		if ( $is_core ) {
			switch ( $name ) {
				case 'post_title':
					return $post->post_title;
				case 'post_content':
					return $post->post_content;
				case 'post_excerpt':
					return $post->post_excerpt;
				case '_thumbnail_id':
					return (string) get_post_thumbnail_id( $post->ID );
			}
		}

		// Taxonomy fields
		if ( ( $field['type'] ?? '' ) === 'taxonomy' && ! empty( $field['taxonomy'] ) ) {
			$terms = wp_get_post_terms( $post->ID, $field['taxonomy'], array( 'fields' => 'ids' ) );
			return is_wp_error( $terms ) ? '' : implode( ',', $terms );
		}

		// Meta fields
		$value = get_post_meta( $post->ID, $name, true );
		return is_array( $value ) ? wp_json_encode( $value ) : (string) $value;
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		EDITOR CONFIG
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Get editor configuration for a CPT.
	 *
	 * Plugins can customize via filter:
	 *   add_filter( 'apollo_editor_config_dj', function( $config ) { ... } );
	 *
	 * @param string $post_type CPT slug.
	 * @return array
	 */
	public function get_editor_config( string $post_type ): array {
		$defaults = array(
			'page_title'     => __( 'Editar', 'apollo-templates' ),
			'hero_enabled'   => true,
			'cover_field'    => '',       // meta_key for cover image (e.g. '_dj_banner')
			'avatar_field'   => '',       // meta_key for avatar/main image (e.g. '_dj_image')
			'sections'       => array( 'hero', 'main', 'links', 'sidebar', 'settings' ),
			'section_labels' => array(
				'hero'     => __( 'Destaque', 'apollo-templates' ),
				'main'     => __( 'Informações', 'apollo-templates' ),
				'links'    => __( 'Links & Redes', 'apollo-templates' ),
				'sidebar'  => __( 'Lateral', 'apollo-templates' ),
				'settings' => __( 'Configurações', 'apollo-templates' ),
			),
			'section_icons'  => array(
				'hero'     => 'ri-star-line',
				'main'     => 'ri-information-line',
				'links'    => 'ri-links-line',
				'sidebar'  => 'ri-layout-right-line',
				'settings' => 'ri-compasses-2-line',
			),
			'save_label'     => __( 'Salvar', 'apollo-templates' ),
			'cancel_url'     => '',       // URL to redirect on cancel (default: post permalink)
			'after_save'     => 'toast',  // toast|redirect|reload
			'redirect_url'   => '',       // URL after save if after_save=redirect
		);

		/**
		 * Filter: apollo_editor_config_{cpt}
		 *
		 * @param array $config Default configuration.
		 */
		return apply_filters( "apollo_editor_config_{$post_type}", $defaults );
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		PERMISSIONS
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Check if current user can edit the given post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function can_edit( int $post_id ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Admins can edit anything
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Post author can edit their own
		$post = get_post( $post_id );
		if ( $post && (int) $post->post_author === get_current_user_id() ) {
			return true;
		}

		// Check for linked user via _*_user_id meta
		$post_type   = $post->post_type ?? '';
		$user_id_key = "_{$post_type}_user_id";
		$linked_user = get_post_meta( $post_id, $user_id_key, true );

		if ( $linked_user && (int) $linked_user === get_current_user_id() ) {
			return true;
		}

		/**
		 * Filter: apollo_editor_can_edit_{cpt}
		 *
		 * @param bool $can_edit Default result.
		 * @param int  $post_id
		 * @param int  $user_id Current user ID.
		 */
		return (bool) apply_filters(
			"apollo_editor_can_edit_{$post_type}",
			false,
			$post_id,
			get_current_user_id()
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		AJAX HANDLERS
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Handle AJAX save request.
	 *
	 * @return void
	 */
	public function handle_save(): void {
		// Verify nonce
		if ( ! check_ajax_referer( 'apollo_frontend_editor', '_apollo_editor_nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce inválido.', 'apollo-templates' ) ), 403 );
		}

		$post_id   = (int) ( $_POST['post_id'] ?? 0 );
		$post_type = sanitize_key( $_POST['post_type'] ?? '' );

		if ( ! $post_id || ! $post_type ) {
			wp_send_json_error( array( 'message' => __( 'Dados inválidos.', 'apollo-templates' ) ), 400 );
		}

		// Permission check
		if ( ! $this->can_edit( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'apollo-templates' ) ), 403 );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $post_type ) {
			wp_send_json_error( array( 'message' => __( 'Post não encontrado.', 'apollo-templates' ) ), 404 );
		}

		// Get registered fields
		$fields = $this->get_fields( $post_type );

		// Prepare post data
		$post_data     = array( 'ID' => $post_id );
		$meta_data     = array();
		$taxonomy_data = array();
		$errors        = array();

		foreach ( $fields as $field ) {
			$name     = $field['name'] ?? '';
			$type     = $field['type'] ?? 'text';
			$required = $field['required'] ?? false;
			$is_core  = $field['is_core'] ?? false;
			$readonly = $field['readonly'] ?? false;

			if ( empty( $name ) || $readonly || $type === 'section' || $type === 'html' ) {
				continue;
			}

			// Get raw value from POST
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$raw_value = $_POST[ $name ] ?? '';

			// Sanitize based on type
			$clean_value = $this->sanitize_field( $raw_value, $field );

			// Required check
			if ( $required && $clean_value === '' && $type !== 'image' ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( '%s é obrigatório.', 'apollo-templates' ),
					$field['label'] ?? $name
				);
				continue;
			}

			// Separate core, meta, and taxonomy
			if ( $is_core ) {
				switch ( $name ) {
					case 'post_title':
						$post_data['post_title'] = $clean_value;
						break;
					case 'post_content':
						$post_data['post_content'] = $clean_value;
						break;
					case 'post_excerpt':
						$post_data['post_excerpt'] = $clean_value;
						break;
				}
			} elseif ( $type === 'taxonomy' && ! empty( $field['taxonomy'] ) ) {
				$term_ids                            = array_filter( array_map( 'intval', explode( ',', $clean_value ) ) );
				$taxonomy_data[ $field['taxonomy'] ] = $term_ids;
			} else {
				$meta_data[ $name ] = $clean_value;
			}
		}

		// Return errors if any
		if ( ! empty( $errors ) ) {
			wp_send_json_error(
				array(
					'message' => implode( ' ', $errors ),
					'errors'  => $errors,
				),
				422
			);
		}

		// Update post
		if ( count( $post_data ) > 1 ) {
			$result = wp_update_post( $post_data, true );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
			}
		}

		// Update meta
		foreach ( $meta_data as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		// Update taxonomies
		foreach ( $taxonomy_data as $taxonomy => $term_ids ) {
			wp_set_post_terms( $post_id, $term_ids, $taxonomy, false );
		}

		/**
		 * Action: apollo_editor_after_save_{cpt}
		 *
		 * @param int   $post_id
		 * @param array $meta_data  Saved meta values.
		 * @param array $post_data  Saved post data.
		 */
		do_action( "apollo_editor_after_save_{$post_type}", $post_id, $meta_data, $post_data );

		wp_send_json_success(
			array(
				'message'  => __( 'Salvo com sucesso!', 'apollo-templates' ),
				'post_id'  => $post_id,
				'view_url' => get_permalink( $post_id ),
			)
		);
	}

	/**
	 * Handle AJAX image upload.
	 *
	 * @return void
	 */
	public function handle_upload(): void {
		// Verify nonce
		if ( ! check_ajax_referer( 'apollo_frontend_editor', '_apollo_editor_nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce inválido.', 'apollo-templates' ) ), 403 );
		}

		$post_id  = (int) ( $_POST['post_id'] ?? 0 );
		$meta_key = sanitize_key( $_POST['meta_key'] ?? '' );

		if ( ! $post_id || ! $meta_key ) {
			wp_send_json_error( array( 'message' => __( 'Dados inválidos.', 'apollo-templates' ) ), 400 );
		}

		if ( ! $this->can_edit( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'apollo-templates' ) ), 403 );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Nenhum arquivo enviado.', 'apollo-templates' ) ), 400 );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'file', $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 500 );
		}

		/*
		 * SSOT: Apollo image metas for dj / local / event store ATTACHMENT IDs
		 * (see apollo_dj_get_image, GalleryMetabox, apollo_event_set_banner).
		 * Storing a URL here used to make (int) casts → 0 and kill every reader.
		 */
		$id_meta_keys = array(
			'_thumbnail_id',
			'_dj_image',
			'_dj_banner',
			'_dj_about_photo',
			'_event_banner',
			'_local_image_1',
			'_local_image_2',
			'_local_image_3',
			'_local_image_4',
			'_local_image_5',
		);
		/**
		 * Filter: apollo_frontend_image_id_metas
		 *
		 * @param string[] $id_meta_keys Meta keys that must store attachment ids.
		 * @param string   $meta_key     Current upload target.
		 * @param int      $post_id      Post being edited.
		 */
		$id_meta_keys = (array) apply_filters( 'apollo_frontend_image_id_metas', $id_meta_keys, $meta_key, $post_id );

		if ( $meta_key === '_thumbnail_id' ) {
			set_post_thumbnail( $post_id, $attachment_id );
		} elseif ( in_array( $meta_key, $id_meta_keys, true ) ) {
			update_post_meta( $post_id, $meta_key, (int) $attachment_id );
		} else {
			$image_url = wp_get_attachment_url( $attachment_id );
			update_post_meta( $post_id, $meta_key, $image_url );
		}

		$image_url = wp_get_attachment_url( $attachment_id );

		wp_send_json_success(
			array(
				'message'       => __( 'Imagem enviada!', 'apollo-templates' ),
				'attachment_id' => $attachment_id,
				'url'           => $image_url,
				'thumbnail'     => wp_get_attachment_image_url( $attachment_id, 'medium' ),
				'stored_as'     => in_array( $meta_key, $id_meta_keys, true ) || $meta_key === '_thumbnail_id' ? 'id' : 'url',
			)
		);
	}

	/**
	 * Handle AJAX image deletion.
	 *
	 * @return void
	 */
	public function handle_delete_image(): void {
		if ( ! check_ajax_referer( 'apollo_frontend_editor', '_apollo_editor_nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce inválido.', 'apollo-templates' ) ), 403 );
		}

		$post_id  = (int) ( $_POST['post_id'] ?? 0 );
		$meta_key = sanitize_key( $_POST['meta_key'] ?? '' );

		if ( ! $post_id || ! $meta_key ) {
			wp_send_json_error( array( 'message' => __( 'Dados inválidos.', 'apollo-templates' ) ), 400 );
		}

		if ( ! $this->can_edit( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'apollo-templates' ) ), 403 );
		}

		if ( $meta_key === '_thumbnail_id' ) {
			delete_post_thumbnail( $post_id );
		} else {
			delete_post_meta( $post_id, $meta_key );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Imagem removida.', 'apollo-templates' ),
			)
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		SANITIZATION
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Sanitize a field value based on type.
	 *
	 * @param mixed $value Raw value.
	 * @param array $field Field config.
	 * @return string
	 */
	private function sanitize_field( $value, array $field ): string {
		$type = $field['type'] ?? 'text';

		// Custom sanitize callback
		if ( ! empty( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {
			return (string) call_user_func( $field['sanitize'], $value );
		}

		switch ( $type ) {
			case 'text':
			case 'tel':
			case 'hidden':
				$value = sanitize_text_field( wp_unslash( $value ) );
				break;

			case 'textarea':
				$value = sanitize_textarea_field( wp_unslash( $value ) );
				break;

			case 'url':
				$value = esc_url_raw( wp_unslash( $value ) );
				break;

			case 'email':
				$value = sanitize_email( wp_unslash( $value ) );
				break;

			case 'number':
			case 'coordinates':
				$value = is_numeric( $value ) ? (string) floatval( $value ) : '';
				break;

			case 'select':
				$value = sanitize_key( wp_unslash( $value ) );
				// Validate against allowed options
				if ( ! empty( $field['options'] ) ) {
					$allowed = array_keys( $field['options'] );
					if ( ! in_array( $value, $allowed, true ) ) {
						$value = '';
					}
				}
				break;

			case 'checkbox':
				if ( is_array( $value ) ) {
					$value = implode( ',', array_map( 'sanitize_key', $value ) );
				} else {
					$value = sanitize_key( wp_unslash( $value ) );
				}
				break;

			case 'taxonomy':
				// Term IDs as comma-separated string
				if ( is_array( $value ) ) {
					$value = implode( ',', array_filter( array_map( 'intval', $value ) ) );
				} else {
					$value = sanitize_text_field( wp_unslash( $value ) );
				}
				break;

			case 'image':
				/*
				 * Attachment id OR absolute URL. esc_url_raw() alone would wipe
				 * numeric ids (Apollo SSOT for dj/local/event image metas).
				 */
				$raw = wp_unslash( (string) $value );
				if ( is_numeric( $raw ) && (int) $raw > 0 ) {
					$value = (string) absint( $raw );
				} elseif ( $raw === '' ) {
					$value = '';
				} else {
					$value = esc_url_raw( $raw );
				}
				break;

			default:
				$value = sanitize_text_field( wp_unslash( $value ) );
				break;
		}

		// Enforce maxlength
		$maxlength = (int) ( $field['maxlength'] ?? 0 );
		if ( $maxlength > 0 ) {
			$value = mb_substr( $value, 0, $maxlength );
		}

		return $value;
	}
}