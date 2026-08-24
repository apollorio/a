<?php

/**
 * Archive DJ — Apollo Blank Canvas
 *
 * Radar-style DJ directory with sound filter pills,
 * search, verified badges, and GSAP animations.
 * Template-parts architecture for modularity.
 *
 * @package Apollo\DJs
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/* ─── Constants ────────────────────────────────────────────── */

$dj_cpt    = defined('APOLLO_DJ_CPT') ? APOLLO_DJ_CPT : 'dj';
$tax_sound = defined('APOLLO_DJ_TAX_SOUND') ? APOLLO_DJ_TAX_SOUND : 'sound';

/* ─── Sound Terms ──────────────────────────────────────────── */

$sound_terms = get_terms(
    array(
        'taxonomy'   => $tax_sound,
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
    )
);
if (is_wp_error($sound_terms)) {
    $sound_terms = array();
}

/* ─── DJ Query ─────────────────────────────────────────────── */

$dj_query = new WP_Query(
    array(
        'post_type'      => $dj_cpt,
        'posts_per_page' => 300,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    )
);

$djs = array();

if ($dj_query->have_posts()) {
    while ($dj_query->have_posts()) {
        $dj_query->the_post();
        $did = get_the_ID();

        $sound_names = wp_get_post_terms($did, $tax_sound, array('fields' => 'names'));
        $sound_slugs = wp_get_post_terms($did, $tax_sound, array('fields' => 'slugs'));
        if (is_wp_error($sound_names)) {
            $sound_names = array();
        }
        if (is_wp_error($sound_slugs)) {
            $sound_slugs = array();
        }

        $djs[] = array(
            'id'             => $did,
            'name'           => get_the_title(),
            'url'            => get_permalink(),
            'image'          => function_exists('apollo_dj_get_image')
                ? apollo_dj_get_image($did)
                : (get_the_post_thumbnail_url($did, 'medium') ?: ''),
            'verified'       => function_exists('apollo_dj_is_verified')
                ? apollo_dj_is_verified($did) : false,
            'bio'            => get_post_meta($did, '_dj_bio_short', true),
            'sounds'         => $sound_names,
            'sound_slugs'    => $sound_slugs,
            'links'          => function_exists('apollo_dj_get_links')
                ? apollo_dj_get_links($did) : array(),
            'upcoming_count' => function_exists('apollo_dj_count_upcoming_events')
                ? apollo_dj_count_upcoming_events($did) : 0,
        );
    }
    wp_reset_postdata();
}

$total_found = count($djs);

// Pre-count per sound
$sound_counts = array();
foreach ($djs as $dj) {
    foreach ($dj['sound_slugs'] as $slug) {
        $sound_counts[$slug] = ($sound_counts[$slug] ?? 0) + 1;
    }
}

// Path to template-parts
$parts = __DIR__ . '/template-parts/archive-dj/';

ob_start();
?>
    <link href="https://fonts.googleapis.com/css2?family=Shrikhand&display=swap" rel="stylesheet">
    <?php require $parts . 'styles.php'; ?>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'DJs — ' . get_bloginfo('name'),
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body>

    <!-- Page Loader -->
    <div class="page-loader"></div>

    <!-- Navbar -->
    <?php
    if (function_exists('apollo_get_navbar')) {
        apollo_get_navbar();
    }
    ?>

    <!-- Hero -->
    <?php require $parts . 'hero.php'; ?>

    <!-- Main Content -->
    <main class="dj-main">

        <!-- Toolbar -->
        <?php require $parts . 'toolbar.php'; ?>

        <!-- DJ Grid -->
        <section class="dj-grid-wrap">
            <div class="dj-grid" id="dj-grid">
                <?php if (! empty($djs)) : ?>
                    <?php foreach ($djs as $dj) : ?>
                        <?php include $parts . 'dj-card.php'; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="dj-empty" id="dj-empty">
                        <i class="ri-disc-line"></i>
                        <p>Nenhum DJ encontrado</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Live empty (JS-driven) -->
            <div class="dj-empty dj-empty--hidden" id="dj-empty-live">
                <i class="ri-filter-off-line"></i>
                <p>Nenhum DJ corresponde aos filtros</p>
                <button class="dj-empty__clear" id="dj-clear-all">Limpar filtros</button>
            </div>
        </section>

        <!-- Counter -->
        <div class="dj-counter">
            <span class="dj-counter__num" id="dj-counter"><?php echo esc_html($total_found); ?></span>
            <span class="dj-counter__label">DJs cadastrados</span>
        </div>

    </main>

    <!-- GSAP already loaded by CDN core.js (v3.14.2) -->

    <!-- Scripts -->
    <?php require $parts . 'scripts.php'; ?>

</body>

</html>