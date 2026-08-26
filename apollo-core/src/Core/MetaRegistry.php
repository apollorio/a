<?php
/**
 * Apollo Core - Central Meta Registry
 *
 * MASTER REGISTRY for ALL Meta Keys in Apollo ecosystem.
 * Registers meta keys for REST API exposure and validation.
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta Registry - Singleton Pattern
 */
final class MetaRegistry {

	/**
	 * Instance
	 *
	 * @var MetaRegistry|null
	 */
	private static ?MetaRegistry $instance = null;

	/**
	 * Post Meta Definitions
	 *
	 * @var array
	 */
	private array $post_meta = array();

	/**
	 * User Meta Definitions
	 *
	 * @var array
	 */
	private array $user_meta = array();

	/**
	 * Term Meta Definitions
	 *
	 * @var array
	 */
	private array $term_meta = array();

	/**
	 * Get instance (Singleton)
	 */
	public static function get_instance(): MetaRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->load_definitions();
	}

	/**
	 * Initialize
	 */
	public static function init(): void {
		$instance = self::get_instance();

		// Apply external definitions at init:8 so sibling plugins have time to register filters
		add_action( 'init', array( $instance, 'apply_external_definitions' ), 8 );

		// Register meta on init priority 9 (after CPTs and taxonomies)
		add_action( 'init', array( $instance, 'register_all_meta' ), 9 );
	}

	/**
	 * Load meta definitions from registry
	 */
	private function load_definitions(): void {
		// ═══════════════════════════════════════════════════════════════
		// POST META - Organized by CPT
		// ═══════════════════════════════════════════════════════════════

		$this->post_meta = array(
			// ─────────────────────────────────────────────────────────────
			// EVENT META
			// ─────────────────────────────────────────────────────────────
			'event'       => array(
				'_event_start_date'   => array(
					'type'         => 'string',
					'description'  => 'Event start date (Y-m-d)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_event_end_date'     => array(
					'type'         => 'string',
					'description'  => 'Event end date (Y-m-d)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_event_start_time'   => array(
					'type'         => 'string',
					'description'  => 'Event start time (H:i)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_event_end_time'     => array(
					'type'         => 'string',
					'description'  => 'Event end time (H:i)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_event_dj_ids'       => array(
					'type'         => 'array',
					'description'  => 'Array of DJ post IDs',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					'default'      => array(),
				),
				'_event_dj_slots'     => array(
					'type'         => 'array',
					'description'  => 'DJ time slots with start/end times',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'dj_id'      => array( 'type' => 'integer' ),
									'start_time' => array( 'type' => 'string' ),
									'end_time'   => array( 'type' => 'string' ),
								),
							),
						),
					),
					'default'      => array(),
				),
				'_event_loc_id'       => array(
					'type'         => 'integer',
					'description'  => 'Location post ID (local CPT)',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_event_banner'       => array(
					'type'         => 'integer',
					'description'  => 'Banner image attachment ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_event_ticket_url'   => array(
					'type'         => 'string',
					'description'  => 'Ticket purchase URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_event_ticket_price' => array(
					'type'         => 'string',
					'description'  => 'Price display text',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_event_privacy'      => array(
					'type'         => 'string',
					'description'  => 'Event privacy: public, private, invite',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 'public',
					'enum'         => array( 'public', 'private', 'invite' ),
				),
				'_event_status'       => array(
					'type'         => 'string',
					'description'  => 'Event status',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 'scheduled',
					'enum'         => array( 'scheduled', 'cancelled', 'postponed', 'ongoing', 'finished' ),
				),
				'_event_is_gone'      => array(
					'type'         => 'string',
					'description'  => 'Expiration flag set 30min after event ends',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_event_budget'       => array(
					'type'         => 'number',
					'description'  => 'Event budget amount',
					'single'       => true,
					'show_in_rest' => false,
				),
				'_event_video_url'    => array(
					'type'         => 'string',
					'description'  => 'Promotional video URL (YouTube/MP4)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_event_gallery'      => array(
					'type'         => 'array',
					'description'  => 'Gallery image attachment IDs (max 3)',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					'default'      => array(),
				),
				'_event_coupon_code'  => array(
					'type'         => 'string',
					'description'  => 'Event coupon/promo code',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_event_list_url'     => array(
					'type'         => 'string',
					'description'  => 'Guest list URL (lista amiga)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),

				// ═══════════════════════════════════════════════════════════════
				// INTERNAL RANKING — 2026-08-24
				// NEVER RENDER FRONTEND. 0 (worst/default) .. 10 (outstanding).
				// Editorial signal set ONLY by an admin (manage_options) on the
				// wp-admin single-event edit screen, right below the
				// Publish/Update button — see apollo-events/src/Admin/RankMetabox.php.
				// Consumed by apollo-telegram's event-selection logic (which event
				// to surface/recommend) via apollo_event_get_int_rank() /
				// apollo_event_get_top_ranked() — see apollo-events/includes/functions.php.
				// show_in_rest is FALSE on purpose, same pattern as _mod_notes /
				// _doc_cpf: unregistered from REST means it can never leak into
				// any public template that reads events over REST, and it is
				// invisible to the Gutenberg meta panel (which only lists
				// show_in_rest=true fields). The classic metabox form below is the
				// only write path, and it is capability-gated a second time.
				// ═══════════════════════════════════════════════════════════════
				'_event_int_rank'     => array(
					'type'              => 'integer',
					'description'       => 'INTERNAL USE ONLY — editorial ranking 0-10 used by apollo-telegram to decide which event to surface. 0 = baseline (default, worst), 10 = outstanding. Never rendered on any public/frontend surface.',
					'single'            => true,
					'show_in_rest'      => false,
					'default'           => 0,
					'auth_callback'     => function () {
						return current_user_can( 'manage_options' );
					},
					'sanitize_callback' => function ( $value ) {
						$value = is_numeric( $value ) ? (int) $value : 0;
						return max( 0, min( 10, $value ) );
					},
				),

				// ═══════════════════════════════════════════════════════════════
				// INTERNAL VIBE TAGS — 2026-08-24
				// NEVER RENDER FRONTEND. Five independent checkboxes, same admin
				// spot as _event_int_rank (wp-admin single-event edit screen,
				// below the Publish/Update button) — see
				// apollo-events/src/Admin/RankMetabox.php. String-bool '1'/'',
				// same convention as _event_is_gone / _event_highlighted.
				// One canonical map (slug => meta key) lives in
				// apollo_event_internal_tags() in apollo-events/includes/functions.php
				// — that function is the single source of truth for the UI loop,
				// the save handler, and every read helper below; this registry
				// block must stay in sync with it if a tag is ever renamed/added.
				// ═══════════════════════════════════════════════════════════════
				'_event_tag_underground' => array(
					'type'          => 'boolean',
					'description'   => 'INTERNAL — vibe tag: Underground. Never rendered on any public/frontend surface.',
					'single'        => true,
					'show_in_rest'  => false,
					'default'       => false,
					'auth_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
				'_event_tag_mainstream'  => array(
					'type'          => 'boolean',
					'description'   => 'INTERNAL — vibe tag: Mainstream. Never rendered on any public/frontend surface.',
					'single'        => true,
					'show_in_rest'  => false,
					'default'       => false,
					'auth_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
				'_event_tag_comercial'   => array(
					'type'          => 'boolean',
					'description'   => 'INTERNAL — vibe tag: Comercial. Never rendered on any public/frontend surface.',
					'single'        => true,
					'show_in_rest'  => false,
					'default'       => false,
					'auth_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
				'_event_tag_lgbtqia'     => array(
					'type'          => 'boolean',
					'description'   => 'INTERNAL — vibe tag: LGBTQIA+. Never rendered on any public/frontend surface.',
					'single'        => true,
					'show_in_rest'  => false,
					'default'       => false,
					'auth_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
				'_event_tag_sexparty'    => array(
					'type'          => 'boolean',
					'description'   => 'INTERNAL — vibe tag: Sex Party. Never rendered on any public/frontend surface.',
					'single'        => true,
					'show_in_rest'  => false,
					'default'       => false,
					'auth_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
			),

			// ─────────────────────────────────────────────────────────────
			// DJ META
			// ─────────────────────────────────────────────────────────────
			'dj'          => array(
				'_dj_image'              => array(
					'type'         => 'integer',
					'description'  => 'Profile image attachment ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_dj_banner'             => array(
					'type'         => 'integer',
					'description'  => 'Banner image attachment ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_dj_website'            => array(
					'type'         => 'string',
					'description'  => 'DJ website URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_instagram'          => array(
					'type'         => 'string',
					'description'  => 'Instagram handle (without @)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_dj_soundcloud'         => array(
					'type'         => 'string',
					'description'  => 'SoundCloud URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_spotify'            => array(
					'type'         => 'string',
					'description'  => 'Spotify URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_youtube'            => array(
					'type'         => 'string',
					'description'  => 'YouTube URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_mixcloud'           => array(
					'type'         => 'string',
					'description'  => 'Mixcloud URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_user_id'            => array(
					'type'         => 'integer',
					'description'  => 'Linked WordPress user ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_dj_verified'           => array(
					'type'         => 'boolean',
					'description'  => 'DJ verified status',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => false,
				),
				'_dj_bio_short'          => array(
					'type'         => 'string',
					'description'  => 'Short bio (max 280 chars)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_textarea_field',
				),
				'_dj_name'               => array(
					'type'         => 'string',
					'description'  => 'DJ name if different from post title',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_dj_bio'                => array(
					'type'         => 'string',
					'description'  => 'Full bio text',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_textarea_field',
				),
				'_dj_facebook'           => array(
					'type'         => 'string',
					'description'  => 'Facebook page URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_bandcamp'           => array(
					'type'         => 'string',
					'description'  => 'Bandcamp URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_beatport'           => array(
					'type'         => 'string',
					'description'  => 'Beatport URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_resident_advisor'   => array(
					'type'         => 'string',
					'description'  => 'Resident Advisor URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_twitter'            => array(
					'type'         => 'string',
					'description'  => 'Twitter/X URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_tiktok'             => array(
					'type'         => 'string',
					'description'  => 'TikTok URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_original_project_1' => array(
					'type'         => 'string',
					'description'  => 'Original project name 1',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_dj_original_project_2' => array(
					'type'         => 'string',
					'description'  => 'Original project name 2',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_dj_original_project_3' => array(
					'type'         => 'string',
					'description'  => 'Original project name 3',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_dj_set_url'            => array(
					'type'         => 'string',
					'description'  => 'Featured set/mix URL for vinyl player',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_media_kit_url'      => array(
					'type'         => 'string',
					'description'  => 'Media kit download URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_rider_url'          => array(
					'type'         => 'string',
					'description'  => 'Rider download URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_mix_url'            => array(
					'type'         => 'string',
					'description'  => 'Mix/playlist URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_tracks'             => array(
					'type'         => 'array',
					'description'  => 'Out Now — structured track repeater. Schema v2 (2026-07-28): '
						. 'title, artists, duration are mandatory; at least one of url_soundcloud, '
						. 'url_spotify, url_bandcamp, url_download is mandatory. Superset of the legacy '
						. '{title,url,year,duration} shape read by apollo-djs front-end templates — see '
						. 'plugins/_inventory/registry/20-meta-schemas.json for the full contract and '
						. 'the back-compat plan.',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'title'          => array( 'type' => 'string' ),
									'artists'        => array( 'type' => 'string' ),
									'duration'       => array( 'type' => 'string' ),
									'bpm'            => array( 'type' => 'integer' ),
									'release_date'   => array( 'type' => 'string' ),
									'album'          => array( 'type' => 'string' ),
									'label'          => array( 'type' => 'string' ),
									'genre'          => array( 'type' => 'string' ),
									'cover_url'      => array( 'type' => 'string' ),
									'cover_id'       => array( 'type' => 'integer' ),
									'url_soundcloud' => array( 'type' => 'string' ),
									'url_spotify'    => array( 'type' => 'string' ),
									'url_bandcamp'   => array( 'type' => 'string' ),
									'url_download'   => array( 'type' => 'string' ),
								),
							),
						),
					),
					'default'      => array(),
					// Defined in apollo-djs/includes/functions.php — same cross-plugin pattern as
					// apollo_membership_normalize_storage for _apollo_membership. WP's sanitize_meta()
					// no-ops safely if apollo-djs is inactive (is_callable() guard in WP core).
					'sanitize'     => 'apollo_dj_sanitize_tracks_meta',
				),
				// ═══════════════════════════════════════════════════════════════
				// ORPHANS CLOSED — 2026-08-11
				// These four keys are READ by apollo-djs templates and WRITTEN by the
				// apollo-lux-panels DjPanel, but were never registered here. Unregistered
				// post meta still stores and reads via get/update_post_meta(), so nothing
				// looked broken — but it is invisible to REST, runs no sanitize_callback
				// and no auth_callback. `_dj_statement` and `_dj_bio` carry user prose
				// straight onto a public page, which is exactly the case registration
				// exists for. See _inventory/registry/21-mockup-field-contract.json.
				// ═══════════════════════════════════════════════════════════════
				'_dj_statement'          => array(
					'type'         => 'string',
					'description'  => 'Artist statement — the pinned scrub sentence on /dj/{slug}',
					'single'       => true,
					'show_in_rest' => true,
					// One <em> allowed: the mockup accents a single word in the statement.
					// A separate "which word is gold" key desyncs the moment the sentence
					// is edited, so the emphasis lives inside the field it belongs to.
					'sanitize'     => 'apollo_core_kses_inline_em',
				),
				'_dj_booking'            => array(
					'type'         => 'string',
					'description'  => 'Booking contact e-mail (the CONTACT — state lives in _dj_booking_status)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_email',
				),
				'_dj_about_photo'        => array(
					'type'         => 'string',
					'description'  => 'Sobre section photo URL (fallback when _dj_about_video is empty)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_about_video'        => array(
					'type'         => 'string',
					'description'  => 'Sobre section looping muted video URL — takes priority over _dj_about_photo',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_dj_gallery'            => array(
					'type'         => 'array',
					'description'  => 'Gallery attachment IDs. NOTE: has a metabox input and NO section renders it — build the section or retire the key.',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					'default'      => array(),
				),

				// ═══════════════════════════════════════════════════════════════
				// NEW — required by the dj single-page mockup, 2026-08-11
				// Audit: _inventory/registry/21-mockup-field-contract.json → dj.missing
				// ═══════════════════════════════════════════════════════════════
				'_dj_home_city'          => array(
					'type'         => 'string',
					'description'  => 'Artist base city — hero eyebrow. The dj CPT had NO city field; `_loc_city` in the deep scan is an event/loc key that leaked in. Base city is NOT derivable from gig history.',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_dj_booking_status'     => array(
					'type'         => 'string',
					'description'  => 'Whether the artist is taking bookings right now — rendered as the live dot. Distinct from _dj_booking, which is the contact.',
					'single'       => true,
					'show_in_rest' => true,
					'enum'         => array( 'open', 'selective', 'closed' ),
					'default'      => 'open',
				),
				'_dj_media_kit_stats'    => array(
					'type'         => 'array',
					'description'  => 'The four .kit-meta rows. A Google Drive folder cannot be introspected, so size/photo-count/file-list are claims the artist makes, not derivable facts. Max 4 rows.',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'     => 'array',
							'maxItems' => 4,
							'items'    => array(
								'type'       => 'object',
								'properties' => array(
									'value' => array( 'type' => 'string' ),
									'label' => array( 'type' => 'string' ),
								),
							),
						),
					),
					'default'      => array(),
					// Same cross-plugin pattern as _dj_tracks: ONE sanitizer shared by the
					// REST path and the apollo-lux-panels metabox. Two sanitizers is how
					// _dj_tracks ended up with two competing schemas on one edit screen.
					'sanitize'     => 'apollo_dj_sanitize_kit_stats',
				),

				// ── Overrides: nullable, each with a documented fallback ──────
				'_dj_eyebrow'            => array(
					'type'         => 'string',
					'description'  => 'Editorial override for the hero eyebrow. EMPTY BY DEFAULT — falls back to _dj_home_city + first two `sound` terms, so the two halves cannot drift apart.',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_dj_name_lines'         => array(
					'type'         => 'array',
					'description'  => 'Override for the two-line hero display split. EMPTY BY DEFAULT — falls back to explode(" ", _dj_name). The derivation breaks on one-word and three-word names; this is the escape hatch.',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'     => 'array',
							'maxItems' => 2,
							'items'    => array( 'type' => 'string' ),
						),
					),
					'default'      => array(),
				),
				'_dj_footer_image'       => array(
					'type'         => 'string',
					'description'  => 'Override for the full-bleed footer photo. EMPTY BY DEFAULT — falls back to the newest cover from this DJ\'s played events, so every artist gets a real image instead of a stock placeholder.',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),

			),

			// ─────────────────────────────────────────────────────────────
			// LOCAL META
			// ─────────────────────────────────────────────────────────────
			'local'       => array(
				'_local_name'         => array(
					'type'         => 'string',
					'description'  => 'Location name if different from title',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_description'  => array(
					'type'         => 'string',
					'description'  => 'Location description/bio',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_textarea_field',
				),
				'_local_address'      => array(
					'type'         => 'string',
					'description'  => 'Full street address',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_city'         => array(
					'type'         => 'string',
					'description'  => 'City name',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_state'        => array(
					'type'         => 'string',
					'description'  => 'State/Province',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_country'      => array(
					'type'         => 'string',
					'description'  => 'Country',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 'Brasil',
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_postal'       => array(
					'type'         => 'string',
					'description'  => 'Postal/ZIP code',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_region'       => array(
					'type'         => 'string',
					'description'  => 'Region/neighborhood',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_lat'          => array(
					'type'         => 'number',
					'description'  => 'Latitude coordinate',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_local_lng'          => array(
					'type'         => 'number',
					'description'  => 'Longitude coordinate',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_local_phone'        => array(
					'type'         => 'string',
					'description'  => 'Phone number',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_website'      => array(
					'type'         => 'string',
					'description'  => 'Website URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_local_instagram'    => array(
					'type'         => 'string',
					'description'  => 'Instagram handle',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_facebook'     => array(
					'type'         => 'string',
					'description'  => 'Facebook URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_local_whatsapp'     => array(
					'type'         => 'string',
					'description'  => 'WhatsApp number',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_capacity'     => array(
					'type'         => 'integer',
					'description'  => 'Maximum capacity',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_local_price_range'  => array(
					'type'         => 'string',
					'description'  => 'Price range indicator',
					'single'       => true,
					'show_in_rest' => true,
					'enum'         => array( '$', '$$', '$$$', '$$$$' ),
				),
				'_local_image_1'      => array(
					'type'         => 'integer',
					'description'  => 'Gallery image 1 attachment ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_local_image_2'      => array(
					'type'         => 'integer',
					'description'  => 'Gallery image 2 attachment ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_local_image_3'      => array(
					'type'         => 'integer',
					'description'  => 'Gallery image 3 attachment ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_local_image_4'      => array(
					'type'         => 'integer',
					'description'  => 'Gallery image 4 attachment ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_local_image_5'      => array(
					'type'         => 'integer',
					'description'  => 'Gallery image 5 attachment ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_local_testimonials' => array(
					'type'         => 'array',
					'description'  => 'User testimonials/reviews',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'user_id' => array( 'type' => 'integer' ),
									'text'    => array( 'type' => 'string' ),
									'rating'  => array( 'type' => 'integer' ),
									'date'    => array( 'type' => 'string' ),
								),
							),
						),
					),
					'default'      => array(),
				),
				'_local_user_id'      => array(
					'type'         => 'integer',
					'description'  => 'Linked WP user ID (owner/manager)',
					'single'       => true,
					'show_in_rest' => true,
				),
				// ═══════════════════════════════════════════════════════════════
				// NEW — required by the local single-page mockup, 2026-08-11
				// Audit: _inventory/registry/21-mockup-field-contract.json → local.missing
				// ═══════════════════════════════════════════════════════════════
				'_local_tagline'      => array(
					'type'         => 'string',
					'description'  => 'Editorial half of the hero kicker ("Templo do Techno"). data-registry maps hero_kicker as derived from city+state+"copy" — this IS that copy, and nothing derives it.',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_local_founded_year' => array(
					'type'         => 'integer',
					'description'  => 'Year the venue opened. The "23+ anos" beside it is the SAME fact — store the year, derive the span, or the number is wrong every 1 January.',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'absint',
				),
				'_local_rooms'        => array(
					'type'         => 'array',
					'description'  => 'Room / ambiente names ("pista", "lounge", "rooftop"). Count AND names are printed; the count is derived. NOT _local_capacity — Apollo never verifies lotacao and must never render it.',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'default'      => array(),
				),

			),

			// ─────────────────────────────────────────────────────────────
			// CLASSIFIED META
			// ─────────────────────────────────────────────────────────────
			'classified'  => array(
				// ── Kind + system ─────────────────────────────────────────
				// _classified_type discriminates the three advert kinds and
				// drives every marketplace query. Stored canonically as
				// general|ticket|accommodation — the legacy aliases
				// ticket_sell/rent_space are accepted on input and normalised
				// by apollo_adverts_canonical_type() before any write.
				'_classified_type'             => array(
					'type'         => 'string',
					'description'  => 'Advert kind: general | ticket | accommodation',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 'general',
					'enum'         => array( 'general', 'ticket', 'accommodation', 'ticket_sell', 'rent_space' ),
				),
				'_classified_views'            => array(
					'type'         => 'integer',
					'description'  => 'View counter',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_classified_rating'           => array(
					'type'         => 'number',
					'description'  => 'Aggregate rating',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_classified_badge'            => array(
					'type'         => 'string',
					'description'  => 'Editorial badge label (staff-set)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),

				// ── TICKET RESALE BLOCK ───────────────────────────────────
				// _classified_event_id is the real relation to the `event`
				// CPT; the three _event_* fields below are a denormalised
				// snapshot so a resale card renders without joining to the
				// event on every request. apollo_adverts_link_event() writes
				// both together and refreshes the snapshot.
				'_classified_event_id'         => array(
					'type'         => 'integer',
					'description'  => 'Linked event post ID (resale adverts)',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_classified_event_title'      => array(
					'type'         => 'string',
					'description'  => 'Event title snapshot',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_classified_event_date'       => array(
					'type'         => 'string',
					'description'  => 'Event date snapshot (Y-m-d)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_classified_event_loc'        => array(
					'type'         => 'string',
					'description'  => 'Event venue snapshot',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_classified_quantity'         => array(
					'type'         => 'integer',
					'description'  => 'Number of tickets offered',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 1,
				),

				'_classified_price'            => array(
					'type'         => 'number',
					'description'  => 'Price amount',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_classified_currency'         => array(
					'type'         => 'string',
					'description'  => 'Currency code',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 'BRL',
				),
				'_classified_negotiable'       => array(
					'type'         => 'boolean',
					'description'  => 'Price negotiable',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => false,
				),
				'_classified_condition'        => array(
					'type'         => 'string',
					'description'  => 'Item condition',
					'single'       => true,
					'show_in_rest' => true,
					'enum'         => array( 'novo', 'usado', 'recondicionado' ),
				),
				'_classified_loc'              => array(
					'type'         => 'string',
					'description'  => 'Seller location',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_classified_contact_phone'    => array(
					'type'         => 'string',
					'description'  => 'Contact phone',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_classified_contact_whatsapp' => array(
					'type'         => 'string',
					'description'  => 'WhatsApp number',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_classified_expires_at'       => array(
					'type'         => 'string',
					'description'  => 'Expiration date (Y-m-d)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_classified_featured'         => array(
					'type'         => 'boolean',
					'description'  => 'Featured listing',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => false,
				),

				// ── ACCOMMODATION BLOCK ───────────────────────────────────
				// Only meaningful when _classified_type is 'accommodation' or
				// 'rent_space'. Kept on the `classified` map (not a separate
				// CPT) because accommodation adverts ARE classifieds — the
				// type meta is what discriminates them.
				//
				// _classified_hostel is the privacy switch for the whole
				// accommodation surface: a hostel is a public, worldwide-listed
				// business, so its listing may be shown openly and links out to
				// its own site. Everything else is somebody's home — it stays
				// locked behind auth, identical to how resale tickets behave.
				'_classified_hostel'           => array(
					'type'         => 'boolean',
					'description'  => 'Accommodation is a hostel (public business — listing is not auth-gated)',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => false,
				),
				'_classified_hostel_url'       => array(
					'type'         => 'string',
					'description'  => 'Hostel website URL — replaces the chat CTA when _classified_hostel is on',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_classified_min_nights'       => array(
					'type'         => 'integer',
					'description'  => 'Minimum nights the space can be rented/shared (hostel and non-hostel)',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_classified_max_days'         => array(
					'type'         => 'integer',
					'description'  => 'Maximum days the space can be rented/shared by the same user (hostel and non-hostel)',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				// Availability window applies to non-hostel listings only —
				// hostels operate year-round, so the metabox hides these when
				// the hostel box is ticked and they simply stay empty.
				'_classified_avail_start'      => array(
					'type'         => 'string',
					'description'  => 'Availability start date, Y-m-d (non-hostel only)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_classified_avail_end'        => array(
					'type'         => 'string',
					'description'  => 'Availability end date, Y-m-d (non-hostel only)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),

				// ── HOSTEL RELATION (2026-08-17) ──────────────────────────
				// An advert belongs EITHER to a member (post_author) OR to an
				// official hostel — never both. This is the pointer to the
				// `hostel` CPT that supersedes the _classified_hostel boolean
				// above.
				//
				// The boolean could mark a listing as official but could not
				// answer "show me THIS hostel's page", and the accommodation
				// surface needs exactly that. _classified_hostel is retained
				// as a read fallback for one release; new writes set the id.
				//
				// ADMIN-ONLY, ENFORCED AT THE WRITE BOUNDARY. Same reasoning as
				// the flag it replaces: the relation decides whether a listing
				// bypasses the auth gate and is shown to the whole internet, so
				// it is an editorial claim staff verify, not a seller
				// preference. apollo-adverts lists it in
				// APOLLO_ADVERTS_ADMIN_ONLY_META, which branches the
				// auth_callback to manage_options — so the CPT's generic REST
				// meta endpoint refuses it too, not just the UI.
				'_classified_hostel_id'        => array(
					'type'         => 'integer',
					'description'  => 'Related hostel post ID (admin-only). Supersedes the _classified_hostel boolean.',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
			),

			// ─────────────────────────────────────────────────────────────
			// TRACK META — "Out Now" releases (owner: apollo-djs)
			//
			// EVERY FIELD IS A FLAT SCALAR, AND THAT IS THE POINT. These are
			// the same fields as the `_dj_tracks` v2 repeater, unpacked onto a
			// post. The repeater could never be edited from the front end —
			// apollo-templates FrontendEditor::sanitize_field() returns string
			// and has no array branch — so a DJ could not add their own
			// release. Scalars on a post need nothing new from the editor.
			//
			// `_dj_tracks` stays registered through the migration and
			// apollo_dj_get_tracks() merges both sources, so nothing on screen
			// changes the day this ships. See 20-meta-schemas.json.
			// ─────────────────────────────────────────────────────────────
			'track'       => array(
				// Artists is mandatory in the v2 contract and stays mandatory
				// here: a release with no credited artist is not a release.
				'_track_artists'        => array(
					'type'         => 'string',
					'description'  => 'Credited artists, display string',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				// Relation, mirroring _event_dj_ids. This is what a repeater on
				// one DJ post could never express: a B2B or a remix credits
				// more than one artist, and duplicating the row onto both is
				// two copies that drift.
				'_track_dj_ids'         => array(
					'type'         => 'array',
					'description'  => 'Related DJ post IDs — a release may credit more than one',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					'default'      => array(),
				),
				'_track_duration'       => array(
					'type'         => 'string',
					'description'  => 'Duration, display string (e.g. 6:42)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				// Drives sort order everywhere and the "latest -> landing"
				// rule on /casa. Y-m-d.
				'_track_release_date'   => array(
					'type'         => 'string',
					'description'  => 'Release date, Y-m-d — the sort key for every track surface',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_track_bpm'            => array(
					'type'         => 'integer',
					'description'  => 'Beats per minute',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_track_album'          => array(
					'type'         => 'string',
					'description'  => 'Album or EP name',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_track_label'          => array(
					'type'         => 'string',
					'description'  => 'Record label',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				// Genre is a `sound` TERM, not a string. The taxonomy already
				// exists and is already what the DJ surface filters on —
				// registering a second genre vocabulary here would fork it.
				// This key holds only the legacy freeform value carried over
				// from the v2 repeater during migration.
				'_track_genre_legacy'   => array(
					'type'         => 'string',
					'description'  => 'Legacy freeform genre from _dj_tracks v2 — migrate to the `sound` taxonomy and retire',
					'single'       => true,
					'show_in_rest' => false,
					'sanitize'     => 'sanitize_text_field',
				),
				// Cover art. The post thumbnail is the canonical image; these
				// carry the v2 values through migration for rows whose cover
				// was a bare URL with no attachment behind it.
				'_track_cover_url'      => array(
					'type'         => 'string',
					'description'  => 'Cover image URL (fallback when no attachment exists)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				// Platform links. At least one is required by the v2 contract
				// and that rule carries over — a release nobody can listen to
				// is not a release.
				'_track_url_soundcloud' => array(
					'type'         => 'string',
					'description'  => 'SoundCloud URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_track_url_spotify'    => array(
					'type'         => 'string',
					'description'  => 'Spotify URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_track_url_bandcamp'   => array(
					'type'         => 'string',
					'description'  => 'Bandcamp URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_track_url_download'   => array(
					'type'         => 'string',
					'description'  => 'Free/direct download URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				// Set by the migration so a converted row can be traced back to
				// the dj post it came from, and so the merge in
				// apollo_dj_get_tracks() can de-duplicate without guessing.
				'_track_migrated_from'  => array(
					'type'         => 'integer',
					'description'  => 'Source dj post ID when this track was migrated out of _dj_tracks',
					'single'       => true,
					'show_in_rest' => false,
					'default'      => 0,
				),

				// ── GHOST CREDITS (2026-08-17) ────────────────────────────
				// A release credits artists who may have NO dj post. Real DJs
				// are linked through _track_dj_ids; everyone else is a name.
				//
				// Two flat scalars, NOT a repeater of {dj_id,name}. A repeater
				// cannot be edited from the front end —
				// apollo-templates FrontendEditor::sanitize_field() returns
				// string and has no array branch — and that limitation is the
				// entire reason `track` became a CPT instead of staying meta.
				// Re-introducing one here would rebuild the wall just removed.
				//
				// A ghost is a CREDIT, not a profile. Auto-creating a stub dj
				// post per featured artist would fill the curated /djs roster
				// with empty posts nobody claims. apollo_track_credits() merges
				// both sources into one ordered list, and a ghost resolves to a
				// real DJ automatically if that person ever registers — with no
				// change to stored data.
				'_track_ghost_artists'  => array(
					'type'         => 'string',
					'description'  => 'Comma-separated credited artists that have no dj post (ghost credits)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),

				// ── PREVIEW (2026-08-17) ──────────────────────────────────
				// DELIBERATELY SEPARATE FROM _track_url_*. Those are the
				// DESTINATIONS — where a listener goes to hear the whole thing
				// on the artist's own platform. This is a short in-page taste.
				//
				// Conflating them would mean either a full Spotify embed on
				// /casa (heavy, account-walled) or no preview at all wherever
				// the release lives somewhere unembeddable.
				//
				// Providers, resolved by host in apollo_track_preview():
				//   catbox.moe    direct MP3 hotlink, no account, 200MB — best
				//                 case: native <audio>, real scrubbing, no
				//                 third-party script
				//   archive.org   permanent, free, direct stream
				//   youtube       practical fallback, privacy-friendly iframe
				//   direct        any other .mp3/.ogg/.m4a — self-hosted
				//
				// Guests never receive a preview source; the locked card design
				// stands in its place.
				'_track_preview_url'    => array(
					'type'         => 'string',
					'description'  => 'Short preview source — Catbox / Archive.org / YouTube / direct audio',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_track_preview_start'  => array(
					'type'         => 'integer',
					'description'  => 'Preview start offset in seconds',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_track_preview_seconds' => array(
					'type'         => 'integer',
					'description'  => 'Preview length in seconds (0 = site default, 30)',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
			),

			// ─────────────────────────────────────────────────────────────
			// HOSTEL META — official accommodation providers (owner: apollo-adverts)
			//
			// Deliberately THIN. `local` already registers address, coords and
			// gallery meta; a hostel that needs a map or a gallery should
			// relate to a `local` through _hostel_loc_id rather than have
			// those keys registered a second time here. Two copies of address
			// meta is precisely the divergence apollo-adverts.json already
			// flags at $accommodation_meta_and_depoimentos_2026_07_28.
			// ─────────────────────────────────────────────────────────────
			'hostel'      => array(
				'_hostel_url'         => array(
					'type'         => 'string',
					'description'  => 'Hostel website — the booking destination for its listings',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_hostel_loc_id'      => array(
					'type'         => 'integer',
					'description'  => 'Related local post ID — where address, coords and gallery live. Do not re-register those here.',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_hostel_contact'     => array(
					'type'         => 'string',
					'description'  => 'Public booking contact (a business line, not a personal number)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_hostel_check_in'    => array(
					'type'         => 'string',
					'description'  => 'Check-in time, display string',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_hostel_check_out'   => array(
					'type'         => 'string',
					'description'  => 'Check-out time, display string',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
			),

			// ─────────────────────────────────────────────────────────────
			// SUPPLIER META
			// ─────────────────────────────────────────────────────────────
			'supplier'    => array(
				'_supplier_company'       => array(
					'type'         => 'string',
					'description'  => 'Company name',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_supplier_cnpj'          => array(
					'type'         => 'string',
					'description'  => 'CNPJ number',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_supplier_contact_name'  => array(
					'type'         => 'string',
					'description'  => 'Contact person name',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_supplier_contact_email' => array(
					'type'         => 'string',
					'description'  => 'Contact email',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_email',
				),
				'_supplier_contact_phone' => array(
					'type'         => 'string',
					'description'  => 'Contact phone',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_supplier_website'       => array(
					'type'         => 'string',
					'description'  => 'Website URL',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'esc_url_raw',
				),
				'_supplier_address'       => array(
					'type'         => 'string',
					'description'  => 'Address',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_supplier_verified'      => array(
					'type'         => 'boolean',
					'description'  => 'Verified supplier',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => false,
				),
				'_supplier_rating'        => array(
					'type'         => 'number',
					'description'  => 'Rating (0-5)',
					'single'       => true,
					'show_in_rest' => true,
				),
			),

			// ─────────────────────────────────────────────────────────────
			// DOC META
			// ─────────────────────────────────────────────────────────────
			'doc'         => array(
				'_doc_file_id'   => array(
					'type'         => 'integer',
					'description'  => 'Attachment ID of the file',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_doc_folder_id' => array(
					'type'         => 'integer',
					'description'  => 'Folder term ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_doc_access'    => array(
					'type'         => 'string',
					'description'  => 'Access level',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 'private',
					'enum'         => array( 'public', 'private', 'group', 'industry' ),
				),
				'_doc_version'   => array(
					'type'         => 'string',
					'description'  => 'Document version',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_doc_downloads' => array(
					'type'         => 'integer',
					'description'  => 'Download count',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_doc_status'    => array(
					'type'         => 'string',
					'description'  => 'Document workflow status',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 'draft',
					'enum'         => array( 'draft', 'locked', 'finalized', 'signed' ),
				),
				'_doc_checksum'  => array(
					'type'         => 'string',
					'description'  => 'SHA-256 checksum',
					'single'       => true,
					'show_in_rest' => false,
					'sanitize'     => 'sanitize_text_field',
				),
				'_doc_cpf'       => array(
					'type'         => 'string',
					'description'  => 'Owner CPF (masked/validated by app)',
					'single'       => true,
					'show_in_rest' => false,
					'sanitize'     => 'sanitize_text_field',
				),
			),

			// ─────────────────────────────────────────────────────────────
			// HUB META
			// ─────────────────────────────────────────────────────────────
			'hub'         => array(
				'_hub_bio'        => array(
					'type'         => 'string',
					'description'  => 'Short bio (max 280)',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_textarea_field',
				),
				'_hub_links'      => array(
					'type'         => 'array',
					'description'  => 'Hub links array',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'title' => array( 'type' => 'string' ),
									'url'   => array( 'type' => 'string' ),
									'icon'  => array( 'type' => 'string' ),
								),
							),
						),
					),
					'default'      => array(),
				),
				'_hub_socials'    => array(
					'type'         => 'array',
					'description'  => 'Social media links',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'platform' => array( 'type' => 'string' ),
									'url'      => array( 'type' => 'string' ),
									'username' => array( 'type' => 'string' ),
								),
							),
						),
					),
					'default'      => array(),
				),
				'_hub_theme'      => array(
					'type'         => 'string',
					'description'  => 'Hub theme',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_hub_avatar'     => array(
					'type'         => 'integer',
					'description'  => 'Avatar image ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_hub_cover'      => array(
					'type'         => 'integer',
					'description'  => 'Cover image ID',
					'single'       => true,
					'show_in_rest' => true,
				),
				'_hub_custom_css' => array(
					'type'         => 'string',
					'description'  => 'Custom CSS',
					'single'       => true,
					'show_in_rest' => true,
				),
			),

			// ─────────────────────────────────────────────────────────────
			// EMAIL_APRIO META
			// ─────────────────────────────────────────────────────────────
			'email_aprio' => array(
				'_email_subject'   => array(
					'type'         => 'string',
					'description'  => 'Email subject line',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_email_type'      => array(
					'type'         => 'string',
					'description'  => 'Email type',
					'single'       => true,
					'show_in_rest' => true,
					'enum'         => array( 'transactional', 'marketing', 'digest' ),
				),
				'_email_variables' => array(
					'type'         => 'array',
					'description'  => 'Available merge tags',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'default'      => array(),
				),
			),

			// ─────────────────────────────────────────────────────────────
			// UNIVERSAL META (all CPTs via empty-string post type)
			// ─────────────────────────────────────────────────────────────
			''            => array(
				'_fav_count'       => array(
					'type'         => 'integer',
					'description'  => 'Total favorites count',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_wow_count'       => array(
					'type'         => 'integer',
					'description'  => 'Total WOW reactions',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => 0,
				),
				'_wow_counts'      => array(
					'type'         => 'object',
					'description'  => 'WOW counts per type',
					'single'       => true,
					'show_in_rest' => true,
					'default'      => array(),
				),
				'_coauthors'       => array(
					'type'         => 'array',
					'description'  => 'Co-author user IDs',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					'default'      => array(),
				),
				'_mod_status'      => array(
					'type'         => 'string',
					'description'  => 'Moderation status',
					'single'       => true,
					'show_in_rest' => true,
					'enum'         => array( 'pending', 'approved', 'rejected', 'flagged' ),
				),
				'_mod_notes'       => array(
					'type'         => 'string',
					'description'  => 'Moderator notes',
					'single'       => true,
					'show_in_rest' => false, // Admin only
				),
				'_mod_reviewed_by' => array(
					'type'         => 'integer',
					'description'  => 'Reviewer user ID',
					'single'       => true,
					'show_in_rest' => false,
				),
				'_mod_reviewed_at' => array(
					'type'         => 'string',
					'description'  => 'Review timestamp',
					'single'       => true,
					'show_in_rest' => false,
				),
				'_apollo_seo'      => array(
					'type'         => 'array',
					'description'  => 'SEO data (title, description, og, schema)',
					'single'       => true,
					'show_in_rest' => false,
					'default'      => array(),
				),
			),

			// ─────────────────────────────────────────────────────────────
			// PAGE META (apollo-templates)
			// ─────────────────────────────────────────────────────────────
			'page'        => array(
				'_apollo_template'    => array(
					'type'         => 'string',
					'description'  => 'Template type',
					'single'       => true,
					'show_in_rest' => true,
					'sanitize'     => 'sanitize_text_field',
				),
				'_apollo_canvas_data' => array(
					'type'         => 'array',
					'description'  => 'Canvas blocks data',
					'single'       => true,
					'show_in_rest' => false,
					'default'      => array(),
				),
			),
		);

		// ═══════════════════════════════════════════════════════════════
		// USER META - For user profiles and preferences
		// ═══════════════════════════════════════════════════════════════

		$this->user_meta = array(
			// ─────────────────────────────────────────────────────────────
			// CORE USER META (apollo-users)
			// ─────────────────────────────────────────────────────────────
			'_apollo_user_verified'          => array(
				'type'         => 'boolean',
				'description'  => 'Account verified status',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => false,
			),
			'_apollo_membership'             => array(
				'type'         => 'array',
				'description'  => 'Membership slugs array: [profile_badge, ...access_slugs] (NOT role)',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'default'      => array( 'nao-verificado' ),
				'items_enum'   => true,
			),
			'_apollo_agent_for'              => array(
				'type'              => 'array',
				'description'       => 'Membership slugs an agent may add/remove (manage_options writes only)',
				'single'            => true,
				'show_in_rest'      => false,
				'default'           => array(),
				'items_enum'        => true,
				'auth_callback'     => function () {
					return current_user_can( 'manage_options' );
				},
			),
			'_apollo_profile_completed'      => array(
				'type'         => 'integer',
				'description'  => 'Profile completion percentage',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 0,
			),

			// ─────────────────────────────────────────────────────────────
			// MATCHMAKING & SOUND PREFERENCES (CRITICAL for registration)
			// ─────────────────────────────────────────────────────────────
			'_apollo_matchmaking_data'       => array(
				'type'         => 'array',
				'description'  => 'Matchmaking preferences',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'preference' => array( 'type' => 'string' ),
								'value'      => array( 'type' => 'string' ),
								'weight'     => array( 'type' => 'integer' ),
							),
						),
					),
				),
				'default'      => array(),
			),
			'_apollo_sound_preferences'      => array(
				'type'         => 'array',
				'description'  => 'Preferred sound/music genres (term IDs)',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'default'      => array(),
			),

			// ─────────────────────────────────────────────────────────────
			// PROFILE FIELDS
			// ─────────────────────────────────────────────────────────────
			'cover_image'                    => array(
				'type'         => 'integer',
				'description'  => 'Cover image attachment ID',
				'single'       => true,
				'show_in_rest' => true,
			),
			'custom_avatar'                  => array(
				'type'         => 'integer',
				'description'  => 'Custom avatar attachment ID',
				'single'       => true,
				'show_in_rest' => true,
			),
			'instagram'                      => array(
				'type'         => 'string',
				'description'  => 'Instagram handle',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'sanitize_text_field',
			),
			'user_location'                  => array(
				'type'         => 'string',
				'description'  => 'User location',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'sanitize_text_field',
			),
			'_apollo_bio'                    => array(
				'type'         => 'string',
				'description'  => 'User biography (max 500)',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'sanitize_textarea_field',
			),
			'_apollo_website'                => array(
				'type'         => 'string',
				'description'  => 'Personal website URL',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'esc_url_raw',
			),
			'_apollo_phone'                  => array(
				'type'         => 'string',
				'description'  => 'Phone number',
				'single'       => true,
				'show_in_rest' => false, // Privacy
				'sanitize'     => 'sanitize_text_field',
			),
			'_apollo_birth_date'             => array(
				'type'         => 'string',
				'description'  => 'Birth date (Y-m-d)',
				'single'       => true,
				'show_in_rest' => false, // Privacy
				'sanitize'     => 'sanitize_text_field',
			),

			// ─────────────────────────────────────────────────────────────
			// PRIVACY SETTINGS
			// ─────────────────────────────────────────────────────────────
			'_apollo_privacy_profile'        => array(
				'type'         => 'string',
				'description'  => 'Profile visibility',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 'public',
				'enum'         => array( 'public', 'members', 'private' ),
			),
			'_apollo_privacy_email'          => array(
				'type'         => 'boolean',
				'description'  => 'Hide email from public',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => true,
			),
			'_apollo_disable_author_url'     => array(
				'type'         => 'boolean',
				'description'  => 'Disable author URL exposure',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => true,
			),
			'_apollo_profile_views'          => array(
				'type'         => 'integer',
				'description'  => 'Total profile views',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 0,
			),

			// ─────────────────────────────────────────────────────────────
			// LOGIN META (from apollo-login)
			// ─────────────────────────────────────────────────────────────
			'_apollo_quiz_score'             => array(
				'type'         => 'integer',
				'description'  => 'Total quiz score',
				'single'       => true,
				'show_in_rest' => true,
			),
			'_apollo_simon_highscore'        => array(
				'type'         => 'integer',
				'description'  => 'Best Simon game score',
				'single'       => true,
				'show_in_rest' => true,
			),
			'_apollo_quiz_answers'           => array(
				'type'         => 'array',
				'description'  => 'Ethics & respect answers',
				'single'       => true,
				'show_in_rest' => false,
			),
			'_apollo_email_verified'         => array(
				'type'         => 'boolean',
				'description'  => 'Email verified status',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => false,
			),
			'_apollo_last_login'             => array(
				'type'         => 'string',
				'description'  => 'Last login timestamp',
				'single'       => true,
				'show_in_rest' => true,
			),
			'_apollo_lockout_until'          => array(
				'type'         => 'integer',
				'description'  => 'Lockout expiration timestamp',
				'single'       => true,
				'show_in_rest' => false,
			),

			// ─────────────────────────────────────────────────────────────
			// SOCIAL META — Party Model: all users auto-connected, no ego counters
			// ─────────────────────────────────────────────────────────────

			// ─────────────────────────────────────────────────────────────
			// NOTIFICATION & EMAIL PREFERENCES
			// ─────────────────────────────────────────────────────────────
			'_apollo_notif_prefs'            => array(
				'type'         => 'array',
				'description'  => 'Notification preferences',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'type'     => array( 'type' => 'string' ),
								'enabled'  => array( 'type' => 'boolean' ),
								'channels' => array(
									'type'  => 'array',
									'items' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'default'      => array(),
			),
			'_apollo_notif_unread'           => array(
				'type'         => 'integer',
				'description'  => 'Unread notifications count',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 0,
			),
			'_apollo_email_prefs'            => array(
				'type'         => 'array',
				'description'  => 'Email preferences',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'type'      => array( 'type' => 'string' ),
								'enabled'   => array( 'type' => 'boolean' ),
								'frequency' => array(
									'type' => 'string',
									'enum' => array( 'immediate', 'daily', 'weekly', 'never' ),
								),
							),
						),
					),
				),
				'default'      => array(),
			),

			// ─────────────────────────────────────────────────────────────
			// CENA/INDUSTRY ACCESS
			// ─────────────────────────────────────────────────────────────
			'_apollo_cena_access'            => array(
				'type'         => 'boolean',
				'description'  => 'Has industry access',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => false,
			),
			'_apollo_cena_role'              => array(
				'type'         => 'string',
				'description'  => 'Industry role',
				'single'       => true,
				'show_in_rest' => true,
				'enum'         => array( 'member', 'verified', 'admin' ),
			),

			// ─────────────────────────────────────────────────────────────
			// DASHBOARD PREFERENCES
			// ─────────────────────────────────────────────────────────────
			'_apollo_dashboard_layout'       => array(
				'type'         => 'array',
				'description'  => 'Dashboard widget layout',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'widget_id' => array( 'type' => 'string' ),
								'position'  => array( 'type' => 'integer' ),
								'size'      => array( 'type' => 'string' ),
							),
						),
					),
				),
				'default'      => array(),
			),

			// ─────────────────────────────────────────────────────────────
			// REGISTRATION / AUTH META (apollo-login)
			// ─────────────────────────────────────────────────────────────
			'_apollo_social_name'            => array(
				'type'         => 'string',
				'description'  => 'Social name (trans/queer inclusive - neutral Portuguese)',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'sanitize_text_field',
			),
			'_apollo_instagram'              => array(
				'type'         => 'string',
				'description'  => 'Instagram username (equals Apollo username)',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'sanitize_text_field',
			),
			'_apollo_avatar_url'             => array(
				'type'         => 'string',
				'description'  => 'Instagram profile picture URL (HD quality)',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'esc_url_raw',
			),
			'_apollo_avatar_attachment_id'   => array(
				'type'         => 'integer',
				'description'  => 'WordPress attachment ID for downloaded Instagram avatar',
				'single'       => true,
				'show_in_rest' => true,
			),
			'avatar_thumb'                   => array(
				'type'         => 'string',
				'description'  => 'Avatar thumbnail URL',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'esc_url_raw',
			),
			'_apollo_verification_token'     => array(
				'type'         => 'string',
				'description'  => 'Email verification token',
				'single'       => true,
				'show_in_rest' => false,
				'sanitize'     => 'sanitize_text_field',
			),
			'_apollo_password_reset_token'   => array(
				'type'         => 'string',
				'description'  => 'Password reset token',
				'single'       => true,
				'show_in_rest' => false,
				'sanitize'     => 'sanitize_text_field',
			),
			'_apollo_password_reset_expires' => array(
				'type'         => 'integer',
				'description'  => 'Reset token expiration timestamp',
				'single'       => true,
				'show_in_rest' => false,
			),
			'_apollo_login_attempts'         => array(
				'type'         => 'integer',
				'description'  => 'Failed login attempts count',
				'single'       => true,
				'show_in_rest' => false,
				'default'      => 0,
			),

			// ─────────────────────────────────────────────────────────────
			// CHAT META (apollo-chat)
			// ─────────────────────────────────────────────────────────────
			'_apollo_chat_status'            => array(
				'type'         => 'string',
				'description'  => 'Current presence status',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 'offline',
				'enum'         => array( 'online', 'away', 'busy', 'offline' ),
			),
			'_apollo_chat_last_seen'         => array(
				'type'         => 'integer',
				'description'  => 'Timestamp of last activity (Unix timestamp)',
				'single'       => true,
				'show_in_rest' => true,
			),
			'_apollo_chat_preferences'       => array(
				'type'         => 'array',
				'description'  => 'Chat notification preferences (sound, browser, email)',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'sound'   => array( 'type' => 'boolean' ),
								'browser' => array( 'type' => 'boolean' ),
								'email'   => array( 'type' => 'boolean' ),
							),
						),
					),
				),
				'default'      => array(),
			),
			'_apollo_chat_blocked_users'     => array(
				'type'         => 'array',
				'description'  => 'Array of blocked user IDs',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'default'      => array(),
			),
			'_apollo_chat_muted_threads'     => array(
				'type'         => 'array',
				'description'  => 'Array of muted thread IDs',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'default'      => array(),
			),

			// ─────────────────────────────────────────────────────────────
			// GAMIFICATION META (apollo-membership)
			// ─────────────────────────────────────────────────────────────
			'_apollo_triggered_triggers'     => array(
				'type'         => 'array',
				'description'  => 'Multi-dimensional array [site_id][trigger] => count',
				'single'       => true,
				'show_in_rest' => false,
				'default'      => array(),
			),
			'_apollo_can_notify_user'        => array(
				'type'         => 'boolean',
				'description'  => 'Email opt-out preference',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => true,
			),
			'_apollo_active_achievements'    => array(
				'type'         => 'array',
				'description'  => 'In-progress achievements',
				'single'       => true,
				'show_in_rest' => false,
				'default'      => array(),
			),
			'_apollo_achievement_count'      => array(
				'type'         => 'integer',
				'description'  => 'Total achievements earned',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 0,
			),
			'_apollo_points_total'           => array(
				'type'         => 'integer',
				'description'  => 'Total points accumulated',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 0,
			),
			'_apollo_current_rank'           => array(
				'type'         => 'string',
				'description'  => 'Current rank title',
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => 'sanitize_text_field',
			),
			'_apollo_rank_entry_id'          => array(
				'type'         => 'integer',
				'description'  => 'Current rank entry ID',
				'single'       => true,
				'show_in_rest' => true,
			),
		);

		// ═══════════════════════════════════════════════════════════════
		// TERM META - For taxonomy terms
		// ═══════════════════════════════════════════════════════════════

		$this->term_meta = array(
			// ─────────────────────────────────────────────────────────────
			// SEO TERM META (all taxonomies via empty string key)
			// ─────────────────────────────────────────────────────────────
			'' => array(
				'_apollo_seo_term' => array(
					'type'         => 'array',
					'description'  => 'Term SEO data',
					'single'       => true,
					'show_in_rest' => false,
					'default'      => array(),
				),
			),
		);
	}

	/**
	 * Allow modules to register meta definitions THROUGH apollo-core.
	 *
	 * Compatibility:
	 * - apollo_core_register_meta (legacy: post meta map by post_type)
	 * - apollo_core_register_post_meta
	 * - apollo_core_register_user_meta
	 * - apollo_core_register_term_meta
	 */
	public function apply_external_definitions(): void {
		$legacy_post_meta = apply_filters( 'apollo_core_register_meta', $this->post_meta );
		if ( is_array( $legacy_post_meta ) ) {
			$this->post_meta = $legacy_post_meta;
		}

		$post_meta = apply_filters( 'apollo_core_register_post_meta', $this->post_meta );
		if ( is_array( $post_meta ) ) {
			$this->post_meta = $post_meta;
		}

		$user_meta = apply_filters( 'apollo_core_register_user_meta', $this->user_meta );
		if ( is_array( $user_meta ) ) {
			$this->user_meta = $user_meta;
		}

		$term_meta = apply_filters( 'apollo_core_register_term_meta', $this->term_meta );
		if ( is_array( $term_meta ) ) {
			$this->term_meta = $term_meta;
		}
	}

	/**
	 * Register all meta
	 */
	public function register_all_meta(): void {
		// Register post meta
		foreach ( $this->post_meta as $post_type => $metas ) {
			foreach ( $metas as $key => $args ) {
				$this->register_post_meta( $post_type, $key, $args );
			}
		}

		// Register user meta
		foreach ( $this->user_meta as $key => $args ) {
			$this->register_user_meta( $key, $args );
		}

		// Register term meta
		foreach ( $this->term_meta as $taxonomy => $metas ) {
			foreach ( $metas as $key => $args ) {
				$this->register_term_meta( $taxonomy, $key, $args );
			}
		}

		// Fire action
		do_action(
			'apollo/meta/registered',
			array(
				'post_meta' => array_keys( $this->post_meta ),
				'user_meta' => array_keys( $this->user_meta ),
				'term_meta' => array_keys( $this->term_meta ),
			)
		);
	}

	/**
	 * Register single post meta
	 */
	private function register_post_meta( string $post_type, string $key, array $args ): void {
		$defaults = array(
			'type'              => 'string',
			'description'       => '',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => null,
			'auth_callback'     => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Handle sanitize shortcut
		if ( isset( $args['sanitize'] ) && is_string( $args['sanitize'] ) ) {
			$args['sanitize_callback'] = $args['sanitize'];
			unset( $args['sanitize'] );
		}

		// Handle enum validation
		if ( isset( $args['enum'] ) ) {
			$enum                      = $args['enum'];
			$args['sanitize_callback'] = function ( $value ) use ( $enum ) {
				return in_array( $value, $enum, true ) ? $value : $enum[0];
			};
			unset( $args['enum'] );
		}

		register_post_meta( $post_type, $key, $args );
	}

	/**
	 * Register single user meta
	 */
	private function register_user_meta( string $key, array $args ): void {
		$defaults = array(
			'type'              => 'string',
			'description'       => '',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => null,
			'auth_callback'     => function ( $allowed, $meta_key, $user_id ) {
				return current_user_can( 'edit_user', $user_id );
			},
		);

		$args = wp_parse_args( $args, $defaults );

		// Handle sanitize shortcut
		if ( isset( $args['sanitize'] ) && is_string( $args['sanitize'] ) ) {
			$args['sanitize_callback'] = $args['sanitize'];
			unset( $args['sanitize'] );
		}

		// Handle enum validation (scalar or array items when items_enum is set).
		if ( isset( $args['enum'] ) && is_array( $args['enum'] ) && ! empty( $args['enum'] ) ) {
			$enum                      = $args['enum'];
			$args['sanitize_callback'] = function ( $value ) use ( $enum ) {
				return in_array( $value, $enum, true ) ? $value : '';
			};
			unset( $args['enum'] );
		}

		if ( ! empty( $args['items_enum'] ) && ( $args['type'] ?? '' ) === 'array' ) {
			$valid_slugs = class_exists( '\Apollo\Core\Config\MembershipRegistry' )
				? \Apollo\Core\Config\MembershipRegistry::all_valid_slugs()
				: array( 'nao-verificado' );

			$args['sanitize_callback'] = function ( $value ) use ( $valid_slugs ) {
				if ( function_exists( 'apollo_membership_normalize_storage' ) ) {
					return apollo_membership_normalize_storage( $value );
				}

				if ( ! is_array( $value ) ) {
					$value = is_string( $value ) && '' !== $value ? array( $value ) : array( 'nao-verificado' );
				}

				$normalized = array();
				foreach ( $value as $slug ) {
					$slug = sanitize_key( (string) $slug );
					if ( '' !== $slug && in_array( $slug, $valid_slugs, true ) ) {
						$normalized[] = $slug;
					}
				}

				return ! empty( $normalized ) ? array_values( array_unique( $normalized ) ) : array( 'nao-verificado' );
			};

			if ( is_array( $args['show_in_rest'] ?? null ) ) {
				$args['show_in_rest']['schema']['items']['enum'] = $valid_slugs;
			}

			unset( $args['items_enum'] );
		}

		register_meta( 'user', $key, $args );
	}

	/**
	 * Register single term meta.
	 */
	private function register_term_meta( string $taxonomy, string $key, array $args ): void {
		$defaults = array(
			'type'              => 'string',
			'description'       => '',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => null,
			'auth_callback'     => null,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( isset( $args['sanitize'] ) && is_string( $args['sanitize'] ) ) {
			$args['sanitize_callback'] = $args['sanitize'];
			unset( $args['sanitize'] );
		}

		if ( isset( $args['enum'] ) ) {
			$enum                      = $args['enum'];
			$args['sanitize_callback'] = function ( $value ) use ( $enum ) {
				return in_array( $value, $enum, true ) ? $value : '';
			};
			unset( $args['enum'] );
		}

		register_term_meta( $taxonomy, $key, $args );
	}

	/**
	 * Get all post meta definitions
	 */
	public function get_post_meta_definitions(): array {
		return $this->post_meta;
	}

	/**
	 * Get all user meta definitions
	 */
	public function get_user_meta_definitions(): array {
		return $this->user_meta;
	}

	/**
	 * Get meta definition for specific CPT
	 */
	public function get_cpt_meta( string $cpt ): array {
		return $this->post_meta[ $cpt ] ?? array();
	}
}
