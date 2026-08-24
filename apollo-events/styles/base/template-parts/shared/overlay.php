<?php
/**
 * Shared App-Shell — Overlay
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* #ax-overlay is part of the canonical app-shell — skip the local copy. */
if ( defined( 'APOLLO_APP_SHELL_LOADED' ) ) {
	return;
}
?>
    <!-- overlay compartilhado -->
    <div class="ax-overlay" id="ax-overlay" aria-hidden="true"></div>