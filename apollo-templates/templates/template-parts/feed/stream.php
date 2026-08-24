<?php
/**
 * Feed — post stream (mockup: .fd-stream / .fd-post).
 *
 * Posts come from apollo-social when present. There is no fixture fallback by
 * design: an empty scene shows an empty state, it does not show invented
 * activity.
 *
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$posts = array();
if ( function_exists( 'apollo_social_get_feed' ) ) {
    $posts = (array) apollo_social_get_feed( array( 'per_page' => 20 ) );
}
?>
<div class="fd-stream" id="apolloFeedStream" data-feed-endpoint="<?php echo esc_url( rest_url( 'apollo/v1/feed' ) ); ?>">
    <?php if ( empty( $posts ) ) : ?>
        <div class="fd-empty">
            <i class="ri-chat-3-line" aria-hidden="true"></i>
            <p><?php esc_html_e( 'Ainda não há publicações por aqui.', 'apollo-templates' ); ?></p>
            <span><?php esc_html_e( 'Seja quem começa a conversa da cena hoje.', 'apollo-templates' ); ?></span>
        </div>
    <?php else : ?>
        <?php foreach ( $posts as $post_item ) : ?>
            <?php apollo_plus_part( 'feed/post', array( 'post_item' => $post_item ) ); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
