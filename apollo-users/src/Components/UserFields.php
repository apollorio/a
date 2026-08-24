<?php
/**
 * User Fields Component
 *
 * Handles custom user fields registration and rendering.
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
 * User Fields class
 */
class UserFields {

	/**
	 * Default user fields
	 *
	 * @var array
	 */
	private array $default_fields = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->default_fields = $this->get_default_fields();

		// Add fields to user profile in admin
		add_action( 'show_user_profile', array( $this, 'render_admin_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'render_admin_fields' ) );

		// Save fields from admin
		add_action( 'personal_options_update', array( $this, 'save_admin_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_admin_fields' ) );
	}

	/**
	 * Get default fields configuration
	 *
	 * @return array
	 */
	private function get_default_fields(): array {
		return array(
			'_apollo_social_name'     => array(
				'label'       => __( 'Nome Social' ),
				'type'        => 'text',
				'placeholder' => __( 'Como você quer ser chamado' ),
				'required'    => false,
				'public'      => true,
			),
			'_apollo_bio'             => array(
				'label'       => __( 'Bio' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Conte um pouco sobre você...' ),
				'required'    => false,
				'public'      => true,
				'maxlength'   => 500,
			),
			'_apollo_phone'           => array(
				'label'       => __( 'Telefone/WhatsApp' ),
				'type'        => 'tel',
				'placeholder' => __( '(11) 99999-9999' ),
				'required'    => false,
				'public'      => false,
			),
			'user_location'           => array(
				'label'       => __( 'Cidade' ),
				'type'        => 'text',
				'placeholder' => __( 'São Paulo, SP' ),
				'required'    => false,
				'public'      => true,
			),
			'instagram'               => array(
				'label'       => __( 'Instagram' ),
				'type'        => 'text',
				'placeholder' => __( '@seuusuario' ),
				'required'    => false,
				'public'      => true,
			),
			'_apollo_website'         => array(
				'label'       => __( 'Site' ),
				'type'        => 'url',
				'placeholder' => __( 'https://seusite.com' ),
				'required'    => false,
				'public'      => true,
			),
			'_apollo_privacy_profile' => array(
				'label'   => __( 'Privacidade do Perfil' ),
				'type'    => 'select',
				'options' => array(
					'public'  => __( 'Público' ),
					'members' => __( 'Apenas Membros' ),
					'private' => __( 'Privado' ),
				),
				'default' => 'public',
			),
			'_apollo_privacy_email'   => array(
				'label'   => __( 'Ocultar e-mail no perfil' ),
				'type'    => 'checkbox',
				'default' => true,
			),
		);
	}

	/**
	 * Render admin profile fields
	 *
	 * @param \WP_User $user User object.
	 * @return void
	 */
	public function render_admin_fields( \WP_User $user ): void {
		?>
		<h2><?php esc_html_e( 'Informações Apollo' ); ?></h2>
		<table class="form-table">
			<?php foreach ( $this->default_fields as $key => $field ) : ?>
				<tr>
					<th>
						<label for="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $field['label'] ); ?>
						</label>
					</th>
					<td>
						<?php $this->render_field( $key, $field, $user->ID ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Render individual field
	 *
	 * @param string $key      Field key.
	 * @param array  $field    Field configuration.
	 * @param int    $user_id  User ID.
	 * @return void
	 */
	private function render_field( string $key, array $field, int $user_id ): void {
		$value = get_user_meta( $user_id, $key, true );

		switch ( $field['type'] ) {
			case 'textarea':
				printf(
					'<textarea name="%1$s" id="%1$s" class="apollo-textarea regular-text" rows="5" placeholder="%2$s" %3$s>%4$s</textarea>',
					esc_attr( $key ),
					esc_attr( $field['placeholder'] ?? '' ),
					isset( $field['maxlength'] ) ? 'maxlength="' . intval( $field['maxlength'] ) . '"' : '',
					esc_textarea( $value )
				);
				break;

			case 'select':
				printf( '<select name="%1$s" id="%1$s" class="regular-text">', esc_attr( $key ) );
				foreach ( $field['options'] as $opt_key => $opt_label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $opt_key ),
						selected( $value ?: ( $field['default'] ?? '' ), $opt_key, false ),
						esc_html( $opt_label )
					);
				}
				echo '</select>';
				break;

			case 'checkbox':
				$checked = $value !== '' ? (bool) $value : ( $field['default'] ?? false );
				printf(
					'<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s />',
					esc_attr( $key ),
					checked( $checked, true, false )
				);
				break;

			default:
				printf(
					'<input type="%1$s" name="%2$s" id="%2$s" class="regular-text" value="%3$s" placeholder="%4$s" />',
					esc_attr( $field['type'] ),
					esc_attr( $key ),
					esc_attr( $value ),
					esc_attr( $field['placeholder'] ?? '' )
				);
				break;
		}
	}

	/**
	 * Save admin profile fields
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_admin_fields( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		foreach ( $this->default_fields as $key => $field ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = $this->sanitize_field( $key, $field, $_POST[ $key ] );
				update_user_meta( $user_id, $key, $value );
			} elseif ( $field['type'] === 'checkbox' ) {
				// Unchecked checkbox
				update_user_meta( $user_id, $key, false );
			}
		}
	}

	/**
	 * Sanitize field value
	 *
	 * @param string $key   Field key.
	 * @param array  $field Field configuration.
	 * @param mixed  $value Value to sanitize.
	 * @return mixed
	 */
	private function sanitize_field( string $key, array $field, $value ) {
		switch ( $field['type'] ) {
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'url':
				return esc_url_raw( $value );
			case 'email':
				return sanitize_email( $value );
			case 'checkbox':
				return ! empty( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get all fields configuration
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return apply_filters( 'apollo_users_fields', $this->default_fields );
	}

	/**
	 * Get public fields for a user
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_public_fields( int $user_id ): array {
		$public_data = array();

		foreach ( $this->default_fields as $key => $field ) {
			if ( ! empty( $field['public'] ) ) {
				$public_data[ $key ] = array(
					'label' => $field['label'],
					'value' => get_user_meta( $user_id, $key, true ),
				);
			}
		}

		return $public_data;
	}
}