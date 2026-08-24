<?php
/**
 * TaxonomyRegistrar — registra local_type e local_area
 *
 * Fallback ativo apenas quando apollo-core não registrou as taxonomias.
 *
 * @package Apollo\Local\CPT
 */

declare(strict_types=1);

namespace Apollo\Local\CPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TaxonomyRegistrar {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 6 );
	}

	public function register(): void {
		$this->register_type();
		$this->register_area();
	}

	private function register_type(): void {
		if ( taxonomy_exists( APOLLO_LOCAL_TAX_TYPE ) ) {
			return;
		}

		register_taxonomy(
			APOLLO_LOCAL_TAX_TYPE,
			APOLLO_LOCAL_CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Tipos de Local', 'apollo-local' ),
					'singular_name' => __( 'Tipo de Local', 'apollo-local' ),
					'add_new_item'  => __( 'Adicionar Tipo', 'apollo-local' ),
					'edit_item'     => __( 'Editar Tipo', 'apollo-local' ),
				),
				'hierarchical' => true,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'tipo-local' ),
			)
		);
	}

	private function register_area(): void {
		if ( taxonomy_exists( APOLLO_LOCAL_TAX_AREA ) ) {
			return;
		}

		register_taxonomy(
			APOLLO_LOCAL_TAX_AREA,
			APOLLO_LOCAL_CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Zonas / Áreas', 'apollo-local' ),
					'singular_name' => __( 'Zona', 'apollo-local' ),
					'add_new_item'  => __( 'Adicionar Zona', 'apollo-local' ),
					'edit_item'     => __( 'Editar Zona', 'apollo-local' ),
				),
				'hierarchical' => true,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'zona' ),
			)
		);
	}
}
