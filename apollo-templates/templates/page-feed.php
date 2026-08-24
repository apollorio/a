<?php

/**
 * Feed — /feed  ·  PHASE 001
 *
 * Blank Canvas Apollo+ screen. This file is now ONLY a mount point: it opens
 * the shared shell, renders the Feed layout, and closes. Everything that used
 * to live here inline — chrome, styles, markup, data — moved into parts:
 *
 *   shell            includes/apollo-plus-api.php  -> apollo_plus_open/close()
 *   aside (nav)      template-parts/apollo-plus/aside.php
 *   screen styles    template-parts/feed/styles.php
 *   layout           template-parts/feed/layout.php
 *     header           feed/header.php
 *     composer         feed/composer.php
 *     stream + post    feed/stream.php - feed/post.php
 *     sidebar widgets  feed/widgets/{news,events,trending,nucleos,market,stats}.php
 *   data             includes/feed-data.php  (real WP - no simulated.data.js)
 *
 * The previous 1131-line monolith is preserved at
 * templates/_legacy/page-feed.monolith.php for rollback.
 *
 * AUTH: /feed is members-only per apollo-social's route table (auth:true).
 * Guests are sent to /acesso with a redirect back, rather than being shown a
 * feed they cannot participate in.
 *
 * @package Apollo\Templates
 * @since   1.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! is_user_logged_in()) {
    wp_safe_redirect(home_url('/acesso?redirect=' . rawurlencode(home_url('/feed'))));
    exit;
}

if (! function_exists('apollo_plus_open')) {
    wp_die(esc_html__('Apollo Templates: shell API indisponível.', 'apollo-templates'));
}

ob_start();
apollo_plus_part('feed/styles');
$apf_head = ob_get_clean();

apollo_plus_open(
    array(
        'title'      => get_bloginfo('name') . ' — Feed',
        'extra_head' => $apf_head,
        'screen'     => 'feed',
    )
);

apollo_plus_part('feed/layout');

apollo_plus_close();
