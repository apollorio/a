<?php
/**
 * Legacy shim — /eventos/url now renders via styles/base/url-import.php.
 *
 * @package Apollo\Event
 * @deprecated 1.7.0 Use styles/base/url-import.php directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require APOLLO_EVENT_DIR . 'styles/base/url-import.php';
