<?php

namespace Apollo\Telegram;

class Asset
{
	public function __construct()
	{
		add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
	}

	/**
	 * Enqueues admin styles and scripts.
	 *
	 * @param	string	$hookSuffix		Current admin page.
	 *
	 * @return	void
	 *
	 * @hooked	action: `admin_enqueue_scripts` - 10
	 */
	public function enqueueAdminScripts($hookSuffix)
	{
		// if (strpos($hookSuffix, APOLLO_TELEGRAM_MENUS_SLUG) !== false)
		wp_enqueue_style('APOLLO_TELEGRAM_admin', APOLLO_TELEGRAM()->url('assets/admin/css/admin.css'), [], APOLLO_TELEGRAM_VERSION);

		wp_enqueue_script('APOLLO_TELEGRAM_admin', APOLLO_TELEGRAM()->url('assets/admin/js/admin.js'), [], APOLLO_TELEGRAM_VERSION, true);
	}
}

