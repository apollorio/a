<?php
/**
 * Apollo Admin Settings — CRUD for the single serialized option.
 *
 * All settings live in a single WP option (APOLLO_ADMIN_OPTION_KEY)
 * structured as: [ 'plugin-slug' => [ 'key' => 'value', ... ], ... ]
 *
 * Inspired by UiPress `UipOptions::get/update` pattern.
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	private static ?Settings $instance = null;

	/** @var array<string, array> Full settings blob */
	private array $data = array();

	private bool $loaded = false;

	public static function get_instance(): Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Initialize — load from DB once
	 */
	public function init(): void {
		$this->load();
	}

	/* ───────────────────────────── READ ──────────────────────────────── */

	/**
	 * Get the entire settings blob
	 */
	public function all(): array {
		$this->ensure_loaded();
		return $this->data;
	}

	/**
	 * Get all settings for a specific plugin
	 */
	public function for_plugin( string $slug ): array {
		$this->ensure_loaded();
		return $this->data[ $slug ] ?? array();
	}

	/**
	 * Get a single setting value
	 */
	public function get( string $slug, string $key, mixed $default = null ): mixed {
		$this->ensure_loaded();
		return $this->data[ $slug ][ $key ] ?? $default;
	}

	/* ───────────────────────────── WRITE ─────────────────────────────── */

	/**
	 * Update a single setting for a specific plugin
	 */
	public function set( string $slug, string $key, mixed $value ): bool {
		$this->ensure_loaded();
		if ( ! isset( $this->data[ $slug ] ) ) {
			$this->data[ $slug ] = array();
		}
		$this->data[ $slug ][ $key ] = $value;
		return $this->save();
	}

	/**
	 * Bulk update all settings for a specific plugin
	 */
	public function update_plugin( string $slug, array $values ): bool {
		$this->ensure_loaded();
		$existing            = $this->data[ $slug ] ?? array();
		$this->data[ $slug ] = array_merge( $existing, $values );
		return $this->save();
	}

	/**
	 * Replace all settings for a specific plugin (destructive)
	 */
	public function replace_plugin( string $slug, array $values ): bool {
		$this->ensure_loaded();
		$this->data[ $slug ] = $values;
		return $this->save();
	}

	/**
	 * Delete all settings for a specific plugin
	 */
	public function delete_plugin( string $slug ): bool {
		$this->ensure_loaded();
		unset( $this->data[ $slug ] );
		return $this->save();
	}

	/**
	 * Delete a single setting key for a plugin
	 */
	public function delete( string $slug, string $key ): bool {
		$this->ensure_loaded();
		unset( $this->data[ $slug ][ $key ] );
		return $this->save();
	}

	/* ───────────────────────────── EXPORT / IMPORT ───────────────────── */

	/**
	 * Export all settings as JSON string
	 */
	public function export(): string {
		$this->ensure_loaded();
		return wp_json_encode( $this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Import settings from JSON string (merge, not replace)
	 */
	public function import( string $json ): bool {
		$incoming = json_decode( $json, true );
		if ( ! is_array( $incoming ) ) {
			return false;
		}
		$this->ensure_loaded();
		foreach ( $incoming as $slug => $settings ) {
			if ( is_array( $settings ) ) {
				$this->data[ $slug ] = array_merge(
					$this->data[ $slug ] ?? array(),
					$settings
				);
			}
		}
		return $this->save();
	}

	/* ───────────────────────────── DEFAULT SCHEMAS ───────────────────── */

	/**
	 * Get the default settings schema for a given plugin slug.
	 * This defines what fields each plugin's tab should render.
	 *
	 * @return array<string, array{type:string,label:string,default:mixed,options?:array}>
	 */
	public static function get_schema( string $slug ): array {
		// Global settings (the _global tab)
		$schemas = array(
			'_global'                => array(
				'brand_name'  => array(
					'type'    => 'text',
					'label'   => 'Projeto apollo::rio v2.0.0',
					'default' => 'Apollo',
				),
				'brand_color' => array(
					'type'    => 'color',
					'label'   => 'Cor primária',
					'default' => '#6366f1',
				),
				'dark_mode'   => array(
					'type'    => 'toggle',
					'label'   => 'Modo escuro',
					'default' => false,
				),
				'compact_ui'  => array(
					'type'    => 'toggle',
					'label'   => 'Interface compacta',
					'default' => false,
				),
			),
			'apollo-core'            => array(
				'debug_mode'           => array(
					'type'    => 'toggle',
					'label'   => 'Debug mode',
					'default' => false,
				),
				'cleanup_on_uninstall' => array(
					'type'    => 'toggle',
					'label'   => 'Apagar tudo ao desinstalar',
					'default' => false,
				),
			),
			'apollo-login'           => array(
				'enable_registration' => array(
					'type'    => 'toggle',
					'label'   => 'Permitir registro',
					'default' => true,
				),
				'registration_quiz'   => array(
					'type'    => 'toggle',
					'label'   => 'Quiz no registro',
					'default' => true,
				),
				'login_redirect'      => array(
					'type'    => 'text',
					'label'   => 'Redirect após login',
					'default' => '/',
				),
				'disable_wp_login'    => array(
					'type'    => 'toggle',
					'label'   => 'Ocultar wp-login.php',
					'default' => true,
				),
			),
			'apollo-users'           => array(
				'enable_radar'       => array(
					'type'    => 'toggle',
					'label'   => 'Habilitar radar',
					'default' => true,
				),
				'enable_matchmaking' => array(
					'type'    => 'toggle',
					'label'   => 'Matchmaking',
					'default' => false,
				),
				'default_role'       => array(
					'type'    => 'text',
					'label'   => 'Role padrão',
					'default' => 'subscriber',
				),
			),
			'apollo-membership'      => array(
				'enable_points'   => array(
					'type'    => 'toggle',
					'label'   => 'Sistema de pontos',
					'default' => true,
				),
				'enable_badges'   => array(
					'type'    => 'toggle',
					'label'   => 'Badges',
					'default' => true,
				),
				'enable_ranks'    => array(
					'type'    => 'toggle',
					'label'   => 'Ranks',
					'default' => true,
				),
				'points_per_post' => array(
					'type'    => 'number',
					'label'   => 'Pontos por post',
					'default' => 10,
				),
			),
			'apollo-social'          => array(
				'enable_activity'  => array(
					'type'    => 'toggle',
					'label'   => 'Activity stream',
					'default' => true,
				),
				'enable_follow'    => array(
					'type'    => 'toggle',
					'label'   => 'Seguir/block',
					'default' => true,
				),
				'enable_reactions' => array(
					'type'    => 'toggle',
					'label'   => 'Reactions',
					'default' => true,
				),
			),
			'apollo-chat'            => array(
				'enable_dm'          => array(
					'type'    => 'toggle',
					'label'   => 'Mensagens diretas',
					'default' => true,
				),
				'enable_group_chat'  => array(
					'type'    => 'toggle',
					'label'   => 'Chat em grupo',
					'default' => true,
				),
				'max_message_length' => array(
					'type'    => 'number',
					'label'   => 'Max chars por msg',
					'default' => 2000,
				),
			),
			'apollo-groups'          => array(
				'enable_groups'       => array(
					'type'    => 'toggle',
					'label'   => 'Habilitar grupos',
					'default' => true,
				),
				'max_groups_per_user' => array(
					'type'    => 'number',
					'label'   => 'Max grupos por user',
					'default' => 10,
				),
			),
			'apollo-email'           => array(
				'from_name'    => array(
					'type'    => 'text',
					'label'   => 'Nome do remetente',
					'default' => 'Apollo',
				),
				'from_email'   => array(
					'type'    => 'email',
					'label'   => 'Email do remetente',
					'default' => '',
				),
				'enable_queue' => array(
					'type'    => 'toggle',
					'label'   => 'Fila de emails',
					'default' => true,
				),
				'smtp_host'    => array(
					'type'    => 'text',
					'label'   => 'SMTP Host',
					'default' => '',
				),
				'smtp_port'    => array(
					'type'    => 'number',
					'label'   => 'SMTP Port',
					'default' => 587,
				),
			),
			'apollo-events'          => array(
				'enable_rsvp'      => array(
					'type'    => 'toggle',
					'label'   => 'RSVP',
					'default' => true,
				),
				'enable_tickets'   => array(
					'type'    => 'toggle',
					'label'   => 'Tickets',
					'default' => false,
				),
				'enable_calendar'  => array(
					'type'    => 'toggle',
					'label'   => 'Calendário',
					'default' => true,
				),
				'default_currency' => array(
					'type'    => 'text',
					'label'   => 'Moeda padrão',
					'default' => 'BRL',
				),
			),
			'apollo-djs'             => array(
				'enable_profiles' => array(
					'type'    => 'toggle',
					'label'   => 'DJ Profiles',
					'default' => true,
				),
				'enable_carousel' => array(
					'type'    => 'toggle',
					'label'   => 'Carousel view',
					'default' => true,
				),
			),
			'apollo-loc'             => array(
				'enable_maps'      => array(
					'type'    => 'toggle',
					'label'   => 'Google Maps',
					'default' => true,
				),
				'enable_geocoding' => array(
					'type'    => 'toggle',
					'label'   => 'Geocoding',
					'default' => true,
				),
				'default_city'     => array(
					'type'    => 'text',
					'label'   => 'Cidade padrão',
					'default' => 'Rio de Janeiro',
				),
			),
			'apollo-notif'           => array(
				'enable_push'         => array(
					'type'    => 'toggle',
					'label'   => 'Push notifications',
					'default' => false,
				),
				'enable_email_digest' => array(
					'type'    => 'toggle',
					'label'   => 'Email digest',
					'default' => true,
				),
				'digest_frequency'    => array(
					'type'    => 'select',
					'label'   => 'Frequência do digest',
					'default' => 'weekly',
					'options' => array(
						'daily'   => 'Diário',
						'weekly'  => 'Semanal',
						'monthly' => 'Mensal',
					),
				),
			),
			'apollo-dashboard'       => array(
				'enable_quick_publish' => array(
					'type'    => 'toggle',
					'label'   => 'Quick publish',
					'default' => true,
				),
				'enable_mod_queue'     => array(
					'type'    => 'toggle',
					'label'   => 'Fila de moderação',
					'default' => true,
				),
			),
			'apollo-shortcodes'      => array(
				'enable_manager' => array(
					'type'    => 'toggle',
					'label'   => 'Shortcode manager',
					'default' => true,
				),
			),
			'apollo-templates'       => array(
				'enable_blank_canvas' => array(
					'type'    => 'toggle',
					'label'   => 'Blank canvas',
					'default' => true,
				),
				'override_archive'    => array(
					'type'    => 'toggle',
					'label'   => 'Override archives',
					'default' => false,
				),
			),
			'apollo-docs'            => array(
				'enable_versioning' => array(
					'type'    => 'toggle',
					'label'   => 'Versionamento',
					'default' => false,
				),
				'enable_toc'        => array(
					'type'    => 'toggle',
					'label'   => 'Table of contents',
					'default' => true,
				),
			),
			'apollo-secure-upload'   => array(
				'max_file_size' => array(
					'type'    => 'number',
					'label'   => 'Max upload MB',
					'default' => 10,
				),
				'scan_malware'  => array(
					'type'    => 'toggle',
					'label'   => 'Scan malware',
					'default' => true,
				),
				'allowed_types' => array(
					'type'    => 'text',
					'label'   => 'Tipos permitidos',
					'default' => 'jpg,png,webp,pdf',
				),
			),
			'apollo-webp-compressor' => array(
				'auto_convert' => array(
					'type'    => 'toggle',
					'label'   => 'Auto-convert WebP',
					'default' => true,
				),
				'quality'      => array(
					'type'    => 'number',
					'label'   => 'Qualidade (0-100)',
					'default' => 82,
				),
			),
			'apollo-hub'             => array(
				'enable_search'    => array(
					'type'    => 'toggle',
					'label'   => 'Search global',
					'default' => true,
				),
				'enable_directory' => array(
					'type'    => 'toggle',
					'label'   => 'Diretório',
					'default' => true,
				),
			),
			'apollo-cdn'             => array(
				'cdn_url'    => array(
					'type'    => 'text',
					'label'   => 'CDN base URL',
					'default' => '',
				),
				'minify_js'  => array(
					'type'    => 'toggle',
					'label'   => 'Minificar JS',
					'default' => false,
				),
				'minify_css' => array(
					'type'    => 'toggle',
					'label'   => 'Minificar CSS',
					'default' => false,
				),
			),
			'apollo-admin'           => array(
				'admin_accent' => array(
					'type'    => 'color',
					'label'   => 'Cor de destaque admin',
					'default' => '#6366f1',
				),
			),
			'apollo-mod'             => array(
				'auto_mod'         => array(
					'type'    => 'toggle',
					'label'   => 'Auto-moderação',
					'default' => false,
				),
				'report_threshold' => array(
					'type'    => 'number',
					'label'   => 'Reports para flag',
					'default' => 3,
				),
			),
			'apollo-coauthor'        => array(
				'enable_coauthor'  => array(
					'type'    => 'toggle',
					'label'   => 'Multi-autoria',
					'default' => true,
				),
				'show_in_frontend' => array(
					'type'    => 'toggle',
					'label'   => 'Mostrar no frontend',
					'default' => true,
				),
			),
			'apollo-statistics'      => array(
				'track_logins'    => array(
					'type'    => 'toggle',
					'label'   => 'Rastrear logins',
					'default' => true,
				),
				'track_pageviews' => array(
					'type'    => 'toggle',
					'label'   => 'Rastrear pageviews',
					'default' => false,
				),
				'retention_days'  => array(
					'type'    => 'number',
					'label'   => 'Retenção (dias)',
					'default' => 90,
				),
			),
			'apollo-hardening'       => array(
				'disable_xmlrpc'    => array(
					'type'    => 'toggle',
					'label'   => 'Desabilitar XML-RPC',
					'default' => true,
				),
				'disable_rest_anon' => array(
					'type'    => 'toggle',
					'label'   => 'REST só autenticado',
					'default' => true,
				),
				'cleanup_headers'   => array(
					'type'    => 'toggle',
					'label'   => 'Limpar headers',
					'default' => true,
				),
				'login_lockout'     => array(
					'type'    => 'toggle',
					'label'   => 'Lockout após falhas',
					'default' => true,
				),
				'lockout_attempts'  => array(
					'type'    => 'number',
					'label'   => 'Tentativas máx',
					'default' => 5,
				),
			),
			'apollo-adverts'         => array(
				'enable_classifieds' => array(
					'type'    => 'toggle',
					'label'   => 'Habilitar classificados',
					'default' => true,
				),
				'require_approval'   => array(
					'type'    => 'toggle',
					'label'   => 'Aprovação manual',
					'default' => true,
				),
			),
			'apollo-suppliers'       => array(
				'enable_suppliers' => array(
					'type'    => 'toggle',
					'label'   => 'Habilitar fornecedores',
					'default' => true,
				),
			),
			'apollo-comment'         => array(
				'enable_reactions' => array(
					'type'    => 'toggle',
					'label'   => 'Reactions em comments',
					'default' => true,
				),
				'enable_threading' => array(
					'type'    => 'toggle',
					'label'   => 'Threading',
					'default' => true,
				),
			),
			'apollo-fav'             => array(
				'enable_favorites' => array(
					'type'    => 'toggle',
					'label'   => 'Habilitar favoritos',
					'default' => true,
				),
			),
			'apollo-wow'             => array(
				'enable_reactions' => array(
					'type'    => 'toggle',
					'label'   => 'Habilitar reactions',
					'default' => true,
				),
				'available_types'  => array(
					'type'    => 'text',
					'label'   => 'Tipos disponíveis',
					'default' => 'like,love,fire,support,celebrate',
				),
			),
			'apollo-rio'             => array(
				'pwa_name'        => array(
					'type'    => 'text',
					'label'   => 'PWA App Name',
					'default' => 'Apollo Rio',
				),
				'pwa_short_name'  => array(
					'type'    => 'text',
					'label'   => 'PWA Short Name',
					'default' => 'Apollo',
				),
				'pwa_theme_color' => array(
					'type'    => 'color',
					'label'   => 'Theme Color',
					'default' => '#6366f1',
				),
				'enable_sw'       => array(
					'type'    => 'toggle',
					'label'   => 'Service Worker',
					'default' => true,
				),
			),
		);

		return $schemas[ $slug ] ?? array();
	}

	/* ───────────────────────────── INTERNALS ─────────────────────────── */

	private function load(): void {
		if ( $this->loaded ) {
			return;
		}
		$raw          = get_option( APOLLO_ADMIN_OPTION_KEY, array() );
		$this->data   = is_array( $raw ) ? $raw : array();
		$this->loaded = true;
	}

	private function ensure_loaded(): void {
		if ( ! $this->loaded ) {
			$this->load();
		}
	}

	private function save(): bool {
		return update_option( APOLLO_ADMIN_OPTION_KEY, $this->data );
	}
}
