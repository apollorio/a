<?php
/**
 * Card: Crash — Reusable accommodation card.
 * Expects $crash array with: title, price, meta, img.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $crash ) || ! is_array( $crash ) ) {
    return;
}
?>
<a href="/acesso" class="nh-crash-card ai">
    <div class="nh-crash-img-wrap">
        <img src="<?php echo esc_url( $crash['img'] ); ?>" class="nh-crash-img" alt="<?php echo esc_attr( $crash['title'] ); ?>" loading="lazy"/>
        <div class="nh-crash-login-hint" aria-hidden="true"><i class="ri-lock-2-line"></i></div>
    </div>
    <div class="nh-crash-info">
        <div class="nh-crash-info-header">
            <h3><?php echo esc_html( $crash['title'] ); ?></h3>
            <span class="nh-crash-price"><?php echo esc_html( $crash['price'] ); ?></span>
        </div>
        <p class="nh-crash-meta"><?php echo esc_html( $crash['meta'] ); ?></p>
    </div>
</a>
