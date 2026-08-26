<?php
/**
 * Rate limiter — object cache (Redis-friendly) with transient fallback.
 *
 * @package Apollo\Telegram\Security
 */

declare(strict_types=1);

namespace Apollo\Telegram\Security;

if (! defined('ABSPATH')) {
	exit;
}

final class RateLimiter
{
	/**
	 * Check and increment; return true if under limit.
	 *
	 * @param string $scope  Logical scope (e.g. verify_code:phone:ip).
	 * @param int    $max    Maximum hits in window.
	 * @param int    $window TTL seconds.
	 */
	public static function allow(string $scope, int $max, int $window): bool
	{
		$cache_key = 'tg_rl_' . md5($scope);
		$transient = 'apollo_telegram_rl_' . md5($scope);

		$count = (int) get_transient($transient) + 1;
		set_transient($transient, $count, $window);

		if (function_exists('wp_cache_incr')) {
			$cached = wp_cache_incr($cache_key, 1, 'apollo');
			if (false === $cached) {
				wp_cache_set($cache_key, $count, 'apollo', $window);
			}
		}

		if ($count > $max) {
			if (function_exists('apollo_telegram_debug_log')) {
				apollo_telegram_debug_log(
					'rate_limit_hit',
					array(
						'scope' => preg_replace('/\d{6,}/', '***', $scope),
						'max'   => $max,
					)
				);
			}

			return false;
		}

		return true;
	}

	/**
	 * Client IP for rate-limit scopes.
	 */
	public static function client_ip(): string
	{
		foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $header) {
			if (empty($_SERVER[ $header ])) {
				continue;
			}
			$raw = sanitize_text_field(wp_unslash((string) $_SERVER[ $header ]));
			if (str_contains($raw, ',')) {
				$raw = trim(explode(',', $raw)[0]);
			}
			if (filter_var($raw, FILTER_VALIDATE_IP)) {
				return $raw;
			}
		}

		return '0.0.0.0';
	}
}
