<?php

/**
 * Plugin Activation Handler
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
 * Activation Handler
 */
class Activation
{

    /**
     * Run activation tasks
     *
     * @return void
     */
    public static function activate(): void
    {
        self::set_defaults();
        update_option('apollo_maps_activated', time());
    }

    /**
     * Set default options
     */
    private static function set_defaults(): void
    {
        add_option('apollo_maps_center_lat', -22.9502);
        add_option('apollo_maps_center_lng', -43.1903);
        add_option('apollo_maps_default_zoom', 12);
    }
}
