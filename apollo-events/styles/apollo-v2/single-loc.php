<?php

/**
 * Template: Single Location — Apollo V2 Style
 *
 * Venue profile page with full-bleed hero photo, address info,
 * interactive map, directions form, and upcoming events grid.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * FIELD MAPPING — How this template connects to WordPress
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * LOC CPT: "loc" (registered by apollo-core)
 * Rewrite: /local/{slug}
 *
 * CORE FIELDS:
 *   get_the_title()                 → Venue name (hero overlay)
 *   the_content()                   → Full venue description
 *   get_the_post_thumbnail_url()    → Hero background photo
 *
 * LOCATION META FIELDS (registered by apollo-loc, CPT slug: 'local'):
 *   _local_address    (string)       → Full street address
 *   _local_city       (string)       → City name
 *   _local_lat        (float)        → Latitude for OSM map embed
 *   _local_lng        (float)        → Longitude for OSM map embed
 *
 * NEW META FIELDS (for apollo-v2 enhanced loc page):
 *   _local_phone      (string)       → Venue contact phone
 *   _local_instagram  (string)       → Instagram handle (without @)
 *   _local_website    (string)       → Venue website URL
 *   _local_capacity   (string)       → Venue capacity (e.g. "500 pessoas")
 *   _local_bairro     (string)       → Neighborhood (e.g. "Alto da Boa Vista")
 *   _local_gallery    (array)        → Gallery attachment IDs
 *
 * CONNECTED DATA:
 *   Events via _event_loc_id → WP_Query where loc_id matches this venue
 *   Shows upcoming events at this venue
 *
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * @package Apollo\Event
 * @style   apollo-v2
 */

if (! defined('ABSPATH')) {
    exit;
}

if (function_exists('apollo_event_ensure_helpers')) {
    apollo_event_ensure_helpers();
} elseif (defined('APOLLO_EVENT_DIR') && is_readable(APOLLO_EVENT_DIR . 'includes/bootstrap.php')) {
    require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';
}

$loc_page_title = get_the_title() . ' — Apollo::Rio';

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title' => $loc_page_title,
        )
    );
} else {
?>
    <!DOCTYPE html>
    <html lang="pt-BR" data-theme="light">

    <head>
        <meta charset="UTF-8">
        <link rel="preconnect" href="https://assets.apollo.rio.br">
        <link rel="preconnect" href="https://cdn.apollo.rio.br">
        <script
            src="<?php echo esc_url(function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb'); ?>"
            fetchpriority="high"></script>
        <title><?php echo esc_html($loc_page_title); ?></title>
    <?php
}
    ?>
    </head>

    <body>
        <?php

        $post_id  = get_the_ID();
        $loc_name = get_the_title();
        $photo    = get_the_post_thumbnail_url($post_id, 'full')
            ?: (defined('APOLLO_ASSETS_GRAIN_URL') ? APOLLO_ASSETS_GRAIN_URL : 'https://assets.apollo.rio.br/img/bg/grain-001.jpg');

        /* ── Location Meta ──────────────────────────────────────────────────── */
        $address = get_post_meta($post_id, '_local_address', true);
        $city    = get_post_meta($post_id, '_local_city', true);
        $lat     = (float) get_post_meta($post_id, '_local_lat', true);
        $lng     = (float) get_post_meta($post_id, '_local_lng', true);

        /* ── New Meta (apollo-v2 extensions) ────────────────────────────────── */
        $phone     = get_post_meta($post_id, '_local_phone', true);
        $instagram = get_post_meta($post_id, '_local_instagram', true);
        $website   = get_post_meta($post_id, '_local_website', true);
        $capacity  = get_post_meta($post_id, '_local_capacity', true);
        $bairro    = get_post_meta($post_id, '_local_bairro', true);

        /* ── Gallery ────────────────────────────────────────────────────────── */
        $gallery_ids    = get_post_meta($post_id, '_local_gallery', true) ?: array();
        $gallery_images = array();
        if (! empty($gallery_ids) && is_array($gallery_ids)) {
            foreach ($gallery_ids as $att_id) {
                $url = wp_get_attachment_image_url((int) $att_id, 'large');
                if ($url) {
                    $gallery_images[] = $url;
                }
            }
        }

        /* ── Upcoming events at this venue ──────────────────────────────────── */
        $upcoming_events = array();
        $now             = current_time('Y-m-d');
        $events_query    = new \WP_Query(
            array(
                'post_type'      => APOLLO_EVENT_CPT,
                'post_status'    => 'publish',
                'posts_per_page' => 8,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'   => '_event_loc_id',
                        'value' => $post_id,
                        'type'  => 'NUMERIC',
                    ),
                    array(
                        'key'     => '_event_start_date',
                        'value'   => $now,
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ),
                ),
                'orderby'        => 'meta_value',
                'meta_key'       => '_event_start_date',
                'order'          => 'ASC',
            )
        );

        if ($events_query->have_posts()) {
            while ($events_query->have_posts()) {
                $events_query->the_post();
                $e_id     = get_the_ID();
                $e_date   = get_post_meta($e_id, '_event_start_date', true);
                $e_time   = get_post_meta($e_id, '_event_start_time', true);
                $e_parsed = apollo_event_parse_date($e_date ?: $now);
                $e_djs    = apollo_event_get_djs($e_id);
                $e_sounds = wp_get_post_terms($e_id, APOLLO_EVENT_TAX_SOUND, array('fields' => 'names'));

                $upcoming_events[] = array(
                    'id'        => $e_id,
                    'title'     => get_the_title(),
                    'permalink' => get_permalink(),
                    'parsed'    => $e_parsed,
                    'time'      => $e_time,
                    'dj_names'  => implode(', ', array_column($e_djs, 'title')),
                    'sounds'    => is_wp_error($e_sounds) ? array() : $e_sounds,
                );
            }
            wp_reset_postdata();
        }

        /* ── Enqueue ────────────────────────────────────────────────────────── */
        wp_enqueue_style('apollo-events-v2', APOLLO_EVENT_URL . 'styles/apollo-v2/style.css', array('apollo-events'), APOLLO_EVENT_VERSION);

        if ($lat && $lng && apollo_event_option('enable_osm_map', true)) {
            wp_enqueue_style('leaflet');
            wp_enqueue_script('leaflet');
            wp_enqueue_script('apollo-events-map');
        }
        ?>

        <!-- ═══════════════════════════════════════════════════════════════════
	APOLLO V2 — SINGLE LOCATION
	═══════════════════════════════════════════════════════════════════ -->

        <div class="av2-loc" data-loc-id="<?php echo esc_attr($post_id); ?>">

            <!-- ═══ PAGE LOADER ═══ -->
            <div class="av2-loader"></div>

            <!-- ═══════════════════════════════════════════════════════════════
		LOCATION HERO — Full-bleed photo with overlay
		─ Photo:   get_the_post_thumbnail_url()
		─ Name:    get_the_title()
		─ Address: _loc_address + _loc_bairro + _loc_city
		═══════════════════════════════════════════════════════════════ -->
            <section class="av2-loc-hero">
                <div class="av2-loc-hero__bg">
                    <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($loc_name); ?>" loading="eager">
                </div>
                <div class="av2-loc-hero__overlay"></div>
                <div class="av2-loc-hero__content">
                    <h1 class="av2-loc-hero__name av2-title-word"><?php echo esc_html(strtoupper($loc_name)); ?></h1>
                    <?php if ($address || $bairro || $city) : ?>
                        <p class="av2-loc-hero__address">
                            <?php
                            $parts = array_filter(array($address, $bairro, $city));
                            echo esc_html(implode(' — ', $parts));
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════
		BODY — 2-Column Layout
		Left:  Description + Gallery + Events
		Right: Info card + Map + Directions
		═══════════════════════════════════════════════════════════════ -->
            <div class="av2-container av2-loc-body">

                <!-- ═══ LEFT: Main Content ═══ -->
                <div class="av2-loc-main">

                    <!-- Description → the_content() -->
                    <div class="av2-bio av2-reveal">
                        <?php the_content(); ?>
                    </div>

                    <!-- ─── Venue Gallery ───────────────────────────────────
				Source: _loc_gallery (array of attachment IDs)
				Fallback: Section hidden if empty
				─────────────────────────────────────────────────── -->
                    <?php if (! empty($gallery_images)) : ?>
                        <div class="av2-gallery av2-reveal">
                            <?php foreach ($gallery_images as $idx => $img_url) : ?>
                                <div class="av2-gallery__item<?php echo $idx === 2 ? ' av2-gallery__item--wide' : ''; ?>">
                                    <img src="<?php echo esc_url($img_url); ?>" class="av2-gallery__img av2-reveal-img"
                                        alt="<?php echo esc_attr($loc_name . ' — ' . ($idx + 1)); ?>" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ─── Upcoming Events at this Venue ───────────────────
				Query: event CPT WHERE _event_loc_id = this loc ID
						AND _event_start_date >= today
				Each card links to single-event.php
				─────────────────────────────────────────────────── -->
                    <?php if (! empty($upcoming_events)) : ?>
                        <div class="av2-reveal">
                            <h3 class="av2-section-title"><?php esc_html_e('Próximos Eventos', 'apollo-events'); ?></h3>
                            <div class="av2-loc-events__grid">
                                <?php foreach ($upcoming_events as $evt) : ?>
                                    <a href="<?php echo esc_url($evt['permalink']); ?>" class="av2-event-mini">
                                        <div class="av2-event-mini__date">
                                            <span class="av2-event-mini__day"><?php echo esc_html($evt['parsed']['day']); ?></span>
                                            <span
                                                class="av2-event-mini__month"><?php echo esc_html(strtoupper($evt['parsed']['month_pt'])); ?></span>
                                        </div>
                                        <div class="av2-event-mini__info">
                                            <div class="av2-event-mini__title"><?php echo esc_html($evt['title']); ?></div>
                                            <div class="av2-event-mini__meta">
                                                <?php
                                                if ($evt['time']) {
                                                    echo esc_html($evt['time']);
                                                }
                                                ?>
                                                <?php
                                                if ($evt['dj_names']) {
                                                    echo ' · ' . esc_html($evt['dj_names']);
                                                }
                                                ?>
                                            </div>
                                            <?php if (! empty($evt['sounds'])) : ?>
                                                <div class="av2-event-mini__sounds">
                                                    <?php foreach (array_slice($evt['sounds'], 0, 3) as $s) : ?>
                                                        <span class="av2-event-mini__sound"><?php echo esc_html($s); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    /**
                     * Hook: apollo_loc_single_after_content
                     */
                    do_action('apollo_loc_single_after_content', $post_id);
                    ?>

                </div>

                <!-- ═══ RIGHT: Sidebar ═══ -->
                <aside class="av2-loc-aside">

                    <!-- ─── Venue Info Card ─────────────────────────────────
				Address:   _loc_address
				Bairro:    _loc_bairro (new meta)
				City:      _loc_city
				Capacity:  _loc_capacity (new meta)
				Phone:     _loc_phone (new meta)
				Instagram: _loc_instagram (new meta)
				Website:   _loc_website (new meta)
				─────────────────────────────────────────────────── -->
                    <div class="av2-loc-info-card">
                        <?php if ($address) : ?>
                            <div class="av2-loc-info-card__row">
                                <span class="av2-loc-info-card__label"><?php esc_html_e('Endereço', 'apollo-events'); ?></span>
                                <span class="av2-loc-info-card__value"><?php echo esc_html($address); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($bairro) : ?>
                            <div class="av2-loc-info-card__row">
                                <span class="av2-loc-info-card__label"><?php esc_html_e('Bairro', 'apollo-events'); ?></span>
                                <span class="av2-loc-info-card__value"><?php echo esc_html($bairro); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($city) : ?>
                            <div class="av2-loc-info-card__row">
                                <span class="av2-loc-info-card__label"><?php esc_html_e('Cidade', 'apollo-events'); ?></span>
                                <span class="av2-loc-info-card__value"><?php echo esc_html($city); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($capacity) : ?>
                            <div class="av2-loc-info-card__row">
                                <span
                                    class="av2-loc-info-card__label"><?php esc_html_e('Capacidade', 'apollo-events'); ?></span>
                                <span class="av2-loc-info-card__value"><?php echo esc_html($capacity); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($phone) : ?>
                            <div class="av2-loc-info-card__row">
                                <span class="av2-loc-info-card__label"><?php esc_html_e('Telefone', 'apollo-events'); ?></span>
                                <span class="av2-loc-info-card__value">
                                    <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($instagram) : ?>
                            <div class="av2-loc-info-card__row">
                                <span class="av2-loc-info-card__label">Instagram</span>
                                <span class="av2-loc-info-card__value">
                                    <a href="https://instagram.com/<?php echo esc_attr($instagram); ?>" target="_blank"
                                        rel="noopener">
                                        @<?php echo esc_html($instagram); ?>
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($website) : ?>
                            <div class="av2-loc-info-card__row">
                                <span class="av2-loc-info-card__label">Website</span>
                                <span class="av2-loc-info-card__value">
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html(wp_parse_url($website, PHP_URL_HOST)); ?>
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ─── Map ─────────────────────────────────────────────
				Source: _loc_lat + _loc_lng
				OpenStreetMap embed with marker
				Google Maps directions form below
				─────────────────────────────────────────────────── -->
                    <?php if ($lat && $lng) : ?>
                        <div class="av2-loc-map-full" id="a-eve-single-map" data-lat="<?php echo esc_attr($lat); ?>"
                            data-lng="<?php echo esc_attr($lng); ?>" data-title="<?php echo esc_attr($loc_name); ?>">
                            <iframe src="https://www.openstreetmap.org/export/embed.html?bbox=
					<?php
                        echo esc_attr(($lng - 0.025) . ',' . ($lat - 0.025) . ',' . ($lng + 0.025) . ',' . ($lat + 0.025));
                    ?>
					&layer=mapnik&marker=<?php echo esc_attr($lat . ',' . $lng); ?>" width="100%" height="100%" frameborder="0"
                                loading="lazy">
                            </iframe>
                        </div>

                        <form class="av2-directions" action="https://www.google.com/maps/dir/" target="_blank">
                            <input type="text" name="saddr"
                                placeholder="<?php esc_attr_e('De onde você vem?', 'apollo-events'); ?>" class="apollo-input"
                                class="av2-directions__input">
                            <input type="hidden" name="daddr" value="<?php echo esc_attr($lat . ',' . $lng); ?>">
                            <button type="submit" class="av2-directions__go"><i class="ri-guide-line"></i></button>
                        </form>
                    <?php endif; ?>

                    <?php
                    /**
                     * Hook: apollo_loc_single_sidebar
                     */
                    do_action('apollo_loc_single_sidebar', $post_id);
                    ?>

                </aside>

            </div>

            <?php do_action('apollo_loc_single_after', $post_id); ?>

            <!-- ═══ FOOTER VISUAL ═══ -->
            <div class="av2-footer-visual">
                <video class="av2-footer-video" autoplay muted loop playsinline poster="<?php echo esc_url($photo); ?>">
                    <source src="https://assets.apollo.rio.br/vid/luz.mp4" type="video/mp4">
                </video>
                <div class="av2-footer-text">
                    <h2><?php echo esc_html(strtoupper($loc_name)); ?></h2>
                </div>
            </div>

        </div>

        <!-- GSAP already loaded by CDN core.js (v3.14.2) -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof gsap === 'undefined') return;
                gsap.registerPlugin(ScrollTrigger);

                var tl = gsap.timeline();
                tl.to('.av2-loader', {
                    scaleY: 0,
                    duration: 1,
                    ease: 'power4.inOut'
                });
                tl.to('.av2-title-word', {
                    y: '0%',
                    duration: 1.2,
                    ease: 'power3.out'
                }, '-=0.5');

                gsap.utils.toArray('.av2-reveal').forEach(function(el) {
                    gsap.from(el, {
                        scrollTrigger: {
                            trigger: el,
                            start: 'top 85%'
                        },
                        y: 50,
                        opacity: 0,
                        duration: 1,
                        ease: 'power3.out'
                    });
                });

                gsap.utils.toArray('.av2-gallery__img').forEach(function(img) {
                    gsap.fromTo(img, {
                        scale: 1.2
                    }, {
                        scale: 1,
                        ease: 'none',
                        scrollTrigger: {
                            trigger: img,
                            start: 'top bottom',
                            end: 'bottom top',
                            scrub: true
                        }
                    });
                });

                gsap.to('.av2-footer-video', {
                    yPercent: 20,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: '.av2-footer-visual',
                        start: 'top bottom',
                        end: 'bottom top',
                        scrub: true
                    }
                });
            });
        </script>

        <?php if (is_user_logged_in()) {
            apollo_render_navbar();
        } ?>

        <?php
        if (function_exists('apollo_render_document_close')) {
            apollo_render_document_close();
        } else {
            echo '</body></html>';
        }
