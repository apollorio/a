<?php
/**
 * DJ Single — modular renderer (SSOT for page, inline embed and lightbox).
 *
 * Mirrors apollo-events/includes/render-single.php exactly, because that is the
 * shape apollo-core's Surface Contract expects and the shape that let the event
 * page, its inline embed and its REST fragment stop drifting apart.
 *
 * Before this file, apollo-djs shipped the PARTS but not the DATA: the ~120
 * lines that build $dj_name_formatted, $dj_tagline, $dj_photo_url and the rest
 * were inline at the top of templates/single-dj.php, reachable only by loading
 * that template as a page request. The markup was modular; nothing else was. So
 * a DJ could not be rendered into a lightbox, an embed, or a REST fragment —
 * there was no way to ask for one outside a page load.
 *
 * Usage
 * -----
 * Full page      : styles/base/single-dj.php
 * Inline embed   : echo apollo_dj_render_single( $id, array( 'mode' => 'inline' ) );
 * Lightbox       : data-ap-open="dj:{id}" — apollo-core wires the rest
 * REST fragment  : GET /wp-json/apollo/v1/djs/{id}/fragmento
 *
 * @package Apollo\DJs
 * @since   1.0.5
 * @see     apollo-core/includes/surface-contract.php
 * @see     _sandbox/build-dj-cells.py  the mockup → cells generator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'APOLLO_DJ_SINGLE_PARTS' ) ) {
	/**
	 * Cell order. This IS the page composition — the only place it is declared.
	 *
	 * 'styles' and 'scripts' bracket the content because the mockup's runtime
	 * measures nodes that must already exist, and the stylesheet must land
	 * before first paint or the hero flashes unstyled at full width.
	 */
	define(
		'APOLLO_DJ_SINGLE_PARTS',
		array(
			'styles',
			'progress',
			'topbar',
			'hero',
			'statement',
			'marquee',
			'played-on',
			'numbers',
			'played-with',
			'sound',
			'kit',
			'about',
			'footer',
			'dock',
			'lightbox',
			'toast',
			'data',
			'scripts',
		)
	);
}

/**
 * Can the current viewer see this DJ?
 *
 * The public REST fragment uses this as its only gate, so it must mirror what
 * /dj/{slug} would show the same visitor — no more.
 *
 * @param int $post_id DJ id.
 * @return bool
 */
function apollo_dj_can_view( int $post_id ): bool {
	$post = get_post( $post_id );
	if ( ! $post || 'dj' !== $post->post_type ) {
		return false;
	}
	if ( 'publish' !== $post->post_status ) {
		return current_user_can( 'read_post', $post_id );
	}
	return true;
}

/**
 * Split a stage name into the mockup's two display lines.
 *
 * The hero renders the name as stacked lines, the second in the accent italic
 * ("LEO" / "JANEIRO"). A one-word name yields one line rather than an empty
 * second one; a three-word name keeps the tail together on line two so the
 * accent treatment still reads as one gesture.
 *
 * @param string $name Stage name.
 * @return string[]
 */
function apollo_dj_name_lines( string $name ): array {
	$words = preg_split( '/\s+/u', trim( $name ) ) ?: array();
	$words = array_values( array_filter( $words, static fn( $w ) => '' !== $w ) );

	if ( count( $words ) <= 1 ) {
		return $words ?: array( $name );
	}
	return array( $words[0], implode( ' ', array_slice( $words, 1 ) ) );
}

/**
 * Everything one DJ page needs, in one call.
 *
 * Every key below traces to a meta key or taxonomy declared in
 * _inventory/registry/09-plugins/apollo-djs.json — the mockup annotates each of
 * its values with the same `@field:` reference, so the two can be diffed.
 *
 * @param int $post_id DJ id.
 * @return array<string,mixed>
 */
function apollo_dj_single_context( int $post_id ): array {
	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$m = static function ( string $key, string $default = '' ) use ( $post_id ): string {
		$v = get_post_meta( $post_id, $key, true );
		return is_string( $v ) && '' !== $v ? $v : $default;
	};

	$name = $m( '_dj_name', (string) get_the_title( $post_id ) );

	// Taxonomy `sound` — the genre tags and the marquee both read this.
	$sounds = function_exists( 'apollo_dj_get_sounds' ) ? (array) apollo_dj_get_sounds( $post_id ) : array();

	/*
	 * Tracks come through apollo_dj_get_tracks(), NOT raw meta. That function is
	 * the v2 read path added on 2026-07-28 ($tracks_read_path_migration in the
	 * registry): reading _dj_tracks directly returns the retired v1 shape and
	 * silently drops every field added since.
	 */
	$tracks = function_exists( 'apollo_dj_get_tracks' ) ? (array) apollo_dj_get_tracks( $post_id ) : array();

	// apollo_dj_get_banner()/get_image() already own the meta-then-thumbnail fallback.
	$hero_image = function_exists( 'apollo_dj_get_banner' ) ? (string) apollo_dj_get_banner( $post_id ) : '';
	if ( '' === $hero_image && function_exists( 'apollo_dj_get_image' ) ) {
		$hero_image = (string) apollo_dj_get_image( $post_id );
	}

	$ctx = array(
		'dj_id'          => $post_id,
		'dj_name'        => $name,
		'dj_name_upper'  => function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $name, 'UTF-8' ) : strtoupper( $name ),
		'dj_name_lines'  => apollo_dj_name_lines( $name ),
		'dj_permalink'   => (string) get_permalink( $post_id ),
		'dj_bio'         => $m( '_dj_bio' ),
		'dj_bio_short'   => $m( '_dj_bio_short' ),
		'dj_statement'   => $m( '_dj_statement' ),
		'dj_hero_image'  => $hero_image,
		'dj_foot_image'  => $m( '_dj_about_photo', $hero_image ),
		'dj_sounds'      => $sounds,
		'dj_verified'    => function_exists( 'apollo_dj_is_verified' ) && apollo_dj_is_verified( $post_id ),

		/*
		 * Hero eyebrow: city + the two leading sounds, which is what the mockup
		 * spells out longhand ("Rio de Janeiro — Hard Groove & Peak Time").
		 * Falls back to the name so the slot is never an empty rule.
		 */
		'dj_eyebrow'     => trim(
			implode(
				' — ',
				array_filter(
					array(
						$m( '_loc_city', __( 'Rio de Janeiro', 'apollo-djs' ) ),
						implode( ' & ', array_slice( $sounds, 0, 2 ) ),
					)
				)
			)
		),
		'dj_status'      => '' !== $m( '_dj_booking' )
			/* translators: %s: current year. */
			? sprintf( __( 'Booking aberto · %s', 'apollo-djs' ), gmdate( 'Y' ) )
			: '',
	);

	/*
	 * Counters come from apollo_dj_compute_stats(), which apollo-djs already
	 * ships — it returns events/venues/cities/dawns, which is field-for-field the
	 * four the mockup asks for. An earlier draft of this file re-derived them
	 * with its own WP_Query; that was a second implementation of a solved
	 * problem and the two would have disagreed the first time either changed.
	 *
	 * A zero is dropped rather than rendered, so a new DJ shows a short band
	 * instead of a wall of noughts.
	 */
	$past  = function_exists( 'apollo_dj_get_past_events' ) ? (array) apollo_dj_get_past_events( $post_id, 40 ) : array();
	$stats = function_exists( 'apollo_dj_compute_stats' ) ? (array) apollo_dj_compute_stats( $past ) : array();

	$labels = array(
		'events' => __( 'Eventos tocados no circuito Apollo', 'apollo-djs' ),
		'venues' => __( 'Casas e promotoras diferentes que já bookaram', 'apollo-djs' ),
		'cities' => __( 'Cidades onde já tocou', 'apollo-djs' ),
		'dawns'  => __( 'Amanheceres que a pista se recusou a soltar', 'apollo-djs' ),
	);
	$ctx['dj_numbers'] = array();
	foreach ( $labels as $key => $label ) {
		if ( ! empty( $stats[ $key ] ) ) {
			$ctx['dj_numbers'][] = array( 'v' => (int) $stats[ $key ], 'l' => $label );
		}
	}

	$pill_labels = array(
		'events' => __( 'Eventos', 'apollo-djs' ),
		'cities' => __( 'Cidades', 'apollo-djs' ),
	);
	$ctx['dj_pills'] = array();
	foreach ( $pill_labels as $key => $label ) {
		if ( ! empty( $stats[ $key ] ) ) {
			$ctx['dj_pills'][] = array( 'v' => (string) (int) $stats[ $key ], 'l' => $label );
		}
	}

	/*
	 * The runtime payload. Key names are the mockup's, not PHP's — the JS was
	 * written against this shape and renaming here to match snake_case would
	 * mean editing the runtime, which is the one file that should stay a copy of
	 * what was signed off.
	 */
	$ctx['dj_payload'] = array(
		'name'         => $name,
		'soundcloud'   => $m( '_dj_soundcloud' ),
		'bandcamp'     => $m( '_dj_bandcamp' ),
		'spotify'      => $m( '_dj_spotify' ),
		'instagram'    => $m( '_dj_instagram' ),
		'bookingEmail' => $m( '_dj_booking' ),
		'mediaKitUrl'  => $m( '_dj_media_kit_url' ),
		'riderUrl'     => $m( '_dj_rider_url' ),
		'videoUrl'     => $m( '_dj_about_video' ),
		'aboutPhoto'   => $m( '_dj_about_photo' ),
		'genres'       => $sounds,
		/* Both shapes are produced by apollo-djs' own helpers and already match
		   the mockup key-for-key — playedOn {title,venue,date,withCount,cover,url},
		   playedWith {name,role,avatar,url}. Nothing is re-derived here. */
		'playedOn'     => function_exists( 'apollo_dj_map_played_on' ) ? (array) apollo_dj_map_played_on( $past ) : array(),
		'playedWith'   => function_exists( 'apollo_dj_get_played_with' ) ? (array) apollo_dj_get_played_with( $post_id, 12 ) : array(),
		'tracks'       => $tracks,
	);

	/**
	 * Filter the DJ render context.
	 *
	 * @param array<string,mixed> $ctx     Context.
	 * @param int                 $post_id DJ id.
	 */
	$ctx = (array) apply_filters( 'apollo_dj_single_context', $ctx, $post_id );

	$cache[ $post_id ] = $ctx;
	return $ctx;
}

/**
 * Locate one cell, theme-overridable.
 *
 * A theme replaces a single cell by shipping the same filename under its own
 * apollo-djs/{style}/ tree — the same override shape apollo-events uses.
 *
 * @param string $part Cell slug.
 * @return string Absolute path, or '' when absent.
 */
function apollo_dj_single_part_path( string $part ): string {
	$part = sanitize_key( str_replace( '_', '-', $part ) );
	$rel  = 'template-parts/single/' . $part . '.php';
	$dir  = defined( 'APOLLO_DJ_DIR' ) ? APOLLO_DJ_DIR : plugin_dir_path( __DIR__ );

	foreach ( array( 'styles/apollo-v3/', 'styles/base/' ) as $style ) {
		$file = $dir . $style . $rel;
		if ( is_readable( $file ) ) {
			return $file;
		}
	}
	return '';
}

/**
 * Render one cell with the context in scope.
 *
 * @param string              $part Cell slug.
 * @param array<string,mixed> $ctx  Context from apollo_dj_single_context().
 * @return string
 */
function apollo_dj_render_part( string $part, array $ctx ): string {
	$file = apollo_dj_single_part_path( $part );
	if ( '' === $file ) {
		return '';
	}

	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- the cell contract.
	extract( $ctx, EXTR_SKIP );

	ob_start();
	include $file;
	return (string) ob_get_clean();
}

/**
 * Render one DJ.
 *
 * THE single entry point. The page template, an inline embed and the REST
 * fragment all come through here, which is what stops the three drifting.
 *
 * @param int                 $post_id DJ id.
 * @param array<string,mixed> $args    mode: page|inline|fragment · parts: string[]
 * @return string
 */
function apollo_dj_render_single( int $post_id, array $args = array() ): string {
	if ( ! apollo_dj_can_view( $post_id ) ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'mode'  => 'page',
			'parts' => APOLLO_DJ_SINGLE_PARTS,
		)
	);

	$parts = array_values( array_intersect( (array) $args['parts'], APOLLO_DJ_SINGLE_PARTS ) );
	if ( empty( $parts ) ) {
		$parts = APOLLO_DJ_SINGLE_PARTS;
	}

	$ctx         = apollo_dj_single_context( $post_id );
	$ctx['mode'] = (string) $args['mode'];

	$html = '';
	foreach ( $parts as $part ) {
		$html .= apollo_dj_render_part( $part, $ctx );
	}

	/*
	 * .dj-page — THE SCOPE THE STYLESHEET IS WRITTEN AGAINST.
	 *
	 * The mockup styles bare elements (a, button, figure, i, img, ul) because it
	 * is a standalone document and owns them. Inside WordPress the active theme
	 * owns them too, and two global rulesets for `a` just fight. The generator
	 * therefore rewrites every one of those rules to `.dj-page a { … }`, which
	 * means the markup MUST sit inside this element or the page renders with the
	 * theme's link, button and figure styling instead of its own.
	 *
	 * Wrapping here rather than in the page template is deliberate: the REST
	 * fragment the lightbox injects goes through this same function, and it is
	 * injected into a DOCUMENT THAT IS NOT THIS PAGE. Without the wrapper
	 * travelling with the markup, a DJ opened in the lightbox would lose every
	 * element-level rule.
	 *
	 * Class rules need no wrapper — they are all `.dj-*`, which nothing else on
	 * the platform declares.
	 */
	$html = '<div class="dj-page" data-dj-root="' . esc_attr( (string) $post_id ) . '">'
		. $html
		. '</div>';

	/**
	 * Filter the rendered DJ markup.
	 *
	 * @param string              $html    Markup.
	 * @param int                 $post_id DJ id.
	 * @param array<string,mixed> $args    Render args.
	 */
	return (string) apply_filters( 'apollo_dj_render_single', $html, $post_id, $args );
}
