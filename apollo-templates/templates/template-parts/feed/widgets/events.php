<?php
/**
 * Feed widget — Próximos eventos.
 * Contract from js/app-widget-events.js (.sb-ev / .sb-ev-date / .sb-ev-info).
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = function_exists( 'apollo_feed_events' ) ? apollo_feed_events( 4 ) : array();
if ( empty( $items ) ) { return; }
?>
<div app="app-widget-events">
    <div class="sb-card">
        <div class="sb-title"><?php esc_html_e( 'Próximos eventos', 'apollo-templates' ); ?></div>
        <?php foreach ( $items as $e ) : ?>
            <a class="sb-ev" href="<?php echo esc_url( $e['url'] ); ?>" style="text-decoration:none">
                <div class="sb-ev-date">
                    <b><?php echo esc_html( $e['day'] ); ?></b>
                    <span><?php echo esc_html( $e['month'] ); ?></span>
                </div>
                <div class="sb-ev-info">
                    <div class="news-title"><?php echo esc_html( $e['title'] ); ?></div>
                    <?php if ( $e['meta'] ) : ?>
                        <div class="news-time"><?php echo esc_html( $e['meta'] ); ?></div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        <a class="sb-see-more" href="<?php echo esc_url( home_url( '/eventos' ) ); ?>"><?php esc_html_e( 'Ver agenda →', 'apollo-templates' ); ?></a>
    </div>
</div>
