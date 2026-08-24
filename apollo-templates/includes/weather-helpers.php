<?php
/**
 * Weather Helpers - OpenMeteo API Integration
 *
 * Fetches real-time weather for Rio de Janeiro using OpenMeteo (free, no API key).
 * Caches via WordPress transients (30 minutes TTL).
 *
 * @package Apollo\Templates
 * @link https://open-meteo.com/
 */

declare(strict_types=1);

namespace Apollo\Templates\Weather;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Rio de Janeiro coordinates
const RIO_LAT = -22.9068;
const RIO_LON = -43.1729;

/**
 * Fetch weather data from OpenMeteo API
 *
 * @return array|null Weather data or null on failure
 */
function fetch_rio_weather(): ?array {
	// Check cache first (30min TTL)
	$cached = get_transient( 'apollo_rio_weather' );
	if ( $cached !== false ) {
		return $cached;
	}

	// OpenMeteo API - FREE, no auth required
	$url = sprintf(
		'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current=temperature_2m,weather_code,relative_humidity_2m,wind_speed_10m&timezone=America/Sao_Paulo',
		RIO_LAT,
		RIO_LON
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 10,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'Apollo Weather: Failed to fetch - ' . $response->get_error_message() );
		return null;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['current'] ) ) {
		error_log( 'Apollo Weather: Invalid API response' );
		return null;
	}

	$current = $data['current'];

	// Parse weather data
	$weather = array(
		'temp'       => round( $current['temperature_2m'] ) . '°',
		'code'       => (int) $current['weather_code'],
		'humidity'   => $current['relative_humidity_2m'] ?? null,
		'wind_speed' => $current['wind_speed_10m'] ?? null,
		'timestamp'  => $current['time'] ?? current_time( 'mysql' ),
	);

	// Map weather code to condition and icon
	$mapped               = map_weather_code( $weather['code'] );
	$weather['condition'] = $mapped['condition'];
	$weather['icon']      = $mapped['icon'];

	// Cache for 30 minutes
	set_transient( 'apollo_rio_weather', $weather, 30 * MINUTE_IN_SECONDS );

	return $weather;
}

/**
 * Map OpenMeteo weather codes to conditions and RemixIcon classes
 *
 * @link https://open-meteo.com/en/docs#weathervariables
 *
 * @param int $code Weather code from API
 * @return array ['condition' => string, 'icon' => string]
 */
function map_weather_code( int $code ): array {
	$map = array(
		// Clear
		0  => array(
			'condition' => 'Céu limpo',
			'icon'      => 'ri-sun-fill',
		),

		// Partly cloudy
		1  => array(
			'condition' => 'Parcialmente nublado',
			'icon'      => 'ri-cloud-fill',
		),
		2  => array(
			'condition' => 'Parcialmente nublado',
			'icon'      => 'ri-cloudy-fill',
		),
		3  => array(
			'condition' => 'Nublado',
			'icon'      => 'ri-cloudy-2-fill',
		),

		// Fog
		45 => array(
			'condition' => 'Neblina',
			'icon'      => 'ri-mist-fill',
		),
		48 => array(
			'condition' => 'Névoa',
			'icon'      => 'ri-mist-fill',
		),

		// Drizzle
		51 => array(
			'condition' => 'Garoa leve',
			'icon'      => 'ri-drizzle-fill',
		),
		53 => array(
			'condition' => 'Garoa moderada',
			'icon'      => 'ri-drizzle-fill',
		),
		55 => array(
			'condition' => 'Garoa intensa',
			'icon'      => 'ri-drizzle-fill',
		),

		// Rain
		61 => array(
			'condition' => 'Chuva leve',
			'icon'      => 'ri-rainy-fill',
		),
		63 => array(
			'condition' => 'Chuva moderada',
			'icon'      => 'ri-rainy-fill',
		),
		65 => array(
			'condition' => 'Chuva forte',
			'icon'      => 'ri-heavy-showers-fill',
		),
		80 => array(
			'condition' => 'Pancadas leves',
			'icon'      => 'ri-showers-fill',
		),
		81 => array(
			'condition' => 'Pancadas moderadas',
			'icon'      => 'ri-showers-fill',
		),
		82 => array(
			'condition' => 'Pancadas violentas',
			'icon'      => 'ri-heavy-showers-fill',
		),

		// Snow (rare in Rio, but included)
		71 => array(
			'condition' => 'Neve leve',
			'icon'      => 'ri-snowy-fill',
		),
		73 => array(
			'condition' => 'Neve moderada',
			'icon'      => 'ri-snowy-fill',
		),
		75 => array(
			'condition' => 'Neve intensa',
			'icon'      => 'ri-snowy-fill',
		),
		77 => array(
			'condition' => 'Granizo',
			'icon'      => 'ri-hail-fill',
		),

		// Thunderstorm
		95 => array(
			'condition' => 'Trovoada',
			'icon'      => 'ri-thunderstorms-fill',
		),
		96 => array(
			'condition' => 'Trovoada com granizo',
			'icon'      => 'ri-thunderstorms-fill',
		),
		99 => array(
			'condition' => 'Tempestade severa',
			'icon'      => 'ri-thunderstorms-fill',
		),
	);

	return $map[ $code ] ?? array(
		'condition' => 'Desconhecido',
		'icon'      => 'ri-question-fill',
	);
}

/**
 * Hook into apollo_mural_weather_* filters
 */
function init_weather_filters(): void {
	add_filter( 'apollo_mural_weather_temp', __NAMESPACE__ . '\\get_weather_temp' );
	add_filter( 'apollo_mural_weather_condition', __NAMESPACE__ . '\\get_weather_condition' );
	add_filter( 'apollo_mural_weather_icon', __NAMESPACE__ . '\\get_weather_icon' );
}
add_action( 'init', __NAMESPACE__ . '\\init_weather_filters' );

/**
 * Filter: Get current temperature
 */
function get_weather_temp( string $default ): string {
	$weather = fetch_rio_weather();
	return $weather['temp'] ?? $default;
}

/**
 * Filter: Get weather condition
 */
function get_weather_condition( string $default ): string {
	$weather = fetch_rio_weather();
	return $weather['condition'] ?? $default;
}

/**
 * Filter: Get weather icon
 */
function get_weather_icon( string $default ): string {
	$weather = fetch_rio_weather();
	return $weather['icon'] ?? $default;
}

/**
 * AJAX endpoint to force refresh weather cache
 * Usage: POST /wp-admin/admin-ajax.php?action=apollo_refresh_weather
 */
function ajax_refresh_weather(): void {
	check_ajax_referer( 'apollo_refresh_weather', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	delete_transient( 'apollo_rio_weather' );
	$weather = fetch_rio_weather();

	if ( $weather ) {
		wp_send_json_success( $weather );
	} else {
		wp_send_json_error( 'Failed to fetch weather', 500 );
	}
}
add_action( 'wp_ajax_apollo_refresh_weather', __NAMESPACE__ . '\\ajax_refresh_weather' );

/**
 * WP-CLI command to test weather fetching
 * Usage: wp eval "Apollo\Templates\Weather\fetch_rio_weather();"
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command(
		'apollo weather',
		function () {
			delete_transient( 'apollo_rio_weather' );
			$weather = fetch_rio_weather();

			if ( $weather ) {
				\WP_CLI::success( 'Weather fetched successfully:' );
				\WP_CLI::line( 'Temperature: ' . $weather['temp'] );
				\WP_CLI::line( 'Condition: ' . $weather['condition'] );
				\WP_CLI::line( 'Icon: ' . $weather['icon'] );
				\WP_CLI::line( 'Code: ' . $weather['code'] );
			} else {
				\WP_CLI::error( 'Failed to fetch weather' );
			}
		}
	);
}
