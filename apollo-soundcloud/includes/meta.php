<?php
/**
 * SoundCloud metadata via oEmbed — no credentials required.
 *
 * oEmbed is the one metadata surface SoundCloud left open. It takes any public
 * track URL and returns title, author and artwork with no client id, no OAuth
 * and no application form. That is what lets Apollo's own player show a real
 * title instead of "SoundCloud track" while the HTTP API stays closed.
 *
 * ALWAYS CACHED, ALWAYS FAIL-CLOSED. A rail of fifteen cards must never make
 * fifteen blocking HTTP calls on render, and a SoundCloud outage must degrade to
 * a player with no title rather than a slow page or a fatal.
 *
 * @package Apollo\SoundCloud
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_soundcloud_meta' ) ) {
	/**
	 * Title, author and artwork for a SoundCloud URL.
	 *
	 * @param string $url   SoundCloud URL.
	 * @param bool   $fetch Allow a network call on a cache miss. Pass false on
	 *                      hot render paths that must never block.
	 * @return array{title:string,author:string,artwork:string,found:bool}
	 */
	function apollo_soundcloud_meta( string $url, bool $fetch = true ): array {
		$empty = array(
			'title'   => '',
			'author'  => '',
			'artwork' => '',
			'found'   => false,
		);

		if ( ! apollo_soundcloud_is( $url ) ) {
			return $empty;
		}

		$key    = 'apollo_sc_oe_' . md5( $url );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		if ( ! $fetch ) {
			return $empty;
		}

		$res = wp_safe_remote_get(
			add_query_arg(
				array(
					'format' => 'json',
					'url'    => rawurlencode( $url ),
				),
				'https://soundcloud.com/oembed'
			),
			array(
				'timeout' => 5,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			/*
			 * Cache the FAILURE, briefly. Without this a dead or rate-limited
			 * endpoint is retried on every render of every card — turning one
			 * outage into a page that takes 15×5s to build.
			 */
			set_transient( $key, $empty, 10 * MINUTE_IN_SECONDS );
			return $empty;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $body ) ) {
			set_transient( $key, $empty, 10 * MINUTE_IN_SECONDS );
			return $empty;
		}

		$out = array(
			'title'   => sanitize_text_field( (string) ( $body['title'] ?? '' ) ),
			'author'  => sanitize_text_field( (string) ( $body['author_name'] ?? '' ) ),
			'artwork' => esc_url_raw( (string) ( $body['thumbnail_url'] ?? '' ) ),
			'found'   => true,
		);

		set_transient( $key, $out, 12 * HOUR_IN_SECONDS );
		return $out;
	}
}
