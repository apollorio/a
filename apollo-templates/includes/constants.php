<?php
/**
 * Plugin Constants
 *
 * @package Apollo\Templates
 */

declare(strict_types=1);

namespace Apollo\Templates;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// REST API namespace
define( 'APOLLO_TEMPLATES_REST_NAMESPACE', 'apollo/v1' );
