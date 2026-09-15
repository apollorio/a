<?php
/**
 * CoreIntegration — integração com apollo-core
 *
 * Conecta apollo-loc ao ciclo de vida do Core quando ativo.
 *
 * @package Apollo\Local\Integration
 */

declare(strict_types=1);

namespace Apollo\Local\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CoreIntegration {

	public function __construct() {
		if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
			return;
		}

		/*
		 * WHY did_action() and not a plain add_action().
		 *
		 * apollo-core fires apollo/core/initialized from apollo_core_bootstrap()
		 * at plugins_loaded:1 (apollo-core/apollo-core.php:364). This class is
		 * constructed from Plugin.php:58, reached from apollo_local_init(), which
		 * is bound at plugins_loaded:15 (apollo-loc.php:109) -- fourteen priority
		 * levels AFTER the hook has already fired. A plain add_action() here
		 * subscribes to an event that is already over, so on_core_ready() never
		 * ran. It went unnoticed because the handler only re-broadcasts on
		 * apollo/loc/core_ready, which has zero listeners today.
		 *
		 * Do NOT 'simplify' this back to one add_action(), and do NOT fix it by
		 * moving apollo-loc's boot earlier -- :15 is the ecosystem convention and
		 * moving it would reorder this plugin against 21 others.
		 */
		if ( did_action( 'apollo/core/initialized' ) ) {
			$this->on_core_ready();
		} else {
			add_action( 'apollo/core/initialized', array( $this, 'on_core_ready' ) );
		}

		/*
		 * The filter needs no such guard: MetaRegistry applies
		 * apollo_core_register_meta at src/Core/MetaRegistry.php:2285, during
		 * meta registration at init -- well after this constructor runs.
		 */
		add_filter( 'apollo_core_register_meta', array( $this, 'expose_meta_to_core' ) );
	}

	/**
	 * Callback quando apollo-core está pronto.
	 *
	 * @param array $info Dados do core.
	 */
	public function on_core_ready( array $info = array() ): void {
		do_action( 'apollo/loc/core_ready', $info );
	}

	/**
	 * Expõe meta keys do Local para o apollo-core meta registry.
	 *
	 * Uses post-type-keyed format expected by MetaRegistry:
	 *   $meta[ $cpt ][ $key ] = array( 'type' => ..., 'sanitize' => ... );
	 *
	 * @param array $meta Meta keys existentes no core.
	 * @return array Meta keys com os do Local adicionados.
	 */
	public function expose_meta_to_core( array $meta ): array {
		if ( ! isset( $meta[ APOLLO_LOCAL_CPT ] ) ) {
			$meta[ APOLLO_LOCAL_CPT ] = array();
		}

		$definitions = \Apollo\Local\CPT\MetaRegistrar::get_definitions();

		foreach ( $definitions as $key => $def ) {
			$sanitizers = array(
				'string'  => 'sanitize_text_field',
				'number'  => 'floatval',
				'integer' => 'absint',
			);
			$sanitize = $sanitizers[ $def['type'] ] ?? 'sanitize_text_field';

			if ( in_array( $key, array( '_local_website', '_local_instagram', '_local_facebook', '_local_whatsapp' ), true ) ) {
				$sanitize = 'esc_url_raw';
			}

			$meta[ APOLLO_LOCAL_CPT ][ $key ] = array(
				'type'     => $def['type'],
				'sanitize' => $sanitize,
			);
		}

		return $meta;
	}
}
