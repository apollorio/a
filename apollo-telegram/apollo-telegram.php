<?php



/**

 * Plugin Name: Apollo Telegram

 * Plugin URI:  https://apollo.rio.br

 * Description: Apollo Telegram Bot - Independent test plugin for Telegram Bot integration with Apollo Rio. Test mode only. No registry involvement yet.

 * Version: 	1.1.5

 * Author:      Apollo Rio

 * Author URI:  https://apollo.rio.br

 * License:     GPL-2.0-or-later

 * License URI: https://www.gnu.org/licenses/gpl-2.0.html

 * Text Domain: apollo-telegram

 * Domain Path: /languages

 * Requires at least: 6.4

 * Requires PHP: 8.1

 */



declare(strict_types=1);



use Apollo\Telegram\Container;

use Apollo\Telegram\Core;



if (! defined('ABSPATH')) {

	exit;
}



define('APOLLO_TELEGRAM_VERSION', '1.1.5');
// 2026-08-25b: voice pass — every conversational Lang::t() string now has
// 5 hand-written pt/en variants (close-friend-texting tone) with per-chat
// anti-repeat memory (Lang::pick()), so the bot never parrots the exact
// same line back to back. See src/Services/Lang.php class docblock.

define('APOLLO_TELEGRAM_FILE', __FILE__);

define('APOLLO_TELEGRAM_URL', plugin_dir_url(APOLLO_TELEGRAM_FILE));

define('APOLLO_TELEGRAM_DIR', plugin_dir_path(APOLLO_TELEGRAM_FILE));

define('APOLLO_TELEGRAM_BASENAME', plugin_basename(APOLLO_TELEGRAM_FILE));

define('APOLLO_TELEGRAM_SLUG', 'apollo-telegram');

define('APOLLO_TELEGRAM_OPTIONS_KEY', 'apollo_telegram_options');

define('APOLLO_TELEGRAM_DB_VERSION', '1.1.0');

define('APOLLO_TELEGRAM_OPTIONS_KEY_DB_VERSION', 'apollo_telegram_db_version');

if (! defined('APOLLO_TELEGRAM_MENUS_SLUG')) {
	define('APOLLO_TELEGRAM_MENUS_SLUG', 'apollo_telegram');
}



// Bot credentials: define in wp-config.php only — never hardcode in plugin source.

// define('APOLLO_TELEGRAM_BOT_TOKEN', 'your-token-from-botfather');

// define('APOLLO_TELEGRAM_BOT_USERNAME', 'YourBotUsername');



$__src = APOLLO_TELEGRAM_DIR . 'src/';

if (file_exists($__src . 'Container.php')) {

	require_once $__src . 'Container.php';
}

if (file_exists($__src . 'Core.php')) {

	require_once $__src . 'Core.php';
}



require_once APOLLO_TELEGRAM_DIR . 'includes/helpers.php';

require_once APOLLO_TELEGRAM_DIR . 'includes/autoload.php';

require_once APOLLO_TELEGRAM_DIR . 'src/Security/RateLimiter.php';

require_once APOLLO_TELEGRAM_DIR . 'src/Services/VerificationService.php';

require_once APOLLO_TELEGRAM_DIR . 'src/Services/WebhookService.php';

require_once APOLLO_TELEGRAM_DIR . 'src/Services/TelegramService.php';

require_once APOLLO_TELEGRAM_DIR . 'src/API/VerificationController.php';



if (file_exists(APOLLO_TELEGRAM_DIR . 'vendor/autoload.php')) {

	require_once APOLLO_TELEGRAM_DIR . 'vendor/autoload.php';
} else {

	require_once APOLLO_TELEGRAM_DIR . 'functions.php';
}



if (! function_exists('apollo_telegram')) {

	/**

	 * @return \Apollo\Telegram\Services\TelegramService

	 */

	function apollo_telegram(): \Apollo\Telegram\Services\TelegramService
	{

		static $instance = null;

		if (null === $instance) {

			$instance = new \Apollo\Telegram\Services\TelegramService();
		}



		return $instance;
	}
}



/** @var Core|null */

global $apolloTelegramCore;

$apolloTelegramCore = null;



try {

	if (class_exists(Container::class) && class_exists(Core::class)) {

		$container = new Container();

		$container->singleton(Container::class, static fn() => $container);

		$container->singleton(Core::class);

		$apolloTelegramCore = $container->make(Core::class);
	}
} catch (\Throwable $e) {

	if (defined('WP_DEBUG') && WP_DEBUG) {

		apollo_telegram_debug_log('core_init_skipped', array('error' => $e->getMessage()));
	}
}



add_action('admin_notices', static function (): void {

	if (! current_user_can('manage_options')) {

		return;
	}

	if ('' !== apollo_telegram_bot_token()) {

		return;
	}

	echo '<div class="notice notice-error"><p>';

	echo esc_html__('Apollo Telegram: defina APOLLO_TELEGRAM_BOT_TOKEN em wp-config.php ou nas opções do plugin.', 'apollo-telegram');

	echo '</p></div>';
});



add_action('rest_api_init', array(\Apollo\Telegram\API\VerificationController::class, 'register_routes'));

// Self-heal rewrites: stored rules went stale in production (both /wp-json/ and
// /telegram were 404). One-shot versioned flush regenerates the full rule set.
// Hard flush (true) also writes .htaccess so Apache passes /telegram to WordPress.
add_action(
	'init',
	static function (): void {
	$ver = '3';
		if (get_option('apollo_telegram_rewrites_ver') === $ver) {
			return;
		}
		flush_rewrite_rules(true);
		update_option('apollo_telegram_rewrites_ver', $ver);
		set_transient('apollo_telegram_rewrites_flushed', 1, 30);
		apollo_telegram_debug_log('rewrites_flushed', array('ver' => $ver));
	},
	99
);

// Admin notice confirming rewrite rules were regenerated.
add_action('admin_notices', static function (): void {
	if (! current_user_can('manage_options')) {
		return;
	}
	if (1 !== get_transient('apollo_telegram_rewrites_flushed')) {
		return;
	}
	delete_transient('apollo_telegram_rewrites_flushed');
	echo '<div class="notice notice-success is-dismissible"><p>';
	echo esc_html__('Apollo Telegram: regras de rewrite regeneradas. A rota /telegram e a REST API foram restauradas.', 'apollo-telegram');
	echo '</p></div>';
});

// Production: ensure the Telegram webhook is registered (daily check, skipped on local polling).
add_action(
	'init',
	static function (): void {
		if (apollo_telegram_uses_local_polling() || '' === apollo_telegram_bot_token()) {
			return;
		}
		if (false !== get_transient('apollo_tg_webhook_checked_v3')) {
			return;
		}

		// rest_route query form is immune to rewrite-rule corruption (unlike /wp-json/ pretty path).
		$webhook_url = add_query_arg('rest_route', '/APOLLO_TELEGRAM/v1/get-message', home_url('/'));
		$registered  = \Apollo\Telegram\Services\WebhookService::registerWebhook($webhook_url);

		// Retry soon on failure; daily re-check on success.
		set_transient('apollo_tg_webhook_checked_v3', $registered ? 1 : 0, $registered ? DAY_IN_SECONDS : 15 * MINUTE_IN_SECONDS);
		apollo_telegram_debug_log('webhook_daily_check', array('registered' => $registered, 'url' => $webhook_url));
	},
	20
);

add_action(
	'plugins_loaded',
	static function (): void {
		\Apollo\Telegram\Services\VerificationService::install_schema();
	},
	20
);



register_activation_hook(

	__FILE__,

	static function (): void {

		$default_opts = array(

			'bot_token'    => apollo_telegram_bot_token(),

			'bot_username' => apollo_telegram_bot_username() ?: 'apolloRio_bot',

			'test_mode'    => true,

		);

		add_option(APOLLO_TELEGRAM_OPTIONS_KEY, $default_opts);



		\Apollo\Telegram\Services\VerificationService::install_schema();



		if (! wp_next_scheduled('apollo_clean_expired_verifs')) {

			wp_schedule_event(time(), 'daily', 'apollo_clean_expired_verifs');
		}



		add_rewrite_rule('^telegram/?$', 'index.php?apollo_telegram_test=1', 'top');

		flush_rewrite_rules(false);
	}

);



add_action(

	'apollo_clean_expired_verifs',

	array(\Apollo\Telegram\Services\VerificationService::class, 'cleanup_expired')

);



register_deactivation_hook(

	__FILE__,

	static function (): void {

		wp_clear_scheduled_hook('apollo_clean_expired_verifs');

		flush_rewrite_rules(false);
	}

);



add_action(

	'init',

	static function (): void {

		add_rewrite_rule('^telegram/?$', 'index.php?apollo_telegram_test=1', 'top');
	},

	5

);



add_filter(

	'query_vars',

	static function (array $vars): array {

		$vars[] = 'apollo_telegram_test';



		return $vars;
	}

);



add_action(

	'template_redirect',

	static function (): void {

		$is_rewrite = ! empty(get_query_var('apollo_telegram_test'));

		$is_debug   = defined('WP_DEBUG') && WP_DEBUG

			&& isset($_GET['telegram-test'])

			&& '1' === sanitize_text_field(wp_unslash((string) $_GET['telegram-test']));



		if (! $is_rewrite && ! $is_debug) {
			return;
		}

		status_header(200);

		nocache_headers();

		header('Content-Type: text/html; charset=utf-8');

		header('X-Apollo-Telegram-Test: 1');



		$view = APOLLO_TELEGRAM_DIR . 'views/telegram-phone-support.php';

		if (is_readable($view)) {

			$apollo_telegram_page_config = array(

				'restUrl' => esc_url_raw(rest_url('apollo-telegram/v1')),

				'nonce'   => wp_create_nonce('apollo_telegram_verify'),

				'botUser' => apollo_telegram_bot_username(),

			);

			include $view;
		} else {

			echo '<h1>' . esc_html__('View missing', 'apollo-telegram') . '</h1>';
		}

		exit;
	},

	1

);



add_filter(

	'apollo/error/should_intercept',

	static function ($should, $uri) {

		$uri = (string) ($uri ?? '');

		if (0 === stripos($uri, '/telegram')) {

			return false;
		}



		return $should;
	},

	5,

	2

);



function apollo_telegram_get_linked_chats(): array

{

	return apollo_telegram()->getAllLinkedUsers();
}



function apollo_telegram_broadcast(string $message, array $opts = array()): array

{

	return apollo_telegram()->broadcastToAll($message, $opts);
}



function apollo_telegram_send(int|string $chat_id, string $text): bool

{

	return apollo_telegram()->sendMessage($chat_id, $text);
}



function apollo_telegram_link_chat(int|string $chat_id, array $data = array()): bool

{

	$linked              = apollo_telegram_get_linked_chats();

	$linked[$chat_id]  = array_merge($linked[$chat_id] ?? array(), $data, array('linked_at' => time()));

	update_option('apollo_telegram_linked_chats', $linked);



	return true;
}



function apollo_telegram_send_message(int|string $chat_id, string $text, string $parse_mode = 'HTML'): bool

{

	if (empty($chat_id) || '' === $text) {

		return false;
	}



	$bot_token = apollo_telegram_bot_token();

	if ('' === $bot_token) {

		apollo_telegram_debug_log('send_message_no_token', array());



		return false;
	}



	$url      = 'https://api.telegram.org/bot' . $bot_token . '/sendMessage';

	$response = wp_remote_post(

		$url,

		array(

			'timeout' => 15,

			'body'    => array(

				'chat_id'                  => $chat_id,

				'text'                     => $text,

				'parse_mode'               => $parse_mode,

				'disable_web_page_preview' => true,

			),

		)

	);



	if (is_wp_error($response)) {

		apollo_telegram_debug_log('send_message_error', array('error' => $response->get_error_message()));



		return false;
	}



	$body = json_decode(wp_remote_retrieve_body($response), true);



	return ! empty($body['ok']);
}
