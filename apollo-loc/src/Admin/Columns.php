<?php
/**
 * Columns — colunas customizadas na listagem admin de "local"
 *
 * @package Apollo\Local\Admin
 */

declare(strict_types=1);

namespace Apollo\Local\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Columns {

	public function __construct() {
		add_filter( 'manage_' . APOLLO_LOCAL_CPT . '_posts_columns', array( $this, 'register' ) );
		add_action( 'manage_' . APOLLO_LOCAL_CPT . '_posts_custom_column', array( $this, 'render' ), 10, 2 );
	}

	public function register( array $columns ): array {
		unset( $columns['date'] );

		$columns['local_city']     = __( 'Cidade', 'apollo-local' );
		$columns['local_capacity'] = __( 'Capacidade', 'apollo-local' );
		$columns['local_coords']   = __( 'Coords', 'apollo-local' );
		$columns['date']           = __( 'Data', 'apollo-local' );

		return $columns;
	}

	public function render( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'local_city':
				$city  = get_post_meta( $post_id, '_local_city', true );
				$state = get_post_meta( $post_id, '_local_state', true );
				echo esc_html( implode( '/', array_filter( array( $city, $state ) ) ) ?: '—' );
				break;

			case 'local_capacity':
				$cap = get_post_meta( $post_id, '_local_capacity', true );
				echo $cap ? esc_html( number_format( (int) $cap ) . ' pessoas' ) : '—';
				break;

			case 'local_coords':
				$lat = get_post_meta( $post_id, '_local_lat', true );
				$lng = get_post_meta( $post_id, '_local_lng', true );
				if ( $lat && $lng ) {
					printf(
						'<span title="%s" style="font-family:monospace;font-size:11px;color:#888">%s, %s</span>',
						esc_attr( 'lat: ' . $lat . ' / lng: ' . $lng ),
						esc_html( round( (float) $lat, 4 ) ),
						esc_html( round( (float) $lng, 4 ) )
					);
				} else {
					echo '<span style="color:#ccc;">—</span>';
				}
				break;
		}
	}
}
