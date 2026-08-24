<?php

/**
 * Meus Eventos — /eventos/meus  ·  PHASE 007
 *
 * Blank Canvas Apollo+ screen, same mount-point shape as phases 001-006:
 * opens the shared shell, renders the KPI dashboard layout, closes. Real
 * "Painel de Eventos" — additive alongside the existing /meus-eventos,
 * /painel, /painel/eventos, /dashboard (dashboard-event.php, untouched),
 * which keep serving the simpler author/co-author manage list they always
 * have. This screen is the richer KPI view the mockup's
 * #view-eventos-meus specifies (ticket-status donut, season/genre bars,
 * monthly timeline, DJ/venue/coauthor leaderboards, content-readiness
 * checklist, upcoming-events table) — all real data, see
 * template-parts/dash/data.php.
 *
 * @package Apollo\Event
 * @since   3.2.0
 * @see     template-parts/dash/{styles,layout,data}.php
 */

if (! defined('ABSPATH')) {
    exit;
}

if (function_exists('apollo_event_ensure_helpers')) {
    apollo_event_ensure_helpers();
}

if (! is_user_logged_in()) {
    wp_safe_redirect(home_url('/acesso?redirect=' . rawurlencode(home_url('/eventos/meus'))));
    exit;
}

if (! function_exists('apollo_plus_open')) {
    wp_die(esc_html__('Apollo Templates: shell API indisponível.', 'apollo-event'));
}

$aed_parts = __DIR__ . '/template-parts/dash/';

ob_start();
require $aed_parts . 'styles.php';
$aed_head = ob_get_clean();

apollo_plus_open(
    array(
        'title'      => get_bloginfo('name') . ' — Meus Eventos',
        'extra_head' => $aed_head,
        'screen'     => 'eventos/meus',
    )
);

require $aed_parts . 'layout.php';

apollo_plus_close();
