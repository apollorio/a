<?php

/**
 * New Home — Events Section
 *
 * WP_Query loop on `event` CPT, ordered by _event_start_date.
 * Month dropdown populated in JS (current + next 2 months).
 * Cards use `.a-eve-card` class (Apollo event card standard).
 * Falls back to "Em breve.." if no events published.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$events_query = new WP_Query(
    array(
        'post_type'      => 'event',
        'posts_per_page' => 7,
        'post_status'    => 'publish',
        'meta_key'       => '_event_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
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

// PT-BR month names for display
$months_pt = array(
    1  => 'jan',
    2  => 'fev',
    3  => 'mar',
    4  => 'abr',
    5  => 'mai',
    6  => 'jun',
    7  => 'jul',
    8  => 'ago',
    9  => 'set',
    10 => 'out',
    11 => 'nov',
    12 => 'dez',
);

$months_full = array(
    1  => 'Janeiro',
    2  => 'Fevereiro',
    3  => 'Março',
    4  => 'Abril',
    5  => 'Maio',
    6  => 'Junho',
    7  => 'Julho',
    8  => 'Agosto',
    9  => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro',
);

$current_month_num  = (int) current_time('n');
$current_month_name = isset($months_full[$current_month_num]) ? $months_full[$current_month_num] : current_time('F');

// Days of the week abbreviations
$days_pt = array(
    'Mon' => 'Seg',
    'Tue' => 'Ter',
    'Wed' => 'Qua',
    'Thu' => 'Qui',
    'Fri' => 'Sex',
    'Sat' => 'Sáb',
    'Sun' => 'Dom',
);
?>
<section class="section" id="events" aria-labelledby="events-title">
    <div class="container">
        <div class="nh-section-head ai">
            <span class="grouped-left">
                <h2 id="events-title" split-chars style="font-size: clamp(2rem, 5.5vw, 4rem) !important;">Events</h2>
                <div class="nh-month-dropdown">
                    <button id="nhMonthTrigger" class="nh-month-trigger"
                        aria-label="<?php esc_attr_e('Selecionar mês', 'apollo-templates'); ?>">
                        <span class="nh-month-text"><?php echo esc_html($current_month_name); ?></span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </button>
                    <ul id="nhMonthMenu" class="nh-month-row"></ul>
                </div>
            </span>
            <a href="<?php echo esc_url(home_url('/eventos')); ?>"
                aria-label="<?php esc_attr_e('Ver todos os eventos', 'apollo-templates'); ?>">Ver Todos →</a>
        </div>

        <div class="nh-events-grid">

            <?php if ($events_query->have_posts()) : ?>
            <?php
                while ($events_query->have_posts()) :
                    $events_query->the_post();
                ?>
            <?php
                    $event_id    = get_the_ID();
                    $permalink   = get_permalink();
                    $start_date  = get_post_meta($event_id, '_event_start_date', true);
                    $start_time  = get_post_meta($event_id, '_event_start_time', true);
                    $loc_id      = get_post_meta($event_id, '_event_loc_id', true);
                    $dj_ids      = get_post_meta($event_id, '_event_dj_ids', true);
                    $status      = get_post_meta($event_id, '_event_status', true);
                    $thumb_url   = get_the_post_thumbnail_url($event_id, 'medium_large');
                    $placeholder = 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=600&q=75';
                    $image       = $thumb_url ? $thumb_url : $placeholder;

                    // Parse date
                    $day        = $start_date ? date_i18n('j', strtotime($start_date)) : '—';
                    $month_num  = $start_date ? (int) date('n', strtotime($start_date)) : $current_month_num;
                    $month_abbr = isset($months_pt[$month_num]) ? $months_pt[$month_num] : 'set';

                    // Location info
                    $loc_name = '';
                    $loc_area = '';
                    if ($loc_id) {
                        $loc_post = get_post($loc_id);
                        if ($loc_post) {
                            $loc_name = $loc_post->post_title;
                            $loc_area = get_post_meta($loc_id, '_local_address', true);
                            if (! $loc_area) {
                                $areas    = wp_get_post_terms($loc_id, 'local_area', array('fields' => 'names'));
                                $loc_area = ! is_wp_error($areas) && ! empty($areas) ? $areas[0] : '';
                            }
                        }
                    }
                    $location_text = $loc_name;
                    if ($loc_area) {
                        $location_text .= ' · ' . $loc_area;
                    }

                    // DJs lineup
                    $dj_names = array();
                    if (is_array($dj_ids) && ! empty($dj_ids)) {
                        foreach (array_slice($dj_ids, 0, 3) as $dj_id) {
                            $dj_post = get_post(absint($dj_id));
                            if ($dj_post) {
                                $dj_names[] = $dj_post->post_title;
                            }
                        }
                    }
                    $lineup_text = ! empty($dj_names) ? implode(', ', $dj_names) : '';

                    // Sound taxonomy (genres)
                    $sounds      = wp_get_post_terms($event_id, 'sound', array('fields' => 'names'));
                    $sounds      = is_wp_error($sounds) ? array() : $sounds;
                    $genres_text = ! empty($sounds) ? implode(', ', array_slice($sounds, 0, 3)) : '—';
                    $lineup_meta = ! empty($lineup_text) ? $lineup_text : 'Line-up a confirmar';

                    // .a-eve-tags — design-system contract: up to 2 genre pills
                    // (see eveCardHTML() in app-portal-eventos.js). Cancelled
                    // status rides along as an extra tag when applicable.
                    $tags = array();
                    if ($status === 'cancelled') {
                        $tags[] = 'Cancelado';
                    }
                    foreach (array_slice($sounds, 0, 2) as $sound_tag) {
                        $tags[] = $sound_tag;
                    }

                    // Guest → event-page RIGHT | Logged → permalink
                    $is_guest = ! is_user_logged_in();
                    ?>
            <!-- Card structure = verbatim contract from eveCardHTML() in
                 app-portal-eventos.js: <article class="app-market eve"> wraps
                 <a class="a-eve-card"> > .a-eve-date + .a-eve-media + .a-eve-content
                 with the three .a-eve-meta rows (line-up · venue · genres), in
                 that order. reveal-up/ai are Apollo-side additions (scroll reveal),
                 not part of the mockup's own SSR. -->
            <article class="app-market eve" data-listing="<?php echo esc_attr($event_id); ?>">
            <?php
            /* The card is ALWAYS a real link to the event. `data-casa-event`
               upgrades it in place to the lightbox cell (cells/lightbox-event.php)
               and the handler calls preventDefault() — so with JS the visitor
               gets the reader, without it they get the page.

               It previously read `data-to="event-page" … href="#"`. Nothing in
               the repository binds `data-to`, and `ApolloSlider` — which the six
               panel-*.php files call — is defined nowhere. page-home.php includes
               no panel. Since mural-router.php 302s every logged-in visitor to
               /feed, that guest branch was 100% of /casa traffic: the primary CTA
               of this section scrolled you to the top of the page and did nothing
               else. See _inventory/CASA-MAP-2026-08-08.md. */
            ?>
            <a href="<?php echo esc_url($permalink); ?>"
                <?php if ($is_guest) : ?>data-casa-event="<?php echo esc_attr($event_id); ?>"<?php endif; ?>
                class="a-eve-card reveal-up ai" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                <div class="a-eve-date">
                    <span class="a-eve-date-day"><?php echo esc_html($day); ?></span>
                    <span class="a-eve-date-month"><?php echo esc_html($month_abbr); ?></span>
                </div>
                <div class="a-eve-media">
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" decoding="async" />
                    <?php if (! empty($tags)) : ?>
                    <div class="a-eve-tags">
                        <?php foreach ($tags as $tag) : ?>
                        <span class="a-eve-tag"><?php echo esc_html($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="a-eve-content">
                    <h2 class="a-eve-title"><?php the_title(); ?></h2>
                    <p class="a-eve-meta"><i class="ri-sound-module-fill" aria-hidden="true"></i><span><?php echo esc_html($lineup_meta); ?></span></p>
                    <p class="a-eve-meta"><i class="ri-map-pin-2-line" aria-hidden="true"></i><span><?php echo esc_html($location_text ? $location_text : 'Local a anunciar'); ?></span></p>
                    <p class="a-eve-meta"><i class="ri-music-2-line" aria-hidden="true"></i><span><?php echo esc_html($genres_text); ?></span></p>
                </div>
            </a>
            </article>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
            <?php else : ?>
            <!-- Em breve — no events published yet -->
            <div class="nh-empty-state ai" style="grid-column:1/-1;">
                <i class="ri-calendar-line" aria-hidden="true"></i>
                <p>Em breve..</p>
                <span class="nh-empty-sub">Novos eventos sendo confirmados para os próximos dias.</span>
            </div>
            <?php endif; ?>

            <!-- Explore CTA card (always shown) -->
            <a href="<?php echo esc_url(home_url('/eventos')); ?>" class="a-eve-explore reveal-up ai"
                aria-label="<?php esc_attr_e('Ver todos os eventos', 'apollo-templates'); ?>">
                <div class="xp-inner">
                    <i class="ri-arrow-right-up-line xp-icon" aria-hidden="true"></i>
                    <span class="xp-all">ALL</span>
                    <span class="xp-label">Eventos</span>
                </div>
            </a>

        </div><!-- /.nh-events-grid -->
    </div>
</section>