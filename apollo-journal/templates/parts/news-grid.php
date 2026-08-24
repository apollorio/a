<?php

/**
 * Template Part: News Grid Widget
 *
 * Renders a 3-column news grid section compatible with the Mural page.
 * Drop-in replacement / enhancement for apollo-templates mural/news.php.
 *
 * Usage: include from any template.
 *   <?php include APOLLO_JOURNAL_DIR . 'templates/parts/news-grid.php'; ?>
 *
 * @package Apollo\Journal
 */

defined( 'ABSPATH' ) || exit;

$aj_per_page = apply_filters( 'apollo/journal/news_grid_count', 6 );

$aj_posts = get_posts(
	array(
		'post_type'      => 'post',
		'posts_per_page' => $aj_per_page,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

if ( empty( $aj_posts ) ) {
	return;
}
?>

<section class="aj-news-section" data-lazy-section>
	<div class="aj-news-header">
		<h2 class="aj-news-header__title">
			<i class="ri-newspaper-line"></i>
			Journal
		</h2>
		<a href="<?php echo esc_url( home_url( '/category/news/' ) ); ?>" class="aj-news-header__link">
			<?php esc_html_e( 'Ver todos', 'apollo-journal' ); ?>
			<i class="ri-arrow-right-s-line"></i>
		</a>
	</div>

	<div class="aj-news-grid">
		<?php
		foreach ( $aj_posts as $aj_post ) :
			setup_postdata( $aj_post );
			$cats     = get_the_category( $aj_post->ID );
			$cat_name = ! empty( $cats ) ? $cats[0]->name : 'News';
			$cat_slug = ! empty( $cats ) ? $cats[0]->slug : 'news';
			$nrep     = get_post_meta( $aj_post->ID, '_nrep_code', true );
			$badge    = $nrep ? $nrep : strtoupper( $cat_name );
			$badge_cl = 'aj-ng-badge';
			if ( $nrep ) {
				$badge_cl .= ' aj-ng-badge--nrep';
			} elseif ( 'nota-de-repudio' === $cat_slug ) {
				$badge_cl .= ' aj-ng-badge--nrep';
			}

			$time_ago_dt = get_post_time( 'Y-m-d H:i:s', false, $aj_post );

			$author = get_the_author_meta( 'display_name', $aj_post->post_author );
			?>
			<a href="<?php echo esc_url( get_permalink( $aj_post ) ); ?>" class="aj-ng-item">
				<?php if ( has_post_thumbnail( $aj_post ) ) : ?>
					<img
						class="aj-ng-item__img"
						src="<?php echo esc_url( get_the_post_thumbnail_url( $aj_post, 'medium' ) ); ?>"
						alt="<?php echo esc_attr( get_the_title( $aj_post ) ); ?>"
						loading="lazy">
				<?php endif; ?>
				<div class="aj-ng-item__body">
					<div class="aj-ng-item__top">
						<span class="<?php echo esc_attr( $badge_cl ); ?>"><?php echo esc_html( $badge ); ?></span>
						<span class="aj-ng-item__time"><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( $time_ago_dt ) : esc_html( human_time_diff( get_post_time( 'U', false, $aj_post ), current_time( 'timestamp' ) ) ) ); ?></span>
					</div>
					<div class="aj-ng-item__title"><?php echo esc_html( get_the_title( $aj_post ) ); ?></div>
					<div class="aj-ng-item__author"><?php echo esc_html( $author ); ?></div>
				</div>
			</a>
			<?php
		endforeach;
		wp_reset_postdata();
		?>
	</div>
</section>
