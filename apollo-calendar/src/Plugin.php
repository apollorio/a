<?php
/**
 * Apollo Calendar — Main Plugin Bootstrap
 *
 * @package Apollo\Calendar
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar;

use Apollo\Calendar\API\CalendarController;
use Apollo\Calendar\Frontend\CalendarPage;
use Apollo\Calendar\Admin\HolidaysAdmin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		// User meta registration (timezone + prefs).
		( new Registry() )->init();

		// Frontend route: /agenda.
		( new CalendarPage() )->init();

		// REST API.
		add_action( 'rest_api_init', function () {
			new CalendarController();
		} );

		// Admin: holiday manager.
		if ( is_admin() ) {
			( new HolidaysAdmin() )->init();
		}

		// Cache-busting listeners.
		CacheBuster::init();

		// i18n.
		add_action(
			'init',
			function () {
				load_plugin_textdomain(
					'apollo-calendar',
					false,
					dirname( APOLLO_CALENDAR_BASENAME ) . '/languages'
				);
			}
		);
	}
}
