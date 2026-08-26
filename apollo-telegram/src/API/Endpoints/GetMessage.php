<?php

namespace Apollo\Telegram\API\Endpoints;

use Longman\TelegramBot\TelegramLog;
use Apollo\Telegram\API\BaseEndpoint;
use Apollo\Telegram\Helpers\TelegramHelper;
use Apollo\Telegram\Telegram\ExtendedClasses\Telegram;

class GetMessage extends BaseEndpoint
{
	public $namespace = 'APOLLO_TELEGRAM/v1';
	public $route     = 'get-message';
	public $method    = 'POST';

	/**
	 * Handles API request when the authorization was successful.
	 *
	 * @param	\WP_REST_Request	$request	The current matched request object.
	 *
	 * @return	\WP_REST_Response
	 */
	public function checkPermission($request = null)
	{
		if ($request instanceof \WP_REST_Request) {
			return \Apollo\Telegram\Services\WebhookService::validateIncoming($request);
		}

		return false;
	}

	public function handle($request)
	{
		$telegram = TelegramHelper::instantiateTelegram();
		if (!$telegram instanceof Telegram)
			return $this->getRestResponse(502, $telegram);

		try {
			if ($telegram->handle()) return $this->getRestResponse(200);
			else return $this->getRestResponse(502);
		} catch (\Exception $e) {
			TelegramLog::error($e);

			return $this->getRestResponse(502, esc_html__('Error on handling the updates!', 'apollo-telegram'));
		}
	}
}

