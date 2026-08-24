<?php

/**
 * Main Plugin Singleton
 *
 * Orchestrates all Gestor components: admin menu, AJAX handlers, assets.
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private const OPTION_KEY = 'apollo_gestor_settings';

	private const DEFAULTS = [
		'enable_milestones' => true,
		'enable_payments'   => false,
		'enable_team'       => true,
		'default_currency'  => 'BRL',
	];

	private static ?Plugin $instance = null;

	private bool $initialized = false;

	private ?Core\Module_Manager $modules = null;

	/** @var Cron\TaskReminderCron */
	private ?Cron\TaskReminderCron $reminder_cron = null;

	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Initialize all plugin components
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		// Ensure DB tables exist
		$this->maybe_upgrade_db();

		// Boot module system
		$this->modules = new Core\Module_Manager();
		$this->modules->init();

		// Boot task reminder cron
		$this->reminder_cron = new Cron\TaskReminderCron();
		$this->reminder_cron->register();

		// Admin
		if ( is_admin() ) {
			$admin = new Admin\Controller( $this->modules );
			$admin->init();
		}

		// Hooks
		do_action( 'apollo/gestor/initialized', $this );
	}

	/**
	 * Get the module manager
	 */
	public function modules(): ?Core\Module_Manager {
		return $this->modules;
	}

	/**
	 * Get a plugin setting value
	 *
	 * @param string $key     Setting key from wp-admin.json contract
	 * @param mixed  $default Override default (optional)
	 * @return mixed
	 */
	public static function get_setting( string $key, $default = null ) {
		$settings = get_option( self::OPTION_KEY, [] );
		$settings = wp_parse_args( $settings, self::DEFAULTS );

		if ( $default !== null ) {
			return $settings[ $key ] ?? $default;
		}

		return $settings[ $key ] ?? ( self::DEFAULTS[ $key ] ?? null );
	}

	/**
	 * Ensure default settings exist on activation
	 */
	public static function ensure_defaults(): void {
		if ( false === get_option( self::OPTION_KEY ) ) {
			update_option( self::OPTION_KEY, self::DEFAULTS, true );
		}
	}

	/**
	 * Check if DB needs upgrade
	 */
	private function maybe_upgrade_db(): void {
		$installed = (int) get_option( 'apollo_gestor_db_version', 0 );

		if ( $installed < APOLLO_GESTOR_DB_VERSION ) {
			Database::install();          // Create / alter tables first.
			Database::upgrade( $installed ); // Then run data migrations.
			update_option( 'apollo_gestor_db_version', APOLLO_GESTOR_DB_VERSION );
		}
	}
}
