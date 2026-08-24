<?php
/**
 * HaversineCalculator — cálculo de distância entre dois pontos GPS
 *
 * Fórmula de Haversine em PHP puro. Sem dependências externas.
 *
 * @package Apollo\Local\Geo
 */

declare(strict_types=1);

namespace Apollo\Local\Geo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HaversineCalculator {

	/** Raio médio da Terra em km */
	private const EARTH_RADIUS_KM = 6371.0;

	/** Raio médio da Terra em milhas */
	private const EARTH_RADIUS_MI = 3958.8;

	/**
	 * Calcula distância entre dois pontos em quilômetros.
	 *
	 * @param float $lat1 Latitude ponto A.
	 * @param float $lng1 Longitude ponto A.
	 * @param float $lat2 Latitude ponto B.
	 * @param float $lng2 Longitude ponto B.
	 * @return float Distância em km.
	 */
	public static function km( float $lat1, float $lng1, float $lat2, float $lng2 ): float {
		return self::calculate( $lat1, $lng1, $lat2, $lng2, self::EARTH_RADIUS_KM );
	}

	/**
	 * Calcula distância entre dois pontos em milhas.
	 *
	 * @param float $lat1 Latitude ponto A.
	 * @param float $lng1 Longitude ponto A.
	 * @param float $lat2 Latitude ponto B.
	 * @param float $lng2 Longitude ponto B.
	 * @return float Distância em milhas.
	 */
	public static function miles( float $lat1, float $lng1, float $lat2, float $lng2 ): float {
		return self::calculate( $lat1, $lng1, $lat2, $lng2, self::EARTH_RADIUS_MI );
	}

	/**
	 * Cálculo core da fórmula de Haversine.
	 *
	 * @param float $lat1   Latitude ponto A (graus).
	 * @param float $lng1   Longitude ponto A (graus).
	 * @param float $lat2   Latitude ponto B (graus).
	 * @param float $lng2   Longitude ponto B (graus).
	 * @param float $radius Raio da esfera.
	 * @return float Distância na unidade do raio.
	 */
	private static function calculate( float $lat1, float $lng1, float $lat2, float $lng2, float $radius ): float {
		$dlat = deg2rad( $lat2 - $lat1 );
		$dlng = deg2rad( $lng2 - $lng1 );

		$a = sin( $dlat / 2 ) ** 2
			+ cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlng / 2 ) ** 2;

		$c = 2 * asin( sqrt( $a ) );

		return round( $radius * $c, 2 );
	}

	/**
	 * Gera bounding box para pré-filtro SQL (evita Haversine em toda a tabela).
	 *
	 * @param float $lat    Latitude central.
	 * @param float $lng    Longitude central.
	 * @param float $radius Raio em km.
	 * @return array{lat_min:float, lat_max:float, lng_min:float, lng_max:float}
	 */
	public static function bounding_box( float $lat, float $lng, float $radius ): array {
		$lat_delta = $radius / self::EARTH_RADIUS_KM * ( 180 / M_PI );
		$lng_delta = $radius / ( self::EARTH_RADIUS_KM * cos( deg2rad( $lat ) ) ) * ( 180 / M_PI );

		return array(
			'lat_min' => $lat - $lat_delta,
			'lat_max' => $lat + $lat_delta,
			'lng_min' => $lng - $lng_delta,
			'lng_max' => $lng + $lng_delta,
		);
	}
}
