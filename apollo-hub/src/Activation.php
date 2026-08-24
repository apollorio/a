<?php

/**
 * Activation — Apollo Hub
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {


	/**
	 * Executado na ativação do plugin.
	 */
	public static function activate(): void {
		// Registra CPT para gerar rewrite rules
		self::register_hub_cpt_temp();

		// Rewrite rules
		flush_rewrite_rules();

		// Marca versão de ativação
		update_option( 'apollo_hub_version', APOLLO_HUB_VERSION );

		// Dispara ação para outros plugins
		do_action( 'apollo/hub/activated' );
	}

	/**
	 * Registra CPT temporariamente durante ativação (antes do Plugin estar init).
	 */
	private static function register_hub_cpt_temp(): void {
		if ( post_type_exists( APOLLO_HUB_CPT ) ) {
			return;
		}

		register_post_type(
			APOLLO_HUB_CPT,
			array(
				'public'       => true,
				'rewrite'      => array(
					'slug'       => APOLLO_HUB_SLUG,
					'with_front' => false,
				),
				'supports'     => array( 'title', 'author' ),
				'show_in_rest' => true,
				'rest_base'    => 'hubs',
			)
		);
	}
}
