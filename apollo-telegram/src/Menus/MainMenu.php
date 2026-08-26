<?php

namespace Apollo\Telegram\Menus;

class MainMenu extends Base
{
	protected $menuSlug = APOLLO_TELEGRAM_MENUS_SLUG . '_settings';

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
		$submenus['settings'] = [
			'page_title' => esc_html__('Telegram Bot Boilerplate Plugin', 'apollo-telegram'),
			'menu_title' => esc_html__('Telegram Bot', 'apollo-telegram'),
			'callback'   => [$this, 'displayContent'],
			'position'   => 1,
		];

		return $submenus;
	}

	/**
	 * Returns tabs for this submenu.
	 *
	 * @return	array
	 */
	public function getTabs()
	{
		return apply_filters('APOLLO_TELEGRAM_menus_main_tabs', [
			'general' => esc_html__('General Settings', 'apollo-telegram'),
			'proxy'   => esc_html__('Proxy Settings', 'apollo-telegram'),
		]);
	}

	/**
	 * Returns fields for this submenu.
	 *
	 * @return	array
	 */
	public function getFields()
	{
		return apply_filters('APOLLO_TELEGRAM_menus_main_fields', [
			// General section
			'bot_token'    => [
				'id'      => 'bot_token',
				'label'   => esc_html__('Bot token', 'apollo-telegram'),
				'section' => 'general',
				'type'    => 'text',
				'default' => '',
				'args'    => [],
			],
			'bot_username' => [
				'id'      => 'bot_username',
				'label'   => esc_html__('Bot username', 'apollo-telegram'),
				'section' => 'general',
				'type'    => 'text',
				'default' => '',
				'args'    => [
					'description' => esc_html__('With @', 'apollo-telegram'),
				],
			],
			'admin_ids'    => [
				'id'      => 'admin_ids',
				'label'   => esc_html__('Admins IDs', 'apollo-telegram'),
				'section' => 'general',
				'type'    => 'text',
				'default' => '',
				'args'    => [
					'description' => esc_html__('Enter Telegram ID (numeric) of admins, separate IDs with a comma (,).', 'apollo-telegram'),
				],
			],

			// Proxy section
			'proxy_update_receiver' => [
				'id'      => 'proxy_update_receiver',
				'label'   => esc_html__('Update receiver URL', 'apollo-telegram'),
				'section' => 'proxy',
				'type'    => 'text',
				'default' => '',
				'args'    => [
					'description' => esc_html__('Find forward-to-telegram.php that exists in the project root, upload it on a middleman server and enter its full URL here.', 'apollo-telegram'),
				],
			],
		]);
	}
}

