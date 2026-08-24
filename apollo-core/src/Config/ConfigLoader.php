<?php

/**
 * Apollo Config Loader
 *
 * Central utility for loading and caching config files.
 * Provides a single entry point for all config data across the ecosystem.
 *
 * Usage:
 *   use Apollo\Core\Config\ConfigLoader;
 *   $cpts = ConfigLoader::load('cpts');
 *   $meta = ConfigLoader::load('meta');
 *
 * @package Apollo\Core\Config
 * @since   6.1.0
 */

declare(strict_types=1);

namespace Apollo\Core\Config;

if (! defined('ABSPATH')) {
    exit;
}

final class ConfigLoader
{

    /**
     * In-memory cache for loaded config files.
     *
     * @var array<string, mixed>
     */
    private static array $cache = array();

    /**
     * Valid config file names.
     *
     * @var string[]
     */
    private const VALID_FILES = array(
        'constants',
        'cpts',
        'taxonomies',
        'meta',
        'tables',
        'roles',
        'options',
        'hooks',
        'routes',
        'registry',
    );

    /**
     * Load a config file by name.
     *
     * @param string $name Config file name (without .php extension).
     * @return array The config data.
     *
     * @throws \InvalidArgumentException If config file name is not valid.
     */
    public static function load(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        if (! in_array($name, self::VALID_FILES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid config file: "%s". Valid: %s', $name, implode(', ', self::VALID_FILES))
            );
        }

        $path = self::config_path($name);

        if (! file_exists($path)) {
            throw new \RuntimeException(
                sprintf('Config file not found: %s', $path)
            );
        }

        /** @var array $data */
        $data = require $path;

        self::$cache[$name] = $data;

        return $data;
    }

    /**
     * Get full path to a config file.
     */
    public static function config_path(string $name): string
    {
        $runtime_path = self::runtime_config_path($name);

        if ($runtime_path !== null) {
            return $runtime_path;
        }

        return dirname(__DIR__, 2) . '/config/' . $name . '.php';
    }

    /**
     * Resolve runtime/host override config path when available.
     *
     * Runtime plugin should define APOLLO_RUNTIME_CONFIG_PATH.
     * If override file exists, it takes precedence over core defaults.
     */
    public static function runtime_config_path(string $name): ?string
    {
        if (! defined('APOLLO_RUNTIME_CONFIG_PATH')) {
            return null;
        }

        $runtime_base = rtrim((string) APOLLO_RUNTIME_CONFIG_PATH, '/\\');
        $candidate    = $runtime_base . DIRECTORY_SEPARATOR . $name . '.php';

        if (! file_exists($candidate)) {
            return null;
        }

        return $candidate;
    }

    /**
     * Get a specific key from a config file.
     *
     * @param string $file Config file name.
     * @param string $key  Dot-notation key (e.g., 'post.event._event_start_date').
     * @return mixed The value, or $default.
     */
    public static function get(string $file, string $key, mixed $default = null): mixed
    {
        $data = self::load($file);

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    /**
     * Check if a key exists in a config file.
     */
    public static function has(string $file, string $key): bool
    {
        $data = self::load($file);

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return false;
            }
            $data = $data[$segment];
        }

        return true;
    }

    /**
     * Clear the in-memory cache.
     * Useful for testing or after config updates.
     */
    public static function flush(): void
    {
        self::$cache = array();
    }

    /**
     * Load all config files at once (warm cache).
     *
     * @return array<string, array> All config data keyed by file name.
     */
    public static function load_all(): array
    {
        foreach (self::VALID_FILES as $name) {
            self::load($name);
        }

        return self::$cache;
    }

    /**
     * Define WordPress constants from config/constants.php.
     *
     * Called by includes/constants.php during bootstrap.
     * Uses ! defined() guard for backward compatibility.
     *
     * NOTE: WP core constants (WP_DEBUG, WP_DEBUG_DISPLAY, WP_DEBUG_LOG, etc.)
     * must be defined in wp-config.php BEFORE wp-settings.php loads.
     * This method explicitly skips WP_* constants to prevent redefinition warnings.
     */
    public static function define_constants(): void
    {
        $constants = self::load('constants');

        foreach ($constants as $name => $value) {
            // Skip WP_ prefixed constants — must come from wp-config.php only
            if (strpos($name, 'WP_') === 0) {
                continue;
            }

            if (! defined($name)) {
                define($name, $value);
            }
        }
    }

    /** Prevent instantiation. */
    private function __construct() {}
}
