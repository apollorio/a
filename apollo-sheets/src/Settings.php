<?php

/**
 * Settings — global plugin options for Apollo Sheets
 *
 * Manages: default DataTables behavior, custom CSS, admin menu position,
 * column editor width, per-user preferences.
 *
 * Options stored in WP option: `apollo_sheets_settings`
 * User options stored in WP user option: `apollo_sheets_user_options`
 *
 * Adapted from TablePress Options Model architecture.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {


	/**
	 * WP option key for global settings
	 */
	public const OPTION_KEY = 'apollo_sheets_settings';

	/**
	 * WP user option key for per-user settings
	 */
	public const USER_OPTION_KEY = 'apollo_sheets_user_options';

	/**
	 * Default global settings
	 */
	public const DEFAULTS = array(
		'use_custom_css'              => false,
		'custom_css'                  => '',
		'use_default_css'             => true,
		'admin_menu_position'         => 'middle',   // top | middle | bottom
		'default_datatables'          => true,
		'default_datatables_sort'     => true,
		'default_datatables_filter'   => true,
		'default_datatables_paginate' => true,
		'default_paginate_entries'    => 25,
		'default_responsive'          => true,
		'default_table_head'          => 1,
		'default_alternating_colors'  => true,
		'evaluate_formulas'           => true,
		'wp_search_integration'       => true,
		'cache_table_output'          => false,
	);

	/**
	 * Default per-user settings
	 */
	public const USER_DEFAULTS = array(
		'editor_column_width' => 150,  // px
		'editor_line_clamp'   => 5,    // max visible lines per cell
	);

	/**
	 * In-memory cache
	 */
	private static ?array $cache      = null;
	private static ?array $user_cache = null;

	// ─── Get / Set ──────────────────────────────────────────────────

	/**
	 * Get all global settings (merged with defaults).
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$saved       = get_option( self::OPTION_KEY, array() );
		$saved       = is_array( $saved ) ? $saved : array();
		self::$cache = array_merge( self::DEFAULTS, $saved );

		return self::$cache;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not set (uses DEFAULTS).
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$all = self::get_all();
		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}
		return null !== $default ? $default : ( self::DEFAULTS[ $key ] ?? null );
	}

	/**
	 * Update settings (partial update — only provided keys are changed).
	 *
	 * @param array $new_settings Key-value pairs to update.
	 * @return bool True on success.
	 */
	public static function update( array $new_settings ): bool {
		$all = self::get_all();

		foreach ( $new_settings as $key => $value ) {
			if ( array_key_exists( $key, self::DEFAULTS ) ) {
				$all[ $key ] = $value;
			}
		}

		self::$cache = $all;

		/**
		 * Fires before saving Apollo Sheets settings.
		 *
		 * @param array $all Full settings array being saved.
		 */
		do_action( 'apollo/sheets/before_save_settings', $all );

		$saved = update_option( self::OPTION_KEY, $all, false );

		/**
		 * Fires after saving Apollo Sheets settings.
		 *
		 * @param array $all Full settings array saved.
		 * @param bool  $saved Whether save was successful.
		 */
		do_action( 'apollo/sheets/saved_settings', $all, $saved );

		return $saved;
	}

	// ─── Per-User Settings ──────────────────────────────────────────

	/**
	 * Get all per-user settings for the current user.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_user_all(): array {
		if ( null !== self::$user_cache ) {
			return self::$user_cache;
		}

		$saved            = get_user_option( self::USER_OPTION_KEY );
		$saved            = is_array( $saved ) ? $saved : array();
		self::$user_cache = array_merge( self::USER_DEFAULTS, $saved );

		return self::$user_cache;
	}

	/**
	 * Get a single user setting.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get_user( string $key ) {
		$all = self::get_user_all();
		return $all[ $key ] ?? ( self::USER_DEFAULTS[ $key ] ?? null );
	}

	/**
	 * Update per-user settings.
	 *
	 * @param array $new_settings
	 * @return bool
	 */
	public static function update_user( array $new_settings ): bool {
		$all = self::get_user_all();
		foreach ( $new_settings as $key => $value ) {
			if ( array_key_exists( $key, self::USER_DEFAULTS ) ) {
				$all[ $key ] = $value;
			}
		}
		self::$user_cache = $all;
		return (bool) update_user_option( get_current_user_id(), self::USER_OPTION_KEY, $all );
	}

	// ─── Custom CSS ─────────────────────────────────────────────────

	/**
	 * Sanitize and save custom CSS.
	 *
	 * Sanitizes using wp_strip_all_tags, then saves to setting.
	 *
	 * @param string $css Raw CSS input.
	 * @return bool
	 */
	public static function save_custom_css( string $css ): bool {
		// Allow only CSS-safe content
		$css = wp_strip_all_tags( $css );

		// For users without unfiltered_html, further restrict
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			// Remove CSS expressions and url() with non-safe schemes
			$css = preg_replace( '/expression\s*\(/i', '', $css );
			$css = preg_replace( '/url\s*\(\s*["\']?\s*(?!https?:\/\/|\/)[^)]+\)/i', '', $css );
		}

		return self::update(
			array(
				'custom_css'     => $css,
				'use_custom_css' => '' !== $css,
			)
		);
	}

	/**
	 * Get custom CSS for frontend output.
	 *
	 * @return string Sanitized CSS, or empty string.
	 */
	public static function get_custom_css(): string {
		if ( ! self::get( 'use_custom_css' ) ) {
			return '';
		}
		return (string) self::get( 'custom_css', '' );
	}

	// ─── Reset ──────────────────────────────────────────────────────

	/**
	 * Reset all settings to defaults.
	 *
	 * @return bool
	 */
	public static function reset(): bool {
		self::$cache = null;
		return (bool) update_option( self::OPTION_KEY, self::DEFAULTS, false );
	}
}
