<?php

/**
 * Marketplace — /anuncios  ·  PHASE 003
 *
 * Blank Canvas Apollo+ screen. Like /feed and /portal, this file is only a
 * mount point: it opens the shared shell, renders the Marketplace layout, and
 * closes. Everything else lives in parts under marketplace/parts/mk/.
 *
 * It no longer renders its own <html>/<body> or links marketplace.css — the
 * shell owns the document, and the screen's CSS is a part loaded into the
 * Apollo+ head so it participates in the same cascade as the shell and the
 * Design System components.
 *
 * The previous self-hosted document is preserved at
 * templates/_legacy/archive-classified.monolith.php for rollback.
 *
 * @package Apollo\Adverts
 * @since   1.0.7
 */

if (! defined('ABSPATH')) {
    exit;
}

$mk_parts = plugin_dir_path(__FILE__) . 'marketplace/parts/mk/';

ob_start();
require $mk_parts . 'styles.php';
$mk_head = ob_get_clean();

$mk_use_plus = function_exists('apollo_plus_open');

if ($mk_use_plus) {
    apollo_plus_open(
        array(
            'title'      => 'Marketplace — Apollo::Rio',
            'extra_head' => $mk_head,
            'screen'     => 'anuncios',
        )
    );
} else {
    // Legacy standalone document — only if apollo-templates is inactive.
    if (function_exists('apollo_render_document_open')) {
        apollo_render_document_open(array('title' => 'Marketplace — Apollo::Rio', 'extra_head' => $mk_head));
    }
    echo '</head><body>';
    if (function_exists('apollo_get_navbar')) {
        apollo_get_navbar();
    }
    echo '<main class="ax-main">';
}

require $mk_parts . 'layout.php';

if ($mk_use_plus) {
    apollo_plus_close();
} else {
    echo '</main>';
    if (function_exists('apollo_render_document_close')) {
        apollo_render_document_close();
    } else {
        echo '</body></html>';
    }
}
