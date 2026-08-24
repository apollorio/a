<?php
/**
 * Apollo Calendar — REST Controller
 *
 * Namespace: apollo/v1
 *
 * Routes:
 *   GET  /calendar                       → aggregated items for current user
 *   POST /calendar/appointments          → create personal appointment
 *   PUT  /calendar/appointments/{id}     → update (owner check)
 *   DELETE /calendar/appointments/{id}  → delete (owner check)
 *   GET  /calendar/holidays              → public holiday list
 *   GET  /calendar/ical                  → iCalendar (.ics) export
 *
 * @package Apollo\Calendar\API
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar\API;

use Apollo\Core\API\RestBase;
use Apollo\Calendar\CalendarAggregator;
use Apollo\Calendar\Models\AppointmentModel;
use Apollo\Calendar\Holidays\HolidayModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CalendarController extends RestBase {

	public function __construct() {
		parent::__construct();
		$this->register_routes();
	}

	public function register_routes(): void {
		// Aggregated calendar.
		register_rest_route(
			$this->namespace,
			'/calendar',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_calendar' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
				'args'                => array(
					'from'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'to'      => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'sources' => array(
						'type'    => 'array',
						'default' => array(),
						'items'   => array( 'type' => 'string' ),
					),
				),
			)
		);

		// Personal appointments CRUD.
		register_rest_route(
			$this->namespace,
			'/calendar/appointments',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_appointment' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
				'args'                => $this->appointment_args( false ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/calendar/appointments/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_appointment' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
					'args'                => array_merge(
						array(
							'id' => array(
								'type'     => 'integer',
								'required' => true,
								'minimum'  => 1,
							),
						),
						$this->appointment_args( true )
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_appointment' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
					'args'                => array(
						'id' => array(
							'type'     => 'integer',
							'required' => true,
							'minimum'  => 1,
						),
					),
				),
			)
		);

		// Holidays (public — no secrets returned).
		register_rest_route(
			$this->namespace,
			'/calendar/holidays',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_holidays' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'from'   => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'to'     => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'region' => array(
						'type'              => 'string',
						'default'           => 'RJ',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// iCal export (logged-in only).
		register_rest_route(
			$this->namespace,
			'/calendar/ical',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_ical' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
				'args'                => array(
					'from' => array(
						'type'              => 'string',
						'default'           => gmdate( 'Y-m-d\T00:00:00\Z' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'to'   => array(
						'type'              => 'string',
						'default'           => gmdate( 'Y-m-d\T23:59:59\Z', strtotime( '+6 months' ) ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	// ─── Handlers ──────────────────────────────────────────────────────

	/**
	 * GET /calendar — aggregated items.
	 */
	public function get_calendar( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$user_id = get_current_user_id();
		$from    = $request->get_param( 'from' );
		$to      = $request->get_param( 'to' );
		$sources = (array) $request->get_param( 'sources' );

		$items = CalendarAggregator::get( $user_id, $from, $to, $sources );

		return $this->prepare_response( $items );
	}

	/**
	 * POST /calendar/appointments — create.
	 */
	public function create_appointment( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$user_id = get_current_user_id();
		$data    = $request->get_params();

		$id = \Apollo\Calendar\Models\AppointmentModel::create( $user_id, $data );

		if ( false === $id ) {
			return $this->prepare_error( 'calendar_create_failed', __( 'Could not create appointment.', 'apollo-calendar' ), 500 );
		}

		CalendarAggregator::bust_user( $user_id );

		$row = AppointmentModel::get_owned( $id, $user_id );

		// Fire optional reminder if requested.
		$remind = absint( $data['remind_before'] ?? 0 );
		if ( $remind > 0 && $row ) {
			$this->maybe_schedule_reminder( $user_id, $id, $row['starts_at'], $remind, $row['title'] );
		}

		return $this->prepare_response( $row, 201 );
	}

	/**
	 * PUT /calendar/appointments/{id} — update (owner only).
	 */
	public function update_appointment( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );
		$data    = $request->get_params();

		$existing = AppointmentModel::get_owned( $id, $user_id );
		if ( ! $existing ) {
			return $this->prepare_error( 'calendar_not_found', __( 'Appointment not found.', 'apollo-calendar' ), 404 );
		}

		$ok = AppointmentModel::update( $id, $user_id, $data );

		if ( ! $ok ) {
			return $this->prepare_error( 'calendar_update_failed', __( 'Could not update appointment.', 'apollo-calendar' ), 500 );
		}

		CalendarAggregator::bust_user( $user_id );

		$updated = AppointmentModel::get_owned( $id, $user_id );

		return $this->prepare_response( $updated );
	}

	/**
	 * DELETE /calendar/appointments/{id} — delete (owner only).
	 */
	public function delete_appointment( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		$existing = AppointmentModel::get_owned( $id, $user_id );
		if ( ! $existing ) {
			return $this->prepare_error( 'calendar_not_found', __( 'Appointment not found.', 'apollo-calendar' ), 404 );
		}

		$ok = AppointmentModel::delete( $id, $user_id );

		if ( ! $ok ) {
			return $this->prepare_error( 'calendar_delete_failed', __( 'Could not delete appointment.', 'apollo-calendar' ), 500 );
		}

		CalendarAggregator::bust_user( $user_id );

		return $this->prepare_response( array( 'deleted' => true ) );
	}

	/**
	 * GET /calendar/holidays — public.
	 */
	public function get_holidays( \WP_REST_Request $request ): \WP_REST_Response {
		$from   = $request->get_param( 'from' );
		$to     = $request->get_param( 'to' );
		$region = $request->get_param( 'region' );

		$rows = HolidayModel::get_by_range(
			substr( $from, 0, 10 ),
			substr( $to, 0, 10 ),
			array( 'national', 'state', 'municipal' ),
			$region
		);

		return $this->prepare_response( $rows );
	}

	/**
	 * GET /calendar/ical — iCal export.
	 */
	public function get_ical( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$user_id = get_current_user_id();
		$from    = $request->get_param( 'from' );
		$to      = $request->get_param( 'to' );

		$items = CalendarAggregator::get( $user_id, $from, $to );
		$ics   = $this->build_ics( $items, $user_id );

		// Return as plain text; client should download.
		$response = new \WP_REST_Response( $ics, 200 );
		$response->header( 'Content-Type', 'text/calendar; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="apollo-calendar.ics"' );

		return $response;
	}

	// ─── iCal builder ──────────────────────────────────────────────────

	/**
	 * Build a VCALENDAR string from normalized items.
	 *
	 * @param array<int,array<string,mixed>> $items
	 * @param int $user_id
	 * @return string
	 */
	private function build_ics( array $items, int $user_id ): string {
		$user = get_userdata( $user_id );
		$name = $user ? sanitize_text_field( $user->display_name ) : 'User';
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Apollo::Rio//Apollo Calendar//PT-BR',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'X-WR-CALNAME:' . $name . ' — Apollo Calendar',
			'X-WR-TIMEZONE:UTC',
		);

		foreach ( $items as $item ) {
			$uid        = esc_html( $item['id'] ?? 'item' ) . '@' . $host;
			$start_val  = $this->ics_datetime( $item['start_iso'] ?? '', (bool) ( $item['all_day'] ?? false ) );
			$dtstart    = (bool) ( $item['all_day'] ?? false )
				? 'DTSTART;VALUE=DATE:' . $start_val
				: 'DTSTART:' . $start_val;
			$summary    = $this->ics_escape( $item['title'] ?? '' );
			$location   = $this->ics_escape( $item['location'] ?? '' );
			$url        = esc_url_raw( $item['url'] ?? '' );

			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:' . $uid;
			$lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
			$lines[] = $dtstart;

			if ( ! empty( $item['end_iso'] ) ) {
				$end_val = $this->ics_datetime( $item['end_iso'], (bool) ( $item['all_day'] ?? false ) );
				$lines[] = (bool) ( $item['all_day'] ?? false )
					? 'DTEND;VALUE=DATE:' . $end_val
					: 'DTEND:' . $end_val;
			}

			$lines[] = 'SUMMARY:' . $summary;

			if ( $location ) {
				$lines[] = 'LOCATION:' . $location;
			}
			if ( $url ) {
				$lines[] = 'URL:' . $url;
			}

			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';

		return implode( "\r\n", $lines ) . "\r\n";
	}

	private function ics_datetime( string $iso, bool $all_day ): string {
		if ( $all_day ) {
			return str_replace( '-', '', substr( $iso, 0, 10 ) );
		}
		return gmdate( 'Ymd\THis\Z', strtotime( $iso ) ?: time() );
	}

	private function ics_escape( string $value ): string {
		return str_replace( array( '\\', ';', ',', "\n", "\r" ), array( '\\\\', '\\;', '\\,', '\\n', '' ), $value );
	}

	// ─── Reminder integration ──────────────────────────────────────────

	private function maybe_schedule_reminder( int $user_id, int $appt_id, string $starts_at_utc, int $remind_before_minutes, string $title ): void {
		if ( ! function_exists( 'apollo_remind_schedule' ) ) {
			return;
		}

		$fire_at = strtotime( $starts_at_utc ) - ( $remind_before_minutes * MINUTE_IN_SECONDS );
		if ( $fire_at <= time() ) {
			return;
		}

		apollo_remind_schedule( array(
			'user_id'    => $user_id,
			'type'       => 'calendar_appointment',
			'object_id'  => $appt_id,
			'message'    => sprintf(
				/* translators: %s: appointment title */
				__( 'Lembrete: %s', 'apollo-calendar' ),
				$title
			),
			'fire_at'    => gmdate( 'Y-m-d H:i:s', $fire_at ),
		) );
	}

	// ─── Argument schema ───────────────────────────────────────────────

	/**
	 * @param bool $all_optional If true, all fields are optional (for PATCH-style update).
	 * @return array<string,array<string,mixed>>
	 */
	private function appointment_args( bool $all_optional ): array {
		$required = ! $all_optional;

		return array(
			'title' => array(
				'type'              => 'string',
				'required'          => $required,
				'sanitize_callback' => 'sanitize_text_field',
				'maxLength'         => 255,
			),
			'starts_at' => array(
				'type'              => 'string',
				'required'          => $required,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => 'UTC ISO-8601 datetime',
			),
			'ends_at' => array(
				'type'              => array( 'string', 'null' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'all_day' => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'description' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'location' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'color' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'recurrence' => array(
				'type'    => 'string',
				'default' => 'none',
				'enum'    => array( 'none', 'daily', 'weekly', 'monthly', 'yearly' ),
			),
			'remind_before' => array(
				'type'    => 'integer',
				'default' => 0,
				'minimum' => 0,
			),
		);
	}
}
