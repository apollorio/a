<?php
/**
 * WeeklyDigestCron — time-zone–aware email digests (America/Sao_Paulo).
 *
 * Schedules two single-event chains (re-armed after each run):
 * - Wednesday 18:00 — general events reminder (content via filters / other plugins).
 * - Friday 15:00 — per-user weekly roundup (stats summary + filters for customization).
 *
 * Opt-in user meta (value '1'):
 * - apollo_stats_digest_events
 * - apollo_stats_digest_roundup
 *
 * @package Apollo\Statistics\Collectors
 * @since   2.0.1
 */

declare(strict_types=1);

namespace Apollo\Statistics\Collectors;

use Apollo\Statistics\Core\DataAggregator;
use DateTimeImmutable;
use DateTimeZone;
use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

final class WeeklyDigestCron {

    /** Rio de Janeiro civil time (no DST). */
    private const TIMEZONE = 'America/Sao_Paulo';

    public const HOOK_EVENTS_REMINDER = 'apollo_stats_cron_events_reminder_email';

    public const HOOK_WEEKLY_ROUNDUP = 'apollo_stats_cron_weekly_roundup_email';

    /** User meta: '1' = receive Wednesday events reminder email. */
    public const META_DIGEST_EVENTS = 'apollo_stats_digest_events';

    /** User meta: '1' = receive Friday weekly roundup email. */
    public const META_DIGEST_ROUNDUP = 'apollo_stats_digest_roundup';

    public function init(): void {
        add_action(self::HOOK_EVENTS_REMINDER, array($this, 'run_events_reminder'));
        add_action(self::HOOK_WEEKLY_ROUNDUP, array($this, 'run_weekly_roundup'));

        if (false === wp_next_scheduled(self::HOOK_EVENTS_REMINDER)) {
            wp_schedule_single_event(self::next_slot_timestamp(3, 18, 0), self::HOOK_EVENTS_REMINDER);
        }
        if (false === wp_next_scheduled(self::HOOK_WEEKLY_ROUNDUP)) {
            wp_schedule_single_event(self::next_slot_timestamp(5, 15, 0), self::HOOK_WEEKLY_ROUNDUP);
        }
    }

    /**
     * Next occurrence of weekday at H:M in America/Sao_Paulo, strictly after "now" in that zone.
     *
     * @param int $iso_weekday Monday=1 … Sunday=7 (Wednesday=3, Friday=5).
     */
    public static function next_slot_timestamp(int $iso_weekday, int $hour, int $minute): int {
        $tz  = new DateTimeZone(self::TIMEZONE);
        $now = new DateTimeImmutable('now', $tz);

        for ($i = 0; $i < 14; $i++) {
            $slot = $now->modify('+' . $i . ' days')->setTime($hour, $minute, 0);
            if ((int) $slot->format('N') === $iso_weekday && $slot > $now) {
                return $slot->getTimestamp();
            }
        }

        return $now->getTimestamp() + WEEK_IN_SECONDS;
    }

    /**
     * Wednesday 18:00 America/Sao_Paulo — events reminder batch.
     */
    public function run_events_reminder(): void {
        try {
            foreach ($this->recipients_with_meta(self::META_DIGEST_EVENTS) as $user) {
                if (! is_email($user->user_email)) {
                    continue;
                }
                $subject = (string) apply_filters(
                    'apollo_stats_events_reminder_subject',
                    __('Apollo — lembrete de eventos', 'apollo-statistics'),
                    $user
                );
                $body = (string) apply_filters(
                    'apollo_stats_events_reminder_body',
                    $this->default_events_reminder_html($user),
                    $user
                );
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($user->user_email, wp_specialchars_decode($subject, ENT_QUOTES), $body, $headers);
            }
            do_action('apollo_stats_events_reminder_completed');
        } finally {
            wp_schedule_single_event(self::next_slot_timestamp(3, 18, 0), self::HOOK_EVENTS_REMINDER);
        }
    }

    /**
     * Friday 15:00 America/Sao_Paulo — weekly activity roundup (per user, opt-in).
     */
    public function run_weekly_roundup(): void {
        try {
            foreach ($this->recipients_with_meta(self::META_DIGEST_ROUNDUP) as $user) {
                if (! is_email($user->user_email)) {
                    continue;
                }
                $lines = (array) apply_filters(
                    'apollo_stats_weekly_roundup_lines',
                    $this->default_roundup_lines($user),
                    $user
                );
                $subject = (string) apply_filters(
                    'apollo_stats_weekly_roundup_subject',
                    __('Sua semana no Apollo — resumo', 'apollo-statistics'),
                    $user,
                    $lines
                );
                $body = (string) apply_filters(
                    'apollo_stats_weekly_roundup_body',
                    $this->format_roundup_html($user, $lines),
                    $user,
                    $lines
                );
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($user->user_email, wp_specialchars_decode($subject, ENT_QUOTES), $body, $headers);
            }
            do_action('apollo_stats_weekly_roundup_completed');
        } finally {
            wp_schedule_single_event(self::next_slot_timestamp(5, 15, 0), self::HOOK_WEEKLY_ROUNDUP);
        }
    }

    /**
     * @return WP_User[]
     */
    private function recipients_with_meta(string $meta_key): array {
        $users = get_users(
            array(
                'meta_key'     => $meta_key,
                'meta_value'   => '1',
                'meta_compare' => '=',
                'number'       => 2000,
                'orderby'      => 'ID',
                'order'        => 'ASC',
            )
        );

        $out = array();
        foreach ($users as $u) {
            if ($u instanceof WP_User) {
                $out[] = $u;
            }
        }

        return $out;
    }

    private function default_events_reminder_html(WP_User $user): string {
        $name = esc_html($user->display_name ?: $user->user_login);
        $home  = esc_url(home_url('/'));

        return '<p>' . sprintf(
            /* translators: 1: user display name */
            esc_html__('Olá %1$s — este é o lembrete semanal de eventos Apollo.', 'apollo-statistics'),
            $name
        ) . '</p>'
            . '<p>' . esc_html__('Consulte a agenda e as novidades na plataforma.', 'apollo-statistics') . '</p>'
            . '<p><a href="' . $home . '">' . esc_html__('Abrir Apollo', 'apollo-statistics') . '</a></p>';
    }

    /**
     * Default stats-based lines for the last 7 days (apollo_stats_users aggregates).
     *
     * @return string[] Plain-text lines (escaped when rendered into HTML).
     */
    private function default_roundup_lines(WP_User $user): array {
        global $wpdb;

        $agg   = DataAggregator::instance();
        $table = $agg->table('apollo_stats_users');
        $since = gmdate('Y-m-d', strtotime('-7 days'));

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from internal registry.
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT metric_type, SUM(metric_value) AS total
                 FROM {$table}
                 WHERE user_id = %d AND recorded_date >= %s
                 GROUP BY metric_type",
                $user->ID,
                $since
            ),
            ARRAY_A
        );

        $map = array();
        foreach ($rows ?: array() as $row) {
            $map[ (string) $row['metric_type'] ] = (int) $row['total'];
        }

        $lines = array();

        $chat = (int) ( $map['chat_message'] ?? 0 );
        if ($chat > 0) {
            $lines[] = sprintf(
                /* translators: %d: chat message count */
                _n('%d mensagem de chat na semana', '%d mensagens de chat na semana', $chat, 'apollo-statistics'),
                $chat
            );
        }

        $pv = (int) ( $map['profile_view'] ?? 0 );
        if ($pv > 0) {
            $lines[] = sprintf(
                /* translators: %d: profile view count */
                _n('%d visita ao seu perfil', '%d visitas ao seu perfil', $pv, 'apollo-statistics'),
                $pv
            );
        }

        $wow = (int) ( $map['wow_given'] ?? 0 );
        if ($wow > 0) {
            $lines[] = sprintf(
                /* translators: %d: wow count */
                _n('%d wow enviado', '%d wows enviados', $wow, 'apollo-statistics'),
                $wow
            );
        }

        if ($lines === array()) {
            $lines[] = __('Nenhuma atividade registrada na última semana — continue conectando!', 'apollo-statistics');
        }

        return $lines;
    }

    /**
     * @param string[] $lines
     */
    private function format_roundup_html(WP_User $user, array $lines): string {
        $name = esc_html($user->display_name ?: $user->user_login);
        $home = esc_url(home_url('/'));

        $html  = '<p>' . sprintf(
            esc_html__('Olá %1$s — aqui vai o resumo da sua semana no Apollo.', 'apollo-statistics'),
            $name
        ) . '</p><ul>';
        foreach ($lines as $line) {
            $html .= '<li>' . esc_html($line) . '</li>';
        }
        $html .= '</ul>';
        $html .= '<p><a href="' . $home . '">' . esc_html__('Abrir Apollo', 'apollo-statistics') . '</a></p>';

        return $html;
    }
}
