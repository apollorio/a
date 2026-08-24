<?php

/**
 * Hub.rio directory — /hub, /hub/projetos, /hub/tarefas  ·  PHASE 005
 *
 * Blank Canvas Apollo+ screen. Official #/hub surface for guests
 * (_official_layout/js/view.hub.js). Logged-in users hitting bare /hub are
 * redirected to /hub/app (the former HubRio editor that used to occupy /hub).
 *
 * Deliberately NOT the Linktree-style single-hub page (that is
 * templates/single-hub.php, at /hub/{username}, unrelated and untouched).
 *
 * @package Apollo\Hub
 * @since   1.0.2
 */

if (! defined('ABSPATH')) {
    exit;
}

$ahd_view = (string) get_query_var('apollo_hub_view');
if (! in_array($ahd_view, array('hub', 'hub-projetos', 'hub-tarefas'), true)) {
    $ahd_view = 'hub';
}

/* Screen config — mirrors view.hub.js's SCREENS map 1:1 (icon/title/tag
   verbatim, grid flag verbatim: true only for the bare /hub route). */
$ahd_screens = array(
    'hub'          => array(
        'icon'  => 'ri-body-scan-line',
        'title' => __('Hub.rio', 'apollo-hub'),
        'tag'   => __('o ecossistema apollo em um lugar', 'apollo-hub'),
        'grid'  => true,
        'route' => 'hub',
    ),
    'hub-projetos' => array(
        'icon'  => 'ri-terminal-window-line',
        'title' => __('Meus projetos', 'apollo-hub'),
        'tag'   => __('o ecossistema apollo em um lugar', 'apollo-hub'),
        'grid'  => false,
        'route' => 'hub/projetos',
    ),
    'hub-tarefas'  => array(
        'icon'  => 'ri-todo-line',
        'title' => __('Minhas tarefas', 'apollo-hub'),
        'tag'   => __('o ecossistema apollo em um lugar', 'apollo-hub'),
        'grid'  => false,
        'route' => 'hub/tarefas',
    ),
);
$ahd_cfg   = $ahd_screens[$ahd_view];
$dir_parts = APOLLO_HUB_DIR . 'templates/parts/dir/';

ob_start();
require $dir_parts . 'styles.php';
$ahd_head = ob_get_clean();

if (! function_exists('apollo_plus_open')) {
    wp_die(esc_html__('Apollo Hub: shell indisponível.', 'apollo-hub'));
}

apollo_plus_open(
    array(
        'title'      => get_bloginfo('name') . ' — ' . $ahd_cfg['title'],
        'extra_head' => $ahd_head,
        'screen'     => $ahd_cfg['route'],
    )
);

require $dir_parts . 'layout.php';

apollo_plus_close();
