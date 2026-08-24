<?php
/**
 * AssetLoader — registra e enfileira CSS/JS do Apollo Local
 *
 * Centraliza toda a gestão de assets. Plugin.php delega para cá.
 *
 * @package Apollo\Local\Assets
 */

declare(strict_types=1);

namespace Apollo\Local\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AssetLoader {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin' ) );
	}

	/**
	 * Registra todos os assets frontend.
	 */
	public function register(): void {
		// ── Leaflet (mapa OSM) ────────────────────────────────────────────
		// cdn.jsdelivr.net, not unpkg.com — unpkg isn't in the CSP allowlist
		// (script-src/style-src), so it was being silently blocked.
		wp_register_style(
			'leaflet',
			'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);

		wp_register_script(
			'leaflet',
			'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		// ── Hero slider ───────────────────────────────────────────────────
		wp_register_script(
			'apollo-loc-hero',
			APOLLO_LOCAL_URL . 'assets/js/loc-hero.js',
			array(),
			APOLLO_LOCAL_VERSION,
			true
		);

		// ── Mapa Leaflet ──────────────────────────────────────────────────
		wp_register_script(
			'apollo-loc-map',
			APOLLO_LOCAL_URL . 'assets/js/loc-map.js',
			array( 'leaflet' ),
			APOLLO_LOCAL_VERSION,
			true
		);

		// ── UI interactions ───────────────────────────────────────────────
		wp_register_script(
			'apollo-loc-ui',
			APOLLO_LOCAL_URL . 'assets/js/loc-ui.js',
			array(),
			APOLLO_LOCAL_VERSION,
			true
		);

		// ── CSS single ────────────────────────────────────────────────────
		wp_register_style(
			'apollo-loc-single',
			APOLLO_LOCAL_URL . 'assets/css/local-single.css',
			array(),
			APOLLO_LOCAL_VERSION
		);

		// ── CSS v1 style (archive/grid) ───────────────────────────────────
		wp_register_style(
			'apollo-loc-v1',
			APOLLO_LOCAL_URL . 'styles/apollo-v1/style.css',
			array(),
			APOLLO_LOCAL_VERSION
		);

		// ── Dados globais para JS (apenas se single local) ────────────────
		if ( is_singular( APOLLO_LOCAL_CPT ) ) {
			$this->enqueue_single();
		}

		// ── Archive grid ──────────────────────────────────────────────────
		if ( is_post_type_archive( APOLLO_LOCAL_CPT ) || is_tax( array( APOLLO_LOCAL_TAX_TYPE, APOLLO_LOCAL_TAX_AREA ) ) ) {
			wp_enqueue_style( 'apollo-loc-v1' );
		}
	}

	/**
	 * Enfileira assets específicos da single page.
	 */
	private function enqueue_single(): void {
		wp_enqueue_style( 'apollo-loc-single' );
		wp_enqueue_script( 'apollo-loc-hero' );

		$local_id   = get_the_ID();
		$coords     = apollo_local_get_coords( (int) $local_id );
		$has_coords = ! empty( $coords );

		if ( $has_coords ) {
			wp_enqueue_style( 'leaflet' );
			wp_enqueue_script( 'apollo-loc-map' );

			wp_localize_script(
				'apollo-loc-map',
				'apolloLocMap',
				array(
					'lat'    => $coords['lat'],
					'lng'    => $coords['lng'],
					'name'   => get_the_title(),
					'zoom'   => 15,
					'tile'   => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
				)
			);
		}

		wp_enqueue_script( 'apollo-loc-ui' );

		wp_localize_script(
			'apollo-loc-ui',
			'apolloLocData',
			array(
				'rest_url'   => esc_url_raw( rest_url( APOLLO_LOCAL_REST_NAMESPACE ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'local_id'   => (int) $local_id,
				'plugin_url' => APOLLO_LOCAL_URL,
			)
		);
	}

	/**
	 * Assets para tela admin (metaboxes).
	 *
	 * @param string $hook_suffix Hook da tela atual.
	 */
	public function register_admin( string $hook_suffix ): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== APOLLO_LOCAL_CPT ) {
			return;
		}

		// Leaflet para pré-visualização GPS no admin
		if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_style( 'leaflet' );
			wp_enqueue_script( 'leaflet' );
		}
	}
}
