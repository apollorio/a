<?php

/**
 * Template: Archive Journal
 *
 * Magazine-style archive for categories and custom taxonomies.
 * Mobile-first, Apollo Design System, ColorMag-inspired grid layout.
 *
 * Canvas mode: uses wp_head / wp_footer.
 *
 * @package Apollo\Journal
 */

defined('ABSPATH') || exit;

$queried    = get_queried_object();
$term_name  = $queried instanceof \WP_Term ? $queried->name : __('Journal', 'apollo-journal');
$term_desc  = $queried instanceof \WP_Term ? $queried->description : '';
$term_count = $queried instanceof \WP_Term ? $queried->count : 0;

get_header();
?>

<!-- Apollo CDN -->
<script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

<style id="aj-archive-inline">
    /* ── Archive Layout ── */
    .aj-archive {
        max-width: 1080px;
        margin: 0 auto;
        padding: 0 var(--space-4, 24px) var(--space-6, 48px);
    }

    .aj-hero {
        padding: var(--space-6, 48px) 0 var(--space-4, 24px);
        border-bottom: 1px solid var(--border, #00000027);
        margin-bottom: var(--space-5, 32px);
    }

    .aj-hero__label {
        font-family: var(--ff-mono, "Space Mono", monospace);
        font-size: 10px;
        color: var(--primary, #FF9820);
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .aj-hero__title {
        font-family: var(--ff-fun, "Syne", sans-serif);
        font-size: clamp(1.75rem, 1.4rem + 1.75vi, 2.5rem);
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.1;
        color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
        margin-bottom: 8px;
    }

    .aj-hero__meta {
        font-size: 13px;
        color: var(--txt-muted, rgba(19, 21, 23, 0.31));
    }

    .aj-hero__desc {
        font-size: 14px;
        color: var(--muted, rgba(19, 21, 23, 0.31));
        margin-top: 8px;
        line-height: 1.6;
        max-width: 600px;
    }

    /* ── Featured (first post — large) ── */
    .aj-featured {
        display: block;
        margin-bottom: var(--space-5, 32px);
        border-radius: var(--r-sm, 10px);
        overflow: hidden;
        background: var(--surface, rgba(var(--rgb-d), 0.04));
        text-decoration: none;
        color: inherit;
        transition: background var(--transition-ui, .25s);
    }

    .aj-featured:hover {
        background: var(--card-hover, rgba(var(--rgb-d), 0.06));
    }

    .aj-featured__img {
        width: 100%;
        aspect-ratio: 21 / 9;
        object-fit: cover;
        display: block;
    }

    .aj-featured__body {
        padding: var(--space-4, 24px);
    }

    .aj-featured__badge {
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

    .aj-featured__title {
        font-family: var(--ff-fun, "Syne", sans-serif);
        font-size: clamp(1.25rem, 1rem + 1.25vi, 1.75rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin-bottom: 8px;
        color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
    }

    .aj-featured__excerpt {
        font-size: 14px;
        color: var(--txt-color, rgba(19, 21, 23, 0.77));
        line-height: 1.6;
        margin-bottom: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .aj-featured__footer {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 12px;
        color: var(--txt-muted, rgba(19, 21, 23, 0.31));
    }

    /* ── Grid ── */
    .aj-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: var(--space-3, 16px);
    }

    @media (min-width: 640px) {
        .aj-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 960px) {
        .aj-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    /* ── Card ── */
    .aj-card {
        display: flex;
        flex-direction: column;
        background: var(--surface, rgba(var(--rgb-d), 0.04));
        border-radius: var(--r-sm, 10px);
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: background var(--transition-ui, .25s), transform .2s;
    }

    .aj-card:hover {
        background: var(--card-hover, rgba(var(--rgb-d), 0.06));
        transform: translateY(-2px);
    }

    .aj-card__img {
        width: 100%;
        aspect-ratio: 16 / 10;
        object-fit: cover;
        display: block;
    }

    .aj-card__body {
        padding: var(--space-3, 16px);
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .aj-card__badge {
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

    .aj-card__badge--nrep {
        color: #e53935;
        border-color: #e53935;
    }

    .aj-card__title {
        font-family: var(--ff-main, "Space Grotesk", sans-serif);
        font-size: 15px;
        font-weight: 600;
        letter-spacing: -0.01em;
        line-height: 1.3;
        margin-bottom: 8px;
        color: var(--txt-color-hover, rgba(19, 21, 23, 0.9));
    }

    .aj-card__meta {
        margin-top: auto;
        font-size: 11px;
        color: var(--txt-muted, rgba(19, 21, 23, 0.31));
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ── Pagination ── */
    .aj-pagination {
        display: flex;
        justify-content: center;
        gap: var(--space-2, 8px);
        padding: var(--space-5, 32px) 0;
    }

    .aj-pagination a,
    .aj-pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        font-family: var(--ff-mono, "Space Mono", monospace);
        font-size: 12px;
        border-radius: var(--r-sm, 10px);
        text-decoration: none;
        transition: var(--transition-ui, .25s);
    }

    .aj-pagination a {
        background: var(--surface, rgba(var(--rgb-d), 0.04));
        color: var(--txt-color, rgba(19, 21, 23, 0.77));
    }

    .aj-pagination a:hover {
        background: var(--card-hover, rgba(var(--rgb-d), 0.06));
    }

    .aj-pagination .current {
        background: var(--primary, #FF9820);
        color: #fff;
    }

    /* ── Empty ── */
    .aj-empty {
        text-align: center;
        padding: var(--space-6, 48px) var(--space-4, 24px);
        color: var(--txt-muted, rgba(19, 21, 23, 0.31));
    }

    .aj-empty i {
        font-size: 48px;
        margin-bottom: 16px;
        display: block;
    }
</style>

<main class="aj-archive">
    <!-- Hero Header -->
    <header class="aj-hero">
        <div class="aj-hero__label">Apollo Journal</div>
        <h1 class="aj-hero__title"><?php echo esc_html($term_name); ?></h1>
        <?php if ($term_count) : ?>
            <div class="aj-hero__meta">
                <?php
                printf(
                    /* translators: %d: number of articles */
                    esc_html__('%d artigos publicados', 'apollo-journal'),
                    $term_count
                );
                ?>
            </div>
        <?php endif; ?>
        <?php if ($term_desc) : ?>
            <div class="aj-hero__desc"><?php echo wp_kses_post($term_desc); ?></div>
        <?php endif; ?>
    </header>

    <?php if (have_posts()) : ?>

        <?php
        // ── Featured: first post large ──
        the_post();
        $cats     = get_the_category();
        $cat_name = ! empty($cats) ? $cats[0]->name : 'Journal';
        $cat_slug = ! empty($cats) ? $cats[0]->slug : '';
        $nrep     = get_post_meta(get_the_ID(), '_nrep_code', true);
        $badge    = $nrep ? $nrep : strtoupper($cat_name);
        $badge_cl = $nrep ? 'aj-featured__badge aj-card__badge--nrep' : 'aj-featured__badge';
        ?>
        <a href="<?php the_permalink(); ?>" class="aj-featured">
            <?php if (has_post_thumbnail()) : ?>
                <img class="aj-featured__img" src="<?php echo esc_url(get_the_post_thumbnail_url(null, 'large')); ?>"
                    alt="<?php the_title_attribute(); ?>" loading="eager">
            <?php endif; ?>
            <div class="aj-featured__body">
                <span class="<?php echo esc_attr($badge_cl); ?>"><?php echo esc_html($badge); ?></span>
                <h2 class="aj-featured__title"><?php the_title(); ?></h2>
                <div class="aj-featured__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 30)); ?></div>
                <div class="aj-featured__footer">
                    <span><?php echo esc_html(get_the_author()); ?></span>
                    <span>&middot;</span>
                    <span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?></span>
                </div>
            </div>
        </a>

        <!-- Grid of remaining posts -->
        <div class="aj-grid">
            <?php
            while (have_posts()) :
                the_post();
                $cats     = get_the_category();
                $cat_name = ! empty($cats) ? $cats[0]->name : 'Journal';
                $cat_slug = ! empty($cats) ? $cats[0]->slug : '';
                $nrep     = get_post_meta(get_the_ID(), '_nrep_code', true);
                $badge    = $nrep ? $nrep : strtoupper($cat_name);
                $badge_cl = $nrep ? 'aj-card__badge aj-card__badge--nrep' : 'aj-card__badge';
            ?>
                <a href="<?php the_permalink(); ?>" class="aj-card">
                    <?php if (has_post_thumbnail()) : ?>
                        <img class="aj-card__img" src="<?php echo esc_url(get_the_post_thumbnail_url(null, 'medium_large')); ?>"
                            alt="<?php the_title_attribute(); ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="aj-card__body">
                        <span class="<?php echo esc_attr($badge_cl); ?>"><?php echo esc_html($badge); ?></span>
                        <h3 class="aj-card__title"><?php the_title(); ?></h3>
                        <div class="aj-card__meta">
                            <span><?php echo esc_html(get_the_author()); ?></span>
                            <span>&middot;</span>
                            <span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?></span>
                        </div>
                    </div>
                </a>
            <?php
            endwhile;
            ?>
        </div>

        <!-- Pagination -->
        <nav class="aj-pagination">
            <?php
            $pagination = paginate_links(
                array(
                    'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
                    'next_text' => '<i class="ri-arrow-right-s-line"></i>',
                    'type'      => 'plain',
                )
            );
            if ( $pagination ) {
                echo wp_kses_post( $pagination );
            }
            ?>
        </nav>

    <?php else : ?>

        <div class="aj-empty">
            <i class="ri-newspaper-line"></i>
            <p><?php esc_html_e('Nenhum artigo encontrado nesta seção.', 'apollo-journal'); ?></p>
        </div>

    <?php endif; ?>
</main>

<?php
get_footer();
