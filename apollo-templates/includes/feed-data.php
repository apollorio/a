<?php

/**
 * Feed screen — real WordPress data providers (PHASE 001).
 *
 * The mockup's screens read globals seeded by simulated.data.js. That file is
 * explicitly NOT ported: only its SHAPE is honoured, so the ported structure
 * and scripts keep working while every value comes from the database.
 *
 * Each provider returns [] when its source doesn't exist yet, so a widget
 * renders its empty state rather than inventing content.
 *
 * @package Apollo\Templates
 * @since   1.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_feed_greeting')) {
    /**
     * Time-of-day greeting + place/date line (mockup: FEED_META).
     *
     * @return array{greeting:string,location:string,dateLabel:string}
     */
    function apollo_feed_greeting(): array
    {
        $hour = (int) current_time('G');
        if ($hour < 6) {
            $greet = __('Boa madrugada', 'apollo-templates');
        } elseif ($hour < 12) {
            $greet = __('Bom dia', 'apollo-templates');
        } elseif ($hour < 19) {
            $greet = __('Boa tarde', 'apollo-templates');
        } else {
            $greet = __('Boa noite', 'apollo-templates');
        }

        if (is_user_logged_in()) {
            $first = explode(' ', trim((string) wp_get_current_user()->display_name))[0];
            if ('' !== $first) {
                $greet .= ', ' . $first;
            }
        }

        return array(
            'greeting'  => $greet,
            'location'  => 'Rio de Janeiro',
            'dateLabel' => date_i18n('l, j \d\e F'),
        );
    }
}

if (! function_exists('apollo_feed_news')) {
    /**
     * Sidebar news items (mockup: SIDEBAR_NEWS) — latest `post` entries.
     *
     * @param int $limit Max items.
     * @return array<int, array{cat:string,title:string,time:string,url:string,status:string}>
     */
    function apollo_feed_news(int $limit = 4): array
    {
        $q = new WP_Query(
            array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            )
        );

        $out = array();
        foreach ($q->posts as $p) {
            $cats = get_the_category($p->ID);
            $age  = time() - (int) get_post_time('U', true, $p);
            $out[] = array(
                'cat'    => ! empty($cats) ? $cats[0]->name : __('Cena', 'apollo-templates'),
                'title'  => get_the_title($p),
                'time'   => sprintf(
                    /* translators: %s: human-readable time difference. */
                    __('há %s', 'apollo-templates'),
                    human_time_diff(get_post_time('U', true, $p), time())
                ),
                'url'    => (string) get_permalink($p),
                // "Novo" is derived from real publish time, not authored.
                'status' => $age < DAY_IN_SECONDS ? 'new' : '',
            );
        }
        return $out;
    }
}

if (! function_exists('apollo_feed_events')) {
    /**
     * Sidebar upcoming events (mockup: SIDEBAR_EVENTS).
     *
     * @param int $limit Max items.
     * @return array<int, array{day:string,month:string,title:string,meta:string,url:string}>
     */
    function apollo_feed_events(int $limit = 4): array
    {
        if (! post_type_exists('event')) {
            return array();
        }

        $q = new WP_Query(
            array(
                'post_type'      => 'event',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'meta_key'       => '_event_start_date',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                'no_found_rows'  => true,
                'meta_query'     => array(
                    array(
                        'key'     => '_event_start_date',
                        'value'   => current_time('Y-m-d'),
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ),
                ),
            )
        );

        $out = array();
        foreach ($q->posts as $p) {
            $start = (string) get_post_meta($p->ID, '_event_start_date', true);
            $ts    = $start ? strtotime($start) : 0;
            $loc   = function_exists('apollo_event_get_loc') ? apollo_event_get_loc($p->ID) : null;
            $out[] = array(
                'day'   => $ts ? date_i18n('d', $ts) : '--',
                'month' => $ts ? mb_strtolower(date_i18n('M', $ts)) : '',
                'title' => get_the_title($p),
                'meta'  => (string) ($loc['title'] ?? ''),
                'url'   => (string) get_permalink($p),
            );
        }
        return $out;
    }
}

if (! function_exists('apollo_feed_market')) {
    /**
     * Sidebar marketplace teasers (mockup: SIDEBAR_MARKET).
     *
     * Titles and prices only — never the seller. Guest privacy on advert
     * surfaces is enforced site-wide (see apollo-adverts' seller-privacy
     * pass); this widget must not become the leak.
     *
     * @param int $limit Max items.
     * @return array<int, array{title:string,price:string,url:string}>
     */
    function apollo_feed_market(int $limit = 3): array
    {
        if (! post_type_exists('classified')) {
            return array();
        }

        $q = new WP_Query(
            array(
                'post_type'      => 'classified',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            )
        );

        $out = array();
        foreach ($q->posts as $p) {
            $price = get_post_meta($p->ID, '_classified_price', true);
            $out[] = array(
                'title' => get_the_title($p),
                'price' => $price ? 'R$ ' . number_format_i18n((float) $price, 0) : '—',
                'url'   => (string) get_permalink($p),
            );
        }
        return $out;
    }
}

if (! function_exists('apollo_feed_trending')) {
    /**
     * Trending sound/genre terms (mockup: SIDEBAR_TRENDING) — by real usage.
     *
     * @param int $limit Max terms.
     * @return array<int, array{label:string,count:int,url:string}>
     */
    function apollo_feed_trending(int $limit = 6): array
    {
        if (! taxonomy_exists('sound')) {
            return array();
        }
        $terms = get_terms(
            array(
                'taxonomy'   => 'sound',
                'hide_empty' => true,
                'orderby'    => 'count',
                'order'      => 'DESC',
                'number'     => $limit,
            )
        );
        if (is_wp_error($terms)) {
            return array();
        }

        $out = array();
        foreach ($terms as $t) {
            $out[] = array(
                'label' => $t->name,
                'count' => (int) $t->count,
                'url'   => (string) get_term_link($t),
            );
        }
        return $out;
    }
}

if (! function_exists('apollo_feed_nucleos')) {
    /**
     * Sidebar núcleos / comunas (mockup: SIDEBAR_NUCLEOS).
     *
     * @param int $limit Max items.
     * @return array<int, array{title:string,meta:string,url:string}>
     */
    function apollo_feed_nucleos(int $limit = 4): array
    {
        $type = post_type_exists('nucleo') ? 'nucleo' : (post_type_exists('comuna') ? 'comuna' : '');
        if ('' === $type) {
            return array();
        }

        $q = new WP_Query(
            array(
                'post_type'      => $type,
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            )
        );

        $out = array();
        foreach ($q->posts as $p) {
            $out[] = array(
                'title' => get_the_title($p),
                'meta'  => (string) get_post_meta($p->ID, '_nucleo_role', true),
                'url'   => (string) get_permalink($p),
            );
        }
        return $out;
    }
}

if (! function_exists('apollo_feed_stats')) {
    /**
     * Scene counters (mockup: SIDEBAR_STATS) — real published counts.
     *
     * @return array<int, array{label:string,value:string}>
     */
    function apollo_feed_stats(): array
    {
        $count = static function (string $pt): int {
            if (! post_type_exists($pt)) {
                return 0;
            }
            $c = wp_count_posts($pt);
            return (int) ($c->publish ?? 0);
        };

        $out = array();
        if (post_type_exists('event')) {
            $out[] = array('label' => __('Eventos', 'apollo-templates'), 'value' => number_format_i18n($count('event')));
        }
        if (post_type_exists('dj')) {
            $out[] = array('label' => __('DJs', 'apollo-templates'), 'value' => number_format_i18n($count('dj')));
        }
        if (post_type_exists('local')) {
            $out[] = array('label' => __('Locais', 'apollo-templates'), 'value' => number_format_i18n($count('local')));
        }
        $users = count_users();
        $out[] = array('label' => __('Na cena', 'apollo-templates'), 'value' => number_format_i18n((int) ($users['total_users'] ?? 0)));

        return $out;
    }
}
