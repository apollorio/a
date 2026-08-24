<?php

/**
 * Single DJ — page template.
 *
 * Composes the cells in template-parts/single/ through apollo_dj_render_single().
 * The page holds no markup of its own beyond the document shell: every visible
 * node comes from a cell, and the cells are generated from the approved mockup
 * by _sandbox/build-dj-cells.py.
 *
 * WHAT THIS REPLACED
 * ------------------
 * A 161-line monolith rendering `.a-dj-single__*` BEM markup that had no
 * relationship to the approved design — banner, avatar, link groups, upcoming
 * list. It is preserved verbatim at _legacy/single-dj.monolith.php for rollback
 * and is not loaded.
 *
 * The old file also built its own context inline (bio_short, banner, image,
 * verified, sounds, links, events), which is precisely why a DJ could not be
 * rendered anywhere except a page request. That data now lives in
 * apollo_dj_single_context(), so the same DJ renders identically as a page, an
 * inline embed, and the REST fragment the lightbox consumes.
 *
 * CANVAS VARIANT: "apollo" — zero chrome, per 18-canvas-shell.json. The design
 * carries its own fixed `.ev-top` (back · name · share); apollo_render_navbar()
 * is deliberately NOT called. The old template called it for logged-in users,
 * which put the app navbar on top of the artist card's own bar.
 *
 * @package Apollo\DJs
 * @since   1.0.5
 */

if (! defined('ABSPATH')) {
    exit;
}

$dj_id = (int) get_the_ID();

if (! function_exists('apollo_dj_render_single')) {
    // Renderer missing (partial deploy) — fall back rather than render a blank page.
    $apollo_dj_legacy = __DIR__ . '/_legacy/single-dj.monolith.php';
    if (is_readable($apollo_dj_legacy)) {
        require $apollo_dj_legacy;
        return;
    }
}

/*
 * The stylesheet is a cell, and it must reach <head> — a full-bleed hero that
 * paints before its CSS lands flashes edge-to-edge unstyled. Rendered here and
 * handed to the document opener; the body render below then skips it.
 */
$extra_head = apollo_dj_render_part('styles', apollo_dj_single_context($dj_id));

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => get_the_title($dj_id) . ' — Apollo::Rio',
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <script src="<?php echo esc_url((defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/') . 'core.js'); ?>" fetchpriority="high"></script>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template cell output.
    echo $extra_head;
    ?>
</head>
<body>
    <?php
}

/*
 * Every cell except 'styles', which is already in <head>. The order lives in
 * APOLLO_DJ_SINGLE_PARTS (includes/render-single.php) — the single place the
 * page composition is declared.
 */
$apollo_dj_body_parts = array_values(
    array_diff(APOLLO_DJ_SINGLE_PARTS, array('styles'))
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template cell output.
echo apollo_dj_render_single(
    $dj_id,
    array(
        'mode'  => 'page',
        'parts' => $apollo_dj_body_parts,
    )
);

if (function_exists('apollo_render_document_close')) {
    apollo_render_document_close();
} else {
    ?>
</body>
</html>
    <?php
}
