<?php
/**
 * Single Event — Warm-Up chat + gallery lightbox
 *
 * Both overlays are instance-scoped (data-ev hooks + uid-prefixed ids) so a
 * lightboxed event can carry its own chat without colliding with the host page.
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uid = (string) ( $uid ?? '' );
?>
<!-- WARM-UP CHAT -->
<div class="ev-chat" id="<?php echo esc_attr( apollo_ev_id( 'chatOv', $uid ) ); ?>" data-ev="chat" aria-hidden="true">
  <div class="ev-chat-bd" data-ev-action="close-chat"></div>
  <div class="ev-chat-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Warm-Up', 'apollo-events' ); ?>">
    <div class="ev-chat-knob" aria-hidden="true"></div>
    <div class="ev-chat-hd">
      <div>
        <strong><?php esc_html_e( 'Warm-Up', 'apollo-events' ); ?></strong>
        <small><?php echo esc_html( $title ); ?></small>
      </div>
      <button type="button" class="ev-chat-x" data-ev-action="close-chat" aria-label="<?php esc_attr_e( 'Fechar', 'apollo-events' ); ?>">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="ev-chat-msgs" data-ev="chat-msgs"></div>
    <div class="ev-chat-in" data-ev="chat-input-row">
      <input type="text" data-ev="chat-input"
             placeholder="<?php esc_attr_e( 'Mensagem para o Warm-Up.', 'apollo-events' ); ?>"
             aria-label="<?php esc_attr_e( 'Mensagem', 'apollo-events' ); ?>" autocomplete="off">
      <button type="button" data-ev-action="send-msg" aria-label="<?php esc_attr_e( 'Enviar', 'apollo-events' ); ?>">
        <i class="ri-send-plane-2-fill"></i>
      </button>
    </div>
    <a class="ev-chat-login" data-ev="chat-login" href="#" hidden>
      <i class="ri-login-circle-line"></i> <?php esc_html_e( 'Entre para participar do Warm-Up', 'apollo-events' ); ?>
    </a>
  </div>
</div>

<!-- GALLERY LIGHTBOX -->
<div class="ev-lightbox" id="<?php echo esc_attr( apollo_ev_id( 'lightbox', $uid ) ); ?>" data-ev="gallery-lightbox" aria-hidden="true">
  <button type="button" class="ev-lightbox-x" data-ev-action="close-gallery" aria-label="<?php esc_attr_e( 'Fechar', 'apollo-events' ); ?>">
    <i class="ri-close-line"></i>
  </button>
  <img data-ev="gallery-lightbox-img" src="" alt="">
</div>
