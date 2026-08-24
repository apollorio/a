<?php
/**
 * Ativação — apollo-djs
 *
 * @package Apollo\DJs
 */

declare(strict_types=1);

namespace Apollo\DJs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {

	public static function activate(): void {
		self::set_defaults();
		self::create_pages();
	}

	private static function set_defaults(): void {
		$defaults = array(
			'default_style' => 'apollo-v1',
			'per_page'      => 12,
			'default_view'  => 'grid',
		);

		if ( ! get_option( 'apollo_dj_settings' ) ) {
			update_option( 'apollo_dj_settings', $defaults );
		}
	}

	/**
	 * Páginas do plugin.
	 *
	 * IMPORTANTE: /portal pertence ao apollo-events (portal público de eventos —
	 * /portal e /portal/eventos). O antigo "Portal DJ" (WP Page slug 'portal')
	 * NÃO deve mais ser criado: colidia com a rota virtual e causava 404 em
	 * /portal/eventos. A descoberta de DJs vive no archive do CPT (/djs).
	 */
	private static function create_pages(): void {
		// No pages to create — /djs is handled by the CPT archive and
		// /portal is an apollo-events virtual route (GLOBAL BRIDGE rule).
	}
}
