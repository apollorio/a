<?php

/**
 * System Section — Content CPTs
 *
 * Page ID: page-sys-content
 * 10 feed-tabs: DJs, Locais, Classifieds, Suppliers, Hub, Coauthor, Moderation, Docs, Sign, Gestor
 *
 * @package Apollo\Admin
 */

if (! defined('ABSPATH')) {
    exit;
}

?>
<div class="page" id="page-sys-content">
    <div class="feed-tabs">
        <button class="feed-tab active" data-sub="cpt-djs" title="DJs"><i class="ri-sound-module-line"></i></button>
        <button class="feed-tab" data-sub="cpt-loc" title="<?php esc_attr_e('Locais', 'apollo-admin'); ?>"><i class="ri-map-pin-line"></i></button>
        <button class="feed-tab" data-sub="cpt-adverts" title="Classifieds"><i class="ri-exchange-box-line"></i></button>
        <button class="feed-tab" data-sub="cpt-suppliers" title="Suppliers"><i class="ri-contacts-line"></i></button>
        <button class="feed-tab" data-sub="cpt-hub" title="Hub"><i class="ri-connector-line"></i></button>
        <button class="feed-tab" data-sub="cpt-coauthor" title="Coauthor"><i class="ri-contacts-book-3-line"></i></button>
        <button class="feed-tab" data-sub="cpt-mod" title="Moderation"><i class="ri-pencil-ruler-line"></i></button>
        <button class="feed-tab" data-sub="cpt-docs" title="Docs"><i class="ri-file-text-line"></i></button>
        <button class="feed-tab" data-sub="cpt-sign" title="Sign"><i class="ri-quill-pen-ai-line"></i></button>
        <button class="feed-tab" data-sub="cpt-gestor" title="Gestor"><i class="ri-clipboard-line"></i></button>
    </div>

    <!-- DJs -->
    <div class="sub-content visible" id="sub-cpt-djs">
        <div class="panel">
            <div class="panel-header"><i class="ri-sound-module-line"></i> <?php esc_html_e('DJ Profiles', 'apollo-admin'); ?> <span class="badge">apollo-djs</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[djs_enable]" value="1" <?php checked($apollo['djs_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable DJ Profiles', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable DJ profile pages', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[djs_carousel]" value="1" <?php checked($apollo['djs_carousel'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Carousel', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable carousel view on archives', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[djs_soundcloud]" value="1" <?php checked($apollo['djs_soundcloud'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable SoundCloud Embed', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Auto-embed SoundCloud player on profiles', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[djs_genre_filter]" value="1" <?php checked($apollo['djs_genre_filter'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Genre Filter', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Show genre filter on archive page', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('DJ Slug', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[djs_slug]" value="<?php echo esc_attr($apollo['djs_slug'] ?? 'dj'); ?>"><span class="field-hint"><?php esc_html_e('Custom CPT permalink slug', 'apollo-admin'); ?></span></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Profiles Per Page', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[djs_per_page]" value="<?php echo esc_attr($apollo['djs_per_page'] ?? 12); ?>"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Locations -->
    <div class="sub-content" id="sub-cpt-loc">
        <div class="panel">
            <div class="panel-header"><i class="ri-map-pin-line"></i> <?php esc_html_e('Locations (Locais)', 'apollo-admin'); ?> <span class="badge">apollo-loc</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[loc_maps]" value="1" <?php checked($apollo['loc_maps'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Maps', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable map rendering on location pages', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[loc_geocoding]" value="1" <?php checked($apollo['loc_geocoding'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Geocoding', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable geocoding from address input', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('Default City', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[loc_city]" value="<?php echo esc_attr($apollo['loc_city'] ?? 'Rio de Janeiro'); ?>"></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Maps API Key', 'apollo-admin'); ?></label><input type="password" class="apollo-input input" name="apollo[loc_maps_key]" value="<?php echo esc_attr($apollo['loc_maps_key'] ?? ''); ?>" placeholder="AIzaSy..."></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Default Lat', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[loc_lat]" value="<?php echo esc_attr($apollo['loc_lat'] ?? '-22.9068'); ?>"></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Default Lng', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[loc_lng]" value="<?php echo esc_attr($apollo['loc_lng'] ?? '-43.1729'); ?>"></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Default Zoom', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[loc_zoom]" value="<?php echo esc_attr($apollo['loc_zoom'] ?? 12); ?>"></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Loc Slug', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[loc_slug]" value="<?php echo esc_attr($apollo['loc_slug'] ?? 'gps'); ?>"></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Nearby Radius (km)', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[loc_radius]" value="<?php echo esc_attr($apollo['loc_radius'] ?? 5); ?>"></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Per Page', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[loc_per_page]" value="<?php echo esc_attr($apollo['loc_per_page'] ?? 20); ?>"></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[loc_nearby]" value="1" <?php checked($apollo['loc_nearby'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Nearby Search', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable nearby loc search (Haversine formula)', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[loc_clustering]" value="1" <?php checked($apollo['loc_clustering'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Clustering', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable map marker clustering', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[loc_bigdata]" value="1" <?php checked($apollo['loc_bigdata'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Big Data Optimizations', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable large dataset optimizations (50k+ listings)', 'apollo-admin'); ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Classifieds -->
    <div class="sub-content" id="sub-cpt-adverts">
        <div class="panel">
            <div class="panel-header"><i class="ri-exchange-box-line"></i> <?php esc_html_e('Classifieds', 'apollo-admin'); ?> <span class="badge">apollo-adverts</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[adv_enable]" value="1" <?php checked($apollo['adv_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Classifieds', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable the classifieds system', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[adv_approval]" value="1" <?php checked($apollo['adv_approval'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Require Approval', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Require manual admin approval for listings', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('Listing Expiry (days)', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[adv_expiry]" value="<?php echo esc_attr($apollo['adv_expiry'] ?? 30); ?>"></div>
                    <div class="field">
                        <label class="field-label"><?php esc_html_e('Expired Status', 'apollo-admin'); ?></label>
                        <select class="select" name="apollo[adv_expired_status]">
                            <option value="draft" <?php selected($apollo['adv_expired_status'] ?? 'draft', 'draft'); ?>><?php esc_html_e('Draft', 'apollo-admin'); ?></option>
                            <option value="private" <?php selected($apollo['adv_expired_status'] ?? 'draft', 'private'); ?>><?php esc_html_e('Private', 'apollo-admin'); ?></option>
                            <option value="closed" <?php selected($apollo['adv_expired_status'] ?? 'draft', 'closed'); ?>><?php esc_html_e('Closed', 'apollo-admin'); ?></option>
                        </select>
                    </div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Max Images', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[adv_max_images]" value="<?php echo esc_attr($apollo['adv_max_images'] ?? 10); ?>"></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[adv_geo]" value="1" <?php checked($apollo['adv_geo'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Geo Targeting', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable geolocation targeting for ads', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[adv_featured]" value="1" <?php checked($apollo['adv_featured'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Featured Listings', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Allow featured/promoted listings', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[adv_payment]" value="1" <?php checked($apollo['adv_payment'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Payment Plans', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Paid listing promotion plans', 'apollo-admin'); ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suppliers -->
    <div class="sub-content" id="sub-cpt-suppliers">
        <div class="panel">
            <div class="panel-header"><i class="ri-contacts-line"></i> <?php esc_html_e('Suppliers', 'apollo-admin'); ?> <span class="badge">apollo-suppliers</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sup_enable]" value="1" <?php checked($apollo['sup_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Suppliers', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable supplier directory', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('Supplier Slug', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[sup_slug]" value="<?php echo esc_attr($apollo['sup_slug'] ?? 'fornecedor'); ?>"></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Per Page', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[sup_per_page]" value="<?php echo esc_attr($apollo['sup_per_page'] ?? 20); ?>"></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sup_categories]" value="1" <?php checked($apollo['sup_categories'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Categories', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable supplier category taxonomy', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sup_verification]" value="1" <?php checked($apollo['sup_verification'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Verification', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Verified supplier badge system', 'apollo-admin'); ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hub -->
    <div class="sub-content" id="sub-cpt-hub">
        <div class="panel">
            <div class="panel-header"><i class="ri-connector-line"></i> <?php esc_html_e('Hub & Directory', 'apollo-admin'); ?> <span class="badge">apollo-hub</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[hub_search]" value="1" <?php checked($apollo['hub_search'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Global Search', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable unified search across all CPTs', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[hub_directory]" value="1" <?php checked($apollo['hub_directory'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Directory', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable directory listings view', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[hub_facets]" value="1" <?php checked($apollo['hub_facets'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Faceted Filters', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable faceted search filters (taxonomies, dates, etc.)', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('Results Per Page', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[hub_per_page]" value="<?php echo esc_attr($apollo['hub_per_page'] ?? 20); ?>"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coauthor -->
    <div class="sub-content" id="sub-cpt-coauthor">
        <div class="panel">
            <div class="panel-header"><i class="ri-contacts-book-3-line"></i> <?php esc_html_e('Co-authorship', 'apollo-admin'); ?> <span class="badge">apollo-coauthor</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[coauthor_enable]" value="1" <?php checked($apollo['coauthor_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Co-authorship', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable multi-author support on posts', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[coauthor_frontend]" value="1" <?php checked($apollo['coauthor_frontend'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Show in Frontend', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Display all authors on frontend templates', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[coauthor_guest]" value="1" <?php checked($apollo['coauthor_guest'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Guest Authors', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Allow guest authors without WP account', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field">
                        <label class="field-label"><?php esc_html_e('Author Box Layout', 'apollo-admin'); ?></label>
                        <select class="select" name="apollo[coauthor_layout]">
                            <option value="horizontal" <?php selected($apollo['coauthor_layout'] ?? 'horizontal', 'horizontal'); ?>><?php esc_html_e('Horizontal', 'apollo-admin'); ?></option>
                            <option value="vertical" <?php selected($apollo['coauthor_layout'] ?? 'horizontal', 'vertical'); ?>><?php esc_html_e('Vertical', 'apollo-admin'); ?></option>
                            <option value="grid" <?php selected($apollo['coauthor_layout'] ?? 'horizontal', 'grid'); ?>><?php esc_html_e('Grid', 'apollo-admin'); ?></option>
                        </select>
                    </div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Authors Per Page', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[coauthor_per_page]" value="<?php echo esc_attr($apollo['coauthor_per_page'] ?? 20); ?>"></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Custom Byline Format', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[coauthor_byline]" value="<?php echo esc_attr($apollo['coauthor_byline'] ?? ''); ?>" placeholder="<?php esc_attr_e('e.g., By {authors}', 'apollo-admin'); ?>"></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[coauthor_avatar]" value="1" <?php checked($apollo['coauthor_avatar'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Show Avatar', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[coauthor_bio]" value="1" <?php checked($apollo['coauthor_bio'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Show Bio', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[coauthor_social]" value="1" <?php checked($apollo['coauthor_social'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Show Social Links', 'apollo-admin'); ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Moderation -->
    <div class="sub-content" id="sub-cpt-mod">
        <div class="panel">
            <div class="panel-header"><i class="ri-pencil-ruler-line"></i> <?php esc_html_e('Moderation', 'apollo-admin'); ?> <span class="badge">apollo-mod</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mod_auto]" value="1" <?php checked($apollo['mod_auto'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Auto-Moderation', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable automatic content moderation', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('Report Threshold', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[mod_report_threshold]" value="<?php echo esc_attr($apollo['mod_report_threshold'] ?? 3); ?>"><span class="field-hint"><?php esc_html_e('Reports needed to auto-flag content', 'apollo-admin'); ?></span></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Auto-Ban Threshold', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[mod_ban_threshold]" value="<?php echo esc_attr($apollo['mod_ban_threshold'] ?? 10); ?>"><span class="field-hint"><?php esc_html_e('Total reports before auto-banning user', 'apollo-admin'); ?></span></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Ban Duration (days)', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[mod_ban_duration]" value="<?php echo esc_attr($apollo['mod_ban_duration'] ?? 0); ?>"><span class="field-hint"><?php esc_html_e('0 = permanent', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mod_word_filter]" value="1" <?php checked($apollo['mod_word_filter'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Word Filter', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable forbidden word list filter', 'apollo-admin'); ?></span></div>
                </div>
                <div class="field" style="margin-top:12px"><label class="field-label"><?php esc_html_e('Word Blacklist', 'apollo-admin'); ?></label><textarea class="apollo-textarea input" rows="4" name="apollo[mod_blacklist]" placeholder="<?php esc_attr_e('One word per line...', 'apollo-admin'); ?>"><?php echo esc_textarea($apollo['mod_blacklist'] ?? ''); ?></textarea><span class="field-hint"><?php esc_html_e('Newline-separated forbidden words', 'apollo-admin'); ?></span></div>
            </div>
        </div>
    </div>

    <!-- Docs -->
    <div class="sub-content" id="sub-cpt-docs">
        <div class="panel">
            <div class="panel-header"><i class="ri-file-text-line"></i> <?php esc_html_e('Documents & File Manager', 'apollo-admin'); ?> <span class="badge">apollo-docs</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[docs_enable]" value="1" <?php checked($apollo['docs_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Document System', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable docs CPT, editor, versioning and file manager', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[docs_versioning]" value="1" <?php checked($apollo['docs_versioning'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Versioning', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Track version history and changelogs for each document', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[docs_pdf_gen]" value="1" <?php checked($apollo['docs_pdf_gen'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable PDF Generation', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Allow documents to be exported as PDF files', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[docs_public_access]" value="1" <?php checked($apollo['docs_public_access'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Allow Public Documents', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Allow users to set documents as public access', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('Max File Size (MB)', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[docs_max_size]" value="<?php echo esc_attr($apollo['docs_max_size'] ?? 10); ?>"><span class="field-hint"><?php esc_html_e('Maximum upload file size in megabytes', 'apollo-admin'); ?></span></div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Storage Path', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[docs_storage_path]" value="<?php echo esc_attr($apollo['docs_storage_path'] ?? 'apollo-docs'); ?>" readonly><span class="field-hint"><?php esc_html_e('Relative to wp-content/uploads/', 'apollo-admin'); ?></span></div>
                    <div class="field">
                        <label class="field-label"><?php esc_html_e('Allowed File Types', 'apollo-admin'); ?></label>
                        <select class="select" name="apollo[docs_allowed_types]">
                            <option value="docs_only" <?php selected($apollo['docs_allowed_types'] ?? 'all', 'docs_only'); ?>><?php esc_html_e('Documents Only (PDF, DOC, XLS)', 'apollo-admin'); ?></option>
                            <option value="docs_img" <?php selected($apollo['docs_allowed_types'] ?? 'all', 'docs_img'); ?>><?php esc_html_e('Documents + Images', 'apollo-admin'); ?></option>
                            <option value="all" <?php selected($apollo['docs_allowed_types'] ?? 'all', 'all'); ?>><?php esc_html_e('All (Docs + Images + Audio + Video)', 'apollo-admin'); ?></option>
                        </select>
                    </div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Documents Per Page', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[docs_per_page]" value="<?php echo esc_attr($apollo['docs_per_page'] ?? 20); ?>"></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[docs_checksum]" value="1" <?php checked($apollo['docs_checksum'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('SHA-256 Checksum', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Calculate and store SHA-256 hash for integrity verification', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[docs_download_log]" value="1" <?php checked($apollo['docs_download_log'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Log Downloads', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Track who downloads which document and when', 'apollo-admin'); ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sign (ICP-Brasil) -->
    <div class="sub-content" id="sub-cpt-sign">
        <div class="panel">
            <div class="panel-header"><i class="ri-quill-pen-ai-line"></i> <?php esc_html_e('Digital Signatures — ICP-Brasil', 'apollo-admin'); ?> <span class="badge">apollo-sign</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sign_enable]" value="1" <?php checked($apollo['sign_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Digital Signatures', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('ICP-Brasil PKCS#7 (A1/A3) digital signature system', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sign_cpf_required]" value="1" <?php checked($apollo['sign_cpf_required'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Require CPF', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Require CPF number from signer (extracted from certificate CN or entered manually)', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sign_cpf_validate]" value="1" <?php checked($apollo['sign_cpf_validate'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Validate CPF Algorithm', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Validate CPF check digits using Brazilian algorithm', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sign_audit_trail]" value="1" <?php checked($apollo['sign_audit_trail'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Audit Trail', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Full audit trail with timestamps, IP, and user-agent for each action', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sign_auto_link]" value="1" <?php checked($apollo['sign_auto_link'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Auto-Generate Signing Link', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Auto-create /assinar/{hash} link when document is finalized', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[sign_public_verify]" value="1" <?php checked($apollo['sign_public_verify'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Public Verification', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable public signature verification endpoint (/signatures/verify/{hash})', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field">
                        <label class="field-label"><?php esc_html_e('Certificate Types', 'apollo-admin'); ?></label>
                        <select class="select" name="apollo[sign_cert_types]">
                            <option value="a1" <?php selected($apollo['sign_cert_types'] ?? 'a1_a3', 'a1'); ?>><?php esc_html_e('A1 Only (Software PFX)', 'apollo-admin'); ?></option>
                            <option value="a1_a3" <?php selected($apollo['sign_cert_types'] ?? 'a1_a3', 'a1_a3'); ?>><?php esc_html_e('A1 + A3 (Software + Token/SmartCard)', 'apollo-admin'); ?></option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="field-label"><?php esc_html_e('Hash Algorithm', 'apollo-admin'); ?></label>
                        <input type="text" class="apollo-input input" value="SHA-256 (ICP-Brasil)" readonly>
                        <span class="field-hint"><?php esc_html_e('Fixed: SHA-256 minimum required by ICP-Brasil since 2012', 'apollo-admin'); ?></span>
                    </div>
                    <div class="field"><label class="field-label"><?php esc_html_e('Temp Cert Cleanup (hours)', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[sign_cleanup_hours]" value="<?php echo esc_attr($apollo['sign_cleanup_hours'] ?? 1); ?>"><span class="field-hint"><?php esc_html_e('Delete temp PFX files after N hours', 'apollo-admin'); ?></span></div>
                </div>
                <div style="margin-top:16px;padding:12px;background:var(--card);border-radius:8px;border-left:3px solid var(--green)">
                    <strong style="color:var(--green)"><i class="ri-shield-check-line"></i> <?php esc_html_e('ICP-Brasil Compliance', 'apollo-admin'); ?></strong>
                    <ul style="margin:8px 0 0 16px;font-size:12px;color:var(--gray-1);line-height:1.8">
                        <li><?php esc_html_e('PKCS#7 (CMS) detached digital signatures', 'apollo-admin'); ?></li>
                        <li><?php esc_html_e('SHA-256 hashing (minimum ICP-Brasil)', 'apollo-admin'); ?></li>
                        <li><?php esc_html_e('Certificate validation (dates, chain, CN/CPF extraction)', 'apollo-admin'); ?></li>
                        <li><?php esc_html_e('Full audit trail with IP + user-agent', 'apollo-admin'); ?></li>
                        <li><?php esc_html_e('CPF extraction from certificate CN (ICP-Brasil pattern)', 'apollo-admin'); ?></li>
                        <li><?php esc_html_e('Public verification endpoint for third-party validation', 'apollo-admin'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Gestor (File Manager / Project Manager) -->
    <div class="sub-content" id="sub-cpt-gestor">
        <div class="panel">
            <div class="panel-header"><i class="ri-clipboard-line"></i> <?php esc_html_e('Gestor — Project Manager', 'apollo-admin'); ?> <span class="badge">apollo-gestor</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[gestor_enable]" value="1" <?php checked($apollo['gestor_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Gestor', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Event production manager — tasks, teams, payments, milestones', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[gestor_payments]" value="1" <?php checked($apollo['gestor_payments'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Payment Tracking', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Track payments and financial milestones per event', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[gestor_team]" value="1" <?php checked($apollo['gestor_team'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Team Management', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Assign team members with roles (DJ, VJ, Produtor, etc.)', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[gestor_milestones]" value="1" <?php checked($apollo['gestor_milestones'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Milestones', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Track event production milestones and deadlines', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[gestor_activity_log]" value="1" <?php checked($apollo['gestor_activity_log'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Activity Log', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Log all task/team/payment changes', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field">
                        <label class="field-label"><?php esc_html_e('Default Capability', 'apollo-admin'); ?></label>
                        <select class="select" name="apollo[gestor_capability]">
                            <option value="edit_posts" <?php selected($apollo['gestor_capability'] ?? 'edit_posts', 'edit_posts'); ?>><?php esc_html_e('Editor (edit_posts)', 'apollo-admin'); ?></option>
                            <option value="manage_options" <?php selected($apollo['gestor_capability'] ?? 'edit_posts', 'manage_options'); ?>><?php esc_html_e('Admin Only (manage_options)', 'apollo-admin'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>