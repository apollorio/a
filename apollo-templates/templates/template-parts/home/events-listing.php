<?php

/**
 * Apollo Home — Events Listing Section
 *
 * Dynamic events grid pulling from 'event' CPT.
 *
 * @package Apollo\Templates
 * @since   3.0.0
 */

defined('ABSPATH') || exit;

$args     = $args ?? array();
$per_page = $args['per_page'] ?? 16;
$orderby  = $args['orderby'] ?? 'meta_value';
$order    = $args['order'] ?? 'ASC';

// Query future events.
$events_query = new WP_Query(
    array(
        'post_type'      => defined('APOLLO_CPT_EVENT') ? APOLLO_CPT_EVENT : 'event',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
        'meta_key'       => '_event_date',
        'orderby'        => $orderby,
        'order'          => $order,
        'meta_query'     => array(
            array(
                'key'     => '_event_date',
                'value'   => wp_date('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    )
);

// Seasons for filters.
$seasons = array(
    'verao'   => __('Verão', 'apollo-templates'),
    'carna26' => __("Carna'26", 'apollo-templates'),
    'bey26'   => __("Bey'26", 'apollo-templates'),
    'rir26'   => __("RiR'26", 'apollo-templates'),
);

// Genre taxonomy terms.
$genre_terms = get_terms(
    array(
        'taxonomy'   => 'event_genre',
        'hide_empty' => true,
    )
);

if (! defined('APOLLO_CDN_BASE')) {
    define('APOLLO_CDN_BASE', 'https://assets.apollo.rio.br');
}
?>

<section id="events" class="events container" style="padding-left:24px;padding-right:24px; margin-top: 4rem;">

    <div class="events-header reveal-up" style="display:flex;line-height:1.5rem!important; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:20px; margin:170px auto auto;">
        <div class="events-title">
            <h2><?php esc_html_e('Eventos', 'apollo-templates'); ?></h2>
            <p class="events-subtitle" style="color:var(--apollo-gray); line-height:1.5rem!important;">
                <?php esc_html_e('Acompanhe a pulsação da cidade.', 'apollo-templates'); ?>
            </p>
        </div>

        <div class="events-filters" style="display:flex; gap:12px; flex-wrap:wrap;">
            <?php
            // Enqueue composite search
            if (function_exists('apollo_enqueue_composite_search')) {
                apollo_enqueue_composite_search();
            }

            // Composite Search
            if (function_exists('apollo_composite_search')) {
                apollo_composite_search(
                    array(
                        'context'     => 'events',
                        'placeholder' => __('Buscar eventos...', 'apollo-templates'),
                        'class'       => 'events-search-composite',
                    )
                );
            }
            ?>

            <div class="apollo-custom-select" data-type="single" style="position:relative;">
                <div class="apollo-select-trigger"><?php esc_html_e('Temporada', 'apollo-templates'); ?></div>
                <div class="apollo-select-dropdown">
                    <?php foreach ($seasons as $value => $label) : ?>
                        <div class="apollo-select-option" data-value="<?php echo esc_attr($value); ?>">
                            <?php echo esc_html($label); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (! is_wp_error($genre_terms) && ! empty($genre_terms)) : ?>
                <div class="apollo-custom-select" data-type="multi" style="position:relative;">
                    <div class="apollo-select-trigger"><?php esc_html_e('Gêneros', 'apollo-templates'); ?></div>
                    <div class="apollo-select-dropdown">
                        <?php foreach ($genre_terms as $term) : ?>
                            <div class="apollo-select-option" data-value="<?php echo esc_attr($term->slug); ?>">
                                <input type="checkbox" id="genre-<?php echo esc_attr($term->slug); ?>" title="<?php echo esc_attr($term->name); ?>">
                                <label for="genre-<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="apollo-events-grid">
        <?php if ($events_query->have_posts()) : ?>
            <?php
            $delay = 100;
            while ($events_query->have_posts()) :
                $events_query->the_post();

                $event_id    = get_the_ID();
                $event_date  = get_post_meta($event_id, '_event_date', true);
                $event_loc   = get_post_meta($event_id, '_event_local_name', true);
                $ticket_url  = get_post_meta($event_id, '_ticket_url', true);
                $coupon_code = get_post_meta($event_id, '_coupon_code', true) ?: 'APOLLORIO';
                $organizer   = get_post_meta($event_id, '_event_organizer', true);

                $thumbnail  = get_the_post_thumbnail_url($event_id, 'medium_large');
                $genres     = get_the_terms($event_id, 'event_genre');
                $genre_name = ($genres && ! is_wp_error($genres)) ? $genres[0]->name : '';

                $day   = $event_date ? wp_date('d', strtotime($event_date)) : '';
                $month = $event_date ? wp_date('M', strtotime($event_date)) : '';
            ?>

                <a href="<?php echo esc_url($ticket_url ?: get_permalink()); ?>"
                    <?php echo $ticket_url ? 'target="_blank" rel="noopener"' : ''; ?>
                    class="a-eve-card coup-apollo reveal-up delay-<?php echo esc_attr((string) $delay); ?>"
                    data-coupon="<?php echo esc_attr(strtolower($coupon_code)); ?>">

                    <div class="a-eve-date">
                        <span class="a-eve-date-day"><?php echo esc_html($day); ?></span>
                        <span class="a-eve-date-month"><?php echo esc_html($month); ?></span>
                    </div>

                    <div class="a-eve-media">
                        <?php if ($thumbnail) : ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                        <?php else : ?>
                            <img src="<?php echo esc_url(APOLLO_CDN_BASE . '/img/placeholder-event.webp'); ?>" alt="Apollo Event Placeholder" loading="lazy">
                        <?php endif; ?>

                        <?php if ($genre_name) : ?>
                            <div class="a-eve-tags">
                                <span class="a-eve-tag"><?php echo esc_html($genre_name); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="a-eve-content">
                        <h3 class="a-eve-title"><?php the_title(); ?></h3>

                        <?php if ($organizer) : ?>
                            <p class="a-eve-meta">
                                <i class="ri-sound-module-fill"></i>
                                <span><?php echo esc_html($organizer); ?></span>
                            </p>
                        <?php endif; ?>

                        <?php if ($event_loc) : ?>
                            <p class="a-eve-meta">
                                <i class="ri-map-pin-2-line"></i>
                                <span><?php echo esc_html($event_loc); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </a>

            <?php
                $delay = ($delay >= 300) ? 100 : $delay + 100;
            endwhile;
            wp_reset_postdata();
            ?>

        <?php else : ?>
            <div class="empty-state" style="grid-column:1/-1; text-align:center; padding:64px 24px;">
                <i class="ri-calendar-event-line" style="font-size:33px; opacity:0.3; color:var(--apollo-gray);"></i>
                <p style="color:var(--apollo-gray); margin-top:16px; font-size:.8rem;opacity:0.4;">
                    <?php esc_html_e('Nenhum evento encontrado para as próximas datas.', 'apollo-templates'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    (function() {
        // Toggle custom select dropdowns
        document.querySelectorAll('.apollo-select-trigger').forEach(function(trigger) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                var parent = this.closest('.apollo-custom-select');
                if (parent) {
                    document.querySelectorAll('.apollo-custom-select').forEach(function(s) {
                        if (s !== parent) s.classList.remove('open');
                    });
                    parent.classList.toggle('open');
                }
            });
        });

        document.addEventListener('click', function() {
            document.querySelectorAll('.apollo-custom-select').forEach(function(s) {
                s.classList.remove('open');
            });
        });
    })();
</script>