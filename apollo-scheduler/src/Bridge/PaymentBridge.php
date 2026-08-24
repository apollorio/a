<?php
/**
 * Payment bridge — Stripe/PayPal/manual defaults.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Bridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaymentBridge {

	public static function process( int $appointment_id, string $method, float $amount ): void {
		$method = sanitize_key( $method );

		$result = apply_filters(
			'apollo/scheduler/process_payment',
			array(
				'success' => 'manual' === $method || $amount <= 0,
				'method'  => $method,
				'amount'  => $amount,
			),
			$appointment_id
		);

		$status = ! empty( $result['success'] ) ? 'paid' : 'pending';
		update_post_meta( $appointment_id, '_apollo_appointment_payment_status', $status );

		do_action( 'apollo/scheduler/payment_processed', $appointment_id, $result );
	}
}
