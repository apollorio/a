<?php

/**
 * Email Logger — tracks all sent/failed/opened/clicked emails.
 *
 * @package Apollo\Email\Log
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\Log;

if (! defined('ABSPATH')) {
    exit;
}

class Logger
{

    /**
     * Log a successfully sent email.
     *
     * @return int|false Log ID.
     */
    public function logSent(string $to, string $subject, string $template = '', string $email_type = 'transactional', array $meta = array()): int|false
    {
        return $this->insert(
            array(
                'to_email'    => $to,
                'subject'     => $subject,
                'template'    => $template ?: null,
                'email_type'  => $email_type,
                'status'      => 'sent',
                'transport'   => \Apollo\Email\Plugin::activeTransport(),
                'track_token' => wp_generate_password(32, false),
                'meta'        => ! empty($meta) ? wp_json_encode($meta) : null,
            )
        );
    }

    /**
     * Log a failed email.
     *
     * @return int|false Log ID.
     */
    public function logFailed(string $to, string $subject, string $template = '', string $error = '', array $meta = array()): int|false
    {
        return $this->insert(
            array(
                'to_email'      => $to,
                'subject'       => $subject,
                'template'      => $template ?: null,
                'email_type'    => 'transactional',
                'status'        => 'failed',
                'transport'     => \Apollo\Email\Plugin::activeTransport(),
                'error_message' => $error,
                'meta'          => ! empty($meta) ? wp_json_encode($meta) : null,
            )
        );
    }

    /**
     * Get a log entry by its tracking token.
     *
     * @param string $token Tracking token.
     * @return object|null
     */
    public function get_by_token(string $token): ?object
    {
        global $wpdb;
        $table = $this->table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE track_token = %s", $token)) ?: null;
    }

    /**
     * Log an email open event.
     */
    public function logOpened(int $log_id): void
    {
        global $wpdb;
        $table = $this->table();

        $wpdb->update(
            $table,
            array(
                'status'    => 'opened',
                'opened_at' => current_time('mysql'),
            ),
            array('id' => $log_id)
        );

        /**
         * Fires when an email is opened.
         *
         * @param int $log_id Log entry ID.
         */
        do_action('apollo/email/opened', $log_id);
    }

    /**
     * Log an email click event.
     */
    public function logClicked(int $log_id): void
    {
        global $wpdb;
        $table = $this->table();

        $wpdb->update(
            $table,
            array(
                'status'     => 'clicked',
                'clicked_at' => current_time('mysql'),
            ),
            array('id' => $log_id)
        );
    }

    /**
     * Get log entries with pagination.
     *
     * @param array $args Filter/pagination args.
     * @return array{items: array, total: int, pages: int}
     */
    public function getEntries(array $args = array()): array
    {
        global $wpdb;
        $table    = $this->table();
        $per_page = absint($args['per_page'] ?? 20);
        $page     = max(1, absint($args['page'] ?? 1));
        $offset   = ($page - 1) * $per_page;

        $where  = array('1=1');
        $params = array();

        if (! empty($args['status'])) {
            $where[]  = 'status = %s';
            $params[] = $args['status'];
        }

        if (! empty($args['email'])) {
            $where[]  = 'to_email LIKE %s';
            $params[] = '%' . $wpdb->esc_like($args['email']) . '%';
        }

        if (! empty($args['template'])) {
            $where[]  = 'template = %s';
            $params[] = $args['template'];
        }

        if (! empty($args['email_type'])) {
            $where[]  = 'email_type = %s';
            $params[] = $args['email_type'];
        }

        if (! empty($args['date_from'])) {
            $where[]  = 'sent_at >= %s';
            $params[] = $args['date_from'];
        }

        if (! empty($args['date_to'])) {
            $where[]  = 'sent_at <= %s';
            $params[] = $args['date_to'] . ' 23:59:59';
        }

        $where_str = implode(' AND ', $where);

        // Count
        $count_q = "SELECT COUNT(*) FROM {$table} WHERE {$where_str}";
        $total   = $params
            ? (int) $wpdb->get_var($wpdb->prepare($count_q, ...$params))
            : (int) $wpdb->get_var($count_q);

        // Fetch
        $query    = "SELECT * FROM {$table} WHERE {$where_str} ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($query, ...$params));

        return array(
            'items' => $items,
            'total' => $total,
            'pages' => (int) ceil($total / $per_page),
        );
    }

    /**
     * Get aggregate statistics.
     *
     * @param int $days Number of days to look back.
     * @return array{sent: int, failed: int, opened: int, clicked: int, bounced: int, rate_open: float, rate_click: float}
     */
    public function getStats(int $days = 30): array
    {
        global $wpdb;
        $table  = $this->table();
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, COUNT(*) as count FROM {$table} WHERE sent_at >= %s GROUP BY status",
                $cutoff
            )
        );

        $stats = array(
            'sent'    => 0,
            'failed'  => 0,
            'opened'  => 0,
            'clicked' => 0,
            'bounced' => 0,
        );
        foreach ($results as $row) {
            $stats[$row->status] = (int) $row->count;
        }

        $total_delivered     = $stats['sent'] + $stats['opened'] + $stats['clicked'];
        $stats['rate_open']  = $total_delivered > 0 ? round(($stats['opened'] + $stats['clicked']) / $total_delivered * 100, 1) : 0;
        $stats['rate_click'] = $total_delivered > 0 ? round($stats['clicked'] / $total_delivered * 100, 1) : 0;

        return $stats;
    }

    /**
     * Get daily send counts for charts.
     *
     * @param int $days Number of days.
     * @return array Daily counts.
     */
    public function getDailyStats(int $days = 30): array
    {
        global $wpdb;
        $table  = $this->table();
        $cutoff = gmdate('Y-m-d', time() - ($days * DAY_IN_SECONDS));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(sent_at) as date, COUNT(*) as total
             FROM {$table}
             WHERE sent_at >= %s
             GROUP BY DATE(sent_at)
             ORDER BY date ASC",
                $cutoff
            ),
            ARRAY_A
        );
    }

    /**
     * Purge old log entries.
     *
     * @param int $days Entries older than this.
     * @return int Deleted count.
     */
    public function purge(int $days = 90): int
    {
        global $wpdb;
        $table  = $this->table();
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE sent_at < %s",
                $cutoff
            )
        );
    }

    /**
     * Insert a log entry.
     */
    private function insert(array $data): int|false
    {
        global $wpdb;

        $data['sent_at'] = $data['sent_at'] ?? current_time('mysql');

        $result = $wpdb->insert($this->table(), $data);
        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Get table name.
     */
    private function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'apollo_email_log';
    }
}
