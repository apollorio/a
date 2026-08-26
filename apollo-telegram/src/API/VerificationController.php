<?php

/**
 * REST API — phone verification + broadcast.
 *
 * @package Apollo\Telegram\API
 */

declare(strict_types=1);

namespace Apollo\Telegram\API;

use Apollo\Telegram\Security\RateLimiter;
use Apollo\Telegram\Services\VerificationService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
	exit;
}

final class VerificationController
{
	public static function register_routes(): void
	{
		register_rest_route(
			'apollo-telegram/v1',
			'/request-support-verification',
			array(
				'methods'             => 'POST',
				'callback'            => array(self::class, 'request_verification'),
				'permission_callback' => array(self::class, 'permission_verify_nonce'),
				'args'                => array(
					'phone'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'nonce'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apollo-telegram/v1',
			'/verify-support-code',
			array(
				'methods'             => 'POST',
				'callback'            => array(self::class, 'verify_code'),
				'permission_callback' => array(self::class, 'permission_verify_nonce'),
				'args'                => array(
					'phone'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'request_id' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'code'       => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'nonce'      => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apollo-telegram/v1',
			'/verification-status',
			array(
				'methods'             => 'POST',
				'callback'            => array(self::class, 'verification_status'),
				'permission_callback' => array(self::class, 'permission_verify_nonce'),
				'args'                => array(
					'phone'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'request_id' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'nonce'      => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apollo-telegram/v1',
			'/poll-tick',
			array(
				'methods'             => 'POST',
				'callback'            => array(self::class, 'poll_tick'),
				'permission_callback' => array(self::class, 'permission_verify_nonce'),
				'args'                => array(
					'nonce' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apollo-telegram/v1',
			'/check-phone-available',
			array(
				'methods'             => 'POST',
				'callback'            => array(self::class, 'check_phone_available'),
				'permission_callback' => array(self::class, 'permission_verify_nonce'),
				'args'                => array(
					'phone' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'nonce' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apollo-telegram/v1',
			'/telegram',
			array(
				'methods'             => array('GET', 'POST'),
				'callback'            => array(self::class, 'legacy_link_key'),
				'permission_callback' => array(self::class, 'permission_legacy_debug'),
				'args'                => array(
					'phone' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'nonce' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apollo-telegram/v1',
			'/broadcast',
			array(
				'methods'             => 'POST',
				'callback'            => array(self::class, 'broadcast'),
				'permission_callback' => static function (): bool {
					return current_user_can('manage_options');
				},
				'args'                => array(
					'message'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'chat_ids' => array(
						'required'          => false,
						'type'              => 'array',
						'sanitize_callback' => static function ($value) {
							if (! is_array($value)) {
								return array();
							}

							return array_map('absint', $value);
						},
					),
				),
			)
		);
	}

	public static function permission_verify_nonce(WP_REST_Request $request): bool
	{
		return apollo_telegram_verify_rest_nonce($request);
	}

	public static function permission_legacy_debug(WP_REST_Request $request): bool
	{
		if (! defined('WP_DEBUG') || ! WP_DEBUG) {
			return false;
		}

		return apollo_telegram_verify_rest_nonce($request);
	}

	public static function request_verification(WP_REST_Request $request): WP_REST_Response
	{
		$phone       = (string) $request->get_param('phone');
		$web_user_id = get_current_user_id();

		$result = VerificationService::createPending($phone, $web_user_id);

		$status = ! empty($result['success']) ? 200 : (('rate_limited' === ($result['code'] ?? '')) ? 429 : 400);

		return new WP_REST_Response($result, $status);
	}

	public static function verify_code(WP_REST_Request $request): WP_REST_Response
	{
		$result = VerificationService::verifyCode(
			(string) $request->get_param('request_id'),
			(string) $request->get_param('phone'),
			(string) $request->get_param('code')
		);

		$status = ! empty($result['success']) ? 200 : (('rate_limited' === ($result['code'] ?? '')) ? 429 : 400);

		return new WP_REST_Response($result, $status);
	}

	public static function verification_status(WP_REST_Request $request): WP_REST_Response
	{
		$ip = RateLimiter::client_ip();
		if (! RateLimiter::allow('verif_status:' . $ip, 60, 5 * MINUTE_IN_SECONDS)) {
			return new WP_REST_Response(array('success' => false, 'code' => 'rate_limited'), 429);
		}

		$result = VerificationService::getStatus(
			(string) $request->get_param('request_id'),
			(string) $request->get_param('phone')
		);

		return new WP_REST_Response($result, ! empty($result['success']) ? 200 : 404);
	}

	public static function check_phone_available(WP_REST_Request $request): WP_REST_Response
	{
		$normalized = apollo_telegram_normalize_phone((string) $request->get_param('phone'));
		if (null === $normalized) {
			return new WP_REST_Response(
				array(
					'success'   => false,
					'available' => false,
					'message'   => __('Número de telefone inválido.', 'apollo-telegram'),
				),
				400
			);
		}

		if (VerificationService::phoneAlreadyRegistered($normalized)) {
			return new WP_REST_Response(
				array(
					'success'   => true,
					'available' => false,
					'message'   => __('Este telefone já está registrado.', 'apollo-telegram'),
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'available' => true,
				'message'   => __('Telefone disponível.', 'apollo-telegram'),
			),
			200
		);
	}

	/**
	 * Local dev only: process pending getUpdates so the bot answers without a
	 * webhook/tunnel. No-op (mode: webhook) in production. Lock prevents
	 * concurrent getUpdates (Telegram API 409).
	 */
	public static function poll_tick(WP_REST_Request $request): WP_REST_Response
	{
		if (! apollo_telegram_uses_local_polling()) {
			return new WP_REST_Response(array('success' => true, 'mode' => 'webhook'), 200);
		}

		$ip = RateLimiter::client_ip();
		if (! RateLimiter::allow('poll_tick:' . $ip, 30, MINUTE_IN_SECONDS)) {
			return new WP_REST_Response(array('success' => false, 'code' => 'rate_limited'), 429);
		}

		if (false !== get_transient('apollo_tg_poll_lock')) {
			return new WP_REST_Response(array('success' => true, 'mode' => 'polling', 'skipped' => 'locked'), 200);
		}
		set_transient('apollo_tg_poll_lock', 1, 10);

		try {
			$telegram = \Apollo\Telegram\Helpers\TelegramHelper::instantiateTelegram();
			if (! $telegram instanceof \Apollo\Telegram\Telegram\ExtendedClasses\Telegram) {
				return new WP_REST_Response(array('success' => false, 'message' => (string) $telegram), 502);
			}

			// getUpdates conflicts with an active webhook — drop it once per hour.
			if (false === get_transient('apollo_tg_webhook_dropped')) {
				\Apollo\Telegram\Telegram\ExtendedClasses\Request::deleteWebhook(array());
				set_transient('apollo_tg_webhook_dropped', 1, HOUR_IN_SECONDS);
			}

			$response = $telegram->handleGetUpdates(array('timeout' => 0));

			return new WP_REST_Response(
				array(
					'success' => $response->isOk(),
					'mode'    => 'polling',
				),
				$response->isOk() ? 200 : 502
			);
		} catch (\Throwable $e) {
			apollo_telegram_debug_log('poll_tick_failed', array('error' => $e->getMessage()));

			return new WP_REST_Response(array('success' => false), 502);
		} finally {
			delete_transient('apollo_tg_poll_lock');
		}
	}

	public static function legacy_link_key(WP_REST_Request $request): WP_REST_Response
	{
		if (! function_exists('apollo_telegram')) {
			return new WP_REST_Response(array('success' => false, 'message' => 'Service unavailable.'), 503);
		}

		$phone   = $request->get_param('phone') ?: null;
		$service = apollo_telegram();
		$result  = $service->generateLinkKey($phone, array('source' => 'legacy_debug'));

		return new WP_REST_Response(
			array(
				'success'   => true,
				'key'       => $result['key'],
				'deep_link' => $result['deep_link'],
			),
			200
		);
	}

	public static function broadcast(WP_REST_Request $request): WP_REST_Response
	{
		if (! function_exists('apollo_telegram_broadcast')) {
			return new WP_REST_Response(array('success' => false), 503);
		}

		$message  = (string) $request->get_param('message');
		$chat_ids = $request->get_param('chat_ids') ?: array();
		$result   = apollo_telegram_broadcast($message, is_array($chat_ids) ? $chat_ids : array());

		return new WP_REST_Response(
			array(
				'success' => ($result['failed'] ?? 0) === 0,
				'result'  => $result,
			),
			200
		);
	}
}
