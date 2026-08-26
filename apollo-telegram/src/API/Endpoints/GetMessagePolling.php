<?php

namespace Apollo\Telegram\API\Endpoints;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\TelegramLog;
use Apollo\Telegram\API\BaseEndpoint;
use Apollo\Telegram\Helpers\TelegramHelper;
use Apollo\Telegram\Telegram\ExtendedClasses\Telegram;

class GetMessagePolling extends BaseEndpoint
{
	public $namespace = 'APOLLO_TELEGRAM/v1';
	public $route     = 'get-message-polling';
	public $method    = 'GET';

	public function checkPermission($request = null)
	{
		if (function_exists('wp_get_environment_type') && 'local' !== wp_get_environment_type()) {
			return false;
		}

		if (! is_user_logged_in() || ! current_user_can('manage_options')) {
			return false;
		}

		// Browser GET to wp-json requires wp_rest nonce (cookie alone is not enough).
		if ($request instanceof \WP_REST_Request) {
			$nonce = $request->get_header('X-WP-Nonce');
			if (empty($nonce)) {
				$nonce = $request->get_param('_wpnonce');
			}
			if (empty($nonce) || ! wp_verify_nonce((string) $nonce, 'wp_rest')) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Uses `getUpdates` method and fetches updates from Telegram.
	 *
	 * @param	\WP_REST_Request	$request	The current matched request object.
	 *
	 * @return	\WP_REST_Response
	 */
	public function handle($request)
	{
		if (wp_get_environment_type() !== 'local')
			return $this->getRestResponse(401, esc_html__('Not allowed!', 'apollo-telegram'));

		$telegram = TelegramHelper::instantiateTelegram();
		if (!$telegram instanceof Telegram)
			return $this->getRestResponse(502, $telegram);

		try {
			$serverResponse = $telegram->handleGetUpdates();
			if ($serverResponse instanceof ServerResponse && $serverResponse->isOk())
				return $this->getRestResponse(200);

			return $this->getRestResponse(502, $serverResponse->printError(true));
		} catch (\Exception $e) {
			TelegramLog::error($e);

			return $this->getRestResponse(502, esc_html__('Error on handling the updates!', 'apollo-telegram'));
		}
	}
}

