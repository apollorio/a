<?php
/**
 * Screens 1–5 under apollo-admin. Views only render.
 * This run wires screen 1 (settings + embedded session strip) only.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Admin {

	private static ?self $instance = null;

	private const PAGE_SLUG   = 'apollo-wa-settings';
	private const SAVE_ACTION = 'apollo_wa_save_settings';
	private const TEST_ACTION = 'apollo_wa_test_connection';

	/** Hook suffix returned by add_menu_page()/add_submenu_page(), for asset scoping. */
	private ?string $hook_suffix = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ), 25 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_' . self::TEST_ACTION, array( $this, 'handle_test' ) );
	}

	/**
	 * Nests under apollo-admin's top-level "apollo" menu
	 * (see plugins/apollo-admin/src/AdminPage.php:66) when that plugin is
	 * active and has already registered it; falls back to a standalone
	 * top-level menu otherwise. Same pattern as apollo-radio's Settings.php.
	 */
	public function menu(): void {
		$cap = $this->capability();

		if ( function_exists( 'menu_page_url' ) && menu_page_url( 'apollo', false ) ) {
			$this->hook_suffix = (string) add_submenu_page(
				'apollo',
				__( 'Apollo WhatsApp', 'apollo-waha' ),
				__( 'WhatsApp', 'apollo-waha' ),
				$cap,
				self::PAGE_SLUG,
				array( $this, 'render_settings' )
			);
			return;
		}

		$this->hook_suffix = (string) add_menu_page(
			__( 'Apollo WhatsApp', 'apollo-waha' ),
			__( 'Apollo WhatsApp', 'apollo-waha' ),
			$cap,
			self::PAGE_SLUG,
			array( $this, 'render_settings' ),
			'dashicons-whatsapp'
		);
	}

	public function assets( string $hook ): void {
		if ( null === $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}
		wp_enqueue_style( 'apollo-waha-admin', plugins_url( 'assets/admin.css', APOLLO_WAHA_FILE ), array(), APOLLO_WAHA_VER );
		wp_enqueue_script( 'apollo-waha-admin', plugins_url( 'assets/admin.js', APOLLO_WAHA_FILE ), array(), APOLLO_WAHA_VER, true );
	}

	/**
	 * apollo_manage_whatsapp when the current user holds it, else the
	 * registry's declared fallback manage_options — see
	 * waha-registry.json plugin.capability / capability_fallback.
	 */
	public function capability(): string {
		return current_user_can( 'apollo_manage_whatsapp' ) ? 'apollo_manage_whatsapp' : 'manage_options';
	}

	private function can_manage(): bool {
		return current_user_can( 'apollo_manage_whatsapp' ) || current_user_can( 'manage_options' );
	}

	public function render_settings(): void {
		include APOLLO_WAHA_DIR . 'admin/views/settings.php';
	}

	public function render_queue(): void {
		include APOLLO_WAHA_DIR . 'admin/views/queue.php';
	}

	public function render_pane(): void {
		include APOLLO_WAHA_DIR . 'admin/views/pane.php';
	}

	public function render_flows(): void {
		include APOLLO_WAHA_DIR . 'admin/views/flows.php';
	}

	/**
	 * admin-post handler for Tela 1's form. Generates the HMAC secret on
	 * first save only — does not implement webhook verify() (Run 2).
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), self::SAVE_ACTION ) ) {
			wp_die( esc_html__( 'Sessão expirada. Recarregue e tente novamente.', 'apollo-waha' ), '', array( 'response' => 403 ) );
		}
		if ( ! $this->can_manage() ) {
			wp_die( esc_html__( 'Sem permissão.', 'apollo-waha' ), '', array( 'response' => 403 ) );
		}

		$base_url = isset( $_POST['apollo_wa_base_url'] ) ? esc_url_raw( wp_unslash( $_POST['apollo_wa_base_url'] ) ) : '';
		$session  = isset( $_POST['apollo_wa_session'] ) ? sanitize_key( wp_unslash( $_POST['apollo_wa_session'] ) ) : '';
		$group_id = isset( $_POST['apollo_wa_group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['apollo_wa_group_id'] ) ) : '';
		$api_key  = isset( $_POST['apollo_wa_api_key'] ) ? trim( (string) wp_unslash( $_POST['apollo_wa_api_key'] ) ) : '';

		Apollo_Waha_Options::set( 'apollo_wa_base_url', $base_url ?: 'http://127.0.0.1:3000' );
		Apollo_Waha_Options::set( 'apollo_wa_session', $session ?: 'apollo' );
		Apollo_Waha_Options::set( 'apollo_wa_group_id', $group_id );

		// Blank field = keep existing key. Never round-trip the secret through the input value.
		if ( '' !== $api_key ) {
			Apollo_Waha_Options::set( 'apollo_wa_api_key', $api_key );
		}

		// First save only — webhook verify() does not read this yet (Run 2).
		if ( '' === (string) Apollo_Waha_Options::get( 'apollo_wa_hmac_key', '' ) ) {
			Apollo_Waha_Options::set( 'apollo_wa_hmac_key', wp_generate_password( 48, true, true ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX "Testar": forces a fresh session ping. Never returns the API key.
	 */
	public function handle_test(): void {
		check_ajax_referer( self::TEST_ACTION, 'nonce' );
		if ( ! $this->can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'apollo-waha' ) ), 403 );
		}

		$session = Apollo_Waha_Session::instance();
		$result  = $session->ping( true );
		$label   = $session->status_label( false, $result );

		wp_send_json(
			array(
				'ok'      => (bool) $result['ok'],
				'http'    => (int) $result['status'],
				'status'  => $label,
				'working' => $result['ok'] && 'WORKING' === $label,
			)
		);
	}
}
