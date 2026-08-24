<?php
/**
 * Profile Feed Section
 *
 * Feed tabs + 2-column card grid with hover overlays.
 *
 * @package Apollo\Users
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variables: $user, $user_id, $is_own_profile

// Gather user content for feed
$feed_posts = get_posts(
	array(
		'author'         => $user_id,
		'posts_per_page' => 12,
		'post_status'    => 'publish',
		'post_type'      => array( 'post', 'apollo_event', 'apollo_classified' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
?>

<section class="feed-section">
	<div class="feed-tabs">
		<button class="feed-tab active" data-filter="all">All</button>
		<button class="feed-tab" data-filter="apollo_event">Events</button>
		<button class="feed-tab" data-filter="apollo_classified">Classifieds</button>
	</div>

	<div class="complex-grid" id="feed-grid">
		<?php if ( $feed_posts ) : ?>
			<?php
			foreach ( $feed_posts as $fpost ) :
				$thumb      = get_the_post_thumbnail_url( $fpost, 'medium_large' );
				$post_type  = $fpost->post_type;
				$type_label = match ( $post_type ) {
					'apollo_event'      => 'Event',
					'apollo_classified' => 'Classified',
					default             => 'Post',
				};
				$is_dark = ( $post_type === 'apollo_event' && ! $thumb );
				?>
			<article class="f-card <?php echo esc_attr( $is_dark ? 'f-card--dark' : '' ); ?>"
					data-type="<?php echo esc_attr( $post_type ); ?>"
					<?php echo $is_dark ? 'style="background:var(--black-1);border-color:var(--black-1);"' : ''; ?>>
				<div class="f-overlay">
					<div class="f-overlay-content">
						<span><i class="ri-brain-line"></i> <?php echo (int) get_post_meta( $fpost->ID, '_apollo_wows', true ); ?> wows</span>
						<span><i class="ri-chat-4-line"></i> <?php echo (int) $fpost->comment_count; ?></span>
					</div>
				</div>

				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" class="f-card-img" alt="">
				<?php endif; ?>

				<div class="f-card-body">
					<div class="f-meta" <?php echo $is_dark ? 'style="color:#fff;"' : ( $post_type === 'apollo_event' ? 'style="color:var(--primary);"' : '' ); ?>>
						<?php echo esc_html( $type_label ); ?>
					</div>
					<h3 class="f-title" <?php echo $is_dark ? 'style="color:#fff;"' : ''; ?>>
						<?php echo esc_html( $fpost->post_title ); ?>
					</h3>
					<p class="f-text" <?php echo $is_dark ? 'style="color:var(--gray-5);"' : ''; ?>>
						<?php echo esc_html( wp_trim_words( $fpost->post_content, 18, '…' ) ); ?>
					</p>
				</div>
			</article>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="feed-empty" style="grid-column:1/-1; text-align:center; padding:48px 0; color:var(--txt-muted); font-family:var(--ff-mono); font-size:12px;">
				Nenhuma publicação ainda.
			</div>
		<?php endif; ?>
	</div>
</section>
