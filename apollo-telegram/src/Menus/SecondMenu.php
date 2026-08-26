<?php

namespace Apollo\Telegram\Menus;

class SecondMenu extends Base
{
	protected $menuSlug = APOLLO_TELEGRAM_MENUS_SLUG . '_second';

	public function __construct()
	{
		parent::__construct();

		// Uncomment if you want to change select options programmatically
		// add_filter('APOLLO_TELEGRAM_menus_second_fields', [$this, 'populateSelectValues']);
	}

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
		$submenus['second'] = [
			'page_title' => esc_html__('More Boilerplate Settings', 'apollo-telegram'),
			'menu_title' => esc_html__('Second Menu', 'apollo-telegram'),
			'callback'   => [$this, 'displayContent'],
			'position'   => 2,
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
		return [
			'general' => esc_html__('General', 'apollo-telegram'),
			'second'  => esc_html__('Second', 'apollo-telegram'),
		];
	}

	/**
	 * Returns fields for this submenu.
	 *
	 * @return	array
	 */
	public function getFields()
	{
		return apply_filters('APOLLO_TELEGRAM_menus_second_fields', [
			'example_field_second' => [
				'id'      => 'example_field_second',
				'label'   => esc_html__('Example Field', 'apollo-telegram'),
				'section' => 'general',
				'type'    => 'text',
				'default' => '',
				'args'    => [],
			],
			'test_field_second'    => [
				'id'      => 'test_field_second',
				'label'   => esc_html__('Second Tab Field', 'apollo-telegram'),
				'section' => 'second',
				'type'    => 'text',
				'default' => '',
				'args'    => [],
			],
			'test_checkbox_second' => [
				'id'      => 'test_checkbox_second',
				'label'   => esc_html__('Checkbox Field', 'apollo-telegram'),
				'section' => 'second',
				'type'    => 'checkbox',
				'default' => true,
				'args'    => [],
			],
			'test_textarea_field' => [
				'id'      => 'test_textarea_field',
				'label'   => esc_html__('Textarea Field', 'apollo-telegram'),
				'section' => 'second',
				'type'    => 'textarea',
				'default' => '',
				'args'    => [
					'placeholder' => esc_html__('Placeholder', 'apollo-telegram'),
				],
			],
			'test_select_field' => [
				'id'      => 'test_select_field',
				'label'   => esc_html__('Select Field', 'apollo-telegram'),
				'section' => 'second',
				'type'    => 'select',
				'default' => '',
				'args'    => [
					'options'  => [
						// Either keep the options empty here and populate them using the `APOLLO_TELEGRAM_menus_second_fields` filter like below
						// '' => '',

						// Or add options manually yourself
						'key1' => esc_html__('Value 01', 'apollo-telegram'),
						'key2' => esc_html__('Value 02', 'apollo-telegram'),
					],
					// 'multiple' => true,
				],
			],
		]);
	}

	/**
	 * Adds some example values to the select field.
	 *
	 * @param	array	$fields
	 *
	 * @return	array
	 *
	 * @hooked	filter: `APOLLO_TELEGRAM_menus_second_fields` - 10
	 */
	public function populateSelectValues($fields)
	{
		if (empty($fields['test_select_field'])) return $fields;

		$fields['test_select_field']['args']['options'] = [];
		foreach ([1, 2, 3, 4, 5] as $number)
			$fields['test_select_field']['args']['options']["key$number"] = "Value 0$number";

		return $fields;
	}
}

