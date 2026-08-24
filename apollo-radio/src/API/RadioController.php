<?php

namespace Apollo\Radio\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Apollo\Radio\Service\SoundCloudProxy;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API controller for Apollo Radio.
 *
 * Endpoints:
 *   GET /apollo/v1/radio/status   — health + config
 *   GET /apollo/v1/radio/now      — current playback position (pure calculation)
 *   GET /apollo/v1/radio/playlist — 3-hour block JSON (CDN proxy + cache)
 *   GET /apollo/v1/radio/stream   — SoundCloud stream resolver (WP fallback)
 */
class RadioController extends WP_REST_Controller {

    protected $namespace = 'apollo/v1';
    protected $rest_base = 'radio';

    private const BLOCK_HOURS = [ 0, 3, 6, 9, 12, 15, 18, 21 ];

    public function register_routes(): void {

        // GET /radio/status
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/status', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_status' ],
                'permission_callback' => '__return_true',
            ],
        ] );

        // GET /radio/now
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/now', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_now' ],
                'permission_callback' => '__return_true',
            ],
        ] );

        // GET /radio/playlist
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/playlist', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_playlist' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'block' => [
                        'type'              => 'string',
                        'default'           => 'auto',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ] );

        // GET /radio/stream
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/stream', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_stream' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'url' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                        'validate_callback' => function ( $value ) {
                            return (bool) filter_var( $value, FILTER_VALIDATE_URL )
                                && str_contains( $value, 'soundcloud.com' );
                        },
                    ],
                ],
            ],
        ] );
    }

    /* ── GET /radio/status ───────────────────────────────────────────── */
    public function get_status( WP_REST_Request $request ): WP_REST_Response {
        $proxy_url = get_option( 'apollo_radio_sc_proxy_url', 'https://apradio.pages.dev/sc-proxy' );
        $cdn_url   = get_option( 'apollo_radio_json_cdn', 'https://assets.apollo.rio.br/radio/json/' );

        return new WP_REST_Response( [
            'version'   => APOLLO_RADIO_VERSION,
            'proxy_url' => $proxy_url,
            'cdn_url'   => $cdn_url,
            'blocks'    => self::BLOCK_HOURS,
            'current'   => $this->current_block_hour(),
        ], 200 );
    }

    /* ── GET /radio/now ──────────────────────────────────────────────── */
    public function get_now( WP_REST_Request $request ): WP_REST_Response {
        $block_hour = $this->current_block_hour();
        $file       = 'radio.' . str_pad( (string) $block_hour, 2, '0', STR_PAD_LEFT ) . '.json';
        $elapsed    = $this->block_elapsed( $block_hour );

        return new WP_REST_Response( [
            'block'   => $block_hour,
            'file'    => $file,
            'elapsed' => round( $elapsed, 2 ),
        ], 200 );
    }

    /* ── GET /radio/playlist ─────────────────────────────────────────── */
    public function get_playlist( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $block_param = $request->get_param( 'block' );

        if ( $block_param === 'auto' || $block_param === null ) {
            $block_hour = $this->current_block_hour();
        } else {
            $block_hour = absint( $block_param );
            if ( ! in_array( $block_hour, self::BLOCK_HOURS, true ) ) {
                return new WP_Error( 'invalid_block', 'Block inválido. Use: 0,3,6,9,12,15,18,21', [ 'status' => 400 ] );
            }
        }

        $file = 'radio.' . str_pad( (string) $block_hour, 2, '0', STR_PAD_LEFT ) . '.json';

        // Check transient cache first (30 min).
        $cache_key = 'apollo_radio_playlist_' . $block_hour;
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) {
            return new WP_REST_Response( $cached, 200 );
        }

        // Fetch from CDN.
        $cdn_url  = trailingslashit( get_option( 'apollo_radio_json_cdn', 'https://assets.apollo.rio.br/radio/json/' ) );
        $response = wp_remote_get( $cdn_url . $file, [
            'timeout' => 10,
            'headers' => [ 'Accept' => 'application/json' ],
        ] );

        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            if ( is_array( $data ) && count( $data ) > 0 ) {
                set_transient( $cache_key, $data, 30 * MINUTE_IN_SECONDS );
                return new WP_REST_Response( $data, 200 );
            }
        }

        // WP uploads fallback.
        $fallback = get_option( 'apollo_radio_json_fallback', '0' );
        if ( $fallback === '1' ) {
            $upload_dir = wp_upload_dir();
            $local_path = $upload_dir['basedir'] . '/apollo-radio/' . $file;
            if ( file_exists( $local_path ) ) {
                $local_data = json_decode( file_get_contents( $local_path ), true );
                if ( is_array( $local_data ) && count( $local_data ) > 0 ) {
                    set_transient( $cache_key, $local_data, 30 * MINUTE_IN_SECONDS );
                    return new WP_REST_Response( $local_data, 200 );
                }
            }
        }

        return new WP_Error( 'playlist_unavailable', 'Playlist indisponível', [ 'status' => 502 ] );
    }

    /* ── GET /radio/stream ───────────────────────────────────────────── */
    public function get_stream( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $url = $request->get_param( 'url' );

        // Validate: must be a soundcloud.com URL to prevent SSRF.
        if ( empty( $url ) || ! preg_match( '/^https?:\/\/(www\.)?soundcloud\.com\//i', $url ) ) {
            return new WP_Error( 'invalid_url', 'URL inválida. Apenas links SoundCloud são suportados.', [ 'status' => 400 ] );
        }
        $url = esc_url_raw( $url );

        // Rate limit: max 60 calls per hour per IP.
        $ip       = $this->get_client_ip();
        $rate_key = 'apollo_radio_rate_' . md5( $ip ) . '_' . gmdate( 'YmdH' );
        $count    = (int) get_transient( $rate_key );
        if ( $count >= 60 ) {
            return new WP_Error( 'rate_limited', 'Taxa de requisições excedida. Tente novamente em breve.', [ 'status' => 429 ] );
        }
        set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );

        // Check cache first (15 min).
        $cache_key = 'apollo_radio_stream_' . md5( $url );
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) {
            return new WP_REST_Response( [ 'stream_url' => $cached ], 200 );
        }

        try {
            $proxy    = new SoundCloudProxy();
            $resolved = $proxy->resolve( $url );

            if ( ! empty( $resolved['stream_url'] ) ) {
                set_transient( $cache_key, $resolved['stream_url'], 15 * MINUTE_IN_SECONDS );
                return new WP_REST_Response( [ 'stream_url' => $resolved['stream_url'] ], 200 );
            }

            return new WP_Error( 'no_stream', 'Stream não encontrado', [ 'status' => 404 ] );
        } catch ( \Exception $e ) {
            return new WP_Error( 'stream_error', $e->getMessage(), [ 'status' => 502 ] );
        }
    }

    /* ── Helpers ─────────────────────────────────────────────────────── */

    private function current_block_hour(): int {
        $hour = (int) wp_date( 'G' );
        $bh   = 0;
        foreach ( array_reverse( self::BLOCK_HOURS ) as $h ) {
            if ( $h <= $hour ) {
                $bh = $h;
                break;
            }
        }
        return $bh;
    }

    private function block_elapsed( int $block_hour ): float {
        // Use WP local timezone components to stay consistent with current_block_hour()
        // which reads the clock via wp_date('G') — both in WP-configured timezone.
        $hours   = (int) wp_date( 'G' );
        $minutes = (int) wp_date( 'i' );
        $seconds = (int) wp_date( 's' );
        $elapsed = ( $hours - $block_hour ) * 3600 + $minutes * 60 + $seconds;
        return max( 0, (float) $elapsed );
    }

    private function get_client_ip(): string {
        $headers = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ];
        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                // X-Forwarded-For may contain multiple IPs.
                if ( str_contains( $ip, ',' ) ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        return '127.0.0.1';
    }
}
