<?php
/**
 * Single Event — RSVP
 *
 * Final mockup behaviour:
 *  - selected card gets the animated mesh-gradient border (.rc-glow + ::before/::after)
 *    driven by --pointer-° / --pointer-d, NOT a flat accent border;
 *  - selected checkbox background is var(--black-1) (core.js token);
 *  - no participant avatars anywhere — when the viewer picks an option their
 *    own .ev-status is moved INSIDE the chosen card by the JS.
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uid = (string) ( $uid ?? '' );

$apollo_rsvp_options = array(
	'will'  => array(
		'emoji' => '🔥',
		'label' => __( 'Eu vou!!', 'apollo-events' ),
		'sub'   => __( 'Confirmo presença', 'apollo-events' ),
		'id'    => 'rcWill',
	),
	'maybe' => array(
		'emoji' => '👀',
		'label' => __( 'Quero ir.', 'apollo-events' ),
		'sub'   => __( 'Ainda pensando', 'apollo-events' ),
		'id'    => 'rcMaybe',
	),
);
?>
  <!-- RSVP -->
  <section class="ev-sec" id="<?php echo esc_attr( apollo_ev_id( 'rsvpSec', $uid ) ); ?>" data-ev="rsvp">
    <h2 class="ev-h2"><?php esc_html_e( 'Interesse no evento', 'apollo-events' ); ?></h2>
    <div class="ev-lede">
      <p><?php esc_html_e( 'Demonstrar interesse abre o Warm-Up — o canal vivo antes da pista.', 'apollo-events' ); ?></p>
    </div>

    <div class="ev-rsvp-grid" id="<?php echo esc_attr( apollo_ev_id( 'rsvpGrid', $uid ) ); ?>" data-ev="rsvp-grid">
		<?php foreach ( $apollo_rsvp_options as $apollo_rsvp_type => $apollo_rsvp_opt ) : ?>
      <div class="ev-rsvp-cell" data-type="<?php echo esc_attr( $apollo_rsvp_type ); ?>">
        <button type="button" class="ev-rsvp-card"
                id="<?php echo esc_attr( apollo_ev_id( $apollo_rsvp_opt['id'], $uid ) ); ?>"
                data-rsvp="<?php echo esc_attr( $apollo_rsvp_type ); ?>" aria-pressed="false">
          <span class="rc-glow" aria-hidden="true"></span>
          <div class="ev-rsvp-top">
            <span class="ev-rsvp-emoji" aria-hidden="true"><?php echo esc_html( $apollo_rsvp_opt['emoji'] ); ?></span>
            <span class="ev-radio" aria-hidden="true"></span>
          </div>
          <div>
            <div class="ev-rsvp-lbl"><?php echo esc_html( $apollo_rsvp_opt['label'] ); ?></div>
            <div class="ev-rsvp-sub"><?php echo esc_html( $apollo_rsvp_opt['sub'] ); ?></div>
          </div>
        </button>
      </div>
		<?php endforeach; ?>
    </div>

    <!-- Moved inside the selected card by the JS; borderless because it then
         sits within the card chrome. -->
    <div class="ev-status" id="<?php echo esc_attr( apollo_ev_id( 'myBar', $uid ) ); ?>" data-ev="rsvp-status">
      <p id="<?php echo esc_attr( apollo_ev_id( 'myTxt', $uid ) ); ?>" data-ev="rsvp-status-text"></p>
      <button type="button" class="ev-warmup" data-ev-action="open-chat">
        <i class="ri-chat-3-line"></i> <?php esc_html_e( 'Warm-Up', 'apollo-events' ); ?>
      </button>
    </div>
  </section>
