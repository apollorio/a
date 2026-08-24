<?php

/**
 * Mapa screen — real WordPress data provider (PHASE 006).
 *
 * Sources events + loc CPT posts from apollo-maps' own tested REST logic
 * (dispatched in-process via rest_do_request(), not an HTTP round-trip, and
 * not a duplicated WP_Query) rather than any fixture. Per the phase's
 * "real data only" decision, the mockup's static "Perto de você" layer
 * (parks/metro/beaches/museums/airports/stadiums + demo nightlife venues,
 * from data/map-places.json + data/map-filters.json) is deliberately NOT
 * ported: no Apollo plugin/CPT backs that content, so it stays out rather
 * than shipping invented or copied fixture data.
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_mapa_get_places')) {
    /**
     * Real map places: upcoming events with loc coordinates + standalone
     * loc CPT posts, shaped into the one record type the mapa explorer's
     * JS renders (marker, card, rail, list) — no separate "type" system
     * needed beyond the two real categories, evento and local.
     *
     * @return array<int, array<string, mixed>>
     */
    function apollo_mapa_get_places(): array
    {
        static $cache = null;
        if (null !== $cache) {
            return $cache;
        }

        $places = array();

        if (! function_exists('rest_do_request') || ! defined('APOLLO_MAPS_REST_NAMESPACE')) {
            return $cache = $places;
        }

        $request  = new WP_REST_Request('GET', '/' . APOLLO_MAPS_REST_NAMESPACE . '/map/explorer');
        $request->set_param('per_page', 150);
        $request->set_param('upcoming', 1);
        $request->set_param('with_locs', 1);
        $response = rest_do_request($request);

        if ($response->is_error()) {
            return $cache = $places;
        }

        $data = $response->get_data();

        foreach ((array) ($data['events'] ?? array()) as $ev) {
            if (empty($ev['lat']) && 0.0 !== (float) ($ev['lat'] ?? null)) {
                continue;
            }
            $loc      = (array) ($ev['loc'] ?? array());
            $when_raw = (string) ($ev['when'] ?? '');
            $places[] = array(
                'id'       => 'event-' . (int) $ev['id'],
                'name'     => (string) ($ev['title'] ?? ''),
                'shortRef' => (string) ($loc['title'] ?? ''),
                'category' => __('Evento', 'apollo-templates'),
                'hours'    => $when_raw ? apollo_mapa_format_when($when_raw) : '',
                'days'     => '',
                'image'    => apollo_mapa_event_image((int) $ev['id']),
                'location' => array(
                    'lat' => (float) $ev['lat'],
                    'lng' => (float) $ev['lng'],
                ),
                'type'     => 'evento',
                'url'      => (string) ($ev['link'] ?? ''),
            );
        }

        foreach ((array) ($data['locs'] ?? array()) as $loc) {
            if (empty($loc)) {
                continue;
            }
            $places[] = array(
                'id'       => 'local-' . (int) $loc['id'],
                'name'     => (string) ($loc['title'] ?? ''),
                'shortRef' => (string) ($loc['address'] ?? ''),
                'category' => __('Local', 'apollo-templates'),
                'hours'    => '',
                'days'     => '',
                'image'    => apollo_mapa_loc_image((int) $loc['id']),
                'location' => array(
                    'lat' => (float) $loc['lat'],
                    'lng' => (float) $loc['lng'],
                ),
                'type'     => 'local',
                'url'      => (string) ($loc['link'] ?? ''),
            );
        }

        return $cache = $places;
    }
}

if (! function_exists('apollo_mapa_format_when')) {
    /**
     * "2026-08-14 22:00" → "14/08 · 22:00" (falls back to the raw string on
     * an unparsable date rather than guessing).
     */
    function apollo_mapa_format_when(string $when): string
    {
        $ts = strtotime($when);
        if (! $ts) {
            return $when;
        }
        $time = date_i18n('H:i', $ts);
        return date_i18n('d/m', $ts) . ('00:00' !== $time ? ' · ' . $time : '');
    }
}

if (! function_exists('apollo_mapa_event_image')) {
    /**
     * Real event cover (_event_banner — attachment ID or URL, same dual
     * convention as loc's gallery meta), falling back to the ecosystem's
     * canonical placeholder (never a stock photo).
     */
    function apollo_mapa_event_image(int $event_id): string
    {
        $raw = get_post_meta($event_id, '_event_banner', true);
        return apollo_mapa_resolve_image($raw);
    }
}

if (! function_exists('apollo_mapa_loc_image')) {
    /**
     * Real loc gallery image (_local_image_1, same source single-local.php
     * uses for its hero), falling back to the canonical placeholder.
     */
    function apollo_mapa_loc_image(int $loc_id): string
    {
        $raw = get_post_meta($loc_id, '_local_image_1', true);
        return apollo_mapa_resolve_image($raw);
    }
}

if (! function_exists('apollo_mapa_resolve_image')) {
    /**
     * Shared attachment-ID-or-URL resolver (mirrors apollo-loc's
     * GalleryMetabox / single-local.php convention) with the ecosystem's
     * real placeholder as the only fallback — never a third-party stock
     * photo.
     */
    function apollo_mapa_resolve_image($raw): string
    {
        $placeholder = 'https://assets.apollo.rio.br/img/bg/grain-001.jpg';
        if (empty($raw)) {
            return $placeholder;
        }
        if (is_numeric($raw)) {
            $url = wp_get_attachment_image_url((int) $raw, 'medium_large');
            return $url ?: $placeholder;
        }
        return (string) $raw;
    }
}
