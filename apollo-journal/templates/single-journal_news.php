<?php
/**
 * Template: Single Journal News (artigo)
 *
 * Full article view for journal_news CPT.
 * Canvas mode: uses wp_head / wp_footer.
 *
 * @package Apollo\Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

the_post();

$headline  = get_post_meta( get_the_ID(), '_apollo_headline', true );
$subtitle  = get_post_meta( get_the_ID(), '_apollo_subtitle', true );
$featured  = get_post_meta( get_the_ID(), '_apollo_featured', true );
$nrep_code = get_post_meta( get_the_ID(), '_nrep_code', true );

$cats      = get_the_category();
$cat_name  = ! empty( $cats ) ? $cats[0]->name : '';
$author_id = get_the_author_meta( 'ID' );

// Related news (same category, exclude current).
$related_args = array(
	'post_type'      => 'journal_news',
	'posts_per_page' => 3,
	'post_status'    => 'publish',
	'post__not_in'   => array( get_the_ID() ),
	'orderby'        => 'date',
	'order'          => 'DESC',
);
if ( ! empty( $cats ) ) {
	$related_args['cat'] = $cats[0]->term_id;
}
$related = new WP_Query( $related_args );
if ( $related->have_posts() ) {
	$related_ids = wp_list_pluck( $related->posts, 'ID' );
	update_post_meta_cache( $related_ids );
}
?>

<!-- Apollo CDN -->
<script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

<style id="aj-single-news-inline">
	/* ── Article Layout ── */
	.aj-article {
		max-width: 780px;
		margin: 0 auto;
		padding: 0 var(--space-4, 24px) var(--space-6, 48px);
	}

	/* ── Header ── */
	.aj-article-header {
		padding: var(--space-5, 32px) 0;
	}

	.aj-article-header__badge {
		display: inline-block;
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 9px;
		letter-spacing: 0.15em;
		text-transform: uppercase;
		color: var(--primary, #FF9820);
		border: 1px solid var(--primary, #FF9820);
		padding: 4px 14px;
		margin-bottom: var(--space-3, 16px);
	}

	.aj-article-header__badge--nrep {
		color: #e53935;
		border-color: #e53935;
	}

	.aj-article-header__title {
		font-family: var(--ff-fun, "Syne", sans-serif);
		font-size: clamp(1.75rem, 1.25rem + 2.5vi, 2.75rem);
		font-weight: 800;
		letter-spacing: -0.04em;
		line-height: 1.1;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
		margin: 0 0 var(--space-2, 8px);
	}

	.aj-article-header__subtitle {
		font-family: var(--ff-main, "Space Grotesk", sans-serif);
		font-size: clamp(1rem, 0.9rem + 0.5vi, 1.25rem);
		font-weight: 400;
		color: var(--txt-color, rgba(19, 21, 23, 0.77));
		line-height: 1.4;
		margin: 0 0 var(--space-3, 16px);
	}

	/* ── Author Row ── */
	.aj-article-author {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: var(--space-3, 16px) 0;
		border-top: 1px solid var(--border, #00000012);
		border-bottom: 1px solid var(--border, #00000012);
	}

	.aj-article-author__avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		object-fit: cover;
	}

	.aj-article-author__info {
		flex: 1;
	}

	.aj-article-author__name {
		font-family: var(--ff-main, "Space Grotesk", sans-serif);
		font-size: 14px;
		font-weight: 600;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
	}

	.aj-article-author__meta {
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 11px;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
		display: flex;
		align-items: center;
		gap: 8px;
	}

	/* ── Hero Image ── */
	.aj-article-hero {
		margin: var(--space-4, 24px) calc(-1 * var(--space-4, 24px));
	}

	@media (min-width: 860px) {
		.aj-article-hero {
			margin-left: -10%;
			margin-right: -10%;
			border-radius: var(--r-sm, 10px);
			overflow: hidden;
		}
	}

	.aj-article-hero__img {
		width: 100%;
		aspect-ratio: 21 / 9;
		object-fit: cover;
		display: block;
	}

	/* ── Body ── */
	.aj-article-body {
		padding: var(--space-5, 32px) 0;
		font-family: var(--ff-main, "Space Grotesk", sans-serif);
		font-size: 16px;
		line-height: 1.8;
		color: var(--txt-color, rgba(19, 21, 23, 0.77));
	}

	.aj-article-body p {
		margin: 0 0 var(--space-3, 16px);
	}

	.aj-article-body h2,
	.aj-article-body h3,
	.aj-article-body h4 {
		font-family: var(--ff-fun, "Syne", sans-serif);
		font-weight: 700;
		letter-spacing: -0.02em;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
		margin: var(--space-4, 24px) 0 var(--space-2, 8px);
	}

	.aj-article-body h2 { font-size: 1.5rem; }
	.aj-article-body h3 { font-size: 1.25rem; }
	.aj-article-body h4 { font-size: 1.1rem; }

	.aj-article-body img {
		max-width: 100%;
		height: auto;
		border-radius: var(--r-xs, 6px);
	}

	.aj-article-body blockquote {
		border-left: 3px solid var(--primary, #FF9820);
		padding: var(--space-2, 8px) var(--space-3, 16px);
		margin: var(--space-3, 16px) 0;
		font-style: italic;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
	}

	.aj-article-body a {
		color: var(--primary, #FF9820);
		text-decoration: underline;
		text-underline-offset: 2px;
	}

	/* ── Tags ── */
	.aj-article-tags {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		padding: var(--space-3, 16px) 0;
		border-top: 1px solid var(--border, #00000012);
	}

	.aj-article-tags__item {
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 10px;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
		background: var(--surface, rgba(var(--rgb-d, 0, 0, 0), 0.04));
		padding: 4px 12px;
		border-radius: var(--r-pill, 999px);
		text-decoration: none;
		transition: background var(--transition-ui, .25s);
	}

	.aj-article-tags__item:hover {
		background: var(--card-hover, rgba(var(--rgb-d, 0, 0, 0), 0.06));
		color: var(--txt-color, rgba(19, 21, 23, 0.77));
	}

	/* ── Related ── */
	.aj-related {
		padding: var(--space-5, 32px) 0;
		border-top: 2px solid var(--primary, #FF9820);
	}

	.aj-related__title {
		font-family: var(--ff-fun, "Syne", sans-serif);
		font-size: 16px;
		font-weight: 700;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
		margin: 0 0 var(--space-3, 16px);
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.aj-related__title i {
		color: var(--primary, #FF9820);
	}

	.aj-related__grid {
		display: grid;
		grid-template-columns: 1fr;
		gap: var(--space-3, 16px);
	}

	@media (min-width: 540px) {
		.aj-related__grid {
			grid-template-columns: repeat(3, 1fr);
		}
	}

	.aj-related-card {
		display: flex;
		flex-direction: column;
		background: var(--surface, rgba(var(--rgb-d, 0, 0, 0), 0.04));
		border-radius: var(--r-sm, 10px);
		overflow: hidden;
		text-decoration: none;
		color: inherit;
		transition: background var(--transition-ui, .25s), transform .2s;
	}

	.aj-related-card:hover {
		background: var(--card-hover, rgba(var(--rgb-d, 0, 0, 0), 0.06));
		transform: translateY(-2px);
	}

	.aj-related-card__img {
		width: 100%;
		aspect-ratio: 16 / 10;
		object-fit: cover;
	}

	.aj-related-card__body {
		padding: var(--space-3, 16px);
	}

	.aj-related-card__title {
		font-family: var(--ff-main, "Space Grotesk", sans-serif);
		font-size: 14px;
		font-weight: 600;
		line-height: 1.3;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
		margin-bottom: 6px;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}

	.aj-related-card__meta {
		font-size: 10px;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
	}

	/* ── Back Link ── */
	.aj-back-link {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 11px;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
		text-decoration: none;
		margin-bottom: var(--space-3, 16px);
		transition: color var(--transition-ui, .25s);
	}

	.aj-back-link:hover {
		color: var(--primary, #FF9820);
	}

	/* ── Dark Mode ── */
	html.dark-mode .aj-article-body {
		color: var(--txt-color, rgba(255, 255, 255, 0.77));
	}

	html.dark-mode .aj-related-card,
	html.dark-mode .aj-article-tags__item {
		background: var(--surface, #1a1a1c);
	}

	html.dark-mode .aj-related-card:hover {
		background: var(--card-hover, #232325);
	}
</style>

<article class="aj-article" itemscope itemtype="https://schema.org/NewsArticle">

	<!-- Back Link -->
	<a href="<?php echo esc_url( home_url( '/jornal' ) ); ?>" class="aj-back-link">
		<i class="ri-arrow-left-s-line"></i>
		<?php esc_html_e( 'Jornal.rio', 'apollo-journal' ); ?>
	</a>

	<!-- ═══════ Article Header ═══════ -->
	<header class="aj-article-header">
		<?php if ( $cat_name ) : ?>
			<span class="aj-article-header__badge<?php echo $nrep_code ? ' aj-article-header__badge--nrep' : ''; ?>">
				<?php echo esc_html( $nrep_code ?: strtoupper( $cat_name ) ); ?>
			</span>
		<?php endif; ?>

		<h1 class="aj-article-header__title" itemprop="headline">
			<?php echo esc_html( $headline ?: get_the_title() ); ?>
		</h1>

		<?php if ( $subtitle ) : ?>
			<p class="aj-article-header__subtitle" itemprop="description"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>

		<div class="aj-article-author">
			<?php echo get_avatar( $author_id, 40, '', '', array( 'class' => 'aj-article-author__avatar' ) ); ?>
			<div class="aj-article-author__info">
				<div class="aj-article-author__name" itemprop="author" itemscope itemtype="https://schema.org/Person">
					<span itemprop="name"><?php echo esc_html( get_the_author() ); ?></span>
				</div>
				<div class="aj-article-author__meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" itemprop="datePublished">
						<?php echo esc_html( get_the_date() ); ?>
					</time>
					<span>&middot;</span>
					<span>
						<?php
						$word_count = str_word_count( wp_strip_all_tags( get_the_content() ) );
						$read_time  = max( 1, (int) ceil( $word_count / 200 ) );
						printf(
							/* translators: %d: minutes to read */
							esc_html__( '%d min de leitura', 'apollo-journal' ),
							$read_time
						);
						?>
					</span>
				</div>
			</div>
		</div>
	</header>

	<!-- ═══════ Hero Image ═══════ -->
	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="aj-article-hero">
			<img class="aj-article-hero__img"
				src="<?php echo esc_url( get_the_post_thumbnail_url( null, 'full' ) ); ?>"
				alt="<?php the_title_attribute(); ?>"
				loading="eager"
				itemprop="image">
		</figure>
	<?php endif; ?>

	<!-- ═══════ Article Body ═══════ -->
	<div class="aj-article-body" itemprop="articleBody">
		<?php the_content(); ?>
	</div>

	<!-- ═══════ Tags ═══════ -->
	<?php
	$tags       = get_the_tags();
	$music_tags = get_the_terms( get_the_ID(), 'music' );
	$all_tags   = array();

	if ( is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			$all_tags[] = array(
				'name' => $tag->name,
				'url'  => get_tag_link( $tag->term_id ),
			);
		}
	}
	if ( is_array( $music_tags ) ) {
		foreach ( $music_tags as $mt ) {
			$all_tags[] = array(
				'name' => $mt->name,
				'url'  => get_term_link( $mt ),
			);
		}
	}
	?>
	<?php if ( ! empty( $all_tags ) ) : ?>
		<div class="aj-article-tags">
			<?php foreach ( $all_tags as $t ) : ?>
				<a href="<?php echo esc_url( $t['url'] ); ?>" class="aj-article-tags__item" rel="tag">
					<?php echo esc_html( $t['name'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- ═══════ Related ═══════ -->
	<?php if ( $related->have_posts() ) : ?>
		<section class="aj-related">
			<h2 class="aj-related__title">
				<i class="ri-newspaper-line"></i>
				<?php esc_html_e( 'Leia também', 'apollo-journal' ); ?>
			</h2>
			<div class="aj-related__grid">
				<?php
				while ( $related->have_posts() ) :
					$related->the_post();
				?>
					<a href="<?php the_permalink(); ?>" class="aj-related-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<img class="aj-related-card__img"
								src="<?php echo esc_url( get_the_post_thumbnail_url( null, 'medium_large' ) ); ?>"
								alt="<?php the_title_attribute(); ?>"
								loading="lazy">
						<?php endif; ?>
						<div class="aj-related-card__body">
							<div class="aj-related-card__title"><?php the_title(); ?></div>
							<div class="aj-related-card__meta">
								<?php echo esc_html( get_the_author() ); ?> &middot;
								<?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?>
							</div>
						</div>
					</a>
				<?php endwhile; ?>
			</div>
		</section>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>

</article>

<?php
/**
 * Fires after a single journal_news article is rendered.
 *
 * @since 1.1.0
 * @param int $post_id The article post ID.
 */
do_action( 'apollo/journal/render_news', get_the_ID() );

get_footer();
