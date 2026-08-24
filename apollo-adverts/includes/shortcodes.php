<?php

/**
 * Shortcodes Loader
 *
 * Registers all classifieds shortcodes.
 * Adapted from WPAdverts shortcodes.php pattern.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register shortcodes
 * Adapted from WPAdverts add_shortcode calls
 */
function apollo_adverts_register_shortcodes(): void
{
    require_once APOLLO_ADVERTS_DIR . 'includes/shortcodes/apollo-classifieds-list.php';
    require_once APOLLO_ADVERTS_DIR . 'includes/shortcodes/apollo-classified-single.php';
    require_once APOLLO_ADVERTS_DIR . 'includes/shortcodes/apollo-classified-form.php';
    require_once APOLLO_ADVERTS_DIR . 'includes/shortcodes/apollo-marketplace-forms.php';

    add_shortcode('apollo_classifieds', 'apollo_adverts_shortcode_list');
    add_shortcode('apollo_classified', 'apollo_adverts_shortcode_single');
    add_shortcode('apollo_classified_form', 'apollo_adverts_shortcode_form');
    // Migrated from apollo-classifieds (FASE 1 merge)
    add_shortcode('apollo_classifieds_sell_ticket', 'apollo_adverts_shortcode_sell_ticket');
    add_shortcode('apollo_classifieds_rent_space', 'apollo_adverts_shortcode_rent_space');
}
add_action('init', 'apollo_adverts_register_shortcodes', 10);
