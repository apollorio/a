<?php

namespace Apollo\Docs\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT + Taxonomy registrar for apollo-docs.
 *
 * Registers `doc` CPT and `doc_folder` / `doc_type` taxonomies.
 * Respects apollo-core fallback bridge pattern — only registers
 * if core hasn't already provided them.
 */
final class Registrar {

	public function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ), 5 );
		add_action( 'init', array( $this, 'register_taxonomies' ), 5 );
	}

	/**
	 * Register `doc` CPT.
	 */
	public function register_cpt(): void {
		if ( post_type_exists( 'doc' ) ) {
			return; /* Core fallback already registered it */
		}

		register_post_type(
			'doc',
			array(
				'labels'          => array(
					'name'               => 'Documentos',
					'singular_name'      => 'Documento',
					'add_new'            => 'Novo Documento',
					'add_new_item'       => 'Novo Documento',
					'edit_item'          => 'Editar Documento',
					'view_item'          => 'Ver Documento',
					'all_items'          => 'Todos os Documentos',
					'search_items'       => 'Buscar Documentos',
					'not_found'          => 'Nenhum documento encontrado',
					'not_found_in_trash' => 'Nenhum documento na lixeira',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => true,
				'rest_base'       => 'docs',
				'rest_namespace'  => 'apollo/v1',
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'supports'        => array( 'title', 'editor', 'author', 'revisions' ),
				'has_archive'     => false,
				'rewrite'         => array(
					'slug'       => 'documento',
					'with_front' => false,
				),
				'menu_icon'       => 'dashicons-media-document',
			)
		);
	}

	/**
	 * Register taxonomies: doc_folder (hierarchical) + doc_type.
	 */
	public function register_taxonomies(): void {
		if ( ! taxonomy_exists( 'doc_folder' ) ) {
			register_taxonomy(
				'doc_folder',
				array( 'doc' ),
				array(
					'labels'       => array(
						'name'          => 'Pastas',
						'singular_name' => 'Pasta',
						'add_new_item'  => 'Nova Pasta',
						'edit_item'     => 'Editar Pasta',
						'search_items'  => 'Buscar Pastas',
					),
					'hierarchical' => true,
					'public'       => false,
					'show_ui'      => true,
					'show_in_rest' => true,
					'rewrite'      => array( 'slug' => 'pasta' ),
				)
			);
		}

		if ( ! taxonomy_exists( 'doc_type' ) ) {
			register_taxonomy(
				'doc_type',
				array( 'doc' ),
				array(
					'labels'       => array(
						'name'          => 'Tipos de Documento',
						'singular_name' => 'Tipo',
						'add_new_item'  => 'Novo Tipo',
						'edit_item'     => 'Editar Tipo',
						'search_items'  => 'Buscar Tipos',
					),
					'hierarchical' => false,
					'public'       => false,
					'show_ui'      => true,
					'show_in_rest' => true,
					'rewrite'      => array( 'slug' => 'tipo-documento' ),
				)
			);
		}
	}
}
