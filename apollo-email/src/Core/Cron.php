<?php

/**
 * Cron scheduler for queue processing.
 *
 * @package Apollo\Email\Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\Core;

use Apollo\Email\Mailer\Queue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cron {

	private Queue $queue;

	public function __construct( Queue $queue ) {
		$this->queue = $queue;

		// Register custom cron interval
		add_filter( 'cron_schedules', array( $this, 'addIntervals' ) );
	}

	/**
	 * Add custom cron intervals.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified schedules.
	 */
	public function addIntervals( array $schedules ): array {
		if ( ! isset( $schedules['apollo_five_minutes'] ) ) {
			$schedules['apollo_five_minutes'] = array(
				'interval' => 300,
				'display'  => __( 'A cada 5 minutos (Apollo Email)', 'apollo-email' ),
			);
		}

		if ( ! isset( $schedules['apollo_hourly'] ) ) {
			$schedules['apollo_hourly'] = array(
				'interval' => 3600,
				'display'  => __( 'A cada hora (Apollo Email)', 'apollo-email' ),
			);
		}

		return $schedules;
	}

	/**
	 * Ensure cron is scheduled. Called on admin_init.
	 */
	public function ensureScheduled(): void {
		if ( ! wp_next_scheduled( APOLLO_EMAIL_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'apollo_five_minutes', APOLLO_EMAIL_CRON_HOOK );
		}
	}
}
