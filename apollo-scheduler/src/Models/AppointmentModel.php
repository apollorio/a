<?php
/**
 * Appointment model — CPT-backed persistence.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Models;

use Apollo\Scheduler\Bridge\CalendarBridge;
use Apollo\Scheduler\Cache\AvailabilityCache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AppointmentModel {

	/**
	 * @param array<string, mixed> $data
	 * @return int|\WP_Error
	 */
	public function create( array $data ) {
		$start = (string) ( $data['start'] ?? '' );
		$end   = (string) ( $data['end'] ?? '' );

		if ( '' === $start || '' === $end ) {
			return new \WP_Error( 'invalid_dates', __( 'Start and end are required.', 'apollo-scheduler' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => APOLLO_SCHEDULER_CPT_APPOINTMENT,
				'post_title'  => sprintf(
					/* translators: %s: appointment datetime */
					__( 'Appointment %s', 'apollo-scheduler' ),
					$start
				),
				'post_status' => 'publish',
				'post_author' => (int) ( $data['customer_id'] ?? get_current_user_id() ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_apollo_appointment_start', $start );
		update_post_meta( $post_id, '_apollo_appointment_end', $end );
		update_post_meta( $post_id, '_apollo_appointment_service_id', (int) ( $data['service_id'] ?? 0 ) );
		update_post_meta( $post_id, '_apollo_appointment_agent_id', (int) ( $data['agent_id'] ?? 0 ) );
		update_post_meta( $post_id, '_apollo_appointment_resource_id', (int) ( $data['resource_id'] ?? 0 ) );
		update_post_meta( $post_id, '_apollo_appointment_nucleo_id', (int) ( $data['nucleo_id'] ?? 0 ) );
		update_post_meta( $post_id, '_apollo_appointment_customer_id', (int) ( $data['customer_id'] ?? get_current_user_id() ) );
		update_post_meta( $post_id, '_apollo_appointment_status', (string) ( $data['status'] ?? 'confirmed' ) );
		update_post_meta( $post_id, '_apollo_appointment_notes', sanitize_textarea_field( (string) ( $data['notes'] ?? '' ) ) );

		$event_id = CalendarBridge::link_to_calendar( (int) $post_id, $data );
		if ( $event_id ) {
			update_post_meta( $post_id, '_apollo_appointment_event_id', $event_id );
		}

		AvailabilityCache::flush_agent( (int) ( $data['agent_id'] ?? 0 ) );

		do_action( 'apollo/scheduler/booked', (int) $post_id, $data );

		return (int) $post_id;
	}

	/**
	 * @return array<int, array{start_datetime:string,end_datetime:string}>
	 */
	public function get_blocking_events_for_agent( int $agent_id, string $date, ?int $exclude_id = null ): array {
		return $this->query_blocking_events(
			array(
				array(
					'key'   => '_apollo_appointment_agent_id',
					'value' => $agent_id,
				),
			),
			$date,
			$exclude_id
		);
	}

	/**
	 * @return array<int, array{start_datetime:string,end_datetime:string}>
	 */
	public function get_blocking_events_for_resource( int $resource_id, string $date, ?int $exclude_id = null ): array {
		return $this->query_blocking_events(
			array(
				array(
					'key'   => '_apollo_appointment_resource_id',
					'value' => $resource_id,
				),
			),
			$date,
			$exclude_id
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $meta_query
	 * @return array<int, array{start_datetime:string,end_datetime:string}>
	 */
	private function query_blocking_events( array $meta_query, string $date, ?int $exclude_id ): array {
		$meta_query[] = array(
			'key'     => '_apollo_appointment_start',
			'value'   => array( $date . ' 00:00:00', $date . ' 23:59:59' ),
			'compare' => 'BETWEEN',
			'type'    => 'DATETIME',
		);

		$args = array(
			'post_type'      => APOLLO_SCHEDULER_CPT_APPOINTMENT,
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'meta_query'     => $meta_query,
			'fields'         => 'ids',
		);

		if ( $exclude_id ) {
			$args['post__not_in'] = array( $exclude_id );
		}

		$query  = new \WP_Query( $args );
		$events = array();

		foreach ( $query->posts as $post_id ) {
			$start = (string) get_post_meta( (int) $post_id, '_apollo_appointment_start', true );
			$end   = (string) get_post_meta( (int) $post_id, '_apollo_appointment_end', true );

			if ( $start && $end ) {
				$events[] = array(
					'start_datetime' => $start,
					'end_datetime'   => $end,
				);
			}
		}

		return $events;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get( int $appointment_id ): ?array {
		$post = get_post( $appointment_id );
		if ( ! $post instanceof \WP_Post || APOLLO_SCHEDULER_CPT_APPOINTMENT !== $post->post_type ) {
			return null;
		}

		return array(
			'id'          => $appointment_id,
			'start'       => (string) get_post_meta( $appointment_id, '_apollo_appointment_start', true ),
			'end'         => (string) get_post_meta( $appointment_id, '_apollo_appointment_end', true ),
			'service_id'  => (int) get_post_meta( $appointment_id, '_apollo_appointment_service_id', true ),
			'agent_id'    => (int) get_post_meta( $appointment_id, '_apollo_appointment_agent_id', true ),
			'resource_id' => (int) get_post_meta( $appointment_id, '_apollo_appointment_resource_id', true ),
			'nucleo_id'   => (int) get_post_meta( $appointment_id, '_apollo_appointment_nucleo_id', true ),
			'customer_id' => (int) get_post_meta( $appointment_id, '_apollo_appointment_customer_id', true ),
			'status'      => (string) get_post_meta( $appointment_id, '_apollo_appointment_status', true ),
			'event_id'    => (int) get_post_meta( $appointment_id, '_apollo_appointment_event_id', true ),
		);
	}
}
