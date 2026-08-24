<?php

/**
 * Activation — runs on plugin activation
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {


	/**
	 * Run activation routines
	 */
	public static function activate(): void {
		self::create_default_options();
		self::add_capabilities();

		// Store activation version
		update_option( 'apollo_sheets_version', APOLLO_SHEETS_VERSION );
		update_option( 'apollo_sheets_db_version', APOLLO_SHEETS_DB_VERSION );
	}

	/**
	 * Create default options if they don't exist
	 */
	private static function create_default_options(): void {
		// Initialize ID mapping if not present
		if ( false === get_option( 'apollo_sheets_tables' ) ) {
			add_option(
				'apollo_sheets_tables',
				array(
					'last_id'    => 0,
					'table_post' => array(),
				),
				'',
				false
			);
		}

		// Default plugin settings
		if ( false === get_option( 'apollo_sheets_settings' ) ) {
			add_option(
				'apollo_sheets_settings',
				array(
					'default_datatables' => true,
					'default_responsive' => true,
					'custom_css'         => '',
				),
				'',
				false
			);
		}
	}

	/**
	 * Add custom capabilities to admin role
	 */
	private static function add_capabilities(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}

		$caps = array(
			'apollo_sheets_list',
			'apollo_sheets_add',
			'apollo_sheets_edit',
			'apollo_sheets_copy',
			'apollo_sheets_delete',
			'apollo_sheets_import',
			'apollo_sheets_export',
			'apollo_sheets_options',
			'apollo_sheets_bulk_edit',
		);

		foreach ( $caps as $cap ) {
			$admin->add_cap( $cap );
		}

		// Editor role — limited caps (NO bulk edit)
		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor_caps = array(
				'apollo_sheets_list',
				'apollo_sheets_add',
				'apollo_sheets_edit',
				'apollo_sheets_copy',
				'apollo_sheets_import',
				'apollo_sheets_export',
			);
			foreach ( $editor_caps as $cap ) {
				$editor->add_cap( $cap );
			}
		}

		// Moderator role — bulk edit access (if role exists in Apollo)
		$moderator = get_role( 'moderator' );
		if ( $moderator ) {
			$moderator->add_cap( 'apollo_sheets_bulk_edit' );
		}
	}
}
