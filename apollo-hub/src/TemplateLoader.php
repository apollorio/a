<?php

/**
 * TemplateLoader — Apollo Hub
 *
 * Intercepta single_template para CPT hub → templates/single-hub.php
 * Rota: /hub/{username} → single CPT com post_name = username
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TemplateLoader {


	public function __construct() {
		add_filter( 'single_template', array( $this, 'single_template' ) );
	}

	/**
	 * Retorna template para single hub.
	 *
	 * @param  string $template Template padrão.
	 * @return string
	 */
	public function single_template( string $template ): string {
		if ( ! is_singular( APOLLO_HUB_CPT ) ) {
			return $template;
		}

		$located = $this->locate( 'single-hub.php' );
		return $located ?: $template;
	}

	/**
	 * Localiza template com fallback.
	 *
	 * Ordem de busca:
	 * 1. theme/apollo-hub/single-hub.php
	 * 2. child-theme/apollo-hub/single-hub.php
	 * 3. plugin/templates/single-hub.php
	 *
	 * @param  string $template_name Nome do arquivo de template.
	 * @return string Caminho absoluto ou string vazia.
	 */
	public function locate( string $template_name ): string {
		$paths = array(
			get_stylesheet_directory() . '/apollo-hub/' . $template_name,
			get_template_directory() . '/apollo-hub/' . $template_name,
			APOLLO_HUB_DIR . 'templates/' . $template_name,
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return '';
	}
}
