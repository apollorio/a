<?php
/**
 * Constantes do Apollo DJs
 *
 * @package Apollo\DJs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── REST ────────────────────────────────────────────────────────────────────
define( 'APOLLO_DJ_REST_NAMESPACE', 'apollo/v1' );

// ─── CPT ─────────────────────────────────────────────────────────────────────
define( 'APOLLO_DJ_CPT', 'dj' );

// ─── Taxonomies (GLOBAL BRIDGE via apollo-core) ─────────────────────────────
// sound taxonomy é compartilhada com apollo-events
define( 'APOLLO_DJ_TAX_SOUND', 'sound' );

// ─── Style ───────────────────────────────────────────────────────────────────
// 'apollo-v3' → styles/apollo-v3/single-dj.php bridge → templates/single-dj-v3.php
// (mockup dj-single-page.html build). 'apollo-v1' had NO styles/ folder, so every
// request silently fell back to the minimal styles/base/single-dj.php — that was
// why /dj/{slug} never matched the mockup. Archive keeps base fallback (no
// styles/apollo-v3/archive-dj.php), so /djs is unaffected by this switch.
define( 'APOLLO_DJ_DEFAULT_STYLE', 'apollo-v3' );

// ─── Cache ───────────────────────────────────────────────────────────────────
define( 'APOLLO_DJ_CACHE_GROUP', 'apollo_djs' );
define( 'APOLLO_DJ_CACHE_TTL', 300 );

// ─── Meta Keys — conforme apollo-registry.json ──────────────────────────────
define(
	'APOLLO_DJ_META_KEYS',
	array(
		'_dj_image',
		'_dj_banner',
		'_dj_website',
		'_dj_instagram',
		'_dj_soundcloud',
		'_dj_spotify',
		'_dj_youtube',
		'_dj_mixcloud',
		'_dj_user_id',
		'_dj_verified',
		'_dj_bio_short',
		'_dj_name',
		'_dj_bio',
		'_dj_facebook',
		'_dj_bandcamp',
		'_dj_beatport',
		'_dj_resident_advisor',
		'_dj_twitter',
		'_dj_tiktok',
		'_dj_original_project_1',
		'_dj_original_project_2',
		'_dj_original_project_3',
		'_dj_set_url',
		'_dj_media_kit_url',
		'_dj_rider_url',
		'_dj_mix_url',
		'_dj_booking',
		'_dj_about_photo',
		'_dj_about_video',
		'_dj_tracks',
		'_dj_gallery',
		'_dj_statement',
	)
);
