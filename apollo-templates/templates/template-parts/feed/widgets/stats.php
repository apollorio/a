<?php
/**
 * Feed widget — Scene counters (real published counts).
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = function_exists( 'apollo_feed_stats' ) ? apollo_feed_stats() : array();
if ( empty( $items ) ) { return; }
?>
<div app="app-widget-stats">
    <div class="sb-card">
        <div class="sb-title"><?php esc_html_e( 'A cena em números', 'apollo-templates' ); ?></div>
        <div class="sb-stats">
            <?php foreach ( $items as $s ) : ?>
                <div class="sb-stat">
                    <b><?php echo esc_html( $s['value'] ); ?></b>
                    <span><?php echo esc_html( $s['label'] ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
