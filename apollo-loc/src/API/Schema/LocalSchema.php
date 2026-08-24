<?php
/**
 * LocalSchema — serialização de um post "local" para REST API
 *
 * Método prepare_local() reutilizado por todos os endpoints.
 *
 * @package Apollo\Local\API\Schema
 */

declare(strict_types=1);

namespace Apollo\Local\API\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LocalSchema {

	/**
	 * Serializa um WP_Post do tipo "local" para array REST-ready.
	 *
	 * @param \WP_Post $post Post do tipo local.
	 * @return array<string, mixed>
	 */
	public static function prepare( \WP_Post $post ): array {
		$meta_keys = array(
			'_local_name', '_local_address', '_local_city', '_local_state',
			'_local_country', '_local_postal', '_local_lat', '_local_lng',
			'_local_phone', '_local_website', '_local_instagram', '_local_facebook',
			'_local_whatsapp', '_local_capacity', '_local_price_range', '_local_description',
		);

		$meta = array();
		foreach ( $meta_keys as $key ) {
			$clean_key        = str_replace( array( '_local_', '_' ), array( '', '_' ), ltrim( $key, '_' ) );
			$clean_key        = preg_replace( '/^local_/', '', ltrim( $key, '_' ) );
			$meta[ $clean_key ] = get_post_meta( $post->ID, $key, true );
		}

		$types = wp_get_post_terms( $post->ID, APOLLO_LOCAL_TAX_TYPE, array( 'fields' => 'all' ) );
		$areas = wp_get_post_terms( $post->ID, APOLLO_LOCAL_TAX_AREA, array( 'fields' => 'all' ) );

		// Gallery (up to 5 images)
		$gallery = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$img = get_post_meta( $post->ID, "_local_image_{$i}", true );
			if ( $img ) {
				$gallery[] = is_numeric( $img )
					? wp_get_attachment_image_url( (int) $img, 'large' )
					: esc_url( $img );
			}
		}

		return array(
			'id'          => $post->ID,
			'slug'        => $post->post_name,
			'title'       => get_the_title( $post ),
			'content'     => apply_filters( 'the_content', $post->post_content ),
			'url'         => get_permalink( $post->ID ),
			'thumbnail'   => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: null,
			'gallery'     => $gallery,
			'meta'        => $meta,
			'types'       => is_wp_error( $types ) ? array() : array_map( fn( $t ) => array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ), $types ),
			'areas'       => is_wp_error( $areas ) ? array() : array_map( fn( $t ) => array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ), $areas ),
			'created_at'  => $post->post_date_gmt,
			'modified_at' => $post->post_modified_gmt,
		);
	}

	/**
	 * Parâmetros comuns de coleção (paginação, filtro, busca).
	 *
	 * @return array<string, mixed>
	 */
	public static function collection_params(): array {
		return array(
			'per_page' => array( 'type' => 'integer', 'default' => 20, 'sanitize_callback' => 'absint' ),
			'page'     => array( 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint' ),
			'orderby'  => array( 'type' => 'string',  'default' => 'title', 'sanitize_callback' => 'sanitize_text_field' ),
			'order'    => array( 'type' => 'string',  'default' => 'ASC',   'sanitize_callback' => 'sanitize_text_field', 'enum' => array( 'ASC', 'DESC' ) ),
			'type'     => array( 'type' => 'string',  'default' => '',      'sanitize_callback' => 'sanitize_text_field' ),
			'area'     => array( 'type' => 'string',  'default' => '',      'sanitize_callback' => 'sanitize_text_field' ),
			'search'   => array( 'type' => 'string',  'default' => '',      'sanitize_callback' => 'sanitize_text_field' ),
		);
	}

	/**
	 * Parâmetros de criação/edição.
	 *
	 * @return array<string, mixed>
	 */
	public static function write_params(): array {
		return array(
			'title'       => array( 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
			'content'     => array( 'type' => 'string', 'required' => false ),
			'address'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'city'        => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'state'       => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'country'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'postal'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'lat'         => array( 'type' => 'number' ),
			'lng'         => array( 'type' => 'number' ),
			'phone'       => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'website'     => array( 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' ),
			'instagram'   => array( 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' ),
			'capacity'    => array( 'type' => 'integer', 'sanitize_callback' => 'absint' ),
			'price_range' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'types'       => array( 'type' => 'array' ),
			'areas'       => array( 'type' => 'array' ),
		);
	}

	/**
	 * Salva meta keys de uma request REST.
	 *
	 * @param int              $post_id Post ID.
	 * @param \WP_REST_Request $request Request REST.
	 */
	public static function save_meta( int $post_id, \WP_REST_Request $request ): void {
		$map = array(
			'address'     => '_local_address',
			'city'        => '_local_city',
			'state'       => '_local_state',
			'country'     => '_local_country',
			'postal'      => '_local_postal',
			'phone'       => '_local_phone',
			'website'     => '_local_website',
			'instagram'   => '_local_instagram',
			'capacity'    => '_local_capacity',
			'price_range' => '_local_price_range',
		);

		foreach ( $map as $param => $meta_key ) {
			$val = $request->get_param( $param );
			if ( $val !== null ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $val ) );
			}
		}

		if ( null !== $request->get_param( 'lat' ) ) {
			update_post_meta( $post_id, '_local_lat', (string) (float) $request->get_param( 'lat' ) );
		}
		if ( null !== $request->get_param( 'lng' ) ) {
			update_post_meta( $post_id, '_local_lng', (string) (float) $request->get_param( 'lng' ) );
		}
	}

	/**
	 * Salva taxonomias de uma request REST.
	 *
	 * @param int              $post_id Post ID.
	 * @param \WP_REST_Request $request Request REST.
	 */
	public static function save_taxonomies( int $post_id, \WP_REST_Request $request ): void {
		if ( null !== $request->get_param( 'types' ) ) {
			wp_set_post_terms( $post_id, array_map( 'absint', (array) $request->get_param( 'types' ) ), APOLLO_LOCAL_TAX_TYPE );
		}
		if ( null !== $request->get_param( 'areas' ) ) {
			wp_set_post_terms( $post_id, array_map( 'absint', (array) $request->get_param( 'areas' ) ), APOLLO_LOCAL_TAX_AREA );
		}
	}
}
