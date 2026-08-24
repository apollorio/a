<?php
/**
 * CPTRegistrar — registra o post type "local"
 *
 * Fallback ativo apenas quando apollo-core não registrou o CPT.
 *
 * @package Apollo\Local\CPT
 */

declare(strict_types=1);

namespace Apollo\Local\CPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CPTRegistrar {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 5 );
	}

	public function register(): void {
		if ( post_type_exists( APOLLO_LOCAL_CPT ) ) {
			return; // apollo-core já registrou
		}

		register_post_type(
			APOLLO_LOCAL_CPT,
			array(
				'labels'              => array(
					'name'               => __( 'Locais', 'apollo-local' ),
					'singular_name'      => __( 'Local', 'apollo-local' ),
					'add_new'            => __( 'Novo Local', 'apollo-local' ),
					'add_new_item'       => __( 'Adicionar Novo Local', 'apollo-local' ),
					'edit_item'          => __( 'Editar Local', 'apollo-local' ),
					'new_item'           => __( 'Novo Local', 'apollo-local' ),
					'view_item'          => __( 'Ver Local', 'apollo-local' ),
					'search_items'       => __( 'Buscar Locais', 'apollo-local' ),
					'not_found'          => __( 'Nenhum local encontrado', 'apollo-local' ),
					'not_found_in_trash' => __( 'Nenhum local na lixeira', 'apollo-local' ),
				),
				'public'              => true,
				'has_archive'         => 'locais',
				'rewrite'             => array(
					'slug'       => 'local',
					'with_front' => false,
				),
				'rest_base'           => 'local',
				'show_in_rest'        => true,
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'menu_icon'           => 'dashicons-location',
				'menu_position'       => 8,
				'taxonomies'          => array( APOLLO_LOCAL_TAX_TYPE, APOLLO_LOCAL_TAX_AREA ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'show_in_admin_bar'   => true,
				'exclude_from_search' => false,
			)
		);
	}
}
