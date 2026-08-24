<?php
/**
 * Settings Bridge
 *
 * Fans the CPanel's flat $_POST['apollo'] array out to real per-plugin
 * WordPress options, and reads them back for template rendering.
 * All routes are declared in RouteRegistry and are extensible via
 * the apollo_admin_route_map filter.
 *
 * @package Apollo\Admin\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SettingsBridge {

    /** @var array<string,array{option:string,type:string,default:mixed}>|null */
    private static ?array $map = null;

    /* ─── Route map ─────────────────────────────────────────────── */

    public static function route_map(): array {
        if ( null === self::$map ) {
            self::$map = apply_filters(
                'apollo_admin_route_map',
                RouteRegistry::build()
            );
        }
        return self::$map;
    }

    /* ─── Save ───────────────────────────────────────────────────── */

    /**
     * @param array<string,mixed> $posted  Raw $_POST['apollo'] array.
     * @return array{saved:int,errors:list<string>}
     */
    public static function save( array $posted ): array {
        $map    = self::route_map();
        $saved  = 0;
        $errors = [];

        foreach ( $map as $cpanel_key => $meta ) {
            // Only save keys present in the posted data; treat missing
            // checkboxes (unchecked) as false for bool fields.
            $raw = $posted[ $cpanel_key ] ?? ( $meta['type'] === 'bool' ? false : null );
            if ( null === $raw && $meta['type'] !== 'bool' ) {
                continue;
            }

            $value = self::sanitize( $raw, $meta['type'] );

            if ( update_option( $meta['option'], $value, false ) ) {
                ++$saved;
            }
        }

        return compact( 'saved', 'errors' );
    }

    /* ─── Get ────────────────────────────────────────────────────── */

    /**
     * Read the live value for a CPanel field key.
     * Falls back to $default when no route exists (back-compat).
     *
     * @param string $cpanel_key   e.g. 'site_name', 'smtp_host', 'radio_stream_url'
     * @param mixed  $default
     * @return mixed
     */
    public static function get( string $cpanel_key, mixed $default = null ): mixed {
        $map = self::route_map();
        if ( ! isset( $map[ $cpanel_key ] ) ) {
            return $default;
        }
        $meta = $map[ $cpanel_key ];
        return get_option( $meta['option'], $meta['default'] ?? $default );
    }

    /* ─── Sanitize ───────────────────────────────────────────────── */

    public static function sanitize( mixed $raw, string $type ): mixed {
        switch ( $type ) {
            case 'bool':
                return (bool) filter_var( $raw, FILTER_VALIDATE_BOOLEAN );
            case 'int':
                return (int) $raw;
            case 'float':
                return (float) $raw;
            case 'email':
                return sanitize_email( (string) $raw );
            case 'url':
                return esc_url_raw( (string) $raw );
            case 'color':
                return sanitize_hex_color( (string) $raw ) ?: '';
            case 'textarea':
                return sanitize_textarea_field( (string) $raw );
            case 'select':
            case 'text':
            default:
                return sanitize_text_field( (string) $raw );
        }
    }
}
