<?php
/**
 * Single Event — Bio / Description
 *
 * Event description text with reveal-up animation class.
 *
 * Expected variables: $post_id
 *
 * @package Apollo\Event
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$content = get_the_content( null, false, $post_id );
$content = apply_filters( 'the_content', $content );

if ( empty( trim( wp_strip_all_tags( $content ) ) ) ) {
    return;
}
?>

<div class="event-bio reveal-up">
    <?php echo wp_kses_post( $content ); ?>
</div>
