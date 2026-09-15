<?php
/**
 * Track releases — query and derivation.
 *
 * Pure functions. No rendering, no writes, no side effects. Everything that
 * displays a release reads through here, which is what stops the five "Out Now"
 * implementations from becoming six.
 *
 * @package Apollo\DJs
 * @since   1.0.8
 * @see     _inventory/PLAN-track-releases.md
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Default preview length when a track does not set its own. */
const APOLLO_TRACK_PREVIEW_DEFAULT_SECONDS = 30;

if ( ! function_exists( 'apollo_track_query' ) ) {
	/**
	 * THE query for released tracks. One place, one shape.
	 *
	 * REPLACES AN UNBOUNDED N+1. apollo_get_latest_dj_tracks() used to run
	 * get_posts( post_type=dj, -1, fields=ids ) and then one get_post_meta()
	 * per published DJ, uncached, on every /casa render — because tracks lived
	 * in meta on the dj post. They are their own CPT now, so this is a single
	 * indexed query with a real LIMIT.
	 *
	 * Ordered by _track_release_date descending. Tracks with no release date
	 * sort last rather than vanishing: an undated release is still a release,
	 * and silently dropping rows is how "my track isn't showing" becomes a
	 * support thread nobody can reproduce.
	 *
	 * @param array<string,mixed> $args posts_per_page, dj_id, post_status.
	 * @return int[] Track post IDs, newest first.
	 */
	function apollo_track_query( array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'posts_per_page' => 15,
				'dj_id'          => 0,
				'post_status'    => 'publish',
			)
		);

		$q = array(
			'post_type'              => 'track',
			'post_status'            => $args['post_status'],
			'posts_per_page'         => (int) $args['posts_per_page'],
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			/*
			 * Undated releases must still appear. A plain meta_value ordering on
			 * _track_release_date would drop every row lacking the key, so the
			 * clause is split: dated rows sort by date, undated rows fall in
			 * behind them by post date.
			 */
			'meta_query'             => array(
				'relation' => 'OR',
				'dated'    => array(
					'key'     => '_track_release_date',
					'compare' => 'EXISTS',
				),
				'undated'  => array(
					'key'     => '_track_release_date',
					'compare' => 'NOT EXISTS',
				),
			),
			'orderby'                => array(
				'dated' => 'DESC',
				'date'  => 'DESC',
			),
		);

		if ( $args['dj_id'] > 0 ) {
			/*
			 * Meta LIKE on a serialised array is the standard WP idiom for
			 * "post id appears in this array meta" and is what _event_dj_ids
			 * consumers already use. The delimiters matter: without them, dj 4
			 * matches dj 42.
			 */
			$q['meta_query'] = array(
				array(
					'key'     => '_track_dj_ids',
					'value'   => sprintf( ':%d;', (int) $args['dj_id'] ),
					'compare' => 'LIKE',
				),
			);
			$q['orderby']    = array( 'date' => 'DESC' );
		}

		/**
		 * Filter the track query args.
		 *
		 * @param array<string,mixed> $q    WP_Query args.
		 * @param array<string,mixed> $args Caller args.
		 */
		$q = (array) apply_filters( 'apollo_track_query_args', $q, $args );

		return array_map( 'intval', ( new WP_Query( $q ) )->posts );
	}
}

if ( ! function_exists( 'apollo_track_credits' ) ) {
	/**
	 * Everyone credited on a release, real and ghost, in one ordered list.
	 *
	 * A release credits artists who may not have a dj post — a guest vocalist,
	 * a label mate, a remixer who is not on the roster. Real DJs come from
	 * _track_dj_ids and carry a permalink; ghosts come from
	 * _track_ghost_artists and are names only.
	 *
	 * DJs first, then ghosts, each in declared order.
	 *
	 * A ghost whose name later matches a published dj post is resolved to that
	 * post automatically — no stored data changes, no migration. That is the
	 * whole reason ghosts are names rather than stub posts.
	 *
	 * @param int $track_id Track post ID.
	 * @return list<array{type:string,id:int,name:string,url:string}>
	 */
	function apollo_track_credits( int $track_id ): array {
		if ( $track_id <= 0 ) {
			return array();
		}

		$out  = array();
		$seen = array();

		$dj_ids = get_post_meta( $track_id, '_track_dj_ids', true );
		foreach ( (array) ( is_array( $dj_ids ) ? $dj_ids : array() ) as $dj_id ) {
			$dj_id = (int) $dj_id;
			if ( $dj_id <= 0 || 'publish' !== get_post_status( $dj_id ) ) {
				continue;
			}
			$name = (string) get_the_title( $dj_id );
			$out[] = array(
				'type' => 'dj',
				'id'   => $dj_id,
				'name' => $name,
				'url'  => (string) get_permalink( $dj_id ),
			);
			$seen[ mb_strtolower( $name ) ] = true;
		}

		$ghosts = (string) get_post_meta( $track_id, '_track_ghost_artists', true );
		foreach ( array_filter( array_map( 'trim', explode( ',', $ghosts ) ) ) as $name ) {
			$key = mb_strtolower( $name );
			if ( isset( $seen[ $key ] ) ) {
				continue; // Already credited as a real DJ — do not print twice.
			}
			$seen[ $key ] = true;

			/*
			 * Late resolution: if a dj post with this exact title exists now,
			 * the ghost becomes a link. Nothing is written back — the ghost stays
			 * a ghost in storage and simply renders richer.
			 */
			$match = get_page_by_title( $name, OBJECT, 'dj' );
			if ( $match instanceof WP_Post && 'publish' === $match->post_status ) {
				$out[] = array(
					'type' => 'dj',
					'id'   => (int) $match->ID,
					'name' => $name,
					'url'  => (string) get_permalink( $match ),
				);
				continue;
			}

			$out[] = array(
				'type' => 'ghost',
				'id'   => 0,
				'name' => $name,
				'url'  => '',
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'apollo_track_credits_line' ) ) {
	/**
	 * Credits as a display string.
	 *
	 * Falls back to the stored _track_artists when nothing structured exists,
	 * so migrated rows and hand-typed credits keep rendering.
	 *
	 * @param int $track_id Track post ID.
	 * @return string
	 */
	function apollo_track_credits_line( int $track_id ): string {
		$names = array_column( apollo_track_credits( $track_id ), 'name' );
		if ( $names ) {
			return implode( ', ', $names );
		}
		return (string) get_post_meta( $track_id, '_track_artists', true );
	}
}

if ( ! function_exists( 'apollo_track_soundcloud_artwork' ) ) {
	/**
	 * Cached SoundCloud oEmbed thumbnail — used when apollo-soundcloud is off.
	 *
	 * Budget-capped per request so /casa does not block on 15 HTTP calls.
	 *
	 * @param string $sc_url SoundCloud permalink.
	 * @return string Image URL or ''.
	 */
	function apollo_track_soundcloud_artwork( string $sc_url ): string {
		static $budget = 4;
		$sc_url        = trim( $sc_url );
		if ( '' === $sc_url ) {
			return '';
		}

		$key    = 'apollo_sc_art_' . md5( $sc_url );
		$cached = get_transient( $key );
		if ( is_string( $cached ) ) {
			return $cached;
		}

		if ( $budget <= 0 ) {
			return '';
		}
		--$budget;

		$art  = '';
		$resp = wp_remote_get(
			'https://soundcloud.com/oembed?format=json&url=' . rawurlencode( $sc_url ),
			array(
				'timeout'    => 2,
				'user-agent' => 'ApolloRio/track-cover',
			)
		);
		if ( ! is_wp_error( $resp ) && 200 === (int) wp_remote_retrieve_response_code( $resp ) ) {
			$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
			$art  = esc_url_raw( (string) ( $body['thumbnail_url'] ?? '' ) );
		}
		set_transient( $key, $art, DAY_IN_SECONDS );
		return $art;
	}
}

if ( ! function_exists( 'apollo_track_resolve_cover' ) ) {
	/**
	 * Resolve track artwork — featured image, meta URL, oEmbed artwork, DJ fallback.
	 *
	 * @param int $track_id Track post ID.
	 * @return string Image URL or ''.
	 */
	function apollo_track_resolve_cover( int $track_id ): string {
		$thumb_id = (int) get_post_thumbnail_id( $track_id );
		if ( $thumb_id > 0 ) {
			foreach ( array( 'medium', 'large', 'thumbnail' ) as $size ) {
				$url = (string) wp_get_attachment_image_url( $thumb_id, $size );
				if ( '' !== $url ) {
					return $url;
				}
			}
		}

		$cover = trim( (string) get_post_meta( $track_id, '_track_cover_url', true ) );
		if ( '' !== $cover ) {
			return esc_url_raw( $cover );
		}

		$sc_url = trim( (string) get_post_meta( $track_id, '_track_url_soundcloud', true ) );
		if ( '' === $sc_url ) {
			$sc_url = trim( (string) get_post_meta( $track_id, '_track_preview_url', true ) );
		}
		if ( '' !== $sc_url && function_exists( 'apollo_track_url_is_soundcloud' ) && apollo_track_url_is_soundcloud( $sc_url ) ) {
			$art = '';
			if ( function_exists( 'apollo_soundcloud_meta' ) ) {
				$meta = apollo_soundcloud_meta( $sc_url, true );
				$art  = trim( (string) ( $meta['artwork'] ?? '' ) );
			}
			if ( '' === $art ) {
				$art = apollo_track_soundcloud_artwork( $sc_url );
			}
			if ( '' !== $art ) {
				return esc_url_raw( $art );
			}
		}

		$credits = apollo_track_credits( $track_id );
		$first   = $credits[0]['id'] ?? 0;
		if ( $first && function_exists( 'apollo_dj_get_image' ) ) {
			$dj_cover = (string) apollo_dj_get_image( $first );
			if ( '' !== $dj_cover && ! str_contains( $dj_cover, 'placeholder-dj' ) ) {
				return $dj_cover;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'apollo_track_display_urls' ) ) {
	/**
	 * Platform URLs for the expanded icon row — excludes listen-only duplicates.
	 *
	 * When SoundCloud is only the in-page preview transport (same URL as listen
	 * canonical), do not show a redundant SC icon in the collapsed rail.
	 *
	 * @param int                $track_id Track post ID.
	 * @param array<string,mixed> $listen  Listen contract from apollo_track_listen().
	 * @return array<string,string> spotify|bandcamp|soundcloud|youtube => url
	 */
	function apollo_track_display_urls( int $track_id, array $listen = array() ): array {
		$raw = array(
			'spotify'    => trim( (string) get_post_meta( $track_id, '_track_url_spotify', true ) ),
			'bandcamp'   => trim( (string) get_post_meta( $track_id, '_track_url_bandcamp', true ) ),
			'soundcloud' => trim( (string) get_post_meta( $track_id, '_track_url_soundcloud', true ) ),
			'youtube'    => trim( (string) get_post_meta( $track_id, '_track_url_youtube', true ) ),
		);

		$listen_canonical = trim( (string) ( $listen['canonical'] ?? '' ) );
		$listen_provider  = (string) ( $listen['provider'] ?? '' );

		$out = array();
		foreach ( $raw as $key => $href ) {
			if ( '' === $href ) {
				continue;
			}
			// Drop SC icon when it is the same URL used only for in-page preview.
			if ( 'soundcloud' === $key && 'soundcloud' === $listen_provider && $listen_canonical !== '' ) {
				if ( untrailingslashit( strtolower( $href ) ) === untrailingslashit( strtolower( $listen_canonical ) ) ) {
					continue;
				}
			}
			$out[ $key ] = $href;
		}

		return $out;
	}
}

if ( ! function_exists( 'apollo_track_preview' ) ) {
	/**
	 * Resolve a track's preview source to something a player can mount.
	 *
	 * FAIL-CLOSED. An unrecognised or empty URL returns provider '' and the
	 * caller renders no player — never a broken embed, never an <audio> pointed
	 * at a web page.
	 *
	 * @param int $track_id Track post ID.
	 * @return array{provider:string,src:string,mode:string,start:int,seconds:int}
	 */
	function apollo_track_preview( int $track_id ): array {
		$none = array(
			'provider' => '',
			'src'      => '',
			'mode'     => '',
			'start'    => 0,
			'seconds'  => 0,
		);

		$url = trim( (string) get_post_meta( $track_id, '_track_preview_url', true ) );
		if ( '' === $url ) {
			return $none;
		}

		$start   = max( 0, (int) get_post_meta( $track_id, '_track_preview_start', true ) );
		$seconds = (int) get_post_meta( $track_id, '_track_preview_seconds', true );
		$seconds = $seconds > 0 ? $seconds : APOLLO_TRACK_PREVIEW_DEFAULT_SECONDS;

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( '' === $host ) {
			return $none;
		}

		// ── Catbox — the best case. Direct MP3, no account, no third-party JS.
		if ( str_contains( $host, 'catbox.moe' ) ) {
			return array(
				'provider' => 'catbox',
				'src'      => esc_url_raw( $url ),
				'mode'     => 'audio',
				'start'    => $start,
				'seconds'  => $seconds,
			);
		}

		// ── Internet Archive — permanent and free; direct stream.
		if ( str_contains( $host, 'archive.org' ) ) {
			return array(
				'provider' => 'archive',
				'src'      => esc_url_raw( $url ),
				'mode'     => 'audio',
				'start'    => $start,
				'seconds'  => $seconds,
			);
		}

		// ── YouTube — the practical fallback.
		if ( str_contains( $host, 'youtube.com' ) || str_contains( $host, 'youtu.be' ) ) {
			$id = '';
			if ( str_contains( $host, 'youtu.be' ) ) {
				$id = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
			} else {
				parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $qs );
				$id = (string) ( $qs['v'] ?? '' );
				if ( '' === $id && preg_match( '#/(embed|shorts)/([^/?#]+)#', (string) wp_parse_url( $url, PHP_URL_PATH ), $m ) ) {
					$id = $m[2];
				}
			}
			$id = preg_replace( '/[^A-Za-z0-9_-]/', '', $id );
			if ( '' === $id ) {
				return $none;
			}

			/*
			 * STRICT ambient-style embed: no chrome, no keyboard, no fullscreen.
			 * Card UI is the only control surface.
			 */
			$src = sprintf(
				'https://www.youtube-nocookie.com/embed/%s?start=%d&rel=0&modestbranding=1&controls=0&playsinline=1&disablekb=1&fs=0&iv_load_policy=3&cc_load_policy=0&enablejsapi=1',
				rawurlencode( $id ),
				$start
			);

			return array(
				'provider' => 'youtube',
				'src'      => esc_url_raw( $src ),
				'mode'     => 'iframe',
				'start'    => $start,
				'seconds'  => $seconds,
			);
		}

		// ── SoundCloud — Widget API transport (PLAN S3).
		if ( function_exists( 'apollo_soundcloud_is' ) && apollo_soundcloud_is( $url ) ) {
			$r = function_exists( 'apollo_soundcloud_resolve' )
				? apollo_soundcloud_resolve( $url )
				: array( 'kind' => '', 'embed_url' => '', 'canonical' => '' );
			if ( '' !== ( $r['kind'] ?? '' ) && '' !== ( $r['embed_url'] ?? '' ) ) {
				return array(
					'provider'  => 'soundcloud',
					'src'       => esc_url_raw( (string) $r['embed_url'] ),
					'mode'      => 'widget',
					'start'     => $start,
					'seconds'   => $seconds,
					'canonical' => esc_url_raw( (string) ( $r['canonical'] ?? $url ) ),
				);
			}
		}

		// ── Anything else is only accepted if it is plainly an audio file.
		$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		if ( preg_match( '/\.(mp3|ogg|oga|m4a|wav)$/', $path ) ) {
			return array(
				'provider' => 'direct',
				'src'      => esc_url_raw( $url ),
				'mode'     => 'audio',
				'start'    => $start,
				'seconds'  => $seconds,
			);
		}

		return $none;
	}
}

if ( ! function_exists( 'apollo_track_card_data' ) ) {
	/**
	 * Everything one track card needs, in one call.
	 *
	 * The card renderer takes this and nothing else, so a second surface cannot
	 * assemble a slightly different shape — the defect that left five "Out Now"
	 * implementations reading three different structures.
	 *
	 * @param int $track_id Track post ID.
	 * @return array<string,mixed>
	 */
	function apollo_track_card_data( int $track_id ): array {
		$cover = apollo_track_resolve_cover( $track_id );

		$genre = '';
		$terms = get_the_terms( $track_id, 'sound' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$genre = (string) $terms[0]->name;
		}
		if ( '' === $genre ) {
			$genre = (string) get_post_meta( $track_id, '_track_genre_legacy', true );
		}

		// Per-platform release links (icon row). Empty values stay empty.
		$urls = array(
			'spotify'    => (string) get_post_meta( $track_id, '_track_url_spotify', true ),
			'bandcamp'   => (string) get_post_meta( $track_id, '_track_url_bandcamp', true ),
			'soundcloud' => (string) get_post_meta( $track_id, '_track_url_soundcloud', true ),
			'youtube'    => (string) get_post_meta( $track_id, '_track_url_youtube', true ),
			'download'   => (string) get_post_meta( $track_id, '_track_url_download', true ),
		);

		$listen = function_exists( 'apollo_track_listen' )
			? apollo_track_listen( $track_id )
			: apollo_track_listen_none();

		$platform_urls = apollo_track_display_urls( $track_id, $listen );

		// First non-empty release link — play fallback when no in-page preview.
		$url = '';
		foreach ( array( 'spotify', 'bandcamp', 'soundcloud', 'youtube', 'download' ) as $p ) {
			if ( '' !== $urls[ $p ] ) {
				$url = $urls[ $p ];
				break;
			}
		}

		return array(
			'id'        => $track_id,
			'title'     => (string) get_the_title( $track_id ),
			'permalink' => (string) get_permalink( $track_id ),
			'artists'   => apollo_track_credits_line( $track_id ),
			'credits'   => apollo_track_credits( $track_id ),
			'cover'     => $cover,
			'has_cover' => '' !== $cover,
			'genre'     => $genre,
			'duration'  => (string) get_post_meta( $track_id, '_track_duration', true ),
			'url'       => $url,
			'urls'      => $urls,
			'platform_urls' => $platform_urls,
			'preview'   => apollo_track_preview( $track_id ),
			'listen'    => $listen,
		);
	}
}
