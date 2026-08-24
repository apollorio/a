<?php

/**
 * Apollo Admin Panel — Top Bar with Section Tabs
 *
 * @package Apollo\Admin
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="topbar">
    <!-- ADMIN TABS -->
    <div class="topbar-tabs" id="tabs-admin">
        <button class="tab-btn active" data-tab="admin-global"><i class="ri-dashboard-line"></i><span class="tooltip"><?php esc_html_e('Overview', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="admin-users"><i class="ri-contacts-book-fill"></i><span class="tooltip"><?php esc_html_e('Users', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="admin-membership"><i class="ri-verified-badge-line"></i><span class="tooltip"><?php esc_html_e('Memberships', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="admin-moderate"><i class="ri-shield-user-fill"></i><span class="tooltip"><?php esc_html_e('Moderation', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="admin-notify"><i class="ri-signal-tower-fill"></i><span class="tooltip"><?php esc_html_e('Signaling', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="admin-users-sheet"><i class="ri-file-excel-2-line"></i><span class="tooltip"><?php esc_html_e('Spreadsheets', 'apollo-admin'); ?></span></button>
    </div>

    <!-- IDENTITY TABS -->
    <div class="topbar-tabs" id="tabs-identity" style="display:none">
        <button class="tab-btn active" data-tab="id-login"><i class="ri-fingerprint-fill"></i><span class="tooltip"><?php esc_html_e('Login Methods', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="id-users"><i class="ri-contacts-book-fill"></i><span class="tooltip"><?php esc_html_e('User Directory', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="id-membership"><i class="ri-verified-badge-line"></i><span class="tooltip"><?php esc_html_e('Membership Tiers', 'apollo-admin'); ?></span></button>
    </div>

    <!-- EMAIL TABS -->
    <div class="topbar-tabs" id="tabs-email" style="display:none">
        <button class="tab-btn active" data-tab="email-settings"><i class="ri-compasses-2-line"></i><span class="tooltip"><?php esc_html_e('Settings', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="email-templates"><i class="ri-pages-line"></i><span class="tooltip"><?php esc_html_e('Templates', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="email-subscribers"><i class="ri-contacts-line"></i><span class="tooltip"><?php esc_html_e('Subscribers', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="email-stats"><i class="ri-line-chart-line"></i><span class="tooltip"><?php esc_html_e('Statistics', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="email-workflows"><i class="ri-flow-chart"></i><span class="tooltip"><?php esc_html_e('Workflows', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="email-logger"><i class="ri-file-list-3-line"></i><span class="tooltip"><?php esc_html_e('Logger', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="email-unsub"><i class="ri-user-unfollow-line"></i><span class="tooltip"><?php esc_html_e('Unsubscribes', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="email-tools"><i class="ri-tools-line"></i><span class="tooltip"><?php esc_html_e('Tools', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="email-rss"><i class="ri-rss-line"></i><span class="tooltip"><?php esc_html_e('RSS Push', 'apollo-admin'); ?></span></button>
    </div>

    <!-- EVENTS TABS -->
    <div class="topbar-tabs" id="tabs-events" style="display:none">
        <button class="tab-btn active" data-tab="evt-general"><i class="ri-compasses-2-line"></i><span class="tooltip"><?php esc_html_e('General', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-calendar"><i class="ri-calendar-line"></i><span class="tooltip"><?php esc_html_e('Calendar', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-maps"><i class="ri-map-pin-line"></i><span class="tooltip"><?php esc_html_e('Maps', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-theme"><i class="ri-palette-line"></i><span class="tooltip"><?php esc_html_e('Theme', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-eventtop"><i class="ri-layout-top-line"></i><span class="tooltip"><?php esc_html_e('EventTop', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-eventcard"><i class="ri-id-card-line"></i><span class="tooltip"><?php esc_html_e('EventCard', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-custom"><i class="ri-input-method-line"></i><span class="tooltip"><?php esc_html_e('Custom Fields', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-single"><i class="ri-file-paper-2-line"></i><span class="tooltip"><?php esc_html_e('Single Event', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-social"><i class="ri-share-forward-line"></i><span class="tooltip"><?php esc_html_e('Social Share', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="evt-repeat"><i class="ri-refresh-line"></i><span class="tooltip"><?php esc_html_e('Repeat', 'apollo-admin'); ?></span></button>
    </div>

    <!-- SOCIAL TABS -->
    <div class="topbar-tabs" id="tabs-social" style="display:none">
        <button class="tab-btn active" data-tab="soc-activity"><i class="ri-pulse-line"></i><span class="tooltip"><?php esc_html_e('Activity', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="soc-chat"><i class="ri-message-3-fill"></i><span class="tooltip"><?php esc_html_e('Chat System', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="soc-groups"><i class="ri-user-community-fill"></i><span class="tooltip"><?php esc_html_e('Groups', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="soc-reactions"><i class="ri-brain-ai-3-line"></i><span class="tooltip"><?php esc_html_e('Wow', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="soc-notif"><i class="ri-signal-tower-fill"></i><span class="tooltip"><?php esc_html_e('Notifications', 'apollo-admin'); ?></span></button>
    </div>

    <!-- SYSTEM TABS -->
    <div class="topbar-tabs" id="tabs-system" style="display:none">
        <button class="tab-btn active" data-tab="sys-core"><i class="ri-cpu-line"></i><span class="tooltip"><?php esc_html_e('Core & CDN', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="sys-content"><i class="ri-database-2-line"></i><span class="tooltip"><?php esc_html_e('Content CPTs', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="sys-templates"><i class="ri-pages-line"></i><span class="tooltip"><?php esc_html_e('Templates', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="sys-stats"><i class="ri-line-chart-line"></i><span class="tooltip"><?php esc_html_e('Analytics', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="sys-pwa"><i class="ri-device-line"></i><span class="tooltip"><?php esc_html_e('PWA', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="sys-seo"><i class="ri-seo-line"></i><span class="tooltip"><?php esc_html_e('SEO', 'apollo-admin'); ?></span></button>
        <button class="tab-btn" data-tab="sys-security"><i class="ri-shield-keyhole-fill"></i><span class="tooltip"><?php esc_html_e('Security', 'apollo-admin'); ?></span></button>
    </div>

    <!-- Placeholder para topbars modulares injetadas pelo ModuleLoader -->
    <div id="topbar-switch"></div>

    <div class="topbar-right">
        <button class="topbar-icon-btn"><i class="ri-search-line"></i></button>
        <button class="topbar-save" id="apollo-save-btn">
            <i class="ri-save-3-line"></i>
            <span class="tooltip"><?php esc_html_e('Save Changes', 'apollo-admin'); ?></span>
        </button>
    </div>
</div>