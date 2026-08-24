<?php
/**
 * Feed widget — Trending sounds (real `sound` taxonomy usage).
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = function_exists( 'apollo_feed_trending' ) ? apollo_feed_trending( 6 ) : array();
if ( empty( $items ) ) { return; }
?>
<div app="app-widget-trending">
    <div class="sb-card">
        <div class="sb-title"><?php esc_html_e( 'Em alta', 'apollo-templates' ); ?></div>
        <div class="sb-tags">
            <?php foreach ( $items as $t ) : ?>
                <a class="sb-tag" href="<?php echo esc_url( $t['url'] ); ?>">
                    <?php echo esc_html( $t['label'] ); ?>
                    <span class="sb-tag-n"><?php echo esc_html( (string) $t['count'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
