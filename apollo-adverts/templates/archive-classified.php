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
$mk_chrome = ob_get_clean();

$mk_v   = defined('APOLLO_ADVERTS_VERSION') ? APOLLO_ADVERTS_VERSION : '1.2.2';
$mk_css = defined('APOLLO_ADVERTS_URL') ? APOLLO_ADVERTS_URL . 'assets/css/market-screen.css?v=' . $mk_v : '';
$mk_rt  = defined('APOLLO_ADVERTS_URL') ? APOLLO_ADVERTS_URL . 'assets/css/rt-card.css?v=' . $mk_v : '';
$mk_rtjs = defined('APOLLO_ADVERTS_URL') ? APOLLO_ADVERTS_URL . 'assets/js/rt-card.js?v=' . $mk_v : '';
$mk_js  = defined('APOLLO_ADVERTS_URL') ? APOLLO_ADVERTS_URL . 'assets/js/marketplace.js?v=' . $mk_v : '';
$mk_nonce = function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : '';
$mk_head  = '';
if ($mk_css !== '') {
    $mk_head .= '<link rel="stylesheet" id="apollo-mk-screen" href="' . esc_url($mk_css) . '">';
}
if ($mk_rt !== '') {
    $mk_head .= '<link rel="stylesheet" id="apollo-rt-card" href="' . esc_url($mk_rt) . '">';
}
$mk_head .= $mk_chrome;
if ($mk_js !== '') {
    $mk_head .= '<script' . $mk_nonce . ' src="' . esc_url($mk_js) . '" defer></script>';
}
if ($mk_rtjs !== '') {
    $mk_head .= '<script' . $mk_nonce . ' src="' . esc_url($mk_rtjs) . '" defer></script>';
}

$mk_use_plus = function_exists('apollo_plus_open');
$mk_title    = 'Classificados — Marketplace Apollo::Rio';

if ($mk_use_plus) {
    apollo_plus_open(
        array(
            'title'      => $mk_title,
            'extra_head' => $mk_head,
            'screen'     => 'anuncios',
        )
    );
} else {
    // Legacy standalone document — only if apollo-templates is inactive.
    if (function_exists('apollo_render_document_open')) {
        apollo_render_document_open(array('title' => $mk_title, 'extra_head' => $mk_head));
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
