<?php

/**
 * Fav Sound Dashboard functionality.
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

namespace Apollo\Fav;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles user dashboard for fav sounds.
 */
final class Fav_Sound_Dashboard
{

    /**
     * Initializes the class.
     */
    public static function init(): void
    {
        add_shortcode('apollo_fav_dashboard', array(__CLASS__, 'render'));
        // Legacy shortcode alias for backward compatibility.
        add_shortcode('apollo_interesse_dashboard', array(__CLASS__, 'render'));
        add_action('apollo_user_profile_after_header', array(__CLASS__, 'profile_widget'), 20);
        add_action('apollo_user_dashboard_sounds_section', array(__CLASS__, 'render_sounds_section'), 10, 1);
    }

    public static function render($atts): string
    {
        $a   = shortcode_atts(array('user_id' => '0'), $atts, 'apollo_user_dashboard');
        $uid = absint($a['user_id']) ?: get_current_user_id();
        if (! $uid) {
            return '<p class="apollo-login-required">' . esc_html__('Faça login para acessar seu dashboard.', 'apollo-core') . '</p>';
        }
        $viewer = get_current_user_id();
        if ($uid !== $viewer && ! current_user_can('manage_options')) {
            return '<p class="apollo-private">' . esc_html__('Dashboard privado.', 'apollo-core') . '</p>';
        }
        return self::build_dashboard($uid);
    }

    public static function profile_widget(int $uid): void
    {
        $viewer = get_current_user_id();
        if ($uid !== $viewer && ! current_user_can('manage_options')) {
            return;
        }
        echo wp_kses_post(self::build_dashboard($uid, true));
    }

    private static function build_dashboard(int $uid, bool $compact = false): string
    {
        $events       = class_exists(__NAMESPACE__ . '\\Fav_Ranking') ? Fav_Ranking::get_user_interested_events($uid) : array();
        $sounds       = class_exists(__NAMESPACE__ . '\\Fav_Ranking') ? Fav_Ranking::get_user_top_sounds($uid, 10) : array();
        $bubbles      = self::get_user_bubbles($uid);
        $communities  = self::get_user_communities($uid);
        $nucleos      = self::get_user_nucleos($uid);
        $upcoming     = self::get_upcoming_interested_events($uid, 5);
        $total_events = count($events);
        $max_s        = $sounds[0]['count'] ?? 1;
        ob_start();
?>
        <div class="apollo-user-dashboard<?php echo $compact ? ' compact' : ''; ?>">
            <div class="dash-grid">
                <div class="dash-card dash-sounds">
                    <h3><i class="ri-music-2-line"></i> <?php esc_html_e('Seus Top 10 Sons', 'apollo-core'); ?></h3>
                    <?php if (empty($sounds)) : ?>
                        <p class="empty"><?php esc_html_e('Marque favorito nos eventos para descobrir seus sons favoritos.', 'apollo-core'); ?></p>
                    <?php else : ?>
                        <ol class="sounds-list">
                            <?php
                            foreach ($sounds as $i => $s) :
                                $pct = $max_s > 0 ? round(($s['count'] / $max_s) * 100) : 0;
                            ?>
                                <li><span class="pos">#<?php echo esc_html($i + 1); ?></span><span class="name"><?php echo esc_html($s['name']); ?></span><span class="bar"><span style="width:<?php echo esc_html($pct); ?>%"></span></span><span class="cnt"><?php echo absint($s['count']); ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
                <div class="dash-card dash-stats">
                    <h3><i class="ri-bar-chart-box-line"></i> <?php esc_html_e('Estatísticas', 'apollo-core'); ?></h3>
                    <div class="stats-grid">
                        <div class="stat"><span class="val"><?php echo absint($total_events); ?></span><span class="lbl"><?php esc_html_e('Eventos', 'apollo-core'); ?></span></div>
                        <div class="stat"><span class="val"><?php echo count($sounds); ?></span><span class="lbl"><?php esc_html_e('Sons', 'apollo-core'); ?></span></div>
                        <div class="stat"><span class="val"><?php echo count($bubbles); ?></span><span class="lbl"><?php esc_html_e('Bolhas', 'apollo-core'); ?></span></div>
                        <div class="stat"><span class="val"><?php echo count($communities); ?></span><span class="lbl"><?php esc_html_e('Comunidades', 'apollo-core'); ?></span></div>
                    </div>
                </div>
                <?php if (! empty($upcoming)) : ?>
                    <div class="dash-card dash-upcoming">
                        <h3><i class="ri-calendar-event-line"></i> <?php esc_html_e('Próximos Eventos de Interesse', 'apollo-core'); ?></h3>
                        <ul class="events-list">
                            <?php foreach ($upcoming as $e) : ?>
                                <li><a href="<?php echo esc_url(get_permalink($e->ID)); ?>"><span class="date"><?php echo esc_html(date_i18n('d/m', strtotime(get_post_meta($e->ID, '_event_start_date', true)))); ?></span><span class="title"><?php echo esc_html($e->post_title); ?></span></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (! empty($bubbles)) : ?>
                    <div class="dash-card dash-bubbles">
                        <h3><i class="ri-bubble-chart-line"></i> <?php esc_html_e('Suas Bolhas', 'apollo-core'); ?></h3>
                        <div class="tags">
                            <?php
                            foreach ($bubbles as $b) :
                            ?>
                                <span class="tag"><?php echo esc_html($b); ?></span><?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (! empty($communities)) : ?>
                    <div class="dash-card dash-communities">
                        <h3><i class="ri-team-line"></i> <?php esc_html_e('Comunidades', 'apollo-core'); ?></h3>
                        <div class="tags">
                            <?php
                            foreach ($communities as $c) :
                            ?>
                                <span class="tag"><?php echo esc_html($c); ?></span><?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (! empty($nucleos)) : ?>
                    <div class="dash-card dash-nucleos">
                        <h3><i class="ri-group-line"></i> <?php esc_html_e('Núcleos', 'apollo-core'); ?></h3>
                        <div class="tags">
                            <?php
                            foreach ($nucleos as $n) :
                            ?>
                                <span class="tag"><?php echo esc_html($n); ?></span><?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .apollo-user-dashboard {
                --c1: #3498db;
                --c2: #9b59b6;
                --bg: #fff;
                --txt: #2c3e50;
                --muted: #7f8c8d;
                --border: #eee;
                font-family: inherit
            }

            .apollo-user-dashboard.compact {
                font-size: 14px
            }

            .dash-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 16px
            }

            .dash-card {
                background: var(--bg);
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 2px 8px rgba(var(--rgb-d), .06)
            }

            .dash-card h3 {
                margin: 0 0 12px;
                font-size: 15px;
                display: flex;
                align-items: center;
                gap: 8px;
                color: var(--txt)
            }

            .dash-card h3 i {
                color: var(--c1)
            }

            .sounds-list {
                list-style: none;
                margin: 0;
                padding: 0
            }

            .sounds-list li {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 6px 0;
                border-bottom: 1px solid var(--border)
            }

            .sounds-list li:last-child {
                border: 0
            }

            .sounds-list .pos {
                font-weight: 700;
                color: var(--c2);
                min-width: 28px
            }

            .sounds-list .name {
                flex: 1;
                font-weight: 500
            }

            .sounds-list .bar {
                flex: 1;
                max-width: 80px;
                height: 6px;
                background: #eee;
                border-radius: 3px;
                overflow: hidden
            }

            .sounds-list .bar span {
                display: block;
                height: 100%;
                background: linear-gradient(90deg, var(--c2), var(--c1));
                border-radius: 3px
            }

            .sounds-list .cnt {
                font-size: 11px;
                color: var(--muted);
                min-width: 24px;
                text-align: right
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px
            }

            .stat {
                text-align: center;
                padding: 12px;
                background: #f8f9fa;
                border-radius: 8px
            }

            .stat .val {
                display: block;
                font-size: 24px;
                font-weight: 700;
                color: var(--c1)
            }

            .stat .lbl {
                font-size: 11px;
                color: var(--muted);
                text-transform: uppercase
            }

            .events-list {
                list-style: none;
                margin: 0;
                padding: 0
            }

            .events-list li {
                padding: 8px 0;
                border-bottom: 1px solid var(--border)
            }

            .events-list li:last-child {
                border: 0
            }

            .events-list a {
                display: flex;
                gap: 10px;
                text-decoration: none;
                color: var(--txt)
            }

            .events-list .date {
                font-weight: 700;
                color: var(--c1);
                min-width: 40px
            }

            .events-list .title {
                flex: 1
            }

            .tags {
                display: flex;
                flex-wrap: wrap;
                gap: 6px
            }

            .tag {
                background: linear-gradient(135deg, var(--c2), var(--c1));
                color: #fff;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500
            }

            .empty {
                color: var(--muted);
                font-style: italic;
                margin: 0
            }
        </style>
<?php
        return ob_get_clean();
    }

    private static function get_upcoming_interested_events(int $uid, int $limit = 5): array
    {
        $eids = class_exists(__NAMESPACE__ . '\\Fav_Ranking') ? Fav_Ranking::get_user_interested_events($uid) : array();
        if (empty($eids)) {
            return array();
        }
        $args = array(
            'post_type'      => (defined('APOLLO_CPT_EVENT') ? APOLLO_CPT_EVENT : 'event'),
            'post__in'       => $eids,
            'posts_per_page' => $limit,
            'meta_key'       => '_event_start_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_event_start_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
        );
        return get_posts($args);
    }

    private static function get_user_bubbles(int $uid): array
    {
        $b = get_user_meta($uid, 'apollo_user_bubbles', true);
        return is_array($b) ? $b : array();
    }

    private static function get_user_communities(int $uid): array
    {
        $c = get_user_meta($uid, 'apollo_user_communities', true);
        return is_array($c) ? $c : array();
    }

    private static function get_user_nucleos(int $uid): array
    {
        $n = get_user_meta($uid, 'apollo_user_nucleos', true);
        return is_array($n) ? $n : array();
    }

    public static function render_sounds_section(int $uid): void
    {
        $sounds = class_exists(__NAMESPACE__ . '\\Fav_Ranking') ? Fav_Ranking::get_user_top_sounds($uid, 10) : array();
        if (empty($sounds)) {
            return;
        }
        $max = $sounds[0]['count'] ?? 1;
        echo '<div class="apollo-sounds-section"><h4><i class="ri-music-2-line"></i> ' . esc_html__('Top 10 Sons', 'apollo-core') . '</h4><ol class="sounds-mini">';
        foreach ($sounds as $i => $s) {
            $pct = $max > 0 ? round(($s['count'] / $max) * 100) : 0;
            echo '<li><span class="n">#' . esc_html($i + 1) . '</span><span class="t">' . esc_html($s['name']) . '</span><span class="b" style="width:' . esc_html($pct) . '%"></span></li>';
        }
        echo '</ol></div><style>.apollo-sounds-section{margin:16px 0}.sounds-mini{list-style:none;margin:0;padding:0;display:grid;gap:4px}.sounds-mini li{display:flex;align-items:center;gap:8px;font-size:13px}.sounds-mini .n{color:#9b59b6;font-weight:700;min-width:24px}.sounds-mini .t{flex:1}.sounds-mini .b{height:4px;background:linear-gradient(90deg,#9b59b6,#3498db);border-radius:2px}</style>';
    }
}
