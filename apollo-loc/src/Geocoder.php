<?php
/**
 * Geocoder — Auto-resolve endereço para coordenadas via Nominatim (OSM)
 *
 * Chamado automaticamente quando um loc é criado/atualizado sem lat/lng.
 * Gratuito, sem API key, respeitando o uso fair da Nominatim API.
 *
 * @see https://nominatim.org/release-docs/develop/api/Search/
 * @package Apollo\Local
 */

declare(strict_types=1);

namespace Apollo\Local;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Geocoder {

	/** Endpoint Nominatim OSM */
	private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

	/** Cache group */
	private const CACHE_GROUP = 'apollo_geocoder';

	/** Cache TTL: 30 dias em segundos */
	private const CACHE_TTL = 30 * DAY_IN_SECONDS;

	/**
	 * Auto-geocodifica um loc se lat/lng estiverem ausentes.
	 * Chamado após save_meta() em LocalsController.
	 *
	 * @param int $post_id Post ID do loc.
	 * @return bool  true se coordenadas foram salvas, false caso contrário.
	 */
	public static function maybe_geocode( int $post_id ): bool {
		$lat = get_post_meta( $post_id, '_local_lat', true );
		$lng = get_post_meta( $post_id, '_local_lng', true );

		// Já tem coordenadas — não fazer nada
		if ( $lat !== '' && $lat !== null && $lat !== false && $lng !== '' && $lng !== null && $lng !== false ) {
			return false;
		}

		$address = get_post_meta( $post_id, '_local_address', true );
		$city    = get_post_meta( $post_id, '_local_city', true );
		$state   = get_post_meta( $post_id, '_local_state', true );
		$country = get_post_meta( $post_id, '_local_country', true ) ?: 'BR';

		if ( ! $address && ! $city ) {
			return false;
		}

		$coords = self::lookup( $address, $city, $state, $country );

		if ( ! $coords ) {
			return false;
		}

		update_post_meta( $post_id, '_local_lat', (string) $coords['lat'] );
		update_post_meta( $post_id, '_local_lng', (string) $coords['lng'] );

		/**
		 * Disparado quando coordenadas são resolvidas via geocoding.
		 *
		 * @param int   $post_id Post ID do loc.
		 * @param float $lat     Latitude.
		 * @param float $lng     Longitude.
		 */
		do_action( 'apollo/loc/geocoded', $post_id, $coords['lat'], $coords['lng'] );

		return true;
	}

	/**
	 * Faz lookup no Nominatim e retorna lat/lng ou null.
	 *
	 * @param string $address  Endereço (rua + número).
	 * @param string $city     Cidade.
	 * @param string $state    Estado (sigla ou nome).
	 * @param string $country  Código do país (ex: BR).
	 * @return array{lat:float,lng:float}|null
	 */
	public static function lookup( string $address, string $city, string $state, string $country = 'BR' ): ?array {
		// Monta query legível
		$parts = array_filter( array( trim( $address ), trim( $city ), trim( $state ), strtoupper( trim( $country ) ) ) );
		$q     = implode( ', ', $parts );

		if ( ! $q ) {
			return null;
		}

		// Cache baseado na query
		$cache_key = 'geo_' . md5( strtolower( $q ) );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		// Chama Nominatim
		$url = add_query_arg(
			array(
				'q'              => $q,
				'format'         => 'json',
				'limit'          => 1,
				'addressdetails' => 0,
			),
			self::NOMINATIM_URL
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 8,
				'user-agent' => 'Apollo/' . ( defined( 'APOLLO_VERSION' ) ? APOLLO_VERSION : '6.0.0' ) . ' (apollo.rio.br)',
				'headers'    => array( 'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data[0] ) ) {
			return null;
		}

		$result = array(
			'lat' => (float) $data[0]['lat'],
			'lng' => (float) $data[0]['lon'],
		);

		wp_cache_set( $cache_key, $result, self::CACHE_GROUP, self::CACHE_TTL );

		return $result;
	}
}
