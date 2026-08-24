<?php
/**
 * SUPERSEDED — Portal de Eventos runtime.
 *
 * PHASE 002 split this file into portal/{data,helpers,app,bootstrap}.php
 * behind portal/scripts.php. Kept as a thin forwarder rather than deleted so
 * any theme override or stale include path still resolves.
 *
 * Expects $portal_events in scope.
 *
 * @package Apollo\Event
 * @deprecated 1.5.3 Use portal/scripts.php.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require __DIR__ . '/portal/scripts.php';
