<?php
/**
 * Single Part — Pinned Post
 *
 * Highlighted pinned/announcement post.
 * Expects: $group_posts (array) — will find first pinned post.
 * Falls back to showing nothing if no pinned post.
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Find pinned post
$pinned = null;
if ( ! empty( $group_posts ) ) {
	foreach ( $group_posts as $post ) {
		if ( ! empty( $post['is_pinned'] ) ) {
			$pinned = $post;
			break;
		}
	}
}

if ( ! $pinned ) {
	return;
}

$pinned_author = $pinned['social_name'] ?? $pinned['display_name'] ?? 'Admin';
$pinned_avatar = $pinned['author_avatar'] ?? '';
$pinned_time   = function_exists( 'apollo_time_ago' ) ? apollo_time_ago( $pinned['created_at'] ?? '' ) : '';
$pinned_text   = $pinned['content'] ?? '';
?>
<div class="pinned g-fade">
	<div class="pinned-label"><i class="ri-pushpin-line"></i> Fixado</div>
	<div class="pinned-user">
		<div class="pinned-user-av">
			<?php if ( $pinned_avatar ) : ?>
				<img src="<?php echo esc_url( $pinned_avatar ); ?>" alt="" loading="lazy">
			<?php endif; ?>
		</div>
		<span class="pinned-user-name"><?php echo esc_html( $pinned_author ); ?></span>
		<span class="pinned-user-time"><?php echo esc_html( $pinned_time ); ?></span>
	</div>
	<p class="pinned-text"><?php echo wp_kses_post( nl2br( $pinned_text ) ); ?></p>
</div>
