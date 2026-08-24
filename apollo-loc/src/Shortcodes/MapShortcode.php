<?php
/**
 * MapShortcode — [apollo_map] renderiza mapa Leaflet/OSM completo
 *
 * Uso: [apollo_map center="-22.9068,-43.1729" zoom="12" height="500px"]
 *
 * @package Apollo\Local\Shortcodes
 */

declare(strict_types=1);

namespace Apollo\Local\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MapShortcode {

	/**
	 * Renderiza o mapa interativo.
	 *
	 * @param array|string $atts Atributos do shortcode.
	 * @return string HTML resultante.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'locs'   => '',
				'center' => '',
				'zoom'   => 12,
				'height' => '500px',
			),
			$atts,
			'apollo_map'
		);

		$settings   = get_option( 'apollo_local_settings', array() );
		$center_lat = $settings['default_center_lat'] ?? -22.9068;
		$center_lng = $settings['default_center_lng'] ?? -43.1729;

		if ( ! empty( $atts['center'] ) ) {
			$parts = explode( ',', $atts['center'] );
			if ( count( $parts ) === 2 ) {
				$center_lat = (float) trim( $parts[0] );
				$center_lng = (float) trim( $parts[1] );
			}
		}

		$zoom      = max( 1, min( 20, (int) $atts['zoom'] ) );
		$height    = sanitize_text_field( $atts['height'] );
		$map_id    = 'apollo-loc-map-' . wp_unique_id();
		$locs_json = '';

		// Locs específicos (ids CSV) ou todos publicados
		if ( ! empty( $atts['locs'] ) ) {
			$ids      = array_map( 'absint', explode( ',', $atts['locs'] ) );
			$ids      = array_filter( $ids );
			$locs_arr = $this->build_markers( $ids );
		} else {
			$locs_arr = $this->build_all_markers();
		}

		$locs_json = wp_json_encode( $locs_arr );

		// Enfileira Leaflet
		wp_enqueue_style( 'leaflet' );
		wp_enqueue_script( 'apollo-loc-map' );

		$style = sprintf( 'height:%s;width:100%%;border-radius:12px;overflow:hidden;', esc_attr( $height ) );

		return sprintf(
			'<div id="%s" class="apollo-loc-map-wrap" style="%s" data-center-lat="%f" data-center-lng="%f" data-zoom="%d" data-locs=\'%s\'></div>',
			esc_attr( $map_id ),
			esc_attr( $style ),
			$center_lat,
			$center_lng,
			$zoom,
			esc_attr( $locs_json )
		);
	}

	/**
	 * Constrói marcadores para IDs específicos.
	 *
	 * @param int[] $ids Lista de post IDs.
	 * @return array Lista de marcadores {lat, lng, title, url}.
	 */
	private function build_markers( array $ids ): array {
		$markers = array();
		foreach ( $ids as $id ) {
			$marker = $this->post_to_marker( $id );
			if ( $marker ) {
				$markers[] = $marker;
			}
		}
		return $markers;
	}

	/**
	 * Constrói marcadores para todos os locais publicados.
	 *
	 * @return array Lista de marcadores.
	 */
	private function build_all_markers(): array {
		$posts = get_posts(
			array(
				'post_type'      => APOLLO_LOCAL_CPT,
				'posts_per_page' => 500,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array( 'key' => '_local_lat', 'compare' => 'EXISTS' ),
					array( 'key' => '_local_lng', 'compare' => 'EXISTS' ),
				),
			)
		);

		$markers = array();
		foreach ( $posts as $post ) {
			$marker = $this->post_to_marker( $post->ID );
			if ( $marker ) {
				$markers[] = $marker;
			}
		}
		return $markers;
	}

	/**
	 * Converte post para array de marcador.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Marcador ou null se sem coords.
	 */
	private function post_to_marker( int $post_id ): ?array {
		$lat = (float) get_post_meta( $post_id, '_local_lat', true );
		$lng = (float) get_post_meta( $post_id, '_local_lng', true );

		if ( ! $lat || ! $lng ) {
			return null;
		}

		return array(
			'lat'   => $lat,
			'lng'   => $lng,
			'title' => esc_html( get_the_title( $post_id ) ),
			'url'   => esc_url( get_permalink( $post_id ) ),
		);
	}
}
