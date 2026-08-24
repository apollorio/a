<?php

/**
 * Archive Local — Apollo Blank Canvas
 *
 * Radar-style location directory at /local with type/area filter pills,
 * search, and GSAP animations. Template-parts architecture.
 *
 * @package Apollo\Local
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/* ─── Constants ────────────────────────────────────────────── */

$loc_cpt  = defined('APOLLO_LOCAL_CPT') ? APOLLO_LOCAL_CPT : 'local';
$tax_type = defined('APOLLO_LOCAL_TAX_TYPE') ? APOLLO_LOCAL_TAX_TYPE : 'local_type';
$tax_area = defined('APOLLO_LOCAL_TAX_AREA') ? APOLLO_LOCAL_TAX_AREA : 'local_area';

/* ─── Taxonomy Terms ───────────────────────────────────────── */

$type_terms = get_terms(
    array(
        'taxonomy'   => $tax_type,
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
    )
);
if (is_wp_error($type_terms)) {
    $type_terms = array();
}

$area_terms = get_terms(
    array(
        'taxonomy'   => $tax_area,
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    )
);
if (is_wp_error($area_terms)) {
    $area_terms = array();
}

/* ─── Locations Query ──────────────────────────────────────── */

$loc_query = new WP_Query(
    array(
        'post_type'      => $loc_cpt,
        'posts_per_page' => 300,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    )
);

$locs = array();

if ($loc_query->have_posts()) {
    while ($loc_query->have_posts()) {
        $loc_query->the_post();
        $lid = get_the_ID();

        $type_names = wp_get_post_terms($lid, $tax_type, array('fields' => 'names'));
        $type_slugs = wp_get_post_terms($lid, $tax_type, array('fields' => 'slugs'));
        $area_names = wp_get_post_terms($lid, $tax_area, array('fields' => 'names'));
        $area_slugs = wp_get_post_terms($lid, $tax_area, array('fields' => 'slugs'));

        if (is_wp_error($type_names)) {
            $type_names = array();
        }
        if (is_wp_error($type_slugs)) {
            $type_slugs = array();
        }
        if (is_wp_error($area_names)) {
            $area_names = array();
        }
        if (is_wp_error($area_slugs)) {
            $area_slugs = array();
        }

        $locs[] = array(
            'id'          => $lid,
            'name'        => get_the_title(),
            'url'         => get_permalink(),
            'image'       => get_the_post_thumbnail_url($lid, 'medium') ?: '',
            'address'     => get_post_meta($lid, '_local_address', true),
            'city'        => get_post_meta($lid, '_local_city', true),
            'phone'       => get_post_meta($lid, '_local_phone', true),
            'website'     => get_post_meta($lid, '_local_website', true),
            'instagram'   => get_post_meta($lid, '_local_instagram', true),
            'capacity'    => get_post_meta($lid, '_local_capacity', true),
            'price_range' => get_post_meta($lid, '_local_price_range', true),
            'lat'         => get_post_meta($lid, '_local_lat', true),
            'lng'         => get_post_meta($lid, '_local_lng', true),
            'types'       => $type_names,
            'type_slugs'  => $type_slugs,
            'areas'       => $area_names,
            'area_slugs'  => $area_slugs,
        );
    }
    wp_reset_postdata();
}

$total_found = count($locs);

// Pre-count per type
$type_counts = array();
foreach ($locs as $loc) {
    foreach ($loc['type_slugs'] as $slug) {
        $type_counts[$slug] = ($type_counts[$slug] ?? 0) + 1;
    }
}

// Path to template-parts
$parts = __DIR__ . '/template-parts/archive-local/';

ob_start();
?>
    <link href="https://fonts.googleapis.com/css2?family=Shrikhand&display=swap" rel="stylesheet">
    <?php require $parts . 'styles.php'; ?>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'GPS — ' . get_bloginfo('name'),
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
    <main class="gps-main">

        <!-- Toolbar -->
        <?php require $parts . 'toolbar.php'; ?>

        <!-- Locations Grid -->
        <section class="gps-grid-wrap">
            <div class="gps-grid" id="gps-grid">
                <?php if (! empty($locs)) : ?>
                    <?php foreach ($locs as $loc) : ?>
                        <?php include $parts . 'loc-card.php'; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="gps-empty" id="gps-empty">
                        <i class="ri-map-pin-line"></i>
                        <p>Nenhum local encontrado</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Live empty (JS-driven) -->
            <div class="gps-empty gps-empty--hidden" id="gps-empty-live">
                <i class="ri-filter-off-line"></i>
                <p>Nenhum local corresponde aos filtros</p>
                <button class="gps-empty__clear" id="gps-clear-all">Limpar filtros</button>
            </div>
        </section>

        <!-- Counter -->
        <div class="gps-counter">
            <span class="gps-counter__num" id="gps-counter"><?php echo esc_html($total_found); ?></span>
            <span class="gps-counter__label">locais cadastrados</span>
        </div>

    </main>

    <!-- GSAP already loaded by CDN core.js (v3.14.2) -->

    <!-- Scripts -->
    <?php require $parts . 'scripts.php'; ?>

</body>

</html>