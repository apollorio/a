<?php
/**
 * Template: Single Local Page — GPS / Venue Profile
 * Standalone HTML document (no get_header/get_footer)
 * Modular: delegates rendering to templates/parts/*.php
 *
 * @package Apollo\Local
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! have_posts() || get_post_type() !== 'local' ) {
status_header( 404 );
nocache_headers();
include get_404_template();
exit;
}

the_post();
global $post;

$local_id   = $post->ID;
$local_name = get_post_meta( $local_id, '_local_name', true ) ?: get_the_title( $local_id );

// Address
$address      = get_post_meta( $local_id, '_local_address', true ) ?: '';
$city         = get_post_meta( $local_id, '_local_city', true ) ?: '';
$state        = get_post_meta( $local_id, '_local_state', true ) ?: '';
$full_address = trim( implode( ', ', array_filter( array( $address, $city, $state ) ) ) );

// Coordinates
$lat        = get_post_meta( $local_id, '_local_lat', true ) ?: '';
$lng        = get_post_meta( $local_id, '_local_lng', true ) ?: '';
$has_coords = ! empty( $lat ) && ! empty( $lng );

// Description / Bio
$local_desc = get_post_meta( $local_id, '_local_description', true ) ?: '';
$local_bio  = ! empty( $local_desc )
? apply_filters( 'the_content', $local_desc )
: apply_filters( 'the_content', get_the_content() );

// Social links — uses helper from includes/functions-address.php
$social_links = function_exists( 'apollo_local_get_links' )
? apollo_local_get_links( $local_id )
: array();

// Gallery images (up to 5 meta slots + featured fallback)
$gallery = array();
for ( $i = 1; $i <= 5; $i++ ) {
$img = get_post_meta( $local_id, "_local_image_{$i}", true );
if ( $img ) {
$gallery[] = is_numeric( $img ) ? wp_get_attachment_image_url( (int) $img, 'large' ) : $img;
}
}
if ( empty( $gallery ) && has_post_thumbnail( $local_id ) ) {
$gallery[] = get_the_post_thumbnail_url( $local_id, 'large' );
}

// Venue type label (first term from local_type taxonomy)
$loc_types = get_the_terms( $local_id, 'local_type' );
$loc_label = '';
if ( ! is_wp_error( $loc_types ) && ! empty( $loc_types ) ) {
$loc_label = $loc_types[0]->name;
}

// Extra fields used by partials
$capacity = get_post_meta( $local_id, '_local_capacity', true ) ?: '';
$phone    = get_post_meta( $local_id, '_local_phone', true ) ?: '';

// Upcoming events — uses helper from includes/functions-events.php
$upcoming_events = function_exists( 'apollo_local_get_events_data' )
? apollo_local_get_events_data( $local_id, 6 )
: array();

// Testimonials stored as JSON or serialised array
$testimonials_raw = get_post_meta( $local_id, '_local_testimonials', true );
$testimonials     = array();
if ( ! empty( $testimonials_raw ) ) {
if ( is_string( $testimonials_raw ) ) {
$decoded = json_decode( $testimonials_raw, true );
if ( is_array( $decoded ) ) {
$testimonials = $decoded;
}
} elseif ( is_array( $testimonials_raw ) ) {
$testimonials = $testimonials_raw;
}
}

$plugin_url = defined( 'APOLLO_LOCAL_URL' ) ? APOLLO_LOCAL_URL : plugin_dir_url( __DIR__ );
$cdn_base   = 'https://assets.apollo.rio.br/';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $local_name ); ?> · Apollo GPS</title>
<link rel="icon" href="<?php echo esc_url( $cdn_base . 'img/neon-green.webp' ); ?>" type="image/webp">

<!-- Apollo CDN — Canvas Mode -->
<script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

<!-- Local assets -->
<link rel="stylesheet" href="<?php echo esc_url( $plugin_url . 'assets/css/local-single.css' ); ?>">
<?php do_action( 'apollo_local_single_head', $local_id ); ?>
</head>

<body class="local-single-page" data-local-id="<?php echo esc_attr( $local_id ); ?>">

<main class="mobile-container">

<?php include __DIR__ . '/parts/loc-hero.php'; ?>

<div class="loc-body">

<?php if ( ! empty( $social_links ) ) : ?>
<div class="social-row">
<?php foreach ( $social_links as $key => $link ) : ?>
<a href="<?php echo esc_url( $link['url'] ); ?>"
class="social-btn<?php echo 'website' === $key ? ' social-btn--primary' : ''; ?>"
target="_blank" rel="noopener noreferrer"
title="<?php echo esc_attr( $link['label'] ); ?>">
<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
<span><?php echo esc_html( $link['label'] ); ?></span>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ( ! empty( $local_bio ) ) : ?>
<section class="loc-section">
<h2 class="section-title">
<i class="ri-information-line"></i>
<?php esc_html_e( 'Sobre o local', 'apollo-local' ); ?>
</h2>
<div class="loc-bio"><?php echo wp_kses_post( $local_bio ); ?></div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/parts/loc-sidebar.php'; ?>

<?php include __DIR__ . '/parts/loc-events.php'; ?>

<?php include __DIR__ . '/parts/loc-gallery.php'; ?>

<?php include __DIR__ . '/parts/loc-residents.php'; ?>

<?php include __DIR__ . '/parts/loc-reviews.php'; ?>

<footer class="loc-footer">
<span>Apollo::rio · GPS</span>
</footer>

</div><!-- /.loc-body -->

</main><!-- /.mobile-container -->

<script src="<?php echo esc_url( $plugin_url . 'assets/js/loc-hero.js' ); ?>" defer></script>
<script src="<?php echo esc_url( $plugin_url . 'assets/js/loc-map.js' ); ?>" defer></script>
<script src="<?php echo esc_url( $plugin_url . 'assets/js/loc-ui.js' ); ?>" defer></script>

<?php if ( $has_coords ) : ?>
<script>
window.apolloLocMap = {
lat: <?php echo (float) $lat; ?>,
lng: <?php echo (float) $lng; ?>,
zoom: 15,
name: <?php echo wp_json_encode( $local_name ); ?>
};
</script>
<?php endif; ?>

<?php wp_footer(); ?>

</body>

</html>
<?php wp_reset_postdata(); ?>
