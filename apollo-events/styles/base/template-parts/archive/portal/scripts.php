<?php

/**
 * Portal de Eventos — runtime loader.
 *
 * Order is a hard dependency chain, same as the mockup's own load order:
 *   data      → window.APOLLO_EVENTS (real WP rows)
 *   helpers   → window.APOLLO_PORTAL (derivation over that data)
 *   app       → window.AppPortalEventos (behaviour)
 *   bootstrap → mounts the app into #apollo-portal-root
 *
 * Expects $portal_events in scope (built by archive-event.php).
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( array( 'data', 'helpers', 'app', 'bootstrap' ) as $pev_slug ) {
	$pev_file = __DIR__ . '/' . $pev_slug . '.php';
	if ( is_readable( $pev_file ) ) {
		require $pev_file;
	}
}
