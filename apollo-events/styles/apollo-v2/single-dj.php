<?php

/**
 * Template: Single DJ — Apollo V2 Style
 *
 * DJ profile page with hero photo, bio, sound tags, social links,
 * SoundCloud player, and upcoming events grid.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * FIELD MAPPING — How this template connects to WordPress
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * DJ CPT: "dj" (registered by apollo-core)
 * Rewrite: /dj/{slug}
 *
 * CORE FIELDS:
 *   get_the_title()                 → DJ artist name (hero title)
 *   the_content()                   → Full bio / description
 *   get_the_post_thumbnail_url()    → Main profile photo
 *
 * TAXONOMY — Sound (shared GLOBAL BRIDGE with event CPT):
 *   sound taxonomy                  → Genre tags displayed as pills
 *   Shared with events for cross-referencing
 *
 * DJ META FIELDS:
 *   _dj_soundcloud_url   (string)  → SoundCloud profile/track URL for embed
 *   _dj_instagram        (string)  → Instagram handle (without @)
 *   _dj_website          (string)  → Personal website URL
 *   _dj_city             (string)  → Based in city (e.g. "Rio de Janeiro")
 *   _dj_resident_at      (int)     → Loc CPT ID if resident DJ at a venue
 *
 * CONNECTED DATA:
 *   Events via _event_dj_ids → WP_Query with meta LIKE search
 *   Shows upcoming events where this DJ is in the lineup
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

$dj_page_title = get_the_title() . ' — Apollo::Rio';

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title' => $dj_page_title,
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
        <title><?php echo esc_html($dj_page_title); ?></title>
    <?php
}
    ?>
    </head>

    <body>
        <?php

        $post_id = get_the_ID();
        $dj_name = get_the_title();
        $photo   = get_the_post_thumbnail_url($post_id, 'large')
            ?: (defined('APOLLO_ASSETS_GRAIN_URL') ? APOLLO_ASSETS_GRAIN_URL : 'https://assets.apollo.rio.br/img/bg/grain-001.jpg');

        /* ── Sound taxonomy (shared with events) ────────────────────────────── */
        $sounds = array();
        if (taxonomy_exists(APOLLO_EVENT_TAX_SOUND)) {
            $sounds = wp_get_post_terms($post_id, APOLLO_EVENT_TAX_SOUND, array('fields' => 'names'));
            if (is_wp_error($sounds)) {
                $sounds = array();
            }
        }

        /* ── DJ Meta Fields ─────────────────────────────────────────────────── */
        $soundcloud_url = get_post_meta($post_id, '_dj_soundcloud_url', true);
        $instagram      = get_post_meta($post_id, '_dj_instagram', true);
        $website        = get_post_meta($post_id, '_dj_website', true);
        $city           = get_post_meta($post_id, '_dj_city', true);
        $resident_id    = (int) get_post_meta($post_id, '_dj_resident_at', true);

        /* ── Resident venue lookup ──────────────────────────────────────────── */
        $resident_venue = null;
        if ($resident_id) {
            $res_post = get_post($resident_id);
            if ($res_post && 'publish' === $res_post->post_status) {
                $resident_venue = array(
                    'title'     => $res_post->post_title,
                    'permalink' => get_permalink($resident_id),
                );
            }
        }

        /* ── Upcoming events featuring this DJ ──────────────────────────────── */
        $upcoming_events = array();
        $now             = current_time('Y-m-d');
        $events_query    = new \WP_Query(
            array(
                'post_type'      => APOLLO_EVENT_CPT,
                'post_status'    => 'publish',
                'posts_per_page' => 6,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_event_dj_ids',
                        'value'   => '"' . $post_id . '"',
                        'compare' => 'LIKE',
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
                $e_loc    = apollo_event_get_loc($e_id);
                $e_parsed = apollo_event_parse_date($e_date ?: $now);
                $e_sounds = wp_get_post_terms($e_id, APOLLO_EVENT_TAX_SOUND, array('fields' => 'names'));

                $upcoming_events[] = array(
                    'id'        => $e_id,
                    'title'     => get_the_title(),
                    'permalink' => get_permalink(),
                    'parsed'    => $e_parsed,
                    'time'      => $e_time,
                    'loc_name'  => $e_loc ? $e_loc['title'] : '',
                    'sounds'    => is_wp_error($e_sounds) ? array() : $e_sounds,
                );
            }
            wp_reset_postdata();
        }

        /* ── Enqueue style ──────────────────────────────────────────────────── */
        wp_enqueue_style('apollo-events-v2', APOLLO_EVENT_URL . 'styles/apollo-v2/style.css', array('apollo-events'), APOLLO_EVENT_VERSION);
        ?>

        <!-- ═══════════════════════════════════════════════════════════════════
	APOLLO V2 — SINGLE DJ
	═══════════════════════════════════════════════════════════════════ -->

        <div class="av2-dj" data-dj-id="<?php echo esc_attr($post_id); ?>">

            <!-- ═══ PAGE LOADER ═══ -->
            <div class="av2-loader"></div>

            <!-- ═══════════════════════════════════════════════════════════════
		DJ HERO — Photo + Info
		─ Photo: get_the_post_thumbnail_url()
		─ Name:  get_the_title()
		─ Sounds: sound taxonomy
		─ City:  _dj_city meta
		─ Socials: _dj_instagram, _dj_website, _dj_soundcloud_url
		═══════════════════════════════════════════════════════════════ -->
            <div class="av2-container">
                <div class="av2-dj-hero">

                    <!-- Photo → get_the_post_thumbnail_url( $post_id, 'large' ) -->
                    <div class="av2-dj-photo">
                        <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($dj_name); ?>" loading="eager">
                    </div>

                    <div class="av2-dj-info">

                        <!-- Pills: location + resident -->
                        <div class="av2-hero__meta">
                            <div class="av2-pill av2-pill--status">DJ</div>
                            <?php if ($city) : ?>
                                <div class="av2-pill av2-pill--cat"><?php echo esc_html(strtoupper($city)); ?></div>
                            <?php endif; ?>
                            <?php if ($resident_venue) : ?>
                                <a href="<?php echo esc_url($resident_venue['permalink']); ?>" class="av2-pill av2-pill--cat">
                                    RESIDENT @ <?php echo esc_html(strtoupper($resident_venue['title'])); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Name → get_the_title() -->
                        <h1 class="av2-dj-info__name av2-title-word">
                            <?php echo esc_html(strtoupper($dj_name)); ?>
                        </h1>

                        <!-- Sound tags → sound taxonomy -->
                        <?php if (! empty($sounds)) : ?>
                            <div class="av2-dj-info__sounds">
                                <?php foreach ($sounds as $sound) : ?>
                                    <span class="av2-tag"><?php echo esc_html($sound); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Bio excerpt → the_excerpt() or first paragraph -->
                        <?php if (has_excerpt()) : ?>
                            <div class="av2-dj-info__bio">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Social Links
					_dj_soundcloud_url → SoundCloud icon link
					_dj_instagram      → Instagram icon link
					_dj_website        → Globe icon link
					──────────────────────────────────────────── -->
                        <div class="av2-dj-socials">
                            <?php if ($soundcloud_url) : ?>
                                <a href="<?php echo esc_url($soundcloud_url); ?>" target="_blank" rel="noopener"
                                    class="av2-dj-social-link" title="SoundCloud">
                                    <i class="ri-soundcloud-line"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($instagram) : ?>
                                <a href="https://instagram.com/<?php echo esc_attr($instagram); ?>" target="_blank"
                                    rel="noopener" class="av2-dj-social-link" title="Instagram">
                                    <i class="ri-instagram-line"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($website) : ?>
                                <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener"
                                    class="av2-dj-social-link" title="Website">
                                    <i class="ri-global-line"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════
		DJ CONTENT — Full bio + SoundCloud player
		═══════════════════════════════════════════════════════════════ -->
            <div class="av2-container">

                <!-- Full bio → the_content() -->
                <div class="av2-bio av2-reveal">
                    <?php the_content(); ?>
                </div>

                <!-- ─── SoundCloud Embed ────────────────────────────────────
			Source: _dj_soundcloud_url meta
			Rendered as oEmbed iframe via SoundCloud widget API
			Fallback: Section hidden if empty
			─────────────────────────────────────────────────────── -->
                <?php if ($soundcloud_url) : ?>
                    <div class="av2-dj-player av2-reveal">
                        <iframe
                            src="https://w.soundcloud.com/player/?url=<?php echo esc_attr(urlencode($soundcloud_url)); ?>&color=%23FF9820&auto_play=false&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false"
                            allow="autoplay" loading="lazy">
                        </iframe>
                    </div>
                <?php endif; ?>

            </div>

            <!-- ═══════════════════════════════════════════════════════════════
		UPCOMING EVENTS featuring this DJ
		Query: WP_Query on event CPT where _event_dj_ids LIKE dj_id
		Shows: date, title, venue, sound tags
		Each links to: single-event.php (apollo-v2 or active style)
		═══════════════════════════════════════════════════════════════ -->
            <?php if (! empty($upcoming_events)) : ?>
                <div class="av2-container av2-dj-events">
                    <h3 class="av2-section-title"><?php esc_html_e('Próximos Eventos', 'apollo-events'); ?></h3>
                    <div class="av2-dj-events__grid">
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
                                        if ($evt['loc_name']) {
                                            echo ' · ' . esc_html($evt['loc_name']);
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
             * Hook: apollo_dj_single_after
             * Extensibility point for integrations (Fav, Wow, statistics, etc.)
             */
            do_action('apollo_dj_single_after', $post_id);
            ?>

            <!-- ═══ FOOTER VISUAL ═══ -->
            <div class="av2-footer-visual">
                <video class="av2-footer-video" autoplay muted loop playsinline poster="<?php echo esc_url($photo); ?>">
                    <source src="https://assets.apollo.rio.br/vid/luz.mp4" type="video/mp4">
                </video>
                <div class="av2-footer-text">
                    <h2><?php echo esc_html(strtoupper($dj_name)); ?></h2>
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
                tl.from('.av2-hero__meta, .av2-dj-info__sounds, .av2-dj-socials', {
                    y: 30,
                    opacity: 0,
                    duration: 0.8,
                    stagger: 0.1,
                    ease: 'power2.out'
                }, '-=0.6');

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
