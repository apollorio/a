<?php
/**
 * Funções helper do Apollo DJs
 *
 * @package Apollo\DJs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna instância do plugin
 */
function apollo_dj(): ?\Apollo\DJs\Plugin {
	return $GLOBALS['apollo_dj'] ?? null;
}

/**
 * Retorna opção do plugin
 */
function apollo_dj_option( string $key, $default = null ) {
	$settings = get_option( 'apollo_dj_settings', array() );
	return $settings[ $key ] ?? $default;
}

/**
 * Retorna a foto do DJ (banner ou thumbnail como fallback)
 */
function apollo_dj_get_image( int $dj_id ): string {
	$image_id = (int) get_post_meta( $dj_id, '_dj_image', true );
	if ( $image_id ) {
		$url = wp_get_attachment_image_url( $image_id, 'large' );
		if ( $url ) {
			return $url;
		}
	}

	$thumb = get_the_post_thumbnail_url( $dj_id, 'large' );
	if ( $thumb ) {
		return $thumb;
	}

	return APOLLO_DJ_URL . 'assets/images/placeholder-dj.svg';
}

/**
 * Retorna banner do DJ
 */
function apollo_dj_get_banner( int $dj_id ): string {
	$banner_id = (int) get_post_meta( $dj_id, '_dj_banner', true );
	if ( $banner_id ) {
		$url = wp_get_attachment_image_url( $banner_id, 'full' );
		if ( $url ) {
			return $url;
		}
	}

	return apollo_dj_get_image( $dj_id );
}

/**
 * Retorna links sociais agrupados por categoria
 */
function apollo_dj_get_links( int $dj_id ): array {
	$music     = array();
	$social    = array();
	$platforms = array();

	$mappings = array(
		'_dj_soundcloud' => array(
			'group' => 'music',
			'icon'  => 'ri-soundcloud-line',
			'label' => 'SoundCloud',
		),
		'_dj_spotify'    => array(
			'group' => 'music',
			'icon'  => 'ri-spotify-line',
			'label' => 'Spotify',
		),
		'_dj_youtube'    => array(
			'group' => 'music',
			'icon'  => 'ri-youtube-line',
			'label' => 'YouTube',
		),
		'_dj_instagram'  => array(
			'group' => 'social',
			'icon'  => 'ri-instagram-line',
			'label' => 'Instagram',
		),
		'_dj_mixcloud'   => array(
			'group' => 'platforms',
			'icon'  => 'ri-disc-line',
			'label' => 'Mixcloud',
		),
		'_dj_website'    => array(
			'group' => 'platforms',
			'icon'  => 'ri-global-line',
			'label' => 'Website',
		),
	);

	foreach ( $mappings as $meta_key => $info ) {
		$value = get_post_meta( $dj_id, $meta_key, true );
		if ( empty( $value ) ) {
			continue;
		}

		$link = array(
			'url'   => esc_url( $value ),
			'icon'  => $info['icon'],
			'label' => $info['label'],
		);

		switch ( $info['group'] ) {
			case 'music':
				$music[] = $link;
				break;
			case 'social':
				$social[] = $link;
				break;
			case 'platforms':
				$platforms[] = $link;
				break;
		}
	}

	return array(
		'music'     => $music,
		'social'    => $social,
		'platforms' => $platforms,
	);
}

/**
 * Retorna gêneros musicais do DJ (taxonomy sound)
 */
function apollo_dj_get_sounds( int $dj_id ): array {
	$terms = wp_get_post_terms( $dj_id, APOLLO_DJ_TAX_SOUND, array( 'fields' => 'names' ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	return $terms;
}

/**
 * Verifica se DJ é verificado
 */
function apollo_dj_is_verified( int $dj_id ): bool {
	return (bool) get_post_meta( $dj_id, '_dj_verified', true );
}

/**
 * Retorna eventos futuros do DJ
 */
function apollo_dj_get_upcoming_events( int $dj_id, int $limit = 5 ): array {
	$args = array(
		'post_type'      => 'event',
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => '_event_dj_ids',
				'value'   => $dj_id,
				'compare' => 'LIKE',
			),
			array(
				'key'     => '_event_start_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
		'orderby'        => 'meta_value',
		'meta_key'       => '_event_start_date',
		'order'          => 'ASC',
	);

	return get_posts( $args );
}

/**
 * Conta eventos futuros do DJ
 */
function apollo_dj_count_upcoming_events( int $dj_id ): int {
	$cache_key = 'dj_upcoming_count_' . $dj_id;
	$count     = wp_cache_get( $cache_key, APOLLO_DJ_CACHE_GROUP );

	if ( false !== $count ) {
		return (int) $count;
	}

	$count = count( apollo_dj_get_upcoming_events( $dj_id, -1 ) );
	wp_cache_set( $cache_key, $count, APOLLO_DJ_CACHE_GROUP, APOLLO_DJ_CACHE_TTL );

	return $count;
}

/**
 * Limpa cache do DJ quando um evento é salvo
 */
function apollo_dj_flush_cache( int $post_id ): void {
	$post_type = get_post_type( $post_id );

	if ( $post_type === APOLLO_DJ_CPT ) {
		wp_cache_delete( 'dj_upcoming_count_' . $post_id, APOLLO_DJ_CACHE_GROUP );
		wp_cache_delete( 'dj_past_events_' . $post_id, APOLLO_DJ_CACHE_GROUP );
		wp_cache_delete( 'dj_played_with_' . $post_id, APOLLO_DJ_CACHE_GROUP );
		wp_cache_delete( 'dj_context_' . $post_id, APOLLO_DJ_CACHE_GROUP );
	}

	if ( $post_type === 'event' ) {
		$dj_ids = get_post_meta( $post_id, '_event_dj_ids', true );
		if ( is_array( $dj_ids ) ) {
			foreach ( $dj_ids as $dj_id ) {
				$dj_id = (int) $dj_id;
				wp_cache_delete( 'dj_upcoming_count_' . $dj_id, APOLLO_DJ_CACHE_GROUP );
				wp_cache_delete( 'dj_past_events_' . $dj_id, APOLLO_DJ_CACHE_GROUP );
				wp_cache_delete( 'dj_played_with_' . $dj_id, APOLLO_DJ_CACHE_GROUP );
				wp_cache_delete( 'dj_context_' . $dj_id, APOLLO_DJ_CACHE_GROUP );
			}
		}
	}
}
add_action( 'save_post', 'apollo_dj_flush_cache' );

/**
 * Format track meta line for Out now! section.
 * Genre is always hardcoded as RIO DE JANEIRO (never from taxonomy).
 *
 * @param array{title?:string,url?:string,year?:string,duration?:string,meta?:string} $track Track row.
 */
function apollo_dj_format_track_meta( array $track ): string {
	$year     = trim( (string) ( $track['year'] ?? '' ) );
	$duration = trim( (string) ( $track['duration'] ?? '' ) );

	// Legacy freeform meta: "2025 · Hard Groove · 6:12"
	if ( ( '' === $year || '' === $duration ) && ! empty( $track['meta'] ) ) {
		$parts = array_map( 'trim', explode( '·', (string) $track['meta'] ) );
		if ( '' === $year && isset( $parts[0] ) && preg_match( '/^\d{4}$/', $parts[0] ) ) {
			$year = $parts[0];
		}
		if ( '' === $duration && ! empty( $parts ) ) {
			$last = end( $parts );
			if ( is_string( $last ) && preg_match( '/^\d+:\d{2}$/', $last ) ) {
				$duration = $last;
			}
		}
	}

	$bits = array();
	if ( '' !== $year ) {
		$bits[] = $year;
	}
	$bits[] = 'RIO DE JANEIRO';
	if ( '' !== $duration ) {
		$bits[] = $duration;
	}

	return implode( ' · ', $bits );
}

/**
 * Sanitize + validate the _dj_tracks structured repeater (schema v2, 2026-07-28).
 *
 * Registered as the `sanitize` callback for `_dj_tracks` in
 * apollo-core/src/Core/MetaRegistry.php (dj post-meta group).
 *
 * IT RUNS ON EVERY WRITE. THIS PARAGRAPH USED TO SAY THE OPPOSITE (fixed
 * 2026-08-17). The previous text claimed "Direct update_post_meta() calls are
 * NOT routed through this — WP only invokes sanitize_meta() for the REST/
 * registered-meta path". That is false: WP core's update_metadata() calls
 * sanitize_meta() on every single write, whatever the caller.
 *
 * The belief was not harmless. It is why DJsController::save_meta() shipped a
 * quick-add that writes v1 rows {title,url,duration,year} — no `artists`, no
 * `url_*` — which the rules below then discard in full, silently. A user filled
 * in a track on "Cadastrar novo DJ", got a success response, and the track was
 * never stored.
 *
 * So: there is exactly ONE place that decides what a valid track row is, and it
 * is this function. Do not add a second inline copy of these rules anywhere;
 * write through it instead.
 *
 * Rules (per plugins/_inventory/registry/20-meta-schemas.json):
 * - title, artists, duration are mandatory; rows missing any are dropped.
 * - at least one of url_soundcloud / url_spotify / url_bandcamp / url_download
 *   is mandatory; rows with none are dropped.
 * - result is sorted newest-first by release_date.
 *
 * @param mixed $value Raw meta value.
 * @return list<array<string,mixed>>
 */
function apollo_dj_sanitize_tracks_meta( $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$clean = array();

	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$title    = sanitize_text_field( (string) ( $row['title'] ?? '' ) );
		$artists  = sanitize_text_field( (string) ( $row['artists'] ?? '' ) );
		$duration = sanitize_text_field( (string) ( $row['duration'] ?? '' ) );

		if ( '' === $title || '' === $artists || '' === $duration ) {
			continue;
		}

		$urls = array(
			'url_soundcloud' => esc_url_raw( (string) ( $row['url_soundcloud'] ?? '' ) ),
			'url_spotify'    => esc_url_raw( (string) ( $row['url_spotify'] ?? '' ) ),
			'url_bandcamp'   => esc_url_raw( (string) ( $row['url_bandcamp'] ?? '' ) ),
			'url_download'   => esc_url_raw( (string) ( $row['url_download'] ?? '' ) ),
		);

		if ( ! array_filter( $urls ) ) {
			continue;
		}

		$clean[] = array_merge(
			array(
				'title'        => $title,
				'artists'      => $artists,
				'duration'     => $duration,
				'bpm'          => absint( $row['bpm'] ?? 0 ),
				'release_date' => sanitize_text_field( (string) ( $row['release_date'] ?? '' ) ),
				'album'        => sanitize_text_field( (string) ( $row['album'] ?? '' ) ),
				'label'        => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'genre'        => sanitize_text_field( (string) ( $row['genre'] ?? '' ) ),
				'cover_url'    => esc_url_raw( (string) ( $row['cover_url'] ?? '' ) ),
				'cover_id'     => absint( $row['cover_id'] ?? 0 ),
			),
			$urls
		);
	}

	usort(
		$clean,
		static function ( $a, $b ) {
			return strcmp( (string) ( $b['release_date'] ?? '' ), (string) ( $a['release_date'] ?? '' ) );
		}
	);

	return array_values( $clean );
}

/**
 * Normalize _dj_tracks repeater for the front-end card.
 *
 * NOTE (2026-07-28): reads only the legacy {title,url,year,duration} shape.
 * Schema v2 (see apollo_dj_sanitize_tracks_meta() above) stores artists/
 * release_date/url_soundcloud etc. instead of a single url/year — this
 * normalizer has NOT been migrated yet. See registry/20-meta-schemas.json
 * "known_gap" for the tracked follow-up.
 *
 * @return list<array{title:string,url:string,year:string,duration:string,meta:string}>
 */
/**
 * Get a DJ's tracks — reads _dj_tracks (schema v2, 2026-07-28).
 *
 * KNOWN GAP CLOSED (2026-07-28): this used to read the retired v1 shape
 * {title,url,year,duration}, silently losing artists/bpm/release_date/album/
 * label/genre/cover_url/cover_id and every url_* field written by the current
 * admin metabox + REST path. Now reads v2 in full and ADDS back `url`
 * (first non-empty of url_soundcloud/spotify/bandcamp/download) and `year`
 * (derived from release_date) as back-compat aliases — a pure superset, so
 * out-now.php / out-now-lightbox.php / dj-card-single.js and
 * apollo_dj_format_track_meta() (which reads $track['year']/['duration'])
 * keep working unchanged against real v2 data.
 *
 * @param int $dj_id
 * @return list<array<string,mixed>> newest-first by release_date.
 */
function apollo_dj_get_tracks( int $dj_id ): array {
	$raw = get_post_meta( $dj_id, '_dj_tracks', true );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$out = array();
	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$title   = trim( (string) ( $row['title'] ?? '' ) );
		$artists = trim( (string) ( $row['artists'] ?? '' ) );
		if ( '' === $title || '' === $artists ) {
			continue;
		}

		$urls = array(
			'url_soundcloud' => trim( (string) ( $row['url_soundcloud'] ?? '' ) ),
			'url_spotify'    => trim( (string) ( $row['url_spotify'] ?? '' ) ),
			'url_bandcamp'   => trim( (string) ( $row['url_bandcamp'] ?? '' ) ),
			'url_download'   => trim( (string) ( $row['url_download'] ?? '' ) ),
		);
		$primary_url = '';
		foreach ( $urls as $u ) {
			if ( '' !== $u ) {
				$primary_url = $u;
				break;
			}
		}
		if ( '' === $primary_url ) {
			continue;
		}

		$release_date = trim( (string) ( $row['release_date'] ?? '' ) );
		$year         = '';
		if ( '' !== $release_date ) {
			$ts = strtotime( $release_date );
			$year = false !== $ts ? date( 'Y', $ts ) : '';
		}

		$track = array(
			'title'        => $title,
			'artists'      => $artists,
			'duration'     => trim( (string) ( $row['duration'] ?? '' ) ),
			'bpm'          => trim( (string) ( $row['bpm'] ?? '' ) ),
			'release_date' => $release_date,
			'album'        => trim( (string) ( $row['album'] ?? '' ) ),
			'label'        => trim( (string) ( $row['label'] ?? '' ) ),
			'genre'        => trim( (string) ( $row['genre'] ?? '' ) ),
			'cover_url'    => esc_url_raw( (string) ( $row['cover_url'] ?? '' ) ),
			'cover_id'     => absint( $row['cover_id'] ?? 0 ),
			'urls'         => $urls,
			/* back-compat aliases (v1 shape) */
			'url'          => $primary_url,
			'year'         => $year,
			'meta'         => '',
		);
		$track['meta'] = apollo_dj_format_track_meta( $track );
		$out[]         = $track;
	}

	usort(
		$out,
		function ( $a, $b ) {
			$ta = '' !== $a['release_date'] ? strtotime( $a['release_date'] ) : 0;
			$tb = '' !== $b['release_date'] ? strtotime( $b['release_date'] ) : 0;
			return $tb <=> $ta;
		}
	);

	return $out;
}

/**
 * Aggregate the newest released tracks across ALL published DJs — powers the
 * /casa "Out Now!" section with real data (registry spec: max 5 + Ver Todos).
 *
 * @param int $limit Max tracks to return.
 * @return list<array<string,mixed>> Each track plus dj_id/dj_name/dj_permalink,
 *                                   newest-first by release_date.
 */
function apollo_get_latest_dj_tracks( int $limit = 5 ): array {
	$dj_ids = get_posts(
		array(
			'post_type'      => 'dj',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$all = array();
	foreach ( $dj_ids as $dj_id ) {
		$tracks = apollo_dj_get_tracks( (int) $dj_id );
		if ( empty( $tracks ) ) {
			continue;
		}
		$dj_name      = get_the_title( $dj_id );
		$dj_permalink = get_permalink( $dj_id );
		foreach ( $tracks as $track ) {
			$track['dj_id']        = (int) $dj_id;
			$track['dj_name']      = $dj_name;
			$track['dj_permalink'] = $dj_permalink;
			$all[]                 = $track;
		}
	}

	usort(
		$all,
		function ( $a, $b ) {
			$ta = '' !== $a['release_date'] ? strtotime( $a['release_date'] ) : 0;
			$tb = '' !== $b['release_date'] ? strtotime( $b['release_date'] ) : 0;
			return $tb <=> $ta;
		}
	);

	return array_slice( $all, 0, max( 0, $limit ) );
}

/**
 * Past Apollo events where this DJ was on the lineup.
 *
 * @return list<\WP_Post>
 */
function apollo_dj_get_past_events( int $dj_id, int $limit = 20 ): array {
	$cache_key = 'dj_past_events_' . $dj_id . '_' . $limit;
	$cached    = wp_cache_get( $cache_key, APOLLO_DJ_CACHE_GROUP );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'event',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_event_dj_ids',
					'value'   => $dj_id,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_event_start_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '<',
					'type'    => 'DATE',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'DESC',
		)
	);

	wp_cache_set( $cache_key, $posts, APOLLO_DJ_CACHE_GROUP, APOLLO_DJ_CACHE_TTL );

	return $posts;
}

/**
 * Build playedOn cards from past event posts.
 *
 * @param list<\WP_Post> $events Past events.
 * @return list<array{title:string,venue:string,date:string,withCount:int,cover:string,url:string}>
 */
function apollo_dj_map_played_on( array $events ): array {
	$out = array();
	foreach ( $events as $event ) {
		if ( ! $event instanceof \WP_Post ) {
			continue;
		}
		$dj_ids = get_post_meta( $event->ID, '_event_dj_ids', true );
		if ( ! is_array( $dj_ids ) ) {
			$dj_ids = array();
		}
		$loc_id  = (int) get_post_meta( $event->ID, '_event_loc_id', true );
		$venue   = $loc_id ? get_the_title( $loc_id ) : (string) get_post_meta( $event->ID, '_event_venue_name', true );
		$start   = (string) get_post_meta( $event->ID, '_event_start_date', true );
		$ts      = $start ? strtotime( $start ) : false;
		$date    = $ts ? wp_date( 'd M y', $ts ) : '';
		$cover   = get_the_post_thumbnail_url( $event->ID, 'large' );
		if ( ! $cover ) {
			$cover = '';
		}
		$out[] = array(
			'title'     => get_the_title( $event ),
			'venue'     => $venue ?: '',
			'date'      => strtoupper( $date ),
			'withCount' => max( 0, count( $dj_ids ) - 1 ),
			'cover'     => $cover,
			'url'       => get_permalink( $event ) ?: '',
		);
	}
	return $out;
}

/**
 * Co-DJs from past lineups (excludes self).
 *
 * @return list<array{name:string,role:string,avatar:string,url:string,id:int}>
 */
function apollo_dj_get_played_with( int $dj_id, int $limit = 12 ): array {
	$cache_key = 'dj_played_with_' . $dj_id;
	$cached    = wp_cache_get( $cache_key, APOLLO_DJ_CACHE_GROUP );
	if ( false !== $cached && is_array( $cached ) ) {
		return array_slice( $cached, 0, $limit );
	}

	$events = apollo_dj_get_past_events( $dj_id, 40 );
	$seen   = array();
	$out    = array();

	foreach ( $events as $event ) {
		$dj_ids = get_post_meta( $event->ID, '_event_dj_ids', true );
		if ( ! is_array( $dj_ids ) ) {
			continue;
		}
		foreach ( $dj_ids as $other_id ) {
			$other_id = (int) $other_id;
			if ( $other_id === $dj_id || isset( $seen[ $other_id ] ) || $other_id <= 0 ) {
				continue;
			}
			$post = get_post( $other_id );
			if ( ! $post || APOLLO_DJ_CPT !== $post->post_type || 'publish' !== $post->post_status ) {
				continue;
			}
			$seen[ $other_id ] = true;
			$name              = get_post_meta( $other_id, '_dj_name', true ) ?: get_the_title( $other_id );
			$sounds            = apollo_dj_get_sounds( $other_id );
			$role              = ! empty( $sounds ) ? (string) $sounds[0] : __( 'Line-up', 'apollo-djs' );
			$out[]             = array(
				'id'     => $other_id,
				'name'   => (string) $name,
				'role'   => $role,
				'avatar' => apollo_dj_get_image( $other_id ),
				'url'    => get_permalink( $other_id ) ?: '',
			);
		}
	}

	wp_cache_set( $cache_key, $out, APOLLO_DJ_CACHE_GROUP, APOLLO_DJ_CACHE_TTL );

	return array_slice( $out, 0, $limit );
}

/**
 * Compute Em números stats from past events.
 *
 * @param list<\WP_Post> $events Past events.
 * @return array{events:int,venues:int,cities:int,dawns:int}
 */
function apollo_dj_compute_stats( array $events ): array {
	$venues = array();
	$cities = array();
	foreach ( $events as $event ) {
		if ( ! $event instanceof \WP_Post ) {
			continue;
		}
		$loc_id = (int) get_post_meta( $event->ID, '_event_loc_id', true );
		if ( $loc_id ) {
			$venues[ $loc_id ] = true;
			$city              = (string) get_post_meta( $loc_id, '_local_city', true );
			if ( '' !== $city ) {
				$cities[ strtolower( $city ) ] = true;
			}
		}
	}
	$count = count( $events );
	$stats = array(
		'events' => $count,
		'venues' => count( $venues ),
		'cities' => count( $cities ),
		'dawns'  => $count, // proxy: one dawn per past gig until a dedicated meta exists
	);

	/**
	 * Filter computed DJ card stats.
	 *
	 * @param array $stats  Computed stats.
	 * @param array $events Past event posts.
	 */
	return apply_filters( 'apollo_dj_card_stats', $stats, $events );
}

/**
 * Single source of truth for the DJ business-card page + wp_localize_script.
 *
 * Shape mirrors mockup window.APOLLO_DJ — markup/JS stay untouched by DB reads.
 *
 * @return array<string,mixed>
 */
function apollo_get_dj_context( int $dj_id ): array {
	$cache_key = 'dj_context_' . $dj_id;
	$cached    = wp_cache_get( $cache_key, APOLLO_DJ_CACHE_GROUP );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$post = get_post( $dj_id );
	if ( ! $post || APOLLO_DJ_CPT !== $post->post_type ) {
		return array();
	}

	$name = get_post_meta( $dj_id, '_dj_name', true ) ?: get_the_title( $dj_id );

	$about_photo_id = (int) get_post_meta( $dj_id, '_dj_about_photo', true );
	$about_photo    = $about_photo_id ? (string) wp_get_attachment_image_url( $about_photo_id, 'large' ) : '';
	if ( '' === $about_photo ) {
		$about_photo = apollo_dj_get_image( $dj_id );
	}

	$past_posts  = apollo_dj_get_past_events( $dj_id, 20 );
	$played_on   = apollo_dj_map_played_on( $past_posts );
	$played_with = apollo_dj_get_played_with( $dj_id );
	$stats       = apollo_dj_compute_stats( $past_posts );
	$bio_short   = (string) get_post_meta( $dj_id, '_dj_bio_short', true );
	$statement   = (string) get_post_meta( $dj_id, '_dj_statement', true );
	if ( '' === $statement && '' !== $bio_short ) {
		$statement = $bio_short;
	}

	$ctx = array(
		'id'           => $dj_id,
		'name'         => (string) $name,
		'slug'         => $post->post_name,
		'permalink'    => get_permalink( $dj_id ) ?: '',
		'soundcloud'   => (string) get_post_meta( $dj_id, '_dj_soundcloud', true ),
		'bandcamp'     => (string) get_post_meta( $dj_id, '_dj_bandcamp', true ),
		'spotify'      => (string) get_post_meta( $dj_id, '_dj_spotify', true ),
		'instagram'    => (string) get_post_meta( $dj_id, '_dj_instagram', true ),
		'bookingEmail' => (string) get_post_meta( $dj_id, '_dj_booking', true ),
		'mediaKitUrl'  => (string) get_post_meta( $dj_id, '_dj_media_kit_url', true ),
		'riderUrl'     => (string) get_post_meta( $dj_id, '_dj_rider_url', true ),
		'setUrl'       => (string) get_post_meta( $dj_id, '_dj_set_url', true ),
		'videoUrl'     => (string) get_post_meta( $dj_id, '_dj_about_video', true ),
		'aboutPhoto'   => $about_photo,
		'heroImage'    => apollo_dj_get_banner( $dj_id ),
		'footerImage'  => apollo_dj_get_banner( $dj_id ),
		'bioShort'     => $bio_short,
		'bio'          => (string) get_post_meta( $dj_id, '_dj_bio', true ),
		'statement'    => $statement,
		'genres'       => apollo_dj_get_sounds( $dj_id ),
		'tracks'       => apollo_dj_get_tracks( $dj_id ),
		'playedOn'     => $played_on,
		'playedWith'   => $played_with,
		'stats'        => $stats,
		'verified'     => apollo_dj_is_verified( $dj_id ),
	);

	/**
	 * Filter DJ business-card context before localize / render.
	 *
	 * @param array $ctx   Context payload.
	 * @param int   $dj_id DJ post ID.
	 */
	$ctx = apply_filters( 'apollo_get_dj_context', $ctx, $dj_id );

	wp_cache_set( $cache_key, $ctx, APOLLO_DJ_CACHE_GROUP, APOLLO_DJ_CACHE_TTL );

	return $ctx;
}

/**
 * Sanitize `_dj_media_kit_stats` — the four rows under the press-kit CTA.
 *
 * A Google Drive folder cannot be introspected from here, so file size, photo
 * count and rider version are claims the artist makes about their own kit, not
 * facts Apollo can derive. That makes them free text, which makes a sanitizer
 * mandatory rather than optional.
 *
 * This is the same shape as apollo_dj_sanitize_tracks_meta(): ONE function used
 * by BOTH write paths — the REST route (through WP's sanitize_meta()) and the
 * apollo-lux-panels metabox (as its save-time callback). Two sanitizers is how
 * `_dj_tracks` ended up with two competing schemas on a single edit screen
 * (see registry 20-meta-schemas.$duplicate_metabox_found).
 *
 * @since 2026-08-11
 *
 * @param mixed $value Raw repeater value.
 * @return array<int,array{value:string,label:string}> Max 4 clean rows.
 */
function apollo_dj_sanitize_kit_stats( $value ): array {
	if ( is_string( $value ) ) {
		$decoded = json_decode( $value, true );
		$value   = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
	}
	if ( ! is_array( $value ) ) {
		return array();
	}

	$out = array();
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$v = isset( $row['value'] ) ? sanitize_text_field( (string) $row['value'] ) : '';
		$l = isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';

		// A row with no value is decoration; a row with no label is unreadable.
		if ( '' === $v || '' === $l ) {
			continue;
		}

		$out[] = array(
			'value' => mb_substr( $v, 0, 24 ),
			'label' => mb_substr( $l, 0, 40 ),
		);

		// The layout is a four-column grid. A fifth row wraps and breaks it.
		if ( count( $out ) >= 4 ) {
			break;
		}
	}

	return $out;
}
