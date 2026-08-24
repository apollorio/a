<?php

/**
 * Template: HubRio page editor — /hub/app
 *
 * Preserves the production Hub::rio "Editor de Página" that previously
 * occupied /hub (a full HTML document with its own head/CSS/JS). Moved here
 * so /hub can serve the official Hub.rio directory for guests.
 *
 * @package Apollo\Hub
 * @since   1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status = defined( 'APOLLO_HUB_DIR' ) ? APOLLO_HUB_DIR . 'templates/hub-app.html' : '';
if ( $status === '' || ! is_readable( $status ) ) {
	status_header( 503 );
	nocache_headers();
	echo 'Hub app unavailable.';
	exit;
}

status_header( 200 );
nocache_headers();
header( 'Content-Type: text/html; charset=UTF-8' );
readfile( $status );
exit;
