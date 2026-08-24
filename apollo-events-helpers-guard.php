<?php
/**
 * Plugin Name: Apollo Events Helpers Guard
 * Description: Brutal early guarantee that apollo_event_* globals exist before any template runs.
 * Version: 1.0.1
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Force plugin file early if mu force-load has not already.
$__ae_main = WP_CONTENT_DIR . '/plugins/apollo-events/apollo-events.php';
if ( ! defined( 'APOLLO_EVENT_FILE' ) && is_readable( $__ae_main ) ) {
	require_once $__ae_main;
}

if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
	apollo_event_ensure_helpers();
}

if ( ! function_exists( 'apollo_event_parse_date' ) ) {
	/**
	 * @param string $date Y-m-d date.
	 * @return array{timestamp:int,day:string,month_pt:string,iso_date:string,weekday_pt:string}
	 */
	function apollo_event_parse_date( string $date ): array {
		if ( function_exists( '\\Apollo\\Event\\apollo_event_parse_date' ) ) {
			return \Apollo\Event\apollo_event_parse_date( $date );
		}
		$meses_pt = array(
			1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
			7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
		);
		$dias_pt = array(
			'Mon' => 'Seg', 'Tue' => 'Ter', 'Wed' => 'Qua', 'Thu' => 'Qui',
			'Fri' => 'Sex', 'Sat' => 'Sáb', 'Sun' => 'Dom',
		);
		$ts  = strtotime( $date ) ?: time();
		$m   = (int) date( 'n', $ts );
		$dow = date( 'D', $ts );
		return array(
			'timestamp'  => $ts,
			'day'        => date( 'd', $ts ),
			'month_pt'   => $meses_pt[ $m ] ?? date( 'M', $ts ),
			'iso_date'   => date( 'Y-m-d', $ts ),
			'weekday_pt' => $dias_pt[ $dow ] ?? $dow,
		);
	}
}

// Re-assert helpers on every front request before template_include.
add_action(
	'wp',
	static function () {
		if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
			apollo_event_ensure_helpers();
		}
	},
	0
);

// #region agent log
add_action(
	'template_redirect',
	static function () {
		$dir = WP_CONTENT_DIR . '/plugins/apollo-events/';
		@file_put_contents(
			$dir . 'debug-e031aa.log',
			wp_json_encode(
				array(
					'sessionId'    => 'e031aa',
					'runId'        => 'brutal-force',
					'hypothesisId' => 'H3-mu-guard',
					'location'     => 'apollo-events-helpers-guard.php',
					'message'      => 'brutal guard on template_redirect',
					'data'         => array(
						'has_parse_date' => function_exists( 'apollo_event_parse_date' ),
						'has_file'       => defined( 'APOLLO_EVENT_FILE' ),
						'is_event'       => defined( 'APOLLO_EVENT_CPT' ) ? is_singular( APOLLO_EVENT_CPT ) : false,
					),
					'timestamp'    => (int) round( microtime( true ) * 1000 ),
				)
			) . "\n",
			FILE_APPEND | LOCK_EX
		);
	},
	0
);
// #endregion
