<?php

/**
 * Single classified — /anuncio/{slug}/
 *
 * Blank Canvas Apollo+ screen. One advert, one URL. Body is the marketplace
 * card infos for that listing plus the pre-contact safety step (users in
 * common / trust / staff) when the core safety contract applies.
 *
 * Mount only — layout and styles live under templates/parts/single/.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$classified_id = (int) get_the_ID();
if (! $classified_id || get_post_type($classified_id) !== APOLLO_CPT_CLASSIFIED) {
    status_header(404);
    nocache_headers();
    echo esc_html__('Anúncio não encontrado.', 'apollo-adverts');
    exit;
}

$type      = (string) get_post_meta($classified_id, '_classified_type', true);
$is_ticket = defined('APOLLO_ADVERTS_TICKET_TYPES')
    ? in_array($type, APOLLO_ADVERTS_TICKET_TYPES, true)
    : in_array($type, array('ticket', 'ticket_sell'), true);
$is_accom  = defined('APOLLO_ADVERTS_ACCOMMODATION_TYPES')
    ? in_array($type, APOLLO_ADVERTS_ACCOMMODATION_TYPES, true)
    : in_array($type, array('accommodation', 'rent_space'), true);

$needs_gate = function_exists('apollo_safety_applies')
    && apollo_safety_applies($classified_id)
    && function_exists('apollo_adverts_safety_cleared')
    && ! apollo_adverts_safety_cleared($classified_id)
    && is_user_logged_in();

$single_parts = plugin_dir_path(__FILE__) . 'parts/single/';

ob_start();
if (is_readable($single_parts . 'styles.php')) {
    require $single_parts . 'styles.php';
}
$mk_head = ob_get_clean();

if ($needs_gate) {
    wp_enqueue_style('apollo-adverts-safety-gate');
    wp_enqueue_script('apollo-adverts-safety-gate');
}

$mk_use_plus = function_exists('apollo_plus_open');

if ($mk_use_plus) {
    apollo_plus_open(
        array(
            'title'      => get_the_title($classified_id) . ' — Apollo::Rio',
            'extra_head' => $mk_head,
            'screen'     => 'anuncio',
        )
    );
} elseif (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => get_the_title($classified_id) . ' — Apollo::Rio',
            'extra_head' => $mk_head,
        )
    );
    echo '</head><body><main class="ax-main">';
} else {
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $mk_head;
    echo '</head><body><main>';
}

if (is_readable($single_parts . 'layout.php')) {
    require $single_parts . 'layout.php';
}

if ($mk_use_plus) {
    apollo_plus_close();
} elseif (function_exists('apollo_render_document_close')) {
    echo '</main>';
    apollo_render_document_close();
} else {
    echo '</main></body></html>';
}
