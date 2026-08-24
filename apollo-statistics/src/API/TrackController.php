<?php
/**
 * TrackController — REST endpoint that receives tracker.js payloads.
 *
 * Replaces the broken wp_ajax handler. Accepts pageviews, clicks,
 * session heartbeats, custom events, and radio listening data.
 *
 * @package Apollo\Statistics\API
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\API;

use Apollo\Core\API\RestBase;

if (! defined('ABSPATH')) {
    exit;
}

final class TrackController extends RestBase {

    /** Max tracking requests per IP per minute. */
    private const RATE_LIMIT     = 120;
    private const RATE_WINDOW    = 60; // seconds

    public function __construct() {
        parent::__construct();
        $this->rest_base = 'track';
    }

    public function register_routes(): void {

        $throttled = array($this, 'check_throttle');

        // POST /track/pageview
        register_rest_route($this->namespace, '/' . $this->rest_base . '/pageview', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'record_pageview'),
                'permission_callback' => $throttled,
                'args'                => $this->get_pageview_args(),
            ),
        ));

        // POST /track/click
        register_rest_route($this->namespace, '/' . $this->rest_base . '/click', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'record_click'),
                'permission_callback' => $throttled,
                'args'                => $this->get_click_args(),
            ),
        ));

        // POST /track/session — heartbeat / start / end
        register_rest_route($this->namespace, '/' . $this->rest_base . '/session', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'record_session'),
                'permission_callback' => $throttled,
                'args'                => $this->get_session_args(),
            ),
        ));

        // POST /track/event — custom events (wow, fav, share, etc.)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/event', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'record_event'),
                'permission_callback' => $throttled,
                'args'                => $this->get_event_args(),
            ),
        ));

        // POST /track/radio — radio listen start/end
        register_rest_route($this->namespace, '/' . $this->rest_base . '/radio', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'record_radio'),
                'permission_callback' => $throttled,
                'args'                => $this->get_radio_args(),
            ),
        ));

        // POST /track/batch — batched events from tracker.js queue
        register_rest_route($this->namespace, '/' . $this->rest_base . '/batch', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'record_batch'),
                'permission_callback' => $throttled,
                'args'                => array(
                    'events' => array(
                        'required' => true,
                        'type'     => 'array',
                    ),
                ),
            ),
        ));
    }

    /**
     * Rate-limit permission callback — allows anonymous but throttles by IP.
     *
     * Uses transients keyed by hashed IP to enforce RATE_LIMIT per RATE_WINDOW.
     */
    public function check_throttle(\WP_REST_Request $request): bool|\WP_Error {
        $ip_hash    = $this->hash_ip();
        $prefix     = substr($ip_hash, 0, 12);
        $count_key  = 'apollo_track_c_' . $prefix;
        $window_key = 'apollo_track_w_' . $prefix;

        // Open a new fixed window only when none is active.
        // set_transient(window_key) is never called again until it expires,
        // so the window TTL is never accidentally reset mid-window.
        if (false === get_transient($window_key)) {
            set_transient($window_key, 1, self::RATE_WINDOW);
            set_transient($count_key,  0, self::RATE_WINDOW + 30);
        }

        $count = (int) get_transient($count_key);

        if ($count >= self::RATE_LIMIT) {
            return new \WP_Error(
                'rate_limit_exceeded',
                __('Too many requests. Please slow down.', 'apollo-statistics'),
                array('status' => 429)
            );
        }

        // Increment counter — does NOT reset the window TTL.
        set_transient($count_key, $count + 1, self::RATE_WINDOW + 30);
        return true;
    }

    /* ══════════════ Callbacks ══════════════ */

    public function record_pageview(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'apollo_stats_pageviews',
            array(
                'session_id'      => $this->sanitize_uuid($request->get_param('session_id')),
                'user_id'         => get_current_user_id(),
                'url'             => esc_url_raw($request->get_param('url')),
                'page_type'       => sanitize_key($request->get_param('page_type') ?? 'page'),
                'object_id'       => $this->nullable_int($request->get_param('object_id')),
                'time_on_page_ms' => absint($request->get_param('time_on_page') ?? 0),
                'scroll_depth'    => min(absint($request->get_param('scroll_depth') ?? 0), 100),
                'recorded_at'     => current_time('mysql'),
            ),
            array('%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s')
        );

        // Also record in legacy content table for backward compat.
        if ($request->get_param('object_id')) {
            $this->legacy_record_content(
                absint($request->get_param('object_id')),
                'view'
            );
        }

        do_action('apollo/statistics/pageview_recorded', $request->get_params());

        return $this->prepare_response(array('status' => 'ok'), 201);
    }

    public function record_click(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'apollo_stats_clicks',
            array(
                'session_id'       => $this->sanitize_uuid($request->get_param('session_id')),
                'user_id'          => get_current_user_id(),
                'source_url'       => esc_url_raw($request->get_param('source_url')),
                'target_url'       => esc_url_raw($request->get_param('target_url')),
                'element_type'     => sanitize_key($request->get_param('element_type') ?? 'link'),
                'source_page_type' => sanitize_key($request->get_param('source_page_type') ?? ''),
                'source_object_id' => $this->nullable_int($request->get_param('source_object_id')),
                'recorded_at'      => current_time('mysql'),
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        do_action('apollo/statistics/click_recorded', $request->get_params());

        return $this->prepare_response(array('status' => 'ok'), 201);
    }

    public function record_session(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $session_id = $this->sanitize_uuid($request->get_param('session_id'));
        $action     = sanitize_key($request->get_param('action') ?? 'heartbeat');
        $table      = $wpdb->prefix . 'apollo_stats_sessions';

        switch ($action) {
            case 'start':
                $wpdb->insert($table, array(
                    'session_id'  => $session_id,
                    'user_id'     => get_current_user_id(),
                    'started_at'  => current_time('mysql'),
                    'entry_url'   => esc_url_raw($request->get_param('entry_url') ?? ''),
                    'referrer'    => esc_url_raw($request->get_param('referrer') ?? ''),
                    'utm_source'  => sanitize_text_field($request->get_param('utm_source') ?? ''),
                    'utm_medium'  => sanitize_text_field($request->get_param('utm_medium') ?? ''),
                    'utm_campaign' => sanitize_text_field($request->get_param('utm_campaign') ?? ''),
                    'device_type' => sanitize_key($request->get_param('device_type') ?? 'desktop'),
                    'browser'     => sanitize_text_field($request->get_param('browser') ?? ''),
                    'ip_hash'     => $this->hash_ip(),
                ), array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
                break;

            case 'heartbeat':
                $wpdb->update(
                    $table,
                    array(
                        'duration_secs' => absint($request->get_param('duration') ?? 0),
                        'pages_viewed'  => absint($request->get_param('pages_viewed') ?? 0),
                        'exit_url'      => esc_url_raw($request->get_param('exit_url') ?? ''),
                    ),
                    array('session_id' => $session_id),
                    array('%d', '%d', '%s'),
                    array('%s')
                );
                break;

            case 'end':
                $wpdb->update(
                    $table,
                    array(
                        'ended_at'      => current_time('mysql'),
                        'duration_secs' => absint($request->get_param('duration') ?? 0),
                        'pages_viewed'  => absint($request->get_param('pages_viewed') ?? 0),
                        'exit_url'      => esc_url_raw($request->get_param('exit_url') ?? ''),
                    ),
                    array('session_id' => $session_id),
                    array('%s', '%d', '%d', '%s'),
                    array('%s')
                );
                break;
        }

        do_action('apollo/statistics/session_' . $action, $session_id, $request->get_params());

        return $this->prepare_response(array('status' => 'ok'), 201);
    }

    public function record_event(\WP_REST_Request $request): \WP_REST_Response {
        $event_type = sanitize_key($request->get_param('event_type'));
        $object_id  = absint($request->get_param('object_id') ?? 0);
        $value      = absint($request->get_param('value') ?? 1);

        // Route to the correct legacy table based on event type.
        switch ($event_type) {
            case 'wow':
            case 'fav':
            case 'share':
            case 'view':
                if ($object_id > 0) {
                    $this->legacy_record_content($object_id, $event_type);
                }
                break;

            case 'login':
            case 'registration':
            case 'profile_view':
                $this->legacy_record_user(get_current_user_id(), $event_type);
                break;

            default:
                $this->legacy_record_event($object_id, $event_type, $value);
                break;
        }

        do_action('apollo/statistics/event_recorded', $event_type, $object_id, $value, $request->get_params());

        return $this->prepare_response(array('status' => 'ok'), 201);
    }

    public function record_radio(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $action     = sanitize_key($request->get_param('action') ?? 'start');
        $session_id = $this->sanitize_uuid($request->get_param('session_id'));
        $table      = $wpdb->prefix . 'apollo_stats_radio';

        if ($action === 'start') {
            $wpdb->insert($table, array(
                'user_id'    => get_current_user_id(),
                'session_id' => $session_id,
                'started_at' => current_time('mysql'),
            ), array('%d', '%s', '%s'));
        } else {
            // 'end' or 'heartbeat' — update existing row.
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table}
                 SET ended_at      = %s,
                     duration_secs = %d,
                     track_count   = %d,
                     pause_count   = %d
                 WHERE session_id  = %s
                   AND ended_at IS NULL
                 ORDER BY id DESC
                 LIMIT 1",
                current_time('mysql'),
                absint($request->get_param('duration') ?? 0),
                absint($request->get_param('track_count') ?? 0),
                absint($request->get_param('pause_count') ?? 0),
                $session_id
            ));
        }

        do_action('apollo/statistics/radio_' . $action, $session_id, $request->get_params());

        return $this->prepare_response(array('status' => 'ok'), 201);
    }

    /**
     * Process batched events from tracker.js queue.
     */
    public function record_batch(\WP_REST_Request $request): \WP_REST_Response {
        $events    = $request->get_param('events');
        $accepted  = 0;
        $rejected  = 0;
        $max_batch = 50;

        if (! is_array($events)) {
            return $this->prepare_response(array('accepted' => 0, 'rejected' => 0), 200);
        }

        $total_received = count($events);
        $truncated      = $total_received > $max_batch;

        foreach (array_slice($events, 0, $max_batch) as $event) {
            if (! is_array($event) || empty($event['type'])) {
                $rejected++;
                continue;
            }

            $type = sanitize_key($event['type']);
            $sub  = new \WP_REST_Request('POST');
            $sub->set_body_params($event);

            switch ($type) {
                case 'pageview':
                    $this->record_pageview($sub);
                    break;
                case 'click':
                    $this->record_click($sub);
                    break;
                case 'session':
                    $this->record_session($sub);
                    break;
                case 'event':
                    $this->record_event($sub);
                    break;
                case 'radio':
                    $this->record_radio($sub);
                    break;
                default:
                    $rejected++;
                    continue 2;
            }
            $accepted++;
        }

        return $this->prepare_response(array(
            'accepted'       => $accepted,
            'rejected'       => $rejected,
            'total_received' => $total_received,
            'truncated'      => $truncated,
        ), 201);
    }

    /* ══════════════ Legacy bridge functions ══════════════ */

    private function legacy_record_content(int $post_id, string $metric_type): void {
        if (function_exists('apollo_stats_record_content')) {
            apollo_stats_record_content($post_id, $metric_type);
        }
    }

    private function legacy_record_user(int $user_id, string $metric_type): void {
        if (function_exists('apollo_stats_record_user')) {
            apollo_stats_record_user($user_id, $metric_type);
        }
    }

    private function legacy_record_event(int $event_id, string $metric_type, int $value = 1): void {
        if (function_exists('apollo_stats_record_event')) {
            apollo_stats_record_event($event_id, $metric_type, $value);
        }
    }

    /* ══════════════ Sanitization helpers ══════════════ */

    /**
     * Sanitize a UUID v4 string.
     */
    private function sanitize_uuid(mixed $value): string {
        $value = (string) ($value ?? '');
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            return strtolower($value);
        }
        // Generate a new UUID if invalid.
        return wp_generate_uuid4();
    }

    /**
     * Hash the client IP for privacy (one-way SHA-256).
     * Supports reverse-proxy and CDN environments (Cloudflare, nginx).
     */
    private function hash_ip(): string {
        // Priority: CF-Connecting-IP → X-Real-IP → first X-Forwarded-For → REMOTE_ADDR.
        $ip = sanitize_text_field(
            $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0])
            ?? $_SERVER['REMOTE_ADDR']
            ?? ''
        );
        $salt = wp_salt('auth');
        return hash('sha256', trim($ip) . $salt);
    }

    /**
     * Return int or null for nullable DB columns.
     */
    private function nullable_int(mixed $value): ?int {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        return absint($value);
    }

    /* ══════════════ Args schemas ══════════════ */

    private function get_pageview_args(): array {
        return array(
            'session_id'   => array('required' => true, 'type' => 'string'),
            'url'          => array('required' => true, 'type' => 'string'),
            'page_type'    => array('type' => 'string', 'default' => 'page'),
            'object_id'    => array('type' => 'integer', 'default' => 0),
            'time_on_page' => array('type' => 'integer', 'default' => 0),
            'scroll_depth' => array('type' => 'integer', 'default' => 0),
        );
    }

    private function get_click_args(): array {
        return array(
            'session_id'       => array('required' => true, 'type' => 'string'),
            'source_url'       => array('required' => true, 'type' => 'string'),
            'target_url'       => array('required' => true, 'type' => 'string'),
            'element_type'     => array('type' => 'string', 'default' => 'link'),
            'source_page_type' => array('type' => 'string', 'default' => ''),
            'source_object_id' => array('type' => 'integer', 'default' => 0),
        );
    }

    private function get_session_args(): array {
        return array(
            'session_id'   => array('required' => true, 'type' => 'string'),
            'action'       => array('required' => true, 'type' => 'string', 'enum' => array('start', 'heartbeat', 'end')),
            'entry_url'    => array('type' => 'string', 'default' => ''),
            'exit_url'     => array('type' => 'string', 'default' => ''),
            'referrer'     => array('type' => 'string', 'default' => ''),
            'duration'     => array('type' => 'integer', 'default' => 0),
            'pages_viewed' => array('type' => 'integer', 'default' => 0),
            'device_type'  => array('type' => 'string', 'default' => 'desktop'),
            'browser'      => array('type' => 'string', 'default' => ''),
            'utm_source'   => array('type' => 'string', 'default' => ''),
            'utm_medium'   => array('type' => 'string', 'default' => ''),
            'utm_campaign' => array('type' => 'string', 'default' => ''),
        );
    }

    private function get_event_args(): array {
        return array(
            'session_id' => array('required' => true, 'type' => 'string'),
            'event_type' => array('required' => true, 'type' => 'string'),
            'object_id'  => array('type' => 'integer', 'default' => 0),
            'value'      => array('type' => 'integer', 'default' => 1),
        );
    }

    private function get_radio_args(): array {
        return array(
            'session_id'  => array('required' => true, 'type' => 'string'),
            'action'      => array('required' => true, 'type' => 'string', 'enum' => array('start', 'end', 'heartbeat')),
            'duration'    => array('type' => 'integer', 'default' => 0),
            'track_count' => array('type' => 'integer', 'default' => 0),
            'pause_count' => array('type' => 'integer', 'default' => 0),
        );
    }
}
