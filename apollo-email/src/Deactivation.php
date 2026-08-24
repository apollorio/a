<?php

/**
 * Plugin Deactivation — clears cron, transients.
 *
 * @package Apollo\Email
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {

	/**
	 * Run deactivation routine.
	 */
	public static function deactivate(): void {
		// Clear scheduled cron
		$timestamp = wp_next_scheduled( APOLLO_EMAIL_CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, APOLLO_EMAIL_CRON_HOOK );
		}

		// Clear transients
		delete_transient( 'apollo_email_activated' );
		delete_transient( 'apollo_email_queue_lock' );

		flush_rewrite_rules();
	}
}
