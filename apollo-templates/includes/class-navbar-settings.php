<?php
/**
 * Navbar Apps Settings — Admin UI
 *
 * Manages the apps grid in the navbar via wp-admin.
 * Stores configuration in wp_options as 'apollo_navbar_apps'.
 *
 * @package Apollo\Templates
 * @since 1.1.0
 */

declare(strict_types=1);

namespace Apollo\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Navbar Settings manager
 */
final class NavbarSettings {

	/** Option key in wp_options */
	const OPTION_KEY = 'apollo_navbar_apps';

	/** Settings group */
	const SETTINGS_GROUP = 'apollo_navbar_settings';

	/**
	 * Singleton
	 */
	private static ?NavbarSettings $instance = null;

	public static function get_instance(): NavbarSettings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Bootstrap — call from Plugin::init()
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_apollo_navbar_upload_image', array( $this, 'ajax_upload_image' ) );
	}

	/**
	 * Add submenu under Apollo Templates
	 */
	public function add_submenu(): void {
		add_submenu_page(
			'apollo-templates',
			__( 'Navbar Apps', 'apollo-templates' ),
			__( 'Navbar Apps', 'apollo-templates' ),
			'manage_options',
			'apollo-navbar-apps',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register setting
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_apps' ),
				'default'           => self::get_defaults(),
			)
		);
	}

	/**
	 * Enqueue admin assets only on our page
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'templates_page_apollo-navbar-apps' !== $hook ) {
			return;
		}

		// Media uploader
		wp_enqueue_media();

		wp_enqueue_style(
			'apollo-navbar-settings',
			APOLLO_TEMPLATES_URL . 'assets/css/admin-navbar-settings.css',
			array(),
			APOLLO_TEMPLATES_VERSION
		);

		wp_enqueue_script(
			'apollo-navbar-settings',
			APOLLO_TEMPLATES_URL . 'assets/js/admin-navbar-settings.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-media-utils' ),
			APOLLO_TEMPLATES_VERSION,
			true
		);

		wp_localize_script(
			'apollo-navbar-settings',
			'apolloNavbarAdmin',
			array(
				'nonce'       => wp_create_nonce( 'apollo_navbar_admin' ),
				'uploadNonce' => wp_create_nonce( 'apollo_navbar_upload' ),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'defaultApp'  => array(
					'label'      => '',
					'url'        => '',
					'icon'       => 'ri-apps-fill',
					'bg_color'   => 'FF9820',
					'icon_color' => '#ffffff',
					'bg_image'   => '',
				),
				'i18n'        => array(
					'removeConfirm' => __( 'Remover este app?', 'apollo-templates' ),
					'selectImage'   => __( 'Selecionar imagem de fundo', 'apollo-templates' ),
					'useImage'      => __( 'Usar esta imagem', 'apollo-templates' ),
				),
			)
		);
	}

	/**
	 * AJAX — handle image upload
	 */
	public function ajax_upload_image(): void {
		check_ajax_referer( 'apollo_navbar_upload', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$attachment_id = absint( $_POST['attachment_id'] ?? 0 );
		if ( ! $attachment_id ) {
			wp_send_json_error( 'No attachment' );
		}

		$url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
		wp_send_json_success( array( 'url' => $url ) );
	}

	/**
	 * Sanitize apps array before saving
	 */
	public function sanitize_apps( $input ): array {
		if ( ! is_array( $input ) ) {
			return self::get_defaults();
		}

		$clean = array();

		foreach ( $input as $app ) {
			if ( empty( $app['label'] ) ) {
				continue;
			}

			$clean[] = array(
				'label'      => sanitize_text_field( $app['label'] ?? '' ),
				'url'        => esc_url_raw( $app['url'] ?? '' ),
				'icon'       => sanitize_text_field( $app['icon'] ?? 'ri-apps-fill' ),
				'bg_color'   => sanitize_hex_color( $app['bg_color'] ?? 'FF9820' ) ?: 'FF9820',
				'icon_color' => sanitize_hex_color( $app['icon_color'] ?? '#ffffff' ) ?: '#ffffff',
				'bg_image'   => esc_url_raw( $app['bg_image'] ?? '' ),
			);
		}

		return $clean;
	}

	/**
	 * Default apps list (matches current hardcoded navbar)
	 */
	public static function get_defaults(): array {
		return array(
			array(
				'label'      => 'Eventos',
				'url'        => '/eventos',
				'icon'       => 'i-apollo-ticket-s',
				'bg_color'   => 'FF9820',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
			array(
				'label'      => 'Classificados',
				'url'        => '/classificados',
				'icon'       => 'ri-p2p-fill',
				'bg_color'   => '#3b82f6',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
			array(
				'label'      => 'DJs',
				'url'        => '/djs',
				'icon'       => 'ri-contacts-fill',
				'bg_color'   => '#a855f7',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
			array(
				'label'      => 'Locais',
				'url'        => '/locais',
				'icon'       => 'ri-map-pin-user-fill',
				'bg_color'   => '#22c55e',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
			array(
				'label'      => 'Radar',
				'url'        => '/radar',
				'icon'       => 'ri-body-scan-fill',
				'bg_color'   => '#ec4899',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
			array(
				'label'      => 'Feed',
				'url'        => '/feed',
				'icon'       => 'ri-user-community-fill',
				'bg_color'   => 'FF9820',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
			array(
				'label'      => 'Comunas',
				'url'        => '/grupos',
				'icon'       => 'ri-group-fill',
				'bg_color'   => '#22c55e',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
			array(
				'label'      => 'Perfil',
				'url'        => '/id/{username}',
				'icon'       => 'ri-user-fill',
				'bg_color'   => '#64748b',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
			array(
				'label'      => 'Documentos',
				'url'        => '/documentos',
				'icon'       => 'ri-folder-3-fill',
				'bg_color'   => '#0ea5e9',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			),
		);
	}

	/**
	 * Get apps for frontend (with URL resolution)
	 */
	public static function get_apps(): array {
		$apps = get_option( self::OPTION_KEY, self::get_defaults() );

		if ( empty( $apps ) || ! is_array( $apps ) ) {
			$apps = self::get_defaults();
		}

		$is_logged_in = is_user_logged_in();
		$current_user = $is_logged_in ? wp_get_current_user() : null;

		// Resolve relative URLs and {username} placeholder
		foreach ( $apps as &$app ) {
			$url = $app['url'] ?? '';

			// Replace {username} placeholder
			if ( str_contains( $url, '{username}' ) ) {
				if ( $is_logged_in && $current_user ) {
					$url = str_replace( '{username}', $current_user->user_login, $url );
				} else {
					$url = '#';
				}
			}

			// Make relative URLs absolute
			if ( ! empty( $url ) && $url !== '#' && ! str_starts_with( $url, 'http' ) ) {
				$url = home_url( $url );
			}

			$app['url'] = $url;
		}
		unset( $app );

		return $apps;
	}

	/**
	 * Render admin settings page
	 */
	public function render_page(): void {
		$apps = get_option( self::OPTION_KEY, self::get_defaults() );
		if ( empty( $apps ) || ! is_array( $apps ) ) {
			$apps = self::get_defaults();
		}
		?>
<div class="wrap apollo-navbar-settings">
	<h1>
		<i class="dashicons dashicons-grid-view" style="margin-right:8px;"></i>
		<?php esc_html_e( 'Navbar Apps', 'apollo-templates' ); ?>
	</h1>
	<p class="description">
		<?php esc_html_e( 'Configure os aplicativos que aparecem no menu da navbar. Arraste para reordenar.', 'apollo-templates' ); ?>
	</p>

	<form method="post" action="options.php" id="apollo-navbar-form">
		<?php settings_fields( self::SETTINGS_GROUP ); ?>

		<div id="apollo-apps-list" class="apollo-apps-list">
			<?php foreach ( $apps as $index => $app ) : ?>
				<?php $this->render_app_row( $index, $app ); ?>
			<?php endforeach; ?>
		</div>

		<div class="apollo-apps-actions">
			<button type="button" id="apollo-add-app" class="button button-secondary">
				<span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span>
				<?php esc_html_e( 'Adicionar App', 'apollo-templates' ); ?>
			</button>

			<button type="button" id="apollo-reset-defaults" class="button button-link-delete">
				<?php esc_html_e( 'Restaurar Padrões', 'apollo-templates' ); ?>
			</button>
		</div>

		<?php submit_button( __( 'Salvar Configurações', 'apollo-templates' ) ); ?>
	</form>

	<!-- Preview -->
	<div class="apollo-preview-section">
		<h2><?php esc_html_e( 'Preview', 'apollo-templates' ); ?></h2>
		<div class="apollo-preview-grid" id="apollo-preview-grid">
			<!-- Populated by JS -->
		</div>
	</div>

	<!-- Template for new rows (hidden) -->
	<template id="apollo-app-row-template">
		<?php
		$this->render_app_row(
			'__INDEX__',
			array(
				'label'      => '',
				'url'        => '',
				'icon'       => 'ri-apps-fill',
				'bg_color'   => 'FF9820',
				'icon_color' => '#ffffff',
				'bg_image'   => '',
			)
		);
		?>
	</template>
</div>
		<?php
	}

	/**
	 * Render a single app row in the admin UI
	 */
	private function render_app_row( $index, array $app ): void {
		$name_prefix = self::OPTION_KEY . '[' . $index . ']';
		?>
<div class="apollo-app-row" data-index="<?php echo esc_attr( (string) $index ); ?>">
	<div class="app-row-handle" title="Arrastar para reordenar">
		<span class="dashicons dashicons-menu"></span>
	</div>

	<div class="app-row-preview">
		<div class="app-row-icon-preview"
			style="background:<?php echo esc_attr( $app['bg_color'] ?? 'FF9820' ); ?>;color:<?php echo esc_attr( $app['icon_color'] ?? '#ffffff' ); ?>;">
			<i class="<?php echo esc_attr( $app['icon'] ?? 'ri-apps-fill' ); ?>"></i>
		</div>
	</div>

	<div class="app-row-fields">
		<div class="field-row">
			<div class="field-group">
				<label><?php esc_html_e( 'Nome', 'apollo-templates' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[label]"
					value="<?php echo esc_attr( $app['label'] ?? '' ); ?>" placeholder="Ex: Eventos"
					class="regular-text app-field-label" />
			</div>

			<div class="field-group">
				<label><?php esc_html_e( 'URL', 'apollo-templates' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[url]"
					value="<?php echo esc_attr( $app['url'] ?? '' ); ?>" placeholder="/eventos ou https://..."
					class="regular-text app-field-url" />
			</div>

			<div class="field-group">
				<label><?php esc_html_e( 'Classe do Ícone', 'apollo-templates' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[icon]"
					value="<?php echo esc_attr( $app['icon'] ?? 'ri-apps-fill' ); ?>" placeholder="ri-apps-fill"
					class="regular-text app-field-icon" />
				<p class="description">
					<a href="https://remixicon.com" target="_blank" rel="noopener">RemixIcon</a>
					<?php esc_html_e( 'ou classe customizada (ex: i-apollo-ticket-s)', 'apollo-templates' ); ?>
				</p>
			</div>
		</div>

		<div class="field-row">
			<div class="field-group field-color">
				<label><?php esc_html_e( 'Cor de Fundo', 'apollo-templates' ); ?></label>
				<input type="color" name="<?php echo esc_attr( $name_prefix ); ?>[bg_color]"
					value="<?php echo esc_attr( $app['bg_color'] ?? 'FF9820' ); ?>" class="app-field-bg-color" />
			</div>

			<div class="field-group field-color">
				<label><?php esc_html_e( 'Cor do Ícone', 'apollo-templates' ); ?></label>
				<input type="color" name="<?php echo esc_attr( $name_prefix ); ?>[icon_color]"
					value="<?php echo esc_attr( $app['icon_color'] ?? '#ffffff' ); ?>" class="app-field-icon-color" />
			</div>

			<div class="field-group field-image">
				<label><?php esc_html_e( 'Imagem de Fundo', 'apollo-templates' ); ?></label>
				<div class="image-field-wrap">
					<input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[bg_image]"
						value="<?php echo esc_attr( $app['bg_image'] ?? '' ); ?>" placeholder="URL da imagem"
						class="regular-text app-field-bg-image" />
					<button type="button" class="button app-upload-image">
						<span class="dashicons dashicons-format-image" style="margin-top:3px;"></span>
					</button>
					<?php if ( ! empty( $app['bg_image'] ) ) : ?>
					<button type="button" class="button app-remove-image">
						<span class="dashicons dashicons-no" style="margin-top:3px;"></span>
					</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="app-row-actions">
		<button type="button" class="button app-remove-row"
			title="<?php esc_attr_e( 'Remover', 'apollo-templates' ); ?>">
			<span class="dashicons dashicons-trash" style="margin-top:3px;"></span>
		</button>
	</div>
</div>
		<?php
	}
}