<?php
/**
 * SocialIntegration — integração com apollo-social
 *
 * Processa eventos de check-in, compartilhamento e favoritos
 * ligados a locais/venues.
 *
 * @package Apollo\Local\Integration
 */

declare(strict_types=1);

namespace Apollo\Local\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SocialIntegration {

	public function __construct() {
		if ( defined( 'APOLLO_SOCIAL_VERSION' ) ) {
			add_action( 'apollo/social/checkin', array( $this, 'handle_checkin' ), 10, 2 );
			add_action( 'apollo/social/share',   array( $this, 'handle_share' ),   10, 2 );
		}

		// Registro de CPT para favoritos (apollo-fav)
		if ( defined( 'APOLLO_FAV_VERSION' ) ) {
			add_filter( 'apollo/fav/supported_post_types', array( $this, 'register_fav_support' ) );
		}

		// Registro de CPT para wow/reações (apollo-wow)
		if ( defined( 'APOLLO_WOW_VERSION' ) ) {
			add_filter( 'apollo/wow/supported_post_types', array( $this, 'register_wow_support' ) );
		}
	}

	/**
	 * Processa check-in de usuário em um local.
	 *
	 * @param int $user_id  Usuário fazendo check-in.
	 * @param int $local_id Local (post ID).
	 */
	public function handle_checkin( int $user_id, int $local_id ): void {
		if ( get_post_type( $local_id ) !== APOLLO_LOCAL_CPT ) {
			return;
		}

		// Incrementa contador de visitas
		$visits = (int) get_post_meta( $local_id, '_local_visit_count', true );
		update_post_meta( $local_id, '_local_visit_count', $visits + 1 );

		do_action( 'apollo/loc/checkin', $user_id, $local_id );
	}

	/**
	 * Processa compartilhamento de local.
	 *
	 * @param int    $user_id  Usuário compartilhando.
	 * @param int    $local_id Local (post ID).
	 */
	public function handle_share( int $user_id, int $local_id ): void {
		if ( get_post_type( $local_id ) !== APOLLO_LOCAL_CPT ) {
			return;
		}
		do_action( 'apollo/loc/shared', $user_id, $local_id );
	}

	/**
	 * Habilita suporte a favoritos para locais.
	 *
	 * @param array $post_types CPTs suportados.
	 * @return array CPTs com 'local' adicionado.
	 */
	public function register_fav_support( array $post_types ): array {
		$post_types[] = APOLLO_LOCAL_CPT;
		return $post_types;
	}

	/**
	 * Habilita suporte a wow/reações para locais.
	 *
	 * @param array $post_types CPTs suportados.
	 * @return array CPTs com 'local' adicionado.
	 */
	public function register_wow_support( array $post_types ): array {
		$post_types[] = APOLLO_LOCAL_CPT;
		return $post_types;
	}
}
