<?php
/**
 * Telegram webhook secret_token management.
 *
 * @package Apollo\Telegram\Services
 */

declare(strict_types=1);

namespace Apollo\Telegram\Services;

if (! defined('ABSPATH')) {
	exit;
}

final class WebhookService
{
	private const OPTION_SECRET = 'webhook_secret_token';

	/**
	 * Get or generate webhook secret (stored in plugin options).
	 */
	public static function getSecretToken(): string
	{
		$stored = (string) apollo_telegram_config(self::OPTION_SECRET, '');
		if ('' !== $stored) {
			return $stored;
		}

		$secret = wp_generate_password(32, false, false);
		$opts   = get_option(APOLLO_TELEGRAM_OPTIONS_KEY, array());
		if (! is_array($opts)) {
			$opts = array();
		}
		$opts[ self::OPTION_SECRET ] = $secret;
		update_option(APOLLO_TELEGRAM_OPTIONS_KEY, $opts);

		return $secret;
	}

	/**
	 * Validate incoming webhook request header.
	 */
	public static function validateIncoming(\WP_REST_Request $request): bool
	{
		$expected = self::getSecretToken();
		if ('' === $expected) {
			return false;
		}

		$received = (string) $request->get_header('X-Telegram-Bot-Api-Secret-Token');

		return hash_equals($expected, $received);
	}

	/**
	 * Register webhook URL with Telegram API (HTTPS + secret_token).
	 *
	 * @param string $url Full webhook URL.
	 */
	public static function registerWebhook(string $url): bool
	{
		if (function_exists('apollo_telegram_uses_local_polling') && apollo_telegram_uses_local_polling()) {
			apollo_telegram_debug_log('webhook_register_skipped_local_dev', array('url' => $url));

			return false;
		}

		$token  = apollo_telegram_bot_token();
		$secret = self::getSecretToken();

		if ('' === $token || '' === $url) {
			return false;
		}

		$api = 'https://api.telegram.org/bot' . $token . '/setWebhook';
		$body = array(
			'url'          => $url,
			'secret_token' => $secret,
			'allowed_updates' => wp_json_encode(array('message', 'callback_query')),
		);

		$response = wp_remote_post(
			$api,
			array(
				'timeout' => 15,
				'body'    => $body,
			)
		);

		if (is_wp_error($response)) {
			apollo_telegram_debug_log('webhook_register_failed', array('error' => $response->get_error_message()));

			return false;
		}

		$data = json_decode(wp_remote_retrieve_body($response), true);

		return ! empty($data['ok']);
	}
}
