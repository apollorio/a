<?php

/**
 * Helper Functions
 *
 * @package Apollo\Maps
 */

declare(strict_types=1);

namespace Apollo\Maps;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin instance
 *
 * @return Plugin
 */

function apollo_maps(): Plugin
{
    return Plugin::get_instance();
}

// Add plugin-specific helper functions below
