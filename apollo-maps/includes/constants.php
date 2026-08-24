<?php

/**
 * Plugin Constants
 *
 * @package Apollo\Maps
 */

declare(strict_types=1);

namespace Apollo\Maps;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// REST API namespace (fallback if main file not loaded yet)
if (! defined('APOLLO_MAPS_REST_NAMESPACE')) {
    define('APOLLO_MAPS_REST_NAMESPACE', 'apollo/v1');
}

// Database tables (without prefix)
// define( 'APOLLO_MAPS_TABLE_EXAMPLE', 'apollo_maps_example' );

// Add plugin-specific constants below
