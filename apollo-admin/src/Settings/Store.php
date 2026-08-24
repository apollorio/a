<?php
/**
 * Settings Store — Pure CRUD for the single serialized option.
 *
 * All settings live in one WP option (APOLLO_ADMIN_OPTION_KEY)
 * structured as: [ 'plugin-slug' => [ 'key' => 'value', … ], … ]
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Store {

    private static ?Store $instance = null;

    /** @var array<string, array<string, mixed>> */
    private array $data = array();

    private bool $loaded = false;

    public static function get_instance(): Store {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        $this->load();
    }

    /* ── READ ─────────────────────────────────────────────────────── */

    public function all(): array {
        $this->ensure_loaded();
        return $this->data;
    }

    public function for_plugin( string $slug ): array {
        $this->ensure_loaded();
        return $this->data[ $slug ] ?? array();
    }

    public function get( string $slug, string $key, mixed $default = null ): mixed {
        $this->ensure_loaded();
        return $this->data[ $slug ][ $key ] ?? $default;
    }

    /* ── WRITE ────────────────────────────────────────────────────── */

    public function set( string $slug, string $key, mixed $value ): bool {
        $this->ensure_loaded();
        if ( ! isset( $this->data[ $slug ] ) ) {
            $this->data[ $slug ] = array();
        }
        $this->data[ $slug ][ $key ] = $value;
        return $this->save();
    }

    public function update_plugin( string $slug, array $values ): bool {
        $this->ensure_loaded();
        $existing            = $this->data[ $slug ] ?? array();
        $this->data[ $slug ] = array_merge( $existing, $values );
        return $this->save();
    }

    public function replace_plugin( string $slug, array $values ): bool {
        $this->ensure_loaded();
        $this->data[ $slug ] = $values;
        return $this->save();
    }

    /* ── DELETE ────────────────────────────────────────────────────── */

    public function delete_plugin( string $slug ): bool {
        $this->ensure_loaded();
        unset( $this->data[ $slug ] );
        return $this->save();
    }

    public function delete( string $slug, string $key ): bool {
        $this->ensure_loaded();
        unset( $this->data[ $slug ][ $key ] );
        return $this->save();
    }

    /* ── INTERNALS ────────────────────────────────────────────────── */

    private function load(): void {
        if ( $this->loaded ) {
            return;
        }
        $raw          = get_option( APOLLO_ADMIN_OPTION_KEY, array() );
        $this->data   = is_array( $raw ) ? $raw : array();
        $this->loaded = true;
    }

    private function ensure_loaded(): void {
        if ( ! $this->loaded ) {
            $this->load();
        }
    }

    private function save(): bool {
        return update_option( APOLLO_ADMIN_OPTION_KEY, $this->data );
    }
}
