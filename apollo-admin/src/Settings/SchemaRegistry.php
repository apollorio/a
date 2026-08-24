<?php
/**
 * Schema Registry — loads per-plugin schema files from Schema/ directory.
 *
 * Each file in Schema/ returns an array of field definitions for one plugin slug.
 * File name = plugin slug (sanitized). E.g. Schema/apollo-core.php → 'apollo-core' schema.
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SchemaRegistry {

    /** @var array<string, array>|null Cached schemas */
    private static ?array $cache = null;

    /**
     * Get schema for a specific plugin slug.
     *
     * @return array<string, array{type:string,label:string,default:mixed,options?:array}>
     */
    public static function get( string $slug ): array {
        $all = self::all();
        return $all[ $slug ] ?? array();
    }

    /**
     * Get all schemas keyed by plugin slug.
     */
    public static function all(): array {
        if ( null !== self::$cache ) {
            return self::$cache;
        }

        self::$cache = array();
        $dir         = __DIR__ . '/Schema/';

        if ( ! is_dir( $dir ) ) {
            return self::$cache;
        }

        $files = glob( $dir . '*.php' );
        if ( ! $files ) {
            return self::$cache;
        }

        foreach ( $files as $file ) {
            $slug = basename( $file, '.php' );
            $data = include $file;
            if ( is_array( $data ) ) {
                self::$cache[ $slug ] = $data;
            }
        }

        return self::$cache;
    }

    /**
     * Clear the internal cache (useful for tests).
     */
    public static function flush(): void {
        self::$cache = null;
    }
}
