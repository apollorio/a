<?php

/**
 * New Home — Marquee Ticker (Real Data)
 *
 * Animated horizontal ticker strip — news/journal titles ONLY
 * (post type: post). Each item is always: newspaper icon + DD/MM
 * publish date + title.
 *
 * Duplicate set for seamless CSS infinite scroll animation.
 * Cached via transient (5 min) to avoid per-request queries.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Build marquee items from real news/journal posts only.
 * Uses transient cache for performance.
 */
function apollo_build_marquee_items()
{
    $cache_key = 'apollo_marquee_items_v6';
    $cached    = get_transient($cache_key);
    if (false !== $cached) {
        return $cached;
    }

    $items = array();

    // ── Latest News / Journal posts (only source) ──
    $news_q = new WP_Query(array(
        'post_type'      => 'post',
        'posts_per_page' => 12,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ));
    if ($news_q->have_posts()) {
        while ($news_q->have_posts()) {
            $news_q->the_post();
            $title = trim(wp_strip_all_tags(get_the_title()));
            /* Demo / seed posts destroy editorial calm under the hero. */
            if ($title === '' || preg_match('/^hello\s*world!?$/iu', $title)) {
                continue;
            }
            $items[] = array(
                'type'  => 'news',
                'icon'  => 'ri-newspaper-line',
                'date'  => get_the_date('d/m'),
                'title' => wp_trim_words($title, 8, '…'),
                // Clicking a ticker item opens that exact post. News is public
                // (post type `post`), so no auth gate here — unlike tracks /
                // resell / accommodation, which route through the hub lightbox.
                'url'   => get_permalink(),
            );
        }
        wp_reset_postdata();
    }

    // ── Fallback if no real news posts exist yet — quiet, not spammy ──
    if (empty($items)) {
        $items = array(
            array(
                'type'  => 'news',
                'icon'  => 'ri-newspaper-line',
                'date'  => current_time('d/m'),
                'title' => __('Novas matérias em breve', 'apollo-templates'),
                'url'   => '',
            ),
        );
    }

    // Ensure minimum items for smooth animation (pad with repeats if needed)
    while (count($items) < 6) {
        $items = array_merge($items, $items);
    }
    $items = array_slice($items, 0, 12);

    set_transient($cache_key, $items, 5 * MINUTE_IN_SECONDS);
    return $items;
}

$marquee_items = apply_filters('apollo/home/marquee_items', apollo_build_marquee_items());

/* The ticker prints the set TWICE for a seamless CSS loop. Only the first
   pass is real content: the second is a visual duplicate, so it's marked
   aria-hidden + tabindex="-1" to keep screen readers and keyboard tabbing
   from meeting every headline a second time. (Previously the whole wrapper
   was aria-hidden, which was fine while items were inert text — now that
   each item is a real link to its post, the first pass has to be reachable.) */
?>

<div class="nh-marquee-wrap">
    <div class="nh-marquee-content">
        <?php for ($pass = 0; $pass < 2; $pass++) : ?>
            <?php foreach ($marquee_items as $item) : ?>
                <?php
                $mq_class = 'nh-marquee-item nh-marquee--' . sanitize_html_class($item['type']);
                $mq_url   = ! empty($item['url']) ? $item['url'] : '';
                $mq_inner = '<i class="' . esc_attr($item['icon']) . '"></i>'
                    . '<span class="nh-marquee-date">' . esc_html($item['date']) . '</span>'
                    . '<span class="nh-marquee-title">' . esc_html($item['title']) . '</span>';
                ?>
                <?php if ($mq_url) : ?>
                    <a class="<?php echo esc_attr($mq_class); ?>" href="<?php echo esc_url($mq_url); ?>"
                        <?php echo $pass ? 'aria-hidden="true" tabindex="-1"' : ''; ?>>
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped above.
                        echo $mq_inner;
                        ?>
                    </a>
                <?php else : ?>
                    <div class="<?php echo esc_attr($mq_class); ?>" <?php echo $pass ? 'aria-hidden="true"' : ''; ?>>
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped above.
                        echo $mq_inner;
                        ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>