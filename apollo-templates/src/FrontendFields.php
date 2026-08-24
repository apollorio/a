<?php
/**
 * Frontend Fields — Per-type field renderers
 *
 * Renders HTML for each field type in the frontend editor.
 * Inspired by WPUF's Form_Field_* classes but simplified:
 *   - No DB-stored forms — config-array driven
 *   - UI matches apollo-users/edit-profile.php patterns:
 *     → .editable-input with transparent bg → dashed border → orange glow
 *     → .field-label with mono font, icon, counter
 *     → Image upload with preview, click-to-upload, delete
 *
 * @package Apollo\Templates
 */

declare(strict_types=1);

namespace Apollo\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Fields Renderer
 */
final class FrontendFields {

	/**
	 * Singleton instance
	 *
	 * @var FrontendFields|null
	 */
	private static ?FrontendFields $instance = null;

	/**
	 * Get instance
	 *
	 * @return FrontendFields
	 */
	public static function get_instance(): FrontendFields {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/*
	═══════════════════════════════════════════════════════════════════════
		DISPATCHER
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render a field by type.
	 *
	 * @param string   $type  Field type.
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	public function render( string $type, array $field, string $value, \WP_Post $post ): void {
		$method = 'render_' . $type;

		if ( method_exists( $this, $method ) ) {
			$this->$method( $field, $value, $post );
		} else {
			// Fallback: allow external renderers via action
			$has_custom = has_action( "apollo_editor_render_field_{$type}" );

			if ( $has_custom ) {
				do_action( "apollo_editor_render_field_{$type}", $field, $value, $post );
			} else {
				// Default: render as text
				$this->render_text( $field, $value, $post );
			}
		}
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		COMMON ATTRIBUTES
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Build common HTML attributes for an input.
	 *
	 * @param array  $field Field config.
	 * @param string $value Current value.
	 * @return string
	 */
	private function attrs( array $field, string $value = '' ): string {
		$name        = $field['name'];
		$id          = 'field-' . $name;
		$placeholder = $field['placeholder'] ?? '';
		$required    = $field['required'] ?? false;
		$maxlength   = (int) ( $field['maxlength'] ?? 0 );
		$readonly    = $field['readonly'] ?? false;

		$parts = array(
			'id="' . esc_attr( $id ) . '"',
			'name="' . esc_attr( $name ) . '"',
		);

		if ( $placeholder ) {
			$parts[] = 'placeholder="' . esc_attr( $placeholder ) . '"';
		}
		if ( $required ) {
			$parts[] = 'data-required="1"';
		}
		if ( $maxlength > 0 ) {
			$parts[] = 'maxlength="' . $maxlength . '"';
			$parts[] = 'data-maxlength="' . $maxlength . '"';
		}
		if ( $readonly ) {
			$parts[] = 'readonly';
		}

		return implode( ' ', $parts );
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		TEXT INPUT
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render text input.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_text( array $field, string $value, \WP_Post $post ): void {
		$extra_class = $field['class'] ?? '';
		?>
		<input type="text"
				class="apollo-input editable-input <?php echo esc_attr( $extra_class ); ?>"
		       <?php echo $this->attrs( $field, $value ); // phpcs:ignore ?>
				value="<?php echo esc_attr( $value ); ?>">
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		TEXTAREA
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render textarea.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_textarea( array $field, string $value, \WP_Post $post ): void {
		$rows        = (int) ( $field['rows'] ?? 4 );
		$extra_class = $field['class'] ?? '';
		?>
		<textarea class="apollo-textarea editable-input edit-bio <?php echo esc_attr( $extra_class ); ?>"
		          <?php echo $this->attrs( $field, $value ); // phpcs:ignore ?>
					rows="<?php echo esc_attr( (string) $rows ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		URL
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render URL input.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_url( array $field, string $value, \WP_Post $post ): void {
		?>
		<input type="url"
				class="apollo-input editable-input edit-url"
		       <?php echo $this->attrs( $field, $value ); // phpcs:ignore ?>
				value="<?php echo esc_attr( $value ); ?>">
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		EMAIL
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render email input.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_email( array $field, string $value, \WP_Post $post ): void {
		?>
		<input type="email"
				class="apollo-input editable-input edit-url"
		       <?php echo $this->attrs( $field, $value ); // phpcs:ignore ?>
				value="<?php echo esc_attr( $value ); ?>">
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		TEL (PHONE)
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render telephone input.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_tel( array $field, string $value, \WP_Post $post ): void {
		?>
		<input type="tel"
				class="apollo-input editable-input edit-url"
		       <?php echo $this->attrs( $field, $value ); // phpcs:ignore ?>
				value="<?php echo esc_attr( $value ); ?>">
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		NUMBER
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render number input.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_number( array $field, string $value, \WP_Post $post ): void {
		$min  = $field['min'] ?? '';
		$max  = $field['max'] ?? '';
		$step = $field['step'] ?? '';
		?>
		<input type="number"
				class="apollo-input editable-input"
		       <?php echo $this->attrs( $field, $value ); // phpcs:ignore ?>
				value="<?php echo esc_attr( $value ); ?>"
				<?php
				if ( $min !== '' ) :
					?>
					min="<?php echo esc_attr( $min ); ?>"<?php endif; ?>
				<?php
				if ( $max !== '' ) :
					?>
					max="<?php echo esc_attr( $max ); ?>"<?php endif; ?>
				<?php
				if ( $step !== '' ) :
					?>
					step="<?php echo esc_attr( $step ); ?>"<?php endif; ?>>
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		COORDINATES (lat/lng pair)
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render coordinates input (specialized number for lat/lng).
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_coordinates( array $field, string $value, \WP_Post $post ): void {
		?>
		<input type="number"
				class="apollo-input editable-input edit-url"
		       <?php echo $this->attrs( $field, $value ); // phpcs:ignore ?>
				value="<?php echo esc_attr( $value ); ?>"
				step="0.000001"
				min="-180"
				max="180">
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		SELECT / DROPDOWN
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render select dropdown.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_select( array $field, string $value, \WP_Post $post ): void {
		$options     = $field['options'] ?? array();
		$placeholder = $field['placeholder'] ?? __( 'Selecione...', 'apollo-templates' );
		$name        = $field['name'];
		$required    = $field['required'] ?? false;
		?>
		<select class="editable-input privacy-select"
				id="field-<?php echo esc_attr( $name ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				<?php echo $required ? 'data-required="1"' : ''; ?>>
			<option value=""><?php echo esc_html( $placeholder ); ?></option>
			<?php foreach ( $options as $opt_value => $opt_label ) : ?>
				<option value="<?php echo esc_attr( $opt_value ); ?>"
						<?php selected( $value, (string) $opt_value ); ?>>
					<?php echo esc_html( $opt_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		CHECKBOX (single or multiple)
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render checkbox field.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value (comma-separated for multiple).
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_checkbox( array $field, string $value, \WP_Post $post ): void {
		$options        = $field['options'] ?? array();
		$name           = $field['name'];
		$checked_values = array_map( 'trim', explode( ',', $value ) );

		if ( empty( $options ) ) {
			// Single boolean checkbox
			?>
			<label class="custom-checkbox-single">
				<input type="checkbox"
						name="<?php echo esc_attr( $name ); ?>"
						value="1"
						<?php checked( in_array( '1', $checked_values, true ) ); ?>>
				<span><?php echo esc_html( $field['label'] ?? '' ); ?></span>
			</label>
			<?php
		} else {
			// Multiple checkboxes
			echo '<div class="custom-checkbox-group">';
			foreach ( $options as $opt_value => $opt_label ) {
				?>
				<label class="custom-checkbox-item">
					<input type="checkbox"
							name="<?php echo esc_attr( $name ); ?>[]"
							value="<?php echo esc_attr( $opt_value ); ?>"
							<?php checked( in_array( (string) $opt_value, $checked_values, true ) ); ?>>
					<span><?php echo esc_html( $opt_label ); ?></span>
				</label>
				<?php
			}
			echo '</div>';
		}
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		IMAGE UPLOAD
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render image upload field.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value (URL or attachment ID).
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_image( array $field, string $value, \WP_Post $post ): void {
		$name     = $field['name'];
		$is_cover = $field['is_cover'] ?? false;

		// Resolve image URL
		$image_url = '';
		if ( $name === '_thumbnail_id' ) {
			$thumb_id  = get_post_thumbnail_id( $post->ID );
			$image_url = $thumb_id ? wp_get_attachment_image_url( (int) $thumb_id, 'medium_large' ) : '';
		} elseif ( is_numeric( $value ) ) {
			$image_url = wp_get_attachment_image_url( (int) $value, 'medium_large' );
		} else {
			$image_url = $value;
		}

		$wrapper_class = $is_cover ? 'apollo-image-upload apollo-image-cover' : 'apollo-image-upload';
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>"
			data-meta-key="<?php echo esc_attr( $name ); ?>"
			data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>">

			<div class="apollo-image-preview <?php echo $image_url ? 'has-image' : ''; ?>">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="">
				<?php endif; ?>

				<div class="apollo-image-overlay">
					<button type="button" class="apollo-image-upload-btn hero-edit-btn" title="<?php esc_attr_e( 'Alterar imagem', 'apollo-templates' ); ?>">
						<i class="ri-camera-line"></i>
						<span><?php esc_html_e( 'Alterar', 'apollo-templates' ); ?></span>
					</button>
					<?php if ( $image_url ) : ?>
						<button type="button" class="apollo-image-delete-btn hero-edit-btn" title="<?php esc_attr_e( 'Remover imagem', 'apollo-templates' ); ?>">
							<i class="ri-delete-bin-line"></i>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<input type="file"
					class="apollo-image-file-input"
					accept="image/jpeg,image/png,image/webp,image/gif"
					style="display:none">
			<input type="hidden"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>">
		</div>
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		TAXONOMY (checkbox list or select)
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render taxonomy field.
	 *
	 * @param array    $field Field config.
	 * @param string   $value Current value (comma-separated term IDs).
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_taxonomy( array $field, string $value, \WP_Post $post ): void {
		$taxonomy   = $field['taxonomy'] ?? '';
		$name       = $field['name'];
		$tax_format = $field['tax_format'] ?? 'checkbox'; // checkbox|select

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			echo '<span class="apollo-field-help">' . esc_html__( 'Taxonomia não encontrada.', 'apollo-templates' ) . '</span>';
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '<span class="apollo-field-help">' . esc_html__( 'Nenhum termo disponível.', 'apollo-templates' ) . '</span>';
			return;
		}

		$selected_ids = array_filter( array_map( 'intval', explode( ',', $value ) ) );

		if ( $tax_format === 'select' ) {
			?>
			<select class="editable-input privacy-select"
					id="field-<?php echo esc_attr( $name ); ?>"
					name="<?php echo esc_attr( $name ); ?>">
				<option value=""><?php esc_html_e( 'Selecione...', 'apollo-templates' ); ?></option>
				<?php foreach ( $terms as $term ) : ?>
					<option value="<?php echo esc_attr( (string) $term->term_id ); ?>"
							<?php selected( in_array( $term->term_id, $selected_ids, true ) ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		} else {
			// Checkbox list (default)
			echo '<div class="apollo-taxonomy-list">';
			foreach ( $terms as $term ) {
				$input_id = 'tax-' . $taxonomy . '-' . $term->term_id;
				?>
				<label class="custom-checkbox-item tag" for="<?php echo esc_attr( $input_id ); ?>">
					<input type="checkbox"
							id="<?php echo esc_attr( $input_id ); ?>"
							name="<?php echo esc_attr( $name ); ?>[]"
							value="<?php echo esc_attr( (string) $term->term_id ); ?>"
							<?php checked( in_array( $term->term_id, $selected_ids, true ) ); ?>>
					<span><?php echo esc_html( $term->name ); ?></span>
				</label>
				<?php
			}
			echo '</div>';
		}
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		SECTION DIVIDER
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render section divider (decorative, no input).
	 *
	 * @param array    $field Field config.
	 * @param string   $value Not used.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_section( array $field, string $value, \WP_Post $post ): void {
		$label = $field['label'] ?? '';
		$icon  = $field['icon'] ?? '';
		?>
		<div class="apollo-section-divider">
			<?php if ( $icon ) : ?>
				<i class="<?php echo esc_attr( $icon ); ?>"></i>
			<?php endif; ?>
			<?php if ( $label ) : ?>
				<span><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		HTML (raw output, read-only)
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render custom HTML block (read-only).
	 *
	 * @param array    $field Field config.
	 * @param string   $value Not used.
	 * @param \WP_Post $post  Post object.
	 * @return void
	 */
	private function render_html( array $field, string $value, \WP_Post $post ): void {
		$content = $field['html'] ?? '';
		if ( $content ) {
			echo wp_kses_post( $content );
		}
	}
}