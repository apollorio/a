<?php

/**
 * Advert share affordance — one contract across /anuncios, /anuncio/{slug}/, /casa.
 *
 * Opens a small readonly URL field (not navigator.share) so guests can copy a
 * permalink even when seller identity is locked on the card. Single-page detail
 * still applies member-only + safety gate on contact.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Markup for the share icon button.
 *
 * @param string               $url  Canonical permalink (classified or local).
 * @param array<string, mixed> $args Optional class + label overrides.
 */
function apollo_adverts_share_button(string $url, array $args = array()): string
{
    $url = esc_url($url);
    if ($url === '') {
        return '';
    }

    $label = isset($args['label']) && is_string($args['label'])
        ? $args['label']
        : __('Compartilhar anúncio', 'apollo-adverts');

    $class = isset($args['class']) && is_string($args['class'])
        ? $args['class']
        : 'ap-advert-share';

    return sprintf(
        '<button type="button" class="%1$s" data-advert-share="%2$s" aria-label="%3$s" aria-expanded="false" title="%3$s">' .
        '<i class="ri-share-forward-line" aria-hidden="true"></i></button>',
        esc_attr($class),
        $url,
        esc_attr($label)
    );
}

/**
 * Register share assets (idempotent).
 */
function apollo_adverts_share_register_assets(): void
{
    $ver = defined('APOLLO_ADVERTS_VERSION') ? APOLLO_ADVERTS_VERSION : null;

    wp_register_style(
        'apollo-adverts-share',
        APOLLO_ADVERTS_URL . 'assets/css/share-advert.css',
        array(),
        $ver
    );

    wp_register_script(
        'apollo-adverts-share',
        APOLLO_ADVERTS_URL . 'assets/js/share-advert.js',
        array(),
        $ver,
        true
    );
}
add_action('wp_enqueue_scripts', 'apollo_adverts_share_register_assets', 4);

/**
 * Enqueue share script + styles when a surface renders share buttons.
 */
function apollo_adverts_enqueue_share_assets(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    wp_enqueue_style('apollo-adverts-share');
    wp_enqueue_script('apollo-adverts-share');
}
