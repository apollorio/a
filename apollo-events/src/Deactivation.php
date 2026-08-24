<?php
/**
 * Desativação do plugin
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {

	/**
	 * Executa na desativação do plugin
	 */
	public static function deactivate(): void {
		// Remove cron de expiração
		$timestamp = wp_next_scheduled( 'apollo_event_check_expiration' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'apollo_event_check_expiration' );
		}
	}
}
