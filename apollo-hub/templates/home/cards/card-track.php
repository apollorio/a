<?php
/**
 * Card: Track — Reusable track card for horizontal scroll.
 * Expects $track array with: title, artist, genre, plays, img.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $track ) || ! is_array( $track ) ) {
    return;
}
?>
<article class="nh-track-card ai">
    <div class="nh-track-artwork">
        <img src="<?php echo esc_url( $track['img'] ); ?>" alt="<?php echo esc_attr( $track['title'] ); ?>" loading="lazy"/>
        <div class="nh-track-play-overlay" aria-hidden="true">
            <button class="nh-track-play-btn" aria-label="Tocar faixa"><i class="ri-play-fill"></i></button>
        </div>
    </div>
    <div class="nh-track-info">
        <h4><?php echo esc_html( $track['title'] ); ?></h4>
        <div class="nh-track-artist"><?php echo esc_html( $track['artist'] ); ?></div>
        <div class="nh-track-meta">
            <span><i class="ri-headphone-line"></i><?php echo esc_html( $track['genre'] ); ?></span>
            <span><i class="ri-play-circle-line"></i><?php echo esc_html( $track['plays'] ); ?></span>
        </div>
    </div>
</article>
