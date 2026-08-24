<?php
/**
 * Apollo DJs — Uninstall
 *
 * @package Apollo\DJs
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'apollo_dj_settings' );
delete_option( 'apollo_dj_version' );
