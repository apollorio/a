<?php
/**
 * Force load apollo-events — CPT rewrite /evento/ + helpers always online.
 *
 * Same pattern as force-load-apollo-login / force-load-apollo-telegram.
 *
 * @package Apollo\MU
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$events_main = WP_CONTENT_DIR . '/plugins/apollo-events/apollo-events.php';

if ( ! file_exists( $events_main ) ) {
	return;
}

if ( defined( 'APOLLO_EVENT_FILE' ) ) {
	// Already loaded — still force global helpers for templates/OPcache drift.
	if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
		apollo_event_ensure_helpers();
	} elseif ( defined( 'APOLLO_EVENT_DIR' ) && is_readable( APOLLO_EVENT_DIR . 'includes/bootstrap.php' ) ) {
		require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';
	}
	return;
}

require_once $events_main;

if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
	apollo_event_ensure_helpers();
}
