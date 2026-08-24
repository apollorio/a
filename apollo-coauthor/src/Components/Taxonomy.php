<?php
/**
 * Apollo CoAuthor — Taxonomy Component.
 *
 * Registers the `coauthor` taxonomy as a fallback. If apollo-core already
 * registered it, this component attaches it to supported post types and
 * registers the meta key.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor\Components;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy registration / attachment.
 *
 * @since 1.0.0
 */
class Taxonomy {

	/**
	 * Constructor — hooks into `init`.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 5 );
		add_filter( 'apollo_core_register_meta', array( $this, 'register_with_core' ), 10 );
	}

	/**
	 * Register or attach the `coauthor` taxonomy.
	 *
	 * @since 1.0.0
	 */
	public function register_taxonomy(): void {
		$post_types = apollo_coauthor_get_supported_post_types();

		// If taxonomy already registered by apollo-core, just attach it to CPTs.
		if ( taxonomy_exists( APOLLO_COAUTHOR_TAX ) ) {
			foreach ( $post_types as $post_type ) {
				if ( ! is_object_in_taxonomy( $post_type, APOLLO_COAUTHOR_TAX ) ) {
					register_taxonomy_for_object_type( APOLLO_COAUTHOR_TAX, $post_type );
				}
			}
			return;
		}

		// Fallback: register taxonomy ourselves.
		$labels = array(
			'name'                       => _x( 'Co-Autores', 'taxonomy general name', 'apollo-coauthor' ),
			'singular_name'              => _x( 'Co-Autor', 'taxonomy singular name', 'apollo-coauthor' ),
			'search_items'               => __( 'Buscar Co-Autores', 'apollo-coauthor' ),
			'all_items'                  => __( 'Todos os Co-Autores', 'apollo-coauthor' ),
			'edit_item'                  => __( 'Editar Co-Autor', 'apollo-coauthor' ),
			'update_item'                => __( 'Atualizar Co-Autor', 'apollo-coauthor' ),
			'add_new_item'               => __( 'Adicionar Co-Autor', 'apollo-coauthor' ),
			'new_item_name'              => __( 'Novo Co-Autor', 'apollo-coauthor' ),
			'separate_items_with_commas' => __( 'Separe co-autores com vírgulas', 'apollo-coauthor' ),
			'add_or_remove_items'        => __( 'Adicionar ou remover co-autores', 'apollo-coauthor' ),
			'choose_from_most_used'      => __( 'Co-autores mais usados', 'apollo-coauthor' ),
			'not_found'                  => __( 'Nenhum co-autor encontrado', 'apollo-coauthor' ),
			'menu_name'                  => __( 'Co-Autores', 'apollo-coauthor' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => false,  // We use our own metabox.
			'show_admin_column' => false,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_in_rest'      => false,  // We have our own REST endpoints.
			'rewrite'           => false,
			'query_var'         => false,
			'capabilities'      => array(
				'manage_terms' => 'edit_posts',
				'edit_terms'   => 'edit_posts',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'edit_posts',
			),
		);

		register_taxonomy( APOLLO_COAUTHOR_TAX, $post_types, $args );
	}

	/**
	 * Register meta with apollo-core meta registry.
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta_fields Existing meta fields.
	 * @return array
	 */
	public function register_with_core( array $meta_fields ): array {
		$post_types = apollo_coauthor_get_supported_post_types();

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $meta_fields[ $post_type ] ) || ! is_array( $meta_fields[ $post_type ] ) ) {
				$meta_fields[ $post_type ] = array();
			}

			$meta_fields[ $post_type ][ APOLLO_COAUTHOR_META_KEY ] = array(
				'type'          => 'array',
				'description'   => __( 'Array de IDs de co-autores.', 'apollo-coauthor' ),
				'single'        => true,
				'show_in_rest'  => false,
				'default'       => array(),
				'sanitize'      => function ( $value ) {
					return array_map( 'absint', (array) $value );
				},
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			);
		}

		return $meta_fields;
	}
}
