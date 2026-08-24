<?php
/**
 * Template: Profile Stats — Canvas template for /id/{username}/stats.
 *
 * Uses wp_head()/wp_footer() (Canvas pattern) with Apollo CDN.
 *
 * Available variables:
 * @var WP_User $user     The profile user object.
 * @var string  $cdn_url  Apollo CDN base URL.
 * @var string  $rest_url REST endpoint for this user's stats.
 * @var string  $nonce    WP REST nonce.
 *
 * @package Apollo\Statistics\Frontend
 * @since   2.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$is_own_profile  = ( $current_user_id > 0 && $current_user_id === $user->ID );
$visibility      = get_user_meta( $user->ID, '_apollo_stats_visibility', true ) ?: 'public';
$avatar_url      = get_avatar_url( $user->ID, array( 'size' => 96 ) );
$member_since    = date_i18n( 'M Y', strtotime( $user->user_registered ) );
$profile_url     = home_url( '/id/' . $user->user_login . '/' );
$stats_url       = APOLLO_STATS_URL;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?php echo esc_html( $user->display_name ); ?> — Stats | Apollo</title>

    <?php wp_head(); ?>

    <!-- Apollo CDN — fonts, tokens, GSAP -->
    <script src="<?php echo esc_url( $cdn_url . 'hub.js' ); ?>" fetchpriority="high"></script>

    <!-- RemixIcon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.min.css">

    <!-- Profile Stats styles -->
    <link rel="stylesheet" href="<?php echo esc_url( $stats_url . 'assets/css/profile-stats.css' ); ?>">

    <style>
    /* Page chrome */
    .ps-page { min-height: 100vh; min-height: 100dvh; background: var(--bg, #fafafa); }
    .ps-topbar {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border, #eee);
        background: var(--surface, #fff);
        position: sticky; top: 0; z-index: 100;
    }
    .ps-topbar-back {
        display: flex; align-items: center; gap: 6px;
        color: var(--muted-txt, #888); font-size: 14px;
        text-decoration: none; border-radius: 8px;
        padding: 6px 10px; transition: background .15s;
    }
    .ps-topbar-back:hover { background: var(--card-hover, #f0f0f0); color: var(--primary, FF9820); }
    .ps-topbar-title { font-size: 14px; font-weight: 600; color: var(--txt-color-hover, #111); margin: 0; }
    .ps-topbar-sub { font-size: 12px; color: var(--muted-txt, #888); margin: 0; }

    /* User hero */
    .ps-hero {
        background: var(--surface, #fff);
        border-bottom: 1px solid var(--border, #eee);
        padding: 24px 16px 16px;
    }
    .ps-hero-inner { max-width: 960px; margin: 0 auto; display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
    .ps-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        object-fit: cover; flex-shrink: 0;
        border: 2px solid var(--border, #eee);
        background: var(--card-hover, #f0f0f0);
    }
    .ps-hero-info { flex: 1; min-width: 0; }
    .ps-hero-name {
        font-size: 20px; font-weight: 700;
        color: var(--txt-color-hover, #111); margin: 0 0 2px;
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .ps-badge-own {
        font-size: 10px; font-weight: 600; text-transform: uppercase;
        letter-spacing: .06em; padding: 2px 8px; border-radius: 20px;
        background: var(--primary, FF9820); color: #fff;
    }
    .ps-hero-handle {
        font-family: var(--ff-mono, monospace);
        font-size: 13px; color: var(--muted-txt, #888); margin: 0 0 6px;
    }
    .ps-hero-meta {
        display: flex; gap: 14px; flex-wrap: wrap;
        font-size: 12px; color: var(--muted-txt, #888);
    }
    .ps-hero-meta span { display: flex; align-items: center; gap: 4px; }

    /* Visibility toggle (own profile only) */
    .ps-visibility {
        display: flex; align-items: center; gap: 8px;
        margin-top: 12px; padding: 10px 12px;
        background: var(--card-hover, #f5f5f5);
        border-radius: 10px; font-size: 13px;
    }
    .ps-visibility select {
        border: none; background: transparent;
        font-size: 13px; color: var(--txt-color-hover, #111);
        cursor: pointer; padding: 0 4px; font-family: inherit;
    }
    .ps-vis-save {
        margin-left: auto; font-size: 12px; font-weight: 600;
        color: var(--primary, FF9820); background: none; border: none;
        cursor: pointer; padding: 4px 8px; border-radius: 6px; font-family: inherit;
        display: none;
    }
    .ps-vis-save.is-visible { display: inline-block; }

    /* Main content */
    .ps-content { max-width: 960px; margin: 0 auto; padding: 20px 16px 48px; }
    @media (min-width: 600px) { .ps-hero { padding: 32px 24px 20px; } .ps-content { padding: 24px 24px 64px; } }
    @media (min-width: 1024px) { .ps-hero { padding: 40px 32px 24px; } .ps-content { padding: 32px 32px 80px; } }
    </style>
</head>
<body class="apollo-stats-profile ps-page"
      data-a-user="<?php echo esc_attr( (string) $user->ID ); ?>"
      data-a-component="profile-stats"
      data-rest-url="<?php echo esc_url( $rest_url ); ?>"
      data-nonce="<?php echo esc_attr( $nonce ); ?>">

    <!-- Top bar -->
    <div class="ps-topbar">
        <a href="<?php echo esc_url( $profile_url ); ?>" class="ps-topbar-back" aria-label="<?php esc_attr_e( 'Voltar ao perfil', 'apollo-statistics' ); ?>">
            <i class="ri-arrow-left-s-line"></i>
            <span><?php echo esc_html( $user->user_login ); ?></span>
        </a>
        <div>
            <p class="ps-topbar-title"><?php esc_html_e( 'Estat\u00edsticas', 'apollo-statistics' ); ?></p>
            <p class="ps-topbar-sub"><?php esc_html_e( 'Atividade e engajamento', 'apollo-statistics' ); ?></p>
        </div>
    </div>

    <!-- User hero -->
    <div class="ps-hero">
        <div class="ps-hero-inner">
            <img class="ps-avatar"
                 src="<?php echo esc_url( $avatar_url ); ?>"
                 alt="<?php echo esc_attr( $user->display_name ); ?>"
                 width="72" height="72"
                 loading="eager">

            <div class="ps-hero-info">
                <h1 class="ps-hero-name">
                    <?php echo esc_html( $user->display_name ); ?>
                    <?php if ( $is_own_profile ) : ?>
                        <span class="ps-badge-own"><?php esc_html_e( 'Voc\u00ea', 'apollo-statistics' ); ?></span>
                    <?php endif; ?>
                </h1>
                <p class="ps-hero-handle">@<?php echo esc_html( $user->user_login ); ?></p>
                <div class="ps-hero-meta">
                    <span>
                        <i class="ri-calendar-line"></i>
                        <?php
                        /* translators: %s: month and year the user registered */
                        printf( esc_html__( 'Desde %s', 'apollo-statistics' ), esc_html( $member_since ) );
                        ?>
                    </span>
                </div>

                <?php if ( $is_own_profile ) : ?>
                <!-- Visibility control -->
                <div class="ps-visibility" id="ps-visibility-wrap">
                    <i class="ri-eye-line" style="color:var(--primary,FF9820);"></i>
                    <label for="ps-vis-select"><?php esc_html_e( 'Quem v\u00ea suas stats:', 'apollo-statistics' ); ?></label>
                    <select id="ps-vis-select" aria-label="<?php esc_attr_e( 'Visibilidade das estat\u00edsticas', 'apollo-statistics' ); ?>">
                        <option value="public"    <?php selected( $visibility, 'public' ); ?>><?php esc_html_e( 'Todos', 'apollo-statistics' ); ?></option>

                        <option value="private"   <?php selected( $visibility, 'private' ); ?>><?php esc_html_e( 'S\u00f3 eu', 'apollo-statistics' ); ?></option>
                    </select>
                    <button type="button" class="ps-vis-save" id="ps-vis-save"
                            data-nonce="<?php echo esc_attr( $nonce ); ?>"
                            data-url="<?php echo esc_url( rest_url( 'apollo/v1/profile-stats/visibility' ) ); ?>">
                        <?php esc_html_e( 'Salvar', 'apollo-statistics' ); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats content (period tabs + visit chart + cards built by profile-stats.js) -->
    <div class="ps-content">
        <div id="apollo-profile-stats"
             class="apollo-stats-container"
             data-a-user="<?php echo esc_attr( (string) $user->ID ); ?>"
             data-field="stats-dashboard">
            <div class="apollo-stats-loading">
                <div class="apollo-spinner"></div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>

    <!-- Profile Stats JS -->
    <script src="<?php echo esc_url( $stats_url . 'assets/js/profile-stats.js' ); ?>"></script>

    <?php if ( $is_own_profile ) : ?>
    <script>
    /* Visibility selector live save */
    (function() {
        var sel = document.getElementById('ps-vis-select');
        var btn = document.getElementById('ps-vis-save');
        if (!sel || !btn) return;

        sel.addEventListener('change', function() {
            btn.classList.add('is-visible');
        });

        btn.addEventListener('click', function() {
            var nonce = btn.getAttribute('data-nonce') || '';
            var url   = btn.getAttribute('data-url') || '';
            if (!url) return;

            var origText = btn.textContent;
            btn.textContent = '\u2026';
            btn.disabled = true;

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                credentials: 'same-origin',
                body: JSON.stringify({ visibility: sel.value })
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                btn.textContent = '\u2713';
                setTimeout(function() {
                    btn.classList.remove('is-visible');
                    btn.textContent = origText;
                    btn.disabled = false;
                }, 1800);
            })
            .catch(function() {
                btn.textContent = '!';
                btn.disabled = false;
            });
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>