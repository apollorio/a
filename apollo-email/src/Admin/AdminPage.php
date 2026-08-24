<?php
/**
 * Apollo Email — WP-Admin Page Controller.
 *
 * Registers menu "Email — Apollo" under the WordPress admin
 * with sub-pages: Dashboard, Templates, Fila, Log, Configurações.
 *
 * @package Apollo\Email\Admin
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\Admin;

use Apollo\Email\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminPage {

	private Plugin $plugin;
	private string $slug = 'apollo-email';
	private string $cap  = 'manage_options';
	private string $hook = '';

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Boot admin hooks.
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
		add_action( 'admin_post_apollo_email_test', array( $this, 'handleTestEmail' ) );
		add_action( 'admin_post_apollo_email_template_test', array( $this, 'handleTemplateTest' ) );
		add_action( 'admin_post_apollo_email_queue_action', array( $this, 'handleQueueAction' ) );
		add_action( 'admin_post_apollo_email_purge_queue', array( $this, 'handlePurgeQueue' ) );
		add_action( 'admin_post_apollo_email_purge_log', array( $this, 'handlePurgeLog' ) );
	}

	// ──────────────────────────────────────────────────────────────
	// MENU REGISTRATION
	// ──────────────────────────────────────────────────────────────

	public function registerMenu(): void {
		$this->hook = add_menu_page(
			__( 'Email — Apollo', 'apollo-email' ),
			__( 'Email', 'apollo-email' ),
			$this->cap,
			$this->slug,
			array( $this, 'renderDashboard' ),
			'dashicons-email-alt2',
			58
		);

		add_submenu_page(
			$this->slug,
			__( 'Dashboard', 'apollo-email' ),
			__( 'Dashboard', 'apollo-email' ),
			$this->cap,
			$this->slug,
			array( $this, 'renderDashboard' )
		);

		add_submenu_page(
			$this->slug,
			__( 'Templates', 'apollo-email' ),
			__( 'Templates', 'apollo-email' ),
			$this->cap,
			$this->slug . '-templates',
			array( $this, 'renderTemplates' )
		);

		add_submenu_page(
			$this->slug,
			__( 'Fila de Envio', 'apollo-email' ),
			__( 'Fila de Envio', 'apollo-email' ),
			$this->cap,
			$this->slug . '-queue',
			array( $this, 'renderQueue' )
		);

		add_submenu_page(
			$this->slug,
			__( 'Log de Envios', 'apollo-email' ),
			__( 'Log de Envios', 'apollo-email' ),
			$this->cap,
			$this->slug . '-log',
			array( $this, 'renderLog' )
		);

		add_submenu_page(
			$this->slug,
			__( 'Configurações', 'apollo-email' ),
			__( 'Configurações', 'apollo-email' ),
			$this->cap,
			$this->slug . '-settings',
			array( $this, 'renderSettings' )
		);
	}

	// ──────────────────────────────────────────────────────────────
	// SETTINGS API
	// ──────────────────────────────────────────────────────────────

	public function registerSettings(): void {
		register_setting(
			'apollo_email_settings',
			'apollo_email_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitizeSettings' ),
			)
		);

		// Section: General
		add_settings_section( 'apollo_email_general', __( 'Geral', 'apollo-email' ), '__return_empty_string', 'apollo_email_settings' );

		$this->addField( 'from_name', __( 'Nome do Remetente', 'apollo-email' ), 'renderTextField', 'apollo_email_general' );
		$this->addField( 'from_email', __( 'E-mail do Remetente', 'apollo-email' ), 'renderEmailField', 'apollo_email_general' );
		$this->addField( 'reply_to', __( 'Responder Para', 'apollo-email' ), 'renderEmailField', 'apollo_email_general' );

		// Section: Transport
		add_settings_section( 'apollo_email_transport', __( 'Transporte', 'apollo-email' ), '__return_empty_string', 'apollo_email_settings' );

		$this->addField( 'transport', __( 'Método de Envio', 'apollo-email' ), 'renderTransportSelect', 'apollo_email_transport' );
		$this->addField( 'smtp_host', __( 'SMTP Host', 'apollo-email' ), 'renderTextField', 'apollo_email_transport' );
		$this->addField( 'smtp_port', __( 'SMTP Porta', 'apollo-email' ), 'renderNumberField', 'apollo_email_transport' );
		$this->addField( 'smtp_username', __( 'SMTP Usuário', 'apollo-email' ), 'renderTextField', 'apollo_email_transport' );
		$this->addField( 'smtp_password', __( 'SMTP Senha', 'apollo-email' ), 'renderPasswordField', 'apollo_email_transport' );
		$this->addField( 'smtp_encryption', __( 'SMTP Criptografia', 'apollo-email' ), 'renderEncryptionSelect', 'apollo_email_transport' );
		$this->addField( 'ses_region', __( 'SES Região', 'apollo-email' ), 'renderTextField', 'apollo_email_transport' );
		$this->addField( 'ses_key', __( 'SES Access Key', 'apollo-email' ), 'renderTextField', 'apollo_email_transport' );
		$this->addField( 'ses_secret', __( 'SES Secret Key', 'apollo-email' ), 'renderPasswordField', 'apollo_email_transport' );
		$this->addField( 'sendgrid_key', __( 'SendGrid API Key', 'apollo-email' ), 'renderPasswordField', 'apollo_email_transport' );

		// Section: Tracking
		add_settings_section( 'apollo_email_tracking', __( 'Rastreamento', 'apollo-email' ), '__return_empty_string', 'apollo_email_settings' );

		$this->addField( 'track_opens', __( 'Rastrear Aberturas', 'apollo-email' ), 'renderToggleField', 'apollo_email_tracking' );
		$this->addField( 'track_clicks', __( 'Rastrear Cliques', 'apollo-email' ), 'renderToggleField', 'apollo_email_tracking' );

		// Section: Branding
		add_settings_section( 'apollo_email_branding', __( 'Identidade Visual', 'apollo-email' ), '__return_empty_string', 'apollo_email_settings' );

		$this->addField( 'brand_color', __( 'Cor Principal', 'apollo-email' ), 'renderColorField', 'apollo_email_branding' );
		$this->addField( 'footer_text', __( 'Texto do Rodapé', 'apollo-email' ), 'renderTextareaField', 'apollo_email_branding' );
	}

	private function addField( string $key, string $label, string $callback, string $section ): void {
		add_settings_field(
			'apollo_email_' . $key,
			$label,
			array( $this, $callback ),
			'apollo_email_settings',
			$section,
			array( 'key' => $key )
		);
	}

	// ──────────────────────────────────────────────────────────────
	// FIELD RENDERERS
	// ──────────────────────────────────────────────────────────────

	public function renderTextField( array $args ): void {
		$value = $this->plugin->setting( $args['key'], '' );
		printf(
			'<input type="text" name="apollo_email_settings[%s]" value="%s" class="regular-text" />',
			esc_attr( $args['key'] ),
			esc_attr( $value )
		);
	}

	public function renderEmailField( array $args ): void {
		$value = $this->plugin->setting( $args['key'], '' );
		printf(
			'<input type="email" name="apollo_email_settings[%s]" value="%s" class="regular-text" />',
			esc_attr( $args['key'] ),
			esc_attr( $value )
		);
	}

	public function renderNumberField( array $args ): void {
		$value = $this->plugin->setting( $args['key'], '' );
		printf(
			'<input type="number" name="apollo_email_settings[%s]" value="%s" class="small-text" />',
			esc_attr( $args['key'] ),
			esc_attr( $value )
		);
	}

	public function renderPasswordField( array $args ): void {
		$value = $this->plugin->setting( $args['key'], '' );
		printf(
			'<input type="password" name="apollo_email_settings[%s]" value="%s" class="regular-text" autocomplete="off" />',
			esc_attr( $args['key'] ),
			esc_attr( $value )
		);
	}

	public function renderTextareaField( array $args ): void {
		$value = $this->plugin->setting( $args['key'], '' );
		printf(
			'<textarea name="apollo_email_settings[%s]" rows="3" class="large-text">%s</textarea>',
			esc_attr( $args['key'] ),
			esc_textarea( $value )
		);
	}

	public function renderColorField( array $args ): void {
		$value = $this->plugin->setting( $args['key'], '#6C3BF5' );
		printf(
			'<input type="color" name="apollo_email_settings[%s]" value="%s" />',
			esc_attr( $args['key'] ),
			esc_attr( $value )
		);
	}

	public function renderToggleField( array $args ): void {
		$value = $this->plugin->setting( $args['key'], false );
		printf(
			'<label><input type="checkbox" name="apollo_email_settings[%s]" value="1" %s /> %s</label>',
			esc_attr( $args['key'] ),
			checked( $value, true, false ),
			esc_html__( 'Ativado', 'apollo-email' )
		);
	}

	public function renderTransportSelect( array $args ): void {
		$value   = $this->plugin->setting( $args['key'], 'wp_mail' );
		$options = array(
			'wp_mail'  => 'WordPress (wp_mail)',
			'smtp'     => 'SMTP',
			'ses'      => 'Amazon SES',
			'sendgrid' => 'SendGrid',
		);

		echo '<select name="apollo_email_settings[' . esc_attr( $args['key'] ) . ']">';
		foreach ( $options as $k => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $k ),
				selected( $value, $k, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	public function renderEncryptionSelect( array $args ): void {
		$value   = $this->plugin->setting( $args['key'], 'tls' );
		$options = array(
			'none' => __( 'Nenhuma', 'apollo-email' ),
			'ssl'  => 'SSL',
			'tls'  => 'TLS',
		);

		echo '<select name="apollo_email_settings[' . esc_attr( $args['key'] ) . ']">';
		foreach ( $options as $k => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $k ),
				selected( $value, $k, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/**
	 * Sanitize settings on save.
	 */
	public function sanitizeSettings( array $input ): array {
		if ( isset( $input['smtp_user'] ) && ! isset( $input['smtp_username'] ) ) {
			$input['smtp_username'] = $input['smtp_user'];
		}
		if ( isset( $input['smtp_pass'] ) && ! isset( $input['smtp_password'] ) ) {
			$input['smtp_password'] = $input['smtp_pass'];
		}

		$sanitized    = array();
		$text_fields  = array( 'from_name', 'smtp_host', 'smtp_username', 'smtp_password', 'ses_region', 'ses_key', 'ses_secret', 'sendgrid_key', 'transport', 'smtp_encryption' );
		$email_fields = array( 'from_email', 'reply_to' );
		$int_fields   = array( 'smtp_port' );
		$bool_fields  = array( 'track_opens', 'track_clicks' );
		$html_fields  = array( 'footer_text' );

		foreach ( $text_fields as $key ) {
			$sanitized[ $key ] = sanitize_text_field( $input[ $key ] ?? '' );
		}

		foreach ( $email_fields as $key ) {
			$sanitized[ $key ] = sanitize_email( $input[ $key ] ?? '' );
		}

		foreach ( $int_fields as $key ) {
			$sanitized[ $key ] = absint( $input[ $key ] ?? 0 );
		}

		foreach ( $bool_fields as $key ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] );
		}

		foreach ( $html_fields as $key ) {
			$sanitized[ $key ] = wp_kses_post( $input[ $key ] ?? '' );
		}

		if ( isset( $input['brand_color'] ) ) {
			$sanitized['brand_color'] = sanitize_hex_color( $input['brand_color'] ) ?: '#6C3BF5';
		}

		return $sanitized;
	}

	// ──────────────────────────────────────────────────────────────
	// PAGE RENDERERS
	// ──────────────────────────────────────────────────────────────

	public function renderDashboard(): void {
		$this->loadView(
			'dashboard',
			array(
				'log_stats'   => $this->plugin->logger()->getStats( 30 ),
				'queue_stats' => $this->plugin->queue()->getStats(),
				'daily_stats' => $this->plugin->logger()->getDailyStats( 14 ),
				'templates'   => count( $this->plugin->templates()->getTemplates() ),
				'settings'    => get_option( 'apollo_email_settings', array() ),
			)
		);
	}

	public function renderTemplates(): void {
		$this->loadView(
			'templates',
			array(
				'templates' => $this->plugin->templates()->getTemplates(),
			)
		);
	}

	public function renderQueue(): void {
		$status   = sanitize_text_field( $_GET['status'] ?? '' );
		$paged    = absint( $_GET['paged'] ?? 1 );
		$per_page = 20;

		$this->loadView(
			'queue',
			array(
				'items'  => $this->plugin->queue()->getItems( $status, $per_page, $paged ),
				'stats'  => $this->plugin->queue()->getStats(),
				'status' => $status,
				'paged'  => $paged,
			)
		);
	}

	public function renderLog(): void {
		$paged = absint( $_GET['paged'] ?? 1 );

		$this->loadView(
			'log',
			array(
				'entries' => $this->plugin->logger()->getEntries(
					array(
						'status'   => sanitize_text_field( $_GET['status'] ?? '' ),
						'email'    => sanitize_text_field( $_GET['email'] ?? '' ),
						'template' => sanitize_text_field( $_GET['template'] ?? '' ),
						'per_page' => 20,
						'page'     => $paged,
					)
				),
				'stats'   => $this->plugin->logger()->getStats( 30 ),
				'paged'   => $paged,
			)
		);
	}

	public function renderSettings(): void {
		$this->loadView( 'settings' );
	}

	// ──────────────────────────────────────────────────────────────
	// FORM HANDLERS
	// ──────────────────────────────────────────────────────────────

	/**
	 * Handle test email submission.
	 */
	public function handleTestEmail(): void {
		check_admin_referer( 'apollo_email_test_nonce' );

		if ( ! current_user_can( $this->cap ) ) {
			wp_die( __( 'Acesso negado.', 'apollo-email' ) );
		}

		$to     = sanitize_email( $_POST['test_email'] ?? '' );
		$result = $this->plugin->sender()->sendTest( $to );

		$redirect = add_query_arg(
			array(
				'page'       => $this->slug,
				'test_sent'  => $result['success'] ? 1 : 0,
				'test_error' => $result['error'] ?? '',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle per-template test email submission.
	 */
	public function handleTemplateTest(): void {
		$slug = sanitize_text_field( $_POST['template_slug'] ?? '' );

		check_admin_referer( 'apollo_email_template_test_' . $slug );

		if ( ! current_user_can( $this->cap ) ) {
			wp_die( __( 'Acesso negado.', 'apollo-email' ) );
		}

		$to = sanitize_email( wp_unslash( $_POST['test_email'] ?? '' ) );
		if ( ! is_email( $to ) ) {
			$to = sanitize_email( wp_get_current_user()->user_email );
		}
		$payload = $this->getTestPayload( $slug );

		if ( empty( $payload ) ) {
			$result = array( 'success' => false, 'error' => 'Template desconhecido: ' . $slug );
		} else {
			$result = $this->plugin->sender()->sendTemplate( $to, $payload['subject'], $slug, $payload['data'] );
		}

		$redirect = add_query_arg(
			array(
				'page'       => $this->slug . '-templates',
				'test_sent'  => $result['success'] ? 1 : 0,
				'test_slug'  => $slug,
				'test_to'    => $to,
				'test_error' => $result['error'] ?? '',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Return sample payload for a given template slug.
	 *
	 * @param string $slug Template slug.
	 * @return array{subject: string, data: array}|array Empty if slug unknown.
	 */
	private function getTestPayload( string $slug ): array {
		$user      = wp_get_current_user();
		$user_name = $user->display_name ?: $user->user_login;
		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url( '/' );
		$now       = wp_date( 'd/m/Y H:i' );

		$payloads = array(
			'verification'   => array(
				'subject' => '[Apollo Teste] Confirme seu email',
				'data'    => array(
					'user_id'           => $user->ID,
					'user_name'         => $user_name,
					'username'          => $user->user_login,
					'user_email'        => $user->user_email,
					'verify_url'        => home_url( '/verificar-email/?user=' . $user->ID . '&token=TEST_TOKEN' ),
					'confirmation_link' => home_url( '/verificar-email/?user=' . $user->ID . '&token=TEST_TOKEN' ),
					'registration_date' => wp_date( 'd/m/Y', strtotime( $user->user_registered ) ),
					'expires_in'        => '24 horas',
					'expiration_time'   => '24 horas',
					'site_name'         => $site_name,
					'site_url'          => $site_url,
				),
			),
			'welcome'        => array(
				'subject' => '[Apollo Teste] Bem-vindo(a)!',
				'data'    => array(
					'user_id'      => $user->ID,
					'user_name'    => $user_name,
					'username'     => $user->user_login,
					'user_email'   => $user->user_email,
					'profile_url'  => home_url( '/id/' . $user->user_login ),
					'plan_name'    => 'Apollo Gratuito',
					'member_since' => wp_date( 'd/m/Y', strtotime( $user->user_registered ) ),
					'site_name'    => $site_name,
					'site_url'     => $site_url,
				),
			),
			'password-reset' => array(
				'subject' => '[Apollo Teste] Redefinir senha',
				'data'    => array(
					'user_id'           => $user->ID,
					'user_name'         => $user_name,
					'username'          => $user->user_login,
					'reset_url'         => home_url( '/acesso/?action=reset&key=TEST_TOKEN&login=' . rawurlencode( $user->user_login ) ),
					'confirmation_link' => home_url( '/acesso/?action=reset&key=TEST_TOKEN&login=' . rawurlencode( $user->user_login ) ),
					'expires_in'        => '1 hora',
					'expiration_time'   => '1 hora',
					'user_email'        => $user->user_email,
					'registration_date' => wp_date( 'd/m/Y', strtotime( $user->user_registered ) ),
					'site_name'         => $site_name,
				),
			),
			'notification'   => array(
				'subject' => '[Apollo Teste] Comunicado da plataforma',
				'data'    => array(
					'user_id'     => $user->ID,
					'user_name'   => $user_name,
					'title'       => 'Novidades Apollo 2026',
					'message'     => 'Este é um email de teste enviado em ' . $now . '.',
					'action_url'  => $site_url,
					'action_text' => 'Explorar Plataforma',
					'site_name'   => $site_name,
				),
			),
			'digest'         => array(
				'subject' => '[Apollo Teste] Seu resumo semanal',
				'data'    => array(
					'user_id'         => $user->ID,
					'user_name'       => $user_name,
					'digest_title'    => 'Resumo da Semana',
					'digest_intro'    => 'O que aconteceu na plataforma esta semana:',
					'digest_sections' => array(
						array(
							'title' => 'Social',
							'items' => array(
								array( 'heading' => '5 novas conexões', 'message' => 'DJs e produtores entraram na comunidade.' ),
							),
						),
					),
					'site_name' => $site_name,
					'site_url'  => $site_url,
				),
			),
			'event-reminder' => array(
				'subject' => '[Apollo Teste] Lembrete de evento',
				'data'    => array(
					'user_name'  => $user_name,
					'event_name' => 'Festa Teste Apollo',
					'event_date' => wp_date( 'd/m/Y', strtotime( '+3 days' ) ),
					'event_time' => '23:00',
					'event_url'  => home_url( '/eventos/festa-teste/' ),
					'loc_name' => 'Lapa Underground',
					'site_name'  => $site_name,
				),
			),
			'task-deadline'  => array(
				'subject' => '[Apollo Teste] Prazo de tarefa',
				'data'    => array(
					'user_name'     => $user_name,
					'task_title'    => 'Confirmar lineup DJ',
					'task_deadline' => wp_date( 'd/m/Y', strtotime( '+1 day' ) ),
					'task_url'      => home_url( '/gestor/' ),
					'project_name'  => 'Evento Teste',
					'site_name'     => $site_name,
				),
			),
			'report'         => array(
				'subject' => '[Apollo Teste] Relatório',
				'data'    => array(
					'user_name'    => $user_name,
					'report_title' => 'Relatório Mensal',
					'report_date'  => wp_date( 'd/m/Y' ),
					'report_url'   => home_url( '/admin/' ),
					'summary'      => 'Este é um relatório de teste gerado automaticamente.',
					'site_name'    => $site_name,
				),
			),
		);

		return $payloads[ $slug ] ?? array();
	}

	/**
	 * Handle queue actions (cancel / retry).
	 */
	public function handleQueueAction(): void {
		check_admin_referer( 'apollo_email_queue_action' );

		if ( ! current_user_can( $this->cap ) ) {
			wp_die( __( 'Acesso negado.', 'apollo-email' ) );
		}

		$action = sanitize_text_field( $_POST['queue_action'] ?? '' );
		$id     = absint( $_POST['queue_id'] ?? 0 );

		if ( $action === 'cancel' ) {
			$this->plugin->queue()->cancel( $id );
		} elseif ( $action === 'retry' ) {
			$this->plugin->queue()->retry( $id );
		}

		wp_safe_redirect( add_query_arg( 'page', $this->slug . '-queue', admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handle queue purge.
	 */
	public function handlePurgeQueue(): void {
		check_admin_referer( 'apollo_email_purge_queue' );

		if ( ! current_user_can( $this->cap ) ) {
			wp_die( __( 'Acesso negado.', 'apollo-email' ) );
		}

		$days    = absint( $_POST['purge_days'] ?? 30 );
		$deleted = $this->plugin->queue()->purge( $days );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => $this->slug . '-queue',
					'purged' => $deleted,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle log purge.
	 */
	public function handlePurgeLog(): void {
		check_admin_referer( 'apollo_email_purge_log' );

		if ( ! current_user_can( $this->cap ) ) {
			wp_die( __( 'Acesso negado.', 'apollo-email' ) );
		}

		$days    = absint( $_POST['purge_days'] ?? 90 );
		$deleted = $this->plugin->logger()->purge( $days );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => $this->slug . '-log',
					'purged' => $deleted,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// ──────────────────────────────────────────────────────────────
	// VIEW LOADER
	// ──────────────────────────────────────────────────────────────

	private function loadView( string $view, array $data = array() ): void {
        // phpcs:ignore WordPress.PHP.DontExtract
		extract( $data, EXTR_SKIP );

		$file = APOLLO_EMAIL_PATH . 'views/admin/' . $view . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'Erro', 'apollo-email' ) . '</h1>';
			echo '<p>' . esc_html__( 'View não encontrada.', 'apollo-email' ) . '</p></div>';
		}
	}
}