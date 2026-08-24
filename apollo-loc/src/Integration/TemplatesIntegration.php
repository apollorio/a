<?php
/**
 * TemplatesIntegration — integração com apollo-templates
 *
 * Registra template parts de loc para uso global pelo sistema de templates.
 *
 * @package Apollo\Local\Integration
 */

declare(strict_types=1);

namespace Apollo\Local\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TemplatesIntegration {

	public function __construct() {
		if ( defined( 'APOLLO_TEMPLATES_VERSION' ) ) {
			add_filter( 'apollo/templates/parts', array( $this, 'register_parts' ) );
		}
	}

	/**
	 * Adiciona template parts do Local ao registro global.
	 *
	 * @param array $parts Template parts existentes.
	 * @return array Parts com os do Local adicionados.
	 */
	public function register_parts( array $parts ): array {
		$dir = APOLLO_LOCAL_DIR . 'templates/parts/';

		$parts['loc-card']      = $dir . 'loc-hero.php';    // compatibilidade
		$parts['loc-hero']      = $dir . 'loc-hero.php';
		$parts['loc-gallery']   = $dir . 'loc-gallery.php';
		$parts['loc-events']    = $dir . 'loc-events.php';
		$parts['loc-sidebar']   = $dir . 'loc-sidebar.php';
		$parts['loc-contact']   = $dir . 'loc-contact.php';
		$parts['loc-residents'] = $dir . 'loc-residents.php';
		$parts['loc-reviews']   = $dir . 'loc-reviews.php';

		return $parts;
	}
}
