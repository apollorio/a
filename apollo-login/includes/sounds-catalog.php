<?php

/**
 * Canonical sounds catalog from doc.apollo.rio.br JSON.
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login;

if (! defined('ABSPATH')) {
    exit;
}

const APOLLO_SOUNDS_JSON_URL     = 'https://doc.apollo.rio.br/json/sounds.json';
const APOLLO_SOUNDS_CACHE_KEY    = 'apollo_login_sounds_catalog';
const APOLLO_SOUNDS_CACHE_TTL    = 12 * HOUR_IN_SECONDS;
const APOLLO_SOUNDS_ALLOWED_GRPS = array('underground', 'both', 'mainstream');

/**
 * Fetch and normalize the sounds catalog.
 *
 * @return array<int, array{slug: string, label: string, group: string}>
 */
function apollo_login_get_sounds_catalog(): array
{
    $cached = get_transient(APOLLO_SOUNDS_CACHE_KEY);
    if (is_array($cached) && ! empty($cached)) {
        return $cached;
    }

    $catalog = apollo_login_fetch_sounds_catalog_from_remote();
    if (empty($catalog)) {
        $catalog = apollo_login_sounds_catalog_taxonomy_fallback();
    }

    if (! empty($catalog)) {
        set_transient(APOLLO_SOUNDS_CACHE_KEY, $catalog, APOLLO_SOUNDS_CACHE_TTL);
    }

    return $catalog;
}

/**
 * @return array<int, array{slug: string, label: string, group: string}>
 */
function apollo_login_fetch_sounds_catalog_from_remote(): array
{
    $response = wp_remote_get(
        APOLLO_SOUNDS_JSON_URL,
        array(
            'timeout' => 8,
            'headers' => array('Accept' => 'application/json'),
        )
    );

    if (is_wp_error($response)) {
        return array();
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return array();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (! is_array($data) || empty($data['sounds']) || ! is_array($data['sounds'])) {
        return array();
    }

    return apollo_login_normalize_sounds_entries($data['sounds']);
}

/**
 * @param array<int, mixed> $entries Raw JSON sound rows.
 * @return array<int, array{slug: string, label: string, group: string}>
 */
function apollo_login_normalize_sounds_entries(array $entries): array
{
    $catalog  = array();
    $seen     = array();

    foreach ($entries as $entry) {
        if (! is_array($entry)) {
            continue;
        }

        $label = isset($entry['genre']) ? sanitize_text_field((string) $entry['genre']) : '';
        $group = isset($entry['group']) ? strtolower(sanitize_text_field((string) $entry['group'])) : '';

        if ($label === '' || ! in_array($group, APOLLO_SOUNDS_ALLOWED_GRPS, true)) {
            continue;
        }

        $slug = sanitize_title($label);
        if ($slug === '' || isset($seen[$slug])) {
            continue;
        }

        $seen[$slug] = true;
        $catalog[]   = array(
            'slug'  => $slug,
            'label' => $label,
            'group' => $group,
        );
    }

    return $catalog;
}

/**
 * Taxonomy fallback when remote JSON is unavailable.
 *
 * @return array<int, array{slug: string, label: string, group: string}>
 */
function apollo_login_sounds_catalog_taxonomy_fallback(): array
{
    $flat = array();

    if (taxonomy_exists('sound')) {
        $terms = get_terms(
            array(
                'taxonomy'   => 'sound',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );

        if (! is_wp_error($terms) && ! empty($terms)) {
            foreach ($terms as $term) {
                $flat[ $term->slug ] = $term->name;
            }
        }
    }

    if (empty($flat)) {
        $flat = apply_filters(
            'apollo_registration_sounds',
            array(
                'techno'     => 'Techno',
                'house'      => 'House',
                'psytrance'  => 'Psytrance',
                'drum-bass'  => 'Drum & Bass',
                'funk'       => 'Funk',
            )
        );
    }

    $catalog = array();
    foreach ($flat as $slug => $label) {
        $catalog[] = array(
            'slug'  => sanitize_title((string) $slug),
            'label' => sanitize_text_field((string) $label),
            'group' => 'both',
        );
    }

    return $catalog;
}

/**
 * Flat slug => label map (legacy CONFIG.availableSounds).
 *
 * @return array<string, string>
 */
function apollo_login_sounds_catalog_flat(): array
{
    $flat = array();
    foreach (apollo_login_get_sounds_catalog() as $item) {
        $flat[ $item['slug'] ] = $item['label'];
    }
    return $flat;
}

/**
 * Valid slugs for registration validation.
 *
 * @return string[]
 */
function apollo_login_sounds_catalog_slugs(): array
{
    return array_map(
        static fn(array $item): string => $item['slug'],
        apollo_login_get_sounds_catalog()
    );
}
