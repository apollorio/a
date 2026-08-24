<?php
/**
 * Single Part — Post Thread / Replies
 *
 * Collapsible thread with reply list.
 * Expects: $thread_replies (array) with keys: display_name, avatar_url, content, created_at
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $thread_replies ) ) {
	return;
}

$count = count( $thread_replies );
?>
<div class="post-thread">
	<button class="thread-toggle" data-open="false">
		<i class="ri-arrow-down-s-line"></i>
		<?php echo esc_html( $count ); ?> resposta<?php echo $count > 1 ? 's' : ''; ?>
	</button>
	<div class="thread-replies" style="display:none;">
		<?php
		foreach ( $thread_replies as $reply ) :
			$r_name   = $reply['display_name'] ?? $reply['user_login'] ?? 'Anônimo';
			$r_avatar = $reply['avatar_url'] ?? '';
			$r_text   = $reply['content'] ?? '';
			$r_time   = function_exists( 'apollo_time_ago' ) ? apollo_time_ago( $reply['created_at'] ?? '' ) : '';
			?>
			<div class="thread-reply">
				<div class="thread-reply-av">
					<?php if ( $r_avatar ) : ?>
						<img src="<?php echo esc_url( $r_avatar ); ?>" alt="" loading="lazy">
					<?php endif; ?>
				</div>
				<div class="thread-reply-body">
					<span class="thread-reply-name"><?php echo esc_html( $r_name ); ?></span>
					<p class="thread-reply-text"><?php echo wp_kses_post( nl2br( $r_text ) ); ?></p>
					<span class="thread-reply-time"><?php echo esc_html( $r_time ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
