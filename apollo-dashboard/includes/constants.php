<?php
/**
 * Plugin Constants
 *
 * @package Apollo\Dashboard
 */

declare(strict_types=1);

namespace Apollo\Dashboard;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// REST API namespace
define( 'APOLLO_DASHBOARD_REST_NAMESPACE', 'apollo/v1' );
