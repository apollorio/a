<?php
/**
 * TemplateLoader — apollo-djs
 *
 * Intercepta single_template e archive_template para CPT dj
 * Fallback: theme/{style}/ → plugin/styles/{style}/ → plugin/styles/base/
 *
 * @package Apollo\DJs
 */

declare(strict_types=1);

namespace Apollo\DJs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TemplateLoader {

	public function __construct() {
		add_filter( 'single_template', array( $this, 'single_template' ) );
		add_filter( 'archive_template', array( $this, 'archive_template' ) );
	}

	/**
	 * Template para single DJ
	 */
	public function single_template( string $template ): string {
		if ( ! is_singular( APOLLO_DJ_CPT ) ) {
			return $template;
		}

		$located = $this->locate( 'single-dj.php' );
		return $located ?: $template;
	}

	/**
	 * Template para archive DJ
	 */
	public function archive_template( string $template ): string {
		if ( ! is_post_type_archive( APOLLO_DJ_CPT ) ) {
			return $template;
		}

		$located = $this->locate( 'archive-dj.php' );
		return $located ?: $template;
	}

	/**
	 * Localiza template com fallback de estilo
	 */
	public function locate( string $template_name ): string {
		$style = APOLLO_DJ_DEFAULT_STYLE;

		$paths = array(
			get_stylesheet_directory() . '/apollo-djs/' . $style . '/' . $template_name,
			get_template_directory() . '/apollo-djs/' . $style . '/' . $template_name,
			APOLLO_DJ_DIR . 'styles/' . $style . '/' . $template_name,
			APOLLO_DJ_DIR . 'styles/base/' . $template_name,
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return '';
	}

	/**
	 * Renderiza template com variáveis
	 */
	public function render( string $template_name, array $data = array() ): void {
		$path = $this->locate( $template_name );
		if ( ! $path ) {
			return;
		}

		extract( $data, EXTR_SKIP );
		include $path;
	}

	/**
	 * Renderiza para string
	 */
	public function render_to_string( string $template_name, array $data = array() ): string {
		ob_start();
		$this->render( $template_name, $data );
		return ob_get_clean();
	}
}
