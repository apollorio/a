<?php

namespace Apollo\Telegram\Menus;

class CustomMenu extends Base
{
	protected $menuSlug = APOLLO_TELEGRAM_MENUS_SLUG . '_custom';

	/**
	 * Adds the submenu.
	 *
	 * @param	array	$submenus
	 *
	 * @return	array
	 *
	 * @hooked	filter: `APOLLO_TELEGRAM_menus_submenus` - 10
	 */
	public function addSubmenu($submenus)
	{
		$submenus['custom'] = [
			'page_title' => esc_html__('Custom Menu', 'apollo-telegram'),
			'menu_title' => esc_html__('Custom Menu', 'apollo-telegram'),
			'callback'   => [$this, 'displayContent'],
			'position'   => 3,
		];

		return $submenus;
	}

	/**
	 * Outputs the content for this submenu.
	 *
	 * @return	void
	 */
	public function displayContent()
	{
		APOLLO_TELEGRAM()->view('admin.menus.custom-menu', ['test' => 'Test']);
	}
}

