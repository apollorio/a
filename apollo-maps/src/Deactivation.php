<?php

/**
 * Plugin Deactivation Handler
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
 * Deactivation Handler
 */
class Deactivation
{

    /**
     * Run deactivation tasks
     *
     * @return void
     */
    public static function deactivate(): void
    {
        self::clear_cron();
        self::clear_transients();
    }

    /**
     * Clear scheduled cron events
     *
     * @return void
     */
    private static function clear_cron(): void
    {
        // No scheduled hooks yet
    }

    /**
     * Clear transients
     *
     * @return void
     */
    private static function clear_transients(): void
    {
        delete_transient('apollo_maps_cache');
    }
}
