<?php
/**
 * Feed widget — Núcleos / Comunas.
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = function_exists( 'apollo_feed_nucleos' ) ? apollo_feed_nucleos( 4 ) : array();
if ( empty( $items ) ) { return; }
?>
<div app="app-widget-nucleos">
    <div class="sb-card">
        <div class="sb-title"><?php esc_html_e( 'Núcleos', 'apollo-templates' ); ?></div>
        <?php foreach ( $items as $n ) : ?>
            <a class="sb-news-item" href="<?php echo esc_url( $n['url'] ); ?>" style="display:block;text-decoration:none">
                <div class="news-title"><?php echo esc_html( $n['title'] ); ?></div>
                <?php if ( $n['meta'] ) : ?><div class="news-time"><?php echo esc_html( $n['meta'] ); ?></div><?php endif; ?>
            </a>
        <?php endforeach; ?>
        <a class="sb-see-more" href="<?php echo esc_url( home_url( '/comunas' ) ); ?>"><?php esc_html_e( 'Ver todos →', 'apollo-templates' ); ?></a>
    </div>
</div>
