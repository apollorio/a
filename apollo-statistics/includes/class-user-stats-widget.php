<?php

/**
 * Apollo User Stats Widget
 *
 * Displays user statistics on their profile based on visibility settings.
 * Admin controls what users can see of their own stats.
 *
 * @package Apollo\Statistics
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * User Stats Widget Class
 */
class User_Stats_Widget
{

    /**
     * Initialize widget hooks
     */
    public static function init(): void
    {
        // Shortcode for displaying user stats.
        add_shortcode('apollo_user_stats', array(__CLASS__, 'render_shortcode'));

        // AJAX for fetching user stats.
        add_action('wp_ajax_apollo_get_user_stats_widget', array(__CLASS__, 'ajax_get_stats'));
        add_action('wp_ajax_nopriv_apollo_get_user_stats_widget', array(__CLASS__, 'ajax_get_stats'));

        // AJAX for updating user visibility settings.
        add_action('wp_ajax_apollo_update_stats_visibility', array(__CLASS__, 'ajax_update_visibility'));

        // Hook into user profile pages.
        add_action('apollo_user_profile_stats_section', array(__CLASS__, 'render_profile_section'), 10, 2);
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function render_shortcode($atts): string
    {
        $atts = shortcode_atts(
            array(
                'user_id' => 0,
                'period'  => 'month',
                'compact' => 'false',
            ),
            $atts,
            'apollo_user_stats'
        );

        $user_id = absint($atts['user_id']) ?: get_current_user_id();
        $period  = sanitize_text_field($atts['period']);
        $compact = 'true' === $atts['compact'];

        if (! $user_id) {
            return '<p class="apollo-stats-login">' . esc_html__('Please log in to view statistics.', 'apollo-statistics') . '</p>';
        }

        // Check visibility.
        $viewer_id = get_current_user_id();
        if (! Analytics::can_view_user_stats($user_id, $viewer_id)) {
            return '<p class="apollo-stats-private">' . esc_html__('Statistics are private.', 'apollo-statistics') . '</p>';
        }

        // Get stats.
        $stats = Analytics::get_user_stats($user_id, $period);

        return self::render_stats_card($stats, $user_id, $viewer_id, $period, $compact);
    }

    /**
     * Render stats card HTML
     *
     * @param array  $stats     Statistics data.
     * @param int    $user_id   User ID.
     * @param int    $viewer_id Viewer user ID.
     * @param string $period    Time period.
     * @param bool   $compact   Compact mode.
     * @return string HTML.
     */
    private static function render_stats_card(array $stats, int $user_id, int $viewer_id, string $period, bool $compact): string
    {
        $visibility = Analytics::get_user_stats_visibility($user_id);
        $is_own     = $user_id === $viewer_id;
        $is_admin   = current_user_can('manage_options');

        ob_start();
?>
        <div class="apollo-user-stats-widget <?php echo $compact ? 'compact' : ''; ?>" data-a-user="<?php echo esc_attr($user_id); ?>" data-a-component="user-stats-widget" data-period="<?php echo esc_attr($period); ?>">
            <?php if (! $compact) : ?>
                <div class="stats-header">
                    <h3>
                        <i class="ri-bar-chart-2-line"></i>
                        <?php
                        if ($is_own) {
                            esc_html_e('Your Statistics', 'apollo-statistics');
                        } else {
                            $user = get_userdata($user_id);
                            /* translators: %s: user display name */
                            printf(esc_html__('%s\'s Statistics', 'apollo-statistics'), esc_html($user->display_name ?? ''));
                        }
                        ?>
                    </h3>
                    <div class="period-selector">
                        <select class="stats-period-select">
                            <option value="today" <?php selected($period, 'today'); ?>><?php esc_html_e('Today', 'apollo-statistics'); ?></option>
                            <option value="week" <?php selected($period, 'week'); ?>><?php esc_html_e('This Week', 'apollo-statistics'); ?></option>
                            <option value="month" <?php selected($period, 'month'); ?>><?php esc_html_e('This Month', 'apollo-statistics'); ?></option>
                            <option value="year" <?php selected($period, 'year'); ?>><?php esc_html_e('This Year', 'apollo-statistics'); ?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <?php if ($visibility['show_profile_views'] || $is_admin) : ?>
                    <div class="stat-item" data-a-user="<?php echo esc_attr($user_id); ?>" data-field="profile_views">
                        <div class="stat-icon"><i class="ri-eye-line"></i></div>
                        <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['profile_views'] ?? 0)); ?></div>
                        <div class="stat-label"><?php esc_html_e('Profile Views', 'apollo-statistics'); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($visibility['show_content_views'] || $is_admin) : ?>
                    <div class="stat-item" data-a-user="<?php echo esc_attr($user_id); ?>" data-field="event_views">
                        <div class="stat-icon"><i class="ri-calendar-event-line"></i></div>
                        <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['event_views'] ?? 0)); ?></div>
                        <div class="stat-label"><?php esc_html_e('Event Views', 'apollo-statistics'); ?></div>
                    </div>

                    <div class="stat-item" data-a-user="<?php echo esc_attr($user_id); ?>" data-field="post_views">
                        <div class="stat-icon"><i class="ri-article-line"></i></div>
                        <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['post_views'] ?? 0)); ?></div>
                        <div class="stat-label"><?php esc_html_e('Post Views', 'apollo-statistics'); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($visibility['show_engagement'] || $is_admin) : ?>
                    <div class="stat-item" data-a-user="<?php echo esc_attr($user_id); ?>" data-field="wows_received">
                        <div class="stat-icon"><i class="ri-fire-line"></i></div>
                        <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['wows_received'] ?? 0)); ?></div>
                        <div class="stat-label"><?php esc_html_e('Wows', 'apollo-statistics'); ?></div>
                    </div>

                    <div class="stat-item" data-a-user="<?php echo esc_attr($user_id); ?>" data-field="social_shares">
                        <div class="stat-icon"><i class="ri-share-line"></i></div>
                        <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['social_shares'] ?? 0)); ?></div>
                        <div class="stat-label"><?php esc_html_e('Shares', 'apollo-statistics'); ?></div>
                    </div>

                    <div class="stat-item" data-a-user="<?php echo esc_attr($user_id); ?>" data-field="followers_gained">
                        <div class="stat-icon"><i class="ri-user-add-line"></i></div>
                        <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['followers_gained'] ?? 0)); ?></div>
                        <div class="stat-label"><?php esc_html_e('New Followers', 'apollo-statistics'); ?></div>
                    </div>
                <?php endif; ?>

                <div class="stat-item" data-a-user="<?php echo esc_attr($user_id); ?>" data-field="unique_visitors">
                    <div class="stat-icon"><i class="ri-group-line"></i></div>
                    <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['unique_visitors'] ?? 0)); ?></div>
                    <div class="stat-label"><?php esc_html_e('Unique Visitors', 'apollo-statistics'); ?></div>
                </div>
            </div>

            <?php if ($is_own && ! $compact) : ?>
                <div class="stats-footer">
                    <button type="button" class="stats-visibility-toggle" data-open="false">
                        <i class="ri-compasses-2-line"></i>
                        <?php esc_html_e('Visibility Settings', 'apollo-statistics'); ?>
                    </button>
                    <div class="visibility-settings" style="display: none;">
                        <?php
                        $admin_override = get_option('apollo_analytics_admin_override', array());
                        $can_change     = empty($admin_override['force_visibility']) && ! empty($admin_override['allow_user_change']);
                        ?>
                        <?php if ($can_change) : ?>
                            <form class="visibility-form">
                                <?php wp_nonce_field('apollo_stats_visibility', 'visibility_nonce'); ?>
                                <p>
                                    <label>
                                        <input type="checkbox" name="show_profile_views" value="1" <?php checked($visibility['show_profile_views']); ?>>
                                        <?php esc_html_e('Show profile views', 'apollo-statistics'); ?>
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox" name="show_content_views" value="1" <?php checked($visibility['show_content_views']); ?>>
                                        <?php esc_html_e('Show content views', 'apollo-statistics'); ?>
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox" name="show_engagement" value="1" <?php checked($visibility['show_engagement']); ?>>
                                        <?php esc_html_e('Show engagement stats', 'apollo-statistics'); ?>
                                    </label>
                                </p>
                                <p>
                                    <label for="show_to"><?php esc_html_e('Who can see my stats:', 'apollo-statistics'); ?></label>
                                    <select name="show_to" id="show_to">
                                        <option value="self" <?php selected($visibility['show_to'], 'self'); ?>><?php esc_html_e('Only me', 'apollo-statistics'); ?></option>
                                        <option value="followers" <?php selected($visibility['show_to'], 'followers'); ?>><?php esc_html_e('My followers', 'apollo-statistics'); ?></option>
                                        <option value="public" <?php selected($visibility['show_to'], 'public'); ?>><?php esc_html_e('Everyone', 'apollo-statistics'); ?></option>
                                    </select>
                                </p>
                                <button type="submit" class="button"><?php esc_html_e('Save', 'apollo-statistics'); ?></button>
                            </form>
                        <?php else : ?>
                            <p class="visibility-locked">
                                <i class="ri-lock-line"></i>
                                <?php esc_html_e('Visibility settings are managed by the administrator.', 'apollo-statistics'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .apollo-user-stats-widget {
                background: var(--apollo-bg-card, #fff);
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 2px 8px rgba(var(--rgb-d), 0.08);
            }

            .apollo-user-stats-widget.compact {
                padding: 15px;
            }

            .apollo-user-stats-widget .stats-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid var(--apollo-border, #eee);
            }

            .apollo-user-stats-widget .stats-header h3 {
                margin: 0;
                font-size: 18px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .apollo-user-stats-widget .period-selector select {
                padding: 5px 10px;
                border-radius: 6px;
                border: 1px solid var(--apollo-border, #ddd);
            }

            .apollo-user-stats-widget .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
                gap: 15px;
            }

            .apollo-user-stats-widget.compact .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .apollo-user-stats-widget .stat-item {
                text-align: center;
                padding: 15px 10px;
                background: var(--apollo-bg-muted, #f8f9fa);
                border-radius: 8px;
                transition: transform 0.2s;
            }

            .apollo-user-stats-widget .stat-item:hover {
                transform: translateY(-2px);
            }

            .apollo-user-stats-widget .stat-icon {
                font-size: 24px;
                color: var(--apollo-primary, #3498db);
                margin-bottom: 8px;
            }

            .apollo-user-stats-widget .stat-value {
                font-size: 24px;
                font-weight: bold;
                color: var(--apollo-text, #2c3e50);
            }

            .apollo-user-stats-widget.compact .stat-value {
                font-size: 18px;
            }

            .apollo-user-stats-widget .stat-label {
                font-size: 12px;
                color: var(--apollo-text-muted, #7f8c8d);
                margin-top: 4px;
            }

            .apollo-user-stats-widget .stats-footer {
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid var(--apollo-border, #eee);
            }

            .apollo-user-stats-widget .stats-visibility-toggle {
                background: none;
                border: none;
                color: var(--apollo-text-muted, #7f8c8d);
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 13px;
            }

            .apollo-user-stats-widget .visibility-settings {
                margin-top: 15px;
                padding: 15px;
                background: var(--apollo-bg-muted, #f8f9fa);
                border-radius: 8px;
            }

            .apollo-user-stats-widget .visibility-settings p {
                margin: 10px 0;
            }

            .apollo-user-stats-widget .visibility-locked {
                color: var(--apollo-text-muted, #7f8c8d);
                display: flex;
                align-items: center;
                gap: 5px;
            }
        </style>

        <script>
            (function() {
                const widget = document.querySelector('.apollo-user-stats-widget[data-a-user="<?php echo esc_js($user_id); ?>"]');
                if (!widget) return;

                // Period selector
                const periodSelect = widget.querySelector('.stats-period-select');
                if (periodSelect) {
                    periodSelect.addEventListener('change', function() {
                        const url = new URL(window.location);
                        url.searchParams.set('stats_period', this.value);
                        window.location = url;
                    });
                }

                // Visibility toggle
                const toggle = widget.querySelector('.stats-visibility-toggle');
                const settings = widget.querySelector('.visibility-settings');
                if (toggle && settings) {
                    toggle.addEventListener('click', function() {
                        const isOpen = this.dataset.open === 'true';
                        settings.style.display = isOpen ? 'none' : 'block';
                        this.dataset.open = (!isOpen).toString();
                    });
                }

                // Visibility form
                const form = widget.querySelector('.visibility-form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        formData.append('action', 'apollo_update_stats_visibility');

                        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    alert('<?php echo esc_js(__('Settings saved!', 'apollo-statistics')); ?>');
                                }
                            });
                    });
                }
            })();
        </script>
<?php
        return ob_get_clean();
    }

    /**
     * AJAX: Get user stats
     */
    public static function ajax_get_stats(): void
    {
        check_ajax_referer('apollo_analytics_nonce', 'nonce');

        // Rate limiting: 30 requests per hour for user stats widget
        if (! Analytics::check_rate_limit('user_stats_widget', 30, HOUR_IN_SECONDS)) {
            wp_send_json_error('Rate limit exceeded');
        }

        $user_id   = absint($_POST['user_id'] ?? 0);
        $period    = sanitize_text_field($_POST['period'] ?? 'month');
        $viewer_id = get_current_user_id();

        if (! $user_id) {
            wp_send_json_error('Invalid user');
        }

        if (! Analytics::can_view_user_stats($user_id, $viewer_id)) {
            wp_send_json_error('Private');
        }

        $stats = Analytics::get_user_stats($user_id, $period);

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Update visibility settings
     */
    public static function ajax_update_visibility(): void
    {
        check_ajax_referer('apollo_stats_visibility', 'visibility_nonce');

        $user_id = get_current_user_id();

        if (! $user_id) {
            wp_send_json_error('Not logged in');
        }

        $settings = array(
            'show_profile_views' => ! empty($_POST['show_profile_views']),
            'show_content_views' => ! empty($_POST['show_content_views']),
            'show_engagement'    => ! empty($_POST['show_engagement']),
            'show_to'            => sanitize_text_field($_POST['show_to'] ?? 'self'),
        );

        $result = Analytics::update_user_stats_visibility($user_id, $settings);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Could not save');
        }
    }

    /**
     * Render profile section
     *
     * @param int $user_id      User ID being viewed.
     * @param int $viewer_id    User viewing the profile.
     */
    public static function render_profile_section(int $user_id, int $viewer_id): void
    {
        echo do_shortcode('[apollo_user_stats user_id="' . $user_id . '" period="month"]');
    }
}

// Initialize.
add_action('init', array('\Apollo\Statistics\User_Stats_Widget', 'init'));