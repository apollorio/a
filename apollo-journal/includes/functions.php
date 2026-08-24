<?php

/**
 * Helper Functions
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcut to the singleton plugin instance.
 *
 * @return Plugin
 */
function apollo_journal(): Plugin {
	return Plugin::get_instance();
}

/**
 * Human-readable compact time-ago label.
 *
 * Delegates to apollo_time_ago() — compact numeric format (no 'atrás').
 * Falls back to local calculation if apollo-core not active.
 *
 * @param string $datetime MySQL datetime string.
 * @return string e.g. '53min', '2h', '7d'
 */
function aj_time_ago( string $datetime ): string {
	if ( function_exists( 'apollo_time_ago' ) ) {
		return apollo_time_ago( $datetime );
	}
	$diff = max( 0, time() - (int) strtotime( $datetime ) );
	if ( $diff < 60 ) {
		return $diff . 's';
	}
	if ( $diff < 3600 ) {
		return floor( $diff / 60 ) . 'min';
	}
	if ( $diff < 86400 ) {
		return floor( $diff / 3600 ) . 'h';
	}
	if ( $diff < 604800 ) {
		return floor( $diff / 86400 ) . 'd';
	}
	if ( $diff < 31536000 ) {
		return floor( $diff / 604800 ) . 'w';
	}
	return floor( $diff / 31536000 ) . 'y';
}
