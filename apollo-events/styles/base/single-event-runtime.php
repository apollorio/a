<?php
/**
 * Alias entry — same body as single-event.php (TemplateLoader may prefer this name).
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( defined( 'APOLLO_EVENT_SINGLE_RENDERING' ) ) {
	return;
}
define( 'APOLLO_EVENT_SINGLE_RENDERING', true );
require __DIR__ . '/single-event.php';
