<?php
/**
 * Single Part — Post
 *
 * Reusable post card component for the feed.
 * Expects: $post_data (array) with keys: user_id, display_name, social_name,
 *          author_avatar, role, content, image_url, embed_type, embed_data,
 *          poll_data, wow_count, comment_count, share_count, created_at, replies
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $post_data ) ) {
	return;
}

$p_author     = $post_data['social_name'] ?? $post_data['display_name'] ?? 'Anônimo';
$p_avatar     = $post_data['author_avatar'] ?? '';
$p_role       = $post_data['role'] ?? '';
$p_content    = $post_data['content'] ?? '';
$p_image      = $post_data['image_url'] ?? '';
$p_time       = function_exists( 'apollo_time_ago' ) ? apollo_time_ago( $post_data['created_at'] ?? '' ) : '';
$p_wow        = (int) ( $post_data['wow_count'] ?? 0 );
$p_comments   = (int) ( $post_data['comment_count'] ?? 0 ); // Data contract key — 'comment_count' refers to feed replies, not apollo-comment (depoimento)
$p_shares     = (int) ( $post_data['share_count'] ?? 0 );
$p_id         = (int) ( $post_data['id'] ?? 0 );
$p_username   = $post_data['user_login'] ?? '';
$p_embed      = $post_data['embed_type'] ?? '';
$p_embed_data = $post_data['embed_data'] ?? array();
$p_poll       = $post_data['poll_data'] ?? null;
$p_replies    = $post_data['replies'] ?? array();

// Role label
$role_label = '';
if ( $p_role === 'admin' ) {
	$role_label = 'criador';
} elseif ( $p_role === 'mod' ) {
	$role_label = 'moderador';
}
?>
<div class="post g-fade" data-post-id="<?php echo esc_attr( $p_id ); ?>">
	<!-- Header -->
	<div class="post-header">
		<a href="<?php echo esc_url( home_url( '/id/' . $p_username ) ); ?>" class="post-avatar">
			<?php if ( $p_avatar ) : ?>
				<img src="<?php echo esc_url( $p_avatar ); ?>" alt="<?php echo esc_attr( $p_author ); ?>" loading="lazy">
			<?php endif; ?>
		</a>
		<div class="post-user-info">
			<div class="post-user-name"><?php echo esc_html( $p_author ); ?></div>
			<?php if ( $role_label ) : ?>
				<div class="post-user-role"><?php echo esc_html( $role_label ); ?></div>
			<?php endif; ?>
		</div>
		<span class="post-time"><?php echo esc_html( $p_time ); ?></span>
		<button class="post-more"><i class="ri-more-2-fill"></i></button>
	</div>

	<!-- Text content -->
	<?php if ( $p_content ) : ?>
		<div class="post-text"><?php echo wp_kses_post( nl2br( $p_content ) ); ?></div>
	<?php endif; ?>

	<!-- Image -->
	<?php if ( $p_image ) : ?>
		<div class="post-image">
			<img src="<?php echo esc_url( $p_image ); ?>" alt="" loading="lazy">
		</div>
	<?php endif; ?>

	<!-- Embed (Spotify, SoundCloud, YouTube) -->
	<?php if ( $p_embed && ! empty( $p_embed_data ) ) : ?>
		<div class="post-embed">
			<div class="post-embed-icon <?php echo esc_attr( $p_embed ); ?>">
				<?php
				$embed_icons = array(
					'spotify'    => 'ri-spotify-fill',
					'soundcloud' => 'ri-soundcloud-fill',
					'youtube'    => 'ri-youtube-fill',
				);
				$icon        = $embed_icons[ $p_embed ] ?? 'ri-link';
				?>
				<i class="<?php echo esc_attr( $icon ); ?>"></i>
			</div>
			<div class="post-embed-info">
				<div class="post-embed-title"><?php echo esc_html( $p_embed_data['title'] ?? '' ); ?></div>
				<div class="post-embed-meta"><?php echo esc_html( $p_embed_data['meta'] ?? ucfirst( $p_embed ) ); ?></div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Poll -->
	<?php if ( $p_poll ) : ?>
		<?php
		$poll_data_for_part = $p_poll;
		require __DIR__ . '/post-poll.php';
		?>
	<?php endif; ?>

	<!-- Reactions -->
	<div class="post-reactions">
		<button class="reaction-btn" data-action="wow" data-post-id="<?php echo esc_attr( $p_id ); ?>">
			<i class="ri-fire-line"></i> <?php echo $p_wow > 0 ? esc_html( $p_wow ) : ''; ?>
		</button>
		<button class="reaction-btn" data-action="comment" data-post-id="<?php echo esc_attr( $p_id ); ?>">
			<i class="ri-chat-1-line"></i> <?php echo $p_comments > 0 ? esc_html( $p_comments ) : ''; ?>
		</button>
		<button class="reaction-btn" data-action="share" data-post-id="<?php echo esc_attr( $p_id ); ?>">
			<i class="ri-share-forward-line"></i> <?php echo $p_shares > 0 ? esc_html( $p_shares ) : ''; ?>
		</button>
	</div>

	<!-- Thread / Replies -->
	<?php if ( ! empty( $p_replies ) ) : ?>
		<?php
		$thread_replies = $p_replies;
		require __DIR__ . '/post-thread.php';
		?>
	<?php endif; ?>
</div>
