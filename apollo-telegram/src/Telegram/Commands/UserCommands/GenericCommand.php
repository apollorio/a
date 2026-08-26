<?php

namespace Apollo\Telegram\Telegram\Commands\UserCommands;

use Apollo\Telegram\Services\Lang;
use Apollo\Telegram\Services\VerificationService;
use Apollo\Telegram\Services\Integrations\EventsBotService;
use Apollo\Telegram\Telegram\ExtendedClasses\Commands\UserCommand;
use Apollo\Telegram\Telegram\ExtendedClasses\Request;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Generic command — contact share verification flow + events intelligence.
 *
 * Bilingual (2026-08-25): resolves the chat's language once via
 * Lang::resolveForChat() (sticky from the first message on) and threads it
 * through every reply on this path, including EventsBotService.
 */
class GenericCommand extends UserCommand
{
	protected $name = 'generic';

	protected $description = 'Handles non-command messages (contact share for phone verification).';

	protected $usage = '/generic';

	protected $version = '1.2.0';

	public function execute(): ServerResponse
	{
		$message = $this->getMessage();
		if (! $message) {
			return Request::emptyResponse();
		}

		$chat_id       = (int) $message->getChat()->getId();
		$text          = trim($message->getText() ?? '');
		$telegram_from = $message->getFrom();
		$lang          = Lang::resolveForChat($chat_id, $telegram_from ? $telegram_from->getLanguageCode() : null);

		$contact = $message->getContact();
		if ($contact) {
			if (! class_exists(VerificationService::class)) {
				return Request::emptyResponse();
			}

			if (! apollo_rl('contact_' . $chat_id, 3, 600)) {
				return $this->replyToChat(
					Lang::t('generic_limit_reached', $lang, array(), $chat_id),
					array('reply_to_message_id' => $message->getMessageId())
				);
			}

			$tg_phone = apollo_telegram_normalize_phone($contact->getPhoneNumber() ?? '');
			if (null === $tg_phone) {
				return $this->replyToChat(
					Lang::t('generic_invalid_phone', $lang, array(), $chat_id),
					array('reply_to_message_id' => $message->getMessageId())
				);
			}

			// Resolve request_id: message text (rare) or the /start deep-link payload
			// persisted by StartCommand (contact-share messages carry no text).
			$request_id = null;
			if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $text)) {
				$request_id = strtolower($text);
			} else {
				$stored = get_transient('apollo_tg_start_' . $chat_id);
				if (is_string($stored) && '' !== $stored) {
					$request_id = $stored;
				}
			}

			$result = VerificationService::bindContact($tg_phone, $chat_id, $request_id);

			if (empty($result['success'])) {
				return $this->replyToChat(
					$result['message'] ?? Lang::t('generic_verify_not_found', $lang, array(), $chat_id),
					array('reply_to_message_id' => $message->getMessageId())
				);
			}

			$code = $result['code'] ?? '';
			delete_transient('apollo_tg_start_' . $chat_id);

			$response_text  = "✅ " . Lang::t('generic_confirmed_header', $lang, array(), $chat_id) . "\n\n";
			$response_text .= '🔢 <b>' . esc_html($code) . "</b>\n\n";
			$response_text .= Lang::t('generic_confirmed_footer', $lang, array(), $chat_id);

			return $this->replyToChat(
				$response_text,
				array(
					'reply_to_message_id' => $message->getMessageId(),
					'parse_mode'          => 'HTML',
					'reply_markup'        => wp_json_encode(array('remove_keyboard' => true)),
				)
			);
		}

		if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $text)) {
			return $this->replyToChat(
				Lang::t('generic_use_button', $lang, array(), $chat_id),
				array('reply_to_message_id' => $message->getMessageId())
			);
		}

		// Vibe-quiz bare-letter reply ("b" instead of tapping the inline
		// button). Only reinterpreted as a quiz answer while a quiz is
		// actually pending for this chat (hasPendingIntent()) — otherwise a
		// stray "a" typed for any other reason would misfire as one.
		if (class_exists(EventsBotService::class)) {
			$maybe_letter = strtolower($text);
			if (
				in_array($maybe_letter, array('a', 'b', 'c', 'd'), true)
				&& EventsBotService::hasPendingIntent($chat_id)
			) {
				$reply = EventsBotService::replyForVibeAnswer($chat_id, $maybe_letter, $lang);

				return $this->replyToChat(
					$reply,
					array(
						'parse_mode'               => 'HTML',
						'disable_web_page_preview' => true,
					)
				);
			}
		}

		// Events intelligence: "qual a boa", "esse fds", "tem festa hoje?",
		// "what's good tonight?"… Only replies when an events/weather/
		// language-switch intent is detected. Guard: EventsBotService may
		// not be loaded (graceful degradation).
		$events_reply = null;
		if (class_exists(EventsBotService::class)) {
			$events_reply = EventsBotService::handle($text, $chat_id, $lang);
		}
		if (null !== $events_reply) {
			return $this->replyToChat(
				$events_reply,
				array(
					'parse_mode'               => 'HTML',
					'disable_web_page_preview' => true,
				)
			);
		}

		return Request::emptyResponse();
	}
}
