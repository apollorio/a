<?php
/**
 * LocalsGridShortcode — [apollo_locals] renderiza grid de locais
 *
 * Uso: [apollo_locals limit="12" type="clube" area="lapa"]
 *
 * @package Apollo\Local\Shortcodes
 */

declare(strict_types=1);

namespace Apollo\Local\Shortcodes;

use Apollo\Local\TemplateLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LocalsGridShortcode {

	private TemplateLoader $loader;

	public function __construct() {
		$this->loader = new TemplateLoader();
	}

	/**
	 * Renderiza o grid.
	 *
	 * @param array|string $atts Atributos do shortcode.
	 * @return string HTML resultante.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit' => 12,
				'type'  => '',
				'area'  => '',
			),
			$atts,
			'apollo_locals'
		);

		$args = array(
			'post_type'      => APOLLO_LOCAL_CPT,
			'posts_per_page' => (int) $atts['limit'],
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$tax_query = array();

		if ( ! empty( $atts['type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_LOCAL_TAX_TYPE,
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['type'] ) ),
			);
		}

		if ( ! empty( $atts['area'] ) ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_LOCAL_TAX_AREA,
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['area'] ) ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query;
		}

		$query  = new \WP_Query( $args );
		$output = '<div class="apollo-locals-wrap"><div class="apollo-locals-grid">';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$output .= $this->loader->render_to_string(
					'local-card',
					array( 'post_id' => get_the_ID() )
				);
			}
			wp_reset_postdata();
		} else {
			$output .= '<p class="apollo-locals-empty">'
				. esc_html__( 'Nenhum local encontrado.', 'apollo-local' )
				. '</p>';
		}

		$output .= '</div></div>';
		return $output;
	}
}
