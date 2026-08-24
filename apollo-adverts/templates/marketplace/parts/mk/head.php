<?php
/**
 * Marketplace — screen head (title + safety CTA).
 *
 * Mirrors the mockup's .mk-head + safety control. The safety button carries
 * data-apollo-suporte: it is a help surface, and every help/support control in
 * the ecosystem must expose that hook. Children are click-transparent so the
 * whole pill is live regardless of the runtime's selector strategy.
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<header class="mk-head">
    <div>
        <h1><?php esc_html_e( 'Marketplace', 'apollo-adverts' ); ?></h1>
        <span class="sub"><?php esc_html_e( 'Repasses e hospedagem da cena', 'apollo-adverts' ); ?></span>
    </div>
    <button type="button" class="mk-safety" data-apollo-suporte>
        <i class="ri-shield-check-line" data-apollo-suporte aria-hidden="true"></i>
        <span data-apollo-suporte><?php esc_html_e( 'Dicas de segurança', 'apollo-adverts' ); ?></span>
    </button>
</header>
<style>
/* the pill is the target — children never intercept the pointer */
.mk-safety > i, .mk-safety > span { pointer-events: none; }
</style>
