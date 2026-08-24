<?php
/**
 * iCal export endpoint.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\API;

use Apollo\Scheduler\Models\AppointmentModel;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ICalController extends RestController {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ical/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export' ),
					'permission_callback' => array( $this, 'ical_permission' ),
				),
			)
		);
	}

	public function ical_permission( WP_REST_Request $request ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$id   = (int) $request->get_param( 'id' );
		$data = ( new AppointmentModel() )->get( $id );

		if ( ! $data ) {
			return false;
		}

		return (int) $data['customer_id'] === get_current_user_id() || current_user_can( 'edit_posts' );
	}

	public function export( WP_REST_Request $request ) {
		$id   = (int) $request->get_param( 'id' );
		$data = ( new AppointmentModel() )->get( $id );

		if ( ! $data ) {
			return new \WP_Error( 'not_found', __( 'Appointment not found.', 'apollo-scheduler' ), array( 'status' => 404 ) );
		}

		$ics = $this->build_ics( $data );

		return new \WP_REST_Response(
			$ics,
			200,
			array(
				'Content-Type'        => 'text/calendar; charset=utf-8',
				'Content-Disposition' => 'attachment; filename="appointment-' . $id . '.ics"',
			)
		);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function build_ics( array $data ): string {
		$uid    = 'apollo-scheduler-' . (int) $data['id'] . '@apollo.rio.br';
		$start  = gmdate( 'Ymd\THis\Z', strtotime( (string) $data['start'] ) );
		$end    = gmdate( 'Ymd\THis\Z', strtotime( (string) $data['end'] ) );
		$title  = get_the_title( (int) $data['service_id'] ) ?: 'Apollo Appointment';

		return implode(
			"\r\n",
			array(
				'BEGIN:VCALENDAR',
				'VERSION:2.0',
				'PRODID:-//Apollo Scheduler//EN',
				'CALSCALE:GREGORIAN',
				'METHOD:PUBLISH',
				'BEGIN:VEVENT',
				'UID:' . $uid,
				'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
				'DTSTART:' . $start,
				'DTEND:' . $end,
				'SUMMARY:' . $this->escape_ics( $title ),
				'END:VEVENT',
				'END:VCALENDAR',
			)
		) . "\r\n";
	}

	private function escape_ics( string $value ): string {
		return str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\\;', '\\,', '\\n' ), $value );
	}
}
