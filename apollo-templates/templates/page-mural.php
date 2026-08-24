<?php

/**
 * Template Name: Apollo Mural
 * Template Post Type: page
 *
 * Logged-in user dashboard (Mural).
 * Loaded instead of page-home.php when is_user_logged_in() === true.
 *
 * @package Apollo\Templates
 * @since   1.1.0
 */

defined('ABSPATH') || exit;

// Require login — redirect to home if guest.
if (! is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

// Display name (prefer Apollo social name).
$social_name  = get_user_meta($user_id, '_apollo_social_name', true);
$display_name = $social_name ?: $current_user->display_name;
$first_name   = explode(' ', $display_name)[0];

// Location.
$user_location = get_user_meta($user_id, 'user_location', true) ?: 'Rio de Janeiro, Brazil';

// Sound preferences.
$sound_prefs = get_user_meta($user_id, '_apollo_sound_preferences', true);
$sound_tags  = array();
if (! empty($sound_prefs) && is_array($sound_prefs)) {
    foreach ($sound_prefs as $term_id) {
        $term = get_term((int) $term_id);
        if ($term && ! is_wp_error($term)) {
            $sound_tags[] = $term->name;
        }
    }
}

// Favorited events — via apollo_favs table (apollo-fav plugin).
$fav_events = array();
if (function_exists('apollo_get_user_favs')) {
    $fav_rows = apollo_get_user_favs($user_id, 'event', 12, 0);
    $fav_ids  = wp_list_pluck($fav_rows, 'post_id');
} else {
    $fav_ids = get_user_meta($user_id, '_apollo_favorite_events', true);
}
if (! empty($fav_ids) && is_array($fav_ids)) {
    $fav_events = get_posts(
        array(
            'post_type'      => 'event',
            'post__in'       => array_map('intval', $fav_ids),
            'posts_per_page' => 12,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value',
            'meta_key'       => '_event_start_date',
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
}

// Same Vibe events (based on user's sound preferences) - ONLY FUTURE EVENTS.
$same_vibe_events = array();
if (! empty($sound_prefs) && is_array($sound_prefs)) {
    $same_vibe_events = get_posts(
        array(
            'post_type'      => 'event',
            'posts_per_page' => 12,
            'post_status'    => 'publish',
            'orderby'        => 'rand',
            'meta_query'     => array(
                array(
                    'key'     => '_event_start_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'sound',
                    'field'    => 'term_id',
                    'terms'    => array_map('intval', $sound_prefs),
                ),
            ),
        )
    );
}

// Current month/year for calendar view.
$current_month = isset($_GET['month']) ? absint($_GET['month']) : (int) current_time('n');
$current_year  = isset($_GET['year']) ? absint($_GET['year']) : (int) current_time('Y');

// Upcoming events (all from current month).
$month_start = sprintf('%04d-%02d-01', $current_year, $current_month);
$month_end   = date('Y-m-t', strtotime($month_start)); // 't' = last day of month

$upcoming_events = get_posts(
    array(
        'post_type'      => 'event',
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'meta_key'       => '_event_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_event_start_date',
                'value'   => $month_start,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_event_start_date',
                'value'   => $month_end,
                'compare' => '<=',
                'type'    => 'DATE',
            ),
        ),
    )
);

// Classifieds.
$classifieds_hosting = get_posts(
    array(
        'post_type'      => 'classified',
        'posts_per_page' => 3,
        'post_status'    => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => 'classified_domain',
                'field'    => 'slug',
                'terms'    => array('hospedagem', 'aluguel', 'hosting', 'accommodation'),
            ),
        ),
    )
);

$classifieds_tickets = get_posts(
    array(
        'post_type'      => 'classified',
        'posts_per_page' => 3,
        'post_status'    => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => 'classified_domain',
                'field'    => 'slug',
                'terms'    => array('ingresso', 'ingressos', 'ticket', 'tickets'),
            ),
        ),
    )
);

// Next event alert (closest favorited event).
$next_event      = null;
$next_event_days = null;
if (! empty($fav_events)) {
    foreach ($fav_events as $ev) {
        $ev_date = get_post_meta($ev->ID, '_event_start_date', true);
        if ($ev_date) {
            $diff = (strtotime($ev_date) - current_time('timestamp')) / DAY_IN_SECONDS;
            if ($diff >= 0) {
                $next_event      = $ev;
                $next_event_days = (int) ceil($diff);
                break;
            }
        }
    }
}

// News ticker items (dynamic from latest posts).
$ticker_posts = get_posts(
    array(
        'post_type'      => 'post',
        'posts_per_page' => 5,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    )
);
$ticker_items = array();
foreach ($ticker_posts as $tp) {
    $ticker_items[] = strtoupper($tp->post_title);
}
$ticker_items = apply_filters('apollo_mural_ticker_items', $ticker_items);

// Asset URLs.
$css_url = plugins_url('assets/css/mural.css', APOLLO_TEMPLATES_FILE);
$js_url  = plugins_url('assets/js/mural.js', APOLLO_TEMPLATES_FILE);
$ver     = defined('WP_DEBUG') && WP_DEBUG ? time() : APOLLO_TEMPLATES_VERSION;

// Template parts directory.
$parts_dir = APOLLO_TEMPLATES_DIR . 'templates/template-parts/mural/';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(get_bloginfo('name')); ?> — Mural</title>

    <!-- Apollo CDN - Canvas Mode (NO wp_head to prevent theme interference) -->
    <script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

    <!-- Mural CSS -->
    <link rel="stylesheet" href="<?php echo esc_url($css_url); ?>?v=<?php echo esc_attr($ver); ?>">

    <!-- Navbar v2 CSS/JS -->
    <?php if (defined('APOLLO_TEMPLATES_URL') && defined('APOLLO_TEMPLATES_VERSION')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/navbar.v2.css'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>">
        <script src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/navbar.v2.js'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>" defer></script>
    <?php endif; ?>
</head>

<body <?php body_class('apollo-mural'); ?>>
    <?php wp_body_open(); ?>

    <?php apollo_render_navbar(); ?>

    <main id="main-content" class="mural-content">

        <?php
        // ═══ 1. WEATHER HERO (video on top) ═══
        require $parts_dir . 'weather-hero.php';
        ?>

        <div class="app-container">

            <?php
            // ═══ 2. GREETING ═══
            require $parts_dir . 'greeting.php';
            ?>

        </div>

        <?php
        // ═══ 3. NEWS GRID (Latest Posts) ═══
        require $parts_dir . 'news.php';
        ?>

        <?php
        // ═══ 4. NEWS TICKER ═══
        require $parts_dir . 'ticker.php';
        ?>

        <div class="app-container">

            <?php
            // ═══ 5. MY SOUNDS ═══
            if (! empty($sound_tags)) {
                include $parts_dir . 'sounds.php';
            }

            // ═══ 6. FAVORITED EVENTS (Marquee) ═══
            if (! empty($fav_events)) {
                include $parts_dir . 'favorites.php';
            }

            // ═══ 7. SAME VIBE (Marquee Reverse) ═══
            if (! empty($same_vibe_events)) {
                include $parts_dir . 'same-vibe.php';
            }

            // ═══ 8. CLASSIFIEDS (BEFORE CALENDAR) ═══
            if (! empty($classifieds_hosting) || ! empty($classifieds_tickets)) {
                include $parts_dir . 'classifieds.php';
            }
            ?>

        </div>

        <?php
        // ═══ 9. MONTHLY EVENTS CALENDAR (Full Width Grid) ═══
        if (! empty($upcoming_events)) {
            include $parts_dir . 'upcoming.php';
        }
        ?>

        <div class="app-container">

            <?php
            // (End of content)
            ?>

        </div>

    </main>

    <script src="<?php echo esc_url($js_url); ?>?v=<?php echo esc_attr($ver); ?>"></script>

    <?php
    do_action('apollo_after_mural_content');
    /* Canvas Mode - NO wp_footer() to prevent theme interference */
    ?>
</body>

</html>