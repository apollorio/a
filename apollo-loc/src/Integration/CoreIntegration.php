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
		if ( defined( 'APOLLO_CORE_VERSION' ) ) {
			add_action( 'apollo/core/initialized', array( $this, 'on_core_ready' ) );
			add_filter( 'apollo_core_register_meta', array( $this, 'expose_meta_to_core' ) );
		}
	}

	/**
	 * Callback quando apollo-core está pronto.
	 *
	 * @param array $info Dados do core.
	 */
	public function on_core_ready( array $info ): void {
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
