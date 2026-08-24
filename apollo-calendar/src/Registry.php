<?php
/**
 * Apollo Calendar — User Meta Registration
 *
 * Declares per-user calendar preferences via the apollo-core
 * apollo_core_register_user_meta filter (no core file edits required).
 *
 * @package Apollo\Calendar
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Registry {

	public function init(): void {
		add_filter( 'apollo_core_register_user_meta', array( $this, 'register_user_meta' ) );

		// Register rewrite query var so CalendarPage can detect the /agenda route.
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Inject calendar-specific user meta into the core MetaRegistry.
	 *
	 * @param array $user_meta Existing meta definitions.
	 * @return array
	 */
	public function register_user_meta( array $user_meta ): array {
		$user_meta['_apollo_calendar_timezone'] = array(
			'type'         => 'string',
			'description'  => 'User IANA timezone for personal calendar',
			'single'       => true,
			'show_in_rest' => true,
			'default'      => 'America/Sao_Paulo',
			'sanitize'     => 'sanitize_text_field',
		);

		$user_meta['_apollo_calendar_prefs'] = array(
			'type'         => 'array',
			'description'  => 'Calendar display preferences (default view, source toggles, holiday region)',
			'single'       => true,
			'show_in_rest' => false,
			'default'      => array(),
		);

		return $user_meta;
	}

	/**
	 * Expose the apollo_calendar_page query var to WordPress.
	 *
	 * @param string[] $vars
	 * @return string[]
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'apollo_calendar_page';
		return $vars;
	}
}
