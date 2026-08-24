<?php

namespace Apollo\Radio\Service;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SoundCloud proxy service — WP-side fallback for when the Cloudflare Worker is unreachable.
 *
 * Flow: oEmbed → extract track ID → api-v2 → progressive MP3 stream URL.
 * This never exposes SC credentials to the client. The client_id is stored server-side.
 */
class SoundCloudProxy {

    private string $client_id;

    public function __construct() {
        $this->client_id = get_option( 'apollo_radio_sc_client_id', '' );
    }

    /**
     * Resolve a SoundCloud permalink to a direct MP3 stream URL.
     *
     * @param string $permalink SoundCloud track URL.
     * @return array{ stream_url: string, title?: string, duration?: int }
     * @throws \Exception On resolution failure.
     */
    public function resolve( string $permalink ): array {
        if ( empty( $this->client_id ) ) {
            throw new \Exception( 'SoundCloud client_id não configurado. Acesse Configurações → Apollo Radio.' );
        }

        // Step 1: oEmbed to get track ID.
        $oembed_url = 'https://soundcloud.com/oembed?format=json&url=' . urlencode( $permalink );
        $oembed_res = wp_remote_get( $oembed_url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $oembed_res ) ) {
            throw new \Exception( 'oEmbed falhou: ' . $oembed_res->get_error_message() );
        }

        $oembed_code = wp_remote_retrieve_response_code( $oembed_res );
        if ( $oembed_code !== 200 ) {
            throw new \Exception( 'oEmbed retornou HTTP ' . $oembed_code );
        }

        $oembed_body = json_decode( wp_remote_retrieve_body( $oembed_res ), true );
        if ( empty( $oembed_body['html'] ) ) {
            throw new \Exception( 'oEmbed sem campo html' );
        }

        // Extract track ID from the iframe embed HTML.
        if ( ! preg_match( '/tracks%2F(\d+)/', $oembed_body['html'], $matches ) ) {
            throw new \Exception( 'Track ID não encontrado no oEmbed' );
        }
        $track_id = $matches[1];

        // Step 2: Fetch track data from api-v2.
        $api_url = 'https://api-v2.soundcloud.com/tracks/' . $track_id
                 . '?client_id=' . urlencode( $this->client_id );

        $api_res = wp_remote_get( $api_url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $api_res ) ) {
            throw new \Exception( 'API fetch falhou: ' . $api_res->get_error_message() );
        }

        $api_code = wp_remote_retrieve_response_code( $api_res );
        if ( $api_code !== 200 ) {
            throw new \Exception( 'API retornou HTTP ' . $api_code );
        }

        $track_data = json_decode( wp_remote_retrieve_body( $api_res ), true );

        // Step 3: Find progressive MP3 transcoding.
        $stream = null;
        if ( ! empty( $track_data['media']['transcodings'] ) ) {
            foreach ( $track_data['media']['transcodings'] as $transcoding ) {
                if (
                    isset( $transcoding['format']['protocol'] )
                    && $transcoding['format']['protocol'] === 'progressive'
                ) {
                    $stream = $transcoding;
                    break;
                }
            }
        }

        if ( ! $stream || empty( $stream['url'] ) ) {
            throw new \Exception( 'Sem stream progressivo disponível' );
        }

        // Step 4: Resolve the transcoding URL to the actual MP3 URL.
        $separator  = str_contains( $stream['url'], '?' ) ? '&' : '?';
        $stream_url = $stream['url'] . $separator . 'client_id=' . urlencode( $this->client_id );

        $stream_res = wp_remote_get( $stream_url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $stream_res ) ) {
            throw new \Exception( 'Stream URL fetch falhou: ' . $stream_res->get_error_message() );
        }

        $stream_code = wp_remote_retrieve_response_code( $stream_res );
        if ( $stream_code !== 200 ) {
            throw new \Exception( 'Stream URL retornou HTTP ' . $stream_code );
        }

        $stream_data = json_decode( wp_remote_retrieve_body( $stream_res ), true );
        if ( empty( $stream_data['url'] ) ) {
            throw new \Exception( 'Stream URL vazia na resposta' );
        }

        return [
            'stream_url' => $stream_data['url'],
            'title'      => $track_data['title'] ?? '',
            'duration'   => isset( $track_data['duration'] ) ? (int) ( $track_data['duration'] / 1000 ) : 0,
        ];
    }
}
