<?php
/**
 * Track listen resolver — unified playback contract for Out Now cards.
 *
 * Prioritises SoundCloud (_track_url_soundcloud) and builds the fallback chain:
 *   1. SC Widget API (hidden iframe)
 *   2. Direct MP3 via apollo-radio REST proxy
 *
 * @package Apollo\DJs
 * @since   1.1.3
 * @see     _inventory/PLAN-apollo-soundcloud.md S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Preview window start — 20% into track (user requirement for /casa). */
const APOLLO_TRACK_LISTEN_START_PCT = 20;

/** Preview window end (legacy / non-hold players). */
const APOLLO_TRACK_LISTEN_END_PCT = 65;

/** Preview play length after the 20% seek — Out Now cards. */
const APOLLO_TRACK_LISTEN_HOLD_SECONDS = 60;

if ( ! function_exists( 'apollo_track_listen_none' ) ) {
	/**
	 * Empty listen contract.
	 *
	 * @return array<string,mixed>
	 */
	function apollo_track_listen_none(): array {
		return array(
			'can_play'    => false,
			'provider'    => '',
			'mode'        => '',
			'src'         => '',
			'canonical'   => '',
			'embed_url'   => '',
			'start_pct'   => APOLLO_TRACK_LISTEN_START_PCT,
			'end_pct'     => APOLLO_TRACK_LISTEN_END_PCT,
			'start'       => 0,
			'seconds'     => APOLLO_TRACK_PREVIEW_DEFAULT_SECONDS,
			'chain'       => array(),
		);
	}
}

if ( ! function_exists( 'apollo_track_url_is_soundcloud' ) ) {
	/**
	 * Detect a SoundCloud permalink without requiring apollo-soundcloud.
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	function apollo_track_url_is_soundcloud( string $url ): bool {
		$url = trim( $url );
		if ( '' === $url ) {
			return false;
		}
		if ( function_exists( 'apollo_soundcloud_is' ) ) {
			return apollo_soundcloud_is( $url );
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return str_contains( $host, 'soundcloud.com' ) || str_contains( $host, 'snd.sc' );
	}
}

if ( ! function_exists( 'apollo_track_soundcloud_embed' ) ) {
	/**
	 * Resolve a SoundCloud permalink to canonical + widget embed URL.
	 *
	 * Prefers apollo-soundcloud; falls back to the Widget player URL so /casa
	 * preview still works when that plugin is inactive.
	 *
	 * @param string $url SoundCloud permalink.
	 * @return array{kind:string,canonical:string,embed_url:string}
	 */
	function apollo_track_soundcloud_embed( string $url ): array {
		$none = array(
			'kind'      => '',
			'canonical' => '',
			'embed_url' => '',
		);
		$url = trim( $url );
		if ( '' === $url || ! apollo_track_url_is_soundcloud( $url ) ) {
			return $none;
		}

		if ( function_exists( 'apollo_soundcloud_resolve' ) ) {
			$r = apollo_soundcloud_resolve( $url );
			if ( '' !== ( $r['embed_url'] ?? '' ) ) {
				return array(
					'kind'      => (string) ( $r['kind'] ?? 'track' ),
					'canonical' => (string) ( $r['canonical'] ?? $url ),
					'embed_url' => (string) $r['embed_url'],
				);
			}
		}

		$canonical = esc_url_raw( $url );
		$pairs     = array(
			'url=' . rawurlencode( $canonical ),
			'auto_play=false',
			'hide_related=true',
			'show_comments=false',
			'show_user=false',
			'show_reposts=false',
			'show_teaser=false',
			'visual=false',
			'buying=false',
			'sharing=false',
			'download=false',
			'show_playcount=false',
			'show_artwork=false',
			'single_active=false',
		);

		return array(
			'kind'      => 'track',
			'canonical' => $canonical,
			'embed_url' => 'https://w.soundcloud.com/player/?' . implode( '&', $pairs ),
		);
	}
}

if ( ! function_exists( 'apollo_track_listen_from_soundcloud' ) ) {
	/**
	 * Build listen contract + fallback chain for a SoundCloud permalink.
	 *
	 * @param string $permalink SoundCloud URL.
	 * @param int    $start     Explicit start offset (seconds) from meta.
	 * @param int    $seconds   Fixed preview length fallback.
	 * @return array<string,mixed>
	 */
	function apollo_track_listen_from_soundcloud( string $permalink, int $start = 0, int $seconds = 0 ): array {
		$none = apollo_track_listen_none();
		$r    = apollo_track_soundcloud_embed( $permalink );
		if ( '' === $r['embed_url'] ) {
			return $none;
		}

		$seconds   = $seconds > 0 ? $seconds : APOLLO_TRACK_PREVIEW_DEFAULT_SECONDS;
		$canonical = (string) $r['canonical'];
		$embed     = (string) $r['embed_url'];

		$chain = array(
			array(
				'mode'      => 'widget',
				'provider'  => 'soundcloud',
				'canonical' => $canonical,
				'embed_url' => $embed,
				'start_pct' => APOLLO_TRACK_LISTEN_START_PCT,
				'hold'      => APOLLO_TRACK_LISTEN_HOLD_SECONDS,
				'seconds'   => APOLLO_TRACK_LISTEN_HOLD_SECONDS,
			),
			array(
				'mode'      => 'audio',
				'provider'  => 'soundcloud_stream',
				'canonical' => $canonical,
				'start_pct' => APOLLO_TRACK_LISTEN_START_PCT,
				'start'     => $start,
				'seconds'   => APOLLO_TRACK_LISTEN_HOLD_SECONDS,
			),
		);

		return array(
			'can_play'    => true,
			'provider'    => 'soundcloud',
			'mode'        => 'widget',
			'src'         => $embed,
			'canonical'   => $canonical,
			'embed_url'   => $embed,
			'start_pct'   => APOLLO_TRACK_LISTEN_START_PCT,
			'end_pct'     => APOLLO_TRACK_LISTEN_END_PCT,
			'start'       => $start,
			'seconds'     => APOLLO_TRACK_LISTEN_HOLD_SECONDS,
			'chain'       => $chain,
		);
	}
}

if ( ! function_exists( 'apollo_track_listen' ) ) {
	/**
	 * Resolve everything a track card needs to play in-page.
	 *
	 * Source priority:
	 *   1. _track_url_soundcloud
	 *   2. _track_preview_url (catbox / archive / youtube / direct / soundcloud)
	 *   3. _track_url_download when extension is audio
	 *
	 * @param int $track_id Track post ID.
	 * @return array<string,mixed>
	 */
	function apollo_track_listen( int $track_id ): array {
		if ( $track_id <= 0 || 'track' !== get_post_type( $track_id ) ) {
			return apollo_track_listen_none();
		}

		$start   = max( 0, (int) get_post_meta( $track_id, '_track_preview_start', true ) );
		$seconds = (int) get_post_meta( $track_id, '_track_preview_seconds', true );
		$seconds = $seconds > 0 ? $seconds : APOLLO_TRACK_PREVIEW_DEFAULT_SECONDS;

		$sc_url = trim( (string) get_post_meta( $track_id, '_track_url_soundcloud', true ) );
		if ( '' !== $sc_url && apollo_track_url_is_soundcloud( $sc_url ) ) {
			$listen = apollo_track_listen_from_soundcloud( $sc_url, $start, $seconds );
			if ( ! empty( $listen['can_play'] ) ) {
				return $listen;
			}
		}

		if ( function_exists( 'apollo_track_preview' ) ) {
			$preview = apollo_track_preview( $track_id );
			if ( ! empty( $preview['provider'] ) && ! empty( $preview['src'] ) ) {
				$mode     = (string) ( $preview['mode'] ?? 'audio' );
				$provider = (string) $preview['provider'];

				if ( 'soundcloud' === $provider && 'widget' === $mode ) {
					$canonical = trim( (string) get_post_meta( $track_id, '_track_preview_url', true ) );
					return apollo_track_listen_from_soundcloud( $canonical, $start, $seconds );
				}

				// Native audio providers — single chain step.
				if ( in_array( $mode, array( 'audio' ), true ) ) {
					return array(
						'can_play'    => true,
						'provider'    => $provider,
						'mode'        => 'audio',
						'src'         => (string) $preview['src'],
						'canonical'   => '',
						'embed_url'   => '',
						'start_pct'   => APOLLO_TRACK_LISTEN_START_PCT,
						'end_pct'     => APOLLO_TRACK_LISTEN_END_PCT,
						'start'       => (int) ( $preview['start'] ?? $start ),
						'seconds'     => (int) ( $preview['seconds'] ?? $seconds ),
						'chain'       => array(
							array(
								'mode'      => 'audio',
								'provider'  => $provider,
								'src'       => (string) $preview['src'],
								'start'     => (int) ( $preview['start'] ?? $start ),
								'seconds'   => (int) ( $preview['seconds'] ?? $seconds ),
								'start_pct' => APOLLO_TRACK_LISTEN_START_PCT,
								'end_pct'   => APOLLO_TRACK_LISTEN_END_PCT,
							),
						),
					);
				}
			}
		}

		$download = trim( (string) get_post_meta( $track_id, '_track_url_download', true ) );
		if ( '' !== $download ) {
			$path = strtolower( (string) wp_parse_url( $download, PHP_URL_PATH ) );
			if ( preg_match( '/\.(mp3|ogg|oga|m4a|wav)$/', $path ) ) {
				return array(
					'can_play'    => true,
					'provider'    => 'direct',
					'mode'        => 'audio',
					'src'         => esc_url_raw( $download ),
					'canonical'   => '',
					'embed_url'   => '',
					'start_pct'   => APOLLO_TRACK_LISTEN_START_PCT,
					'end_pct'     => APOLLO_TRACK_LISTEN_END_PCT,
					'start'       => $start,
					'seconds'     => $seconds,
					'chain'       => array(
						array(
							'mode'      => 'audio',
							'provider'  => 'direct',
							'src'       => esc_url_raw( $download ),
							'start'     => $start,
							'seconds'   => $seconds,
							'start_pct' => APOLLO_TRACK_LISTEN_START_PCT,
							'end_pct'   => APOLLO_TRACK_LISTEN_END_PCT,
						),
					),
				);
			}
		}

		return apollo_track_listen_none();
	}
}

if ( ! function_exists( 'apollo_track_enqueue_listen_assets' ) ) {
	/**
	 * Register listen-player assets for the Out Now rail (blank-canvas safe).
	 *
	 * Stores config in $GLOBALS['apollo_casa_listen_assets'] for page-home.php
	 * to print script tags — /casa does not call wp_footer().
	 *
	 * @param int[] $track_ids Track post IDs on the rail.
	 * @return array<string,mixed> Asset config for footer printing.
	 */
	function apollo_track_enqueue_listen_assets( array $track_ids ): array {
		$config = array(
			'can_play'   => false,
			'needs_sc'   => false,
			'player_js'  => '',
			'sc_js'      => '',
			'sc_css'     => '',
			'restStream' => rest_url( 'apollo/v1/radio/stream' ),
			'targetVol'  => 0.2,
			'fadeInMs'   => 420,
			'fadeOutMs'  => 280,
		);

		foreach ( $track_ids as $track_id ) {
			$listen = apollo_track_listen( (int) $track_id );
			if ( empty( $listen['can_play'] ) ) {
				continue;
			}
			$config['can_play'] = true;
			if ( 'soundcloud' === ( $listen['provider'] ?? '' ) ) {
				$config['needs_sc'] = true;
			}
		}

		if ( defined( 'APOLLO_TEMPLATES_URL' ) && defined( 'APOLLO_TEMPLATES_VERSION' ) ) {
			$config['player_js'] = APOLLO_TEMPLATES_URL . 'assets/js/apollo-track-player.js?ver=' . APOLLO_TEMPLATES_VERSION;
		}

		if ( defined( 'APOLLO_SC_URL' ) && defined( 'APOLLO_SC_VERSION' ) ) {
			$config['needs_sc'] = true;
			$config['sc_js']    = APOLLO_SC_URL . 'assets/js/apollo-sc.js?ver=' . APOLLO_SC_VERSION;
			$config['sc_css']   = APOLLO_SC_URL . 'assets/css/apollo-sc.css?ver=' . APOLLO_SC_VERSION;
		}

		$GLOBALS['apollo_casa_listen_assets'] = $config;
		return $config;
	}
}
