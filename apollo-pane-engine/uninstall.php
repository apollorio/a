<?php
/**
 * Apollo Pane Engine — Uninstall
 *
 * Cleans up options and transients on plugin deletion.
 *
 * @package Apollo\PaneEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('apollo_pane_engine_version');
delete_transient('apollo_pane_engine_rewrite_flushed');
