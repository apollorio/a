<?php
/**
 * SingleLocalShortcode — [apollo_local id=""] renderiza card de local único
 *
 * @package Apollo\Local\Shortcodes
 */

declare(strict_types=1);

namespace Apollo\Local\Shortcodes;

use Apollo\Local\TemplateLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SingleLocalShortcode {

	private TemplateLoader $loader;

	public function __construct() {
		$this->loader = new TemplateLoader();
	}

	/**
	 * Renderiza card de local único.
	 *
	 * @param array|string $atts Atributos do shortcode.
	 * @return string HTML resultante.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array( 'id' => 0 ),
			$atts,
			'apollo_local'
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post || APOLLO_LOCAL_CPT !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		return '<div class="apollo-local-embed">'
			. $this->loader->render_to_string( 'local-card', array( 'post_id' => $post_id ) )
			. '</div>';
	}
}
