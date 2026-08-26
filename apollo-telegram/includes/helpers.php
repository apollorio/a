<?php
/**
 * Apollo Telegram — shared helpers (bootstrap-safe).
 *
 * @package Apollo\Telegram
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Read plugin option with wp-config constant override for secrets.
 *
 * @param string $key     Option key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function apollo_telegram_config(string $key, mixed $default = null): mixed
{
	if ('bot_token' === $key && defined('APOLLO_TELEGRAM_BOT_TOKEN') && '' !== APOLLO_TELEGRAM_BOT_TOKEN) {
		return APOLLO_TELEGRAM_BOT_TOKEN;
	}

	if ('bot_username' === $key && defined('APOLLO_TELEGRAM_BOT_USERNAME') && '' !== APOLLO_TELEGRAM_BOT_USERNAME) {
		return APOLLO_TELEGRAM_BOT_USERNAME;
	}

	$opts = get_option(APOLLO_TELEGRAM_OPTIONS_KEY, []);

	return $opts[ $key ] ?? $default;
}

/**
 * Bot token from wp-config or options only (never hardcoded in plugin).
 */
function apollo_telegram_bot_token(): string
{
	return (string) apollo_telegram_config('bot_token', '');
}

/**
 * Bot username without @ prefix.
 */
function apollo_telegram_bot_username(): string
{
	$user = (string) apollo_telegram_config('bot_username', '');

	return ltrim($user, '@');
}
/**
 * LocalWP / local dev: receive updates via getUpdates (REST polling), not HTTPS webhook.
 */
function apollo_telegram_uses_local_polling(): bool
{
	if (defined('APOLLO_TELEGRAM_DEV_LOCAL') && APOLLO_TELEGRAM_DEV_LOCAL) {
		return true;
	}

	return function_exists('wp_get_environment_type') && 'local' === wp_get_environment_type();
}

/**
 * PDO credentials aligned with wpdb (LocalWP: localhost mysqli uses TCP port, PDO needs 127.0.0.1:port).
 *
 * @return array{user:string,password:string,database:string,host?:string,port?:int,unix_socket?:string}
 */
function apollo_telegram_wp_db_credentials(): array
{
	global $wpdb;

	$credentials = array(
		'user'     => DB_USER,
		'password' => DB_PASSWORD,
		'database' => DB_NAME,
	);

	$parsed = $wpdb->parse_db_host(DB_HOST);
	if (false === $parsed) {
		$credentials['host'] = DB_HOST;
		return $credentials;
	}

	list($host, $port, $socket, $is_ipv6) = $parsed;

	if (! empty($socket)) {
		$credentials['unix_socket'] = $socket;
		return $credentials;
	}

	if ('localhost' === $host && empty($port) && $wpdb->dbh instanceof \mysqli) {
		$port_row = $wpdb->get_row("SHOW VARIABLES LIKE 'port'");
		if ($port_row && ! empty($port_row->Value)) {
			$credentials['host'] = '127.0.0.1';
			$credentials['port'] = (int) $port_row->Value;
			return $credentials;
		}
	}

	$credentials['host'] = $host;
	if ($port) {
		$credentials['port'] = (int) $port;
	}

	return $credentials;
}


/**
 * Normalize phone to digits only; reject invalid lengths.
 *
 * @param string $phone Raw phone.
 * @return string|null Digits or null if invalid.
 */
function apollo_telegram_normalize_phone(string $phone): ?string
{
	$digits = preg_replace('/\D/', '', $phone) ?? '';

	if (strlen($digits) < 8 || strlen($digits) > 15) {
		return null;
	}

	return $digits;
}

/** @deprecated Use apollo_telegram_normalize_phone() */
function normalizar_phone(string $phone): string
{
	return apollo_telegram_normalize_phone($phone) ?? '';
}

/**
 * Truncate phone for logs (never log full number).
 */
function apollo_telegram_phone_log_suffix(string $phone): string
{
	$digits = preg_replace('/\D/', '', $phone) ?? '';

	if (strlen($digits) < 4) {
		return '****';
	}

	return '***' . substr($digits, -4);
}

/**
 * Debug-only structured log (no tokens, codes, or full phones).
 *
 * @param string               $event Event name.
 * @param array<string, mixed> $data  Context.
 */
function apollo_telegram_debug_log(string $event, array $data = array()): void
{
	if (! defined('WP_DEBUG') || ! WP_DEBUG) {
		return;
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log('[apollo-telegram] ' . $event . ' ' . wp_json_encode($data));
}

/**
 * Verify REST nonce for public verification endpoints.
 */
function apollo_telegram_verify_rest_nonce(\WP_REST_Request $request): bool
{
	$nonce = $request->get_header('X-Apollo-Telegram-Nonce');
	if (empty($nonce)) {
		$nonce = $request->get_param('nonce');
	}

	return (bool) wp_verify_nonce((string) $nonce, 'apollo_telegram_verify');
}

/**
 * Boilerplate-compatible accessor (options + url); Core when available.
 *
 * @return object
 */
function APOLLO_TELEGRAM(): object
{
	global $apolloTelegramCore;

	if ($apolloTelegramCore instanceof \Apollo\Telegram\Core) {
		return $apolloTelegramCore;
	}

	static $facade = null;

	if (null === $facade) {
		$facade = new class() {
			public function option(string $name, mixed $default = null): mixed
			{
				return apollo_telegram_config($name, $default);
			}

			public function url(string $path = ''): string
			{
				return untrailingslashit(APOLLO_TELEGRAM_URL . $path);
			}

			public function view(string $filePath, array $passedArray = array(), bool $echo = true): mixed
			{
				return null;
			}
		};
	}

	return $facade;
}

/**
 * Legacy rate limit wrapper — delegates to RateLimiter when loaded.
 *
 * @param string $key    Scope key.
 * @param int    $max    Max hits.
 * @param int    $window Window seconds.
 */
function apollo_rl(string $key, int $max = 5, int $window = 300): bool
{
	if (class_exists(\Apollo\Telegram\Security\RateLimiter::class)) {
		return \Apollo\Telegram\Security\RateLimiter::allow($key, $max, $window);
	}

	$transient = 'apollo_rl_' . md5($key);
	$data      = get_transient($transient);
	if (! is_array($data)) {
		$data = array(
			'count' => 0,
			'reset' => time() + $window,
		);
	}
	if (time() > (int) $data['reset']) {
		$data = array(
			'count' => 0,
			'reset' => time() + $window,
		);
	}
	if ((int) $data['count'] >= $max) {
		return false;
	}
	++$data['count'];
	set_transient($transient, $data, $window);

	return true;
}
