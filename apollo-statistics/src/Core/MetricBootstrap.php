<?php
/**
 * MetricBootstrap — registers all 68 MetricGroup instances.
 *
 * Wired into the MetricRegistry via 'apollo/statistics/register' action.
 * Each instance is created by cloning a base MetricGroup class
 * with specific config (slug, label, table, filters, etc.).
 *
 * @package Apollo\Statistics\Core
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Core;

use Apollo\Statistics\Metrics\ViewCounter;
use Apollo\Statistics\Metrics\Ranking;
use Apollo\Statistics\Metrics\Distribution;
use Apollo\Statistics\Metrics\Funnel;
use Apollo\Statistics\Metrics\TimeSeries;
use Apollo\Statistics\Metrics\Session;
use Apollo\Statistics\Metrics\ClickTrack;
use Apollo\Statistics\Metrics\EngagementScore;
use Apollo\Statistics\Metrics\Growth;
use Apollo\Statistics\Metrics\Lifecycle;
use Apollo\Statistics\Metrics\Comparison;
use Apollo\Statistics\Metrics\Leaderboard;
use Apollo\Statistics\Metrics\Profile;
use Apollo\Statistics\Metrics\Radio;
use Apollo\Statistics\Metrics\Skeleton;

if (! defined('ABSPATH')) {
    exit;
}

final class MetricBootstrap {

    public static function register(MetricRegistry $registry): void {
        self::register_user_metrics($registry);
        self::register_hub_gestor_metrics($registry);
        self::register_chat_event_dj_loc_metrics($registry);
        self::register_classified_metrics($registry);
        self::register_group_journal_metrics($registry);
        self::register_docs_sign_fav_wow_metrics($registry);
        self::register_login_metrics($registry);
        self::register_social_metrics($registry);
        self::register_dashboard_metrics($registry);
        self::register_radio_metrics($registry);
        self::register_skeleton_metrics($registry);
    }

    /* ═══ #1 ALL USERS ═══ */
    private static function register_user_metrics(MetricRegistry $r): void {
        // MG01: user_sessions
        $r->register(new Session());

        // MG02: user_pageviews
        $r->register(new ViewCounter());

        // MG03: user_clicks
        $r->register(new ClickTrack());

        // MG04: user_online_ranking
        $r->register(
            (new Leaderboard())->with_config('user_online_ranking', 'Online Time Ranking', '', '', '', '_apollo_online_minutes')
        );

        // MG05: user_engagement_score
        $r->register(new EngagementScore());

        // MG06: user_own_stats (Profile dashboard)
        $r->register(new Profile());

        // MG07: user_visit_graph
        $r->register(
            (new TimeSeries())->with_config('user_visit_graph', 'Profile Visits Over Time', 'apollo_stats_users', 'recorded_date', 'metric_value', "metric_type = 'profile_view'")
        );

        // MG08: user_points_map
        $r->register(
            (new TimeSeries())->with_config('user_points_map', 'Points Awarded Over Time', 'apollo_stats_users', 'recorded_date', 'metric_value', "metric_type = 'points_awarded'")
        );

        // MG09: user_time_online_points
        $r->register(
            (new EngagementScore())->with_weights('user_time_online_points', 'Time → Points Bridge', array(), 1.0)
        );
    }

    /* ═══ #2 HUB & GESTOR ═══ */
    private static function register_hub_gestor_metrics(MetricRegistry $r): void {
        // MG10: hub_views
        $r->register(
            (new ViewCounter())->with_post_type('hub')
        );

        // MG11: hub_link_clicks
        $r->register(
            (new ClickTrack())->with_filter('hub_link_clicks', 'Hub Link Clicks', 'source_page_type', 'hub')
        );

        // MG12: hub_visitor_sources
        $r->register(
            (new Distribution())->with_config('hub_visitor_sources', 'Hub Visitor Sources', 'apollo_stats_pageviews', 'referrer', "page_type = 'hub'")
        );

        // MG13: hub_vs_average
        $r->register(
            (new Comparison())->with_config('hub_vs_average', 'Hub vs Average', 'apollo_stats_content', 'metric_value', 'post_id', "post_type = 'hub' AND metric_type = 'view'")
        );

        // MG14: hub_block_performance
        $r->register(
            (new Ranking())->with_config('hub_block_performance', 'Hub Block Performance', 'apollo_stats_clicks', 'target_url', 'id', '')
        );

        // MG15: gestor_task_velocity
        $r->register(
            (new Lifecycle())->with_config('gestor_task_velocity', 'Task Velocity', 'apollo_gestor_tasks', 'id', 'created_at', 'completed_at', 'status', array('pending', 'in_progress', 'completed'))
        );

        // MG16: gestor_team_load
        $r->register(
            (new Distribution())->with_config('gestor_team_load', 'Team Task Load', 'apollo_gestor_tasks', 'assigned_to')
        );

        // MG17: gestor_budget_burn
        $r->register(
            (new TimeSeries())->with_config('gestor_budget_burn', 'Budget Burn Rate', 'apollo_gestor_payments', 'created_at', 'amount')
        );

        // MG18: gestor_milestone_adherence
        $r->register(
            (new Funnel())->with_config('gestor_milestone_adherence', 'Milestone Adherence', 'apollo_gestor_milestones', 'status', array('planned', 'in_progress', 'achieved', 'missed'))
        );
    }

    /* ═══ #3 CHAT & EVENTS & DJ & LOC ═══ */
    private static function register_chat_event_dj_loc_metrics(MetricRegistry $r): void {
        // MG19: chat_volume
        $r->register(
            (new TimeSeries())->with_config('chat_volume', 'Chat Message Volume', 'apollo_chat_messages', 'created_at', '', '', true)
        );

        // MG20: chat_types
        $r->register(
            (new Distribution())->with_config('chat_types', 'Chat Message Types', 'apollo_chat_messages', '_message_type')
        );

        // MG21: chat_active_users
        $r->register(
            (new Leaderboard())->with_config('chat_active_users', 'Most Active Chatters', 'apollo_chat_messages', 'chat_message')
        );

        // MG22: chat_response_time
        $r->register(
            (new TimeSeries())->with_config('chat_response_time', 'Chat Response Time', 'apollo_chat_messages', 'created_at', '', '', true)
        );

        // MG23: event_views
        $r->register(
            (new ViewCounter())->with_post_type('event')
        );

        // MG24: event_rsvp_funnel
        $r->register(
            (new Funnel())->with_config('event_rsvp_funnel', 'RSVP Funnel', 'apollo_event_rsvp', 'status', array('interested', 'maybe', 'going', 'checked_in'))
        );

        // MG25: event_lineup_draw
        $r->register(
            (new Ranking())->with_config('event_lineup_draw', 'Lineup Draw Power', 'apollo_stats_events', 'event_id', 'metric_value', 'rsvp_going')
        );

        // MG26: event_venue_utilization
        $r->register(
            (new Comparison())->with_config('event_venue_utilization', 'Venue Utilization', 'apollo_event_rsvp', 'id', 'event_id', "status = 'going'")
        );

        // MG27: event_timeline
        $r->register(
            (new TimeSeries())->with_config('event_timeline', 'Events Published Over Time', 'apollo_stats_content', 'recorded_date', 'metric_value', "post_type = 'event' AND metric_type = 'published'")
        );

        // MG28: event_coauthor_stats
        $r->register(
            (new Distribution())->with_config('event_coauthor_stats', 'Events per CoAuthor', 'apollo_stats_content', 'post_id', "post_type = 'event' AND metric_type = 'coauthor_invited'")
        );

        // MG29: dj_popularity
        $r->register(
            (new Leaderboard())->with_config('dj_popularity', 'DJ Popularity', 'apollo_stats_users', 'follow_received')
        );

        // MG30: dj_sound_map
        $r->register(
            (new Distribution())->with_config('dj_sound_map', 'Sound Genre Map', 'apollo_stats_content', 'post_type', "post_type = 'dj'")
        );

        // MG31: dj_event_impact
        $r->register(
            (new Comparison())->with_config('dj_event_impact', 'DJ Event Impact', 'apollo_stats_events', 'metric_value', 'event_id', "metric_type = 'rsvp_going'")
        );

        // MG32: loc_utilization
        $r->register(
            (new Comparison())->with_config('loc_utilization', 'Venue Utilization', 'apollo_stats_content', 'metric_value', 'post_id', "post_type = 'loc'")
        );

        // MG33: loc_area_heatmap
        $r->register(
            (new Distribution())->with_config('loc_area_heatmap', 'Events by Area', 'apollo_stats_content', 'post_type', "post_type = 'loc' AND metric_type = 'view'")
        );
    }

    /* ═══ #4 CLASSIFIEDS ═══ */
    private static function register_classified_metrics(MetricRegistry $r): void {
        // MG34: classified_views
        $r->register(
            (new ViewCounter())->with_post_type('classified')
        );

        // MG35: classified_conversion
        $r->register(
            (new Funnel())->with_config('classified_conversion', 'Classified Conversion', 'apollo_stats_content', 'metric_type', array('view', 'contact_click', 'whatsapp_click'))
        );

        // MG36: classified_categories
        $r->register(
            (new Distribution())->with_config('classified_categories', 'Classified Domains', 'apollo_stats_content', 'metric_type', "post_type = 'classified'")
        );

        // MG37: classified_lifecycle
        $r->register(
            (new Lifecycle())->with_config('classified_lifecycle', 'Classified Lifecycle', 'apollo_stats_content', 'post_id', 'recorded_date', 'recorded_date', 'metric_type', array('published', 'expired', 'sold'), "post_type = 'classified'")
        );
    }

    /* ═══ #5 GROUPS & JOURNAL ═══ */
    private static function register_group_journal_metrics(MetricRegistry $r): void {
        // MG38: group_growth
        $r->register(
            (new Growth())->with_config('group_growth', 'Group Member Growth', 'apollo_group_members', 'joined_at')
        );

        // MG39: group_activity
        $r->register(
            (new TimeSeries())->with_config('group_activity', 'Group Activity', 'apollo_stats_events', 'recorded_date', 'metric_value', "metric_type = 'member_joined'")
        );

        // MG40: group_type_comparison
        $r->register(
            (new Distribution())->with_config('group_type_comparison', 'Group Types', 'apollo_stats_events', 'metric_type', "metric_type IN ('member_joined','social_post')")
        );

        // MG41: journal_readership
        $r->register(
            (new ViewCounter())->with_post_type('post')
        );

        // MG42: journal_engagement
        $r->register(
            (new class extends Session {
                protected string $slug = 'journal_engagement';
                protected string $label = 'Journal Engagement';
                protected string $icon = 'ri-book-open-line';
                protected int $priority = 5;
            })
        );

        // MG43: journal_taxonomy_map
        $r->register(
            (new Distribution())->with_config('journal_taxonomy_map', 'Journal Taxonomy Map', 'apollo_stats_content', 'metric_type', "post_type = 'post'")
        );
    }

    /* ═══ #6 DOCS & SIGN & FAV & WOW ═══ */
    private static function register_docs_sign_fav_wow_metrics(MetricRegistry $r): void {
        // MG44: doc_lifecycle
        $r->register(
            (new Funnel())->with_config('doc_lifecycle', 'Document Pipeline', 'apollo_stats_content', 'metric_type', array('draft', 'locked', 'finalized', 'signed'), "post_type = 'doc'")
        );

        // MG45: doc_downloads
        $r->register(
            (new Ranking())->with_config('doc_downloads', 'Document Downloads', 'apollo_stats_content', 'post_id', 'metric_value', 'download')
        );

        // MG46: sign_completion
        $r->register(
            (new Lifecycle())->with_config('sign_completion', 'Signature Completion', 'apollo_stats_events', 'event_id', 'recorded_date', 'recorded_date', 'metric_type', array('sign_requested', 'sign_completed'))
        );

        // MG47: fav_rankings
        $r->register(
            (new Ranking())->with_config('fav_rankings', 'Most Favorited', 'apollo_stats_content', 'post_id', 'metric_value', 'fav')
        );

        // MG48: fav_trends
        $r->register(
            (new TimeSeries())->with_config('fav_trends', 'Favorites Over Time', 'apollo_stats_content', 'recorded_date', 'metric_value', "metric_type = 'fav'")
        );

        // MG49: wow_rankings
        $r->register(
            (new Ranking())->with_config('wow_rankings', 'Most Wowed', 'apollo_stats_content', 'post_id', 'metric_value', 'wow')
        );

        // MG50: wow_emoji_distribution
        $r->register(
            (new Distribution())->with_config('wow_emoji_distribution', 'Reaction Types', 'apollo_stats_content', 'metric_type', "metric_type LIKE 'wow_%'")
        );
    }

    /* ═══ #7 LOGIN ═══ */
    private static function register_login_metrics(MetricRegistry $r): void {
        // MG51: registration_funnel
        $r->register(
            (new Funnel())->with_config('registration_funnel', 'Registration Funnel', 'apollo_stats_users', 'metric_type', array('registration', 'quiz_completed', 'profile_filled', 'avatar_set', 'preferences_set'))
        );

        // MG52: quiz_scores
        $r->register(
            (new Distribution())->with_config('quiz_scores', 'Quiz Score Distribution', 'apollo_stats_users', 'metric_value', "metric_type = 'quiz_completed'")
        );

        // MG53: login_security
        $r->register(
            (new TimeSeries())->with_config('login_security', 'Login Attempts', 'apollo_stats_users', 'recorded_date', 'metric_value', "metric_type = 'login'")
        );

        // MG54: sound_preferences
        $r->register(
            (new Distribution())->with_config('sound_preferences', 'Sound Preferences', 'apollo_stats_users', 'metric_type', "metric_type LIKE 'sound_%'")
        );
    }

    /* ═══ #8 SOCIAL ═══ */
    private static function register_social_metrics(MetricRegistry $r): void {
        // MG55: feed_activity
        $r->register(
            (new TimeSeries())->with_config('feed_activity', 'Feed Activity', 'apollo_stats_users', 'recorded_date', 'metric_value', "metric_type IN ('social_post','social_reply')")
        );

        // MG56: embed_distribution
        $r->register(
            (new Distribution())->with_config('embed_distribution', 'Embed Types', 'apollo_stats_content', 'metric_type', "metric_type LIKE 'embed_%'")
        );

        // MG57: connection_network
        $r->register(
            (new Growth())->with_config('connection_network', 'Connection Growth', 'apollo_stats_users', 'recorded_date', "metric_type = 'follow'")
        );

        // MG58: social_engagement
        $r->register(
            (new EngagementScore())->with_weights('social_engagement', 'Social Engagement', array(
                'social_post'  => 3.0,
                'social_reply' => 2.0,
                'wow_given'    => 1.0,
            ), 0.0)
        );
    }

    /* ═══ #9 DASHBOARD ═══ */
    private static function register_dashboard_metrics(MetricRegistry $r): void {
        // MG59: dashboard_usage — daily pageviews to /painel
        $r->register(
            (new TimeSeries())->with_config('dashboard_usage', 'Dashboard Usage', 'apollo_stats_pageviews', 'recorded_at', '', "page_type = 'dashboard'", true)
        );

        // MG60: dashboard_sections — which /painel sub-sections are visited most
        $r->register(
            new class extends Distribution {
                protected string $slug  = 'dashboard_sections';
                protected string $label = 'Dashboard Sections';
                protected string $icon  = 'ri-dashboard-2-line';
                protected int    $priority = 9;
                public function compute(array $args = array()): array {
                    global $wpdb;
                    $since = $this->resolve_since($args);
                    $table = $wpdb->prefix . 'apollo_stats_pageviews';
                    $rows  = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT TRIM(BOTH '/' FROM SUBSTRING_INDEX(SUBSTRING(url, LOCATE('/painel', url)), '?', 1)) AS category, COUNT(*) AS count
                             FROM {$table}
                             WHERE page_type = 'dashboard' AND recorded_at >= %s
                             GROUP BY category
                             ORDER BY count DESC
                             LIMIT 20",
                            $since
                        ),
                        ARRAY_A
                    );
                    return array('categories' => $rows ?: array());
                }
            }
        );

        // MG61: admin_activity — who uses admin panel most
        $r->register(
            (new Leaderboard())->with_config('admin_activity', 'Admin Activity', 'apollo_stats_pageviews', '', "page_type = 'admin' AND user_id > 0")
        );
    }

    /* ═══ #10 RADIO & SKELETONS ═══ */
    private static function register_radio_metrics(MetricRegistry $r): void {
        // MG63: radio_listeners — daily listening sessions
        $r->register(new Radio());

        // MG64: radio_ranking — top listeners by total duration
        $r->register(
            new class extends Leaderboard {
                protected string $slug     = 'radio_ranking';
                protected string $label    = 'Radio Top Listeners';
                protected string $icon     = 'ri-trophy-line';
                protected int    $priority = 8;
                protected array  $requires = array('apollo-radio');
                public function compute(array $args = array()): array {
                    $agg   = $this->aggregator();
                    $since = $this->resolve_since($args);
                    $limit = $this->resolve_limit($args);
                    $table = $agg->table('apollo_stats_radio');
                    $where = $agg->db()->prepare('user_id > 0 AND started_at >= %s', $since);
                    $items = $agg->top_n($table, 'user_id', 'duration_secs', $limit, $where);
                    foreach ($items as &$item) {
                        $user = get_userdata((int) $item['id']);
                        $item['display_name'] = $user ? $user->display_name : '(anon)';
                        $item['avatar']       = $user ? get_avatar_url((int) $item['id'], array('size' => 40)) : '';
                        $item['hours']        = round((float) $item['total'] / 3600, 1);
                    }
                    unset($item);
                    return array('items' => $items);
                }
            }
        );

        // MG65: radio_pause_ratio — pauses per track per day (engagement quality)
        $r->register(
            new class extends TimeSeries {
                protected string $slug     = 'radio_pause_ratio';
                protected string $label    = 'Radio Pause Ratio';
                protected string $icon     = 'ri-pause-circle-line';
                protected int    $priority = 8;
                protected array  $chart    = array('type' => 'line', 'library' => 'amcharts');
                protected array  $requires = array('apollo-radio');
                public function compute(array $args = array()): array {
                    $agg          = $this->aggregator();
                    $since        = $this->resolve_since($args);
                    $table        = $agg->table('apollo_stats_radio');
                    $daily_pauses = $agg->sum_by_date($table, 'pause_count', 'started_at', $since);
                    $daily_tracks = $agg->sum_by_date($table, 'track_count', 'started_at', $since);
                    $tracks_map   = array();
                    foreach ($daily_tracks as $row) {
                        $tracks_map[$row['date']] = (int) $row['value'];
                    }
                    $series = array();
                    foreach ($daily_pauses as $row) {
                        $tracks   = $tracks_map[$row['date']] ?? 0;
                        $ratio    = $tracks > 0 ? round((float) $row['value'] / $tracks * 100, 1) : 0.0;
                        $series[] = array('date' => $row['date'], 'value' => $ratio);
                    }
                    return array('time_series' => $series);
                }
            }
        );

        // MG66: radio_peak_hours — listening sessions by hour of day
        $r->register(
            new class extends Distribution {
                protected string $slug     = 'radio_peak_hours';
                protected string $label    = 'Radio Peak Hours';
                protected string $icon     = 'ri-time-line';
                protected int    $priority = 8;
                protected array  $chart    = array('type' => 'bar', 'library' => 'amcharts');
                protected array  $requires = array('apollo-radio');
                public function compute(array $args = array()): array {
                    global $wpdb;
                    $since = $this->resolve_since($args);
                    $table = $wpdb->prefix . 'apollo_stats_radio';
                    $rows  = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT HOUR(started_at) AS category, COUNT(*) AS count
                             FROM {$table}
                             WHERE started_at >= %s AND user_id > 0
                             GROUP BY HOUR(started_at)
                             ORDER BY category ASC",
                            $since
                        ),
                        ARRAY_A
                    );
                    return array('categories' => $rows ?: array());
                }
            }
        );
    }

    private static function register_skeleton_metrics(MetricRegistry $r): void {
        // MG62: sheets_usage — bulk operations analytics placeholder
        $r->register(
            (new Skeleton())->with_config('sheets_usage', 'Sheets Usage', 'ri-file-excel-line')
        );
        // MG67: outnow_views — OutNow view analytics placeholder
        $r->register(
            (new Skeleton())->with_config('outnow_views', 'OutNow Views', 'ri-music-2-line')
        );
        // MG68: outnow_plays — OutNow plays/access placeholder
        $r->register(
            (new Skeleton())->with_config('outnow_plays', 'OutNow Plays', 'ri-play-circle-line')
        );
    }
}
