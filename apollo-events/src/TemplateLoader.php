<?php
/**
 * Template Loader — Carregamento de templates com fallback de estilo
 *
 * Prioridade de busca:
 *   1. theme/apollo-events/{style}/template.php
 *   2. plugin/styles/{style}/template.php
 *   3. plugin/styles/base/template.php
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TemplateLoader {

	public function __construct() {
		add_filter( 'template_include', array( $this, 'load_template' ) );
		add_filter( 'single_template', array( $this, 'single_event_template' ) );
		add_filter( 'archive_template', array( $this, 'archive_event_template' ) );
	}

	/**
	 * Carrega template principal (fallback do WP)
	 */
	public function load_template( string $template ): string {
		if ( is_singular( APOLLO_EVENT_CPT ) ) {
			if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
				apollo_event_ensure_helpers();
			} elseif ( defined( 'APOLLO_EVENT_DIR' ) && is_readable( APOLLO_EVENT_DIR . 'includes/bootstrap.php' ) ) {
				require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';
			}
			$custom = $this->locate( 'single-event-runtime.php' );
			if ( ! $custom ) {
				$custom = $this->locate( 'single-event.php' );
			}
			if ( $custom ) {
				return $custom;
			}
		}

		if ( is_post_type_archive( APOLLO_EVENT_CPT ) || $this->is_event_taxonomy() ) {
			if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
				apollo_event_ensure_helpers();
			}
			$custom = $this->locate( 'archive-event.php' );
			if ( $custom ) {
				return $custom;
			}
		}

		return $template;
	}

	/**
	 * Template de single event
	 */
	public function single_event_template( string $template ): string {
		if ( is_singular( APOLLO_EVENT_CPT ) ) {
			if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
				apollo_event_ensure_helpers();
			} elseif ( defined( 'APOLLO_EVENT_DIR' ) && is_readable( APOLLO_EVENT_DIR . 'includes/bootstrap.php' ) ) {
				require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';
			}
			$custom = $this->locate( 'single-event-runtime.php' );
			if ( ! $custom ) {
				$custom = $this->locate( 'single-event.php' );
			}
			if ( $custom ) {
				return $custom;
			}
		}
		return $template;
	}

	/**
	 * Template de archive event
	 */
	public function archive_event_template( string $template ): string {
		if ( is_post_type_archive( APOLLO_EVENT_CPT ) || $this->is_event_taxonomy() ) {
			$custom = $this->locate( 'archive-event.php' );
			if ( $custom ) {
				return $custom;
			}
		}
		return $template;
	}

	/**
	 * Localiza um template respeitando a hierarquia de estilos
	 *
	 * @param string $filename Nome do arquivo (ex: single-event.php).
	 * @param string $style    Estilo (vazio = ativo).
	 * @return string|false Caminho absoluto do template ou false.
	 */
	public function locate( string $filename, string $style = '' ): string|false {
		if ( empty( $style ) ) {
			$style = apollo_event_get_active_style();
		}

		// 1. Tema: wp-content/themes/{theme}/apollo-events/{style}/filename
		$theme_path = get_stylesheet_directory() . '/apollo-events/' . $style . '/' . $filename;
		if ( file_exists( $theme_path ) ) {
			return $theme_path;
		}

		// 1b. Tema pai (child theme support)
		$parent_path = get_template_directory() . '/apollo-events/' . $style . '/' . $filename;
		if ( $parent_path !== $theme_path && file_exists( $parent_path ) ) {
			return $parent_path;
		}

		// 2. Plugin: styles/{style}/filename
		$style_path = APOLLO_EVENT_DIR . 'styles/' . $style . '/' . $filename;
		if ( file_exists( $style_path ) ) {
			return $style_path;
		}

		// 3. Fallback: styles/base/filename
		if ( 'base' !== $style ) {
			$base_path = APOLLO_EVENT_DIR . 'styles/base/' . $filename;
			if ( file_exists( $base_path ) ) {
				return $base_path;
			}
		}

		return false;
	}

	/**
	 * Carrega um template com variáveis injetadas
	 *
	 * @param string $filename Nome do arquivo template.
	 * @param array  $vars     Variáveis para extrair no template.
	 * @param string $style    Estilo (vazio = ativo).
	 */
	public function render( string $filename, array $vars = array(), string $style = '' ): void {
		$template = $this->locate( $filename, $style );
		if ( ! $template ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		include $template;
	}

	/**
	 * Mesmo que render() mas retorna string
	 */
	public function render_to_string( string $filename, array $vars = array(), string $style = '' ): string {
		ob_start();
		$this->render( $filename, $vars, $style );
		return ob_get_clean();
	}

	/**
	 * Verifica se estamos numa taxonomy de evento
	 */
	private function is_event_taxonomy(): bool {
		$event_taxes = array(
			APOLLO_EVENT_TAX_CATEGORY,
			APOLLO_EVENT_TAX_TYPE,
			APOLLO_EVENT_TAX_TAG,
			APOLLO_EVENT_TAX_SOUND,
			APOLLO_EVENT_TAX_SEASON,
		);

		foreach ( $event_taxes as $tax ) {
			if ( is_tax( $tax ) ) {
				return true;
			}
		}

		return false;
	}
}
