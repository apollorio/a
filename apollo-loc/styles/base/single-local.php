<?php

/**
 * Template: Single Local � Vibrant Venue Profile
 *
 * Canvas template (uses wp_head / wp_footer).
 * 4-level fallback: styles/base/single-local.php
 *
 * Data pulled from:
 *   - CPT meta: _local_* (address, coords, capacity, phone, socials, gallery, testimonials)
 *   - Taxonomies: local_type, local_area
 *   - Related: events at this loc, DJs linked to events here
 *   - Ecosystem: apollo-fav, apollo-wow, apollo-comment (depoimentos)
 *
 * @package Apollo\Local
 * @since   2.1.0
 */

defined('ABSPATH') || exit;

if (! have_posts() || get_post_type() !== APOLLO_LOCAL_CPT) {
    status_header(404);
    nocache_headers();
    include get_404_template();
    exit;
}

the_post();
global $post;

$loc_id   = $post->ID;
$loc_name = get_post_meta($loc_id, '_local_name', true) ?: get_the_title($loc_id);

// -- Address ------------------------------------------------------
$address        = get_post_meta($loc_id, '_local_address', true) ?: '';
$city           = get_post_meta($loc_id, '_local_city', true) ?: '';
$state          = get_post_meta($loc_id, '_local_state', true) ?: '';
$postal         = get_post_meta($loc_id, '_local_postal', true) ?: '';
$full_address   = trim(implode(', ', array_filter(array($address, $city, $state))));
$address_detail = trim(implode(', ', array_filter(array($city, $state, $postal))));

// -- Coordinates --------------------------------------------------
$lat        = (float) (get_post_meta($loc_id, '_local_lat', true) ?: 0);
$lng        = (float) (get_post_meta($loc_id, '_local_lng', true) ?: 0);
$has_coords = $lat && $lng;

// -- Description --------------------------------------------------
$loc_desc_raw   = get_post_meta($loc_id, '_local_description', true) ?: '';
$loc_content    = ! empty($loc_desc_raw) ? $loc_desc_raw : get_the_content();
$loc_paragraphs = array_filter(array_map('trim', preg_split('/\n{2,}/', $loc_content)));

// -- Taxonomy -----------------------------------------------------
$type_terms = wp_get_post_terms($loc_id, APOLLO_LOCAL_TAX_TYPE, array('fields' => 'names'));
$area_terms = wp_get_post_terms($loc_id, APOLLO_LOCAL_TAX_AREA, array('fields' => 'names'));
$type_label = (! is_wp_error($type_terms) && ! empty($type_terms)) ? implode(' / ', $type_terms) : '';
$area_label = (! is_wp_error($area_terms) && ! empty($area_terms)) ? $area_terms[0] : '';

// -- Meta ---------------------------------------------------------
$capacity    = get_post_meta($loc_id, '_local_capacity', true) ?: '';
$phone       = get_post_meta($loc_id, '_local_phone', true) ?: '';
$price_range = get_post_meta($loc_id, '_local_price_range', true) ?: '';

// -- Social Links -------------------------------------------------
$social_map   = array(
    'website'   => array('_local_website', 'ri-global-line', 'Website'),
    'instagram' => array('_local_instagram', 'ri-instagram-line', 'Instagram'),
    'facebook'  => array('_local_facebook', 'ri-facebook-circle-line', 'Facebook'),
    'whatsapp'  => array('_local_whatsapp', 'ri-whatsapp-line', 'WhatsApp'),
    'phone'     => array('_local_phone', 'ri-phone-line', 'Telefone'),
);
$social_links = array();
foreach ($social_map as $key => $def) {
    $val = get_post_meta($loc_id, $def[0], true);
    if (! $val) {
        continue;
    }
    $url = $val;
    if ($key === 'whatsapp' && is_numeric(preg_replace('/\D/', '', $val))) {
        $url = 'https://wa.me/' . preg_replace('/\D/', '', $val);
    } elseif ($key === 'phone') {
        $url    = 'tel:' . preg_replace('/\D/', '', $val);
        $def[2] = $val; // Display the phone number
    } elseif ($key === 'instagram' && strpos($val, 'http') === false) {
        $url    = 'https://instagram.com/' . ltrim($val, '@');
        $def[2] = '@' . ltrim($val, '@');
    }
    $social_links[$key] = array(
        'url'   => $url,
        'icon'  => $def[1],
        'label' => $def[2],
        'raw'   => $val,
    );
}

// -- Gallery ------------------------------------------------------
$gallery = array();
for ($i = 1; $i <= 5; $i++) {
    $img = get_post_meta($loc_id, "_local_image_{$i}", true);
    if ($img) {
        $gallery[] = is_numeric($img) ? wp_get_attachment_image_url((int) $img, 'large') : $img;
    }
}
if (has_post_thumbnail($loc_id)) {
    $feat = get_the_post_thumbnail_url($loc_id, 'large');
    if ($feat && ! in_array($feat, $gallery, true)) {
        array_unshift($gallery, $feat);
    }
}
$hero_image    = $gallery[0] ?? 'https://assets.apollo.rio.br/img/bg/grain-001.jpg';
$gallery_count = count($gallery);

// -- Music Tags (from event sounds taxonomy) ----------------------
$sound_terms = array();
if (taxonomy_exists('sound')) {
    // Aggregate sounds from events at this loc
    $sound_events = get_posts(
        array(
            'post_type'      => 'event',
            'posts_per_page' => 20,
            'meta_key'       => '_event_local_id',
            'meta_value'     => $loc_id,
            'fields'         => 'ids',
        )
    );
    $all_sounds   = array();
    foreach ($sound_events as $eid) {
        $s = wp_get_post_terms($eid, 'sound', array('fields' => 'names'));
        if (! is_wp_error($s)) {
            $all_sounds = array_merge($all_sounds, $s);
        }
    }
    $sound_terms = array_unique($all_sounds);
    sort($sound_terms);
}

// -- Upcoming Events ----------------------------------------------
$upcoming_events = array();
if (post_type_exists('event')) {
    $ev_query = new WP_Query(
        array(
            'post_type'      => 'event',
            'posts_per_page' => 6,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_event_local_id',
                    'value'   => $loc_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_event_start_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_event_start_date',
            'order'          => 'ASC',
        )
    );
    while ($ev_query->have_posts()) {
        $ev_query->the_post();
        $eid               = get_the_ID();
        $ev_date           = get_post_meta($eid, '_event_start_date', true);
        $ev_stime          = get_post_meta($eid, '_event_start_time', true) ?: '';
        $ev_etime          = get_post_meta($eid, '_event_end_time', true) ?: '';
        $ev_ticket         = get_post_meta($eid, '_event_ticket_url', true) ?: '';
        $upcoming_events[] = array(
            'id'         => $eid,
            'title'      => get_the_title(),
            'url'        => get_permalink(),
            'date'       => $ev_date,
            'start_time' => $ev_stime,
            'end_time'   => $ev_etime,
            'ticket_url' => $ev_ticket,
        );
    }
    wp_reset_postdata();
}
$total_events = apollo_local_count_upcoming_events($loc_id);

// -- Resident DJs -------------------------------------------------
$resident_djs = array();
if (post_type_exists('dj')) {
    // DJs linked to events at this loc
    $dj_event_ids = get_posts(
        array(
            'post_type'      => 'event',
            'posts_per_page' => 50,
            'meta_key'       => '_event_local_id',
            'meta_value'     => $loc_id,
            'fields'         => 'ids',
        )
    );
    $dj_id_count  = array();
    foreach ($dj_event_ids as $eid) {
        $dj_ids_raw = get_post_meta($eid, '_event_dj_ids', true);
        if (! $dj_ids_raw) {
            continue;
        }
        $ids = is_array($dj_ids_raw) ? $dj_ids_raw : array_filter(array_map('absint', explode(',', (string) $dj_ids_raw)));
        foreach ($ids as $did) {
            $dj_id_count[$did] = ($dj_id_count[$did] ?? 0) + 1;
        }
    }
    // Sort by frequency (most appearances = resident)
    arsort($dj_id_count);
    $top_djs = array_slice(array_keys($dj_id_count), 0, 6, true);
    foreach ($top_djs as $djid) {
        $dj_post = get_post($djid);
        if (! $dj_post || $dj_post->post_status !== 'publish') {
            continue;
        }
        $dj_sounds      = wp_get_post_terms($djid, 'sound', array('fields' => 'names'));
        $resident_djs[] = array(
            'id'     => $djid,
            'name'   => $dj_post->post_title,
            'url'    => get_permalink($djid),
            'avatar' => get_the_post_thumbnail_url($djid, 'thumbnail') ?: '',
            'genre'  => (! is_wp_error($dj_sounds) && ! empty($dj_sounds))
                ? implode(' / ', array_slice($dj_sounds, 0, 2))
                : '',
        );
    }
}

// -- Depoimentos (apollo-comment) ---------------------------------
$testimonials_raw = get_post_meta($loc_id, '_local_testimonials', true);
$testimonials     = array();
if (! empty($testimonials_raw)) {
    if (is_string($testimonials_raw)) {
        $decoded = json_decode($testimonials_raw, true);
        if (is_array($decoded)) {
            $testimonials = $decoded;
        }
    } elseif (is_array($testimonials_raw)) {
        $testimonials = $testimonials_raw;
    }
}
// Also pull WP comments (depoimentos)
$wp_reviews = get_comments(
    array(
        'post_id' => $loc_id,
        'status'  => 'approve',
        'number'  => 10,
        'orderby' => 'comment_date',
        'order'   => 'DESC',
    )
);
foreach ($wp_reviews as $review) {
    $testimonials[] = array(
        'name'   => $review->comment_author,
        'avatar' => get_avatar_url($review->comment_author_email, array('size' => 72)),
        'text'   => $review->comment_content,
        'rating' => (int) get_comment_meta($review->comment_ID, '_rating', true) ?: 5,
        'date'   => $review->comment_date,
    );
}
$total_reviews = count($testimonials);

// -- Stats --------------------------------------------------------
$fav_count  = function_exists('apollo_fav_get_count') ? apollo_fav_get_count($loc_id) : 0;
$wow_count  = function_exists('apollo_wow_get_count') ? apollo_wow_get_count($loc_id) : 0;
$avg_rating = 0;
if ($total_reviews > 0) {
    $total_rating = array_sum(array_column($testimonials, 'rating'));
    $avg_rating   = round($total_rating / $total_reviews, 1);
}

// -- User state ---------------------------------------------------
$is_logged_in = is_user_logged_in();
$user_id      = get_current_user_id();
$is_favorited = ($is_logged_in && function_exists('apollo_fav_is_favorited'))
    ? apollo_fav_is_favorited($user_id, $loc_id)
    : false;

// -- Google Maps route URL ----------------------------------------
$route_url = $has_coords ? sprintf('https://www.google.com/maps/dir/?api=1&destination=%s,%s', $lat, $lng) : '';

// -- Hours (stored as JSON meta or hardcoded placeholder) ---------
$hours_raw = get_post_meta($loc_id, '_local_hours', true);
$hours     = array();
if (! empty($hours_raw) && is_string($hours_raw)) {
    $hours = json_decode($hours_raw, true) ?: array();
} elseif (is_array($hours_raw)) {
    $hours = $hours_raw;
}
$day_names = array('Segunda', 'Ter�a', 'Quarta', 'Quinta', 'Sexta', 'S�bado', 'Domingo');
$today_idx = (int) current_time('N') - 1; // 0=Mon .. 6=Sun

// -- Amenities (from meta JSON or taxonomy) -----------------------
$amenities_raw = get_post_meta($loc_id, '_local_amenities', true);
$amenities     = array();
if (! empty($amenities_raw) && is_string($amenities_raw)) {
    $amenities = json_decode($amenities_raw, true) ?: array();
} elseif (is_array($amenities_raw)) {
    $amenities = $amenities_raw;
}
// Icon map for common amenities
$amenity_icons = array(
    'estacionamento'  => 'ri-parking-box-line',
    'vip'             => 'ri-vip-crown-line',
    'fum�dromo'       => 'ri-cloudy-line',
    'ar condicionado' => 'ri-temp-cold-line',
    'acessibilidade'  => 'ri-wheelchair-line',
    'wifi'            => 'ri-wifi-line',
    'seguran�a'       => 'ri-shield-check-line',
    'rooftop'         => 'ri-building-4-line',
    'piscina'         => 'ri-water-flash-line',
    'palco'           => 'ri-mic-line',
    'cozinha'         => 'ri-restaurant-line',
    'bar'             => 'ri-goblet-fill',
    'no photo'        => 'ri-camera-off-line',
);
ob_start();
?>
    <!-- Fonts: Shrikhand only (Space Grotesk + Space Mono already loaded by CDN) -->
    <link href="https://fonts.googleapis.com/css2?family=Shrikhand&display=swap" rel="stylesheet">

    <!-- Leaflet — cdn.jsdelivr.net, not unpkg.com (unpkg isn't CSP-allowlisted) -->
    <?php if ($has_coords) : ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php endif; ?>

        <style>
        /* -------------------------------------------------------
		TOKENS & THEME
		------------------------------------------------------- */
        :root {
            --ff-fun: "Syne", sans-serif;
            --ff-mono: "Space Mono", monospace;
            --ff-main: "Space Grotesk", system-ui, -apple-system, sans-serif;
            --primary: #FF9820;
            --bg: #ffffff;
            --surface: #f4f4f5;
            --border: #e4e4e7;
            --black-1: #121214;
            --gray-1: #71717a;
            --gray-2: #a1a1aa;
            --radius: 16px;
            --r-lg: 24px;
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
            --max-w: 1280px;
            --ap-orange: #FF9820;
            --ap-green: #22c55e;
            --ap-blue: #3b82f6;
            --ap-purple: #8b5cf6;
            --ap-red: #ef4444;
            --ap-yellow: #eab308;
        }

        body.dark-mode {
            --bg: #0a0a0b;
            --surface: #18181b;
            --border: #27272a;
            --black-1: #fafafa;
            --gray-1: #a1a1aa;
            --gray-2: #71717a;
        }

        /* --- RESET --- */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--ff-main);
            background: var(--bg);
            color: var(--black-1);
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
            overflow-x: hidden;
            transition: background 0.4s, color 0.4s;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            display: block;
            max-width: 100%;
        }

        button {
            cursor: pointer;
            border: none;
            font-family: inherit;
        }

        .container {
            width: 100%;
            max-width: var(--max-w);
            padding: 0 20px;
            margin: 0 auto;
        }

        .page-loader {
            position: fixed;
            inset: 0;
            background: var(--black-1);
            z-index: 9999;
            transform-origin: bottom;
        }

        /* --- HERO --- */
        .loc-hero {
            position: relative;
            height: 70vh;
            min-height: 500px;
            overflow: hidden;
        }

        .loc-hero-bg {
            position: absolute;
            inset: 0;
        }

        .loc-hero-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: grayscale(40%);
            transition: filter 1s;
        }

        .loc-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, var(--black-1) 0%, rgba(var(--rgb-d), 0.3) 40%, transparent 70%);
        }

        .loc-hero-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 40px 20px;
            color: white;
            z-index: 2;
        }

        .loc-hero-content .container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
        }

        .loc-hero-pills {
            display: flex;
            gap: 6px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .loc-pill {
            padding: 4px 12px;
            border-radius: 100px;
            font-family: var(--ff-mono);
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .loc-pill.live {
            background: var(--ap-green);
            color: white;
        }

        .loc-pill.type {
            background: rgba(var(--rgb-t), 0.15);
            color: white;
            backdrop-filter: blur(8px);
        }

        .loc-pill.rating {
            background: rgba(var(--rgb-t), 0.15);
            color: white;
            backdrop-filter: blur(8px);
        }

        .loc-hero-title {
            font-size: clamp(36px, 8vw, 80px);
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -0.04em;
            text-transform: uppercase;
            color: white;
            margin-bottom: 8px;
        }

        .loc-hero-location {
            font-size: 14px;
            color: rgba(var(--rgb-t), 0.7);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .loc-hero-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .loc-action-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(var(--rgb-t), 0.15);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            transition: all 0.3s;
        }

        .loc-action-btn:hover {
            background: var(--ap-orange);
        }

        .loc-action-btn.is-favorited {
            background: var(--ap-orange);
        }

        .gallery-counter {
            position: absolute;
            bottom: 20px;
            right: 20px;
            padding: 6px 14px;
            background: rgba(var(--rgb-d), 0.6);
            backdrop-filter: blur(8px);
            border-radius: 100px;
            color: white;
            font-size: 11px;
            font-weight: 600;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: background .3s;
        }

        .gallery-counter:hover {
            background: rgba(var(--rgb-d), 0.8);
        }

        /* --- MAIN GRID --- */
        .loc-main {
            padding: 48px 0;
        }

        .loc-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 40px;
        }

        /* --- SECTIONS --- */
        .section-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--ap-orange);
        }

        .loc-description {
            font-size: 15px;
            line-height: 1.7;
            color: var(--gray-1);
            margin-bottom: 16px;
        }

        .loc-about {
            margin-bottom: 40px;
        }

        .loc-tags {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .loc-tag {
            padding: 6px 14px;
            background: var(--card);
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-1);
        }

        /* --- QUICK STATS --- */
        .loc-quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border);
            border-radius: var(--r);
            overflow: hidden;
            margin-bottom: 40px;
        }

        .loc-stat {
            background: var(--bg);
            padding: 20px;
            text-align: center;
        }

        .loc-stat-number {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1;
            margin-bottom: 4px;
        }

        .loc-stat-label {
            font-family: var(--ff-mono);
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gray-2);
        }

        /* --- AMENITIES --- */
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 40px;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px;
            background: var(--card);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .amenity-item i {
            font-size: 18px;
            color: var(--ap-orange);
        }

        /* --- EVENT CARDS --- */
        .loc-events-list {
            margin-bottom: 40px;
        }

        .loc-event-card {
            display: flex;
            gap: 16px;
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: var(--r);
            margin-bottom: 12px;
            transition: all 0.3s var(--ease-out);
            align-items: center;
        }

        .loc-event-card:hover {
            border-color: var(--ap-orange);
            transform: translateX(4px);
        }

        .loc-event-date {
            width: 56px;
            height: 56px;
            background: var(--card);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .loc-event-date .month {
            font-family: var(--ff-mono);
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--ap-orange);
            line-height: 1;
        }

        .loc-event-date .day {
            font-size: 20px;
            font-weight: 900;
            line-height: 1.1;
        }

        .loc-event-info {
            flex: 1;
        }

        .loc-event-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .loc-event-meta {
            font-size: 11px;
            color: var(--gray-2);
            display: flex;
            gap: 12px;
        }

        .loc-event-tickets {
            padding: 8px 16px;
            background: var(--ap-orange);
            color: white;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            transition: all 0.3s var(--ease-out);
            flex-shrink: 0;
            display: inline-block;
        }

        .loc-event-tickets:hover {
            transform: scale(1.05);
        }

        /* --- REVIEWS --- */
        .review-card {
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: var(--r);
            margin-bottom: 12px;
        }

        .review-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .review-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .review-name {
            font-size: 13px;
            font-weight: 700;
        }

        .review-date {
            font-size: 10px;
            color: var(--gray-2);
        }

        .review-stars {
            display: flex;
            gap: 2px;
            margin-bottom: 8px;
        }

        .review-stars i {
            font-size: 12px;
            color: var(--ap-yellow);
        }

        .review-text {
            font-size: 13px;
            line-height: 1.6;
            color: var(--gray-1);
        }

        /* --- SIDEBAR --- */
        .loc-sidebar {
            position: sticky;
            top: 80px;
        }

        .loc-map-card {
            border: 1px solid var(--border);
            border-radius: var(--r);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .loc-map {
            height: 240px;
        }

        .loc-map-info {
            padding: 16px;
        }

        .loc-address {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .loc-address-detail {
            font-size: 11px;
            color: var(--gray-2);
        }

        .loc-contact-card {
            border: 1px solid var(--border);
            border-radius: var(--r);
            padding: 20px;
            margin-bottom: 20px;
        }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .contact-row:last-child {
            border-bottom: none;
        }

        .contact-row i {
            font-size: 18px;
            color: var(--ap-orange);
            width: 24px;
            text-align: center;
        }

        .contact-row span {
            font-size: 13px;
        }

        .contact-row a {
            transition: color .2s;
        }

        .contact-row a:hover {
            color: var(--ap-orange);
        }

        .loc-hours-card {
            border: 1px solid var(--border);
            border-radius: var(--r);
            padding: 20px;
            margin-bottom: 20px;
        }

        .hours-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12px;
        }

        .hours-day {
            font-weight: 600;
        }

        .hours-time {
            color: var(--gray-1);
        }

        .hours-row.today {
            color: var(--ap-orange);
            font-weight: 700;
        }

        /* --- RESIDENT DJS --- */
        .resident-dj {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 10px;
            transition: background 0.2s;
            margin-bottom: 4px;
        }

        .resident-dj:hover {
            background: var(--card);
        }

        .resident-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--card);
        }

        .resident-name {
            font-size: 13px;
            font-weight: 700;
        }

        .resident-genre {
            font-size: 10px;
            color: var(--gray-2);
        }

        .resident-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-2);
            font-size: 16px;
        }

        /* --- PHOTO GALLERY --- */
        .photo-gallery {
            padding: 48px 0;
            border-top: 1px solid var(--border);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
            border-radius: var(--r);
            overflow: hidden;
        }

        .gallery-item {
            aspect-ratio: 1;
            overflow: hidden;
            cursor: pointer;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: grayscale(40%);
            transition: filter 0.4s, transform 0.4s var(--ease-out);
        }

        .gallery-item:hover img {
            filter: grayscale(0%);
            transform: scale(1.08);
        }

        /* --- FOOTER --- */
        .site-footer {
            border-top: 1px solid var(--border);
            padding: 32px 0;
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .footer-copy {
            font-family: var(--ff-mono);
            font-size: 10px;
            color: var(--gray-2);
        }

        .footer-links {
            display: flex;
            gap: 20px;
        }

        .footer-links a {
            font-size: 11px;
            color: var(--gray-2);
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--black-1);
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 900px) {
            .loc-grid {
                grid-template-columns: 1fr;
            }

            .loc-sidebar {
                position: static;
            }

            .loc-quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .loc-hero {
                height: 50vh;
                min-height: 400px;
            }

            .loc-hero-content .container {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .loc-hero-title {
                font-size: 36px;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-inner {
                flex-direction: column;
                gap: 16px;
            }
        }

        .reveal-up {
            opacity: 0;
            transform: translateY(30px);
        }
    </style>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => $loc_name . ' — Apollo',
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body <?php body_class(); ?> data-loc-id="<?php echo esc_attr($loc_id); ?>">

    <div class="page-loader"></div>

    <!-- ----------- HERO ----------- -->
    <section class="loc-hero">
        <div class="loc-hero-bg">
            <img src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr($loc_name); ?>">
        </div>
        <div class="loc-hero-overlay"></div>
        <div class="loc-hero-content">
            <div class="container">
                <div>
                    <div class="loc-hero-pills">
                        <?php if ($type_label) : ?>
                            <span class="loc-pill type"><?php echo esc_html($type_label); ?></span>
                        <?php endif; ?>
                        <?php if ($avg_rating > 0) : ?>
                            <span class="loc-pill rating"><i class="ri-star-fill" style="margin-right:3px;"></i> <?php echo esc_html($avg_rating); ?></span>
                        <?php endif; ?>
                        <?php if ($area_label) : ?>
                            <span class="loc-pill type"><?php echo esc_html($area_label); ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 class="loc-hero-title"><?php echo esc_html($loc_name); ?></h1>
                    <?php if ($full_address) : ?>
                        <div class="loc-hero-location">
                            <i class="ri-map-pin-2-fill"></i> <?php echo esc_html($full_address); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="loc-hero-actions">
                    <?php if ($is_logged_in) : ?>
                        <button
                            class="loc-action-btn <?php echo $is_favorited ? 'is-favorited' : ''; ?>"
                            id="loc-fav-btn"
                            data-action="toggle-fav"
                            data-post-id="<?php echo $loc_id; ?>"
                            title="<?php esc_attr_e('Favoritar', 'apollo-local'); ?>">
                            <i class="ri-heart-<?php echo $is_favorited ? 'fill' : 'line'; ?>"></i>
                        </button>
                    <?php endif; ?>
                    <button class="loc-action-btn" id="loc-share-btn" title="<?php esc_attr_e('Compartilhar', 'apollo-local'); ?>">
                        <i class="ri-share-forward-line"></i>
                    </button>
                    <?php if ($route_url) : ?>
                        <a href="<?php echo esc_url($route_url); ?>" class="loc-action-btn" target="_blank" rel="noopener" title="<?php esc_attr_e('Dire��es', 'apollo-local'); ?>">
                            <i class="ri-route-line"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($gallery_count > 1) : ?>
            <div class="gallery-counter" id="scroll-to-gallery"><i class="ri-image-line"></i> <?php echo $gallery_count; ?> fotos</div>
        <?php endif; ?>
    </section>

    <!-- ----------- MAIN ----------- -->
    <main class="loc-main">
        <div class="container">
            <div class="loc-grid">

                <!-- --- LEFT COLUMN --- -->
                <div class="loc-details">

                    <!-- Quick Stats -->
                    <div class="loc-quick-stats reveal-up">
                        <?php if ($avg_rating > 0) : ?>
                            <div class="loc-stat">
                                <div class="loc-stat-number"><?php echo esc_html($avg_rating); ?></div>
                                <div class="loc-stat-label">Rating</div>
                            </div>
                        <?php endif; ?>
                        <?php if ($total_reviews > 0) : ?>
                            <div class="loc-stat">
                                <div class="loc-stat-number"><?php echo esc_html($total_reviews); ?></div>
                                <div class="loc-stat-label">Depoimentos</div>
                            </div>
                        <?php endif; ?>
                        <div class="loc-stat">
                            <div class="loc-stat-number"><?php echo esc_html($total_events); ?></div>
                            <div class="loc-stat-label">Eventos</div>
                        </div>
                        <div class="loc-stat">
                            <div class="loc-stat-number"><?php echo $fav_count >= 1000 ? esc_html(number_format($fav_count / 1000, 1) . 'k') : esc_html($fav_count); ?></div>
                            <div class="loc-stat-label">Favs</div>
                        </div>
                    </div>

                    <!-- About -->
                    <?php if (! empty($loc_paragraphs)) : ?>
                        <div class="loc-about reveal-up">
                            <div class="section-title"><i class="ri-information-line"></i> <?php esc_html_e('Sobre', 'apollo-local'); ?></div>
                            <?php foreach ($loc_paragraphs as $para) : ?>
                                <p class="loc-description"><?php echo wp_kses_post($para); ?></p>
                            <?php endforeach; ?>
                            <?php if (! empty($sound_terms)) : ?>
                                <div class="loc-tags">
                                    <?php foreach ($sound_terms as $tag) : ?>
                                        <span class="loc-tag"><?php echo esc_html($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Amenities -->
                    <?php if (! empty($amenities)) : ?>
                        <div class="reveal-up">
                            <div class="section-title"><i class="ri-service-line"></i> <?php esc_html_e('Estrutura', 'apollo-local'); ?></div>
                            <div class="amenities-grid">
                                <?php
                                foreach ($amenities as $amenity) :
                                    $a_name = is_array($amenity) ? ($amenity['name'] ?? $amenity['label'] ?? '') : $amenity;
                                    $a_icon = is_array($amenity) ? ($amenity['icon'] ?? '') : '';
                                    if (! $a_icon) {
                                        $a_key = mb_strtolower($a_name);
                                        foreach ($amenity_icons as $k => $v) {
                                            if (stripos($a_key, $k) !== false) {
                                                $a_icon = $v;
                                                break;
                                            }
                                        }
                                    }
                                    $a_icon = $a_icon ?: 'ri-checkbox-circle-line';
                                ?>
                                    <div class="amenity-item"><i class="<?php echo esc_attr($a_icon); ?>"></i> <?php echo esc_html($a_name); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Upcoming Events -->
                    <?php if (! empty($upcoming_events)) : ?>
                        <div class="loc-events-list reveal-up">
                            <div class="section-title"><i class="ri-calendar-event-fill"></i> <?php esc_html_e('Pr�ximos Eventos', 'apollo-local'); ?></div>
                            <?php
                            foreach ($upcoming_events as $ev) :
                                $ev_ts    = $ev['date'] ? strtotime($ev['date']) : 0;
                                $ev_day   = $ev_ts ? date_i18n('d', $ev_ts) : '--';
                                $ev_month = $ev_ts ? date_i18n('M', $ev_ts) : '--';
                                $ev_time  = '';
                                if ($ev['start_time']) {
                                    $ev_time = $ev['start_time'];
                                    if ($ev['end_time']) {
                                        $ev_time .= ' - ' . $ev['end_time'];
                                    }
                                }
                            ?>
                                <a href="<?php echo esc_url($ev['url']); ?>" class="loc-event-card">
                                    <div class="loc-event-date">
                                        <span class="month"><?php echo esc_html($ev_month); ?></span>
                                        <span class="day"><?php echo esc_html($ev_day); ?></span>
                                    </div>
                                    <div class="loc-event-info">
                                        <div class="loc-event-name"><?php echo esc_html($ev['title']); ?></div>
                                        <div class="loc-event-meta">
                                            <?php if ($ev_time) : ?>
                                                <span><i class="ri-time-line"></i> <?php echo esc_html($ev_time); ?></span>
                                            <?php endif; ?>
                                            <?php if ($capacity) : ?>
                                                <span><i class="ri-group-line"></i> <?php echo esc_html($capacity); ?> cap.</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($ev['ticket_url']) : ?>
                                        <span class="loc-event-tickets"><?php esc_html_e('Ingressos', 'apollo-local'); ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Reviews / Depoimentos -->
                    <?php if (! empty($testimonials)) : ?>
                        <div class="reveal-up">
                            <div class="section-title"><i class="ri-star-fill"></i> <?php printf(esc_html__('Depoimentos (%d)', 'apollo-local'), $total_reviews); ?></div>
                            <?php
                            foreach (array_slice($testimonials, 0, 5) as $review) :
                                $stars = min(5, (int) ($review['rating'] ?? 5));
                                $half  = (($review['rating'] ?? 5) - $stars) >= 0.3;
                            ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <?php if (! empty($review['avatar'])) : ?>
                                            <img src="<?php echo esc_url($review['avatar']); ?>" alt="" class="review-avatar" loading="lazy">
                                        <?php endif; ?>
                                        <div>
                                            <div class="review-name"><?php echo esc_html($review['name'] ?? __('An�nimo', 'apollo-local')); ?></div>
                                            <?php if (! empty($review['date'])) : ?>
                                                <div class="review-date"><?php echo wp_kses_post(apollo_time_ago_html($review['date'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="review-stars">
                                        <?php
                                        for ($s = 0; $s < $stars; $s++) {
                                            echo '<i class="ri-star-fill"></i>';
                                        }
                                        if ($half) {
                                            echo '<i class="ri-star-half-fill"></i>';
                                        }
                                        ?>
                                    </div>
                                    <p class="review-text"><?php echo esc_html($review['text'] ?? ''); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- --- RIGHT COLUMN � SIDEBAR --- -->
                <div class="loc-sidebar">

                    <!-- Map -->
                    <?php if ($has_coords) : ?>
                        <div class="loc-map-card reveal-up">
                            <div class="loc-map" id="locMap"></div>
                            <div class="loc-map-info">
                                <div class="loc-address"><?php echo esc_html($address ?: $loc_name); ?></div>
                                <?php if ($address_detail) : ?>
                                    <div class="loc-address-detail"><?php echo esc_html($address_detail); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Contact -->
                    <?php if (! empty($social_links)) : ?>
                        <div class="loc-contact-card reveal-up">
                            <div class="section-title" style="margin-bottom:12px;"><i class="ri-phone-line"></i> <?php esc_html_e('Contato', 'apollo-local'); ?></div>
                            <?php foreach ($social_links as $key => $link) : ?>
                                <div class="contact-row">
                                    <i class="<?php echo esc_attr($link['icon']); ?>"></i>
                                    <span><a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($link['label']); ?></a></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Hours -->
                    <?php if (! empty($hours)) : ?>
                        <div class="loc-hours-card reveal-up">
                            <div class="section-title" style="margin-bottom:12px;"><i class="ri-time-line"></i> <?php esc_html_e('Hor�rios', 'apollo-local'); ?></div>
                            <?php
                            foreach ($day_names as $idx => $day_name) :
                                $h        = $hours[$idx] ?? ($hours[$day_name] ?? '');
                                $time_str = is_array($h) ? ($h['open'] . ' - ' . $h['close']) : ($h ?: __('Fechado', 'apollo-local'));
                            ?>
                                <div class="hours-row<?php echo $idx === $today_idx ? ' today' : ''; ?>">
                                    <span class="hours-day"><?php echo esc_html($day_name); ?></span>
                                    <span class="hours-time"><?php echo esc_html($time_str); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Resident DJs -->
                    <?php if (! empty($resident_djs)) : ?>
                        <div class="reveal-up">
                            <div class="section-title" style="margin-bottom:12px;"><i class="ri-disc-line"></i> <?php esc_html_e('DJs Residentes', 'apollo-local'); ?></div>
                            <?php foreach ($resident_djs as $dj) : ?>
                                <a href="<?php echo esc_url($dj['url']); ?>" class="resident-dj">
                                    <?php if ($dj['avatar']) : ?>
                                        <img src="<?php echo esc_url($dj['avatar']); ?>" alt="<?php echo esc_attr($dj['name']); ?>" class="resident-avatar" loading="lazy">
                                    <?php else : ?>
                                        <div class="resident-placeholder"><i class="ri-disc-line"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="resident-name"><?php echo esc_html($dj['name']); ?></div>
                                        <?php if ($dj['genre']) : ?>
                                            <div class="resident-genre"><?php echo esc_html($dj['genre']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- ----------- PHOTO GALLERY ----------- -->
    <?php if (count($gallery) > 1) : ?>
        <section class="photo-gallery" id="photo-gallery">
            <div class="container">
                <div class="section-title reveal-up"><i class="ri-camera-line"></i> <?php esc_html_e('Galeria', 'apollo-local'); ?></div>
                <div class="gallery-grid">
                    <?php foreach ($gallery as $img_url) : ?>
                        <div class="gallery-item reveal-up">
                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($loc_name); ?>" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ----------- FOOTER ----------- -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-inner">
                <div class="footer-copy">Apollo::rio � <?php echo esc_html(date('Y')); ?></div>
                <div class="footer-links">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('In�cio', 'apollo-local'); ?></a>
                    <a href="<?php echo esc_url(home_url('/eventos')); ?>"><?php esc_html_e('Eventos', 'apollo-local'); ?></a>
                    <a href="<?php echo esc_url(home_url('/radar')); ?>"><?php esc_html_e('Radar', 'apollo-local'); ?></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ----------- SCRIPTS ----------- -->
    <script>
        (function() {
            'use strict';

            var REST_URL = '<?php echo esc_url(rest_url('apollo/v1/')); ?>';
            var NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';
            var LOC_ID = <?php echo (int) $loc_id; ?>;

            // -- Page Loader --
            window.addEventListener('load', function() {
                gsap.to('.page-loader', {
                    scaleY: 0,
                    duration: 0.8,
                    ease: 'power4.inOut',
                    delay: 0.2
                });
            });

            // -- Scroll Reveal --
            gsap.registerPlugin(ScrollTrigger);
            document.querySelectorAll('.reveal-up').forEach(function(el) {
                gsap.to(el, {
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 90%',
                        once: true
                    },
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    ease: 'power3.out'
                });
            });

            // -- Parallax hero --
            gsap.to('.loc-hero-bg img', {
                y: '15%',
                ease: 'none',
                scrollTrigger: {
                    trigger: '.loc-hero',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1
                }
            });

            <?php if ($has_coords) : ?>
                // -- Leaflet Map --
                var map = L.map('venueMap', {
                    zoomControl: false,
                    scrollWheelZoom: false,
                    dragging: false
                }).setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 16);

                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: ''
                }).addTo(map);

                L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>]).addTo(map)
                    .bindPopup('<strong><?php echo esc_js($loc_name); ?></strong><?php echo $address ? '<br>' . esc_js($address) : ''; ?>');
            <?php endif; ?>

            // -- Favorite toggle (apollo-fav) --
            var favBtn = document.getElementById('loc-fav-btn');
            if (favBtn) {
                favBtn.addEventListener('click', function() {
                    var icon = this.querySelector('i');
                    var isFav = this.classList.contains('is-favorited');

                    fetch(REST_URL + 'fav', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': NONCE
                            },
                            body: JSON.stringify({
                                post_id: LOC_ID,
                                action: isFav ? 'remove' : 'add'
                            })
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (data.success || data.status) {
                                favBtn.classList.toggle('is-favorited');
                                icon.className = favBtn.classList.contains('is-favorited') ? 'ri-fire-fill' : 'ri-fire-line';
                            }
                        })
                        .catch(console.error);
                });
            }

            // -- Share --
            var shareBtn = document.getElementById('loc-share-btn');
            if (shareBtn) {
                shareBtn.addEventListener('click', function() {
                    if (navigator.share) {
                        navigator.share({
                            title: '<?php echo esc_js($loc_name); ?>',
                            text: '<?php echo esc_js($loc_name . ' � ' . $full_address); ?>',
                            url: '<?php echo esc_js(get_permalink($loc_id)); ?>'
                        }).catch(function() {});
                    } else {
                        navigator.clipboard.writeText('<?php echo esc_js(get_permalink($loc_id)); ?>');
                        alert('Link copiado!');
                    }
                });
            }

            // -- Gallery scroll-to --
            var galleryBtn = document.getElementById('scroll-to-gallery');
            if (galleryBtn) {
                galleryBtn.addEventListener('click', function() {
                    var el = document.getElementById('photo-gallery');
                    if (el) el.scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            }
        })();
    </script>

    <?php
    // wp_footer(); // Removed for blank canvas
    ?>
</body>

</html>
<?php wp_reset_postdata(); ?>