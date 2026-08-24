<?php
/**
 * Settings Exporter — JSON export / import logic.
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Exporter {

    /**
     * Export all settings as pretty-printed JSON.
     */
    public static function export(): string {
        $store = Store::get_instance();
        return wp_json_encode( $store->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    }

    /**
     * Import settings from a JSON string (merge, not replace).
     */
    public static function import( string $json ): bool {
        $incoming = json_decode( $json, true );
        if ( ! is_array( $incoming ) ) {
            return false;
        }

        $store = Store::get_instance();

        foreach ( $incoming as $slug => $settings ) {
            if ( is_array( $settings ) ) {
                $store->update_plugin( sanitize_key( $slug ), $settings );
            }
        }

        return true;
    }
}
