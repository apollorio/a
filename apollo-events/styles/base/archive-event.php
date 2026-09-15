<?php

/**
 * Archive Event — Apollo Blank Canvas
 *
 * Portal de Eventos: Netflix-style promoter calendar (hero slider of
 * highlighted events, sticky month/filter chrome, day-window rails, infinite
 * -load taxonomy browse). Ported from screen/_official_layout's .pev-*
 * mockup — see template-parts/archive/portal-styles.php and
 * portal-scripts.php for the full port notes. This file's job is strictly
 * data: build $portal_events (one row per published event, shaped to match
 * the mockup's APOLLO_EVENTS contract) and hand off to the ported markup/
 * behavior layer.
 *
 * @package Apollo\Event
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
	apollo_event_ensure_helpers();
} elseif ( defined( 'APOLLO_EVENT_DIR' ) && is_readable( APOLLO_EVENT_DIR . 'includes/bootstrap.php' ) ) {
	require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';
}

/* ─── Data Collection ─────────────────────────────────────── */

$event_cpt = defined('APOLLO_EVENT_CPT') ? APOLLO_EVENT_CPT : 'event';
$tax_sound = defined('APOLLO_EVENT_TAX_SOUND') ? APOLLO_EVENT_TAX_SOUND : 'sound';
$tax_cat   = defined('APOLLO_EVENT_TAX_CATEGORY') ? APOLLO_EVENT_TAX_CATEGORY : 'event_category';
$tax_tag   = defined('APOLLO_EVENT_TAX_TAG') ? APOLLO_EVENT_TAX_TAG : 'event_tag';
$tax_type  = defined('APOLLO_EVENT_TAX_TYPE') ? APOLLO_EVENT_TAX_TYPE : 'event_type';

// Custom query — load ALL published events; the portal filters/paginates
// entirely client-side (mode chrome, month cursor, tax chips, day-window
// rails), exactly like the mockup it's ported from.
$events_query = new WP_Query(
    array(
        'post_type'      => $event_cpt,
        'posts_per_page' => 200,
        'post_status'    => 'publish',
        'meta_key'       => '_event_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    )
);

$portal_events = array();
$today_stamp   = current_time('Y-m-d');

if ($events_query->have_posts()) {
    while ($events_query->have_posts()) {
        $events_query->the_post();
        $eid     = get_the_ID();
        $is_gone = function_exists('apollo_event_is_gone') ? apollo_event_is_gone($eid) : false;

        // Optionally skip gone events (site-wide option, same gate the old
        // archive template used).
        if ($is_gone && function_exists('apollo_event_option') && ! apollo_event_option('show_gone_events', true)) {
            continue;
        }

        $start_date = get_post_meta($eid, '_event_start_date', true) ?: $today_stamp;
        $loc_data   = function_exists('apollo_event_get_loc') ? apollo_event_get_loc($eid) : null;
        $djs_data   = function_exists('apollo_event_get_djs') ? apollo_event_get_djs($eid) : array();

        // Season (BR calendar quarter) — real events carry no hand-authored
        // "season" tag like the mockup's demo fixtures (summer/winter/
        // rockinrio/nye/carnival). This mirrors portal-data.js's own
        // seasonBanner() calendar fallback so every event still lands in a
        // taxChip/season bucket. 'summer'/'winter' reuse the mockup's own
        // keys; 'outono'/'primavera' are the two extra SEASON_LABEL entries
        // added in portal-scripts.php (delta #1, see that file's header).
        $month_num = (int) date('n', strtotime($start_date));
        if (12 === $month_num || $month_num <= 2) {
            $season = 'summer';
        } elseif ($month_num <= 5) {
            $season = 'outono';
        } elseif ($month_num <= 8) {
            $season = 'winter';
        } else {
            $season = 'primavera';
        }

        $lineup = array();
        foreach ($djs_data as $dj) {
            if (! empty($dj['title'])) {
                $lineup[] = array( 'name' => $dj['title'] );
            }
        }

        $status = get_post_meta($eid, '_event_status', true) ?: 'scheduled';

        // Shape matches window.APOLLO_EVENTS per event exactly as consumed by
        // the ported portal-data.js / app-portal-eventos.js (id, title,
        // cover, startDate, startTime, venue{name,address}, lineup[{name}],
        // genres[], season, tickets, ticketsUrl, highlight, status, about,
        // url). "url" is a WP-side addition — see portal-scripts.php delta #2.
        $portal_events[] = array(
            'id'         => $eid,
            'title'      => get_the_title(),
            'url'        => get_permalink(),
            'cover'      => function_exists('apollo_event_get_banner')
                ? apollo_event_get_banner($eid)
                : (get_the_post_thumbnail_url($eid, 'large') ?: ''),
            'startDate'  => $start_date,
            'startTime'  => get_post_meta($eid, '_event_start_time', true),
            'venue'      => array(
                'name'    => $loc_data['title'] ?? '',
                'address' => $loc_data['address'] ?? '',
            ),
            'lineup'     => $lineup,
            'genres'     => wp_get_post_terms($eid, $tax_sound, array('fields' => 'slugs')),
            // Real editorial taxonomies. The card's visible tag pills come
            // from these -- assigned by a human in wp-admin -- never from a
            // defaulted meta value.
            'tags'       => array_values(array_filter(array_merge(
                (array) wp_get_post_terms($eid, $tax_tag, array('fields' => 'names')),
                (array) wp_get_post_terms($eid, $tax_type, array('fields' => 'names')),
                (array) wp_get_post_terms($eid, $tax_cat, array('fields' => 'names'))
            ), 'is_string')),
            'season'     => $season,
            // BUGFIX (phase 002 review): this used to be `?: 'available'`, so an
            // event with NO ticket status set silently rendered a "Disponível"
            // chip. That is a claim about ticket availability the site has no
            // basis for -- it is not a taxonomy value, it is a default that
            // looked like data. Empty now stays empty and no chip is drawn.
            // (See the ticket-status note in the docblock above: this is the
            // _event_ticket_status meta, NOT event_tag/event_type/
            // event_category, which are editorial taxonomies and carry no
            // availability meaning.)
            'tickets'    => (string) get_post_meta($eid, '_event_ticket_status', true),
            'ticketsUrl' => get_post_meta($eid, '_event_ticket_url', true),
            'highlight'  => '1' === get_post_meta($eid, '_event_highlighted', true),
            'status'     => $status,
            'about'      => get_the_excerpt(),
        );
    }
    wp_reset_postdata();
}

$rest_base = esc_url(rest_url('apollo/v1/'));
// Public archive: only authenticated users get a live nonce (guests can't mutate, and the
// page may be cached) — never bake a nonce into guest-visible HTML.
$nonce     = is_user_logged_in() ? wp_create_nonce('wp_rest') : '';

/* ─── Render ───────────────────────────────────────────────────────────────
   PHASE 002: this screen no longer owns a document. It used to call
   apollo_render_document_open(), print its own <body>, and render its own
   navbar — which meant /eventos had no .ax-aside at all and duplicated shell
   logic that now lives in exactly one place. It is now a MOUNT POINT on the
   Blank Canvas Apollo+ shell, identical in kind to /feed (phase 001):
   apollo_plus_open() supplies <head> + topbar + aside + <main class="ax-main">.

   Falls back to the old self-hosted document only if apollo-templates is
   inactive, so the events archive can never hard-fail on a missing shell. */

/* Provenance marker — phase 002.
   TemplateLoader::locate() resolves archive-event.php theme-first:
     1. wp-content/themes/{child}/apollo-events/{style}/archive-event.php
     2. wp-content/themes/{parent}/apollo-events/{style}/archive-event.php
     3. plugins/apollo-events/styles/{style}/archive-event.php
     4. plugins/apollo-events/styles/base/archive-event.php   <- this file
   If a theme ships an override, or the active style option points at a pack
   other than 'base', THIS FILE IS NEVER REACHED and edits here have no
   effect. That failure is silent, which is exactly how it hides. The comment
   below is emitted into the page source so "which file actually rendered"
   is answerable by viewing source instead of guessing. */
printf(
    "<!-- apollo-events %s | template: styles/base/archive-event.php | style: %s | portal: %s -->\n",
    esc_html(defined('APOLLO_EVENT_VERSION') ? APOLLO_EVENT_VERSION : '?'),
    esc_html(function_exists('apollo_event_get_active_style') ? apollo_event_get_active_style() : '?'),
    esc_html(get_query_var('apollo_event_page') ?: 'archive')
);

$parts = __DIR__ . '/template-parts/archive/';

ob_start();
?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Shrikhand&display=swap" rel="stylesheet">
    <?php require $parts . 'portal/styles.php'; ?>
<?php
$extra_head = ob_get_clean();

// Portal context: same listing served at /portal + /portal/eventos (public promoter hub).
$is_portal  = ! empty($apollo_portal_context) || 'portal_archive' === get_query_var('apollo_event_page');
$page_title = $is_portal
    ? 'Eventos no Rio | Explore, Descubra e Celebre | Apollo'
    : 'Eventos — ' . get_bloginfo('name');

/*
 * Portal SEO — emit the share-card block from this template.
 * apollo-seo virtual_context historically matched apollo_event_page=portal
 * while this route registers portal_archive, so blank-canvas head fell
 * through to homepage canonical + default thumb. Owning the tags here
 * (and suppressing apollo/seo/head for this request) guarantees WhatsApp /
 * OG / Twitter see /eventos/ + thumb-eventos.webp even if Meta.php sync lags.
 */
if ( $is_portal ) {
	remove_all_actions( 'apollo/seo/head' );
	$portal_thumb = esc_url( content_url( 'uploads/2026/09/thumb-eventos.webp' ) );
	$portal_canon = esc_url( home_url( '/eventos/' ) );
	$portal_seo   = "\n<!-- SEO: Portal de Eventos -->\n"
		. '<meta name="description" content="Explore eventos no Rio de Janeiro: festas, shows, festivais, cultura e experiências para viver a cidade do seu jeito.">' . "\n"
		. '<link rel="canonical" href="' . $portal_canon . '">' . "\n"
		. '<meta name="robots" content="index, follow">' . "\n"
		. '<meta name="theme-color" content="#0B0B0D">' . "\n"
		. '<meta property="og:type" content="website">' . "\n"
		. '<meta property="og:locale" content="pt_BR">' . "\n"
		. '<meta property="og:site_name" content="Apollo Rio">' . "\n"
		. '<meta property="og:url" content="' . $portal_canon . '">' . "\n"
		. '<meta property="og:title" content="Portal de Eventos no Rio | Explore, Descubra e Celebre">' . "\n"
		. '<meta property="og:description" content="Festas, shows, festivais, cultura e experiências. Descubra o que move o Rio, em um só lugar.">' . "\n"
		. '<meta property="og:image" content="' . $portal_thumb . '">' . "\n"
		. '<meta property="og:image:secure_url" content="' . $portal_thumb . '">' . "\n"
		. '<meta property="og:image:type" content="image/webp">' . "\n"
		. '<meta property="og:image:width" content="1200">' . "\n"
		. '<meta property="og:image:height" content="630">' . "\n"
		. '<meta property="og:image:alt" content="Apollo Rio — Portal de Eventos">' . "\n"
		. '<meta name="twitter:card" content="summary_large_image">' . "\n"
		. '<meta name="twitter:title" content="Portal de Eventos no Rio | Explore, Descubra e Celebre">' . "\n"
		. '<meta name="twitter:description" content="Festas, shows, festivais, cultura e experiências. Descubra o que move o Rio, em um só lugar.">' . "\n"
		. '<meta name="twitter:image" content="' . $portal_thumb . '">' . "\n"
		. '<meta name="twitter:image:alt" content="Apollo Rio — Portal de Eventos">' . "\n"
		. '<script type="application/ld+json">'
		. wp_json_encode(
			array(
				'@context'    => 'https://schema.org',
				'@type'       => 'CollectionPage',
				'name'        => 'Portal de Eventos no Rio',
				'description' => 'Explore eventos no Rio de Janeiro: festas, shows, festivais, cultura e experiências para viver a cidade do seu jeito.',
				'url'         => home_url( '/eventos/' ),
				'inLanguage'  => 'pt-BR',
				'isPartOf'    => array(
					'@type' => 'WebSite',
					'name'  => 'Apollo Rio',
					'url'   => home_url( '/' ),
				),
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
		)
		. '</script>' . "\n";
	$extra_head = $portal_seo . $extra_head;
}

$use_plus = function_exists('apollo_plus_open');

if ($use_plus) {
    apollo_plus_open(
        array(
            'title'       => $page_title,
            'extra_head'  => $extra_head,
            'screen'      => 'eventos',
            'theme_color' => $is_portal ? '#0B0B0D' : '',
            'skip_seo'    => $is_portal,
        )
    );
} else {
    // Legacy standalone document — only reachable if apollo-templates is off.
    if (function_exists('apollo_render_document_open')) {
        apollo_render_document_open(
            array(
                'title'       => $page_title,
                'extra_head'  => $extra_head,
                'theme_color' => $is_portal ? '#0B0B0D' : '',
                'skip_seo'    => $is_portal,
            )
        );
    }
    echo '</head><body>';
    if (function_exists('apollo_get_navbar')) {
        apollo_get_navbar();
    }
    echo '<main class="ax-main">';
}
?>

    <?php
    /* LISTING HEADER (1.7.1) — the screen's own chrome, server-rendered.
       This is the approved "Header 02 · Kinetic Mask · Apple" from
       screen/portal/header listing/header-of-listing-events.html, shipped as
       the reusable apollo_listing_header() block in apollo-templates.

       It sits OUTSIDE #apollo-portal-root on purpose. That div is wiped and
       rewritten by app.php's skeleton() on every mount; anything inside it
       cannot exist before the runtime boots. The header must be in the first
       paint — it is the month identity of the page — so it is a sibling, and
       header-bridge.php below joins the two by CustomEvent.

       This replaced .pev-masthead, which app.php used to build in JavaScript. */
    require $parts . 'portal/header.php';
    ?>

    <!-- Portal de Eventos root — markup is built client-side by the ported
         app-portal-eventos.js (skeleton() + renderAll()), exactly like the
         mockup. Data arrives as window.APOLLO_EVENTS from portal/data.php;
         this page's job stops at handing over correctly-shaped JSON. -->
    <div id="apollo-portal-root"
        class="pev-host"
        data-rest="<?php echo esc_attr($rest_base); ?>"
        data-nonce="<?php echo esc_attr($nonce); ?>"
        data-today="<?php echo esc_attr($today_stamp); ?>"
        <?php
        /* Hero empty-state visual, served SAME-ORIGIN through the proxy in
           includes/iframe-proxy.php. Embedding assets.apollo.rio.br directly
           was refused by the browser because that host sends
           X-Frame-Options: SAMEORIGIN — see the note in portal/app.php. */
        if (function_exists('apollo_event_iframe_proxy_url')) :
            ?>
        data-iframe-fallback="<?php echo esc_attr(apollo_event_iframe_proxy_url('highlighted-fallback')); ?>"
        <?php endif; ?>></div>

    <?php require $parts . 'portal/scripts.php'; ?>

    <?php
    /* Header ↔ portal glue. AFTER scripts.php so window.AppPortalEventos
       already exists when the bridge binds its listeners. */
    require $parts . 'portal/header-bridge.php';
    ?>

    <?php
    /* Event cards carry data-ev-open="{id}" — this boots the single-event
       lightbox so a card opens the full event page in place. */
    if (function_exists('apollo_event_lightbox_boot')) {
        apollo_event_lightbox_boot();
    }
    ?>

<?php
if ($use_plus) {
    apollo_plus_close();
} else {
    echo '</main>';
    if (function_exists('apollo_render_document_close')) {
        apollo_render_document_close();
    } else {
        echo '</body></html>';
    }
}
