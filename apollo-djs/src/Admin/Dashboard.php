<?php
/**
 * Admin Dashboard — apollo-djs
 *
 * @package Apollo\DJs
 */

declare(strict_types=1);

namespace Apollo\DJs\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dashboard {

	public function __construct() {
		add_filter( 'apollo_dashboard_tabs', array( $this, 'register_tab' ) );
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'wp_ajax_apollo_dj_save_settings', array( $this, 'save_settings' ) );
	}

	public function register_tab( array $tabs ): array {
		$tabs['djs'] = array(
			'title'    => __( 'DJs', 'apollo-djs' ),
			'icon'     => 'dashicons-format-audio',
			'callback' => array( $this, 'render_tab' ),
			'priority' => 30,
		);
		return $tabs;
	}

	public function register_menu(): void {
		if ( ! has_filter( 'apollo_dashboard_tabs' ) || ! function_exists( 'apollo_dashboard' ) ) {
			return;
		}
	}

	public function render_tab(): void {
		$settings = get_option( 'apollo_dj_settings', array() );
		?>
		<div class="apollo-dashboard-section">
			<h2><?php esc_html_e( 'Configurações — DJs', 'apollo-djs' ); ?></h2>

			<form id="apollo-dj-settings-form" class="apollo-settings-form">
				<?php wp_nonce_field( 'apollo_dj_settings', 'apollo_dj_settings_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="dj_default_style"><?php esc_html_e( 'Estilo padrão', 'apollo-djs' ); ?></label></th>
						<td>
							<select id="dj_default_style" name="default_style">
								<option value="apollo-v1" <?php selected( $settings['default_style'] ?? 'apollo-v1', 'apollo-v1' ); ?>>Apollo V1 (Berlin)</option>
								<option value="base" <?php selected( $settings['default_style'] ?? 'apollo-v1', 'base' ); ?>>Base</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="dj_per_page"><?php esc_html_e( 'DJs por página', 'apollo-djs' ); ?></label></th>
						<td>
							<input type="number" id="dj_per_page" name="per_page" value="<?php echo esc_attr( $settings['per_page'] ?? 12 ); ?>" min="1" max="100">
						</td>
					</tr>
					<tr>
						<th><label for="dj_default_view"><?php esc_html_e( 'Visualização padrão', 'apollo-djs' ); ?></label></th>
						<td>
							<select id="dj_default_view" name="default_view">
								<option value="grid" <?php selected( $settings['default_view'] ?? 'grid', 'grid' ); ?>>Grid</option>
								<option value="list" <?php selected( $settings['default_view'] ?? 'grid', 'list' ); ?>>Lista</option>
								<option value="carousel" <?php selected( $settings['default_view'] ?? 'grid', 'carousel' ); ?>>Carousel</option>
							</select>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Salvar', 'apollo-djs' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	public function save_settings(): void {
		check_ajax_referer( 'apollo_dj_settings', 'apollo_dj_settings_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sem permissão' );
		}

		$settings = array(
			'default_style' => in_array( $_POST['default_style'] ?? '', array( 'apollo-v1', 'base' ), true )
				? $_POST['default_style'] : 'apollo-v1',
			'per_page'      => absint( $_POST['per_page'] ?? 12 ),
			'default_view'  => in_array( $_POST['default_view'] ?? '', array( 'grid', 'list', 'carousel' ), true )
				? $_POST['default_view'] : 'grid',
		);

		update_option( 'apollo_dj_settings', $settings );
		wp_send_json_success( $settings );
	}
}