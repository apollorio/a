<?php
/**
 * SoundCloud URL resolution — THE one builder.
 *
 * The widget-URL string was assembled by hand in four places before this file:
 *
 *   apollo-djs/templates/single-dj.php:76
 *   apollo-users/includes/functions.php:362
 *   apollo-djs/assets/js/dj-card-single.js:138
 *   apollo-djs/styles/…/single/scripts.php:154
 *
 * with different colours, different auto_play values and different toggles for
 * hide_related / show_comments / show_user. A track therefore looked and behaved
 * differently depending on which screen you found it on.
 *
 * Pure functions. No output, no writes, no network — resolution is string work.
 * Metadata that needs the network lives in meta.php.
 *
 * @package Apollo\SoundCloud
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_soundcloud_is' ) ) {
	/**
	 * Is this a SoundCloud URL we can play?
	 *
	 * @param string $url Candidate.
	 * @return bool
	 */
	function apollo_soundcloud_is( string $url ): bool {
		$host = strtolower( (string) wp_parse_url( trim( $url ), PHP_URL_HOST ) );
		if ( '' === $host ) {
			return false;
		}
		return str_contains( $host, 'soundcloud.com' ) || str_contains( $host, 'snd.sc' );
	}
}

if ( ! function_exists( 'apollo_soundcloud_resolve' ) ) {
	/**
	 * Resolve any SoundCloud URL into everything a player needs.
	 *
	 * FAIL-CLOSED. Anything unrecognised returns kind '' and the renderer emits
	 * nothing — never a broken iframe, never a widget pointed at a 404.
	 *
	 * Handles:
	 *   soundcloud.com/{artist}/{track}          a track
	 *   soundcloud.com/{artist}/sets/{playlist}  a set
	 *   soundcloud.com/{artist}                  a profile
	 *   api.soundcloud.com/tracks/{id}           an API resource URL
	 *   on.soundcloud.com / snd.sc               short links (passed through —
	 *                                            the widget resolves them itself)
	 *
	 * @param string              $url  SoundCloud URL.
	 * @param array<string,mixed> $args auto_play, visual, color.
	 * @return array{kind:string,canonical:string,embed_url:string,is_set:bool}
	 */
	function apollo_soundcloud_resolve( string $url, array $args = array() ): array {
		$none = array(
			'kind'      => '',
			'canonical' => '',
			'embed_url' => '',
			'is_set'    => false,
		);

		$url = trim( $url );
		if ( '' === $url || ! apollo_soundcloud_is( $url ) ) {
			return $none;
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );

		$kind   = '';
		$is_set = false;

		if ( str_contains( $host, 'api.soundcloud.com' ) ) {
			// api.soundcloud.com/tracks/123 — already a resource URL.
			$kind = str_contains( $path, 'tracks' ) ? 'track'
				: ( str_contains( $path, 'playlists' ) ? 'set' : 'user' );
			$is_set = ( 'set' === $kind );
		} elseif ( str_contains( $host, 'snd.sc' ) || str_contains( $host, 'on.soundcloud.com' ) ) {
			/*
			 * Short link. We deliberately do NOT follow it server-side: that
			 * would be a blocking HTTP request on render, on a page that may
			 * show fifteen of them. The widget resolves short links itself.
			 */
			$kind = 'track';
		} else {
			$segments = array_values( array_filter( explode( '/', $path ) ) );
			if ( count( $segments ) >= 2 && 'sets' === $segments[1] ) {
				$kind   = 'set';
				$is_set = true;
			} elseif ( count( $segments ) >= 2 ) {
				$kind = 'track';
			} elseif ( 1 === count( $segments ) ) {
				$kind = 'user';
			}
		}

		if ( '' === $kind ) {
			return $none;
		}

		$args = wp_parse_args(
			$args,
			array(
				'auto_play' => false,
				'visual'    => false,
				'color'     => 'ff5c00',
			)
		);

		/*
		 * ONE canonical parameter set, replacing four divergent ones.
		 *
		 * Everything that draws SoundCloud's own chrome is OFF: this iframe is a
		 * TRANSPORT, hidden behind Apollo's player. show_comments, show_user,
		 * show_reposts, show_teaser and hide_related all existed with different
		 * values across the four old builders, which is why the same track
		 * looked different on /dj/{id} than on /perfil.
		 *
		 * A playlist keeps show_user on, because a set legitimately needs its
		 * own track list to be navigable.
		 */
		$params = array(
			'url'             => $url,
			'auto_play'       => $args['auto_play'] ? 'true' : 'false',
			'hide_related'    => 'true',
			'show_comments'   => 'false',
			'show_user'       => $is_set ? 'true' : 'false',
			'show_reposts'    => 'false',
			'show_teaser'     => 'false',
			'visual'          => $args['visual'] ? 'true' : 'false',
			'color'           => '%23' . ltrim( (string) $args['color'], '#' ),
			'buying'          => 'false',
			'sharing'         => 'false',
			'download'        => 'false',
			'show_playcount'  => 'false',
			'show_artwork'    => $args['visual'] ? 'true' : 'false',
			'single_active'   => 'false',
		);

		// build_query would double-encode the pre-encoded colour.
		$pairs = array();
		foreach ( $params as $k => $v ) {
			$pairs[] = $k . '=' . ( 'url' === $k ? rawurlencode( (string) $v ) : $v );
		}

		return array(
			'kind'      => $kind,
			'canonical' => esc_url_raw( $url ),
			'embed_url' => 'https://w.soundcloud.com/player/?' . implode( '&', $pairs ),
			'is_set'    => $is_set,
		);
	}
}
