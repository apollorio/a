<?php
/**
 * Feed widget — News · Rio
 *
 * Markup contract copied from js/app-widget-news.js (.sb-card / .sb-title /
 * .sb-news-item / .news-cat / .news-title / .news-time / .sb-see-more), so
 * feed.css applies unchanged. Server-rendered from real `post` entries — the
 * mockup's SIDEBAR_NEWS global is not ported.
 *
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = function_exists( 'apollo_feed_news' ) ? apollo_feed_news( 4 ) : array();
if ( empty( $items ) ) { return; }
?>
<div app="app-widget-news">
    <div class="sb-card">
        <div class="sb-title"><?php esc_html_e( 'News · Rio', 'apollo-templates' ); ?></div>
        <?php foreach ( $items as $n ) : ?>
            <a class="sb-news-item" href="<?php echo esc_url( $n['url'] ); ?>" style="display:block;text-decoration:none">
                <div class="news-cat">
                    <?php echo esc_html( $n['cat'] ); ?>
                    <?php if ( ! empty( $n['status'] ) ) : ?>
                        <span class="sb-status <?php echo esc_attr( $n['status'] ); ?>"><?php esc_html_e( 'Novo', 'apollo-templates' ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="news-title"><?php echo esc_html( $n['title'] ); ?></div>
                <div class="news-time"><?php echo esc_html( $n['time'] ); ?></div>
            </a>
        <?php endforeach; ?>
        <a class="sb-see-more" href="<?php echo esc_url( home_url( '/jornal' ) ); ?>"><?php esc_html_e( 'Ver mais →', 'apollo-templates' ); ?></a>
    </div>
</div>
