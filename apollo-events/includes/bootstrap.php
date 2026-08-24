<?php
/**
 * Global bootstrap for template helpers (NO namespace).
 *
 * Templates and template_include can run before / without full plugin
 * bootstrap having exposed global aliases. Call apollo_event_ensure_helpers()
 * before any template that uses apollo_event_*() in the global namespace.
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_event_ensure_helpers' ) ) {
	/**
	 * Load namespaced helpers + global aliases. Safe to call repeatedly.
	 */
	function apollo_event_ensure_helpers(): void {
		static $done = false;
		if ( $done ) {
			return;
		}

		$dir = defined( 'APOLLO_EVENT_DIR' )
			? APOLLO_EVENT_DIR
			: dirname( __DIR__ ) . '/';

		if ( ! defined( 'APOLLO_EVENT_DIR' ) ) {
			define( 'APOLLO_EVENT_DIR', trailingslashit( $dir ) );
		}

		$constants = APOLLO_EVENT_DIR . 'includes/constants.php';
		$functions = APOLLO_EVENT_DIR . 'includes/functions.php';
		$global    = APOLLO_EVENT_DIR . 'includes/functions-global.php';
		$render    = APOLLO_EVENT_DIR . 'includes/render-single.php';
		$privacy   = APOLLO_EVENT_DIR . 'includes/privacy.php';
		$iframe    = APOLLO_EVENT_DIR . 'includes/iframe-proxy.php';

		if ( is_readable( $constants ) ) {
			require_once $constants;
		}
		if ( is_readable( $functions ) ) {
			require_once $functions;
		}
		if ( is_readable( $global ) ) {
			require_once $global;
		}
		if ( is_readable( $render ) ) {
			require_once $render;
		}
		if ( is_readable( $privacy ) ) {
			require_once $privacy;
		}
		/* Same-origin proxy for CDN iframes — defeats the asset host's
		   X-Frame-Options: SAMEORIGIN, which no client-side change can. */
		if ( is_readable( $iframe ) ) {
			require_once $iframe;
		}

		// Last-resort: define critical globals if aliases still missing.
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
				$ts = strtotime( $date ) ?: time();
				$m  = (int) date( 'n', $ts );
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

		$done = true;
	}
}

apollo_event_ensure_helpers();
