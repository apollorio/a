<?php

namespace Apollo\Telegram\Telegram\CallbackQueries;

use Longman\TelegramBot\Entities\ServerResponse;
use Apollo\Telegram\Telegram\CallbackQueries\Base;

/**
 * Example callback query.
 */
class ExampleCallback extends Base
{
	/**
	 * @var	string
	 */
	protected $name = 'example';

	/**
	 * @var	string
	 */
	protected $description = 'An example callback.';

	/**
	 * @var	string
	 */
	protected $syntax = 'Example:{PostID}';

	/**
	 * Callback query execute method.
	 *
	 * @return	ServerResponse
	 */
	public function execute(): ServerResponse
	{
		/* if (!SomeValidCheck)
			return $this->answer(); */

		// Some other stuff

		return $this->answer(esc_html__('Example answer!', 'apollo-telegram'), ['show_alert' => true]);
	}
}

