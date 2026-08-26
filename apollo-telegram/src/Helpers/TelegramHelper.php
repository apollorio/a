<?php

namespace Apollo\Telegram\Helpers;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramLogException;
use Longman\TelegramBot\TelegramLog;
use Apollo\Telegram\Telegram\ExtendedClasses\Telegram;

class TelegramHelper
{
	/**
	 * Instantiates and returns Telegram object.
	 *
	 * @return	Telegram|string		Returns the error on failure.
	 */
	public static function instantiateTelegram()
	{
		if (empty($botToken = apollo_telegram_config('bot_token')))
			return esc_html__('Bot token is not defined!', 'apollo-telegram');

		if (empty($botUsername = apollo_telegram_config('bot_username')))
			return esc_html__('Bot username is not defined!', 'apollo-telegram');
		// Longman convention: bot username WITHOUT the @ prefix (breaks /cmd@bot matching otherwise).
		$botUsername = ltrim($botUsername, '@');

		try {
			$telegram = new Telegram($botToken, $botUsername);
			// TODO: $telegram->enableAdmins($bot->get_admin_ids());
			$telegram->addCommandsPaths([APOLLO_TELEGRAM_DIR . '/src/Telegram/Commands']);
			$telegram->enableMySql();
			$telegram->enableLogging();
			$telegram->enableLimiter(['enabled' => true]);

			if (!empty($admins = apollo_telegram_config('admin_ids')))
				$telegram->enableAdmins(explode(',', $admins));
		} catch (TelegramException $e) {
			TelegramLog::error($e);

			if (defined('WP_DEBUG') && WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log('[apollo-telegram] instantiateTelegram: ' . $e->getMessage());
			}

			return defined('WP_DEBUG') && WP_DEBUG
				? esc_html__('Error on initializing the bot!', 'apollo-telegram') . ' [' . $e->getMessage() . ']'
				: esc_html__('Error on initializing the bot!', 'apollo-telegram');
		} catch (TelegramLogException $e) {
			return esc_html__('Error on logging the exception!', 'apollo-telegram');
		}

		return $telegram;
	}
}
