<?php
/**
 * Form Class
 *
 * Adapted from WPAdverts Adverts_Form class pattern.
 * Handles form definition, loading, binding data, validation.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Form {

	/**
	 * Form scheme name
	 */
	protected string $scheme = '';

	/**
	 * Form fields
	 *
	 * @var array<string, array>
	 */
	protected array $fields = array();

	/**
	 * Bound values
	 *
	 * @var array<string, mixed>
	 */
	protected array $values = array();

	/**
	 * Validation errors
	 *
	 * @var array<string, string>
	 */
	protected array $errors = array();

	/**
	 * Registered field types with render callbacks
	 *
	 * @var array<string, callable>
	 */
	protected static array $field_types = array();

	/**
	 * Registered validators
	 *
	 * @var array<string, callable>
	 */
	protected static array $validators = array();

	/**
	 * Registered filter callbacks
	 *
	 * @var array<string, callable>
	 */
	protected static array $filters = array();

	/**
	 * Load form from scheme definition
	 * Adapted from Adverts_Form::load()
	 *
	 * @param string $scheme Form scheme name (e.g. 'publish', 'search')
	 * @return Form
	 */
	public static function load( string $scheme ): Form {
		$form         = new self();
		$form->scheme = $scheme;

		/**
		 * Filter the form scheme fields
		 * Adapted from adverts_form_load filter
		 */
		$fields = apply_filters( 'apollo/adverts/form/load', array(), $scheme );
		$fields = apply_filters( "apollo/adverts/form/load/{$scheme}", $fields );

		foreach ( $fields as $field ) {
			$field = wp_parse_args(
				$field,
				array(
					'name'        => '',
					'type'        => 'text',
					'label'       => '',
					'order'       => 10,
					'max_length'  => 0,
					'class'       => array(),
					'attr'        => array(),
					'value'       => '',
					'options'     => array(),
					'placeholder' => '',
					'is_required' => false,
					'validator'   => array(),
					'filter'      => array(),
				)
			);

			if ( ! empty( $field['name'] ) ) {
				$form->fields[ $field['name'] ] = $field;
			}
		}

		// Sort by order
		uasort(
			$form->fields,
			function ( $a, $b ) {
				return ( $a['order'] ?? 10 ) - ( $b['order'] ?? 10 );
			}
		);

		return $form;
	}

	/**
	 * Bind values from request or post meta
	 * Adapted from Adverts_Form::bind()
	 *
	 * @param array<string, mixed> $data Data to bind
	 * @return void
	 */
	public function bind( array $data ): void {
		foreach ( $this->fields as $name => &$field ) {
			if ( isset( $data[ $name ] ) ) {
				$field['value']        = $data[ $name ];
				$this->values[ $name ] = $data[ $name ];
			}
		}
	}

	/**
	 * Bind from post meta
	 * Adapted from WPAdverts form bind from DB pattern
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function bind_from_post( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$data = array(
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
		);

		foreach ( $this->fields as $name => $field ) {
			if ( strpos( $name, '_classified_' ) === 0 || strpos( $name, '_' ) === 0 ) {
				$meta_value = get_post_meta( $post_id, $name, true );
				if ( $meta_value !== '' ) {
					$data[ $name ] = $meta_value;
				}
			}
		}

		// Taxonomies
		foreach ( array( APOLLO_TAX_CLASSIFIED_DOMAIN, APOLLO_TAX_CLASSIFIED_INTENT ) as $tax ) {
			$terms = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'slugs' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$data[ $tax ] = $terms;
			}
		}

		$this->bind( $data );
	}

	/**
	 * Validate all fields
	 * Adapted from Adverts_Form::validate()
	 *
	 * @return bool True if valid
	 */
	public function validate(): bool {
		$this->errors = array();

		foreach ( $this->fields as $name => $field ) {
			$value = $field['value'] ?? '';

			// Required check
			if ( ! empty( $field['is_required'] ) && ( $value === '' || $value === null || ( is_array( $value ) && empty( $value ) ) ) ) {
				$this->errors[ $name ] = sprintf(
					/* translators: %s: field label */
					__( '%s é obrigatório.', 'apollo-adverts' ),
					$field['label']
				);
				continue;
			}

			// Run registered validators
			if ( ! empty( $field['validator'] ) && is_array( $field['validator'] ) ) {
				foreach ( $field['validator'] as $validator_config ) {
					$validator_name = is_string( $validator_config ) ? $validator_config : ( $validator_config['name'] ?? '' );
					$params         = is_array( $validator_config ) ? $validator_config : array();

					if ( isset( self::$validators[ $validator_name ] ) ) {
						$result = call_user_func( self::$validators[ $validator_name ], $value, $params, $field );
						if ( is_string( $result ) && $result !== '' ) {
							$this->errors[ $name ] = $result;
							break;
						}
					}
				}
			}
		}

		return empty( $this->errors );
	}

	/**
	 * Apply filters to values before saving
	 * Adapted from WPAdverts filter pipeline
	 *
	 * @return array<string, mixed> Filtered values
	 */
	public function get_filtered_values(): array {
		$filtered = array();

		foreach ( $this->fields as $name => $field ) {
			$value = $field['value'] ?? '';

			// Apply registered filters
			if ( ! empty( $field['filter'] ) && is_array( $field['filter'] ) ) {
				foreach ( $field['filter'] as $filter_config ) {
					$filter_name = is_string( $filter_config ) ? $filter_config : ( $filter_config['name'] ?? '' );
					$params      = is_array( $filter_config ) ? $filter_config : array();

					if ( isset( self::$filters[ $filter_name ] ) ) {
						$value = call_user_func( self::$filters[ $filter_name ], $value, $params, $field );
					}
				}
			}

			$filtered[ $name ] = $value;
		}

		return $filtered;
	}

	/**
	 * Get form scheme
	 */
	public function get_scheme(): string {
		return $this->scheme;
	}

	/**
	 * Get all fields
	 *
	 * @return array<string, array>
	 */
	public function get_fields(): array {
		return $this->fields;
	}

	/**
	 * Get single field
	 */
	public function get_field( string $name ): ?array {
		return $this->fields[ $name ] ?? null;
	}

	/**
	 * Get bound values
	 *
	 * @return array<string, mixed>
	 */
	public function get_values(): array {
		return $this->values;
	}

	/**
	 * Get validation errors
	 *
	 * @return array<string, string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Check if form has errors
	 */
	public function has_errors(): bool {
		return ! empty( $this->errors );
	}

	/**
	 * Get error for specific field
	 */
	public function get_error( string $name ): string {
		return $this->errors[ $name ] ?? '';
	}

	/**
	 * Register a field type with its render callback
	 * Adapted from Adverts_Form::add_field_type()
	 *
	 * @param string   $type     Field type name
	 * @param callable $callback Render callback
	 */
	public static function register_field_type( string $type, callable $callback ): void {
		self::$field_types[ $type ] = $callback;
	}

	/**
	 * Register a validator
	 * Adapted from Adverts_Form::add_validator()
	 *
	 * @param string   $name     Validator name
	 * @param callable $callback Validator callback
	 */
	public static function register_validator( string $name, callable $callback ): void {
		self::$validators[ $name ] = $callback;
	}

	/**
	 * Register a filter
	 *
	 * @param string   $name     Filter name
	 * @param callable $callback Filter callback
	 */
	public static function register_filter( string $name, callable $callback ): void {
		self::$filters[ $name ] = $callback;
	}

	/**
	 * Render a single field
	 * Adapted from Adverts_Form::render() field rendering
	 *
	 * @param string $name Field name
	 * @return string HTML output
	 */
	public function render_field( string $name ): string {
		if ( ! isset( $this->fields[ $name ] ) ) {
			return '';
		}

		$field = $this->fields[ $name ];
		$type  = $field['type'];
		$error = $this->get_error( $name );

		ob_start();

		if ( isset( self::$field_types[ $type ] ) ) {
			call_user_func( self::$field_types[ $type ], $field, $error, $this );
		} else {
			// Default text input
			self::render_text_field( $field, $error );
		}

		return ob_get_clean();
	}

	/**
	 * Render entire form
	 *
	 * @return string HTML
	 */
	public function render(): string {
		ob_start();
		foreach ( $this->fields as $name => $field ) {
			echo $this->render_field( $name );
		}
		return ob_get_clean();
	}

	/**
	 * Default text field renderer
	 */
	public static function render_text_field( array $field, string $error = '' ): void {
		$css   = ! empty( $field['class'] ) ? implode( ' ', (array) $field['class'] ) : 'apollo-field-text';
		$attrs = '';
		if ( ! empty( $field['attr'] ) ) {
			foreach ( $field['attr'] as $k => $v ) {
				$attrs .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
			}
		}

		printf( '<div class="apollo-field-wrap %s">', $error ? 'has-error' : '' );
		printf( '<label for="%s">%s%s</label>', esc_attr( $field['name'] ), esc_html( $field['label'] ), $field['is_required'] ? ' <span class="required">*</span>' : '' );
		printf(
			'<input type="text" id="%s" name="%s" value="%s" placeholder="%s" class="apollo-input %s" %s%s />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( (string) ( $field['value'] ?? '' ) ),
			esc_attr( $field['placeholder'] ?? '' ),
			esc_attr( $css ),
			$field['max_length'] ? sprintf( 'maxlength="%d"', (int) $field['max_length'] ) : '',
			$attrs
		);
		if ( $error ) {
			printf( '<span class="apollo-field-error">%s</span>', esc_html( $error ) );
		}
		echo '</div>';
	}

	/**
	 * Select field renderer
	 */
	public static function render_select_field( array $field, string $error = '' ): void {
		$current = $field['value'] ?? '';

		printf( '<div class="apollo-field-wrap %s">', $error ? 'has-error' : '' );
		printf( '<label for="%s">%s%s</label>', esc_attr( $field['name'] ), esc_html( $field['label'] ), $field['is_required'] ? ' <span class="required">*</span>' : '' );
		printf( '<select id="%s" name="%s">', esc_attr( $field['name'] ), esc_attr( $field['name'] ) );

		if ( ! empty( $field['placeholder'] ) ) {
			printf( '<option value="">%s</option>', esc_html( $field['placeholder'] ) );
		}

		foreach ( (array) $field['options'] as $opt ) {
			$val   = $opt['value'] ?? '';
			$label = $opt['text'] ?? $opt['label'] ?? $val;
			printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $current, $val, false ), esc_html( $label ) );
		}

		echo '</select>';
		if ( $error ) {
			printf( '<span class="apollo-field-error">%s</span>', esc_html( $error ) );
		}
		echo '</div>';
	}

	/**
	 * Textarea renderer
	 */
	public static function render_textarea_field( array $field, string $error = '' ): void {
		printf( '<div class="apollo-field-wrap %s">', $error ? 'has-error' : '' );
		printf( '<label for="%s">%s%s</label>', esc_attr( $field['name'] ), esc_html( $field['label'] ), $field['is_required'] ? ' <span class="required">*</span>' : '' );
		printf(
			'<textarea id="%s" name="%s" placeholder="%s" rows="6" class="apollo-textarea">%s</textarea>',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $field['placeholder'] ?? '' ),
			esc_textarea( (string) ( $field['value'] ?? '' ) )
		);
		if ( $error ) {
			printf( '<span class="apollo-field-error">%s</span>', esc_html( $error ) );
		}
		echo '</div>';
	}

	/**
	 * Checkbox renderer
	 */
	public static function render_checkbox_field( array $field, string $error = '' ): void {
		$checked = ! empty( $field['value'] );

		printf( '<div class="apollo-field-wrap apollo-field-checkbox %s">', $error ? 'has-error' : '' );
		printf(
			'<label class="custom-checkbox"><input type="checkbox" name="%s" value="1" %s /> %s</label>',
			esc_attr( $field['name'] ),
			checked( $checked, true, false ),
			esc_html( $field['label'] )
		);
		if ( $error ) {
			printf( '<span class="apollo-field-error">%s</span>', esc_html( $error ) );
		}
		echo '</div>';
	}

	/**
	 * Hidden field renderer
	 */
	public static function render_hidden_field( array $field, string $error = '' ): void {
		printf(
			'<input type="hidden" name="%s" value="%s" />',
			esc_attr( $field['name'] ),
			esc_attr( (string) ( $field['value'] ?? '' ) )
		);
	}
}