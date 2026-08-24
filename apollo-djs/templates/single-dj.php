<?php

/**
 * Template: Single DJ Page — Vinyl Player Design
 * Standalone HTML document (no get_header/get_footer)
 * Ported from 1212 workspace core-dj-single-v2.php
 *
 * @package Apollo\DJs
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Ensure valid DJ post
if ( ! have_posts() || get_post_type() !== 'dj' ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

the_post();
global $post;

$dj_id   = $post->ID;
$dj_name = get_post_meta( $dj_id, '_dj_name', true ) ?: get_the_title( $dj_id );

// Photo
$dj_photo_url = get_the_post_thumbnail_url( $dj_id, 'large' );
if ( ! $dj_photo_url ) {
	$dj_photo_url = get_post_meta( $dj_id, '_dj_image', true );
}
if ( ! $dj_photo_url ) {
	$dj_photo_url = APOLLO_DJ_URL . 'assets/images/placeholder-dj.svg';
}

// Bio
$dj_bio_raw = get_post_meta( $dj_id, '_dj_bio', true ) ?: '';
$dj_tagline = '';
if ( ! empty( $dj_bio_raw ) ) {
	$bio_lines = preg_split( '/\r\n|\r|\n/', $dj_bio_raw );
	if ( count( $bio_lines ) > 1 && strlen( $bio_lines[0] ) < 100 ) {
		$dj_tagline = trim( $bio_lines[0] );
	}
}

$dj_bio_full    = ! empty( $dj_bio_raw )
	? apply_filters( 'the_content', $dj_bio_raw )
	: apply_filters( 'the_content', get_the_content() );
$dj_bio_excerpt = has_excerpt()
	? get_the_excerpt()
	: wp_trim_words( wp_strip_all_tags( $dj_bio_full ), 40 );

// Roles
$dj_roles = 'DJ';

// Projects
$dj_projects = array_filter(
	array(
		get_post_meta( $dj_id, '_dj_original_project_1', true ),
		get_post_meta( $dj_id, '_dj_original_project_2', true ),
		get_post_meta( $dj_id, '_dj_original_project_3', true ),
	)
);

// SoundCloud
$dj_soundcloud  = get_post_meta( $dj_id, '_dj_soundcloud', true ) ?: '';
$dj_set_url     = get_post_meta( $dj_id, '_dj_set_url', true ) ?: $dj_soundcloud;
$dj_track_title = '';
if ( ! empty( $dj_set_url ) && strpos( $dj_set_url, 'soundcloud.com' ) !== false ) {
	$url_parts      = explode( '/', rtrim( $dj_set_url, '/' ) );
	$dj_track_title = ucwords( str_replace( '-', ' ', end( $url_parts ) ) );
}
$sc_embed_url = '';
if ( ! empty( $dj_set_url ) ) {
	$sc_embed_url = 'https://w.soundcloud.com/player/?url=' . rawurlencode( $dj_set_url )
		. '&color=%23ff5500&auto_play=false&hide_related=true&show_comments=false'
		. '&show_user=false&show_reposts=false&show_teaser=false';
}

// Name formatting (line break on multi-word)
$words             = explode( ' ', trim( $dj_name ) );
$dj_name_formatted = count( $words ) >= 2
	? esc_html( $words[0] ) . '<br>' . esc_html( implode( ' ', array_slice( $words, 1 ) ) )
	: esc_html( $dj_name );

// Build all links
$link_defs = array(
	'music'     => array(
		'soundcloud' => array( '_dj_soundcloud', 'ri-soundcloud-line', 'SoundCloud' ),
		'spotify'    => array( '_dj_spotify', 'ri-spotify-line', 'Spotify' ),
		'youtube'    => array( '_dj_youtube', 'ri-youtube-line', 'YouTube' ),
	),
	'social'    => array(
		'instagram' => array( '_dj_instagram', 'ri-instagram-line', 'Instagram' ),
		'facebook'  => array( '_dj_facebook', 'ri-facebook-circle-line', 'Facebook' ),
		'twitter'   => array( '_dj_twitter', 'ri-twitter-x-line', 'Twitter' ),
		'tiktok'    => array( '_dj_tiktok', 'ri-tiktok-line', 'TikTok' ),
	),
	'platforms' => array(
		'mixcloud' => array( '_dj_mixcloud', 'ri-cloud-line', 'Mixcloud' ),
		'beatport' => array( '_dj_beatport', 'ri-vip-crown-line', 'Beatport' ),
		'bandcamp' => array( '_dj_bandcamp', 'ri-album-line', 'Bandcamp' ),
		'ra'       => array( '_dj_resident_advisor', 'ri-radio-line', 'Resident Advisor' ),
		'website'  => array( '_dj_website', 'ri-external-link-line', 'Site oficial' ),
	),
	'assets'    => array(
		'mediakit' => array( '_dj_media_kit_url', 'ri-clipboard-line', 'Media kit' ),
		'rider'    => array( '_dj_rider_url', 'ri-clipboard-fill', 'Rider' ),
		'mix'      => array( '_dj_mix_url', 'ri-play-list-2-line', 'Mix / Playlist' ),
	),
);

$all_links = array();
foreach ( $link_defs as $group => $defs ) {
	$all_links[ $group ] = array();
	foreach ( $defs as $key => $def ) {
		$url = get_post_meta( $dj_id, $def[0], true );
		if ( $url ) {
			$all_links[ $group ][ $key ] = array(
				'url'   => $url,
				'icon'  => $def[1],
				'label' => $def[2],
			);
		}
	}
}

$music_links    = $all_links['music'] ?? array();
$social_links   = $all_links['social'] ?? array();
$platform_links = $all_links['platforms'] ?? array();
$asset_links    = $all_links['assets'] ?? array();
$media_kit_url  = $all_links['assets']['mediakit']['url'] ?? '';

$is_print = isset( $_GET['print'] ) || isset( $_GET['pdf'] );

// Plugin URL
$plugin_url = APOLLO_DJ_URL;
$cdn_base   = 'https://assets.apollo.rio.br/';

// Template parts directory
$parts_dir = APOLLO_DJ_DIR . 'templates/parts/dj/';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $dj_name ); ?> · Apollo Roster</title>
	<link rel="icon" href="<?php echo esc_url( $cdn_base . 'img/neon-green.webp' ); ?>" type="image/webp">

	<?php do_action( 'apollo_dj_single_head_before', $dj_id ); ?>

	<!-- Apollo CDN — Canvas Mode -->
	<script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

	<!-- DJ-specific stylesheet -->
	<link rel="stylesheet" href="<?php echo esc_url( $plugin_url . 'assets/css/dj-single.css' ); ?>">

	<?php if ( ! empty( $sc_embed_url ) ) : ?>
		<script src="https://w.soundcloud.com/player/api.js"></script>
	<?php endif; ?>

	<?php do_action( 'apollo_dj_single_head_after', $dj_id ); ?>
</head>

<body class="dj-single-page<?php echo $is_print ? ' is-print-mode' : ''; ?>" data-dj-id="<?php echo esc_attr( $dj_id ); ?>">

	<?php do_action( 'apollo_dj_single_body_start', $dj_id ); ?>

	<section class="dj-shell">
		<div class="dj-page" id="djPage">
			<div class="dj-content">

				<?php
				// HEADER
				?>
				<?php
				if ( file_exists( $parts_dir . 'header.php' ) ) {
					include $parts_dir . 'header.php';
				}
				?>

				<?php
				// HERO
				?>
				<?php
				if ( file_exists( $parts_dir . 'hero.php' ) ) {
					include $parts_dir . 'hero.php';
				}
				?>

				<?php
				// VINYL PLAYER
				?>
				<?php
				if ( ! $is_print && ! empty( $sc_embed_url ) && file_exists( $parts_dir . 'player.php' ) ) {
					include $parts_dir . 'player.php';
				}
				?>

				<?php
				// INFO GRID
				?>
				<?php
				if ( file_exists( $parts_dir . 'info-grid.php' ) ) {
					include $parts_dir . 'info-grid.php';
				}
				?>

				<?php
				// FOOTER
				?>
				<?php
				if ( file_exists( $parts_dir . 'footer.php' ) ) {
					include $parts_dir . 'footer.php';
				}
				?>

			</div>
		</div>
	</section>

	<?php
	// BIO MODAL
	?>
	<?php
	if ( ! $is_print && ! empty( $dj_bio_full ) && file_exists( $parts_dir . 'bio-modal.php' ) ) {
		include $parts_dir . 'bio-modal.php';
	}
	?>

	<?php do_action( 'apollo_dj_single_before_scripts', $dj_id ); ?>

	<script src="<?php echo esc_url( $plugin_url . 'assets/js/dj-single.js' ); ?>"></script>

	<?php do_action( 'apollo_dj_single_body_end', $dj_id ); ?>

	<?php wp_footer(); ?>
</body>

</html>
<?php wp_reset_postdata(); ?>