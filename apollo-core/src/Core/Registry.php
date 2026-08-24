<?php

/**
 * Apollo Registry
 *
 * Loads apollo-registry.json from WP_CONTENT_DIR / apollo-registry.json (ou APOLLO_REGISTRY_PATH).
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

namespace Apollo\Core;

if (! defined('ABSPATH')) {
    exit;
}

class Registry
{

    private static ?array $registry  = null;
    private static string $cache_key = 'apollo_registry_data';
    private static int $cache_ttl    = 3600;

    public static function init(): void
    {
        self::load_registry();
    }

    /**
     * Caminho absoluto do ficheiro de registry único da instalação.
     *
     * Em produção: tipicamente wp-content/apollo-registry.json.
     */
    public static function resolve_registry_file_path(): string
    {
        if (defined('APOLLO_REGISTRY_PATH')) {
            return (string) constant('APOLLO_REGISTRY_PATH');
        }

        return WP_CONTENT_DIR . '/apollo-registry.json';
    }

    private static function load_registry(): void
    {
        // Try cache first
        $cached = wp_cache_get(self::$cache_key, 'apollo');
        if ($cached !== false) {
            self::$registry = $cached;
            return;
        }

        // Load from file
        $registry_path = self::resolve_registry_file_path();

        if (! file_exists($registry_path)) {
            self::$registry = array();
            return;
        }

        $json = file_get_contents($registry_path);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            self::$registry = array();
            return;
        }

        self::$registry = apply_filters('apollo/registry/data', $data);
        wp_cache_set(self::$cache_key, self::$registry, 'apollo', self::$cache_ttl);
    }

    public static function get_registry(): array
    {
        if (self::$registry === null) {
            self::load_registry();
        }
        return self::$registry ?? array();
    }

    public static function get_plugin(string $slug): ?array
    {
        $registry = self::get_registry();
        return $registry['plugins'][$slug] ?? null;
    }

    public static function get_cdn_config(): array
    {
        $registry = self::get_registry();
        return $registry['cdn'] ?? array();
    }

    public static function clear_cache(): void
    {
        wp_cache_delete(self::$cache_key, 'apollo');
        self::$registry = null;

        if (class_exists(FrontRouteDispatcher::class)) {
            FrontRouteDispatcher::clear_cache();
        }
    }
}
