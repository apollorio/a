<?php

namespace Apollo\Telegram;

use Apollo\Telegram\Shortcodes\ShortcodeManager;

class Core
{
	/**
	 * Dependency injection container.
	 *
	 * @var	Container
	 */
	protected Container $container;

	/**
	 * Plugin options.
	 *
	 * @var	array<string, mixed>
	 */
	private array $options = [];

	/**
	 * Constructor.
	 *
	 * @param	Container	$container	Dependency injection container.
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->container->singleton(Core::class, function () { return $this; });

		$this->registerDependencies();
		$this->setupHooks();
	}

	/**
	 * Registers all the dependencies needed for the plugin.
	 *
	 * @return	void
	 */
	private function registerDependencies(): void
	{
		$this->container->singleton(Model::class);
		$this->container->singleton(Asset::class);
		$this->container->singleton(API::class);
		$this->container->singleton(Menu::class);
		$this->container->singleton(ShortcodeManager::class);
		$this->container->singleton(Service::class);
		$this->container->singleton(View::class);
	}

	/**
	 * Sets WordPress hooks and initializes components up.
	 *
	 * @return	void
	 */
		private function setupHooks(): void
	{
		// Initialize dependencies through container
		$this->container->make(Model::class);
		$this->container->make(Asset::class);
		$this->container->make(API::class);

		if (\is_admin())
		{
			$this->container->make(Menu::class);
		} else {
			$this->container->make(ShortcodeManager::class);
		}

		$this->container->make(Service::class);

		\add_action('init', [$this, 'i18n'], 1);

		if (\is_admin() && function_exists('apollo_telegram_uses_local_polling') && apollo_telegram_uses_local_polling()) {
			\add_action('admin_notices', [$this, 'apollo_telegram_local_dev_notice']);
		}
	}

	/**
	 * Loads plugin's textdomain.
	 *
	 * @return	void
	 *
	 * @hooked	action: `init` - 1
	 */
	public function i18n()
	{
		load_plugin_textdomain('apollo-telegram', false, basename(APOLLO_TELEGRAM_DIR) . DIRECTORY_SEPARATOR . 'languages');
	}

	/**
	 * LocalWP DEV hint (WP_DEBUG only): use REST polling instead of Telegram webhook.
	 *
	 * @return void
	 */
	public function apollo_telegram_local_dev_notice(): void
	{
		if (! defined('WP_DEBUG') || ! WP_DEBUG) {
			return;
		}

		$url = add_query_arg(
			'_wpnonce',
			wp_create_nonce('wp_rest'),
			\rest_url('APOLLO_TELEGRAM/v1/get-message-polling')
		);
		echo '<div class="notice notice-info"><p>';
		echo \esc_html__('LocalWP DEV: Telegram webhook is disabled. While logged in as admin, open the polling endpoint or run dev-poll.php:', 'apollo-telegram');
		echo ' <code>' . \esc_html($url) . '</code>';
		echo '</p></div>';
	}
/**
	 * Returns plugin's URL path, without any slashes in the end (e.g. `https://Site.com/wp-content/plugins/my-plugin`).
	 *
	 * @param	string	$path	Path to append to the end of the URL, without any slashes in the beginning (e.g. `path/to/my-file.php`).
	 *
	 * @return	string
	 */
	public function url(string $path = ''): string
	{
		return untrailingslashit(APOLLO_TELEGRAM_URL . $path);
	}

	/**
	 * Returns plugin's dir path, without any slashes in the end (e.g. `/var/www/html/wp-content/plugins/my-plugin`).
	 *
	 * @param	string	$path	Path to append to the end of the dir, without any slashes in the beginning (e.g. `path/to/my-file.php`).
	 *
	 * @return	string
	 */
	public function dir(string $path = ''): string
	{
		return untrailingslashit(APOLLO_TELEGRAM_DIR . $path);
	}

	/**
	 * Returns `Model` class.
	 *
	 * @return	Model
	 */
	public function model(): Model
	{
		return $this->container->make(Model::class);
	}

	/**
	 * Returns a view from `views/` folder.
	 *
	 * @param	string		$filePath		File path without `.php` extension, where path parts are separated with dots (e.g. `path.to.file`).
	 * @param	array		$passedArray	Input data to pass to the file. The array will be extracted into multiple variables (e.g. `['var1' => 'Foo', 'var2' => 'Bar']`).
	 * @param	bool		$echo			Echo/print the view or just return the view.
	 *
	 * @return	mixed
	 */
	public function view(string $filePath, array $passedArray = [], bool $echo = true): mixed
	{
		$view = $this->container->make(View::class);

		if (!$echo) return $view->display($filePath, $passedArray);

		echo $view->display($filePath, $passedArray);
		return null;
	}

	/**
	 * Returns a plugin option.
	 *
	 * @param	string	$optionName	Option name.
	 * @param	mixed	$default	Default value if option not found.
	 *
	 * @return	mixed
	 */
	public function option(string $optionName, mixed $default = null): mixed
	{
		if (empty($this->options)) {
			$this->options = get_option(APOLLO_TELEGRAM_OPTIONS_KEY, []);
		}

		return $this->options[$optionName] ?? $default;
	}
}

