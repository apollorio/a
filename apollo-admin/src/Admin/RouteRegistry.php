<?php
/**
 * Route Registry
 *
 * Maps every CPanel field key (as used in name="apollo[key]" attributes
 * in the section partials) to a real WordPress option and its sanitisation type.
 *
 * cpanel_key names match the actual name="apollo[KEY]" attributes in the
 * section partials. Other plugins extend this via
 * add_filter('apollo_admin_route_map', …).
 *
 * @package Apollo\Admin\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class RouteRegistry {

    /**
     * Build the full map.
     *
     * Format: 'cpanel_key' => ['option' => 'wp_option_name', 'type' => 'text|bool|…', 'default' => …]
     *
     * @return array<string,array{option:string,type:string,default:mixed}>
     */
    public static function build(): array {
        $map = [
            /* ── SYSTEM / GLOBAL (overview.php) ───────────────────── */
            'site_name'        => [ 'option' => 'blogname',            'type' => 'text',   'default' => '' ],
            'site_description' => [ 'option' => 'blogdescription',     'type' => 'text',   'default' => '' ],
            'language'         => [ 'option' => 'WPLANG',              'type' => 'select', 'default' => 'pt_BR' ],
            'timezone'         => [ 'option' => 'timezone_string',     'type' => 'text',   'default' => 'America/Sao_Paulo' ],
            'cdn_url'          => [ 'option' => 'apollo_cdn_url',      'type' => 'url',    'default' => '' ],
            'debug_mode'       => [ 'option' => 'apollo_debug_mode',   'type' => 'bool',   'default' => false ],
            'gmaps_key'        => [ 'option' => 'apollo_gmaps_key',    'type' => 'text',   'default' => '' ],
            'recaptcha_key'    => [ 'option' => 'apollo_recaptcha_site_key',   'type' => 'text', 'default' => '' ],
            'recaptcha_secret' => [ 'option' => 'apollo_recaptcha_secret_key', 'type' => 'text', 'default' => '' ],
            'recaptcha_on_login' => [ 'option' => 'apollo_recaptcha_on_login', 'type' => 'bool', 'default' => true ],

            /* ── SYSTEM CORE (system/core.php actual keys) ─────────── */
            'core_cdn_url'     => [ 'option' => 'apollo_cdn_url',      'type' => 'url',    'default' => '' ],
            'core_debug'       => [ 'option' => 'apollo_debug_mode',   'type' => 'bool',   'default' => false ],

            /* ── SYSTEM SECURITY (system/security.php actual keys) ─── */
            'sec_lockout_attempts' => [ 'option' => 'apollo_max_login_attempts', 'type' => 'int',  'default' => 5 ],
            'sec_lockout_duration' => [ 'option' => 'apollo_lockout_duration',   'type' => 'int',  'default' => 15 ],

            /* ── IDENTITY / LOGIN — plugin-side keys (future) ──────── */
            'login_slug'              => [ 'option' => 'apollo_login_slug',          'type' => 'text',  'default' => 'acesso' ],
            'login_logo_url'          => [ 'option' => 'apollo_login_logo_url',      'type' => 'url',   'default' => '' ],
            'login_background_color'  => [ 'option' => 'apollo_login_bg_color',      'type' => 'color', 'default' => '#0f0f0f' ],
            'login_primary_color'     => [ 'option' => 'apollo_login_primary_color', 'type' => 'color', 'default' => '#ff6b35' ],
            'firewall_enabled'        => [ 'option' => 'apollo_firewall_enabled',    'type' => 'bool',  'default' => true ],
            'ghost_mode'              => [ 'option' => 'apollo_ghost_mode_enabled',  'type' => 'bool',  'default' => true ],
            'max_login_attempts'      => [ 'option' => 'apollo_max_login_attempts',  'type' => 'int',   'default' => 5 ],
            'lockout_duration'        => [ 'option' => 'apollo_lockout_duration',    'type' => 'int',   'default' => 30 ],

            /* ── LOGIN (login.php actual keys) ─────────────────────── */
            'login_max_attempts'    => [ 'option' => 'apollo_max_login_attempts', 'type' => 'int', 'default' => 5 ],
            'login_lockout_duration'=> [ 'option' => 'apollo_lockout_duration',   'type' => 'int', 'default' => 15 ],

            /* ── EMAIL / SMTP (email/settings.php actual keys) ──────── */
            'email_from_name'    => [ 'option' => 'apollo_email_from_name',      'type' => 'text',  'default' => 'Apollo Rio' ],
            'email_from_email'   => [ 'option' => 'apollo_email_from_address',   'type' => 'email', 'default' => '' ],
            'email_smtp_host'    => [ 'option' => 'apollo_email_smtp_host',      'type' => 'text',  'default' => '' ],
            'email_smtp_port'    => [ 'option' => 'apollo_email_smtp_port',      'type' => 'int',   'default' => 587 ],
            'email_smtp_user'    => [ 'option' => 'apollo_email_smtp_user',      'type' => 'email', 'default' => '' ],

            /* ── EMAIL — plugin-side alias keys (future) ────────────── */
            'smtp_host'       => [ 'option' => 'apollo_email_smtp_host',      'type' => 'text',  'default' => '' ],
            'smtp_port'       => [ 'option' => 'apollo_email_smtp_port',      'type' => 'int',   'default' => 587 ],
            'smtp_user'       => [ 'option' => 'apollo_email_smtp_user',      'type' => 'email', 'default' => '' ],
            'smtp_from'       => [ 'option' => 'apollo_email_from_address',   'type' => 'email', 'default' => '' ],
            'smtp_from_name'  => [ 'option' => 'apollo_email_from_name',      'type' => 'text',  'default' => '' ],
            'smtp_encryption' => [ 'option' => 'apollo_email_smtp_encryption','type' => 'select','default' => 'tls' ],

            /* ── MEMBERSHIP ──────────────────────────────────────────── */
            'membership_default'        => [ 'option' => 'apollo_membership_default',             'type' => 'select', 'default' => 'nao-verificado' ],
            'membership_trial_days'     => [ 'option' => 'apollo_membership_trial_days',          'type' => 'int',    'default' => 7 ],
            'membership_require_verify' => [ 'option' => 'apollo_membership_require_email_verify', 'type' => 'bool',  'default' => true ],
            'membership_agent_enabled'  => [ 'option' => 'apollo_membership_agent_enabled',       'type' => 'bool',   'default' => false ],

            /* ── EVENTS ──────────────────────────────────────────────── */
            'events_per_page'      => [ 'option' => 'apollo_events_per_page',       'type' => 'int',   'default' => 12 ],
            'events_archive_slug'  => [ 'option' => 'apollo_events_archive_slug',   'type' => 'text',  'default' => 'eventos' ],
            'events_date_format'   => [ 'option' => 'apollo_events_date_format',    'type' => 'text',  'default' => 'd/m/Y' ],
            'events_show_past'     => [ 'option' => 'apollo_events_show_past',      'type' => 'bool',  'default' => false ],
            'events_map_enabled'   => [ 'option' => 'apollo_events_map_enabled',    'type' => 'bool',  'default' => true ],
            'events_primary_color' => [ 'option' => 'apollo_events_primary_color',  'type' => 'color', 'default' => '#ff6b35' ],
            'events_card_radius'   => [ 'option' => 'apollo_events_card_radius',    'type' => 'int',   'default' => 12 ],

            /* ── RADIO ───────────────────────────────────────────────── */
            'radio_stream_url'     => [ 'option' => 'apollo_radio_stream_url',     'type' => 'url',  'default' => '' ],
            'radio_default_volume' => [ 'option' => 'apollo_radio_default_volume', 'type' => 'int',  'default' => 80 ],
            'radio_show_metadata'  => [ 'option' => 'apollo_radio_show_metadata',  'type' => 'bool', 'default' => true ],
            'radio_autoplay'       => [ 'option' => 'apollo_radio_autoplay',       'type' => 'bool', 'default' => false ],

            /* ── SEO ─────────────────────────────────────────────────── */
            'seo_title_separator'  => [ 'option' => 'apollo_seo_title_separator',  'type' => 'text', 'default' => '·' ],
            'seo_og_image'         => [ 'option' => 'apollo_seo_og_image',          'type' => 'url',  'default' => '' ],
            'seo_twitter_handle'   => [ 'option' => 'apollo_seo_twitter_handle',   'type' => 'text', 'default' => '' ],
            'seo_noindex_archives' => [ 'option' => 'apollo_seo_noindex_archives', 'type' => 'bool', 'default' => false ],

            /* ── NOTIFICATIONS (social/notif.php actual keys) ────────── */
            'notif_push'          => [ 'option' => 'apollo_notif_push_enabled',  'type' => 'bool', 'default' => false ],
            'notif_digest'        => [ 'option' => 'apollo_notif_email_enabled', 'type' => 'bool', 'default' => true ],
            'notif_vapid_public'  => [ 'option' => 'apollo_notif_vapid_public',  'type' => 'text', 'default' => '' ],
            'notif_vapid_private' => [ 'option' => 'apollo_notif_vapid_private', 'type' => 'text', 'default' => '' ],

            /* ── NOTIFICATIONS — plugin-side alias keys (future) ─────── */
            'notif_push_enabled'  => [ 'option' => 'apollo_notif_push_enabled',  'type' => 'bool', 'default' => false ],
            'notif_email_enabled' => [ 'option' => 'apollo_notif_email_enabled', 'type' => 'bool', 'default' => true ],

            /* ── EVENTS: General (events/general.php actual keys) ─────── */
            'evt_hide_calendars'         => [ 'option' => 'apollo_evt_hide_calendars',         'type' => 'bool', 'default' => false ],
            'evt_remove_meta'            => [ 'option' => 'apollo_evt_remove_meta',             'type' => 'bool', 'default' => false ],
            'evt_enable_rtl'             => [ 'option' => 'apollo_evt_enable_rtl',              'type' => 'bool', 'default' => false ],
            'evt_hide_shortcode_btn'     => [ 'option' => 'apollo_evt_hide_shortcode_btn',       'type' => 'bool', 'default' => false ],

            /* ── EVENTS: Calendar (events/calendar.php actual keys) ───── */
            'evt_hide_nav_arrows'          => [ 'option' => 'apollo_evt_hide_nav_arrows',          'type' => 'bool', 'default' => false ],
            'evt_align_arrows_right'       => [ 'option' => 'apollo_evt_align_arrows_right',       'type' => 'bool', 'default' => false ],
            'evt_override_featured_color'  => [ 'option' => 'apollo_evt_override_featured_color',  'type' => 'bool', 'default' => false ],

            /* ── EVENTS: Maps (events/maps.php actual keys) ───────────── */
            'maps_disable_scroll'  => [ 'option' => 'apollo_maps_disable_scroll',  'type' => 'bool',   'default' => true ],
            'maps_logged_only'     => [ 'option' => 'apollo_maps_logged_only',     'type' => 'bool',   'default' => false ],
            'maps_auto_generate'   => [ 'option' => 'apollo_maps_auto_generate',   'type' => 'bool',   'default' => true ],
            'maps_display_type'    => [ 'option' => 'apollo_maps_display_type',    'type' => 'select', 'default' => 'roadmap' ],
            'maps_zoom'            => [ 'option' => 'apollo_maps_zoom',            'type' => 'select', 'default' => '14' ],
            'maps_style'           => [ 'option' => 'apollo_maps_style',           'type' => 'select', 'default' => 'default' ],
            'maps_marker_icon'     => [ 'option' => 'apollo_maps_marker_icon',     'type' => 'url',    'default' => '' ],

            /* ── EVENTS: Theme & Colors (events/theme.php actual keys) ── */
            'evt_theme'                     => [ 'option' => 'apollo_evt_theme',                     'type' => 'select', 'default' => 'default' ],
            'evt_font_primary'              => [ 'option' => 'apollo_evt_font_primary',              'type' => 'text',   'default' => 'Space Grotesk' ],
            'evt_font_secondary'            => [ 'option' => 'apollo_evt_font_secondary',            'type' => 'text',   'default' => 'Space Mono' ],
            'evt_theme_header_month'        => [ 'option' => 'apollo_evt_theme_header_month',        'type' => 'color',  'default' => '#121214' ],
            'evt_theme_cal_date'            => [ 'option' => 'apollo_evt_theme_cal_date',            'type' => 'color',  'default' => '#121214' ],
            'evt_theme_sort_text'           => [ 'option' => 'apollo_evt_theme_sort_text',           'type' => 'color',  'default' => '#71717a' ],
            'evt_theme_jump_trigger'        => [ 'option' => 'apollo_evt_theme_jump_trigger',        'type' => 'color',  'default' => '#121214' ],
            'evt_theme_jumper_btn'          => [ 'option' => 'apollo_evt_theme_jumper_btn',          'type' => 'color',  'default' => '#e4e4e7' ],
            'evt_theme_jumper_current'      => [ 'option' => 'apollo_evt_theme_jumper_current',      'type' => 'color',  'default' => 'FF9820' ],
            'evt_theme_jumper_active'       => [ 'option' => 'apollo_evt_theme_jumper_active',       'type' => 'color',  'default' => 'FF9820' ],
            'evt_theme_month_btn'           => [ 'option' => 'apollo_evt_theme_month_btn',           'type' => 'color',  'default' => '#121214' ],
            'evt_theme_arrow_circle'        => [ 'option' => 'apollo_evt_theme_arrow_circle',        'type' => 'color',  'default' => '#e4e4e7' ],
            'evt_theme_cal_loader'          => [ 'option' => 'apollo_evt_theme_cal_loader',          'type' => 'color',  'default' => 'FF9820' ],
            'evt_theme_social_icons'        => [ 'option' => 'apollo_evt_theme_social_icons',        'type' => 'color',  'default' => '#71717a' ],
            'evt_theme_search_field'        => [ 'option' => 'apollo_evt_theme_search_field',        'type' => 'color',  'default' => '#ffffff' ],
            'evt_theme_search_icon'         => [ 'option' => 'apollo_evt_theme_search_icon',         'type' => 'color',  'default' => '#71717a' ],
            'evt_theme_search_effect'       => [ 'option' => 'apollo_evt_theme_search_effect',       'type' => 'color',  'default' => 'FF9820' ],
            'evt_theme_events_found'        => [ 'option' => 'apollo_evt_theme_events_found',        'type' => 'color',  'default' => '#121214' ],
            'evt_theme_show_more_bar'       => [ 'option' => 'apollo_evt_theme_show_more_bar',       'type' => 'color',  'default' => '#f4f4f5' ],
            'evt_theme_timezone_sec'        => [ 'option' => 'apollo_evt_theme_timezone_sec',        'type' => 'color',  'default' => '#71717a' ],
            'evt_theme_no_event_bg'         => [ 'option' => 'apollo_evt_theme_no_event_bg',         'type' => 'color',  'default' => '#f8f8f9' ],
            'evt_theme_border_color'        => [ 'option' => 'apollo_evt_theme_border_color',        'type' => 'color',  'default' => '#e4e4e7' ],
            'evt_theme_repeat_header'       => [ 'option' => 'apollo_evt_theme_repeat_header',       'type' => 'color',  'default' => '#121214' ],
            'evt_theme_tag_cancelled'       => [ 'option' => 'apollo_evt_theme_tag_cancelled',       'type' => 'color',  'default' => '#ef4444' ],
            'evt_theme_tag_sold_out'        => [ 'option' => 'apollo_evt_theme_tag_sold_out',        'type' => 'color',  'default' => '#ef4444' ],
            'evt_theme_tag_postponed'       => [ 'option' => 'apollo_evt_theme_tag_postponed',       'type' => 'color',  'default' => '#eab308' ],
            'evt_theme_tag_sold_soon'       => [ 'option' => 'apollo_evt_theme_tag_sold_soon',       'type' => 'color',  'default' => '#f97316' ],
            'evt_theme_tag_rescheduled'     => [ 'option' => 'apollo_evt_theme_tag_rescheduled',     'type' => 'color',  'default' => '#3b82f6' ],
            'evt_theme_tag_featured'        => [ 'option' => 'apollo_evt_theme_tag_featured',        'type' => 'color',  'default' => 'FF9820' ],
            'evt_theme_tag_completed'       => [ 'option' => 'apollo_evt_theme_tag_completed',       'type' => 'color',  'default' => '#71717a' ],
            'evt_theme_tag_live'            => [ 'option' => 'apollo_evt_theme_tag_live',            'type' => 'color',  'default' => '#22c55e' ],
            'evt_theme_section_font_size'   => [ 'option' => 'apollo_evt_theme_section_font_size',   'type' => 'select', 'default' => '13px' ],
            'evt_theme_card_font'           => [ 'option' => 'apollo_evt_theme_card_font',           'type' => 'color',  'default' => '#121214' ],
            'evt_theme_card_bg'             => [ 'option' => 'apollo_evt_theme_card_bg',             'type' => 'color',  'default' => '#ffffff' ],
            'evt_theme_card_inner_bg'       => [ 'option' => 'apollo_evt_theme_card_inner_bg',       'type' => 'color',  'default' => '#f8f8f9' ],
            'evt_theme_card_directions_field' => [ 'option' => 'apollo_evt_theme_card_directions_field', 'type' => 'color', 'default' => '#e4e4e7' ],
            'evt_theme_card_directions_btn'   => [ 'option' => 'apollo_evt_theme_card_directions_btn',   'type' => 'color', 'default' => '#121214' ],
            'evt_theme_btn_detail'          => [ 'option' => 'apollo_evt_theme_btn_detail',          'type' => 'color',  'default' => 'FF9820' ],
            'evt_theme_btn_primary'         => [ 'option' => 'apollo_evt_theme_btn_primary',         'type' => 'color',  'default' => '#121214' ],
            'evt_theme_btn_secondary'       => [ 'option' => 'apollo_evt_theme_btn_secondary',       'type' => 'color',  'default' => '#f4f4f5' ],
            'evt_theme_btn_close'           => [ 'option' => 'apollo_evt_theme_btn_close',           'type' => 'color',  'default' => '#71717a' ],
            'evt_theme_btn_lightbox_close'  => [ 'option' => 'apollo_evt_theme_btn_lightbox_close',  'type' => 'color',  'default' => '#ffffff' ],
            'evt_theme_btn_repeat'          => [ 'option' => 'apollo_evt_theme_btn_repeat',          'type' => 'color',  'default' => '#3b82f6' ],
            'evt_theme_live_title'          => [ 'option' => 'apollo_evt_theme_live_title',          'type' => 'color',  'default' => '#22c55e' ],
            'evt_theme_live_coming_bg'      => [ 'option' => 'apollo_evt_theme_live_coming_bg',      'type' => 'color',  'default' => '#f8f8f9' ],
            'evt_theme_live_coming_text'    => [ 'option' => 'apollo_evt_theme_live_coming_text',    'type' => 'color',  'default' => '#71717a' ],
            'evt_theme_live_counter'        => [ 'option' => 'apollo_evt_theme_live_counter',        'type' => 'color',  'default' => 'FF9820' ],
            'evt_theme_live_no_events'      => [ 'option' => 'apollo_evt_theme_live_no_events',      'type' => 'color',  'default' => '#d4d4d8' ],

            /* ── EVENTS: EventTop (events/eventtop.php actual keys) ───── */
            'et_title_color'        => [ 'option' => 'apollo_et_title_color',        'type' => 'color',  'default' => '#121214' ],
            'et_subtitle_color'     => [ 'option' => 'apollo_et_subtitle_color',     'type' => 'color',  'default' => '#71717a' ],
            'et_text_under'         => [ 'option' => 'apollo_et_text_under',         'type' => 'color',  'default' => '#a1a1aa' ],
            'et_cat_color'          => [ 'option' => 'apollo_et_cat_color',          'type' => 'color',  'default' => 'FF9820' ],
            'et_border_size'        => [ 'option' => 'apollo_et_border_size',        'type' => 'select', 'default' => '10px' ],
            'et_bg_color'           => [ 'option' => 'apollo_et_bg_color',           'type' => 'color',  'default' => '#ffffff' ],
            'et_colorful_text'      => [ 'option' => 'apollo_et_colorful_text',      'type' => 'color',  'default' => '#ffffff' ],
            'et_style'              => [ 'option' => 'apollo_et_style',              'type' => 'select', 'default' => 'colorful' ],
            'et_organizer_action'   => [ 'option' => 'apollo_et_organizer_action',   'type' => 'select', 'default' => 'lightbox' ],
            'et_location_display'   => [ 'option' => 'apollo_et_location_display',   'type' => 'select', 'default' => 'name' ],
            'et_day_name'           => [ 'option' => 'apollo_et_day_name',           'type' => 'select', 'default' => 'show' ],
            'et_start_year'         => [ 'option' => 'apollo_et_start_year',         'type' => 'select', 'default' => 'show' ],
            'et_end_year'           => [ 'option' => 'apollo_et_end_year',           'type' => 'select', 'default' => 'show' ],
            'et_show_meta_icons'    => [ 'option' => 'apollo_et_show_meta_icons',    'type' => 'bool',   'default' => true ],
            'et_show_edit_btn'      => [ 'option' => 'apollo_et_show_edit_btn',      'type' => 'bool',   'default' => false ],
            'et_hide_progress'      => [ 'option' => 'apollo_et_hide_progress',      'type' => 'bool',   'default' => false ],
            'et_hide_live_icon'     => [ 'option' => 'apollo_et_hide_live_icon',     'type' => 'bool',   'default' => false ],
            'et_widget_fields'      => [ 'option' => 'apollo_et_widget_fields',      'type' => 'bool',   'default' => true ],
            'et_hide_virtual'       => [ 'option' => 'apollo_et_hide_virtual',       'type' => 'bool',   'default' => false ],
            'et_hide_hybrid'        => [ 'option' => 'apollo_et_hide_hybrid',        'type' => 'bool',   'default' => false ],
            'et_hide_status'        => [ 'option' => 'apollo_et_hide_status',        'type' => 'bool',   'default' => false ],
            'et_hide_featured'      => [ 'option' => 'apollo_et_hide_featured',      'type' => 'bool',   'default' => false ],
            'et_hide_completed'     => [ 'option' => 'apollo_et_hide_completed',     'type' => 'bool',   'default' => false ],

            /* ── EVENTS: EventCard (events/eventcard.php actual keys) ─── */
            'ec_img_style'          => [ 'option' => 'apollo_ec_img_style',          'type' => 'select', 'default' => 'direct' ],
            'ec_img_min_height'     => [ 'option' => 'apollo_ec_img_min_height',     'type' => 'int',    'default' => 250 ],
            'ec_disable_hover'      => [ 'option' => 'apollo_ec_disable_hover',      'type' => 'bool',   'default' => false ],
            'ec_disable_zoom'       => [ 'option' => 'apollo_ec_disable_zoom',       'type' => 'bool',   'default' => false ],
            'ec_show_magnify'       => [ 'option' => 'apollo_ec_show_magnify',       'type' => 'bool',   'default' => false ],
            'ec_default_img'        => [ 'option' => 'apollo_ec_default_img',        'type' => 'url',    'default' => '' ],
            'ec_loc_img_height'     => [ 'option' => 'apollo_ec_loc_img_height',     'type' => 'int',    'default' => 200 ],
            'ec_cal_options'        => [ 'option' => 'apollo_ec_cal_options',        'type' => 'select', 'default' => 'all' ],
            'ec_full_desc'          => [ 'option' => 'apollo_ec_full_desc',          'type' => 'bool',   'default' => false ],
            'ec_open_all'           => [ 'option' => 'apollo_ec_open_all',           'type' => 'bool',   'default' => false ],
            'ec_disable_filtering'  => [ 'option' => 'apollo_ec_disable_filtering',  'type' => 'bool',   'default' => false ],
            'ec_icon_size'          => [ 'option' => 'apollo_ec_icon_size',          'type' => 'select', 'default' => '14px' ],
            'ec_icon_details'       => [ 'option' => 'apollo_ec_icon_details',       'type' => 'text',   'default' => 'ri-information-line' ],
            'ec_icon_time'          => [ 'option' => 'apollo_ec_icon_time',          'type' => 'text',   'default' => 'ri-time-line' ],
            'ec_icon_repeat'        => [ 'option' => 'apollo_ec_icon_repeat',        'type' => 'text',   'default' => 'ri-repeat-line' ],
            'ec_icon_virtual'       => [ 'option' => 'apollo_ec_icon_virtual',       'type' => 'text',   'default' => 'ri-vidicon-line' ],
            'ec_icon_health'        => [ 'option' => 'apollo_ec_icon_health',        'type' => 'text',   'default' => 'ri-heart-pulse-line' ],
            'ec_icon_location'      => [ 'option' => 'apollo_ec_icon_location',      'type' => 'text',   'default' => 'ri-map-pin-line' ],
            'ec_icon_organizer'     => [ 'option' => 'apollo_ec_icon_organizer',     'type' => 'text',   'default' => 'ri-user-star-line' ],
            'ec_icon_capacity'      => [ 'option' => 'apollo_ec_icon_capacity',      'type' => 'text',   'default' => 'ri-group-line' ],
            'ec_icon_learn-more'    => [ 'option' => 'apollo_ec_icon_learn_more',    'type' => 'text',   'default' => 'ri-book-read-line' ],
            'ec_icon_related'       => [ 'option' => 'apollo_ec_icon_related',       'type' => 'text',   'default' => 'ri-link' ],
            'ec_icon_ticket'        => [ 'option' => 'apollo_ec_icon_ticket',        'type' => 'text',   'default' => 'ri-ticket-line' ],
            'ec_icon_add-to-cal'    => [ 'option' => 'apollo_ec_icon_add_to_cal',    'type' => 'text',   'default' => 'ri-calendar-check-line' ],
            'ec_icon_directions'    => [ 'option' => 'apollo_ec_icon_directions',    'type' => 'text',   'default' => 'ri-route-line' ],

            /* ── EVENTS: Single Event Page (events/single.php actual keys) ── */
            'single_disable_og'       => [ 'option' => 'apollo_single_disable_og',       'type' => 'bool',   'default' => false ],
            'single_sidebar'          => [ 'option' => 'apollo_single_sidebar',          'type' => 'bool',   'default' => false ],
            'single_restrict_logged'  => [ 'option' => 'apollo_single_restrict_logged',  'type' => 'bool',   'default' => false ],
            'single_disable_comments' => [ 'option' => 'apollo_single_disable_comments', 'type' => 'bool',   'default' => false ],
            'single_hide_title'       => [ 'option' => 'apollo_single_hide_title',       'type' => 'bool',   'default' => false ],
            'single_show_month_year'  => [ 'option' => 'apollo_single_show_month_year',  'type' => 'bool',   'default' => false ],
            'single_override_color'   => [ 'option' => 'apollo_single_override_color',   'type' => 'bool',   'default' => false ],
            'single_disable_ics'      => [ 'option' => 'apollo_single_disable_ics',      'type' => 'bool',   'default' => false ],
            'single_eventtop_style'   => [ 'option' => 'apollo_single_eventtop_style',   'type' => 'select', 'default' => 'colorful' ],

            /* ── EVENTS: Social Share (events/social.php actual keys) ─── */
            'share_single_only'       => [ 'option' => 'apollo_share_single_only',       'type' => 'bool', 'default' => false ],
            'share_disable_encoding'  => [ 'option' => 'apollo_share_disable_encoding',  'type' => 'bool', 'default' => false ],
            'share_facebook'          => [ 'option' => 'apollo_share_facebook',          'type' => 'bool', 'default' => true ],
            'share_twitter'           => [ 'option' => 'apollo_share_twitter',           'type' => 'bool', 'default' => true ],
            'share_linkedin'          => [ 'option' => 'apollo_share_linkedin',          'type' => 'bool', 'default' => false ],
            'share_whatsapp'          => [ 'option' => 'apollo_share_whatsapp',          'type' => 'bool', 'default' => true ],
            'share_pinterest'         => [ 'option' => 'apollo_share_pinterest',         'type' => 'bool', 'default' => false ],
            'share_copy_link'         => [ 'option' => 'apollo_share_copy_link',         'type' => 'bool', 'default' => true ],
            'share_email'             => [ 'option' => 'apollo_share_email',             'type' => 'bool', 'default' => false ],

            /* ── EVENTS: Repeat (events/repeat.php actual keys) ────────── */
            'repeat_load_current' => [ 'option' => 'apollo_repeat_load_current', 'type' => 'bool', 'default' => true ],

            /* ── EVENTS: Custom Fields count (events/custom.php) ──────── */
            'cf_num_fields' => [ 'option' => 'apollo_cf_num_fields', 'type' => 'int', 'default' => 3 ],
        ];

        // events/custom.php declares up to 10 repeatable custom-field slots
        // (cf_field_{n}_active/name/type/icon/vis/hide/login_msg) — generated
        // here instead of 70 hand-written lines.
        for ( $n = 1; $n <= 10; $n++ ) {
            $map[ "cf_field_{$n}_active" ]    = [ 'option' => "apollo_cf_field_{$n}_active",    'type' => 'bool',   'default' => false ];
            $map[ "cf_field_{$n}_name" ]      = [ 'option' => "apollo_cf_field_{$n}_name",      'type' => 'text',   'default' => '' ];
            $map[ "cf_field_{$n}_type" ]      = [ 'option' => "apollo_cf_field_{$n}_type",      'type' => 'select', 'default' => 'text' ];
            $map[ "cf_field_{$n}_icon" ]      = [ 'option' => "apollo_cf_field_{$n}_icon",      'type' => 'text',   'default' => '' ];
            $map[ "cf_field_{$n}_vis" ]       = [ 'option' => "apollo_cf_field_{$n}_vis",       'type' => 'select', 'default' => 'everyone' ];
            $map[ "cf_field_{$n}_hide" ]      = [ 'option' => "apollo_cf_field_{$n}_hide",      'type' => 'bool',   'default' => false ];
            $map[ "cf_field_{$n}_login_msg" ] = [ 'option' => "apollo_cf_field_{$n}_login_msg", 'type' => 'bool',   'default' => false ];
        }

        return $map;
    }
}
