<?php
/**
 * TemplateLoader — 4-level fallback para templates
 *
 * 1. child-theme/apollo-local/
 * 2. parent-theme/apollo-local/
 * 3. plugin/styles/{style}/
 * 4. plugin/styles/base/
 *
 * @package Apollo\Local
 */

declare(strict_types=1);

namespace Apollo\Local;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TemplateLoader {

	/**
	 * Filtra template de single loc
	 */
	public function __construct() {
		add_filter( 'single_template', array( $this, 'single_template' ) );
		add_filter( 'archive_template', array( $this, 'archive_template' ) );
	}

	/**
	 * Single template
	 */
	public function single_template( string $template ): string {
		if ( get_post_type() !== APOLLO_LOCAL_CPT ) {
			return $template;
		}

		$found = $this->locate( 'single-local' );
		return $found ?: $template;
	}

	/**
	 * Archive template
	 */
	public function archive_template( string $template ): string {
		if ( ! is_post_type_archive( APOLLO_LOCAL_CPT ) ) {
			return $template;
		}

		$found = $this->locate( 'archive-local' );
		return $found ?: $template;
	}

	/**
	 * Localiza template com 4-level fallback
	 */
	public function locate( string $name ): string {
		$style = apollo_local_option( 'default_style', APOLLO_LOCAL_DEFAULT_STYLE );

		$paths = array(
			get_stylesheet_directory() . '/apollo-local/' . $name . '.php',
			get_template_directory() . '/apollo-local/' . $name . '.php',
			APOLLO_LOCAL_DIR . 'styles/' . $style . '/' . $name . '.php',
			APOLLO_LOCAL_DIR . 'styles/base/' . $name . '.php',
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return '';
	}

	/**
	 * Renderiza template
	 */
	public function render( string $name, array $vars = array() ): void {
		$file = $this->locate( $name );
		if ( ! $file ) {
			return;
		}
		extract( $vars, EXTR_SKIP );
		include $file;
	}

	/**
	 * Renderiza template e retorna como string
	 */
	public function render_to_string( string $name, array $vars = array() ): string {
		ob_start();
		$this->render( $name, $vars );
		return ob_get_clean();
	}
}
