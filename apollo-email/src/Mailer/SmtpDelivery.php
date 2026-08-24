<?php

/**
 * SMTP delivery outcome — classifies remote responses to protect sender reputation.
 *
 * Permanent recipient/server rejections (most 5xx) must not be retried in a loop.
 * Transient 4xx / selected 5xx may be retried with backoff.
 *
 * @package Apollo\Email\Mailer
 * @since   1.0.1
 */

declare(strict_types=1);

namespace Apollo\Email\Mailer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SmtpDelivery {

	/** @var int[] RFC 5321-style codes that must not be retried (hard bounce / policy). */
	private const PERMANENT_CODES = array(
		500, 501, 502, 503, 504, 521, 530,
		550, 551, 552, 553, 554, 555,
	);

	/** @var int[] Transient failures — safe to retry with limits. */
	private const TEMPORARY_CODES = array(
		421, 450, 451, 452, 454,
	);

	/**
	 * Classify an SMTP / PHPMailer error string.
	 *
	 * @return array{kind: 'permanent'|'temporary'|'unknown', code: int}
	 */
	public static function classify( string $error ): array {
		$code = self::extractCode( $error );

		if ( $code > 0 ) {
			if ( in_array( $code, self::PERMANENT_CODES, true ) ) {
				return array( 'kind' => 'permanent', 'code' => $code );
			}
			if ( in_array( $code, self::TEMPORARY_CODES, true ) || ( $code >= 400 && $code < 500 ) ) {
				return array( 'kind' => 'temporary', 'code' => $code );
			}
			if ( $code >= 500 ) {
				return array( 'kind' => 'permanent', 'code' => $code );
			}
		}

		$lower = strtolower( $error );
		if ( str_contains( $lower, 'user unknown' )
			|| str_contains( $lower, 'mailbox unavailable' )
			|| str_contains( $lower, 'recipient address rejected' )
			|| str_contains( $lower, 'does not exist' )
			|| str_contains( $lower, 'no such user' )
			|| str_contains( $lower, 'relay not permitted' )
		) {
			return array( 'kind' => 'permanent', 'code' => $code );
		}

		if ( str_contains( $lower, 'timeout' )
			|| str_contains( $lower, 'try again' )
			|| str_contains( $lower, 'temporarily' )
			|| str_contains( $lower, 'rate limit' )
			|| str_contains( $lower, 'too many' )
		) {
			return array( 'kind' => 'temporary', 'code' => $code );
		}

		return array( 'kind' => 'unknown', 'code' => $code );
	}

	/**
	 * Whether queue/cron should attempt another delivery.
	 */
	public static function shouldRetry( string $error, int $attempts, int $max_attempts ): bool {
		$analysis = self::classify( $error );

		if ( 'permanent' === $analysis['kind'] ) {
			return false;
		}

		return $attempts < $max_attempts;
	}

	/**
	 * Backoff seconds before a transient retry (exponential, capped).
	 */
	public static function retryDelaySeconds( int $attempts ): int {
		$base = (int) apply_filters( 'apollo/email/retry_delay_base', 300 );
		return (int) min( $base * ( 2 ** max( 0, $attempts - 1 ) ), DAY_IN_SECONDS );
	}

	private static function extractCode( string $error ): int {
		if ( preg_match( '/\b([45]\d{2})\b/', $error, $m ) ) {
			return (int) $m[1];
		}
		if ( preg_match( '/SMTP code:\s*(\d+)/i', $error, $m ) ) {
			return (int) $m[1];
		}
		return 0;
	}
}
