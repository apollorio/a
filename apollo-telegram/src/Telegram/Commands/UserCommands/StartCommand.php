<?php

namespace Apollo\Telegram\Telegram\Commands\UserCommands;

use Apollo\Telegram\Security\RateLimiter;
use Apollo\Telegram\Services\Lang;
use Apollo\Telegram\Services\VerificationService;
use Apollo\Telegram\Telegram\ExtendedClasses\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Start command — deep-link OTP delivery (fast path).
 *
 * Bilingual (2026-08-25): resolves the chat's language once via
 * Lang::resolveForChat() — /start is usually the FIRST message a chat ever
 * sends, so this is normally where a chat's language gets decided (from
 * Telegram's own client language_code) and persisted for every reply after.
 */
class StartCommand extends UserCommand
{
	protected $name = 'start';

	protected $description = 'Start command.';

	protected $usage = '/start';

	protected $version = '1.3.0';

	public function execute(): ServerResponse
	{
		$message       = $this->getMessage();
		$chat_id       = (int) $message->getChat()->getId();
		$text          = trim($message->getText() ?? '');
		$telegram_from = $message->getFrom();
		$lang          = Lang::resolveForChat($chat_id, $telegram_from ? $telegram_from->getLanguageCode() : null);

		if (! RateLimiter::allow('start_chat:' . $chat_id, 10, 5 * MINUTE_IN_SECONDS)) {
			return $this->replyToChat(
				Lang::t('rate_limited_generic', $lang, array(), $chat_id),
				array('parse_mode' => 'HTML')
			);
		}

		$payload = '';
		if (str_starts_with($text, '/start ')) {
			$payload = trim(substr($text, 7));
		}

		$is_uuid = (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
			$payload
		);

		// #region agent log
		$trail = get_option('apollo_tg_debug_start_trail', array());
		if (! is_array($trail)) {
			$trail = array();
		}
		$trail[] = array(
			'hypothesisId' => 'B',
			'event'        => 'start_received',
			'chat_id'      => $chat_id,
			'has_payload'  => '' !== $payload,
			'is_uuid'      => $is_uuid,
			'timestamp'    => time(),
		);
		update_option('apollo_tg_debug_start_trail', array_slice($trail, -20), false);
		// #endregion

		// Fast path: /start {request_id} → mint + reply OTP immediately.
		if ($is_uuid && class_exists(VerificationService::class)) {
			$request_id = strtolower($payload);
			set_transient('apollo_tg_start_' . $chat_id, $request_id, 15 * MINUTE_IN_SECONDS);

			$result = VerificationService::deliverCodeForStart($request_id, $chat_id);

			if (! empty($result['success']) && ! empty($result['code'])) {
				$response_text  = Lang::t('start_code_header', $lang, array(), $chat_id) . "\n\n";
				$response_text .= '🔢 <b>' . esc_html((string) $result['code']) . "</b>\n\n";
				$response_text .= Lang::t('start_code_footer', $lang, array(), $chat_id);

				return $this->replyToChat(
					$response_text,
					array(
						'parse_mode'               => 'HTML',
						'disable_web_page_preview' => true,
					)
				);
			}

			$fail_msg = $result['message'] ?? Lang::t('start_invalid_request', $lang, array(), $chat_id);

			return $this->replyToChat(
				'⚠️ ' . $fail_msg,
				array('parse_mode' => 'HTML')
			);
		}

		// Plain /start without token — welcome + optional contact share.
		$kb = array(
			'keyboard'          => array(
				array(
					array(
						'text'            => Lang::t('start_share_contact', $lang),
						'request_contact' => true,
					),
				),
			),
			'resize_keyboard'   => true,
			'one_time_keyboard' => true,
		);

		return $this->replyToChat(
			Lang::t('start_welcome', $lang, array(), $chat_id),
			array(
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => true,
				'reply_markup'             => wp_json_encode($kb),
			)
		);
	}
}
