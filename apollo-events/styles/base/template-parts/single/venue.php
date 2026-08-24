<?php
/**
 * Single Event — Loc block
 *
 * Structure (final mockup): full-bleed photo slider → 30dvh full-bleed map,
 * glued flush with zero gap → loc name/address + "how do I get there" helper.
 *
 * Photos: loc gallery first, event banner + gallery as fallback (resolved in
 * apollo_event_single_context()).
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $loc ) ) {
	return;
}

$uid          = (string) ( $uid ?? '' );
$venue_photos = is_array( $venue_photos ?? null ) ? $venue_photos : array();

$lat  = isset( $loc['lat'] ) ? (float) $loc['lat'] : 0.0;
$lng  = isset( $loc['lng'] ) ? (float) $loc['lng'] : 0.0;
$addr = (string) ( $loc['address'] ?? '' );
$city = (string) ( $loc['city'] ?? '' );

$addr_lines = array_values( array_filter( array( $addr, $city ) ) );
$has_coords = ( 0.0 !== $lat || 0.0 !== $lng );

$search_q = trim( $loc_name . ( $addr ? ' ' . $addr : '' ) . ( $city ? ' ' . $city : '' ) );
$gmaps    = $search_q
	? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $search_q )
	: 'https://www.google.com/maps';
$daddr = $search_q ?: 'Rio de Janeiro';
?>
  <!-- LOC -->
  <section class="ev-venue-scroll" id="<?php echo esc_attr( apollo_ev_id( 'venueScrollSec', $uid ) ); ?>"
           data-ev="venue" aria-label="<?php echo esc_attr( $loc_name ?: __( 'Local', 'apollo-events' ) ); ?>">

	<?php if ( ! empty( $venue_photos ) ) : ?>
    <div class="v-slider" data-ev="venue-slider">
      <div class="v-track" id="<?php echo esc_attr( apollo_ev_id( 'vTrack', $uid ) ); ?>" data-ev="venue-track">
		<?php foreach ( $venue_photos as $src ) : ?>
        <div class="v-slide"><img src="<?php echo esc_url( $src ); ?>" alt="" loading="lazy" decoding="async"></div>
		<?php endforeach; ?>
      </div>
		<?php if ( count( $venue_photos ) > 1 ) : ?>
      <div class="v-dots" id="<?php echo esc_attr( apollo_ev_id( 'vDots', $uid ) ); ?>" data-ev="venue-dots">
			<?php foreach ( $venue_photos as $vp_i => $vp_src ) : ?>
        <button type="button" class="v-dot<?php echo 0 === $vp_i ? ' on' : ''; ?>" data-vgo="<?php echo (int) $vp_i; ?>"
                aria-label="<?php echo esc_attr( sprintf( /* translators: %d: photo number */ __( 'Foto %d', 'apollo-events' ), $vp_i + 1 ) ); ?>"></button>
			<?php endforeach; ?>
      </div>
		<?php endif; ?>
    </div>
	<?php endif; ?>

	<?php if ( $has_coords ) : ?>
    <div class="ev-map" data-ev="venue-map">
      <div class="loc-map" id="<?php echo esc_attr( apollo_ev_id( 'venueMapFrame', $uid ) ); ?>"
           data-ev="venue-map-frame"
           data-lat="<?php echo esc_attr( (string) $lat ); ?>"
           data-lng="<?php echo esc_attr( (string) $lng ); ?>"
           role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: loc name */ __( 'Mapa · %s', 'apollo-events' ), $loc_name ) ); ?>"></div>
    </div>
	<?php endif; ?>

    <div class="ev-vbody">
      <div class="ev-vnr">
        <div>
          <h2 class="ev-vname" id="<?php echo esc_attr( apollo_ev_id( 'venueName', $uid ) ); ?>"><?php echo esc_html( $loc_name ?: __( 'Local', 'apollo-events' ) ); ?></h2>
			<?php if ( ! empty( $addr_lines ) ) : ?>
          <p class="ev-vaddr" id="<?php echo esc_attr( apollo_ev_id( 'venueAddr', $uid ) ); ?>"><?php echo wp_kses( implode( '<br>', array_map( 'esc_html', $addr_lines ) ), array( 'br' => array() ) ); ?></p>
			<?php endif; ?>
        </div>
        <a class="ev-vext" href="<?php echo esc_url( $gmaps ); ?>" target="_blank" rel="noopener"
           aria-label="<?php esc_attr_e( 'Abrir no Maps', 'apollo-events' ); ?>"><i class="ri-external-link-line"></i></a>
      </div>

      <form class="ev-dir" action="https://www.google.com/maps/dir/" method="get" target="_blank" rel="noopener">
        <input class="ev-dir-i" type="text" name="saddr"
               placeholder="<?php esc_attr_e( 'De onde você vem?', 'apollo-events' ); ?>"
               aria-label="<?php esc_attr_e( 'Ponto de partida', 'apollo-events' ); ?>"
               autocomplete="street-address">
        <input type="hidden" name="daddr" value="<?php echo esc_attr( $daddr ); ?>">
        <button type="submit" class="ev-dir-go" aria-label="<?php esc_attr_e( 'Traçar rota', 'apollo-events' ); ?>"><i class="ri-guide-line"></i></button>
      </form>
    </div>
  </section>
