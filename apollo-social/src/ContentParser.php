<?php

/**
 * Content Parser — extracts URLs, enforces char limit, generates embed data
 *
 * Handles:
 * - 280 character limit (Twitter-style)
 * - Apollo event URL detection → mini event card embed
 * - SoundCloud/Spotify URL detection → mini player embed
 * - YouTube URL detection → mini player embed
 * - URL extraction and hiding from character count
 * - @mention linkification
 * - #hashtag linkification
 *
 * Security: NO image uploads allowed. Posts are text + URL only.
 *
 * @package Apollo\Social
 */

declare(strict_types=1);

namespace Apollo\Social;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ContentParser {


	/**
	 * Maximum character limit for social posts (Twitter-style)
	 */
	public const CHAR_LIMIT = 280;

	/**
	 * Regex patterns for supported embed URLs
	 */
	private const PATTERNS = array(
		'apollo_event'     => '#(?:https?://)?(?:www\.)?(?:apollo\.rio\.br|localhost:\d+)/evento/([a-zA-Z0-9_-]+)/?#i',
		'soundcloud'       => '#(?:https?://)?(?:www\.)?soundcloud\.com/([a-zA-Z0-9_-]+)/([a-zA-Z0-9_-]+)(?:\?[^\s]*)?#i',
		'spotify_track'    => '#(?:https?://)?open\.spotify\.com/(?:intl-[a-z]+/)?track/([a-zA-Z0-9]+)(?:\?[^\s]*)?#i',
		'spotify_album'    => '#(?:https?://)?open\.spotify\.com/(?:intl-[a-z]+/)?album/([a-zA-Z0-9]+)(?:\?[^\s]*)?#i',
		'spotify_playlist' => '#(?:https?://)?open\.spotify\.com/(?:intl-[a-z]+/)?playlist/([a-zA-Z0-9]+)(?:\?[^\s]*)?#i',
		'youtube'          => '#(?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/shorts/)([a-zA-Z0-9_-]{11})(?:[&?\s]|$)#i',
	);

	/**
	 * Generic URL pattern
	 */
	private const URL_PATTERN = '#https?://[^\s<>"\']+#i';

	/**
	 * Parse raw content from user input
	 *
	 * Extracts URLs, validates char limit, prepares embed data.
	 *
	 * @param string $raw_content  Raw text from user
	 * @return array {
	 *     @type bool    $valid         Whether content passes validation
	 *     @type string  $error         Error message if invalid
	 *     @type string  $content       Cleaned content (text only, URLs preserved inline)
	 *     @type string  $text_only     Content without URLs (for char counting)
	 *     @type int     $char_count    Character count (excluding URLs)
	 *     @type array   $embeds        Array of embed data objects
	 *     @type array   $urls          All extracted URLs
	 *     @type array   $mentions      Extracted @mentions
	 *     @type array   $hashtags      Extracted #hashtags
	 * }
	 */
	public static function parse( string $raw_content ): array {
		$content = sanitize_textarea_field( $raw_content );
		$content = trim( $content );

		// Extract all URLs
		$urls = array();
		preg_match_all( self::URL_PATTERN, $content, $url_matches );
		if ( ! empty( $url_matches[0] ) ) {
			$urls = $url_matches[0];
		}

		// Calculate text without URLs (for char limit)
		$text_only = $content;
		foreach ( $urls as $url ) {
			$text_only = str_replace( $url, '', $text_only );
		}
		$text_only = trim( preg_replace( '/\s+/', ' ', $text_only ) );

		$char_count = mb_strlen( $text_only, 'UTF-8' );

		// Validate
		if ( empty( $text_only ) && empty( $urls ) ) {
			return array(
				'valid'      => false,
				'error'      => __( 'Conteúdo vazio.', 'apollo-social' ),
				'content'    => '',
				'text_only'  => '',
				'char_count' => 0,
				'embeds'     => array(),
				'urls'       => array(),
				'mentions'   => array(),
				'hashtags'   => array(),
			);
		}

		if ( $char_count > self::CHAR_LIMIT ) {
			return array(
				'valid'      => false,
				'error'      => sprintf(
					__( 'Limite de %1$d caracteres excedido (%2$d caracteres).', 'apollo-social' ),
					self::CHAR_LIMIT,
					$char_count
				),
				'content'    => $content,
				'text_only'  => $text_only,
				'char_count' => $char_count,
				'embeds'     => array(),
				'urls'       => $urls,
				'mentions'   => array(),
				'hashtags'   => array(),
			);
		}

		// Extract embeds from URLs
		$embeds = array();
		foreach ( $urls as $url ) {
			$embed = self::detect_embed( $url );
			if ( $embed ) {
				$embeds[] = $embed;
			}
		}

		// Extract mentions
		$mentions = array();
		preg_match_all( '/@([a-zA-Z0-9_.]+)/', $content, $mention_matches );
		if ( ! empty( $mention_matches[1] ) ) {
			$mentions = array_unique( $mention_matches[1] );
		}

		// Extract hashtags
		$hashtags = array();
		preg_match_all( '/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u', $content, $hash_matches );
		if ( ! empty( $hash_matches[1] ) ) {
			$hashtags = array_unique( $hash_matches[1] );
		}

		return array(
			'valid'      => true,
			'error'      => '',
			'content'    => $content,
			'text_only'  => $text_only,
			'char_count' => $char_count,
			'embeds'     => $embeds,
			'urls'       => $urls,
			'mentions'   => $mentions,
			'hashtags'   => $hashtags,
		);
	}

	/**
	 * Detect embed type from URL
	 *
	 * @param string $url
	 * @return array|null Embed data or null if not recognized
	 */
	public static function detect_embed( string $url ): ?array {
		// Apollo Event
		if ( preg_match( self::PATTERNS['apollo_event'], $url, $m ) ) {
			return self::build_event_embed( $m[1], $url );
		}

		// SoundCloud
		if ( preg_match( self::PATTERNS['soundcloud'], $url, $m ) ) {
			return array(
				'type'  => 'soundcloud',
				'url'   => $url,
				'user'  => $m[1],
				'track' => $m[2],
				'html'  => self::render_soundcloud_player( $url ),
			);
		}

		// Spotify Track
		if ( preg_match( self::PATTERNS['spotify_track'], $url, $m ) ) {
			return array(
				'type'    => 'spotify',
				'subtype' => 'track',
				'url'     => $url,
				'id'      => $m[1],
				'html'    => self::render_spotify_player( 'track', $m[1] ),
			);
		}

		// Spotify Album
		if ( preg_match( self::PATTERNS['spotify_album'], $url, $m ) ) {
			return array(
				'type'    => 'spotify',
				'subtype' => 'album',
				'url'     => $url,
				'id'      => $m[1],
				'html'    => self::render_spotify_player( 'album', $m[1] ),
			);
		}

		// Spotify Playlist
		if ( preg_match( self::PATTERNS['spotify_playlist'], $url, $m ) ) {
			return array(
				'type'    => 'spotify',
				'subtype' => 'playlist',
				'url'     => $url,
				'id'      => $m[1],
				'html'    => self::render_spotify_player( 'playlist', $m[1] ),
			);
		}

		// YouTube
		if ( preg_match( self::PATTERNS['youtube'], $url, $m ) ) {
			return array(
				'type'     => 'youtube',
				'url'      => $url,
				'video_id' => $m[1],
				'html'     => self::render_youtube_player( $m[1] ),
			);
		}

		return null;
	}

	/**
	 * Build event embed data from slug
	 *
	 * @param string $slug  Event post slug
	 * @param string $url   Original URL
	 * @return array
	 */
	private static function build_event_embed( string $slug, string $url ): array {
		$event = get_page_by_path( $slug, OBJECT, 'event' );

		if ( ! $event ) {
			return array(
				'type'  => 'apollo_event',
				'url'   => $url,
				'slug'  => $slug,
				'found' => false,
				'html'  => '<div class="embed-event-notfound"><span>Evento não encontrado</span></div>',
			);
		}

		$event_id   = $event->ID;
		$title      = $event->post_title;
		$thumbnail  = get_the_post_thumbnail_url( $event_id, 'medium' ) ?: '';
		$date       = get_post_meta( $event_id, '_event_start_date', true ) ?: '';
		$loc_name   = '';
		$categories = array();

		// Get location name
		$loc_id = get_post_meta( $event_id, '_event_loc_id', true );
		if ( $loc_id ) {
			$loc_post = get_post( (int) $loc_id );
			$loc_name = $loc_post ? $loc_post->post_title : '';
		}

		// Event categories
		$terms = wp_get_post_terms( $event_id, 'event_category', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $terms ) ) {
			$categories = $terms;
		}

		// Format date
		$formatted_date = '';
		if ( $date ) {
			$timestamp = strtotime( $date );
			if ( $timestamp ) {
				$formatted_date = wp_date( 'd M Y · H:i', $timestamp );
			}
		}

		$permalink = get_permalink( $event_id ) ?: $url;

		$html = self::render_event_card( $title, $thumbnail, $formatted_date, $loc_name, $categories, $permalink );

		return array(
			'type'       => 'apollo_event',
			'url'        => $url,
			'slug'       => $slug,
			'found'      => true,
			'event_id'   => $event_id,
			'title'      => $title,
			'thumbnail'  => $thumbnail,
			'date'       => $formatted_date,
			'loc'        => $loc_name,
			'categories' => $categories,
			'permalink'  => $permalink,
			'html'       => $html,
		);
	}

	/**
	 * Render mini event card HTML
	 */
	private static function render_event_card(
		string $title,
		string $thumbnail,
		string $date,
		string $loc_name,
		array $categories,
		string $permalink
	): string {
		$cat_html = '';
		if ( ! empty( $categories ) ) {
			$cat_html = '<span class="embed-event-cat">' . esc_html( $categories[0] ) . '</span>';
		}

		$thumb_html = '';
		if ( $thumbnail ) {
			$thumb_html = '<div class="embed-event-thumb"><img src="' . esc_url( $thumbnail ) . '" alt="" loading="lazy"></div>';
		}

		return '<a href="' . esc_url( $permalink ) . '" class="embed-event-card" target="_blank" rel="noopener">'
			. $thumb_html
			. '<div class="embed-event-info">'
			. '<div class="embed-event-title">' . esc_html( $title ) . '</div>'
			. ( $date ? '<div class="embed-event-date"><i class="ri-calendar-event-fill"></i> ' . esc_html( $date ) . '</div>' : '' )
			. ( $loc_name ? '<div class="embed-event-loc"><i class="ri-map-pin-fill"></i> ' . esc_html( $loc_name ) . '</div>' : '' )
			. $cat_html
			. '</div>'
			. '</a>';
	}

	/**
	 * Render SoundCloud mini player
	 */
	private static function render_soundcloud_player( string $url ): string {
		$encoded = urlencode( $url );
		return '<div class="embed-player embed-soundcloud">'
			. '<iframe width="100%" height="166" scrolling="no" frameborder="no" allow="autoplay"'
			. ' src="https://w.soundcloud.com/player/?url=' . $encoded
			. '&color=%23ff5500&auto_play=false&hide_related=true&show_comments=false'
			. '&show_user=true&show_reposts=false&show_teaser=false&visual=false"'
			. ' loading="lazy"></iframe>'
			. '</div>';
	}

	/**
	 * Render Spotify mini player
	 */
	private static function render_spotify_player( string $type, string $id ): string {
		$height = $type === 'track' ? '80' : '152';
		return '<div class="embed-player embed-spotify">'
			. '<iframe src="https://open.spotify.com/embed/' . $type . '/' . esc_attr( $id )
			. '?utm_source=generator&theme=0" width="100%" height="' . $height
			. '" frameBorder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"'
			. ' loading="lazy" style="border-radius:12px;"></iframe>'
			. '</div>';
	}

	/**
	 * Render YouTube mini player
	 *
	 * Uses youtube-nocookie.com for privacy-enhanced mode.
	 *
	 * @param string $video_id  YouTube video ID (11 chars)
	 * @return string HTML
	 */
	private static function render_youtube_player( string $video_id ): string {
		return '<div class="embed-player embed-youtube">'
			. '<iframe src="https://www.youtube-nocookie.com/embed/' . esc_attr( $video_id )
			. '?rel=0&modestbranding=1" width="100%" height="280"'
			. ' frameBorder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"'
			. ' allowfullscreen loading="lazy" style="border-radius:12px;"></iframe>'
			. '</div>';
	}

	/**
	 * Render final post content with linkified mentions, hashtags, and embeds below
	 *
	 * @param string $content  Raw stored content
	 * @param array  $embeds   Pre-parsed embed data (stored as meta or re-parsed)
	 * @return string HTML
	 */
	public static function render_post_html( string $content, array $embeds = array() ): string {
		$html = esc_html( $content );

		// Linkify @mentions
		$html = preg_replace(
			'/@([a-zA-Z0-9_.]+)/',
			'<a href="' . esc_url( home_url( '/id/' ) ) . '$1" class="mention">@$1</a>',
			$html
		);

		// Linkify #hashtags
		$html = preg_replace(
			'/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',
			'<a href="#" class="hashtag" data-tag="$1">#$1</a>',
			$html
		);

		// Remove raw URLs from visible text (they become embeds below)
		foreach ( $embeds as $embed ) {
			if ( ! empty( $embed['url'] ) ) {
				$html = str_replace( esc_html( $embed['url'] ), '', $html );
			}
		}

		// Clean trailing whitespace
		$html = trim( $html );

		// Wrap text
		$output = '<div class="post-text">' . $html . '</div>';

		// Append embeds below text
		if ( ! empty( $embeds ) ) {
			$output .= '<div class="post-embeds">';
			foreach ( $embeds as $embed ) {
				$output .= $embed['html'] ?? '';
			}
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get character count excluding URLs
	 *
	 * @param string $content
	 * @return int
	 */
	public static function count_chars( string $content ): int {
		$text = preg_replace( self::URL_PATTERN, '', $content );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		return mb_strlen( $text, 'UTF-8' );
	}
}
