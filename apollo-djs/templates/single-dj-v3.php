<?php
/**
 * Single DJ — Business Card (Canvas)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * APOLLO-DJS · DJ BUSINESS CARD — PHP HANDOFF MAP
 * ═══════════════════════════════════════════════════════════════════════════
 * Source mockup: screen/single cpt/dj/dj-single-page.html
 * Data SSOT:     apollo_get_dj_context( $dj_id ) → wp_localize_script APOLLO_DJ
 *
 * Partial                          | Meta / query
 * ---------------------------------|------------------------------------------
 * progress.php                     | —
 * ev-top.php                       | title / _dj_name
 * hero.php                         | _dj_bio_short, _dj_banner|_dj_image,
 *                                  | _dj_booking, _dj_set_url, stats pills
 * statement.php                    | _dj_statement (fallback _dj_bio_short)
 * marquee.php                      | taxonomy sound via genres[]
 * played-on.php                    | WP_Query event WHERE _event_dj_ids LIKE
 *                                  | AND _event_start_date < today
 * numbers.php                      | computed from past events (stats)
 * played-with.php                  | co-DJs from past lineup (_dj_image avatar)
 * out-now.php                      | _dj_tracks[] title/url/year/duration
 *                                  | display meta = "{year} · RIO DE JANEIRO · {duration}"
 *                                  | first 5 + row 06 Ver todos → lightbox
 * out-now-lightbox.php             | full _dj_tracks list
 * kit.php                          | _dj_media_kit_url → "Acessar Kit Promo"
 * about.php                        | _dj_bio, _dj_about_video|_dj_about_photo
 * footer.php                       | platform links, _dj_instagram, banner
 * dock.php                         | booking / kit / SC / BC / SP / share
 * toast.php                        | —
 * scripts.php                      | enqueue + localize APOLLO_DJ
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * @package Apollo\DJs
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! have_posts() || get_post_type() !== APOLLO_DJ_CPT ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

the_post();
global $post;

$dj_id   = (int) $post->ID;
$ctx     = apollo_get_dj_context( $dj_id );
$dj_name = (string) ( $ctx['name'] ?? get_the_title( $dj_id ) );
$dj_slug = $post->post_name;
$parts   = APOLLO_DJ_DIR . 'templates/parts/dj-card/';

$schema = array(
	'@context'    => 'https://schema.org',
	'@type'       => 'MusicGroup',
	'name'        => $dj_name,
	'genre'       => $ctx['genres'] ?? array(),
	'url'         => $ctx['permalink'] ?? get_permalink( $dj_id ),
	'image'       => $ctx['heroImage'] ?? '',
	'description' => $ctx['bioShort'] ?? '',
);

$name_parts = preg_split( '/\s+/', trim( $dj_name ) ) ?: array( $dj_name );
$hero_line1 = $name_parts[0] ?? $dj_name;
$hero_line2 = count( $name_parts ) > 1 ? implode( ' ', array_slice( $name_parts, 1 ) ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#ffffff">
	<title><?php echo esc_html( $dj_name ); ?> — Cartão de Artista · apollo.rio.br</title>
	<meta name="description" content="<?php echo esc_attr( $ctx['bioShort'] ?? '' ); ?>">
	<meta property="og:title" content="<?php echo esc_attr( $dj_name ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $ctx['bioShort'] ?? '' ); ?>">
	<meta property="og:type" content="profile">
	<?php if ( ! empty( $ctx['heroImage'] ) ) : ?>
		<meta property="og:image" content="<?php echo esc_url( $ctx['heroImage'] ); ?>">
	<?php endif; ?>

	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

	<?php do_action( 'apollo_dj_single_head_before', $dj_id ); ?>

	<link rel="preconnect" href="https://assets.apollo.rio.br" crossorigin>
	<link rel="preconnect" href="https://cdn.apollo.rio.br" crossorigin>
	<script>window.apolloIconConfig=window.apolloIconConfig||{};</script>
	<script src="<?php echo esc_url( function_exists( 'apollo_cdn_core_js_url' ) ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb' ); ?>" fetchpriority="high"></script>

	<link rel="stylesheet" href="<?php echo esc_url( APOLLO_DJ_URL . 'assets/css/dj-card-single.css' ); ?>?v=<?php echo esc_attr( APOLLO_DJ_VERSION ); ?>">

	<?php do_action( 'apollo_dj_single_head_after', $dj_id ); ?>
</head>
<body class="dj-card-single" data-dj-id="<?php echo esc_attr( (string) $dj_id ); ?>" data-dj-slug="<?php echo esc_attr( $dj_slug ); ?>">

<?php do_action( 'apollo_dj_single_body_start', $dj_id ); ?>

<?php
$include = static function ( string $file ) use ( $parts, $ctx, $dj_id, $dj_name, $hero_line1, $hero_line2 ): void {
	$path = $parts . $file;
	if ( file_exists( $path ) ) {
		include $path;
	}
};

$include( 'progress.php' );
$include( 'ev-top.php' );
$include( 'hero.php' );
$include( 'statement.php' );
$include( 'marquee.php' );
$include( 'played-on.php' );
$include( 'numbers.php' );
$include( 'played-with.php' );
$include( 'out-now.php' );
$include( 'kit.php' );
$include( 'about.php' );
$include( 'footer.php' );
$include( 'dock.php' );
$include( 'toast.php' );
$include( 'out-now-lightbox.php' );
?>

<?php do_action( 'apollo_dj_single_before_scripts', $dj_id ); ?>

<?php $include( 'scripts.php' ); ?>

<?php do_action( 'apollo_dj_single_body_end', $dj_id ); ?>
<?php wp_footer(); ?>
</body>
</html>
<?php
wp_reset_postdata();
