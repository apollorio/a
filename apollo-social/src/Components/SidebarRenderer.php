<?php
/**
 * Sidebar Renderer — Render sidebar cards (news, events, trending, market)
 *
 * @package Apollo\Social
 * @version 6.5.0
 */

namespace Apollo\Social\Components;

if (! defined('ABSPATH')) {
    exit;
}

class SidebarRenderer {
    /**
     * Render complete sidebar
     */
    public static function render_sidebar(): string {
        ob_start();
        ?>
        <aside class="sidebar-column">
            <?php echo self::render_news(); ?>
            <?php echo self::render_events(); ?>
            <?php echo self::render_trending(); ?>
            <?php echo self::render_market(); ?>
        </aside>
        <?php
        return ob_get_clean();
    }

    /**
     * Render news card
     */
    private static function render_news(): string {
        $posts = get_posts([
            'numberposts' => 5,
            'post_status' => 'publish',
            'post_type' => 'post',
        ]);

        if (empty($posts)) {
            return '';
        }

        ob_start();
        ?>
        <div class="sb-card">
            <div class="sb-title"><?php esc_html_e('Notícias', 'apollo-social'); ?></div>
            <?php foreach($posts as $post) : ?>
                <div class="sb-news-item" data-post-id="<?php echo absint($post->ID); ?>">
                    <div class="news-cat"><?php echo esc_html(get_post_type_object($post->post_type)->labels->singular_name); ?></div>
                    <div class="news-title"><?php echo esc_html(wp_trim_words($post->post_title, 8)); ?></div>
                    <div class="news-time"><?php echo esc_html(human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' atrás'); ?></div>
                </div>
            <?php endforeach; ?>
            <span class="sb-see-more"><?php esc_html_e('Ver mais', 'apollo-social'); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render upcoming events
     */
    private static function render_events(): string {
        $events = get_posts([
            'numberposts' => 4,
            'post_status' => 'publish',
            'post_type' => 'event',
            'meta_key' => '_event_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ]);

        if (empty($events)) {
            return '';
        }

        ob_start();
        ?>
        <div class="sb-card">
            <div class="sb-title"><?php esc_html_e('Próximos Eventos', 'apollo-social'); ?></div>
            <?php foreach($events as $event) : ?>
                <?php
                $event_date = get_post_meta($event->ID, '_event_date', true);
                $event_loc = get_post_meta($event->ID, '_event_loc_name', true);
                $date = new \DateTime($event_date);
                ?>
                <div class="sb-ev" data-event-id="<?php echo absint($event->ID); ?>">
                    <div class="sbe-date">
                        <div class="dn"><?php echo esc_html(strtoupper($date->format('M'))); ?></div>
                        <div class="dd"><?php echo esc_html($date->format('d')); ?></div>
                    </div>
                    <div class="sbe-info">
                        <div class="sbe-name"><?php echo esc_html(wp_trim_words($event->post_title, 6)); ?></div>
                        <div class="sbe-sub"><i class="ri-map-pin-line"></i> <?php echo esc_html($event_loc); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render trending sounds/tracks
     */
    private static function render_trending(): string {
        // Fetch from music taxonomy or meta
        $trending = apply_filters('apollo/social/trending_tracks', []);

        if (empty($trending)) {
            return '';
        }

        ob_start();
        ?>
        <div class="sb-card">
            <div class="sb-title"><?php esc_html_e('Em Alta', 'apollo-social'); ?></div>
            <?php $i = 1; foreach(array_slice($trending, 0, 5) as $track) : ?>
                <div class="sb-trending-track">
                    <div class="sbt-num"><?php echo esc_html($i++); ?></div>
                    <div class="sbt-art">
                        <img src="<?php echo esc_url($track['artwork'] ?? ''); ?>" alt="<?php echo esc_attr($track['title']); ?>">
                    </div>
                    <div class="sbt-info">
                        <div class="sbt-name"><?php echo esc_html($track['title']); ?></div>
                        <div class="sbt-artist"><?php echo esc_html($track['artist']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render market/classifieds sidebar
     */
    private static function render_market(): string {
        $classifieds_count = wp_count_posts('classified')->publish ?? 0;
        $featured = get_posts([
            'numberposts' => 3,
            'post_status' => 'publish',
            'post_type' => 'classified',
        ]);

        if (empty($featured)) {
            return '';
        }

        ob_start();
        ?>
        <div class="sb-card">
            <div class="sb-title"><?php esc_html_e('Marketplace', 'apollo-social'); ?></div>
            <?php foreach($featured as $item) : ?>
                <div class="sb-market-item" data-item-id="<?php echo absint($item->ID); ?>">
                    <div class="smi-icon">
                        <i class="ri-exchange-box-line"></i>
                    </div>
                    <div class="smi-info">
                        <div class="smi-name"><?php echo esc_html(wp_trim_words($item->post_title, 5)); ?></div>
                        <div class="smi-meta"><?php echo esc_html(get_post_meta($item->ID, '_classified_price', true)); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
