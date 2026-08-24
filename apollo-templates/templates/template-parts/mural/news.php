<?php

/**
 * Mural: Latest News
 *
 * 3-column grid of latest WordPress posts (category: news).
 *
 * @package Apollo\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch latest posts from "news" category
$news_posts = get_posts(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 3,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
		'category_name'  => 'news', // Adjust if your category slug is different
	)
);

// Fallback: if no "news" category, get latest posts
if ( empty( $news_posts ) ) {
	$news_posts = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
}

if ( empty( $news_posts ) ) {
	return;
}
?>

<!-- NEWS GRID (Static, 3 Items) -->
<section class="news-section">
	<div class="app-container">
		<div class="news-grid">
			<?php
			foreach ( $news_posts as $post ) :
				setup_postdata( $post );
				$categories = get_the_category( $post->ID );
				$cat_name   = ! empty( $categories ) ? $categories[0]->name : 'NEWS';
				$cat_slug   = ! empty( $categories ) ? $categories[0]->slug : 'news';
				$time_ago   = apollo_time_ago( get_post_time( 'Y-m-d H:i:s', false, $post ) );
				$author     = get_the_author_meta( 'display_name', $post->post_author );

				// Badge class based on category
				$badge_class = $cat_slug === 'repudy' ? 'badge-repudy' : 'badge-news';
				?>
				<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="news-item">
					<div class="news-header">
						<span class="news-badge <?php echo esc_attr( $badge_class ); ?>">
							<?php echo esc_html( strtoupper( $cat_name ) ); ?>
						</span>
						<span class="news-date"><?php echo wp_kses_post( apollo_time_ago_html( get_post_time( 'Y-m-d H:i:s', false, $post ) ) ); ?></span>
					</div>
					<div class="news-title"><?php echo esc_html( get_the_title( $post ) ); ?></div>
					<div class="news-author">By <?php echo esc_html( $author ); ?></div>
				</a>
				<?php
			endforeach;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>