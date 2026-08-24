<?php
/**
 * Admin Dashboard Tab — Aba de configurações no Apollo Dashboard
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dashboard {

	/** @var string */
	private string $option_key = 'apollo_event_settings';

	public function __construct() {
		// Registrar tab no Apollo Dashboard
		add_filter( 'apollo_dashboard_tabs', array( $this, 'register_tab' ) );

		// Renderizar conteúdo da tab
		add_action( 'apollo_dashboard_tab_events', array( $this, 'render_tab' ) );

		// Salvar settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// AJAX para salvar configurações (se não usa Apollo Dashboard)
		add_action( 'wp_ajax_apollo_event_save_settings', array( $this, 'ajax_save' ) );

		// Menu standalone fallback (se Apollo Dashboard não existe)
		add_action( 'admin_menu', array( $this, 'maybe_add_menu' ), 99 );
	}

	/**
	 * Registra tab no Apollo Dashboard
	 */
	public function register_tab( array $tabs ): array {
		$tabs['events'] = array(
			'label' => __( 'Eventos', 'apollo-events' ),
			'icon'  => 'dashicons-calendar-alt',
			'order' => 30,
		);
		return $tabs;
	}

	/**
	 * Registra settings no WP
	 */
	public function register_settings(): void {
		register_setting(
			'apollo_event_settings_group',
			$this->option_key,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);
	}

	/**
	 * Menu standalone — só adiciona se Apollo Dashboard NÃO existe
	 */
	public function maybe_add_menu(): void {
		if ( has_filter( 'apollo_dashboard_tabs' ) && did_action( 'apollo_dashboard_init' ) ) {
			return;
		}

		add_submenu_page(
			'edit.php?post_type=event',
			__( 'Configurações de Eventos', 'apollo-events' ),
			__( 'Configurações', 'apollo-events' ),
			'manage_options',
			'apollo-events-settings',
			array( $this, 'render_standalone_page' )
		);
	}

	/**
	 * Renderiza tab dentro do Apollo Dashboard
	 */
	public function render_tab(): void {
		$settings = $this->get_settings();
		$this->render_form( $settings );
	}

	/**
	 * Página standalone (fallback)
	 */
	public function render_standalone_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'apollo-events' ) );
		}

		// Processar salvamento
		if ( isset( $_POST['apollo_event_settings_nonce'] ) ) {
			check_admin_referer( 'apollo_event_settings', 'apollo_event_settings_nonce' );
			$this->save_settings( $_POST );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Configurações salvas.', 'apollo-events' ) . '</p></div>';
		}

		$settings = $this->get_settings();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Apollo Events — Configurações', 'apollo-events' ) . '</h1>';
		$this->render_form( $settings );
		echo '</div>';
	}

	/**
	 * Renderiza formulário de configurações
	 */
	private function render_form( array $settings ): void {
		?>
		<form method="post" class="apollo-event-settings-form">
			<?php wp_nonce_field( 'apollo_event_settings', 'apollo_event_settings_nonce' ); ?>

			<!-- Estilo Padrão -->
			<div class="apollo-setting-section">
				<h2><?php esc_html_e( 'Estilo Visual', 'apollo-events' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="default_style"><?php esc_html_e( 'Estilo Padrão', 'apollo-events' ); ?></label>
						</th>
						<td>
							<select name="default_style" id="default_style">
								<?php foreach ( APOLLO_EVENT_STYLES as $style ) : ?>
									<option value="<?php echo esc_attr( $style ); ?>" <?php selected( $settings['default_style'], $style ); ?>>
										<?php echo esc_html( ucfirst( $style ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Estilo visual padrão dos eventos.', 'apollo-events' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Estilos Habilitados', 'apollo-events' ); ?></th>
						<td>
							<?php foreach ( APOLLO_EVENT_STYLES as $style ) : ?>
								<label style="display: block; margin-bottom: 5px;">
									<input type="checkbox" name="styles_enabled[]" value="<?php echo esc_attr( $style ); ?>"
										<?php checked( in_array( $style, $settings['styles_enabled'], true ) ); ?>>
									<?php echo esc_html( ucfirst( $style ) ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Selecione quais estilos estarão disponíveis para uso.', 'apollo-events' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Mapa -->
			<div class="apollo-setting-section">
				<h2><?php esc_html_e( 'Mapa', 'apollo-events' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="enable_osm_map"><?php esc_html_e( 'Mapa OSM (Leaflet)', 'apollo-events' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="enable_osm_map" id="enable_osm_map" value="1"
									<?php checked( $settings['enable_osm_map'] ); ?>>
								<?php esc_html_e( 'Habilitar mapa OpenStreetMap com Leaflet', 'apollo-events' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>

			<!-- Expiração -->
			<div class="apollo-setting-section">
				<h2><?php esc_html_e( 'Expiração', 'apollo-events' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="enable_expiration"><?php esc_html_e( 'Auto-Expiração', 'apollo-events' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="enable_expiration" id="enable_expiration" value="1"
									<?php checked( $settings['enable_expiration'] ); ?>>
								<?php esc_html_e( 'Marcar eventos como expirados automaticamente', 'apollo-events' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gone_offset_minutes"><?php esc_html_e( 'Offset de Expiração', 'apollo-events' ); ?></label>
						</th>
						<td>
							<input type="number" name="gone_offset_minutes" id="gone_offset_minutes"
								value="<?php echo esc_attr( $settings['gone_offset_minutes'] ); ?>"
								min="0" max="1440" step="5" class="small-text">
							<span><?php esc_html_e( 'minutos após o fim do evento', 'apollo-events' ); ?></span>
							<p class="description"><?php esc_html_e( 'Padrão: 30 minutos. O evento será marcado como "gone" após esse tempo.', 'apollo-events' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="show_gone_events"><?php esc_html_e( 'Mostrar Expirados', 'apollo-events' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="show_gone_events" id="show_gone_events" value="1"
									<?php checked( $settings['show_gone_events'] ); ?>>
								<?php esc_html_e( 'Mostrar eventos expirados nas listagens (com classe CSS a-eve-gone)', 'apollo-events' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>

			<!-- Listagem -->
			<div class="apollo-setting-section">
				<h2><?php esc_html_e( 'Listagem', 'apollo-events' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="events_per_page"><?php esc_html_e( 'Eventos por Página', 'apollo-events' ); ?></label>
						</th>
						<td>
							<input type="number" name="events_per_page" id="events_per_page"
								value="<?php echo esc_attr( $settings['events_per_page'] ?? 12 ); ?>"
								min="1" max="100" class="small-text">
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="default_view"><?php esc_html_e( 'Visualização Padrão', 'apollo-events' ); ?></label>
						</th>
						<td>
							<select name="default_view" id="default_view">
								<option value="card" <?php selected( $settings['default_view'] ?? 'card', 'card' ); ?>><?php esc_html_e( 'Cards', 'apollo-events' ); ?></option>
								<option value="list" <?php selected( $settings['default_view'] ?? 'card', 'list' ); ?>><?php esc_html_e( 'Lista', 'apollo-events' ); ?></option>
								<option value="map" <?php selected( $settings['default_view'] ?? 'card', 'map' ); ?>><?php esc_html_e( 'Mapa', 'apollo-events' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<?php submit_button( __( 'Salvar Configurações', 'apollo-events' ) ); ?>
		</form>
		<?php
	}

	/**
	 * AJAX save
	 */
	public function ajax_save(): void {
		check_ajax_referer( 'apollo_event_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Acesso negado.' ) );
		}

		$this->save_settings( $_POST );
		wp_send_json_success( array( 'message' => 'Configurações salvas.' ) );
	}

	/**
	 * Salva configurações
	 */
	private function save_settings( array $input ): void {
		$settings = $this->sanitize_settings( $input );
		update_option( $this->option_key, $settings );
	}

	/**
	 * Sanitiza configurações
	 */
	public function sanitize_settings( $input ): array {
		$defaults = $this->get_defaults();

		return array(
			'default_style'       => in_array( $input['default_style'] ?? '', APOLLO_EVENT_STYLES, true )
				? $input['default_style'] : $defaults['default_style'],
			'styles_enabled'      => isset( $input['styles_enabled'] ) && is_array( $input['styles_enabled'] )
				? array_intersect( $input['styles_enabled'], APOLLO_EVENT_STYLES )
				: $defaults['styles_enabled'],
			'enable_osm_map'      => ! empty( $input['enable_osm_map'] ),
			'enable_expiration'   => ! empty( $input['enable_expiration'] ),
			'show_gone_events'    => ! empty( $input['show_gone_events'] ),
			'gone_offset_minutes' => max( 0, min( 1440, (int) ( $input['gone_offset_minutes'] ?? 30 ) ) ),
			'events_per_page'     => max( 1, min( 100, (int) ( $input['events_per_page'] ?? 12 ) ) ),
			'default_view'        => in_array( $input['default_view'] ?? '', array( 'card', 'list', 'map' ), true )
				? $input['default_view'] : 'card',
		);
	}

	/**
	 * Obtém settings atuais
	 */
	private function get_settings(): array {
		return wp_parse_args( get_option( $this->option_key, array() ), $this->get_defaults() );
	}

	/**
	 * Defaults
	 */
	private function get_defaults(): array {
		return array(
			'default_style'       => APOLLO_EVENT_DEFAULT_STYLE,
			'styles_enabled'      => APOLLO_EVENT_STYLES,
			'enable_osm_map'      => true,
			'enable_expiration'   => true,
			'show_gone_events'    => true,
			'gone_offset_minutes' => APOLLO_EVENT_GONE_OFFSET_MINUTES,
			'events_per_page'     => 12,
			'default_view'        => 'card',
		);
	}
}