<?php
/**
 * ShortcodeRegistry — registra todos os shortcodes do Apollo Local
 *
 * Delega o render para cada classe individual de shortcode.
 *
 * @package Apollo\Local\Shortcodes
 */

declare(strict_types=1);

namespace Apollo\Local\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShortcodeRegistry {

	public function __construct() {
		add_shortcode( 'apollo_locals',  array( new LocalsGridShortcode(), 'render' ) );
		add_shortcode( 'apollo_local',   array( new SingleLocalShortcode(), 'render' ) );
		add_shortcode( 'apollo_map',     array( new MapShortcode(), 'render' ) );
		add_shortcode( 'apollo_add_loc', array( new AddLocShortcode(), 'render' ) );

		// Integração com apollo-shortcodes (lista de shortcodes disponíveis)
		add_filter( 'apollo_shortcodes_registry', array( $this, 'expose_to_registry' ) );
	}

	/**
	 * Expõe shortcodes para o apollo-shortcodes plugin.
	 *
	 * @param array $registry Registro existente.
	 * @return array Registro atualizado.
	 */
	public function expose_to_registry( array $registry ): array {
		$registry['apollo_locals']  = array(
			'description' => __( 'Grid de locais/venues', 'apollo-local' ),
			'atts'        => array( 'limit', 'type', 'area' ),
		);
		$registry['apollo_local']   = array(
			'description' => __( 'Card de local único', 'apollo-local' ),
			'atts'        => array( 'id' ),
		);
		$registry['apollo_map']     = array(
			'description' => __( 'Mapa de locais (Leaflet OSM)', 'apollo-local' ),
			'atts'        => array( 'locs', 'center', 'zoom', 'height' ),
		);
		$registry['apollo_add_loc'] = array(
			'description' => __( 'Formulário para adicionar local', 'apollo-local' ),
			'atts'        => array(),
		);

		return $registry;
	}
}
