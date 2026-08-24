<?php
/**
 * Feed widget — Marketplace teasers.
 *
 * Title + price ONLY. The seller is never named here: guest privacy on advert
 * surfaces is enforced site-wide and this widget must not become the leak.
 *
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = function_exists( 'apollo_feed_market' ) ? apollo_feed_market( 3 ) : array();
if ( empty( $items ) ) { return; }
?>
<div app="app-widget-market">
    <div class="sb-card">
        <div class="sb-title"><?php esc_html_e( 'Marketplace', 'apollo-templates' ); ?></div>
        <?php foreach ( $items as $m ) : ?>
            <a class="sb-news-item" href="<?php echo esc_url( $m['url'] ); ?>" style="display:block;text-decoration:none">
                <div class="news-title"><?php echo esc_html( $m['title'] ); ?></div>
                <div class="news-time"><?php echo esc_html( $m['price'] ); ?></div>
            </a>
        <?php endforeach; ?>
        <a class="sb-see-more" href="<?php echo esc_url( home_url( '/anuncios' ) ); ?>"><?php esc_html_e( 'Ver marketplace →', 'apollo-templates' ); ?></a>
    </div>
</div>
