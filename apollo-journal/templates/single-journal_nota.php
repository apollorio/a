<?php
/**
 * Template: Single Journal Nota (nota de repúdio)
 *
 * Compact article view for journal_nota CPT.
 * Canvas mode: uses wp_head / wp_footer.
 *
 * @package Apollo\Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

the_post();

$nrep_code = get_post_meta( get_the_ID(), '_nrep_code', true );
$note_type = get_post_meta( get_the_ID(), '_apollo_note_type', true );
$source    = get_post_meta( get_the_ID(), '_apollo_source', true );
$author_id = get_the_author_meta( 'ID' );

// Related notas.
$related = new WP_Query( array(
	'post_type'      => 'journal_nota',
	'posts_per_page' => 5,
	'post_status'    => 'publish',
	'post__not_in'   => array( get_the_ID() ),
	'orderby'        => 'date',
	'order'          => 'DESC',
) );
if ( $related->have_posts() ) {
	$related_ids = wp_list_pluck( $related->posts, 'ID' );
	update_post_meta_cache( $related_ids );
}
?>

<!-- Apollo CDN -->
<script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

<style id="aj-single-nota-inline">
/* ── Nota Layout ── */
.aj-nota {
    max-width: 680px;
    margin: 0 auto;
    padding: 0 var(--space-4, 24px) var(--space-6, 48px);
}

/* ── Header ── */
.aj-nota-header {
    padding: var(--space-5, 32px) 0;
    text-align: center;
}

.aj-nota-header__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    background: rgba(229, 57, 53, 0.08);
    border-radius: var(--r-sm, 10px);
    margin-bottom: var(--space-3, 16px);
}

.aj-nota-header__icon i {
    font-size: 24px;
    color: #e53935;
}

.aj-nota-header__badge {
    display: inline-block;
    font-family: var(--ff-mono, "Space Mono", monospace);
    font-size: 10px;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: #e53935;
    border: 1px solid #e53935;
    padding: 4px 14px;
    margin-bottom: var(--space-3, 16px);
}

.aj-nota-header__title {
    font-family: var(--ff-fun, "Syne", sans-serif);
    font-size: clamp(1.5rem, 1.1rem + 2vi, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
    color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
    margin: 0 0 var(--space-2, 8px);
}

.aj-nota-header__type {
    font-family: var(--ff-mono, "Space Mono", monospace);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--txt-muted, rgba(19, 21, 23, 0.31));
    margin-bottom: var(--space-3, 16px);
}

/* ── Author ── */
.aj-nota-author {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: var(--space-3, 16px) 0;
    border-top: 1px solid var(--border, #00000012);
    border-bottom: 1px solid var(--border, #00000012);
}

.aj-nota-author__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.aj-nota-author__name {
    font-family: var(--ff-main, "Space Grotesk", sans-serif);
    font-size: 13px;
    font-weight: 600;
    color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
}

.aj-nota-author__meta {
    font-family: var(--ff-mono, "Space Mono", monospace);
    font-size: 10px;
    color: var(--txt-muted, rgba(19, 21, 23, 0.31));
}

/* ── Body ── */
.aj-nota-body {
    padding: var(--space-5, 32px) 0;
    font-family: var(--ff-main, "Space Grotesk", sans-serif);
    font-size: 15px;
    line-height: 1.8;
    color: var(--txt-color, rgba(19, 21, 23, 0.77));
}

.aj-nota-body p {
    margin: 0 0 var(--space-3, 16px);
}

.aj-nota-body blockquote {
    border-left: 3px solid #e53935;
    padding: var(--space-2, 8px) var(--space-3, 16px);
    margin: var(--space-3, 16px) 0;
    font-style: italic;
    color: var(--txt-muted, rgba(19, 21, 23, 0.31));
}

.aj-nota-body a {
    color: #e53935;
    text-decoration: underline;
    text-underline-offset: 2px;
}

/* ── Source ── */
.aj-nota-source {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: var(--space-3, 16px);
    background: var(--surface, rgba(var(--rgb-d, 0, 0, 0), 0.04));
    border-radius: var(--r-sm, 10px);
    font-family: var(--ff-mono, "Space Mono", monospace);
    font-size: 11px;
    color: var(--txt-muted, rgba(19, 21, 23, 0.31));
    margin-bottom: var(--space-4, 24px);
}

.aj-nota-source i {
    color: #e53935;
    font-size: 16px;
}

.aj-nota-source a {
    color: var(--txt-color, rgba(19, 21, 23, 0.77));
    text-decoration: underline;
    text-underline-offset: 2px;
}

/* ── Related Sidebar ── */
.aj-nota-related {
    padding: var(--space-4, 24px) 0;
    border-top: 2px solid #e53935;
}

.aj-nota-related__title {
    font-family: var(--ff-fun, "Syne", sans-serif);
    font-size: 15px;
    font-weight: 700;
    color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
    margin: 0 0 var(--space-3, 16px);
    display: flex;
    align-items: center;
    gap: 8px;
}

.aj-nota-related__title i {
    color: #e53935;
}

.aj-nota-related-item {
    display: flex;
    gap: 12px;
    padding: var(--space-2, 8px) 0;
    border-bottom: 1px solid var(--border, #00000012);
    text-decoration: none;
    color: inherit;
    transition: opacity var(--transition-ui, .25s);
}

.aj-nota-related-item:last-child {
    border-bottom: none;
}

.aj-nota-related-item:hover {
    opacity: 0.8;
}

.aj-nota-related-item__badge {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    font-family: var(--ff-mono, "Space Mono", monospace);
    font-size: 8px;
    font-weight: 700;
    color: #e53935;
    background: rgba(229, 57, 53, 0.08);
    border-radius: var(--r-xs, 6px);
}

.aj-nota-related-item__title {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.35;
    color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.aj-nota-related-item__meta {
    font-size: 10px;
    color: var(--txt-muted, rgba(19, 21, 23, 0.31));
    margin-top: 2px;
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
    color: #e53935;
}

/* ── Dark mode ── */
html.dark-mode .aj-nota-body {
    color: var(--txt-color, rgba(255, 255, 255, 0.77));
}

html.dark-mode .aj-nota-source {
    background: var(--surface, #1a1a1c);
}
</style>

<article class="aj-nota" itemscope itemtype="https://schema.org/NewsArticle">

    <!-- Back Link -->
    <a href="<?php echo esc_url( home_url( '/jornal' ) ); ?>" class="aj-back-link">
        <i class="ri-arrow-left-s-line"></i>
        <?php esc_html_e( 'Jornal.rio', 'apollo-journal' ); ?>
    </a>

    <!-- ═══════ Header ═══════ -->
    <header class="aj-nota-header">
        <div class="aj-nota-header__icon">
            <i class="ri-megaphone-line"></i>
        </div>

        <?php if ( $nrep_code ) : ?>
        <div>
            <span class="aj-nota-header__badge"><?php echo esc_html( $nrep_code ); ?></span>
        </div>
        <?php endif; ?>

        <h1 class="aj-nota-header__title" itemprop="headline"><?php the_title(); ?></h1>

        <?php if ( $note_type ) : ?>
        <div class="aj-nota-header__type"><?php echo esc_html( ucfirst( $note_type ) ); ?></div>
        <?php endif; ?>

        <div class="aj-nota-author">
            <?php echo get_avatar( $author_id, 36, '', '', array( 'class' => 'aj-nota-author__avatar' ) ); ?>
            <div>
                <div class="aj-nota-author__name" itemprop="author" itemscope itemtype="https://schema.org/Person">
                    <span itemprop="name"><?php echo esc_html( get_the_author() ); ?></span>
                </div>
                <div class="aj-nota-author__meta">
                    <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" itemprop="datePublished">
                        <?php echo esc_html( get_the_date() ); ?>
                    </time>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══════ Body ═══════ -->
    <div class="aj-nota-body" itemprop="articleBody">
        <?php the_content(); ?>
    </div>

    <!-- ═══════ Source ═══════ -->
    <?php if ( $source ) : ?>
    <div class="aj-nota-source">
        <i class="ri-link"></i>
        <span>
            <?php esc_html_e( 'Fonte:', 'apollo-journal' ); ?>
            <?php
				if ( filter_var( $source, FILTER_VALIDATE_URL ) ) {
					printf(
						'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_url( $source ),
						esc_html( wp_parse_url( $source, PHP_URL_HOST ) ?: $source )
					);
				} else {
					echo esc_html( $source );
				}
				?>
        </span>
    </div>
    <?php endif; ?>

    <!-- ═══════ Related Notas ═══════ -->
    <?php if ( $related->have_posts() ) : ?>
    <section class="aj-nota-related">
        <h2 class="aj-nota-related__title">
            <i class="ri-megaphone-line"></i>
            <?php esc_html_e( 'Outras Notas', 'apollo-journal' ); ?>
        </h2>
        <?php while ( $related->have_posts() ) : $related->the_post(); ?>
        <?php $r_nrep = get_post_meta( get_the_ID(), '_nrep_code', true ); ?>
        <a href="<?php the_permalink(); ?>" class="aj-nota-related-item">
            <span class="aj-nota-related-item__badge"><?php echo esc_html( $r_nrep ?: 'NREP' ); ?></span>
            <div>
                <div class="aj-nota-related-item__title"><?php the_title(); ?></div>
                <div class="aj-nota-related-item__meta">
                    <?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?>
                </div>
            </div>
        </a>
        <?php endwhile; ?>
    </section>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>

</article>

<?php
/**
 * Fires after a single journal_nota is rendered.
 *
 * @since 1.1.0
 * @param int $post_id The nota post ID.
 */
do_action( 'apollo/journal/render_nota', get_the_ID() );

get_footer();