<?php
/**
 * Notification bridge — apollo-email, apollo-remind, apollo-notif.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Bridge;

use Apollo\Scheduler\Models\AppointmentModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NotificationBridge {

	public static function send_confirmation( int $appointment_id ): void {
		$data = ( new AppointmentModel() )->get( $appointment_id );
		if ( ! $data ) {
			return;
		}

		$customer = get_userdata( (int) $data['customer_id'] );
		if ( ! $customer instanceof \WP_User ) {
			return;
		}

		$payload = array(
			'appointment_id' => $appointment_id,
			'start'          => $data['start'],
			'end'            => $data['end'],
			'customer_email' => $customer->user_email,
			'customer_name'  => $customer->display_name,
			'ics_url'        => rest_url( APOLLO_SCHEDULER_REST_NAMESPACE . '/' . APOLLO_SCHEDULER_REST_BASE . '/ical/' . $appointment_id ),
		);

		do_action( 'apollo/scheduler/confirmation', $payload );

		if ( function_exists( '\Apollo\Email\apollo_email_queue' ) ) {
			\Apollo\Email\apollo_email_queue(
				$customer->user_email,
				'scheduler_confirmation',
				$payload
			);
		} elseif ( class_exists( '\Apollo\Email\Mailer\Queue' ) ) {
			\Apollo\Email\Mailer\Queue::get_instance()->add(
				$customer->user_email,
				'scheduler_confirmation',
				$payload
			);
		}

		do_action( 'apollo/remind/schedule', 'appointment', $appointment_id, $data['start'] );
		do_action( 'apollo/notif/send', get_current_user_id(), 'appointment_confirmed', $payload );
	}
}
