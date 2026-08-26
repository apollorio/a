<?php
/**
 * PSR-4 autoload for Apollo\Telegram when Composer vendor is absent.
 *
 * @package Apollo\Telegram
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

spl_autoload_register(
	static function (string $class): void {
		$prefix = 'Apollo\\Telegram\\';
		if (0 !== strpos($class, $prefix)) {
			return;
		}

		$relative = str_replace('\\', '/', substr($class, strlen($prefix)));
		$file     = APOLLO_TELEGRAM_DIR . 'src/' . $relative . '.php';

		if (is_readable($file)) {
			require_once $file;
		}
	}
);
