<?php
/**
 * Template: Jornal.rio — Newspaper Front Page
 *
 * Layout: 70% News (journal_news) | 30% Notas de Repúdio (journal_nota)
 * Mobile-first, Apollo Design System, responsive stacking.
 *
 * Canvas mode: uses wp_head / wp_footer.
 *
 * @package Apollo\Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Queries ──
$news_per_page = apply_filters( 'apollo/journal/news_per_page', 12 );
$nota_per_page = apply_filters( 'apollo/journal/nota_per_page', 8 );

$news_query = new WP_Query( array(
	'post_type'      => 'journal_news',
	'posts_per_page' => $news_per_page,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

$nota_query = new WP_Query( array(
	'post_type'      => 'journal_nota',
	'posts_per_page' => $nota_per_page,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

// Prime caches.
if ( $news_query->have_posts() ) {
	$news_ids = wp_list_pluck( $news_query->posts, 'ID' );
	update_post_meta_cache( $news_ids );
	update_object_term_cache( $news_ids, 'journal_news' );
}
if ( $nota_query->have_posts() ) {
	$nota_ids = wp_list_pluck( $nota_query->posts, 'ID' );
	update_post_meta_cache( $nota_ids );
}

ob_start();
?>

<style id="aj-jornal-inline">
	/* ── Jornal Page Layout ── */
	.aj-jornal {
		max-width: 1200px;
		margin: 0 auto;
		padding: 0 var(--space-4, 24px) var(--space-6, 48px);
	}

	/* ── Newspaper Header ── */
	.aj-jornal-header {
		text-align: center;
		padding: var(--space-5, 32px) 0 var(--space-4, 24px);
		border-bottom: 3px double var(--border, #00000027);
		margin-bottom: var(--space-5, 32px);
	}

	.aj-jornal-header__title {
		font-family: var(--ff-fun, "Syne", sans-serif);
		font-size: clamp(2rem, 1.5rem + 2.5vi, 3.5rem);
		font-weight: 800;
		letter-spacing: -0.04em;
		line-height: 1;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
		margin: 0 0 8px;
	}

	.aj-jornal-header__sub {
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 10px;
		letter-spacing: 0.25em;
		text-transform: uppercase;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
	}

	.aj-jornal-header__date {
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 11px;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
		margin-top: 4px;
	}

	/* ── 70/30 Grid ── */
	.aj-jornal-grid {
		display: grid;
		grid-template-columns: 1fr;
		gap: var(--space-4, 24px);
	}

	@media (min-width: 860px) {
		.aj-jornal-grid {
			grid-template-columns: 7fr 3fr;
		}
	}

	/* ── Section Headers ── */
	.aj-section-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding-bottom: var(--space-2, 8px);
		border-bottom: 2px solid var(--primary, #FF9820);
		margin-bottom: var(--space-3, 16px);
	}

	.aj-section-header__title {
		font-family: var(--ff-fun, "Syne", sans-serif);
		font-size: 16px;
		font-weight: 700;
		letter-spacing: -0.01em;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.aj-section-header__title i {
		color: var(--primary, #FF9820);
	}

	.aj-section-header__count {
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 10px;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
		text-transform: uppercase;
		letter-spacing: 0.1em;
	}

	/* ── News Column ── */
	.aj-news-col {}

	.aj-news-featured {
		display: block;
		margin-bottom: var(--space-4, 24px);
		border-radius: var(--r-sm, 10px);
		overflow: hidden;
		background: var(--surface, rgba(var(--rgb-d, 0, 0, 0), 0.04));
		text-decoration: none;
		color: inherit;
		transition: background var(--transition-ui, .25s);
	}

	.aj-news-featured:hover {
		background: var(--card-hover, rgba(var(--rgb-d, 0, 0, 0), 0.06));
	}

	.aj-news-featured__img {
		width: 100%;
		aspect-ratio: 21 / 9;
		object-fit: cover;
		display: block;
	}

	.aj-news-featured__body {
		padding: var(--space-4, 24px);
	}

	.aj-news-featured__badge {
		display: inline-block;
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 9px;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		color: var(--primary, #FF9820);
		border: 1px solid var(--primary, #FF9820);
		padding: 3px 10px;
		margin-bottom: 12px;
	}

	.aj-news-featured__title {
		font-family: var(--ff-fun, "Syne", sans-serif);
		font-size: clamp(1.25rem, 1rem + 1.25vi, 1.75rem);
		font-weight: 700;
		letter-spacing: -0.02em;
		line-height: 1.2;
		margin-bottom: 8px;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
	}

	.aj-news-featured__excerpt {
		font-size: 14px;
		color: var(--txt-color, rgba(19, 21, 23, 0.77));
		line-height: 1.6;
		margin-bottom: 12px;
		display: -webkit-box;
		-webkit-line-clamp: 3;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}

	.aj-news-featured__footer {
		display: flex;
		align-items: center;
		gap: 12px;
		font-size: 12px;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
	}

	/* ── News Grid ── */
	.aj-news-list {
		display: grid;
		grid-template-columns: 1fr;
		gap: var(--space-3, 16px);
	}

	@media (min-width: 540px) {
		.aj-news-list {
			grid-template-columns: repeat(2, 1fr);
		}
	}

	.aj-news-item {
		display: flex;
		flex-direction: column;
		background: var(--surface, rgba(var(--rgb-d, 0, 0, 0), 0.04));
		border-radius: var(--r-sm, 10px);
		overflow: hidden;
		text-decoration: none;
		color: inherit;
		transition: background var(--transition-ui, .25s), transform .2s;
	}

	.aj-news-item:hover {
		background: var(--card-hover, rgba(var(--rgb-d, 0, 0, 0), 0.06));
		transform: translateY(-2px);
	}

	.aj-news-item__img {
		width: 100%;
		aspect-ratio: 16 / 10;
		object-fit: cover;
		display: block;
	}

	.aj-news-item__body {
		padding: var(--space-3, 16px);
		flex: 1;
		display: flex;
		flex-direction: column;
	}

	.aj-news-item__badge {
		display: inline-block;
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 8px;
		letter-spacing: 0.1em;
		text-transform: uppercase;
		color: var(--primary, #FF9820);
		border: 1px solid var(--primary, #FF9820);
		padding: 2px 8px;
		margin-bottom: 8px;
		width: fit-content;
	}

	.aj-news-item__title {
		font-family: var(--ff-main, "Space Grotesk", sans-serif);
		font-size: 15px;
		font-weight: 600;
		letter-spacing: -0.01em;
		line-height: 1.3;
		margin-bottom: 8px;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
	}

	.aj-news-item__meta {
		margin-top: auto;
		font-size: 11px;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
		display: flex;
		align-items: center;
		gap: 8px;
	}

	/* ── Nota Column (sidebar) ── */
	.aj-nota-col {}

	.aj-nota-item {
		display: flex;
		gap: 12px;
		padding: var(--space-3, 16px) 0;
		border-bottom: 1px solid var(--border, #00000012);
		text-decoration: none;
		color: inherit;
		transition: background var(--transition-ui, .25s);
	}

	.aj-nota-item:first-child {
		padding-top: 0;
	}

	.aj-nota-item:last-child {
		border-bottom: none;
	}

	.aj-nota-item:hover {
		opacity: 0.8;
	}

	.aj-nota-item__badge {
		flex-shrink: 0;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 42px;
		height: 42px;
		font-family: var(--ff-mono, "Space Mono", monospace);
		font-size: 9px;
		font-weight: 700;
		color: #e53935;
		background: rgba(229, 57, 53, 0.08);
		border-radius: var(--r-xs, 6px);
		text-align: center;
		line-height: 1.2;
	}

	.aj-nota-item__body {
		flex: 1;
		min-width: 0;
	}

	.aj-nota-item__title {
		font-family: var(--ff-main, "Space Grotesk", sans-serif);
		font-size: 13px;
		font-weight: 600;
		line-height: 1.35;
		color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
		margin-bottom: 4px;
	}

	.aj-nota-item__meta {
		font-size: 10px;
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
		display: flex;
		align-items: center;
		gap: 6px;
	}

	/* ── Empty State ── */
	.aj-empty {
		text-align: center;
		padding: var(--space-5, 32px) var(--space-4, 24px);
		color: var(--txt-muted, rgba(19, 21, 23, 0.31));
	}

	.aj-empty i {
		font-size: 40px;
		margin-bottom: 12px;
		display: block;
	}

	/* ── Dark Mode ── */
	html.dark-mode .aj-news-featured,
	html.dark-mode .aj-news-item {
		background: var(--surface, #1a1a1c);
	}

	html.dark-mode .aj-news-featured:hover,
	html.dark-mode .aj-news-item:hover {
		background: var(--card-hover, #232325);
	}

	html.dark-mode .aj-jornal-header {
		border-bottom-color: rgba(255, 255, 255, 0.1);
	}

	html.dark-mode .aj-nota-item {
		border-bottom-color: rgba(255, 255, 255, 0.06);
	}
</style>
<?php
$extra_head = ob_get_clean();

if ( function_exists( 'apollo_render_document_open' ) ) {
	apollo_render_document_open(
		array(
			'title'      => 'Jornal.rio — Apollo::Rio',
			'extra_head' => $extra_head,
		)
	);
} else {
	?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $extra_head;
}
?>
</head>
<body>

<main class="aj-jornal" role="main">

	<!-- ═══════ Newspaper Header ═══════ -->
	<header class="aj-jornal-header">
		<h1 class="aj-jornal-header__title">Jornal.rio</h1>
		<div class="aj-jornal-header__sub">Cultura &middot; Música &middot; Noite &middot; Rio de Janeiro</div>
		<div class="aj-jornal-header__date">
			<?php echo esc_html( wp_date( 'l, j \d\e F \d\e Y' ) ); ?>
		</div>
	</header>

	<!-- ═══════ 70/30 Grid ═══════ -->
	<div class="aj-jornal-grid">

		<!-- ── 70% News Column ── -->
		<section class="aj-news-col" aria-label="<?php esc_attr_e( 'Notícias', 'apollo-journal' ); ?>">
			<div class="aj-section-header">
				<h2 class="aj-section-header__title">
					<i class="ri-newspaper-line"></i>
					<?php esc_html_e( 'Notícias', 'apollo-journal' ); ?>
				</h2>
				<span class="aj-section-header__count">
					<?php
					$total_news = wp_count_posts( 'journal_news' );
					printf(
						/* translators: %d: number of articles */
						esc_html__( '%d artigos', 'apollo-journal' ),
						(int) $total_news->publish
					);
					?>
				</span>
			</div>

			<?php if ( $news_query->have_posts() ) : ?>

				<?php
				// Featured: first published news.
				$news_query->the_post();
				$headline = get_post_meta( get_the_ID(), '_apollo_headline', true );
				$subtitle = get_post_meta( get_the_ID(), '_apollo_subtitle', true );
				$cats     = get_the_category();
				$cat_name = ! empty( $cats ) ? $cats[0]->name : 'News';
				?>
				<a href="<?php the_permalink(); ?>" class="aj-news-featured">
					<?php if ( has_post_thumbnail() ) : ?>
						<img class="aj-news-featured__img"
							src="<?php echo esc_url( get_the_post_thumbnail_url( null, 'large' ) ); ?>"
							alt="<?php the_title_attribute(); ?>" loading="eager">
					<?php endif; ?>
					<div class="aj-news-featured__body">
						<span class="aj-news-featured__badge"><?php echo esc_html( strtoupper( $cat_name ) ); ?></span>
						<h3 class="aj-news-featured__title"><?php echo esc_html( $headline ?: get_the_title() ); ?></h3>
						<?php if ( $subtitle ) : ?>
							<p style="font-size:14px;color:var(--txt-color,rgba(19,21,23,0.77));margin:0 0 8px;font-style:italic;"><?php echo esc_html( $subtitle ); ?></p>
						<?php endif; ?>
						<div class="aj-news-featured__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></div>
						<div class="aj-news-featured__footer">
							<span><?php echo esc_html( get_the_author() ); ?></span>
							<span>&middot;</span>
							<span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?></span>
						</div>
					</div>
				</a>

				<!-- Grid of remaining news -->
				<div class="aj-news-list">
					<?php while ( $news_query->have_posts() ) : $news_query->the_post(); ?>
						<?php
						$cats     = get_the_category();
						$cat_name = ! empty( $cats ) ? $cats[0]->name : 'News';
						$nrep     = get_post_meta( get_the_ID(), '_nrep_code', true );
						$badge    = $nrep ?: strtoupper( $cat_name );
						$badge_cl = $nrep ? 'aj-news-item__badge' . ' aj-card__badge--nrep' : 'aj-news-item__badge';
						?>
						<a href="<?php the_permalink(); ?>" class="aj-news-item">
							<?php if ( has_post_thumbnail() ) : ?>
								<img class="aj-news-item__img"
									src="<?php echo esc_url( get_the_post_thumbnail_url( null, 'medium_large' ) ); ?>"
									alt="<?php the_title_attribute(); ?>" loading="lazy">
							<?php endif; ?>
							<div class="aj-news-item__body">
								<span class="<?php echo esc_attr( $badge_cl ); ?>"><?php echo esc_html( $badge ); ?></span>
								<h3 class="aj-news-item__title"><?php the_title(); ?></h3>
								<div class="aj-news-item__meta">
									<span><?php echo esc_html( get_the_author() ); ?></span>
									<span>&middot;</span>
									<span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?></span>
								</div>
							</div>
						</a>
					<?php endwhile; ?>
				</div>

			<?php else : ?>
				<div class="aj-empty">
					<i class="ri-newspaper-line"></i>
					<p><?php esc_html_e( 'Nenhuma notícia publicada.', 'apollo-journal' ); ?></p>
				</div>
			<?php endif; ?>

			<?php wp_reset_postdata(); ?>

		</section>

		<!-- ── 30% Notas de Repúdio Column ── -->
		<aside class="aj-nota-col" aria-label="<?php esc_attr_e( 'Notas de Repúdio', 'apollo-journal' ); ?>">
			<div class="aj-section-header">
				<h2 class="aj-section-header__title">
					<i class="ri-megaphone-line"></i>
					<?php esc_html_e( 'Notas de Repúdio', 'apollo-journal' ); ?>
				</h2>
				<span class="aj-section-header__count">
					<?php
					$total_nota = wp_count_posts( 'journal_nota' );
					printf(
						/* translators: %d: number of notes */
						esc_html__( '%d notas', 'apollo-journal' ),
						(int) $total_nota->publish
					);
					?>
				</span>
			</div>

			<?php if ( $nota_query->have_posts() ) : ?>
				<?php while ( $nota_query->have_posts() ) : $nota_query->the_post(); ?>
					<?php
					$nrep_code = get_post_meta( get_the_ID(), '_nrep_code', true );
					$note_type = get_post_meta( get_the_ID(), '_apollo_note_type', true );
					$badge     = $nrep_code ?: 'NREP';
					?>
					<a href="<?php the_permalink(); ?>" class="aj-nota-item">
						<span class="aj-nota-item__badge"><?php echo esc_html( $badge ); ?></span>
						<div class="aj-nota-item__body">
							<div class="aj-nota-item__title"><?php the_title(); ?></div>
							<div class="aj-nota-item__meta">
								<?php if ( $note_type ) : ?>
									<span><?php echo esc_html( ucfirst( $note_type ) ); ?></span>
									<span>&middot;</span>
								<?php endif; ?>
								<span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?></span>
							</div>
						</div>
					</a>
				<?php endwhile; ?>
			<?php else : ?>
				<div class="aj-empty">
					<i class="ri-megaphone-line"></i>
					<p><?php esc_html_e( 'Nenhuma nota publicada.', 'apollo-journal' ); ?></p>
				</div>
			<?php endif; ?>

			<?php wp_reset_postdata(); ?>

		</aside>

	</div>

</main>

<?php
/**
 * Fires after the journal page is rendered.
 *
 * @since 1.1.0
 */
do_action( 'apollo/journal/render_jornal' );

if ( function_exists( 'apollo_render_document_close' ) ) {
	apollo_render_document_close();
} else {
	?>
</body>
</html>
	<?php
}
