<?php
/**
 * Single Event — Video Section
 *
 * Embedded video player with breathe overlay effect.
 *
 * Expected variables: $video_url
 *
 * @package Apollo\Event
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $video_url ) ) {
    return;
}

// Extract YouTube ID if applicable
$youtube_id = '';
if ( preg_match( '/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=))([a-zA-Z0-9_-]{11})/', $video_url, $matches ) ) {
    $youtube_id = $matches[1];
}
?>

<div class="media-section">
    <div class="event-video-frame">
        <?php if ( $youtube_id ) : ?>
            <iframe
                class="apollo-yt-ambient"
                src="<?php echo esc_url( 'https://www.youtube-nocookie.com/embed/' . $youtube_id . '?autoplay=1&mute=1&loop=1&playlist=' . $youtube_id . '&controls=0&showinfo=0&modestbranding=1&rel=0&iv_load_policy=3&playsinline=1&disablekb=1&fs=0&cc_load_policy=0' ); ?>"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="autoplay; encrypted-media"
                allowfullscreen
                loading="lazy"
                tabindex="-1"
                title="<?php esc_attr_e( 'Vídeo do evento', 'apollo-events' ); ?>">
            </iframe>
        <?php else : ?>
            <iframe
                src="<?php echo esc_url( $video_url ); ?>"
                allowfullscreen
                loading="lazy"
                title="<?php esc_attr_e( 'Vídeo do evento', 'apollo-events' ); ?>">
            </iframe>
        <?php endif; ?>
    </div>
</div>
