<?php
/**
 * Booking REST endpoints.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\API;

use Apollo\Scheduler\Bridge\NotificationBridge;
use Apollo\Scheduler\Bridge\PaymentBridge;
use Apollo\Scheduler\Engine\AvailabilityEngine;
use Apollo\Scheduler\Models\AppointmentModel;
use Apollo\Scheduler\Security\BookingRateLimit;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookingController extends RestController {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/book',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'book' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => array(
						'date'        => array( 'required' => true, 'type' => 'string' ),
						'time'        => array( 'required' => true, 'type' => 'string' ),
						'service_id'  => array( 'required' => true, 'type' => 'integer' ),
						'agent_id'    => array( 'required' => true, 'type' => 'integer' ),
						'resource_id' => array( 'type' => 'integer' ),
						'nucleo_id'   => array( 'type' => 'integer' ),
						'notes'       => array( 'type' => 'string' ),
						'payment'     => array( 'type' => 'string' ),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reschedule/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reschedule' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
					'args'                => array(
						'date' => array( 'required' => true, 'type' => 'string' ),
						'time' => array( 'required' => true, 'type' => 'string' ),
					),
				),
			)
		);
	}

	public function book( WP_REST_Request $request ) {
		if ( ! BookingRateLimit::allow_write() ) {
			return new \WP_Error( 'rate_limited', __( 'Too many booking attempts.', 'apollo-scheduler' ), array( 'status' => 429 ) );
		}

		$date       = sanitize_text_field( (string) $request->get_param( 'date' ) );
		$time       = sanitize_text_field( (string) $request->get_param( 'time' ) );
		$service_id = (int) $request->get_param( 'service_id' );
		$agent_id   = (int) $request->get_param( 'agent_id' );
		$resource_id = $request->get_param( 'resource_id' ) ? (int) $request->get_param( 'resource_id' ) : 0;

		$engine = new AvailabilityEngine();
		$hours  = $engine->get_available_hours( $date, $service_id, $agent_id, $resource_id ?: null );

		if ( ! in_array( $time, $hours, true ) ) {
			return new \WP_Error( 'slot_unavailable', __( 'Selected slot is no longer available.', 'apollo-scheduler' ), array( 'status' => 409 ) );
		}

		$service = ( new \Apollo\Scheduler\Models\ServiceModel() )->get( $service_id );

		if ( null === $service ) {
			return new \WP_Error( 'invalid_service', __( 'Service not found.', 'apollo-scheduler' ), array( 'status' => 404 ) );
		}

		$duration = (int) ( $service['duration'] ?? 60 );

		$start = $date . ' ' . $time . ':00';
		$end_dt = new \DateTime( $start, wp_timezone() );
		$end_dt->add( new \DateInterval( 'PT' . $duration . 'M' ) );

		$data = array(
			'start'       => $start,
			'end'         => $end_dt->format( 'Y-m-d H:i:s' ),
			'service_id'  => $service_id,
			'agent_id'    => $agent_id,
			'resource_id' => $resource_id,
			'nucleo_id'   => (int) $request->get_param( 'nucleo_id' ),
			'customer_id' => get_current_user_id(),
			'notes'       => (string) $request->get_param( 'notes' ),
			'status'      => 'confirmed',
		);

		$appointment_id = ( new AppointmentModel() )->create( $data );

		if ( is_wp_error( $appointment_id ) ) {
			return $appointment_id;
		}

		$payment_method = sanitize_text_field( (string) $request->get_param( 'payment' ) );
		if ( $payment_method ) {
			PaymentBridge::process( (int) $appointment_id, $payment_method, (float) ( $service['price'] ?? 0 ) );
		}

		NotificationBridge::send_confirmation( (int) $appointment_id );

		BookingRateLimit::increment_write();

		return rest_ensure_response(
			array(
				'success'        => true,
				'appointment_id' => $appointment_id,
			)
		);
	}

	public function reschedule( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$model = new AppointmentModel();
		$existing = $model->get( $id );

		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'Appointment not found.', 'apollo-scheduler' ), array( 'status' => 404 ) );
		}

		if ( (int) $existing['customer_id'] !== get_current_user_id() && ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'forbidden', __( 'Not allowed.', 'apollo-scheduler' ), array( 'status' => 403 ) );
		}

		$date = sanitize_text_field( (string) $request->get_param( 'date' ) );
		$time = sanitize_text_field( (string) $request->get_param( 'time' ) );

		$engine = new AvailabilityEngine();
		$hours  = $engine->get_available_hours(
			$date,
			(int) $existing['service_id'],
			(int) $existing['agent_id'],
			$existing['resource_id'] ? (int) $existing['resource_id'] : null,
			$id
		);

		if ( ! in_array( $time, $hours, true ) ) {
			return new \WP_Error( 'slot_unavailable', __( 'Selected slot is no longer available.', 'apollo-scheduler' ), array( 'status' => 409 ) );
		}

		$service = ( new \Apollo\Scheduler\Models\ServiceModel() )->get( (int) $existing['service_id'] );

		if ( null === $service ) {
			return new \WP_Error( 'invalid_service', __( 'Service not found.', 'apollo-scheduler' ), array( 'status' => 404 ) );
		}

		$duration = (int) ( $service['duration'] ?? 60 );
		$start = $date . ' ' . $time . ':00';
		$end_dt = new \DateTime( $start, wp_timezone() );
		$end_dt->add( new \DateInterval( 'PT' . $duration . 'M' ) );

		update_post_meta( $id, '_apollo_appointment_start', $start );
		update_post_meta( $id, '_apollo_appointment_end', $end_dt->format( 'Y-m-d H:i:s' ) );

		\Apollo\Scheduler\Cache\AvailabilityCache::flush_agent( (int) $existing['agent_id'] );

		do_action( 'apollo/scheduler/rescheduled', $id, $start, $end_dt->format( 'Y-m-d H:i:s' ) );

		return rest_ensure_response( array( 'success' => true, 'appointment_id' => $id ) );
	}
}
