<?php

/**
 * Feed — screen layout (PHASE 001)
 *
 * Structural shell of the Feed screen, mirroring the mockup's #view-feed:
 *
 *   .app-main
 *     main.feed-column   → .app-feed   (stream: composer + posts)
 *     aside.sidebar-column → six widget slots
 *
 * Every child is its own template-part so any of them can be reused, replaced
 * or themed independently — that is the whole point of this phase. Widgets
 * declare themselves with [app="app-widget-*"] exactly as the mockup does, so
 * the ported runtime scripts find their mount points unchanged.
 *
 * NO simulated.data.js. Structure and behaviour come from the mockup; every
 * value comes from WordPress via apollo_feed_data().
 *
 * @package Apollo\Templates
 * @since   1.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="app-main">
    <main class="feed-column">
        <?php apollo_plus_part('feed/header'); ?>
        <div class="app-feed">
            <?php apollo_plus_part('feed/composer'); ?>
            <?php apollo_plus_part('feed/stream'); ?>
        </div>
    </main>

    <aside class="sidebar-column" aria-label="<?php esc_attr_e('Destaques', 'apollo-templates'); ?>">
        <?php
        /* Slot names are the contract the widget scripts bind to — keep the
           [app="…"] attribute even though these are server-rendered, so a
           later JS refresh can re-hydrate the same node in place. */
        foreach (array('news', 'events', 'trending', 'nucleos', 'market', 'stats') as $apf_widget) {
            apollo_plus_part('feed/widgets/' . $apf_widget);
        }
        ?>
    </aside>
</div>
