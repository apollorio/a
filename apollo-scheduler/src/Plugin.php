<?php
/**
 * Main plugin singleton.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler;

use Apollo\Scheduler\Admin\AgentAssignment;
use Apollo\Scheduler\API\AvailabilityController;
use Apollo\Scheduler\API\BookingController;
use Apollo\Scheduler\API\ICalController;
use Apollo\Scheduler\API\ManagerController;
use Apollo\Scheduler\Bridge\NotificationBridge;
use Apollo\Scheduler\Bridge\PaymentBridge;
use Apollo\Scheduler\Frontend\Assets;
use Apollo\Scheduler\Frontend\BookingWizard;
use Apollo\Scheduler\Frontend\ManagerCalendar;
use Apollo\Scheduler\Integrations\UsersIntegration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_rewrite_rules' ), 1 );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_virtual_pages' ), 5 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		$this->init_components();

		do_action( 'apollo/scheduler/init', $this );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'apollo-scheduler',
			false,
			dirname( APOLLO_SCHEDULER_BASENAME ) . '/languages'
		);
	}

	public function register_rewrite_rules(): void {
		add_rewrite_rule( '^book/?$', 'index.php?apollo_scheduler_page=book', 'top' );
		add_rewrite_rule(
			'^nucleo/([^/]+)/schedule/?$',
			'index.php?apollo_scheduler_page=nucleo_schedule&apollo_nucleo_slug=$matches[1]',
			'top'
		);
		add_rewrite_rule( '^agent/availability/?$', 'index.php?apollo_scheduler_page=agent_availability', 'top' );
	}

	/**
	 * @param array<int, string> $vars
	 * @return array<int, string>
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'apollo_scheduler_page';
		$vars[] = 'apollo_nucleo_slug';
		return $vars;
	}

	public function handle_virtual_pages(): void {
		$page = get_query_var( 'apollo_scheduler_page', '' );

		if ( '' === $page ) {
			return;
		}

		switch ( $page ) {
			case 'book':
				BookingWizard::render_page();
				exit;
			case 'nucleo_schedule':
				ManagerCalendar::render_page( (string) get_query_var( 'apollo_nucleo_slug', '' ) );
				exit;
			case 'agent_availability':
				ManagerCalendar::render_agent_page();
				exit;
		}
	}

	public function register_rest_routes(): void {
		$controllers = array(
			new AvailabilityController(),
			new BookingController(),
			new ManagerController(),
			new ICalController(),
		);

		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}

	private function init_components(): void {
		new Registry();
		new UsersIntegration();
		new AgentAssignment();
		new Assets();
		new BookingWizard();
		new ManagerCalendar();
		new NotificationBridge();
		new PaymentBridge();
	}
}
