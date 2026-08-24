<?php
/**
 * Apollo Calendar — Deactivation
 *
 * @package Apollo\Calendar
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivation {

	public static function deactivate(): void {
		// Clear yearly seeding cron.
		$ts = wp_next_scheduled( 'apollo_calendar_seed_holidays' );
		if ( $ts ) {
			wp_unschedule_event( $ts, 'apollo_calendar_seed_holidays' );
		}

		flush_rewrite_rules();
	}
}
