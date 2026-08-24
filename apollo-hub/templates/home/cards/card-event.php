<?php
/**
 * Card: Event — Reusable event card with date cutout, image, meta.
 * Expects $event array with: day, month, title, djs, loc, genres, tags, img.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $event ) || ! is_array( $event ) ) {
    return;
}
?>
<a href="#" class="a-eve-card ai">
    <div class="a-eve-date">
        <span class="a-eve-date-day"><?php echo esc_html( $event['day'] ); ?></span>
        <span class="a-eve-date-month"><?php echo esc_html( $event['month'] ); ?></span>
    </div>
    <div class="a-eve-media">
        <img src="<?php echo esc_url( $event['img'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" loading="lazy"/>
        <?php if ( ! empty( $event['tags'] ) ) : ?>
            <div class="a-eve-tags">
                <?php foreach ( $event['tags'] as $tag ) : ?>
                    <span class="a-eve-tag"><?php echo esc_html( $tag ); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="a-eve-content">
        <h2 class="a-eve-title"><?php echo esc_html( $event['title'] ); ?></h2>
        <p class="a-eve-meta"><i class="ri-sound-module-fill" aria-hidden="true"></i><span><?php echo esc_html( $event['djs'] ); ?></span></p>
        <p class="a-eve-meta"><i class="ri-map-pin-2-line" aria-hidden="true"></i><span><?php echo esc_html( $event['loc'] ); ?></span></p>
        <p class="a-eve-meta"><i class="ri-music-2-line" aria-hidden="true"></i><span><?php echo esc_html( $event['genres'] ); ?></span></p>
    </div>
</a>
