<?php
/**
 * @partial dock
 * @expects booking / kit / soundcloud / bandcamp / spotify from $ctx
 * Icon-only fixed dock (luxury grade).
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$booking      = (string) ( $ctx['bookingEmail'] ?? '' );
$booking_href = $booking ? 'mailto:' . $booking . '?subject=' . rawurlencode( 'Booking — ' . $dj_name ) : '#';
$kit          = (string) ( $ctx['mediaKitUrl'] ?? '' );
$sc           = (string) ( $ctx['soundcloud'] ?? '' );
$bc           = (string) ( $ctx['bandcamp'] ?? '' );
$sp           = (string) ( $ctx['spotify'] ?? '' );
?>
<div class="dock" id="dock">
	<a class="icon-btn pill" id="dockBooking" href="<?php echo esc_url( $booking_href ); ?>" aria-label="<?php esc_attr_e( 'Contato booking', 'apollo-djs' ); ?>" title="<?php esc_attr_e( 'Contato booking', 'apollo-djs' ); ?>"><i class="ri-mail-send-line"></i></a>
	<?php if ( $kit ) : ?>
		<a class="icon-btn pill" id="dockKit" href="<?php echo esc_url( $kit ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Kit Promo', 'apollo-djs' ); ?>" title="<?php esc_attr_e( 'Kit Promo', 'apollo-djs' ); ?>"><i class="ri-folder-image-line"></i></a>
	<?php endif; ?>
	<?php if ( $sc ) : ?>
		<a class="icon-btn pill" id="dockSoundcloud" href="<?php echo esc_url( $sc ); ?>" target="_blank" rel="noopener" aria-label="SoundCloud" title="SoundCloud"><i class="ri-soundcloud-line"></i></a>
	<?php endif; ?>
	<?php if ( $bc ) : ?>
		<a class="icon-btn pill" id="dockBandcamp" href="<?php echo esc_url( $bc ); ?>" target="_blank" rel="noopener" aria-label="Bandcamp" title="Bandcamp"><i class="ri-bandcamp-line"></i></a>
	<?php endif; ?>
	<?php if ( $sp ) : ?>
		<a class="icon-btn pill" id="dockSpotify" href="<?php echo esc_url( $sp ); ?>" target="_blank" rel="noopener" aria-label="Spotify" title="Spotify"><i class="ri-spotify-line"></i></a>
	<?php endif; ?>
	<button type="button" class="icon-btn pill" id="dockFollow" aria-label="<?php esc_attr_e( 'Seguir', 'apollo-djs' ); ?>" title="<?php esc_attr_e( 'Seguir', 'apollo-djs' ); ?>"><i class="ri-heart-3-line"></i></button>
	<button type="button" class="icon-btn pill" data-share="profile" aria-label="<?php esc_attr_e( 'Compartilhar', 'apollo-djs' ); ?>" title="<?php esc_attr_e( 'Compartilhar', 'apollo-djs' ); ?>"><i class="ri-share-forward-box-line"></i></button>
</div>
