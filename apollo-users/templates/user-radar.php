<?php

/**
 * User Radar Template — Canvas Mode
 *
 * Apollo Radar: visual directory of all registered users.
 * Uses Apollo CDN for base styles, GSAP animations, RemixIcon.
 * NO wp_head / wp_footer — full Canvas Mode.
 *
 * Loaded by: apollo-users.php → apollo_users_handle_virtual_pages()
 * Route:     /radar
 *
 * ─── BADGE SYSTEM (user meta keys) ───────────────────────────
 * _apollo_verified  → Verified user (ri-shield-check-fill)
 * _apollo_team      → Apollo team member (i-apollo-fill)
 * _apollo_cenario   → Cena::rio member (ri-disc-line)
 *
 * ─── RANKING ─────────────────────────────────────────────────
 * _apollo_ranking   → Numeric position (lower = higher rank)
 *
 * TODO: When membership/badges system is built, replace
 *       get_user_meta() calls with the membership API.
 *       Badge icons and filter buttons should be dynamically
 *       generated from the membership tiers configuration.
 * ─────────────────────────────────────────────────────────────
 *
 * @package Apollo\Users
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}
ob_start();
?>
    <style>
        /* ============================================
			CUSTOM STYLES FOR APOLLO RADAR
			============================================ */

        :root {
            --radar-spacing: 40px;
            --card-width: 240px;
            --card-height: 350px;
        }

        body {
            background: var(--bg) !important;
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
        }

        /* ============================================
			HEADER SECTION
			============================================ */
        .radar-header {
            padding: var(--space-6) var(--space-4) var(--space-4);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-4);
            position: relative;
        }

        .radar-brand {
            font-family: var(--ff-mono);
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: rgba(var(--txt-rgb), 0.87);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .live-indicator {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            animation: pulse-radar 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse-radar {

            0%,
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(244, 95, 0, 0.7);
            }

            50% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(244, 95, 0, 0);
            }
        }

        /* ============================================
			FILTER BAR
			============================================ */
        .filter-bar {
            display: flex;
            gap: var(--space-2);
            padding: var(--space-1);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 100px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-btn {
            padding: var(--space-2) var(--space-3);
            border-radius: 100px;
            border: none;
            background: transparent;
            font-family: var(--ff-mono);
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(var(--txt-rgb), 0.55);
            cursor: pointer;
            transition: all var(--transition-ui);
        }

        .filter-btn:hover {
            color: rgba(var(--txt-rgb), 0.77);
            background: var(--card);
        }

        .filter-btn.active {
            background: var(--white-1);
            color: rgba(var(--txt-rgb), 0.87);
            box-shadow: 0 4px 12px rgba(var(--rgb-d), 0.08);
        }

        /* Dark mode adjustments */
        html.dark-mode .filter-btn.active {
            background: var(--white-3);
        }

        /* ============================================
			RADAR GRID
			============================================ */
        .radar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(var(--card-width), 1fr));
            gap: var(--radar-spacing);
            padding: var(--radar-spacing) 5%;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
            justify-items: center;
        }

        /* ============================================
			USER CARD
			Each .user-card is generated dynamically
			from the WP_User_Query loop below.
			============================================ */
        .user-card {
            width: var(--card-width);
            height: var(--card-height);
            border: 4px solid var(--white-1);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(var(--rgb-d), 0.12),
                0 0 0 1px var(--border);
            position: relative;
            background: var(--black-10);
            transition: transform 0.4s var(--ease-default);
            cursor: pointer;
        }

        .user-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 60px -15px rgba(var(--rgb-d), 0.2);
            z-index: 2;
        }

        .user-card::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 2;
            background: linear-gradient(to bottom,
                    rgba(var(--rgb-d), 0) 0%,
                    rgba(var(--rgb-d), 0.2) 50%,
                    rgba(var(--rgb-d), 0.85) 100%);
        }

        .user-card__media {
            margin: 0;
            height: 100%;
            width: 100%;
        }

        .user-card__avatar {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s var(--ease-default);
        }

        .user-card:hover .user-card__avatar {
            transform: scale(1.08);
        }

        .user-card__body {
            position: absolute;
            bottom: 25px;
            left: 20px;
            right: 20px;
            color: #fff;
            z-index: 5;
        }

        .user-card__header {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
        }

        .user-card__name {
            font-family: var(--ff-mono);
            font-size: 1.25rem;
            line-height: 1.1;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 6px rgba(var(--rgb-d), 0.4);
            margin: 0;
        }

        .user-badges {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .user-badges i {
            font-size: 1.1rem;
            color: #fff;
            filter: drop-shadow(0 2px 4px rgba(var(--rgb-d), 0.5));
        }

        .user-card__bio {
            font-family: var(--ff-main);
            font-size: 0.8rem;
            font-weight: 400;
            margin-bottom: var(--space-3);
            opacity: 0.9;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-shadow: 0 1px 3px rgba(var(--rgb-d), 0.3);
        }

        .user-card__footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-card__stats {
            display: flex;
            gap: var(--space-2);
        }

        .user-card__stat {
            display: flex;
            align-items: center;
            gap: 4px;
            font-family: var(--ff-main);
            font-size: 0.625rem;
            font-weight: 600;
            background: rgba(var(--rgb-t), 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(var(--rgb-t), 0.2);
            border-radius: 100px;
            padding: 4px 10px;
            color: #fff;
            transition: all 0.2s var(--ease-default);
            text-decoration: none;
        }

        .user-card__stat:hover {
            background: rgba(var(--rgb-t), 0.3);
            transform: translateY(-1px);
        }

        .user-card__stat i {
            font-size: 0.875rem;
        }

        /* ============================================
			FILTER STATE: Hidden cards
			============================================ */
        .user-card.hidden {
            display: none;
        }

        /* Dark mode card adjustments */
        html.dark-mode .user-card {
            border-color: var(--black-1);
            box-shadow: 0 20px 40px -10px rgba(var(--rgb-d), 0.4),
                0 0 0 1px var(--border);
        }

        html.dark-mode .user-card:hover {
            box-shadow: 0 30px 60px -15px rgba(var(--rgb-d), 0.6);
        }
    </style>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'Apollo | Radar de User::rio',
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

<?php
/* Blank Canvas APOLLO+ — opens <body class="ax-body"> and prints the mandatory
   shell chrome (topbar + panels, or the login control when logged out).
   `ax-body` also supplies the top padding the fixed .ax-top needs, so the
   relative .radar-header below flows correctly beneath it. */
if ( function_exists( 'apollo_render_blank_canvas_body' ) ) {
	apollo_render_blank_canvas_body( array( 'variant' => 'plus' ) );
} else {
	echo '<body class="ax-body">';
}
?>

    <!-- ============================================
		HEADER: Brand + Filters + Search
		============================================ -->
    <header class="radar-header">
        <div class="radar-brand">
            <div class="live-indicator"></div>
            APOLLO RADAR
        </div>

        <!-- ============================================
			FILTER BAR
			TODO: When membership system is ready, generate
			filter buttons dynamically from badge/tier config.
			Current badges:
				- verified  → _apollo_verified meta
				- apollo-team → _apollo_team meta
				- cenario   → _apollo_cenario meta
			============================================ -->
        <div class="filter-bar">
            <button class="filter-btn active" data-filter="all">Todos</button>
            <button class="filter-btn" data-filter="verified">Verificadxs</button>
            <button class="filter-btn" data-filter="apollo-team">Apollo::team</button>
            <button class="filter-btn" data-filter="cenario">Cena::rio</button>
        </div>
    </header>

    <!-- ============================================
		USER GRID — WordPress WP_User_Query Loop
		============================================ -->
    <div class="radar-grid">

        <?php
        /**
         * ─── USER QUERY ──────────────────────────────────────
         * Fetches ALL users ordered by ranking.
         *
         * Meta keys used:
         *   _apollo_ranking   → numeric rank (1 = top)
         *   _apollo_verified  → truthy = verified badge
         *   _apollo_team      → truthy = Apollo team badge
         *   _apollo_cenario   → truthy = Cena::rio badge
         *   description       → WP native bio field
         *
         * TODO: When membership plugin is integrated,
         *       replace meta queries with membership API calls.
         * ──────────────────────────────────────────────────────
         */
        $args = array(
            'number'   => -1,
            'orderby'  => 'meta_value_num',
            'meta_key' => '_apollo_ranking',
            'order'    => 'ASC',
        );

        $user_query = new WP_User_Query($args);
        $users      = $user_query->get_results();

        if (! empty($users)) :
            $rank_counter = 0;

            foreach ($users as $user) :
                ++$rank_counter;

                // ── Membership slugs (SSOT) ──
                $user_badge   = function_exists('apollo_membership_get_user_badge') ? apollo_membership_get_user_badge($user->ID) : 'nao-verificado';
                $user_slugs   = function_exists('apollo_membership_get_user_slugs') ? apollo_membership_get_user_slugs($user->ID) : array($user_badge);
                $is_verified    = in_array('verificado', $user_slugs, true) || get_user_meta($user->ID, '_apollo_user_verified', true);
                $is_apollo_team = in_array('apollo', $user_slugs, true);
                $is_cenario     = in_array('cenario', $user_slugs, true);

                // ── User display data ──
                $avatar_url = get_avatar_url($user->ID, array('size' => 400));
                $bio        = get_user_meta($user->ID, 'description', true);
                $ranking    = get_user_meta($user->ID, '_apollo_ranking', true);

                // Fallback: if no ranking meta, use loop counter
                if (empty($ranking)) {
                    $ranking = $rank_counter;
                }

                // Profile URL — force /id/username
                $profile_url = home_url('/id/' . $user->user_login);
        ?>

                <!-- ── USER CARD ────────────────────────────────────
			Data attributes power the JS filter system.
			data-verified="true|false"
			data-apollo-team="true|false"
			data-cenario="true|false"
			────────────────────────────────────────────── -->
                <article class="user-card reveal-up" role="link"
                    data-a-user="<?php echo esc_attr( (string) $user->ID ); ?>"
                    data-a-component="user-radar"
                    data-profile-url="<?php echo esc_url($profile_url); ?>"
                    onclick="window.location.href=this.dataset.profileUrl"
                    data-verified="<?php echo esc_attr( $is_verified ? 'true' : 'false' ); ?>"
                    data-apollo-team="<?php echo esc_attr( $is_apollo_team ? 'true' : 'false' ); ?>"
                    data-cenario="<?php echo esc_attr( $is_cenario ? 'true' : 'false' ); ?>">
                    <figure class="user-card__media">
                        <img src="<?php echo esc_url($avatar_url); ?>"
                            alt="<?php echo esc_attr($user->display_name); ?>"
                            class="user-card__avatar" />
                    </figure>
                    <div class="user-card__body">
                        <div class="user-card__header">
                            <h2 class="user-card__name"><?php echo esc_html($user->display_name); ?></h2>

                            <?php
                            $badge_html = function_exists('apollo_get_membership_badge_html')
                                ? apollo_get_membership_badge_html($user->ID, 'sm')
                                : '';
                            if ($badge_html) :
                            ?>
                                <div class="user-badges">
                                    <?php echo wp_kses_post($badge_html); ?>
                                </div>
                            <?php elseif ($is_apollo_team || $is_verified || $is_cenario) : ?>
                                <div class="user-badges">
                                    <?php if ($is_apollo_team) : ?>
                                        <i class="i-apollo-fill" title="Apollo team"></i>
                                    <?php endif; ?>
                                    <?php if ($is_verified) : ?>
                                        <i class="ri-shield-check-fill" title="Verificado"></i>
                                    <?php endif; ?>
                                    <?php if ($is_cenario) : ?>
                                        <i class="ri-disc-line" title="Cena::rio"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <?php if (! empty($bio)) : ?>
                            <p class="user-card__bio"><?php echo esc_html($bio); ?></p>
                        <?php endif; ?>

                        <div class="user-card__footer">
                            <div class="user-card__stats">
                                <div class="user-card__stat" title="Ranking Global">
                                    <i class="ri-team-fill"></i> #<?php echo esc_html($ranking); ?>
                                </div>
                                <a href="<?php echo esc_url($profile_url); ?>" class="user-card__stat" title="Ver perfil">
                                    <i class="ri-user-3-line"></i> Perfil
                                </a>
                            </div>
                        </div>
                    </div>
                </article>

            <?php
            endforeach;
        else :
            ?>
            <p style="grid-column: 1 / -1; text-align: center; padding: var(--space-8); color: rgba(var(--txt-rgb), 0.55); font-family: var(--ff-main);">
                Nenhum User<font style="font-family:system-ui; font-size:110%">::</font>rio encontrado no radar.
            </p>
        <?php endif; ?>

    </div>

    <!-- ============================================
		FILTER LOGIC + GSAP ANIMATIONS
		============================================ -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {

            // ======================================
            // GSAP ANIMATIONS (if available from CDN)
            // ======================================
            if (typeof gsap !== 'undefined') {
                gsap.from(".reveal-up", {
                    y: 50,
                    opacity: 0,
                    duration: 0.8,
                    stagger: 0.1,
                    ease: "power3.out"
                });

                gsap.from(".filter-btn", {
                    y: -10,
                    opacity: 0,
                    duration: 0.5,
                    stagger: 0.05,
                    delay: 0.2
                });
            }

            // ======================================
            // FILTER FUNCTIONALITY
            // ======================================
            const filterButtons = document.querySelectorAll('.filter-btn');
            const userCards = document.querySelectorAll('.user-card');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active state
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Get filter type
                    const filterType = this.dataset.filter;

                    // Filter cards
                    userCards.forEach(card => {
                        if (filterType === 'all') {
                            card.classList.remove('hidden');
                        } else if (filterType === 'verified') {
                            if (card.dataset.verified === 'true') {
                                card.classList.remove('hidden');
                            } else {
                                card.classList.add('hidden');
                            }
                        } else if (filterType === 'apollo-team') {
                            if (card.dataset.apolloTeam === 'true') {
                                card.classList.remove('hidden');
                            } else {
                                card.classList.add('hidden');
                            }
                        } else if (filterType === 'cenario') {
                            if (card.dataset.cenario === 'true') {
                                card.classList.remove('hidden');
                            } else {
                                card.classList.add('hidden');
                            }
                        }
                    });

                    // Re-animate visible cards
                    if (typeof gsap !== 'undefined') {
                        const visibleCards = document.querySelectorAll('.user-card:not(.hidden)');
                        gsap.from(visibleCards, {
                            scale: 0.9,
                            opacity: 0,
                            duration: 0.4,
                            stagger: 0.05,
                            ease: "back.out(1.4)"
                        });
                    }
                });
            });
        });
    </script>

</body>

</html>