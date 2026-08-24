<?php

/**
 * Helper Functions
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin instance
 *
 * @return Plugin
 */
function apollo_users(): Plugin
{
    return Plugin::get_instance();
}

/**
 * Get user profile URL
 *
 * @param int|null $user_id User ID (defaults to current user)
 * @return string Profile URL
 */
function apollo_get_profile_url(?int $user_id = null): string
{
    if (null === $user_id) {
        $user_id = get_current_user_id();
    }

    if (! $user_id) {
        return '';
    }

    $user = get_userdata($user_id);
    if (! $user) {
        return '';
    }

    return home_url('/id/' . $user->user_login . '/');
}

/**
 * Get user by username (case-insensitive)
 *
 * @param string $username Username
 * @return \WP_User|false
 */
function apollo_get_user_by_username(string $username)
{
    global $wpdb;

    // Try exact match first
    $user = get_user_by('login', $username);
    if ($user) {
        return $user;
    }

    // Case-insensitive search
    $user_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} WHERE LOWER(user_login) = LOWER(%s) LIMIT 1",
            $username
        )
    );

    if ($user_id) {
        return get_user_by('ID', $user_id);
    }

    return false;
}

/**
 * Get user avatar URL
 *
 * @param int    $user_id User ID
 * @param string $size Size (thumb, medium, full)
 * @return string Avatar URL
 */
function apollo_get_user_avatar_url(int $user_id, string $size = 'thumb'): string
{
    // Check for custom avatar first
    $custom_avatar_id = get_user_meta($user_id, 'custom_avatar', true);
    if ($custom_avatar_id) {
        $image_size = 'thumb' === $size ? 'thumbnail' : ('medium' === $size ? 'medium' : 'full');
        $image      = wp_get_attachment_image_url($custom_avatar_id, $image_size);
        if ($image) {
            return $image;
        }
    }

    // Check for Instagram avatar (from apollo-login)
    $instagram_avatar = get_user_meta($user_id, '_apollo_avatar_url', true);
    if ($instagram_avatar) {
        return $instagram_avatar;
    }

    // Check avatar_thumb meta
    $avatar_thumb = get_user_meta($user_id, 'avatar_thumb', true);
    if ($avatar_thumb) {
        return $avatar_thumb;
    }

    // Fallback to Gravatar
    return get_avatar_url($user_id, array('size' => 'thumb' === $size ? 96 : ('medium' === $size ? 256 : 512)));
}

/**
 * Get user cover image URL
 *
 * @param int $user_id User ID
 * @return string Cover URL or empty
 */
function apollo_get_user_cover_url(int $user_id): string
{
    $cover_id = get_user_meta($user_id, 'cover_image', true);
    if ($cover_id) {
        $image = wp_get_attachment_image_url($cover_id, 'full');
        if ($image) {
            return $image;
        }
    }
    return '';
}

/**
 * Get user membership badge
 *
 * @param int $user_id User ID
 * @return array Badge info with type, label, color
 */
function apollo_get_user_membership(int $user_id): array
{
    $type = function_exists('apollo_membership_get_user_badge')
        ? apollo_membership_get_user_badge($user_id)
        : apollo_get_membership_badge($user_id);

    $info = function_exists('apollo_membership_get_badge_info')
        ? apollo_membership_get_badge_info($type)
        : null;

    $access_slugs = function_exists('apollo_membership_get_user_access_slugs')
        ? apollo_membership_get_user_access_slugs($user_id)
        : array();

    $slugs = function_exists('apollo_membership_get_user_slugs')
        ? apollo_membership_get_user_slugs($user_id)
        : array($type);

    $deprecated = class_exists('\Apollo\Core\Config\MembershipRegistry')
        && \Apollo\Core\Config\MembershipRegistry::is_deprecated($type);

    return array(
        'type'              => $type,
        'label'             => $info['label'] ?? 'Não Verificado',
        'color'             => $info['color'] ?? '#6b7280',
        'icon'              => $info['icon'] ?? 'ri-user-line',
        'slugs'             => $slugs,
        'access_slugs'      => $access_slugs,
        'deprecated'        => $deprecated,
        'membership_active' => in_array('app-apollodj', $access_slugs, true),
        'level'             => in_array('app-apollodj', $access_slugs, true) ? 'premium' : 'free',
        'status'            => ! empty($access_slugs) ? 'active' : 'inactive',
        'is_premium'        => in_array('app-apollodj', $access_slugs, true),
    );
}

/**
 * Check if user profile is viewable
 *
 * @param int      $profile_user_id Profile owner ID
 * @param int|null $viewer_id Viewer ID (null for guest)
 * @return bool
 */
function apollo_can_view_profile(int $profile_user_id, ?int $viewer_id = null): bool
{
    $privacy = get_user_meta($profile_user_id, '_apollo_privacy_profile', true);

    if (empty($privacy) || 'public' === $privacy) {
        return true;
    }

    if ('members' === $privacy) {
        return $viewer_id > 0;
    }

    // Private - only self or admin
    if ('private' === $privacy) {
        if (null === $viewer_id) {
            return false;
        }
        return $viewer_id === $profile_user_id || user_can($viewer_id, 'manage_options');
    }

    return true;
}

/**
 * Record profile view
 *
 * @param int      $profile_user_id Profile owner ID
 * @param int|null $viewer_id Viewer ID
 * @return void
 */
function apollo_record_profile_view(int $profile_user_id, ?int $viewer_id = null): void
{
    global $wpdb;

    // Don't count self views
    if ($viewer_id === $profile_user_id) {
        return;
    }

    $table = $wpdb->prefix . APOLLO_USERS_TABLE_PROFILE_VIEWS;

    // Insert view record
    $wpdb->insert(
        $table,
        array(
            'profile_user_id' => $profile_user_id,
            'viewer_user_id'  => $viewer_id,
            'viewer_ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
            'viewed_at'       => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s')
    );

    // Update total count
    $count = (int) get_user_meta($profile_user_id, '_apollo_profile_views', true);
    update_user_meta($profile_user_id, '_apollo_profile_views', $count + 1);

    /**
     * Fires after a profile view is recorded.
     *
     * @param int      $profile_user_id Profile owner.
     * @param int|null $viewer_id       Viewer user ID.
     */
    do_action('apollo/users/profile_visited', $profile_user_id, $viewer_id ?? 0);
}

/**
 * Get user's social name (display name preference)
 *
 * @param int $user_id User ID
 * @return string
 */
function apollo_get_social_name(int $user_id): string
{
    // First check apollo social name
    $social_name = get_user_meta($user_id, '_apollo_social_name', true);
    if ($social_name) {
        return $social_name;
    }

    // Fallback to display_name
    $user = get_userdata($user_id);
    return $user ? $user->display_name : '';
}

/**
 * Get user bio
 *
 * @param int $user_id User ID
 * @return string
 */
function apollo_get_user_bio(int $user_id): string
{
    $bio = get_user_meta($user_id, '_apollo_bio', true);
    if ($bio) {
        return $bio;
    }

    // Fallback to WP description
    $user = get_userdata($user_id);
    return $user ? $user->description : '';
}

/**
 * Build the complete profile context array.
 *
 * This is the SINGLE source of truth for all profile data.
 * All DB reads happen here — the template only receives pre-escaped display values.
 *
 * @param int $user_id The profile owner's user ID.
 * @return array{
 *   user_id:           int,
 *   display_name:      string,
 *   bio:               string,
 *   avatar_url:        string,
 *   member_since:      string,
 *   membership:        array,
 *   privacy:           string,
 *   is_own:            bool,
 *   is_logged_in:      bool,
 *   is_admin:          bool,
 *   youtube_embed:     string,
 *   soundcloud_url:    string,
 *   soundcloud_widget: string,
 *   sound_tags:        string[],
 *   profile_link:      string,
 *   profile_link_label:string,
 *   stats:             array{fav_count: int, profile_views: int, hits_display: string, ranking_display: string},
 *   rating_averages:   array,
 *   rating_counts:     array,
 *   user_votes:        array,
 *   confiavel:         array{count: int, display: string, avatars: array},
 *   admin_ratings:     array,
 *   feed_posts:        \WP_Post[],
 *   depoimentos:       \WP_Comment[],
 *   js_config:         array,
 * }|null Null if user is invalid.
 */
function apollo_get_profile_context(int $user_id): ?array
{
    $user = get_userdata($user_id);
    if (! $user instanceof \WP_User || $user->ID <= 0) {
        return null;
    }

    $is_logged_in    = is_user_logged_in();
    $current_user_id = get_current_user_id();
    $is_own          = ($current_user_id === $user_id);
    $is_admin        = current_user_can('manage_options');

    // ─── Identity ───
    $display_name = apollo_get_social_name($user_id) ?: $user->display_name;
    $bio          = apollo_get_user_bio($user_id) ?: 'Biografia do usuário aguardando atualização.';
    $avatar_url   = apollo_get_user_avatar_url($user_id, 'large');
    $member_since = date('Y', strtotime($user->user_registered));
    $membership   = apollo_get_user_membership($user_id);

    // ─── Privacy ───
    $privacy = get_user_meta($user_id, '_apollo_privacy_profile', true) ?: 'public';

    // ─── YouTube embed ───
    $default_yt = 'https://www.youtube.com/embed/p_HEbzf1VeU?autoplay=1&mute=1&loop=1&playlist=p_HEbzf1VeU&controls=0&showinfo=0&modestbranding=1&rel=0&iv_load_policy=3&playsinline=1';
    $youtube_url   = get_user_meta($user_id, '_apollo_youtube_url', true);
    $youtube_embed = $default_yt;
    if ($youtube_url) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $youtube_url, $yt_m);
        if (! empty($yt_m[1])) {
            $vid           = sanitize_text_field($yt_m[1]);
            $youtube_embed = 'https://www.youtube.com/embed/' . $vid
                . '?autoplay=1&mute=1&loop=1&playlist=' . $vid
                . '&controls=0&showinfo=0&modestbranding=1&rel=0&iv_load_policy=3&playsinline=1';
        }
    }

    // ─── SoundCloud ───
    $soundcloud_url = get_user_meta($user_id, '_apollo_soundcloud_url', true)
        ?: 'https://soundcloud.com/apollo-rio/welcome-to-rio';
    $soundcloud_widget = 'https://w.soundcloud.com/player/?url=' . rawurlencode($soundcloud_url) . '&auto_play=false&show_artwork=false';

    // ─── Sound preferences ───
    $sound_prefs = get_user_meta($user_id, '_apollo_sound_preferences', true);
    $sound_tags  = array();
    if (! empty($sound_prefs) && is_array($sound_prefs)) {
        foreach ($sound_prefs as $term_id) {
            $term = get_term((int) $term_id);
            if ($term && ! is_wp_error($term)) {
                $sound_tags[] = $term->name;
            }
        }
    } elseif (is_string($sound_prefs) && ! empty($sound_prefs)) {
        $sound_tags = array_map('trim', explode(',', $sound_prefs));
    }

    // ─── Profile link ───
    $profile_link_raw   = get_user_meta($user_id, '_apollo_website', true);
    $profile_link_label = $profile_link_raw ?: 'sem.link.com.br';
    $profile_link       = $profile_link_label;
    if (! preg_match('/^https?:\/\//i', $profile_link)) {
        $profile_link = 'https://' . $profile_link;
    }

    // ─── Stats ───
    $fav_count     = (int) get_user_meta($user_id, '_apollo_fav_count', true);
    $profile_views = (int) get_user_meta($user_id, '_apollo_profile_views', true);
    $ranking       = get_user_meta($user_id, '_apollo_ranking', true);

    // Record view (side-effect kept here — single entry point)
    if (! $is_own && function_exists('Apollo\\Users\\apollo_record_profile_view')) {
        apollo_record_profile_view($user_id, $current_user_id ?: null);
        $profile_views = (int) get_user_meta($user_id, '_apollo_profile_views', true);
    }

    $hits_display    = $profile_views >= 1000
        ? number_format($profile_views / 1000, 1) . 'k'
        : (string) $profile_views;
    $ranking_display = $ranking ? '#' . $ranking : '#-';

    // ─── Ratings ───
    $rating_averages = Components\RatingHandler::get_averages($user_id);
    $rating_counts   = Components\RatingHandler::get_vote_counts($user_id);
    $user_votes      = $is_logged_in
        ? Components\RatingHandler::get_user_votes($current_user_id, $user_id)
        : array();

    // Confiável — public safety data
    $confiavel_count   = $rating_counts['confiavel'] ?? 0;
    $confiavel_avatars = Components\RatingHandler::get_voter_avatars($user_id, 'confiavel', 4);
    $confiavel_display = $confiavel_count >= 1000
        ? number_format($confiavel_count / 1000, 1, ',', '.') . 'k'
        : number_format($confiavel_count, 0, ',', '.');

    // Admin-only rating data — sealed to admin context
    $admin_ratings = array();
    if ($is_admin) {
        foreach (array('sexy', 'legal', 'confiavel') as $cat) {
            $count          = $rating_counts[$cat] ?? 0;
            $admin_ratings[$cat] = array(
                'count'   => $count,
                'display' => $count >= 1000
                    ? number_format($count / 1000, 1, ',', '.') . 'k'
                    : number_format($count, 0, ',', '.'),
                'avatars' => Components\RatingHandler::get_voter_avatars($user_id, $cat, 4),
            );
        }
    }

    // ─── Feed posts ───
    $feed_posts = get_posts(array(
        'author'         => $user_id,
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'post_type'      => array('post', 'apollo_event', 'apollo_classified'),
    ));

    // ─── Depoimentos ───
    $depoimentos = get_comments(array(
        'type'   => 'apollo_depoimento',
        'parent' => $user_id,
        'number' => 2,
        'offset' => 0,
        'status' => 'approve',
    ));

    // ─── Minimal JS config (no sensitive data) ───
    $js_config = array(
        'targetId'   => $user_id,
        'isLoggedIn' => $is_logged_in,
        'isOwn'      => $is_own,
        'canVote'    => $is_logged_in && ! $is_own,
        'userVotes'  => (object) $user_votes,
        'averages'   => (object) $rating_averages,
        'maxScore'   => 3,
        'editUrl'    => $is_own ? home_url('/editar-perfil/') : '',
    );

    // Nonce only for logged-in users, never in DOM attributes
    if ($is_logged_in) {
        $js_config['nonce']   = wp_create_nonce('apollo_profile_nonce');
        $js_config['ajaxUrl'] = admin_url('admin-ajax.php');
    }

    // Admin-only debug keys
    if ($is_admin) {
        $js_config['isAdmin']    = true;
        $js_config['adminNonce'] = wp_create_nonce('apollo_admin_rating');
    }

    return array(
        'user_id'           => $user_id,
        'display_name'      => $display_name,
        'bio'               => $bio,
        'avatar_url'        => $avatar_url,
        'member_since'      => $member_since,
        'membership'        => $membership,
        'privacy'           => $privacy,
        'is_own'            => $is_own,
        'is_logged_in'      => $is_logged_in,
        'is_admin'          => $is_admin,
        'youtube_embed'     => $youtube_embed,
        'soundcloud_url'    => $soundcloud_url,
        'soundcloud_widget' => $soundcloud_widget,
        'sound_tags'        => $sound_tags,
        'profile_link'      => $profile_link,
        'profile_link_label' => $profile_link_label,
        'stats'             => array(
            'fav_count'       => $fav_count,
            'profile_views'   => $profile_views,
            'hits_display'    => $hits_display,
            'ranking_display' => $ranking_display,
        ),
        'rating_averages'   => $rating_averages,
        'rating_counts'     => $rating_counts,
        'user_votes'        => $user_votes,
        'confiavel'         => array(
            'count'   => $confiavel_count,
            'display' => $confiavel_display,
            'avatars' => $confiavel_avatars,
        ),
        'admin_ratings'     => $admin_ratings,
        'feed_posts'        => $feed_posts,
        'depoimentos'       => $depoimentos,
        'js_config'         => $js_config,
    );
}

/**
 * Enqueue page-specific assets
 *
 * @param string $page_type The type of page (radar, profile, edit-profile)
 * @return void
 */
function apollo_users_enqueue_page_assets(string $page_type): void
{
    $plugin_url = plugin_dir_url(APOLLO_USERS_FILE);
    $version    = defined('WP_DEBUG') && WP_DEBUG ? time() : APOLLO_USERS_VERSION;

    switch ($page_type) {
        case 'radar':
            wp_enqueue_style(
                'apollo-users-radar',
                $plugin_url . 'assets/css/radar.css',
                array(),
                $version
            );
            break;

        case 'profile':
            wp_enqueue_style(
                'apollo-users-profile',
                $plugin_url . 'assets/css/profile.css',
                array(),
                $version
            );
            wp_enqueue_script(
                'apollo-users-profile',
                $plugin_url . 'assets/js/profile.js',
                array('jquery'),
                $version,
                true
            );
            break;

        case 'edit-profile':
            wp_enqueue_style(
                'apollo-users-edit-profile',
                $plugin_url . 'assets/css/edit-profile.css',
                array(),
                $version
            );
            break;
    }
}

/**
 * Check if user is verified
 *
 * @param int $user_id User ID
 * @return bool
 */
function apollo_is_user_verified(int $user_id): bool
{
    return (bool) get_user_meta($user_id, '_apollo_user_verified', true);
}

/**
 * Verify user account
 *
 * @param int $user_id User ID
 * @return bool Success
 */
function apollo_verify_user(int $user_id): bool
{
    $result = update_user_meta($user_id, '_apollo_user_verified', true);

    /**
     * Fires after a user is verified
     *
     * @param int $user_id User ID
     */
    do_action('apollo_user_verified', $user_id);

    return $result !== false;
}

/**
 * Get user profile completion percentage
 *
 * @param int $user_id User ID
 * @return int Percentage (0-100)
 */
function apollo_get_profile_completion(int $user_id): int
{
    $completion = get_user_meta($user_id, '_apollo_profile_completed', true);
    return $completion ? (int) $completion : 0;
}

/**
 * Check if user has Apollo capability
 *
 * @param int    $user_id User ID
 * @param string $capability Capability name
 * @return bool
 */
function apollo_user_can(int $user_id, string $capability): bool
{
    $user = get_userdata($user_id);
    if (! $user) {
        return false;
    }

    return $user->has_cap($capability);
}

/**
 * Get user's Apollo role
 *
 * @param int $user_id User ID
 * @return string|null Apollo role or null
 */
function apollo_get_user_role(int $user_id): ?string
{
    $user = get_userdata($user_id);
    if (! $user) {
        return null;
    }

    $apollo_roles = array('apollo_member', 'apollo_producer', 'apollo_dj', 'apollo_venue', 'apollo_moderator');

    foreach ($user->roles as $role) {
        if (in_array($role, $apollo_roles, true)) {
            return $role;
        }
    }

    return null;
}

/**
 * Get complete user display data for posts/comments/activities
 * Returns: name, badges, memberships, núcleos, @handle, time since registration
 *
 * @param int $user_id User ID
 * @return array Structured user data
 */
function apollo_get_user_display_data(int $user_id): array
{
    $user = get_userdata($user_id);
    if (! $user) {
        return array();
    }

    $data = array(
        'id'           => $user_id,
        'display_name' => $user->display_name,
        'user_login'   => $user->user_login,
        'handle'       => '@' . $user->user_login,
        'profile_url'  => home_url('/id/' . $user->user_login),
        'avatar_url'   => apollo_get_user_avatar_url($user_id),
    );

    // Badge data
    if (function_exists('apollo_get_user_badge_data')) {
        $data['badge'] = apollo_get_user_badge_data($user_id);
    } else {
        $data['badge'] = array(
            'type'    => 'nao-verificado',
            'label'   => '',
            'ri_icon' => '',
            'color'   => '',
        );
    }

    // Memberships (access slugs + profile badge)
    $membership         = apollo_get_user_membership($user_id);
    $data['membership'] = array(
        'level'             => $membership['level'] ?? 'free',
        'status'            => $membership['status'] ?? 'inactive',
        'label'             => $membership['label'] ?? '',
        'type'              => $membership['type'] ?? 'nao-verificado',
        'slugs'             => $membership['slugs'] ?? array(),
        'access_slugs'      => $membership['access_slugs'] ?? array(),
        'membership_active' => $membership['membership_active'] ?? false,
        'is_premium'        => ! empty($membership['membership_active']),
    );

    // Núcleos/Groups user participates in
    if (function_exists('apollo_get_user_groups')) {
        $groups              = apollo_get_user_groups($user_id, 5);
        $data['nucleos']     = array();
        $data['nucleos_raw'] = $groups;

        foreach ($groups as $group) {
            if (isset($group['type']) && $group['type'] === 'nucleo') {
                $data['nucleos'][] = array(
                    'id'   => $group['id'] ?? 0,
                    'name' => $group['name'] ?? '',
                    'slug' => $group['slug'] ?? '',
                );
            }
        }
    } else {
        $data['nucleos']     = array();
        $data['nucleos_raw'] = array();
    }

    // Time since registration
    if (isset($user->user_registered)) {
        $registered_timestamp = strtotime($user->user_registered);
        $now                  = time();
        $diff                 = $now - $registered_timestamp;

        $data['registered_date'] = $user->user_registered;
        $data['member_since']    = date('Y', $registered_timestamp);

        // Calculate time ago
        if (function_exists('apollo_time_ago')) {
            $data['member_for'] = apollo_time_ago($user->user_registered);
        } else {
            $years = floor($diff / (365 * 24 * 60 * 60));
            if ($years > 0) {
                $data['member_for'] = $years . ' ano' . ($years > 1 ? 's' : '');
            } else {
                $months = floor($diff / (30 * 24 * 60 * 60));
                if ($months > 0) {
                    $data['member_for'] = $months . ' ' . ($months > 1 ? 'meses' : 'mês');
                } else {
                    $days               = floor($diff / (24 * 60 * 60));
                    $data['member_for'] = $days . ' ' . ($days > 1 ? 'dias' : 'dia');
                }
            }
        }
    } else {
        $data['registered_date'] = '';
        $data['member_since']    = '';
        $data['member_for']      = '';
    }

    return $data;
}

// ═══════════════════════════════════════════════════════════════════════════
// MEMBERS QUERY — shared helper used across apollo-social, apollo-hub, etc.
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Query Apollo platform members with standardised defaults.
 *
 * Acts as the canonical entry point for any plugin that needs a paginated
 * list of site members. Wraps WP_User_Query and fires filters so external
 * plugins can alter the args without duplicating the query logic.
 *
 * Usage example:
 *   $result = apollo_query_members( array( 'search' => 'ana', 'type' => 'newest' ) );
 *   // $result['users'] → WP_User[]
 *   // $result['total'] → int
 *
 * @param array $query_args {
 *   Optional overrides.
 *   @type int    $number   Users per page. Default 20, max 100.
 *   @type int    $offset   Query offset (page-based). Default 0.
 *   @type string $search   Display-name or login search string. Default ''.
 *   @type string $type     Sort mode: 'newest' | 'alphabetical' | 'active'. Default 'newest'.
 *   @type string $role     WP role filter. Default '' (all roles).
 *   @type bool   $count_total Whether to retrieve the total count. Default true.
 * }
 * @return array{ users: WP_User[], total: int }
 */
function apollo_query_members(array $query_args = array()): array
{
    $defaults = array(
        'number'      => 20,
        'offset'      => 0,
        'search'      => '',
        'type'        => 'newest',
        'role'        => '',
        'count_total' => true,
    );

    $opts   = wp_parse_args($query_args, $defaults);
    $number = min(100, absint($opts['number']));

    $args = array(
        'number'      => $number,
        'offset'      => absint($opts['offset']),
        'count_total' => (bool) $opts['count_total'],
    );

    // Restrict to a specific role when requested
    if (! empty($opts['role'])) {
        $args['role'] = sanitize_key($opts['role']);
    }

    // Display-name / login search
    if (! empty($opts['search'])) {
        $search                 = sanitize_text_field($opts['search']);
        $args['search']         = '*' . $search . '*';
        $args['search_columns'] = array('display_name', 'user_login');
    }

    // Sort order
    switch (sanitize_key($opts['type'])) {
        case 'alphabetical':
            $args['orderby'] = 'display_name';
            $args['order']   = 'ASC';
            break;
        case 'active':
            // Falls back to registered date; apollo-social can filter to `last_activity`
            $args['orderby'] = 'registered';
            $args['order']   = 'DESC';
            break;
        case 'newest':
        default:
            $args['orderby'] = 'registered';
            $args['order']   = 'DESC';
            break;
    }

    /**
     * Filters WP_User_Query args before apollo_query_members executes.
     *
     * @param array $args       WP_User_Query arguments.
     * @param array $query_args Original caller arguments.
     */
    $args = apply_filters('apollo/users/query_members_args', $args, $query_args);

    $q = new \WP_User_Query($args);

    return array(
        'users' => $q->get_results(),
        'total' => (int) $q->get_total(),
    );
}
