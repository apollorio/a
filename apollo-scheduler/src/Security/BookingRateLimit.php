<?php
/**
 * Booking rate limiting via apollo-login RateLimiter.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookingRateLimit {

	private const READ_MAX  = 120;
	private const WRITE_MAX = 20;
	private const TTL       = 60;

	public static function allow_read(): bool {
		return self::check( 'read', self::READ_MAX );
	}

	public static function allow_write(): bool {
		return self::check( 'write', self::WRITE_MAX );
	}

	public static function increment_write(): void {
		if ( class_exists( '\Apollo\Login\Security\RateLimiter' ) ) {
			\Apollo\Login\Security\RateLimiter::increment_counter( self::bucket( 'write' ), self::TTL );
		}
	}

	private static function check( string $type, int $max ): bool {
		if ( ! class_exists( '\Apollo\Login\Security\RateLimiter' ) ) {
			return true;
		}

		$bucket = self::bucket( $type );
		$count  = \Apollo\Login\Security\RateLimiter::get_counter( $bucket );

		if ( $count >= $max ) {
			do_action( 'apollo/scheduler/rate_limited', $type, $count );
			return false;
		}

		\Apollo\Login\Security\RateLimiter::increment_counter( $bucket, self::TTL );
		return true;
	}

	private static function bucket( string $type ): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'apollo_scheduler_' . $type . '_' . md5( $ip . '|' . (string) get_current_user_id() );
	}
}
