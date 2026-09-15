<?php
/**
 * Single Event — modular renderer (SSOT for page, inline and lightbox output).
 *
 * The public single event page, an inline embed inside any other template and
 * the REST HTML fragment consumed by the lightbox all render through
 * apollo_event_render_single(). One code path = the three surfaces can never
 * drift apart.
 *
 * Usage
 * -----
 * Full page      : handled by styles/base/single-event.php
 * Inline embed   : echo apollo_event_render_single( $id, array( 'mode' => 'inline' ) );
 * Lightbox       : apollo_event_lightbox_shell(); + data-ev-open="{id}" on any trigger
 * REST fragment  : GET /wp-json/apollo/v1/eventos/{id}/fragmento
 *
 * @package Apollo\Event
 * @since   2.3.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'APOLLO_EVENT_SINGLE_PARTS' ) ) {
	/**
	 * Default part order. Any subset can be rendered via the 'parts' arg,
	 * which is what makes the page replicable piecemeal in other screens.
	 */
	define(
		'APOLLO_EVENT_SINGLE_PARTS',
		array(
			'hero',
			'facts',
			'rsvp',
			'marquee',
			'lineup',
			'timetable',
			'gallery',
			'about',
			'venue',
			'access',
			'footer-visual',
			'chat',
		)
	);
}

/**
 * REVEAL SELECTOR CONTRACT — single source of truth (P3, 2026-08-07).
 *
 * This list used to be hand-copied into THREE files that had no way of knowing
 * about each other:
 *
 *   1. apollo-single-event.js  initAnim()  — the elements GSAP actually animates
 *   2. portal/bootstrap.php    unstrand()  — the anti-strand net
 *   3. portal/styles-lightbox.php          — the CSS visibility floor
 *
 * Both mirrors carried a "keep in sync manually" comment, which is a defect
 * with a note attached, not a design. Adding one part to the single-event
 * template silently produced a section that animates but has no safety net, or
 * a net that covers a selector nothing renders. PHP now owns the list, emits
 * the CSS from it and hands it to JS on window.APOLLO_EVENT_REVEAL.
 *
 * ANIM  — what initAnim() drives (enter + reverse on leave).
 * FLOOR — ANIM plus the elements animated by their own dedicated scenes
 *         (.ev-dj via bindDjRail, [data-reveal-word] via the word stagger) and
 *         the legacy .ev-reveal hook. Everything here must be guaranteed
 *         readable if its trigger never resolves.
 */
if ( ! defined( 'APOLLO_EVENT_REVEAL_ANIM' ) ) {
	define(
		'APOLLO_EVENT_REVEAL_ANIM',
		array(
			'.ev-fact',
			'.ev-panel',
			'[data-ev="access"]',
			'[data-ev="about"]',
			'.ev-spotify',
			'.ev-gallery',
			'.v-slider',
			'.ev-map',
			'.ev-vbody',
		)
	);
}

if ( ! defined( 'APOLLO_EVENT_REVEAL_FLOOR' ) ) {
	define(
		'APOLLO_EVENT_REVEAL_FLOOR',
		array_merge(
			array( '.ev-reveal' ),
			APOLLO_EVENT_REVEAL_ANIM,
			array( '.ev-dj', '[data-reveal-word]' )
		)
	);
}

/**
 * The reveal selector contract, in the shape each consumer needs.
 *
 * @return array{anim:string[],floor:string[],animSel:string,floorSel:string}
 */
function apollo_event_reveal_contract(): array {
	return array(
		'anim'     => APOLLO_EVENT_REVEAL_ANIM,
		'floor'    => APOLLO_EVENT_REVEAL_FLOOR,
		'animSel'  => implode( ',', APOLLO_EVENT_REVEAL_ANIM ),
		'floorSel' => implode( ',', APOLLO_EVENT_REVEAL_FLOOR ),
	);
}

/**
 * Build a CSS selector list by prefixing every reveal selector with a scope.
 *
 * apollo_event_reveal_css_selector( '.ev-lb.ev-lb.is-open ' ) yields the floor
 * rule's left-hand side. Emitted by styles-lightbox.php so the CSS can never
 * fall behind the JS.
 *
 * @param string $scope  Scope prefix, trailing space included.
 * @param string $indent Indent applied to each line after the first.
 * @return string
 */
function apollo_event_reveal_css_selector( string $scope = '', string $indent = '' ): string {
	$out = array();
	foreach ( APOLLO_EVENT_REVEAL_FLOOR as $sel ) {
		$out[] = $scope . $sel;
	}
	return implode( ",\n" . $indent, $out );
}

/**
 * Inline <script> that publishes the contract to the two JS consumers.
 *
 * Printed by both boot paths. Idempotent by assignment — a second copy simply
 * writes the same object.
 *
 * @return string
 */
function apollo_event_reveal_js_config(): string {
	$out = '<script>window.APOLLO_EVENT_REVEAL=' . wp_json_encode(
		array(
			'anim'  => implode( ',', APOLLO_EVENT_REVEAL_ANIM ),
			'floor' => implode( ',', APOLLO_EVENT_REVEAL_FLOOR ),
		)
	) . ';';

	/*
	 * The surface table (apollo-core). This is what lets ONE lightbox runtime
	 * serve events, DJs and locs: the JS looks the endpoint up by type instead
	 * of hard-coding a path, so registering a surface in PHP is the only step.
	 * Absent when apollo-core predates the contract — the runtime then falls
	 * back to the event endpoint and behaves exactly as it always did.
	 */
	if ( function_exists( 'apollo_surface_js_config' ) ) {
		$out .= 'window.APOLLO_SURFACES=' . wp_json_encode( apollo_surface_js_config() ) . ';';
	}

	return $out . '</script>';
}

/**
 * Parts rendered before <main class="ev-wrap">.
 *
 * @return string[]
 */
function apollo_event_single_before_main_parts(): array {
	return array( 'hero' );
}

/**
 * Parts rendered after </main>.
 *
 * 'styles' and 'scripts' are listed so that, if a caller ever requests them
 * explicitly, they land outside <main> instead of inside the content column.
 *
 * @return string[]
 */
function apollo_event_single_after_main_parts(): array {
	return array( 'footer-visual', 'chat', 'styles', 'scripts' );
}

/**
 * Namespaced element id — keeps ids unique when a second instance of the
 * page is injected into a host document (lightbox over a single event page).
 *
 * @param string $name   Base id.
 * @param string $prefix Instance prefix (from $ev['uid']).
 * @return string
 */
function apollo_ev_id( string $name, string $prefix = '' ): string {
	return '' === $prefix ? $name : $prefix . $name;
}

/**
 * Can the current viewer see this event?
 *
 * Mirrors WP visibility rules and honours _event_privacy. Used by every entry
 * point, including the public REST fragment.
 *
 * @param int $post_id Event id.
 * @return bool
 */
function apollo_event_single_can_view( int $post_id ): bool {
	$post = get_post( $post_id );
	if ( ! $post || 'event' !== $post->post_type ) {
		return false;
	}

	if ( 'publish' !== $post->post_status ) {
		return current_user_can( 'read_post', $post_id );
	}

	$privacy = (string) get_post_meta( $post_id, '_event_privacy', true );
	if ( 'private' === $privacy || 'privado' === $privacy ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( (int) $post->post_author === get_current_user_id() ) {
			return true;
		}
		if ( function_exists( 'apollo_event_user_is_coauthor' ) && apollo_event_user_is_coauthor( $post_id, get_current_user_id() ) ) {
			return true;
		}
		return current_user_can( 'edit_post', $post_id );
	}

	return true;
}

/**
 * Build the full render context for one event.
 *
 * Every template part reads from this array (extracted into scope), so adding
 * a datum means touching exactly one function.
 *
 * @param int    $post_id Event id.
 * @param string $uid     Instance prefix for element ids.
 * @return array<string,mixed>
 */
function apollo_event_single_context( int $post_id, string $uid = '' ): array {
	static $cache = array();

	$cache_key = $post_id . '|' . $uid;
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
		apollo_event_ensure_helpers();
	}

	if ( function_exists( 'apollo_event_heal_cover' ) ) {
		apollo_event_heal_cover( $post_id );
	}

	$post    = get_post( $post_id );
	$title   = $post ? (string) $post->post_title : '';
	$content = $post ? (string) apply_filters( 'the_content', $post->post_content ) : '';

	$start_date = (string) get_post_meta( $post_id, '_event_start_date', true );
	$end_date   = (string) get_post_meta( $post_id, '_event_end_date', true );
	$start_time = (string) get_post_meta( $post_id, '_event_start_time', true );
	$end_time   = (string) get_post_meta( $post_id, '_event_end_time', true );

	$parsed_date = function_exists( 'apollo_event_parse_date' )
		? apollo_event_parse_date( $start_date ?: current_time( 'Y-m-d' ) )
		: array(
			'day'        => gmdate( 'd' ),
			'month_pt'   => gmdate( 'M' ),
			'timestamp'  => time(),
			'iso_date'   => gmdate( 'Y-m-d' ),
			'weekday_pt' => gmdate( 'D' ),
		);

	$loc = function_exists( 'apollo_event_get_loc' ) ? apollo_event_get_loc( $post_id ) : null;
	$djs = function_exists( 'apollo_event_get_djs' ) ? apollo_event_get_djs( $post_id ) : array();

	$dj_slots = get_post_meta( $post_id, '_event_dj_slots', true );
	if ( ! is_array( $dj_slots ) ) {
		$dj_slots = array();
	}

	$banner = function_exists( 'apollo_event_get_banner' ) ? apollo_event_get_banner( $post_id, 'full' ) : '';

	$bg_color = (string) get_post_meta( $post_id, '_event_bg_color', true );
	$bg_color = ( $bg_color && preg_match( '/^#[0-9a-fA-F]{3,6}$/', $bg_color ) ) ? $bg_color : '#0a0a0a';

	$video_url = (string) get_post_meta( $post_id, '_event_video_url', true );
	$audio_url = (string) get_post_meta( $post_id, '_event_audio_url', true );

	$gallery_ids = get_post_meta( $post_id, '_event_gallery', true );
	if ( ! is_array( $gallery_ids ) ) {
		$gallery_ids = array();
	}

	$is_gone = function_exists( 'apollo_event_is_gone' ) ? apollo_event_is_gone( $post_id ) : false;

	$sound_tax = defined( 'APOLLO_EVENT_TAX_SOUND' ) ? APOLLO_EVENT_TAX_SOUND : 'sound';
	$sounds    = taxonomy_exists( $sound_tax )
		? wp_get_post_terms( $post_id, $sound_tax, array( 'fields' => 'names' ) )
		: array();
	if ( is_wp_error( $sounds ) ) {
		$sounds = array();
	}

	/* YouTube id from the stored URL (ambient hero layer). */
	$youtube_id = '';
	if ( $video_url && preg_match( '~(?:youtu\.be/|v=|embed/)([A-Za-z0-9_-]{6,})~', $video_url, $ym ) ) {
		$youtube_id = $ym[1];
	}

	/* Hero title lines — last short token becomes the accent. */
	$title_parts  = preg_split( '/\s+/', trim( $title ) ) ?: array( $title );
	$title_accent = '';
	if ( count( $title_parts ) > 1 ) {
		$last = $title_parts[ count( $title_parts ) - 1 ];
		if ( preg_match( '/^(Vol\.?|V\.|#)?\d+/i', $last ) || mb_strlen( $last ) <= 6 ) {
			$title_accent = array_pop( $title_parts );
		}
	}

	$year_fact = $start_date ? gmdate( 'Y', (int) strtotime( $start_date ) ) : gmdate( 'Y' );
	$loc_name  = (string) ( $loc['title'] ?? '' );

	/* Venue slider photos — loc gallery first, event media as fallback. */
	$venue_photos = array();
	if ( ! empty( $loc['gallery'] ) && is_array( $loc['gallery'] ) ) {
		foreach ( array_slice( $loc['gallery'], 0, 6 ) as $src ) {
			if ( is_string( $src ) && '' !== $src ) {
				$venue_photos[] = $src;
			}
		}
	}
	if ( empty( $venue_photos ) ) {
		if ( $banner ) {
			$venue_photos[] = $banner;
		}
		foreach ( array_slice( $gallery_ids, 0, 5 ) as $gid ) {
			/* Entries are attachment ids OR absolute URLs (external images). */
			$url = apollo_event_image_url( $gid, 'large' );
			if ( $url ) {
				$venue_photos[] = $url;
			}
		}
	}

	$ctx = array(
		'uid'          => $uid,
		'post_id'      => $post_id,
		'title'        => $title,
		'content'      => $content,
		'permalink'    => (string) get_permalink( $post_id ),
		'start_date'   => $start_date,
		'end_date'     => $end_date,
		'start_time'   => $start_time,
		'end_time'     => $end_time,
		'parsed_date'  => $parsed_date,
		'loc'          => $loc,
		'loc_name'     => $loc_name,
		'loc_sub'      => (string) ( $loc['city'] ?? ( $loc['address'] ?? '' ) ),
		'djs'          => $djs,
		'dj_slots'     => $dj_slots,
		'banner'       => $banner,
		'bg_color'     => $bg_color,
		'video_url'    => $video_url,
		'audio_url'    => $audio_url,
		'youtube_id'   => $youtube_id,
		'gallery_ids'  => $gallery_ids,
		'venue_photos' => $venue_photos,
		'is_gone'      => $is_gone,
		'sounds'       => $sounds,
		'title_parts'  => $title_parts,
		'title_accent' => $title_accent,
		'date_fact'    => strtoupper( $parsed_date['day'] . ' ' . $parsed_date['month_pt'] ),
		'year_fact'    => $year_fact,
		'time_sub'     => $end_time ? ( '→ ' . $end_time ) : '',
		'meta_date'    => $parsed_date['day'] . ' ' . $parsed_date['month_pt'] . ' ’' . substr( (string) $year_fact, -2 ),
		'meta_time'    => trim( $start_time . ( $end_time ? ' — ' . $end_time : '' ) ),
		'access'       => function_exists( 'apollo_event_build_access_payload' )
			? apollo_event_build_access_payload( $post_id )
			: array(),
	);

	/**
	 * Filter the single-event render context before any part runs.
	 *
	 * @param array $ctx     Context.
	 * @param int   $post_id Event id.
	 */
	$ctx = (array) apply_filters( 'apollo_event_single_context', $ctx, $post_id );

	$cache[ $cache_key ] = $ctx;

	return $ctx;
}

/**
 * Absolute path to the single-event template parts directory.
 *
 * Honours the theme override → active style pack → base lookup order used by
 * TemplateLoader, so a style pack can ship its own part without forking.
 *
 * @param string $part Part slug, e.g. 'hero'.
 * @return string Readable path, or '' when the part does not exist.
 */
function apollo_event_single_part_path( string $part ): string {
	$part = sanitize_file_name( $part );
	if ( '' === $part ) {
		return '';
	}

	$dir       = defined( 'APOLLO_EVENT_DIR' ) ? APOLLO_EVENT_DIR : dirname( __DIR__ ) . '/';
	$style     = function_exists( 'apollo_event_get_active_style' ) ? apollo_event_get_active_style() : 'base';
	$relative  = 'template-parts/single/' . $part . '.php';
	$candidates = array(
		get_stylesheet_directory() . '/apollo-events/' . $relative,
		$dir . 'styles/' . $style . '/' . $relative,
		$dir . 'styles/base/' . $relative,
	);

	foreach ( $candidates as $candidate ) {
		if ( is_readable( $candidate ) ) {
			return $candidate;
		}
	}

	return '';
}

/**
 * Render a single template part with the context in scope.
 *
 * @param string              $part Part slug.
 * @param array<string,mixed> $ev   Render context.
 * @return string
 */
function apollo_event_render_part( string $part, array $ev ): string {
	$path = apollo_event_single_part_path( $part );
	if ( '' === $path ) {
		return '';
	}

	/*
	 * Legacy parts read loose variables ($title, $loc, $djs …). Extracting the
	 * context keeps every existing part working while new parts can read the
	 * canonical $ev array.
	 */
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( $ev, EXTR_SKIP );

	ob_start();
	require $path;
	return (string) ob_get_clean();
}

/**
 * Render the single event body.
 *
 * @param int                 $post_id Event id.
 * @param array<string,mixed> $args    {
 *     @type string   $mode  'page' | 'inline' | 'fragment'. Default 'inline'.
 *     @type string[] $parts Part slugs to render. Default APOLLO_EVENT_SINGLE_PARTS.
 *     @type string   $uid   Element id prefix. Auto-generated for fragments.
 *     @type bool     $wrap  Emit the .ev-root wrapper. Default true outside page mode.
 * }
 * @return string HTML.
 */
function apollo_event_render_single( int $post_id, array $args = array() ): string {
	$args = wp_parse_args(
		$args,
		array(
			'mode'  => 'inline',
			'parts' => APOLLO_EVENT_SINGLE_PARTS,
			'uid'   => '',
			'wrap'  => null,
		)
	);

	$mode = in_array( $args['mode'], array( 'page', 'inline', 'fragment' ), true ) ? $args['mode'] : 'inline';

	if ( ! apollo_event_single_can_view( $post_id ) ) {
		return '';
	}

	if ( '' === $args['uid'] && 'page' !== $mode ) {
		$args['uid'] = 'e' . $post_id . '-';
	}

	$wrap = null === $args['wrap'] ? ( 'page' !== $mode ) : (bool) $args['wrap'];

	$ev = apollo_event_single_context( $post_id, (string) $args['uid'] );

	$parts = array_values(
		array_intersect(
			(array) $args['parts'],
			array_merge( APOLLO_EVENT_SINGLE_PARTS, array( 'styles', 'scripts' ) )
		)
	);

	$before_keys = apollo_event_single_before_main_parts();
	$after_keys  = apollo_event_single_after_main_parts();

	$before = '';
	$main   = '';
	$after  = '';

	foreach ( $parts as $part ) {
		$rendered = apollo_event_render_part( $part, $ev );
		if ( '' === $rendered ) {
			continue;
		}
		if ( in_array( $part, $before_keys, true ) ) {
			$before .= $rendered;
		} elseif ( in_array( $part, $after_keys, true ) ) {
			$after .= $rendered;
		} else {
			$main .= $rendered;
		}
	}

	$html = $before
		. ( '' !== $main ? '<main class="ev-wrap">' . $main . '</main>' : '' )
		. $after;

	if ( $wrap ) {
		/*
		 * Per-instance config travels inside the root as inert JSON — no globals,
		 * so N embedded instances never fight over window.APOLLO_SINGLE_EVENT.
		 */
		$config = sprintf(
			'<script type="application/json" data-ev-config>%s</script>',
			wp_json_encode( apollo_event_single_js_config( $post_id, $ev ) )
		);

		$html = sprintf(
			'<div class="ev-root%s" data-ev-root data-ev-id="%d" data-ev-uid="%s" data-ev-mode="%s">%s%s</div>',
			'fragment' === $mode ? ' is-embed' : '',
			$post_id,
			esc_attr( (string) $args['uid'] ),
			esc_attr( $mode ),
			$config,
			$html
		);
	}

	/**
	 * Filter the rendered single-event HTML.
	 *
	 * @param string $html    Rendered markup.
	 * @param int    $post_id Event id.
	 * @param array  $args    Render args.
	 */
	return (string) apply_filters( 'apollo_event_render_single', $html, $post_id, $args );
}

/**
 * ONE print-once ledger for every lightbox path (P2, 2026-08-07).
 *
 * Three entry points can now bring the lightbox into a document:
 *
 *   · apollo_event_lightbox_boot()      inline, for blank-canvas templates that
 *                                       never run wp_head/wp_footer (/eventos)
 *   · apollo_event_lightbox_enqueue()   the public wp_enqueue API (P1)
 *   · the wp_footer auto-boot           whatever enqueued it, anywhere (P2)
 *
 * Each used to carry its own `static $printed`, which meant they could not see
 * one another: /eventos calls boot() directly, so an unguarded footer hook
 * would print a SECOND [data-ev-lightbox] and the runtime's
 * `querySelector('[data-ev-lightbox]')` would bind the wrong (empty) shell.
 * A single ledger makes "already handled" observable across all three.
 *
 * @param string|null $mark Flag to raise: shell|assets|css|armed.
 * @return array<string,bool>
 */
function apollo_event_lightbox_state( ?string $mark = null ): array {
	static $state = array(
		'shell'  => false, // [data-ev-lightbox] markup is in the document.
		'assets' => false, // Runtimes + base CSS are on their way (either path).
		'css'    => false, // The full-viewport override cell has been emitted.
		'armed'  => false, // Something asked for the footer auto-boot.
	);
	if ( null !== $mark && array_key_exists( $mark, $state ) ) {
		$state[ $mark ] = true;
	}
	return $state;
}

/**
 * Print the lightbox shell once per document.
 *
 * Any element carrying data-ev-open="{event_id}" opens it. The shell is inert
 * until first use — the body is fetched from the REST fragment endpoint.
 *
 * @return void
 */
function apollo_event_lightbox_shell(): void {
	$state = apollo_event_lightbox_state();
	if ( $state['shell'] ) {
		return;
	}
	apollo_event_lightbox_state( 'shell' );
	?>
<div class="ev-lb" id="evLightbox" data-ev-lightbox aria-hidden="true" hidden>
	<div class="ev-lb-bd" data-ev-lb-close></div>
	<div class="ev-lb-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Evento', 'apollo-events' ); ?>">
		<button type="button" class="ev-lb-x" data-ev-lb-close aria-label="<?php esc_attr_e( 'Fechar', 'apollo-events' ); ?>">
			<i class="ri-close-line"></i>
		</button>
		<?php
		/*
		 * data-lenis-prevent IS THE SCROLL FIX (2026-08-01).
		 *
		 * Lenis (booted by core.js) binds a NON-PASSIVE wheel/touch listener to
		 * the window and calls preventDefault() on every event so it can drive
		 * scrolling itself. That kills native scrolling in ANY nested overflow
		 * container — this panel included. Worse, Lenis preventDefaults
		 * *especially* while stopped: lenis.stop() is implemented as "swallow
		 * all scroll input", which is exactly what the portal's modal lock
		 * calls. So the lightbox was doubly frozen: the panel could not scroll
		 * natively, and Lenis was not scrolling it either because Lenis only
		 * ever drives the document.
		 *
		 * data-lenis-prevent is Lenis's own documented opt-out. It is checked
		 * against the event's composedPath BEFORE the isStopped branch, so a
		 * marked subtree keeps native scrolling even while the page is locked —
		 * which is precisely the behaviour a full-screen overlay needs.
		 * overscroll-behavior:contain (CSS) then stops the gesture chaining out
		 * to the document when the panel hits its end.
		 */
		?>
		<div class="ev-lb-scroll" data-ev-lb-scroll data-lenis-prevent tabindex="-1">
			<div class="ev-lb-body" data-ev-lb-body></div>
			<div class="ev-lb-load" data-ev-lb-load aria-hidden="true"><span></span><span></span><span></span></div>
		</div>
	</div>
</div>
	<?php
}

/**
 * Enqueue the shared single-event assets (page, inline and lightbox all use
 * exactly the same CSS/JS bundle).
 *
 * @param bool $with_lightbox Also load the lightbox controller.
 * @return void
 */
function apollo_event_enqueue_single_assets( bool $with_lightbox = false ): void {
	if ( ! defined( 'APOLLO_EVENT_URL' ) ) {
		return;
	}
	$ver = defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.1.0';

	// cdn.jsdelivr.net, not unpkg.com — see the CSP audit note on
	// apollo_event_lightbox_boot() below for how this was found.
	wp_enqueue_style(
		'leaflet',
		'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
		array(),
		'1.9.4'
	);
	wp_enqueue_style(
		'apollo-single-event',
		APOLLO_EVENT_URL . 'assets/css/apollo-single-event.css',
		array( 'leaflet' ),
		apollo_event_asset_ver( 'assets/css/apollo-single-event.css' )
	);
	wp_enqueue_script(
		'leaflet',
		'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
		array(),
		'1.9.4',
		true
	);
	wp_enqueue_script(
		'apollo-single-event',
		APOLLO_EVENT_URL . 'assets/js/apollo-single-event.js',
		array( 'leaflet' ),
		apollo_event_asset_ver( 'assets/js/apollo-single-event.js' ),
		true
	);

	if ( $with_lightbox ) {
		wp_enqueue_script(
			'apollo-event-lightbox',
			APOLLO_EVENT_URL . 'assets/js/apollo-event-lightbox.js',
			array( 'apollo-single-event' ),
			apollo_event_asset_ver( 'assets/js/apollo-event-lightbox.js' ),
			true
		);
		wp_localize_script(
			'apollo-event-lightbox',
			'APOLLO_EVENT_LB',
			array(
				'rest'  => esc_url_raw( rest_url( 'apollo/v1/eventos/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
		wp_localize_script(
			'apollo-event-lightbox',
			'APOLLO_EVENT_REVEAL',
			array(
				'anim'  => implode( ',', APOLLO_EVENT_REVEAL_ANIM ),
				'floor' => implode( ',', APOLLO_EVENT_REVEAL_FLOOR ),
			)
		);
		// Surface table — see apollo_event_reveal_js_config() for why it exists.
		if ( function_exists( 'apollo_surface_js_config' ) ) {
			wp_localize_script( 'apollo-event-lightbox', 'APOLLO_SURFACES', apollo_surface_js_config() );
		}
		/*
		 * Only the LIGHTBOX-bearing call claims the ledger. Claiming it on the
		 * bare-assets call ($with_lightbox = false, which /evento/{slug} uses)
		 * would make a later apollo_event_lightbox_enqueue() see "already
		 * loaded" and return without ever queuing the lightbox runtime.
		 */
		apollo_event_lightbox_state( 'assets' );
	}
}

/**
 * The full-viewport lightbox override CSS, as a string, once per request.
 *
 * The rules live in the portal style cell, which stays their single owner —
 * this only captures them so the wp_enqueue path can ship them to screens that
 * never load the portal cascade. Despite the filename the cell is NOT portal-
 * specific: every selector in it is `.ev-lb.ev-lb …`, i.e. it describes the
 * shared shell, and without it the lightbox renders as the base sheet's 560px
 * centred card instead of the full-screen page.
 *
 * @return string CSS with the <style> wrapper stripped; '' if already emitted.
 */
function apollo_event_lightbox_styles(): string {
	$state = apollo_event_lightbox_state();
	if ( $state['css'] ) {
		return '';
	}

	$file = defined( 'APOLLO_EVENT_DIR' )
		? APOLLO_EVENT_DIR . 'styles/base/template-parts/archive/portal/styles-lightbox.php'
		: '';
	if ( '' === $file || ! is_readable( $file ) ) {
		return '';
	}

	/*
	 * The ledger is claimed by the CELL, not here — styles.php's cascade
	 * requires it directly on /eventos without passing through this function,
	 * so the cell has to be the one that says "mine". This returns '' in that
	 * case because the early-returning cell emits nothing.
	 */
	ob_start();
	require $file;
	$css = (string) ob_get_clean();

	// The cell emits a complete <style> block; wp_add_inline_style wants bare CSS.
	$css = preg_replace( '#</?style[^>]*>#i', '', $css );

	return trim( (string) $css );
}

/**
 * P1 — PUBLIC ENQUEUE API.
 *
 * The one call any other plugin makes to get the event lightbox on its screen.
 * Nothing else is required: after this, every element in the document carrying
 * `data-ev-open="{id}"` opens the full single-event page in place, and
 * `ApolloEventLightbox.open( id )` works programmatically.
 *
 * Contract for the caller's markup — see also _sandbox/PORTAL-MAP.md:
 *
 *   <a href="{real permalink}" data-ev-open="{event id}">…</a>
 *
 * The href is not decorative. The runtime falls back to it when the fragment
 * request fails, and it is what preserves middle-click, ctrl-click and
 * "open in new tab". A card with data-ev-open and no href is a broken card.
 *
 * Call from wp_enqueue_scripts (or any time before wp_footer). Idempotent:
 * safe to call from ten widgets on the same page.
 *
 * @since 1.7.1
 * @return void
 */
function apollo_event_lightbox_enqueue(): void {
	if ( ! defined( 'APOLLO_EVENT_URL' ) ) {
		return;
	}

	// Arm the wp_footer auto-boot (P2) even if the assets were already queued.
	apollo_event_lightbox_state( 'armed' );

	$state = apollo_event_lightbox_state();
	if ( $state['assets'] ) {
		return;
	}

	apollo_event_enqueue_single_assets( true );

	/*
	 * wp_add_inline_style attaches to the base sheet's handle, so the overrides
	 * land immediately after apollo-single-event.css in the emitted <head> and
	 * the cascade order is guaranteed by WP rather than by luck. Skipped when
	 * the portal cascade already printed the cell this request.
	 */
	$css = apollo_event_lightbox_styles();
	if ( '' !== $css ) {
		wp_add_inline_style( 'apollo-single-event', $css );
	}
}

/**
 * P2 — auto-boot the shell on wp_footer.
 *
 * Prints [data-ev-lightbox] for any request that enqueued the lightbox, so a
 * card rendered by an Elementor widget, the feed, or a plugin that has never
 * heard of this template still opens. Runs late (20) so a template calling
 * apollo_event_lightbox_boot() inline near </body> has already claimed the
 * ledger and this becomes a no-op — the double-shell failure the runtime's
 * single querySelector('[data-ev-lightbox]') cannot survive.
 *
 * @return void
 */
function apollo_event_lightbox_footer_boot(): void {
	$state = apollo_event_lightbox_state();
	if ( ! $state['armed'] || $state['shell'] ) {
		return;
	}
	apollo_event_lightbox_shell();
	echo apollo_event_reveal_js_config(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode'd constants.
}
add_action( 'wp_footer', 'apollo_event_lightbox_footer_boot', 20 );

/**
 * Declare the event as an Apollo surface (2026-08-07).
 *
 * ADAPTER ONLY — nothing about how events render changes. This plugin already
 * had every piece of the contract; registering here is what lets apollo-core
 * hand the SAME machinery to apollo-djs and apollo-loc, which had none of it,
 * instead of each growing its own divergent copy.
 *
 * The legacy route (apollo/v1/eventos/{id}/fragmento, registered below) stays
 * exactly as it was — /eventos, /portal and every existing data-ev-open card
 * keep hitting it. The surface adds apollo/v1/eventos/{id}/fragmento's generic
 * twin under the same rest_base, so both spellings resolve to the same
 * renderer. No card has to change to keep working; new cards can use the
 * generic data-ap-open="event:{id}" form.
 *
 * @return void
 */
function apollo_event_register_surface(): void {
	if ( ! function_exists( 'apollo_surface_register' ) ) {
		return; // apollo-core older than the contract — legacy path still works.
	}

	apollo_surface_register(
		'event',
		array(
			'post_type' => 'event',
			'rest_base' => 'eventos',
			'can_view'  => 'apollo_event_single_can_view',
			'enqueue'   => 'apollo_event_lightbox_enqueue',
			/* 'fragment' — the mode the working REST endpoint already uses, so
			   the generic path renders byte-identical output to the legacy one. */
			'renderer'  => static function ( int $post_id, array $args = array() ): string {
				return apollo_event_render_single(
					$post_id,
					wp_parse_args(
						$args,
						array(
							'mode'  => 'fragment',
							'parts' => APOLLO_EVENT_SINGLE_PARTS,
						)
					)
				);
			},
		)
	);
}
add_action( 'init', 'apollo_event_register_surface', 20 );

/**
 * Print everything the lightbox needs, inline.
 *
 * Blank-canvas templates do not always run wp_head/wp_footer, so this helper
 * emits the shell + assets directly. Call it once, near </body>, from any
 * template that renders elements carrying data-ev-open="{id}".
 *
 * Example (archive, portal, cards, a widget, anywhere):
 *   <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" data-ev-open="<?php echo (int) $id; ?>">…</a>
 *   …
 *   <?php apollo_event_lightbox_boot(); ?>
 *
 * CSP AUDIT (2026-08-01) — this is the actual source of the "Refused to load
 * .../unpkg.com/leaflet…" console errors reported on /eventos. archive-event.php
 * calls this unconditionally near </main> so single-event cards can open in a
 * lightbox — which means it prints Leaflet on EVERY portal load, map or no map
 * in view. The blank-canvas CSP's script-src/style-src allowlist
 * cdn.apollo.rio.br and cdn.jsdelivr.net but not unpkg.com, so this tag was
 * being silently dropped by the browser on every single request. Swapped to
 * cdn.jsdelivr.net's npm mirror, which is already trusted and serves the
 * byte-identical 1.9.4 package (same SRI hash, no host-specific content).
 *
 * @return void
 */
function apollo_event_lightbox_boot(): void {
	$state = apollo_event_lightbox_state();
	if ( $state['assets'] ) {
		return;
	}
	apollo_event_lightbox_state( 'assets' );

	/*
	 * PER-FILE VERSION, NOT THE PLUGIN CONSTANT (2026-08-07).
	 *
	 * These tags used to stamp `?v=APOLLO_EVENT_VERSION` — a hand-bumped string
	 * that had sat at 1.7.0 across many deploys. This folder deploys on save,
	 * so the file changes and the URL does not: every returning browser kept
	 * serving the CACHED apollo-single-event.js / apollo-event-lightbox.js
	 * indefinitely. Fixes shipped, verified on disk, and were invisible in the
	 * browser — including, today, the blank-panel fix, which is how this was
	 * found.
	 *
	 * apollo_event_asset_ver() is filemtime-based and is already what the
	 * wp_enqueue path uses; the inline path was simply never updated to match.
	 */
	$url = defined( 'APOLLO_EVENT_URL' ) ? APOLLO_EVENT_URL : '';
	$ver = static function ( string $rel ): string {
		return function_exists( 'apollo_event_asset_ver' )
			? (string) apollo_event_asset_ver( $rel )
			: ( defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.1.0' );
	};

	apollo_event_lightbox_shell();
	?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"
	integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="stylesheet" href="<?php echo esc_url( $url . 'assets/css/apollo-single-event.css?v=' . rawurlencode( $ver( 'assets/css/apollo-single-event.css' ) ) ); ?>">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"
	integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>window.APOLLO_EVENT_LB = <?php
	echo wp_json_encode(
		array(
			'rest'  => esc_url_raw( rest_url( 'apollo/v1/eventos/' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		)
	);
	?>;</script>
<?php
	// P3 — the reveal selector contract, same object the enqueue path localizes.
	echo apollo_event_reveal_js_config(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode'd constants.

	/*
	 * The full-viewport override cell. On /eventos the portal cascade has
	 * already emitted it and this returns '' — on any OTHER screen booting
	 * inline (a widget, the feed, a bare template) it is the only thing that
	 * stops the lightbox rendering as the base sheet's 560px centred card.
	 */
	$lb_css = apollo_event_lightbox_styles();
	if ( '' !== $lb_css ) {
		echo '<style id="apollo-event-lightbox-overrides">' . $lb_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS from a template cell.
	}

	/*
	 * REAL <script defer src>, NOT type="text/apollo-defer" (2026-08-01).
	 *
	 * These two tags used to be emitted as:
	 *   <script type="text/apollo-defer" data-apollo-when="ready" src="…">
	 *
	 * A non-standard `type` means the BROWSER never executes the tag — it is
	 * inert markup, promoted later by core.js's runDeferredPageScripts(). That
	 * promoter only re-executes INLINE script bodies; a tag whose textContent is
	 * empty and whose payload lives behind `src` is skipped entirely. So
	 * apollo-single-event.js and apollo-event-lightbox.js were NEVER LOADED on
	 * any page that booted the lightbox this way.
	 *
	 * Downstream symptom, and the reason "click a card" appeared to do the wrong
	 * thing for so long: window.ApolloEventLightbox never came into existence,
	 * so the portal's openModal() always fell through to its small #pevModal
	 * quick-view instead of the documented full single-event fragment. The bug
	 * looked like a portal/UI problem; it was a script that never ran.
	 *
	 * `defer` is correct and sufficient: apollo-single-event.js already gates
	 * its own boot on apollo:ready (Apollo.whenReady, with an Apollo.isReady
	 * check for the already-fired case) and apollo-event-lightbox.js only
	 * defines a global plus one delegated listener. Neither touches GSAP or
	 * Lenis at parse time, so nothing here needs core.js to have finished — the
	 * deferred-type wrapper was never buying anything, and it cost us the whole
	 * feature.
	 */
	?>
<script defer src="<?php echo esc_url( $url . 'assets/js/apollo-single-event.js?v=' . rawurlencode( $ver( 'assets/js/apollo-single-event.js' ) ) ); ?>"></script>
<script defer src="<?php echo esc_url( $url . 'assets/js/apollo-event-lightbox.js?v=' . rawurlencode( $ver( 'assets/js/apollo-event-lightbox.js' ) ) ); ?>"></script>
	<?php
}

/**
 * Shortcode: [apollo_event_single id="123" mode="inline"]
 *
 * @param array<string,string>|string $atts Attributes.
 * @return string
 */
function apollo_event_single_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'id'    => '0',
			'mode'  => 'inline',
			'parts' => '',
		),
		(array) $atts,
		'apollo_event_single'
	);

	$post_id = absint( $atts['id'] );
	if ( ! $post_id ) {
		$post_id = (int) get_the_ID();
	}
	if ( ! $post_id ) {
		return '';
	}

	$parts = '' !== $atts['parts']
		? array_map( 'sanitize_key', array_filter( array_map( 'trim', explode( ',', $atts['parts'] ) ) ) )
		: APOLLO_EVENT_SINGLE_PARTS;

	/*
	 * P1 — the shortcode is now just another consumer of the public API. It
	 * used to hand-roll the pair (enqueue + its own wp_footer shell hook),
	 * which is the duplication that made the lightbox un-loadable from
	 * anywhere else. The API arms the shared footer boot instead.
	 */
	apollo_event_lightbox_enqueue();

	return apollo_event_render_single(
		$post_id,
		array(
			'mode'  => 'inline',
			'parts' => $parts,
		)
	);
}
add_shortcode( 'apollo_event_single', 'apollo_event_single_shortcode' );

/**
 * REST: GET /apollo/v1/eventos/{id}/fragmento
 *
 * Returns the rendered single-event body for lightbox injection. Read-only,
 * public by design (same visibility as the /evento/{slug} page it mirrors),
 * gated by apollo_event_single_can_view().
 *
 * @return void
 */
function apollo_event_register_fragment_route(): void {
	register_rest_route(
		defined( 'APOLLO_EVENT_REST_NAMESPACE' ) ? APOLLO_EVENT_REST_NAMESPACE : 'apollo/v1',
		'/eventos/(?P<id>\d+)/fragmento',
		array(
			'methods'             => 'GET',
			'callback'            => 'apollo_event_rest_fragment',
			'permission_callback' => '__return_true',
			'args'                => array(
				'id'    => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
					'validate_callback' => static function ( $value ): bool {
						return absint( $value ) > 0;
					},
				),
				'parts' => array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'apollo_event_register_fragment_route' );

/**
 * REST fragment callback.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function apollo_event_rest_fragment( WP_REST_Request $request ) {
	$post_id = absint( $request->get_param( 'id' ) );

	if ( ! apollo_event_single_can_view( $post_id ) ) {
		return new WP_Error(
			'apollo_event_not_found',
			__( 'Evento não encontrado.', 'apollo-events' ),
			array( 'status' => 404 )
		);
	}

	$requested = (string) $request->get_param( 'parts' );
	$parts     = '' !== $requested
		? array_values(
			array_intersect(
				array_map( 'sanitize_key', array_filter( array_map( 'trim', explode( ',', $requested ) ) ) ),
				APOLLO_EVENT_SINGLE_PARTS
			)
		)
		: APOLLO_EVENT_SINGLE_PARTS;

	if ( empty( $parts ) ) {
		$parts = APOLLO_EVENT_SINGLE_PARTS;
	}

	/* The lightbox provides its own chrome — the hero back-chip is redundant. */
	$html = apollo_event_render_single(
		$post_id,
		array(
			'mode'  => 'fragment',
			'parts' => $parts,
		)
	);

	if ( '' === $html ) {
		return new WP_Error(
			'apollo_event_empty',
			__( 'Evento sem conteúdo renderizável.', 'apollo-events' ),
			array( 'status' => 404 )
		);
	}

	$ev = apollo_event_single_context( $post_id, 'e' . $post_id . '-' );

	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array(
				'id'        => $post_id,
				'title'     => $ev['title'],
				'permalink' => $ev['permalink'],
				'html'      => $html,
				'config'    => apollo_event_single_js_config( $post_id, $ev ),
			),
		)
	);
}

/**
 * Runtime JS config for one instance (shared by page and lightbox).
 *
 * @param int                 $post_id Event id.
 * @param array<string,mixed> $ev      Render context.
 * @return array<string,mixed>
 */
function apollo_event_single_js_config( int $post_id, array $ev = array() ): array {
	if ( empty( $ev ) ) {
		$ev = apollo_event_single_context( $post_id );
	}

	$logged = is_user_logged_in();
	$me     = wp_get_current_user();
	$loc    = is_array( $ev['loc'] ?? null ) ? $ev['loc'] : array();

	return array(
		'id'           => $post_id,
		'uid'          => (string) ( $ev['uid'] ?? '' ),
		'title'        => (string) ( $ev['title'] ?? '' ),
		'shareText'    => wp_strip_all_tags( (string) ( $ev['content'] ?? '' ) ),
		'shareUrl'     => (string) ( $ev['permalink'] ?? '' ),
		'shareImage'   => function_exists( 'apollo_event_get_banner' )
			? (string) apollo_event_get_banner( $post_id, 'full' )
			: '',
		'restRsvp'     => esc_url_raw( rest_url( 'apollo/v1/eventos/' . $post_id . '/participantes' ) ),
		'restComments' => esc_url_raw( rest_url( 'apollo/v1/depoimentos' ) ),
		'loggedIn'     => $logged,
		'loginUrl'     => esc_url_raw( home_url( '/acesso' ) ),
		'nonce'        => wp_create_nonce( 'wp_rest' ),
		'me'           => array(
			'id'     => $logged ? (int) $me->ID : 0,
			'name'   => $logged ? (string) $me->display_name : '',
			'avatar' => $logged ? (string) get_avatar_url( $me->ID, array( 'size' => 64 ) ) : '',
		),
		'venue'        => array(
			'lat'   => isset( $loc['lat'] ) ? (float) $loc['lat'] : null,
			'lng'   => isset( $loc['lng'] ) ? (float) $loc['lng'] : null,
			'title' => (string) ( $ev['loc_name'] ?? '' ),
		),
	);
}
