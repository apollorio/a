<?php

/**
 * Helper Functions
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin instance
 *
 * @return Plugin
 */
function apollo_admin(): Plugin
{
    return Plugin::get_instance();
}

/**
 * Get a specific setting value
 *
 * @param string $plugin_slug Plugin slug (e.g. 'apollo-core')
 * @param string $key         Setting key
 * @param mixed  $default     Default value
 * @return mixed
 */
function apollo_admin_get_setting(string $plugin_slug, string $key, mixed $default = null): mixed
{
    return Settings::get_instance()->get($plugin_slug, $key, $default);
}

/**
 * Update a specific setting value
 *
 * @param string $plugin_slug Plugin slug
 * @param string $key         Setting key
 * @param mixed  $value       Value to set
 * @return bool
 */
function apollo_admin_update_setting(string $plugin_slug, string $key, mixed $value): bool
{
    return Settings::get_instance()->set($plugin_slug, $key, $value);
}

/**
 * Get all settings for a plugin
 *
 * @param string $plugin_slug Plugin slug
 * @return array
 */
function apollo_admin_get_plugin_settings(string $plugin_slug): array
{
    return Settings::get_instance()->for_plugin($plugin_slug);
}

/**
 * Check if an Apollo plugin is active
 *
 * @param string $plugin_slug Plugin slug (e.g. 'apollo-core')
 * @return bool
 */
function apollo_is_plugin_active(string $plugin_slug): bool
{
    return Registry::get_instance()->is_active($plugin_slug);
}
