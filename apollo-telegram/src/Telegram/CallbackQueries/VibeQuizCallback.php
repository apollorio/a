<?php

namespace Apollo\Telegram\Telegram\CallbackQueries;

use Apollo\Telegram\Telegram\CallbackQueries\Base;
use Apollo\Telegram\Telegram\ExtendedClasses\Request;
use Apollo\Telegram\Services\Integrations\EventsBotService;
use Apollo\Telegram\Services\Lang;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * VibeQuizCallback — handles taps on the a/b/c/d "what kind of party suits
 * you" inline keyboard (see EventsBotService::sendVibeQuiz()).
 *
 * Callback data syntax: `vibequiz:a` / `vibequiz:b` / `vibequiz:c` / `vibequiz:d`
 * — auto-discovered by CallbackQueryHandler purely from this filename
 * (VibeQuizCallback.php → command "vibequiz"), same convention as every
 * other *Callback.php file in this folder. No new logic lives here beyond
 * wiring the tap to EventsBotService::replyForVibeAnswer() — that single
 * method is also what a bare-text "b" reply calls from GenericCommand, so
 * tapping the button and typing the letter behave identically.
 */
class VibeQuizCallback extends Base
{
	/**
	 * @var	string
	 */
	protected $name = 'vibequiz';

	/**
	 * @var	string
	 */
	protected $description = 'Handles a tap on the a/b/c/d vibe-quiz inline keyboard.';

	/**
	 * @var	string
	 */
	protected $syntax = 'vibequiz:{a|b|c|d}';

	/**
	 * Callback query execute method.
	 *
	 * @return	ServerResponse
	 */
	public function execute(): ServerResponse
	{
		$answer  = strtolower(trim($this->callbackDataWithoutCommand));
		$chat_id = (int) $this->callbackQuery->getMessage()->getChat()->getId();

		if (! class_exists(EventsBotService::class)) {
			return $this->answer();
		}

		$telegram_from = $this->callbackQuery->getFrom();
		$lang          = Lang::resolveForChat($chat_id, $telegram_from ? $telegram_from->getLanguageCode() : null);

		$reply = EventsBotService::replyForVibeAnswer($chat_id, $answer, $lang);

		// Close the button's loading state with a short toast…
		$this->answer('🎉');

		// …then send the actual event list as its own message, same as every
		// other events-intelligence reply in this bot (HTML, link previews off).
		Request::sendMessage(array(
			'chat_id'                  => $chat_id,
			'text'                     => $reply,
			'parse_mode'               => 'HTML',
			'disable_web_page_preview' => true,
		));

		return Request::emptyResponse();
	}
}
