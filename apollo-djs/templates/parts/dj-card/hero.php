<?php
/**
 * @partial hero
 * @expects $ctx['bioShort']  — meta._dj_bio_short
 *          $ctx['heroImage'] — meta._dj_banner | _dj_image
 *          $ctx['bookingEmail'] — meta._dj_booking
 *          $ctx['setUrl']    — meta._dj_set_url (anchor #sound fallback)
 *          $ctx['stats']     — computed pills
 *          $ctx['genres']    — taxonomy sound
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$bio_short   = (string) ( $ctx['bioShort'] ?? '' );
$hero_img    = (string) ( $ctx['heroImage'] ?? '' );
$genres      = $ctx['genres'] ?? array();
$eyebrow     = ! empty( $genres ) ? 'Rio de Janeiro — ' . implode( ' & ', array_slice( (array) $genres, 0, 2 ) ) : 'Rio de Janeiro';
$stats       = $ctx['stats'] ?? array();
$events_n    = (int) ( $stats['events'] ?? 0 );
$cities_n    = (int) ( $stats['cities'] ?? 0 );
$booking     = (string) ( $ctx['bookingEmail'] ?? '' );
$booking_href = $booking ? 'mailto:' . $booking . '?subject=' . rawurlencode( 'Booking — ' . $dj_name ) : '#';
?>
<header class="hero" id="hero">
	<div class="wrap">
		<div class="hero-eyebrow">
			<span class="lbl"><?php echo esc_html( $eyebrow ); ?></span>
			<span class="hero-live"><?php esc_html_e( 'Booking aberto', 'apollo-djs' ); ?></span>
		</div>

		<h1 class="display hero-name" id="heroName" aria-label="<?php echo esc_attr( $dj_name ); ?>">
			<span class="hn-line" aria-hidden="true"><?php echo esc_html( mb_strtoupper( $hero_line1 ) ); ?></span>
			<?php if ( $hero_line2 ) : ?>
				<span class="hn-line is-ac" aria-hidden="true"><?php echo esc_html( mb_strtoupper( $hero_line2 ) ); ?></span>
			<?php endif; ?>
		</h1>

		<div class="hero-under">
			<?php if ( $bio_short ) : ?>
				<p class="hero-bio rv"><strong><?php echo esc_html( $dj_name ); ?>.</strong> <?php echo esc_html( $bio_short ); ?></p>
			<?php endif; ?>
			<div class="hero-ctas rv">
				<a class="btn btn-ink pill" id="heroBooking" href="<?php echo esc_url( $booking_href ); ?>"><i class="ri-mail-send-line"></i> <?php esc_html_e( 'Contato booking', 'apollo-djs' ); ?></a>
				<a class="btn btn-line pill" href="#sound"><i class="ri-soundcloud-fill"></i> <?php esc_html_e( 'Ouvir DJ set', 'apollo-djs' ); ?></a>
			</div>
		</div>
	</div>

	<figure class="hero-figure" id="heroFig">
		<img id="heroImg" src="<?php echo esc_url( $hero_img ); ?>" alt="<?php echo esc_attr( $dj_name ); ?>">
		<div class="hero-cue" aria-hidden="true"><?php esc_html_e( 'Deslize', 'apollo-djs' ); ?> <i class="ri-arrow-down-s-line"></i></div>
		<div class="hero-pills">
			<span class="gpill pill"><strong><?php echo esc_html( (string) $events_n ); ?></strong><span><?php esc_html_e( 'Eventos', 'apollo-djs' ); ?></span></span>
			<span class="gpill pill"><strong><?php echo esc_html( (string) $cities_n ); ?></strong><span><?php esc_html_e( 'Cidades', 'apollo-djs' ); ?></span></span>
		</div>
	</figure>
</header>
