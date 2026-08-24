<?php

/**
 * Mural: Monthly Events Calendar (Apollo v1 Card Design)
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}

// Variables: $upcoming_events, $current_month, $current_year (from page-mural.php)
if (empty($upcoming_events)) {
    return;
}

// Month names in Portuguese
$months_pt = array(
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

// Previous/Next month URLs
$prev_month = $current_month - 1;
$prev_year  = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    --$prev_year;
}

$next_month = $current_month + 1;
$next_year  = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    ++$next_year;
}

$prev_url = add_query_arg(
    array(
        'month' => $prev_month,
        'year'  => $prev_year,
    )
);
$next_url = add_query_arg(
    array(
        'month' => $next_month,
        'year'  => $next_year,
    )
);
?>

<section class="section-upcoming-events">
    <div class="app-container">

        <!-- Month/Year Navigation -->
        <div class="events-month-header">
            <a href="<?php echo esc_url($prev_url); ?>" class="month-nav-btn">
                <i class="ri-arrow-left-s-line"></i>
            </a>
            <h2 class="events-month-title">
                <?php echo esc_html($months_pt[$current_month]); ?> <?php echo esc_html($current_year); ?>
            </h2>
            <a href="<?php echo esc_url($next_url); ?>" class="month-nav-btn">
                <i class="ri-arrow-right-s-line"></i>
            </a>
        </div>

        <!-- Filters (Placeholder for future implementation) -->
        <div class="events-filters">
            <button class="filter-btn active" data-filter="all">
                <i class="ri-list-check"></i> All Events
            </button>
            <button class="filter-btn" data-filter="sounds">
                <i class="ri-music-2-line"></i> My Sounds
            </button>
            <button class="filter-btn" data-filter="category">
                <i class="ri-bookmark-line"></i> Category
            </button>
            <button class="filter-btn" data-filter="season">
                <i class="ri-calendar-line"></i> Season
            </button>
        </div>

    </div>

    <!-- Events Grid (Full Width) -->
    <div class="events-grid-layout">
        <?php
        foreach ($upcoming_events as $event) :
            $banner  = function_exists('apollo_event_get_banner') ? apollo_event_get_banner($event->ID, 'large') : get_the_post_thumbnail_url($event, 'large');
            $ev_date = get_post_meta($event->ID, '_event_start_date', true);
            $ev_time = get_post_meta($event->ID, '_event_start_time', true);
            $loc     = function_exists('apollo_event_get_loc') ? apollo_event_get_loc($event->ID) : null;

            // Format date for display
            $day        = $ev_date ? wp_date('j', strtotime($ev_date)) : '';
            $month_abbr = $ev_date ? strtolower(wp_date('M', strtotime($ev_date))) : '';

            // Get event sounds/genres
            $sounds     = wp_get_post_terms($event->ID, 'sound', array('fields' => 'names'));
            $sounds_str = ! empty($sounds) && ! is_wp_error($sounds) ? implode(', ', $sounds) : '';

            // Get event DJs/artists via helper
            $djs_arr = function_exists('apollo_event_get_djs') ? apollo_event_get_djs($event->ID) : array();
            $djs_str = ! empty($djs_arr) ? implode(', ', wp_list_pluck($djs_arr, 'title')) : '';

            // Get event tags (for top-right badges)
            $tags = wp_get_post_terms($event->ID, 'event_tag', array('number' => 2));
        ?>

            <a href="<?php echo esc_url(get_permalink($event)); ?>" class="a-eve-card">
                <!-- Date Badge (Top Left) -->
                <div class="a-eve-date">
                    <span class="a-eve-date-day"><?php echo esc_html($day); ?></span>
                    <span class="a-eve-date-month"><?php echo esc_html($month_abbr); ?></span>
                </div>

                <!-- Media -->
                <div class="a-eve-media">
                    <img src="<?php echo esc_url($banner); ?>"
                        alt="<?php echo esc_attr($event->post_title); ?>" loading="lazy">

                    <!-- Tags (Top Right) -->
                    <?php if (! empty($tags) && ! is_wp_error($tags)) : ?>
                        <div class="a-eve-tags">
                            <?php foreach ($tags as $tag) : ?>
                                <span class="a-eve-tag"><?php echo esc_html($tag->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="a-eve-content">
                    <h2 class="a-eve-title"><?php echo esc_html($event->post_title); ?></h2>

                    <!-- Sounds (opacity 0.5) -->
                    <?php if ($sounds_str) : ?>
                        <p class="a-eve-meta sounds">
                            <i class="ri-music-2-line"></i>
                            <span><?php echo esc_html($sounds_str); ?></span>
                        </p>
                    <?php endif; ?>

                    <!-- DJs/Artists -->
                    <?php if ($djs_str) : ?>
                        <p class="a-eve-meta">
                            <i class="ri-sound-module-fill"></i>
                            <span><?php echo esc_html($djs_str); ?></span>
                        </p>
                    <?php endif; ?>

                    <!-- Local -->
                    <?php if ($loc) : ?>
                        <p class="a-eve-meta">
                            <i class="ri-map-pin-2-line"></i>
                            <span><?php echo esc_html($loc['title']); ?><?php echo ! empty($loc['city']) ? ', ' . esc_html($loc['city']) : ''; ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            </a>

        <?php endforeach; ?>
    </div>
</section>