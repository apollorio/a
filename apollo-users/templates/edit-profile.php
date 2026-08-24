<?php

/**
 * Edit Profile Template — Canvas Mode
 *
 * Route: /editar-perfil
 * Layout: Edit-mode indicator bar → Hero (editable) → Card (editable inputs) → Stats (read-only) → Feed → Save bar
 *
 * @package Apollo\Users
 */

if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════════
// AUTH CHECK — handled by router but double-check
// ═══════════════════════════════════════════════════════════════════════
if (! is_user_logged_in()) {
    wp_redirect(home_url('/acesso?redirect=' . urlencode(home_url('/editar-perfil'))));
    exit;
}

$user    = wp_get_current_user();
$user_id = $user->ID;

// ─── Current user data ───
$social_name     = get_user_meta($user_id, '_apollo_social_name', true);
$display_name    = $social_name ?: $user->display_name;
$bio             = get_user_meta($user_id, '_apollo_bio', true);
$phone           = get_user_meta($user_id, '_apollo_phone', true);
$location        = get_user_meta($user_id, 'user_location', true);
$instagram       = get_user_meta($user_id, 'instagram', true) ?: $user->user_login;
$website         = get_user_meta($user_id, '_apollo_website', true) ?: $user->user_url;
$privacy_profile = get_user_meta($user_id, '_apollo_privacy_profile', true) ?: 'public';
$privacy_email   = get_user_meta($user_id, '_apollo_privacy_email', true);
$avatar_url      = \Apollo\Users\apollo_get_user_avatar_url($user_id, 'large');
$cover_url       = \Apollo\Users\apollo_get_user_cover_url($user_id);

// ─── YouTube & SoundCloud ───
$youtube_url    = get_user_meta($user_id, '_apollo_youtube_url', true);
$soundcloud_url = get_user_meta($user_id, '_apollo_soundcloud_url', true);

// ─── YouTube embed ───
$youtube_embed = '';
if ($youtube_url) {
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $youtube_url, $yt_matches);
    if (! empty($yt_matches[1])) {
        $youtube_embed = 'https://www.youtube.com/embed/' . $yt_matches[1]
            . '?autoplay=0&mute=1&loop=1&playlist=' . $yt_matches[1]
            . '&controls=0&showinfo=0&modestbranding=1&rel=0';
    }
}

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

// ─── Núcleo tags ───
$nucleo_tags = get_user_meta($user_id, '_apollo_nucleos', true);
if (! is_array($nucleo_tags)) {
    $nucleo_tags = $nucleo_tags ? array_map('trim', explode(',', $nucleo_tags)) : array();
}

// ─── Membership badges (SSOT) ───
$user_slugs  = function_exists('apollo_membership_get_user_slugs') ? apollo_membership_get_user_slugs($user_id) : array();
$is_team     = in_array('apollo', $user_slugs, true);
$is_verified = in_array('verificado', $user_slugs, true) || get_user_meta($user_id, '_apollo_user_verified', true);
$is_cenario  = in_array('cenario', $user_slugs, true);

// ─── Ratings (read-only in edit mode) ───
$averages  = array();
$max_score = 3;
if (class_exists('\\Apollo\\Users\\Components\\RatingHandler')) {
    $averages  = \Apollo\Users\Components\RatingHandler::get_averages($user_id);
    $max_score = \Apollo\Users\Components\RatingHandler::MAX_SCORE;
}

$sexy_rating  = isset($averages['sexy']) ? round($averages['sexy']) : 0;
$legal_rating = isset($averages['legal']) ? round($averages['legal']) : 0;
$trust_rating = isset($averages['confiavel']) ? round($averages['confiavel']) : 0;

// ─── Stats ───
$profile_views = (int) get_user_meta($user_id, '_apollo_profile_views', true);
$favs_count    = (int) get_user_meta($user_id, '_apollo_favs_count', true);
$ranking       = get_user_meta($user_id, '_apollo_ranking', true) ?: '-';
$all_avg       = array_filter(array_values($averages), fn($v) => $v > 0);
$avg_rating    = $all_avg ? number_format(array_sum($all_avg) / count($all_avg), 1) : '0.0';

// Member since
$member_year = date('Y', strtotime($user->user_registered));

// Profile URL
$profile_url = \Apollo\Users\apollo_get_profile_url($user_id);

// REST nonce for uploads, AJAX nonce for form
$rest_nonce = wp_create_nonce('wp_rest');
$ajax_nonce = wp_create_nonce('apollo_profile_nonce');
ob_start();
?>
    <!-- Apollo Forms — minimalist underline design system -->
    <script src="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <?php if (defined('APOLLO_TEMPLATES_URL') && defined('APOLLO_TEMPLATES_VERSION')) : ?>
        <link rel="stylesheet"
            href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/navbar.v2.css'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>">
        <script
            src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/navbar.v2.js'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>"
            defer></script>
    <?php endif; ?>

    <style>
        /* ═══════════════════════════════════════════════════════════
			APOLLO PROFILE — EDIT MODE
			Canvas Mode — Self-contained CSS
			═══════════════════════════════════════════════════════════ */
        /* Editable Input Fields */
        .editable-input {
            background: #f7a71d0e !important;
            border: 0.5px dashed rgba(244, 94, 0, 0.85) !important;
            outline: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: inherit;
            font-size: inherit;
            font-weight: inherit;
            color: inherit;
            width: 100%;
            transition: all 0.3s;
            cursor: text;
        }

        .editable-input:hover {
            background: #fff !important;
            border-color: var(--primary);
        }

        .editable-input:focus {
            background: #fff !important;
            border-color: var(--primary);
            border-style: solid;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(32px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes breathe {

            0%,
            100% {
                opacity: 0.15;
            }

            50% {
                opacity: 0.35;
            }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            color: var(--txt-color);
            font-family: var(--ff-main);
            font-size: var(--fs-p);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            padding-top: 40px;
            padding-left: max(0px, env(safe-area-inset-left));
            padding-right: max(0px, env(safe-area-inset-right));
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
            transition: color 0.25s var(--ease-default);
        }

        img {
            display: block;
            max-width: 100%;
            height: auto;
        }

        .container {
            width: 100%;
            padding: 0 5px;
            margin: 0 auto;
        }

        /* ═══ EDIT MODE INDICATOR ═══ */
        .edit-mode-bar {
            position: fixed;
            transform: rotate(6deg);
            top: 180px;
            width: 110vw;
            left: -10px;
            ;
            right: 0;
            z-index: 999;
            background: rgba(var(--rgb-t), .35);
            color: #fff;
            padding: 7px 15px;
            display: flex;
            -webkit-backdrop-filter: blur(22px);
            backdrop-filter: blur(9px);
            justify-content: space-between;
            align-items: center;
            font-family: var(--ff-mono);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            animation: slideDown 0.4s var(--ease-default);
        }

        .edit-mode-bar a {
            color: hsla(42, 100%, 72%, 0.96);
            transition: opacity 0.3s;
            margin-right: 19px;
        }

        .edit-mode-bar a:hover {
            opacity: 0.7;
        }

        /* ═══ HERO ═══ */
        .hero {
            position: relative;
            padding: 0 0 var(--space-3) 0;
            margin-top: 40px;
            animation: fadeIn 0.8s var(--ease-default);
        }

        .hero-media {
            width: 100%;
            height: 400px;
            border-radius: var(--r-lg);
            overflow: hidden;
            position: relative;
            background: var(--black-1);
            border: none;
            margin-bottom: -200px;
            box-shadow: none;
        }

        .hero-media iframe,
        .hero-media img.hero-cover {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 130%;
            height: 130%;
            transform: translate(-50%, -50%);
            border: none;
            pointer-events: none;
            object-fit: cover;
            filter: grayscale(100%) contrast(1.05);
        }

        .hero-media::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(var(--rgb-d), 0.12) 100%);
            animation: breathe 8s ease-in-out infinite;
            pointer-events: none;
            z-index: 1;
        }

        /* ─── Editable overlay for hero ─── */
        .hero-edit-overlay {
            position: absolute;
            bottom: 16px;
            right: 16px;
            z-index: 10;
            display: flex;
            gap: 8px;
        }

        .hero-edit-btn {
            padding: 8px 16px;
            background: rgba(var(--rgb-d), 0.6);
            color: #fff;
            border: none;
            border-radius: var(--r-sm);
            font-family: var(--ff-mono);
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(8px);
        }

        .hero-edit-btn:hover {
            background: rgba(var(--rgb-d), 0.85);
        }

        .hero-edit-btn.danger:hover {
            background: #dc2626;
        }

        /* ═══ PROFILE CARD ═══ */
        .profile-card {
            background: var(--white-1);
            max-width: 95%;
            margin: 0 3% 0 2%;
            border: none;
            border-radius: var(--r-lg);
            padding: 1.5rem;
            box-shadow: none;
            position: relative;
            animation: fadeUp 0.8s var(--ease-default) 0.1s both;
        }

        .profile-header {
            display: flex;
            gap: 15px;
            margin-bottom: var(--space-3);
        }

        .avatar-wrapper {
            position: relative;
            flex-shrink: 0;
            margin-top: -40px;
        }

        .avatar {
            width: 130px;
            height: 130px;
            border-radius: 30%;
            border: none;
            background: var(--gray-2);
            object-fit: cover;
            box-shadow: none;
            transition: transform 0.6s var(--ease-smooth);
            cursor: pointer;
        }

        .avatar:hover {
            filter: brightness(0.7);
        }

        .avatar-overlay {
            position: absolute;
            inset: 0;
            border-radius: 30%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(var(--rgb-d), 0.4);
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
            color: #fff;
            font-size: 28px;
        }

        .avatar-wrapper:hover .avatar-overlay {
            opacity: 1;
        }

        .profile-identity {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* ─── Editable fields ─── */
        .editable-input {
            font-family: var(--ff-main);
            border: 1px solid transparent;
            border-radius: var(--r-sm);
            padding: 4px 8px;
            background: transparent;
            color: var(----txt-color-hover);
            transition: all 0.3s;
            outline: none;
            width: 100%;
        }

        .editable-input:hover {
            border-color: rgba(var(--rgb-d), 0.06);
            background: var(--card);
        }

        .editable-input:focus {
            border-color: rgba(var(--rgb-d), 0.15);
            background: var(--white-1);
            box-shadow: none;
        }

        .edit-name {
            font-size: clamp(24px, 6vw, 36px);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.03em;
        }

        .edit-bio {
            font-size: 13px;
            line-height: 1.6;
            resize: vertical;
            min-height: 60px;
            font-family: var(--ff-main);
            color: var(--txt-color);
        }

        .edit-url {
            font-family: var(--ff-mono);
            font-size: 11px;
            color: var(--txt-muted);
            font-weight: 600;
        }

        .field-group {
            margin: 12px 0;
        }

        .input-group {
            position: relative;
            margin: 14px 0;
        }

        .input-group .apollo-label {
            position: absolute;
            top: -8px;
            left: 10px;
            background: var(--white-1);
            padding: 0 6px;
            font-family: var(--ff-mono);
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--txt-muted);
            font-weight: 700;
            pointer-events: none;
        }

        .input-group .apollo-input {
            width: 100%;
        }

        .field-label {
            font-family: var(--ff-mono);
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--txt-muted);
            font-weight: 700;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .handle {
            font-size: 12px;
            color: var(--txt-muted);
            font-weight: 500;
            line-height: 1.5;
        }

        .tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 6px 0;
        }

        .tag {
            font-family: var(--ff-mono);
            font-size: 8px;
            text-transform: uppercase;
            padding: 4px 10px;
            border: none;
            border-radius: 100px;
            color: rgba(var(--rgb-d), 0.45);
            background: rgba(var(--rgb-d), 0.04);
            letter-spacing: 0.06em;
            font-weight: 600;
            transition: all 0.4s var(--ease-smooth);
            cursor: default;
        }

        .member-badge {
            text-align: center;
            font-family: var(--ff-mono);
            font-size: 8px;
            color: var(--txt-muted);
            letter-spacing: 0.12em;
            margin: 25px auto 0;
            text-transform: uppercase;
            font-weight: 600;
        }

        .user-membership-badge i {
            font-size: 1.5rem;
            margin: -2px 0 2px;
            display: inline-block;
            color: rgba(var(--rgb-d), .33);
        }

        html.dark-mode .user-membership-badge i {
            color: rgba(var(--rgb-t), .33);
        }

        /* ═══ STATS SECTION (read-only in edit) ═══ */
        .stats-section {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            animation: fadeUp 0.8s var(--ease-default) 0.2s both;
            opacity: 0.75;
        }

        .stat-card {
            background: var(--white-1);
            border: none;
            border-radius: var(--r);
            padding: var(--space-3);
            box-shadow: none;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .stat-title {
            font-family: var(--ff-mono);
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--txt-muted);
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .stat-item {
            background: var(--card);
            padding: 10px;
            border-radius: var(--r-sm);
            text-align: center;
        }

        .stat-label {
            font-size: 9px;
            color: var(--txt-muted);
            text-transform: uppercase;
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 800;
            color: var(----txt-color-hover);
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }

        .ratings-content {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--space-2);
        }

        .rating-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .rating-label {
            font-size: 11px;
            color: var(--txt-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.6;
            text-align: center;
        }

        .rating-icons {
            display: flex;
            gap: 4px;
            justify-content: center;
        }

        .rating-icon {
            font-size: 20px;
            opacity: 0.15;
            filter: grayscale(100%);
        }

        .rating-icon.filled {
            opacity: 1;
            filter: grayscale(0%);
        }

        /* ═══ PRIVACY SECTION ═══ */
        .privacy-section {
            margin-top: 16px;
            padding: 20px;
            background: var(--white-1);
            border: none;
            border-radius: var(--r);
            animation: fadeUp 0.8s var(--ease-default) 0.3s both;
        }

        .privacy-section h3 {
            font-family: var(--ff-mono);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--txt-muted);
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .privacy-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(var(--rgb-d), 0.04);
        }

        .privacy-row:last-child {
            border-bottom: none;
        }

        .privacy-row label {
            font-size: 13px;
            color: var(--txt-color);
            font-weight: 500;
        }

        .privacy-row small {
            font-size: 11px;
            color: var(--txt-muted);
            display: block;
            margin-top: 2px;
        }

        .privacy-select {
            padding: 6px 28px 6px 12px;
            border: none;
            border-bottom: 1px solid rgba(var(--rgb-d), 0.08);
            border-radius: 0;
            font-family: var(--ff-mono);
            font-size: 11px;
            background: transparent;
            color: var(--txt-color);
            cursor: pointer;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            transition: border-color 0.25s cubic-bezier(.16, 1, .3, 1);
        }

        .privacy-select:focus {
            border-color: rgba(var(--rgb-d), 0.25);
        }

        .privacy-select:hover {
            border-color: rgba(var(--rgb-d), 0.18);
        }

        .privacy-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .privacy-checkbox input[type="checkbox"] {
            accent-color: rgba(var(--rgb-d), 0.65);
            width: 16px;
            height: 16px;
        }

        /* ═══ FEED SECTION ═══ */
        .feed-section {
            margin-top: var(--space-6);
            animation: fadeUp 0.8s var(--ease-default) 0.4s both;
        }

        .feed-tabs {
            display: flex;
            gap: var(--space-2);
            margin-bottom: 28px;
            padding-bottom: var(--space-3);
            border-bottom: none;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .feed-tabs::-webkit-scrollbar {
            display: none;
        }

        .feed-tab {
            padding: 10px 18px;
            font-family: var(--ff-mono);
            font-size: 11px;
            font-weight: 700;
            color: rgba(var(--rgb-d), 0.35);
            border-radius: 100px;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 0.04em;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .feed-tab:hover {
            background: var(--card);
            color: var(----txt-color-hover);
        }

        .feed-tab.active {
            background: var(--black-1);
            color: #fff;
            border-color: var(--black-1);
        }

        .feed-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }

        .feed-card {
            background: var(--white-1);
            border: none;
            border-radius: var(--r-lg);
            overflow: hidden;
            position: relative;
            transition: all 0.5s var(--ease-smooth);
            box-shadow: none;
        }

        .feed-card:hover {
            border-color: transparent;
            transform: translateY(-4px);
            box-shadow: none;
        }

        .feed-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            filter: grayscale(100%) contrast(1.05);
            transition: all 0.6s var(--ease-smooth);
        }

        .feed-card:hover .feed-image {
            filter: grayscale(0%);
        }

        .feed-body {
            padding: var(--space-4);
        }

        .feed-meta {
            font-family: var(--ff-mono);
            font-size: 10px;
            color: rgba(var(--rgb-d), 0.35);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 0.08em;
        }

        .feed-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(----txt-color-hover);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .feed-text {
            font-size: 14px;
            color: var(--txt-muted);
            line-height: 1.6;
            margin-bottom: 12px;
        }

        /* Feed card edit controls */
        .feed-card-actions {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 4px;
            z-index: 5;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .feed-card:hover .feed-card-actions {
            opacity: 1;
        }

        .feed-action-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: rgba(var(--rgb-d), 0.5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            backdrop-filter: blur(4px);
        }

        .feed-action-btn:hover {
            background: rgba(var(--rgb-d), 0.85);
        }

        .feed-action-btn.danger:hover {
            background: #dc2626;
        }

        /* ═══ SAVE/CANCEL BAR ═══ */
        .save-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 999;
            background: var(--white-1);
            border-top: none;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideUp 0.4s var(--ease-default);
            box-shadow: none;
        }

        .save-bar-info {
            font-family: var(--ff-mono);
            font-size: 11px;
            color: var(--txt-muted);
        }

        .save-bar-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 10px 24px;
            border-radius: var(--r-sm);
            font-family: var(--ff-mono);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .btn-ghost {
            background: transparent;
            color: var(--txt-color);
        }

        .btn-ghost:hover {
            background: var(--card);
        }

        .btn-primary {
            background: var(--black-1);
            color: #fff;
            border-color: var(--black-1);
        }

        .btn-primary:hover {
            background: rgba(var(--rgb-d), 0.7);
            transform: translateY(-2px);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ═══ MESSAGE TOAST ═══ */
        .toast {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 10000;
            padding: 12px 20px;
            border-radius: var(--r-sm);
            font-family: var(--ff-mono);
            font-size: 12px;
            font-weight: 700;
            display: none;
            animation: fadeIn 0.3s;
            box-shadow: none;
        }

        .toast.success {
            background: #22c55e;
            color: #fff;
            display: block;
        }

        .toast.error {
            background: #dc2626;
            color: #fff;
            display: block;
        }

        /* ═══ DESKTOP ═══ */
        @media (min-width: 980px) {
            body {
                padding-top: 4px;
            }

            .edit-mode-bar {
                top: 64px;
            }

            .hero {
                margin-top: 40px;
            }

            .container {
                max-width: 1400px;
                padding: 0 var(--space-6);
            }

            .hero-media {
                height: 500px;
                margin-bottom: -250px;
            }

            .hero-layout {
                display: grid;
                margin: 0 3% 0 2%;
                grid-template-columns: 1fr 380px;
                gap: 2px;
                position: relative;
                z-index: 10;
            }

            .profile-card {
                padding: 1.5rem;
                margin: 0 2px 0 3.5%;
            }

            .avatar {
                width: 140px;
                height: 140px;
            }

            .edit-name {
                font-size: var(--fs-h2);
            }

            .feed-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 4px;
            }

            .feed-image {
                height: 280px;
            }
        }

        @media (min-width: 1400px) {
            .container {
                max-width: 1600px;
            }

            .hero-layout {
                grid-template-columns: 1fr 420px;
                gap: var(--space-5);
            }

            .feed-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (pointer: coarse) {
            .hero-edit-btn,
            .profile-control-btn,
            .btn,
            .feed-tab {
                min-height: 44px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }
        }

        /* Extra bottom padding for save bar */
        body {
            padding-bottom: 80px;
        }

        /* Profile Controls - Only visible to profile owner */
        .profile-controls {
            position: absolute;
            top: var(--space-3);
            right: var(--space-3);
            display: flex;
            gap: 4px;
            z-index: 10;
        }

        .profile-control-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(var(--rgb-d), 0.04);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s var(--ease-smooth);
            color: var(--gray-9);
            font-size: 16px;
        }

        .profile-control-btn:hover {
            background: var(--black-1);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ─── GLOBAL TOOLTIPS ──────────────────────────────────────────────── */
        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%) translateY(-4px);
            padding: 6px 12px;
            background: var(--ink);
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.4;
            white-space: nowrap;
            border-radius: 6px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 1000;
        }

        [data-tooltip]::after {
            content: "";
            position: absolute;
            bottom: calc(100% + 2px);
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: var(--ink);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 1000;
        }

        [data-tooltip]:hover::before,
        [data-tooltip]:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    </style>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'Editar Perfil | Apollo',
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body>
    <?php apollo_render_navbar(); ?>

    <!-- ═══ EDIT MODE INDICATOR ═══
	<div class="edit-mode-bar">
		<span><i class="ri-edit-line"></i> Modo Edição</span>
		<a href="<?php echo esc_url($profile_url); ?>"><i class="ri-body-scan-line"></i> Ver minha página</a>
	</div>  -->

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <form id="edit-profile-form" data-a-user="<?php echo esc_attr((string) $user_id); ?>" data-a-component="edit-profile">
        <input type="hidden" name="action" value="apollo_update_profile">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($ajax_nonce); ?>">

        <div class="container">
            <section class="hero">
                <!-- ═══ HERO MEDIA (editable) ═══ -->
                <div class="hero-media">
                    <?php if ($youtube_embed) : ?>
                        <iframe src="<?php echo esc_url($youtube_embed); ?>" allow="encrypted-media" loading="lazy"
                            title="Hero video"></iframe>
                    <?php elseif ($cover_url) : ?>
                        <img src="<?php echo esc_url($cover_url); ?>" class="hero-cover" id="cover-preview" alt="Cover">
                    <?php else : ?>
                        <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--card) 0%, var(--gray-2) 100%);display:flex;align-items:center;justify-content:center;"
                            id="cover-placeholder">
                            <i class="ri-image-add-line" style="font-size:48px;color:var(--border);"></i>
                        </div>
                    <?php endif; ?>
                    <div class="hero-edit-overlay">
                        <button type="button" class="hero-edit-btn" id="change-cover" data-tooltip="Alterar Cover">
                            <i class="ri-image-edit-line"></i>
                        </button>
                        <?php if ($cover_url) : ?>
                            <button type="button" class="hero-edit-btn danger" id="remove-cover" data-tooltip="Remover Cover">
                                <i class="ri-delete-bin-7-line"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="cover-input" accept="image/*" style="display:none">
                </div>

                <div class="hero-layout">
                    <!-- ═══ PROFILE CARD (editable) ═══ -->
                    <div class="profile-card">

                        <!-- Profile Controls: Only visible to profile owner -->

                        <div class="profile-controls">
                            <button type="submit" class="profile-control-btn no-sel" id="edit-btn"
                                title="Salvar alterações">
                                <i class=" ri-save-3-fill"></i>
                            </button>
                        </div>


                        <div class="profile-header">
                            <div class="avatar-wrapper">
                                <img src="<?php echo esc_url($avatar_url); ?>" class="avatar" id="avatar-preview"
                                    alt="Avatar">
                                <div class="avatar-overlay" id="change-avatar" title="Trocar avatar">
                                    <i class="ri-camera-line"></i>
                                </div>
                                <input type="file" id="avatar-input" accept="image/*" style="display:none">
                            </div>
                            <div class="profile-identity">
                                <input type="text" name="social_name" class="apollo-input editable-input edit-name"
                                    style="max-width:80%" value="<?php echo esc_attr($display_name); ?>"
                                    placeholder="Seu nome" maxlength="60">

                                <?php if (! empty($nucleo_tags)) : ?>
                                    <div class="tags">
                                        <?php foreach ($nucleo_tags as $nt) : ?>
                                            <span class="tag"><?php echo esc_html($nt); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <span class="handle">@<?php echo esc_html($user->user_login); ?></span>
                            </div>
                        </div>

                        <!-- Editable Bio -->
                        <div class="field-group input-group">
                            <label class="field-label apollo-label"><i class="ri-file-text-line"></i> Bio <small
                                style="font-weight:400;opacity:0.6;">(<?php echo strlen($bio); ?>/500)</small></label>
                            <textarea name="bio" class="editable-input edit-bio apollo-input" maxlength="500"
                            placeholder="Conte um pouco sobre você..."><?php echo esc_textarea($bio); ?></textarea>
                        </div>

                        <!-- Editable Website -->
                        <div class="field-group input-group">
                            <label class="field-label apollo-label"><i class="ri-global-line"></i> Website</label>
                            <input type="url" name="website" class="editable-input edit-url apollo-input" style="max-width:70%"
                                value="<?php echo esc_attr($website); ?>" placeholder="https://seusite.com">
                        </div>

                        <!-- Editable YouTube URL -->
                        <div class="field-group input-group">
                            <label class="field-label apollo-label"><i class="ri-movie-line"></i> YouTube (vídeo do hero)</label>
                            <input type="url" name="youtube_url" class="editable-input edit-url apollo-input"
                                value="<?php echo esc_attr($youtube_url); ?>"
                                placeholder="https://youtube.com/watch?v=...">
                        </div>

                        <!-- Editable SoundCloud URL -->
                        <div class="field-group input-group">
                            <label class="field-label apollo-label"><i class="ri-music-2-line"></i> SoundCloud</label>
                            <input type="url" name="soundcloud_url" class="editable-input edit-url apollo-input"
                                value="<?php echo esc_attr($soundcloud_url); ?>"
                                placeholder="https://soundcloud.com/seu-perfil">
                        </div>

                        <!-- Location -->
                        <div class="field-group input-group">
                            <label class="field-label apollo-label"><i class="ri-map-pin-line"></i> Cidade</label>
                            <input type="text" name="location" class="editable-input edit-url apollo-input" style="max-width:70%"
                                value="<?php echo esc_attr($location); ?>" placeholder="Rio de Janeiro, RJ">
                        </div>

                        <?php
                        // Sound preferences (read-only display, managed elsewhere)
                        ?>
                        <?php if (! empty($sound_tags)) : ?>
                            <div class="field-group">
                                <label class="field-label"><i class="ri-music-2-line"></i> Preferências Musicais</label>
                                <div class="tags">
                                    <?php foreach ($sound_tags as $st) : ?>
                                        <span class="tag"
                                            style="border-color:var(--primary);color:var(--primary);"><?php echo esc_html($st); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="member-badge">Member since <?php echo esc_html($member_year); ?></div>
                    </div>

                    <!-- ═══ Stats Section (read-only) ═══ -->
                    <div class="stats-section">
                        <!-- Ratings (read-only) -->
                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-title"><i class="ri-star-smile-line"></i> Ratings <small
                                        style="opacity:0.5;font-weight:400;">(somente leitura)</small></span>
                            </div>
                            <div class="ratings-content">
                                <div class="rating-column">
                                    <span class="rating-label">Sexy</span>
                                    <div class="rating-icons">
                                        <?php
                                        for ($i = 1; $i <= $max_score; $i++) :
                                            $filled = $i <= $sexy_rating ? 'filled' : '';
                                            $icon   = $i <= $sexy_rating ? 'ri-heart-3-fill' : 'ri-heart-3-line';
                                        ?>
                                            <i class="<?php echo esc_attr( $icon ); ?> rating-icon <?php echo esc_attr( $filled ); ?>"
                                                style="color:FF9820;cursor:default;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="rating-column">
                                    <div class="rating-icons">
                                        <?php
                                        for ($i = 1; $i <= $max_score; $i++) :
                                            $filled = $i <= $legal_rating ? 'filled' : '';
                                            $icon   = $i <= $legal_rating ? 'ri-user-smile-fill' : 'ri-user-smile-line';
                                        ?>
                                            <i class="<?php echo esc_attr( $icon ); ?> rating-icon <?php echo esc_attr( $filled ); ?>"
                                                style="color:#22c55e;cursor:default;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-label">Legal</span>
                                </div>
                                <div class="rating-column">
                                    <div class="rating-icons">
                                        <?php
                                        for ($i = 1; $i <= $max_score; $i++) :
                                            $filled = $i <= $trust_rating ? 'filled' : '';
                                            $icon   = $i <= $trust_rating ? 'ri-instance-fill' : 'ri-instance-line';
                                        ?>
                                            <i class="<?php echo esc_attr( $icon ); ?> rating-icon <?php echo esc_attr( $filled ); ?>"
                                                style="color:#3b82f6;cursor:default;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-label">Confiável</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats (read-only) -->
                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-title"><i class="ri-line-chart-line"></i> Stats</span>
                            </div>
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <span class="stat-label">Favs</span>
                                    <span class="stat-value"><?php echo number_format($favs_count); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Hits</span>
                                    <span class="stat-value"><?php echo number_format($profile_views); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Ranking</span>
                                    <span class="stat-value">#<?php echo esc_html($ranking); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Rate</span>
                                    <span class="stat-value"><?php echo esc_html($avg_rating); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ Privacy Section ═══ -->
            <div class="privacy-section">
                <h3><i class="ri-shield-keyhole-fill"></i> Privacidade</h3>
                <div class="privacy-row">
                    <div>
                        <label for="privacy_profile">Quem pode ver meu perfil</label>
                        <small>Controla a visibilidade do seu perfil público</small>
                    </div>
                    <select name="privacy_profile" id="privacy_profile" class="privacy-select">
                        <option value="public" <?php selected($privacy_profile, 'public'); ?>>Público</option>
                        <option value="members" <?php selected($privacy_profile, 'members'); ?>>Membros</option>
                        <option value="private" <?php selected($privacy_profile, 'private'); ?>>Privado</option>
                    </select>
                </div>
                <div class="privacy-row">
                    <div>
                        <label>Ocultar meu e-mail no perfil</label>
                        <small>Seu e-mail não será visível para outros membros</small>
                    </div>
                    <div class="privacy-checkbox">
                        <input type="checkbox" name="privacy_email" id="privacy_email" value="1"
                            <?php checked($privacy_email); ?>>
                    </div>
                </div>
            </div>

            <!-- ═══ Feed Section (with edit controls) ═══ -->
            <section class="feed-section">
                <div class="feed-tabs">
                    <button type="button" class="feed-tab active" data-tab="all">Minhas Postagens</button>
                    <button type="button" class="feed-tab" data-tab="apollo_event">Eventos</button>
                    <button type="button" class="feed-tab" data-tab="apollo_classified">Classificados</button>
                </div>
                <div class="feed-grid">
                    <?php
                    $user_feed = new WP_Query(
                        array(
                            'author'         => $user_id,
                            'posts_per_page' => 6,
                            'post_status'    => 'publish',
                            'post_type'      => array('post', 'apollo_event', 'apollo_classified'),
                        )
                    );
                    if ($user_feed->have_posts()) :
                        while ($user_feed->have_posts()) :
                            $user_feed->the_post();
                            $post_type  = get_post_type();
                            $type_obj   = get_post_type_object($post_type);
                            $type_label = $type_obj ? $type_obj->labels->singular_name : 'Post';
                    ?>
                            <article class="feed-card" data-type="<?php echo esc_attr($post_type); ?>">
                                <div class="feed-card-actions">
                                    <a href="<?php echo get_edit_post_link(); ?>" class="feed-action-btn" title="Editar"><i
                                            class="ri-edit-line"></i></a>
                                </div>
                                <?php if (has_post_thumbnail()) : ?>
                                    <img src="<?php echo get_the_post_thumbnail_url(null, 'large'); ?>" class="feed-image"
                                        alt="<?php the_title_attribute(); ?>">
                                <?php endif; ?>
                                <div class="feed-body">
                                    <div class="feed-meta"><?php echo esc_html($type_label); ?></div>
                                    <h3 class="feed-title"><?php the_title(); ?></h3>
                                    <p class="feed-text"><?php echo wp_trim_words(get_the_excerpt(), 16); ?></p>
                                </div>
                            </article>
                        <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        ?>
                        <p style="grid-column:1/-1;text-align:center;padding:40px;color:var(--txt-muted);">Nenhuma postagem
                            ainda</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- ═══ SAVE / CANCEL BAR ═══ -->
        <div class="save-bar">
            <span class="save-bar-info" id="save-bar-status">Todas as alterações são salvas ao clicar em "Salvar"</span>
            <div class="save-bar-actions">
                <a href="<?php echo esc_url($profile_url); ?>" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary" id="save-btn">
                    <i class="ri-save-line"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            var restUrl = '<?php echo esc_url(rest_url('apollo/v1/profile/')); ?>';
            var restNonce = '<?php echo esc_js($rest_nonce); ?>';
            var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

            function showToast(type, msg) {
                var t = document.getElementById('toast');
                t.textContent = msg;
                t.className = 'toast ' + type;
                setTimeout(function() {
                    t.className = 'toast';
                }, 4000);
            }

            // ═══ FORM SUBMIT (AJAX) ═══
            document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('save-btn');
                btn.disabled = true;
                btn.innerHTML = '<i class="ri-loader-4-line"></i> Salvando...';
                var fd = new FormData(this);
                fd.append('_wpnonce', document.querySelector('input[name="nonce"]').value);
                fetch(ajaxUrl, {
                        method: 'POST',
                        body: fd
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ri-save-line"></i> Salvar Alterações';
                        if (data.success) {
                            showToast('success', data.data.message || 'Perfil atualizado!');
                        } else {
                            showToast('error', data.data.message || 'Erro ao salvar.');
                        }
                    })
                    .catch(function() {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ri-save-line"></i> Salvar Alterações';
                        showToast('error', 'Erro de conexão.');
                    });
            });

            // ═══ AVATAR UPLOAD ═══
            document.getElementById('change-avatar').addEventListener('click', function() {
                document.getElementById('avatar-input').click();
            });
            document.getElementById('avatar-input').addEventListener('change', function() {
                if (!this.files[0]) return;
                var fd = new FormData();
                fd.append('file', this.files[0]);
                fetch(restUrl + 'avatar', {
                        method: 'POST',
                        headers: {
                            'X-WP-Nonce': restNonce
                        },
                        body: fd
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.avatar_url) {
                            document.getElementById('avatar-preview').src = data.avatar_url;
                            showToast('success', 'Avatar atualizado!');
                        } else {
                            showToast('error', data.message || 'Erro no upload.');
                        }
                    })
                    .catch(function() {
                        showToast('error', 'Erro no upload do avatar.');
                    });
            });

            // ═══ COVER UPLOAD ═══
            document.getElementById('change-cover').addEventListener('click', function() {
                document.getElementById('cover-input').click();
            });
            document.getElementById('cover-input').addEventListener('change', function() {
                if (!this.files[0]) return;
                var fd = new FormData();
                fd.append('file', this.files[0]);
                fetch(restUrl + 'cover', {
                        method: 'POST',
                        headers: {
                            'X-WP-Nonce': restNonce
                        },
                        body: fd
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.cover_url) {
                            location.reload();
                        } else {
                            showToast('error', data.message || 'Erro no upload.');
                        }
                    })
                    .catch(function() {
                        showToast('error', 'Erro no upload da cover.');
                    });
            });

            // ═══ COVER DELETE ═══
            var removeCover = document.getElementById('remove-cover');
            if (removeCover) {
                removeCover.addEventListener('click', function() {
                    if (!confirm('Remover imagem de capa?')) return;
                    fetch(restUrl + 'cover', {
                            method: 'DELETE',
                            headers: {
                                'X-WP-Nonce': restNonce
                            }
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (data.success) location.reload();
                            else showToast('error', 'Erro ao remover.');
                        })
                        .catch(function() {
                            showToast('error', 'Erro ao remover cover.');
                        });
                });
            }

            // ═══ BIO COUNTER ═══
            var bioField = document.querySelector('textarea[name="bio"]');
            var bioLabel = bioField.closest('.field-group').querySelector('small');
            if (bioField && bioLabel) {
                bioField.addEventListener('input', function() {
                    bioLabel.textContent = '(' + this.value.length + '/500)';
                });
            }

            // ═══ FEED TABS ═══
            document.querySelectorAll('.feed-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.feed-tab').forEach(function(t) {
                        t.classList.remove('active');
                    });
                    this.classList.add('active');
                    var tabType = this.dataset.tab;
                    document.querySelectorAll('.feed-card').forEach(function(card) {
                        card.style.display = (tabType === 'all' || card.dataset.type ===
                            tabType) ? '' : 'none';
                    });
                });
            });

            // ═══ GSAP ═══
            if (typeof gsap !== 'undefined') {
                gsap.from('.profile-card', {
                    y: 40,
                    opacity: 0,
                    duration: 1,
                    ease: 'power3.out'
                });
                gsap.from('.stat-card', {
                    y: 30,
                    opacity: 0,
                    duration: 0.8,
                    stagger: 0.15,
                    ease: 'power2.out',
                    delay: 0.3
                });
                gsap.from('.privacy-section', {
                    y: 20,
                    opacity: 0,
                    duration: 0.6,
                    ease: 'power2.out',
                    delay: 0.5
                });
            }
        });
    </script>
</body>

</html>