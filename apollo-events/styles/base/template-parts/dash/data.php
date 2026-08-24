<?php

/**
 * Meus Eventos — real data provider (PHASE 007).
 *
 * Every aggregate below is computed from real event meta/taxonomies per
 * data-registry.json (event.ticket_status → _event_ticket_status,
 * event.season → season taxonomy, event.genres → sound taxonomy,
 * event.dj_ids → _event_dj_ids, event.loc_id → _event_loc_id, coauthors →
 * apollo_get_coauthors()) — nothing here is simulated.data.js/
 * extra-events-simulation.js fixture data. Base event list is fetched via
 * the SAME tested function dashboard-event.php already uses
 * (apollo_event_get_user_manageable_events()), not a duplicated query.
 *
 * @package Apollo\Event
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_event_dash_get_meus_data')) {
    /**
     * @return array<string, mixed>
     */
    function apollo_event_dash_get_meus_data(int $user_id): array
    {
        $base = function_exists('apollo_event_get_user_manageable_events')
            ? apollo_event_get_user_manageable_events($user_id, 100)
            : array();

        $ticket_labels = array(
            'free'         => __('Gratuito', 'apollo-event'),
            'available'    => __('Disponível', 'apollo-event'),
            'soldout_soon' => __('Sold-out soon', 'apollo-event'),
            'sold_out'     => __('Sold-out', 'apollo-event'),
        );

        $events           = array();
        $ticket_buckets   = array_fill_keys(array_keys($ticket_labels), 0);
        $season_buckets   = array();
        $genre_buckets    = array();
        $month_buckets    = array();
        $dj_counts        = array();
        $venue_counts     = array();
        $coauthor_counts  = array();
        $readiness_totals = array(
            'banner'   => 0,
            'video'    => 0,
            'audio'    => 0,
            'tickets'  => 0,
            'coupon'   => 0,
            'lineup'   => 0,
        );
        $today = current_time('Y-m-d');

        // Next 7 months (from the current month), pre-seeded so empty
        // months still show a zero bar instead of just vanishing.
        $cursor = strtotime(current_time('Y-m-01'));
        for ($i = 0; $i < 7; $i++) {
            $key = date_i18n('Y-m', strtotime('+' . $i . ' months', $cursor));
            $month_buckets[$key] = array(
                'label' => date_i18n('M/y', strtotime('+' . $i . ' months', $cursor)),
                'count' => 0,
            );
        }

        foreach ($base as $ev) {
            $eid = (int) $ev['id'];

            $ticket_status = (string) get_post_meta($eid, '_event_ticket_status', true);
            if (! isset($ticket_buckets[$ticket_status])) {
                $ticket_status = $ticket_status ?: 'available';
                if (! isset($ticket_buckets[$ticket_status])) {
                    $ticket_status = 'available';
                }
            }
            $ticket_buckets[$ticket_status]++;

            $season_terms = defined('APOLLO_EVENT_TAX_SEASON')
                ? wp_get_post_terms($eid, APOLLO_EVENT_TAX_SEASON, array('fields' => 'names'))
                : array();
            if (! is_wp_error($season_terms)) {
                foreach ($season_terms as $sname) {
                    $season_buckets[$sname] = ($season_buckets[$sname] ?? 0) + 1;
                }
            }

            $genre_terms = defined('APOLLO_EVENT_TAX_SOUND')
                ? wp_get_post_terms($eid, APOLLO_EVENT_TAX_SOUND, array('fields' => 'names'))
                : array();
            if (! is_wp_error($genre_terms)) {
                foreach ($genre_terms as $gname) {
                    $genre_buckets[$gname] = ($genre_buckets[$gname] ?? 0) + 1;
                }
            }

            $start = (string) $ev['start_date'];
            if ($start) {
                $mkey = date_i18n('Y-m', strtotime($start));
                if (isset($month_buckets[$mkey])) {
                    $month_buckets[$mkey]['count']++;
                }
            }

            $dj_ids = get_post_meta($eid, '_event_dj_ids', true);
            $dj_ids = is_array($dj_ids) ? array_map('absint', $dj_ids) : array();
            foreach ($dj_ids as $djid) {
                if (! $djid) {
                    continue;
                }
                if (! isset($dj_counts[$djid])) {
                    $dj_counts[$djid] = array('name' => get_the_title($djid), 'count' => 0);
                }
                $dj_counts[$djid]['count']++;
            }

            $loc_name = (string) $ev['loc_name'];
            if ($loc_name) {
                $venue_counts[$loc_name] = ($venue_counts[$loc_name] ?? 0) + 1;
            }

            foreach ((array) $ev['coauthors'] as $ca) {
                $name = (string) ($ca['name'] ?? '');
                if ($name) {
                    $coauthor_counts[$name] = ($coauthor_counts[$name] ?? 0) + 1;
                }
            }

            $banner_id   = (int) get_post_meta($eid, '_event_banner', true);
            $video_url   = (string) get_post_meta($eid, '_event_video_url', true);
            $audio_url   = (string) get_post_meta($eid, '_event_audio_url', true);
            $ticket_url  = (string) get_post_meta($eid, '_event_ticket_url', true);
            /*
             * _event_access_buttons is stored as a real ARRAY by
             * EventsController::sanitize_access_buttons(). Casting it to string
             * raised "Array to string conversion" on every dashboard render.
             * Legacy rows may still hold a JSON string, so handle both shapes.
             */
            $access_raw  = get_post_meta($eid, '_event_access_buttons', true);
            $access_list = array();
            if (is_array($access_raw)) {
                $access_list = $access_raw;
            } elseif (is_string($access_raw) && '' !== trim($access_raw)) {
                $decoded     = json_decode($access_raw, true);
                $access_list = is_array($decoded) ? $decoded : array();
            }
            $has_tickets = ('' !== $ticket_url) || ! empty($access_list);
            $coupon      = (string) get_post_meta($eid, '_event_coupon_code', true);

            $readiness_totals['banner']  += $banner_id ? 1 : 0;
            $readiness_totals['video']   += $video_url ? 1 : 0;
            $readiness_totals['audio']   += $audio_url ? 1 : 0;
            $readiness_totals['tickets'] += $has_tickets ? 1 : 0;
            $readiness_totals['coupon']  += $coupon ? 1 : 0;
            $readiness_totals['lineup']  += ! empty($dj_ids) ? 1 : 0;

            $events[] = array_merge(
                $ev,
                array(
                    'ticket_status' => $ticket_status,
                    'season'        => $season_terms && ! is_wp_error($season_terms) ? ($season_terms[0] ?? '') : '',
                    'dj_count'      => count($dj_ids),
                    'is_upcoming'   => $start >= $today,
                )
            );
        }

        $total = count($events);

        $upcoming = array_values(array_filter($events, static function ($e) {
            return ! empty($e['is_upcoming']);
        }));
        usort($upcoming, static function ($a, $b) {
            return strcmp((string) $a['start_date'], (string) $b['start_date']);
        });

        arsort($dj_counts);
        arsort($venue_counts);
        arsort($coauthor_counts);
        arsort($season_buckets);
        arsort($genre_buckets);

        $readiness_pct = array();
        foreach ($readiness_totals as $key => $n) {
            $readiness_pct[$key] = $total > 0 ? (int) round(($n / $total) * 100) : 0;
        }

        return array(
            'total'           => $total,
            'published'       => count(array_filter($events, static function ($e) {
                return 'publish' === $e['status'];
            })),
            'draft'           => count(array_filter($events, static function ($e) {
                return 'draft' === $e['status'];
            })),
            'upcoming_count'  => count($upcoming),
            'next_event'      => $upcoming[0] ?? null,
            'ticket_labels'   => $ticket_labels,
            'ticket_buckets'  => $ticket_buckets,
            'season_buckets'  => array_slice($season_buckets, 0, 6, true),
            'genre_buckets'   => array_slice($genre_buckets, 0, 8, true),
            'month_buckets'   => $month_buckets,
            'dj_leaderboard'  => array_slice($dj_counts, 0, 6, true),
            'venue_leaderboard' => array_slice($venue_counts, 0, 6, true),
            'coauthor_leaderboard' => array_slice($coauthor_counts, 0, 6, true),
            'readiness'       => $readiness_pct,
            'upcoming_table'  => array_slice($upcoming, 0, 10),
            'events'          => $events,
        );
    }
}
