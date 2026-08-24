<?php

/**
 * Meus Anúncios — /anuncios/meus  ·  PHASE 008
 *
 * Blank Canvas Apollo+ screen, same mount-point shape as phases 001-007:
 * opens the shared shell, renders the listing layout, closes. Real data via
 * apollo_adverts_get_user_listings() (includes/my-listings-data.php) — same
 * author+status query the existing BuddyPress "Meus Anúncios" profile tab
 * (/members/{user}/anuncios/meus/, includes/buddypress.php) already uses.
 * That BP tab is untouched and keeps working; this is an additive, ecosystem
 * -shell version of the same real feature, matching the mockup's
 * #view-anuncios-meus screen.
 *
 * @package Apollo\Adverts
 * @since   1.1.0
 * @see     parts/mine/{styles,layout}.php
 * @see     includes/my-listings-data.php
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! is_user_logged_in()) {
    wp_safe_redirect(home_url('/acesso?redirect=' . rawurlencode(home_url('/anuncios/meus'))));
    exit;
}

if (! function_exists('apollo_plus_open')) {
    wp_die(esc_html__('Apollo Templates: shell API indisponível.', 'apollo-adverts'));
}

$mna_parts = plugin_dir_path(__FILE__) . 'parts/mine/';

ob_start();
require $mna_parts . 'styles.php';
$mna_head = ob_get_clean();

apollo_plus_open(
    array(
        'title'      => get_bloginfo('name') . ' — Meus Anúncios',
        'extra_head' => $mna_head,
        'screen'     => 'anuncios/meus',
    )
);

require $mna_parts . 'layout.php';

apollo_plus_close();
