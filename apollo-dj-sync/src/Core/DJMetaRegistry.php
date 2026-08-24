<?php
/**
 * Registers Apollo DJ Sync user meta keys via register_meta().
 *
 * Diamond Rule 1: Meta keys registered through this class only — never scattered.
 * Keys are prefixed _apollo_dj_ per registry naming convention.
 *
 * Divergence D2 (globals/15-divergences.json): wired in apollo-login AppAuthController::build_dj_access_payload()
 * (2026-05-26). Admin edits here flow to GET /dj/permissions when user meta is set; unset metas fall back to site options / membership.
 */

namespace Apollo\DJSync\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DJMetaRegistry {

    /**
     * Meta key → [type, single, default, description].
     *
     * @var array<string, array{type:string, single:bool, default:mixed, description:string}>
     */
    private const META = [
        '_apollo_dj_allowed_tabs'   => [
            'type'        => 'array',
            'single'      => true,
            'default'     => [ 'analysis', 'waveform' ],
            'description' => 'Which tabs the user can access in apolloDJ.exe',
        ],
        '_apollo_dj_quantize_level' => [
            'type'        => 'string',
            'single'      => true,
            'default'     => '1/16',
            'description' => 'Beat quantize resolution forced by server',
        ],
        '_apollo_dj_ultra_mode'     => [
            'type'        => 'boolean',
            'single'      => true,
            'default'     => false,
            'description' => 'Enable ultra-accuracy madmom + full-spectrum processing',
        ],
        '_apollo_dj_daily_limit'    => [
            'type'        => 'integer',
            'single'      => true,
            'default'     => 10,
            'description' => 'Max tracks analyzed per calendar day (0 = unlimited)',
        ],
        '_apollo_dj_premium_until'  => [
            'type'        => 'string',
            'single'      => true,
            'default'     => '',
            'description' => 'ISO 8601 date when premium access expires',
        ],
        '_apollo_dj_can_export'     => [
            'type'        => 'boolean',
            'single'      => true,
            'default'     => false,
            'description' => 'Allow Rekordbox XML / Serato CSV export',
        ],
        '_apollo_dj_can_harmonic'   => [
            'type'        => 'boolean',
            'single'      => true,
            'default'     => false,
            'description' => 'Allow harmonic mixer / Camelot wheel tab',
        ],
        '_apollo_dj_min_accuracy'   => [
            'type'        => 'integer',
            'single'      => true,
            'default'     => 85,
            'description' => 'Minimum BPM confidence % before analysis result is accepted',
        ],
    ];

    public function register(): void {
        foreach ( self::META as $key => $args ) {
            $schema = [ 'type' => $args['type'] ];
            if ( 'array' === $args['type'] ) {
                $schema['items'] = [ 'type' => 'string' ];
            }

            register_meta(
                'user',
                $key,
                [
                    'type'              => $args['type'],
                    'single'            => $args['single'],
                    'default'           => $args['default'],
                    'description'       => $args['description'],
                    'show_in_rest'      => false, // Exposed only through /dj/permissions — not the default /users/{id} endpoint.
                    'sanitize_callback' => $this->_sanitizer( $args['type'] ),
                    'auth_callback'     => fn( $allowed, $meta_key, $user_id ) => current_user_can( 'edit_user', $user_id ),
                ]
            );
        }
    }

    /**
     * Return a type-appropriate sanitizer callback.
     */
    private function _sanitizer( string $type ): callable {
        return match ( $type ) {
            'integer' => 'absint',
            'boolean' => fn( $v ) => (bool) $v,
            'array'   => fn( $v ) => is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : [],
            default   => 'sanitize_text_field',
        };
    }

    /**
     * Return all registered meta keys (used by controller + admin).
     *
     * @return array<string, array>
     */
    public static function all(): array {
        return self::META;
    }
}
