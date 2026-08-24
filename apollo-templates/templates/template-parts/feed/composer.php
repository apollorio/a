<?php
/**
 * Feed — composer (mockup: .fd-composer).
 *
 * Members get the real composer; guests get the same shape as a gate that
 * routes to /acesso. Rendering an inert box for guests would look broken, and
 * hiding it entirely would lose the affordance that posting exists.
 *
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$logged = is_user_logged_in();
$user   = $logged ? wp_get_current_user() : null;
$first  = $logged ? explode( ' ', trim( (string) $user->display_name ) )[0] : '';
?>
<div class="fd-composer"<?php echo $logged ? '' : ' data-auth-required role="button" tabindex="0"'; ?>>
    <span class="avt">
        <?php if ( $logged ) : ?>
            <img src="<?php echo esc_url( get_avatar_url( $user->ID, array( 'size' => 90 ) ) ); ?>" alt="" />
        <?php else : ?>
            <i class="ri-user-line" aria-hidden="true"></i>
        <?php endif; ?>
    </span>
    <?php if ( $logged ) : ?>
        <input type="text" aria-label="<?php esc_attr_e( 'Publicar no feed', 'apollo-templates' ); ?>"
            placeholder="<?php echo esc_attr( sprintf( __( 'O que está rolando na cena, %s?', 'apollo-templates' ), $first ) ); ?>" />
        <div class="fd-acts">
            <button type="button" title="<?php esc_attr_e( 'Foto', 'apollo-templates' ); ?>"><i class="ri-image-line"></i></button>
            <button type="button" title="<?php esc_attr_e( 'Evento', 'apollo-templates' ); ?>"><i class="ri-ticket-2-line"></i></button>
            <button type="button" title="<?php esc_attr_e( 'Som', 'apollo-templates' ); ?>"><i class="ri-music-2-line"></i></button>
        </div>
    <?php else : ?>
        <input type="text" readonly tabindex="-1"
            placeholder="<?php esc_attr_e( 'Entre para publicar na cena', 'apollo-templates' ); ?>" />
        <div class="fd-acts"><i class="ri-lock-2-line" aria-hidden="true"></i></div>
    <?php endif; ?>
</div>
