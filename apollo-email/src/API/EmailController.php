<?php

/**
 * REST API Controller for Apollo Email.
 *
 * Endpoints:
 *   POST /email/send          — Send email (admin)
 *   GET  /email/queue         — View queue (admin)
 *   GET  /email/templates     — List templates (admin)
 *   POST /email/templates     — Create template (admin)
 *   GET  /email/templates/{id} — Get template (admin)
 *   PUT  /email/templates/{id} — Update template (admin)
 *   DELETE /email/templates/{id} — Delete template (admin)
 *   GET  /email/log           — View log (admin)
 *   GET  /email/preferences   — Get user email prefs
 *   PUT  /email/preferences   — Update user email prefs
 *   POST /email/test          — Send test email (admin)
 *   GET  /email/stats         — Dashboard stats (admin)
 *   POST /email/queue/{id}/cancel — Cancel queued email (admin)
 *   POST /email/queue/{id}/retry  — Retry failed email (admin)
 *   POST /email/queue/purge   — Purge old queue items (admin)
 *   POST /email/log/purge     — Purge old log entries (admin)
 *
 * @package Apollo\Email\API
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\API;

use Apollo\Email\Plugin;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class EmailController extends \WP_REST_Controller
{

    protected $namespace = 'apollo/v1';
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Register REST routes.
     */
    public function register_routes(): void
    {
        // ── Send Email ───────────────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/email/send',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'send_email'),
                'permission_callback' => array($this, 'check_admin'),
                'args'                => array(
                    'to'       => array(
                        'required'          => true,
                        'type'              => 'string',
                        'format'            => 'email',
                        'sanitize_callback' => 'sanitize_email',
                    ),
                    'subject'  => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'template' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'data'     => array('type' => 'object'),
                    'body'     => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'wp_kses_post',
                    ),
                ),
            )
        );

        // ── Test Email ───────────────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/email/test',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'send_test'),
                'permission_callback' => array($this, 'check_admin'),
                'args'                => array(
                    'to' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'format'            => 'email',
                        'sanitize_callback' => 'sanitize_email',
                    ),
                ),
            )
        );

        // ── Dashboard Stats ──────────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/email/stats',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_stats'),
                'permission_callback' => array($this, 'check_admin'),
            )
        );

        // ── Queue ────────────────────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/email/queue',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_queue'),
                'permission_callback' => array($this, 'check_admin'),
                'args'                => array(
                    'status'   => array(
                        'type'              => 'string',
                        'enum'              => array('pending', 'processing', 'sent', 'failed', 'cancelled'),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'per_page' => array(
                        'type'              => 'integer',
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'page'     => array(
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/email/queue/(?P<id>\d+)/cancel',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'cancel_queue_item'),
                'permission_callback' => array($this, 'check_admin'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/email/queue/(?P<id>\d+)/retry',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'retry_queue_item'),
                'permission_callback' => array($this, 'check_admin'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/email/queue/purge',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'purge_queue'),
                'permission_callback' => array($this, 'check_admin'),
                'args'                => array(
                    'days' => array(
                        'type'              => 'integer',
                        'default'           => 30,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // ── Templates ────────────────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/email/templates',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_templates'),
                    'permission_callback' => array($this, 'check_admin'),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_template'),
                    'permission_callback' => array($this, 'check_admin'),
                    'args'                => array(
                        'title'     => array(
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'subject'   => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'type'      => array(
                            'type'              => 'string',
                            'enum'              => array('transactional', 'marketing', 'digest'),
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'content'   => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'wp_kses_post',
                        ),
                        'variables' => array('type' => 'array'),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/email/templates/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_template'),
                    'permission_callback' => array($this, 'check_admin'),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array($this, 'update_template'),
                    'permission_callback' => array($this, 'check_admin'),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_template'),
                    'permission_callback' => array($this, 'check_admin'),
                ),
            )
        );

        // ── Log ──────────────────────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/email/log',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_log'),
                'permission_callback' => array($this, 'check_admin'),
                'args'                => array(
                    'status'    => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'email'     => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_email',
                    ),
                    'template'  => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'date_from' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'date_to'   => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'per_page'  => array(
                        'type'              => 'integer',
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'page'      => array(
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/email/log/purge',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'purge_log'),
                'permission_callback' => array($this, 'check_admin'),
                'args'                => array(
                    'days' => array(
                        'type'              => 'integer',
                        'default'           => 90,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // ── Open/Click Tracking ──────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/email/track/open/(?P<token>[a-zA-Z0-9]+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'track_open'),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'token' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/email/track/click/(?P<token>[a-zA-Z0-9]+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'track_click'),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'token' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'url'   => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ),
                ),
            )
        );

        // ── User Preferences ────────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/email/preferences',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_preferences'),
                    'permission_callback' => array($this, 'check_logged_in'),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array($this, 'update_preferences'),
                    'permission_callback' => array($this, 'check_logged_in'),
                    'args'                => array(
                        'transactional' => array(
                            'type'              => 'boolean',
                            'sanitize_callback' => 'rest_sanitize_boolean',
                        ),
                        'marketing'     => array(
                            'type'              => 'boolean',
                            'sanitize_callback' => 'rest_sanitize_boolean',
                        ),
                        'digest'        => array(
                            'type'              => 'boolean',
                            'sanitize_callback' => 'rest_sanitize_boolean',
                        ),
                    ),
                ),
            )
        );
    }

    // ──────────────────────────────────────────────────────────────
    // PERMISSION CALLBACKS
    // ──────────────────────────────────────────────────────────────

    public function check_admin(): bool|WP_Error
    {
        if (! current_user_can('manage_options')) {
            return new WP_Error('apollo_rest_forbidden', __('Acesso negado.', 'apollo-email'), array('status' => 403));
        }
        return true;
    }

    public function check_logged_in(): bool|WP_Error
    {
        if (! is_user_logged_in()) {
            return new WP_Error('apollo_rest_unauthorized', __('Login necessário.', 'apollo-email'), array('status' => 401));
        }
        return true;
    }

	// ──────────────────────────────────────────────────────────────
	// CALLBACK IMPLEMENTATIONS
	// ──────────────────────────────────────────────────────────────

    /**
     * POST /email/send
     */
    public function send_email(WP_REST_Request $request): WP_REST_Response
    {
        $to       = sanitize_email($request->get_param('to'));
        $subject  = sanitize_text_field($request->get_param('subject'));
        $template = sanitize_text_field($request->get_param('template') ?? '');
        $data     = $request->get_param('data') ?? array();
        $body     = $request->get_param('body') ?? '';

        if ($template) {
            $result = $this->plugin->sender()->sendTemplate($to, $subject, $template, $data);
        } else {
            $message = \Apollo\Email\Mailer\Message::make($to, $subject, wp_kses_post($body));
            $result  = $this->plugin->sender()->send($message);
        }

        $status = $result['success'] ? 200 : 500;
        return new WP_REST_Response($result, $status);
    }

    /**
     * POST /email/test
     */
    public function send_test(WP_REST_Request $request): WP_REST_Response
    {
        $to     = sanitize_email($request->get_param('to'));
        $result = $this->plugin->sender()->sendTest($to);

        $status = $result['success'] ? 200 : 500;
        return new WP_REST_Response($result, $status);
    }

    /**
     * GET /email/stats
     */
    public function get_stats(WP_REST_Request $request): WP_REST_Response
    {
        $log_stats   = $this->plugin->logger()->getStats(30);
        $queue_stats = $this->plugin->queue()->getStats();
        $templates   = count($this->plugin->templates()->getTemplates());

        return new WP_REST_Response(
            array(
                'log'       => $log_stats,
                'queue'     => $queue_stats,
                'templates' => $templates,
            )
        );
    }

    /**
     * GET /email/queue
     */
    public function get_queue(WP_REST_Request $request): WP_REST_Response
    {
        $result = $this->plugin->queue()->getItems(
            $request->get_param('status') ?? '',
            absint($request->get_param('per_page') ?: 20),
            absint($request->get_param('page') ?: 1)
        );

        return new WP_REST_Response($result);
    }

    /**
     * POST /email/queue/{id}/cancel
     */
    public function cancel_queue_item(WP_REST_Request $request): WP_REST_Response
    {
        $id     = absint($request->get_param('id'));
        $result = $this->plugin->queue()->cancel($id);

        return new WP_REST_Response(array('success' => $result));
    }

    /**
     * POST /email/queue/{id}/retry
     */
    public function retry_queue_item(WP_REST_Request $request): WP_REST_Response
    {
        $id     = absint($request->get_param('id'));
        $result = $this->plugin->queue()->retry($id);

        return new WP_REST_Response(array('success' => $result));
    }

    /**
     * POST /email/queue/purge
     */
    public function purge_queue(WP_REST_Request $request): WP_REST_Response
    {
        $days    = absint($request->get_param('days') ?: 30);
        $deleted = $this->plugin->queue()->purge($days);

        return new WP_REST_Response(array('deleted' => $deleted));
    }

    /**
     * GET /email/templates
     */
    public function get_templates(WP_REST_Request $request): WP_REST_Response
    {
        $templates = $this->plugin->templates()->getTemplates();
        return new WP_REST_Response($templates);
    }

    /**
     * POST /email/templates
     */
    public function create_template(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = wp_insert_post(
            array(
                'post_type'    => 'email_aprio',
                'post_title'   => sanitize_text_field($request->get_param('title')),
                'post_content' => wp_kses_post($request->get_param('content') ?? ''),
                'post_status'  => 'publish',
            )
        );

        if (is_wp_error($post_id)) {
            return new WP_REST_Response(array('error' => $post_id->get_error_message()), 500);
        }

        if ($request->get_param('subject')) {
            update_post_meta($post_id, '_email_subject', sanitize_text_field($request->get_param('subject')));
        }

        $type = $request->get_param('type') ?: 'transactional';
        update_post_meta($post_id, '_email_type', sanitize_text_field($type));

        $variables = $request->get_param('variables') ?: array();
        update_post_meta($post_id, '_email_variables', array_map('sanitize_text_field', $variables));

        $template = $this->plugin->templates()->getTemplate(get_post_field('post_name', $post_id));
        return new WP_REST_Response($template, 201);
    }

    /**
     * GET /email/templates/{id}
     */
    public function get_template(WP_REST_Request $request): WP_REST_Response
    {
        $id   = absint($request->get_param('id'));
        $post = get_post($id);

        if (! $post || $post->post_type !== 'email_aprio') {
            return new WP_REST_Response(array('error' => __('Template não encontrado.', 'apollo-email')), 404);
        }

        return new WP_REST_Response(
            array(
                'id'        => $post->ID,
                'slug'      => $post->post_name,
                'title'     => $post->post_title,
                'subject'   => get_post_meta($post->ID, '_email_subject', true),
                'type'      => get_post_meta($post->ID, '_email_type', true),
                'variables' => get_post_meta($post->ID, '_email_variables', true),
                'content'   => $post->post_content,
                'modified'  => $post->post_modified,
            )
        );
    }

    /**
     * PUT /email/templates/{id}
     */
    public function update_template(WP_REST_Request $request): WP_REST_Response
    {
        $id   = absint($request->get_param('id'));
        $post = get_post($id);

        if (! $post || $post->post_type !== 'email_aprio') {
            return new WP_REST_Response(array('error' => __('Template não encontrado.', 'apollo-email')), 404);
        }

        $update_data = array('ID' => $id);

        if ($request->has_param('title')) {
            $update_data['post_title'] = sanitize_text_field($request->get_param('title'));
        }

        if ($request->has_param('content')) {
            $update_data['post_content'] = wp_kses_post($request->get_param('content'));
        }

        wp_update_post($update_data);

        if ($request->has_param('subject')) {
            update_post_meta($id, '_email_subject', sanitize_text_field($request->get_param('subject')));
        }

        if ($request->has_param('type')) {
            update_post_meta($id, '_email_type', sanitize_text_field($request->get_param('type')));
        }

        if ($request->has_param('variables')) {
            update_post_meta($id, '_email_variables', array_map('sanitize_text_field', $request->get_param('variables')));
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'id'      => $id,
            )
        );
    }

    /**
     * DELETE /email/templates/{id}
     */
    public function delete_template(WP_REST_Request $request): WP_REST_Response
    {
        $id   = absint($request->get_param('id'));
        $post = get_post($id);

        if (! $post || $post->post_type !== 'email_aprio') {
            return new WP_REST_Response(array('error' => __('Template não encontrado.', 'apollo-email')), 404);
        }

        wp_delete_post($id, true);

        return new WP_REST_Response(
            array(
                'success' => true,
                'deleted' => $id,
            )
        );
    }

    /**
     * GET /email/log
     */
    public function get_log(WP_REST_Request $request): WP_REST_Response
    {
        $result = $this->plugin->logger()->getEntries(
            array(
                'status'    => $request->get_param('status') ?? '',
                'email'     => $request->get_param('email') ?? '',
                'template'  => $request->get_param('template') ?? '',
                'date_from' => $request->get_param('date_from') ?? '',
                'date_to'   => $request->get_param('date_to') ?? '',
                'per_page'  => absint($request->get_param('per_page') ?: 20),
                'page'      => absint($request->get_param('page') ?: 1),
            )
        );

        return new WP_REST_Response($result);
    }

    /**
     * POST /email/log/purge
     */
    public function purge_log(WP_REST_Request $request): WP_REST_Response
    {
        $days    = absint($request->get_param('days') ?: 90);
        $deleted = $this->plugin->logger()->purge($days);

        return new WP_REST_Response(array('deleted' => $deleted));
    }

    /**
     * GET /email/preferences
     */
    public function get_preferences(WP_REST_Request $request): WP_REST_Response
    {
        $user_id = get_current_user_id();
        $prefs   = get_user_meta($user_id, '_apollo_email_prefs', true);

        if (! is_array($prefs)) {
            $prefs = array(
                'transactional' => true,
                'marketing'     => true,
                'digest'        => true,
            );
        }

        return new WP_REST_Response($prefs);
    }

    /**
     * PUT /email/preferences
     */
    public function update_preferences(WP_REST_Request $request): WP_REST_Response
    {
        $user_id = get_current_user_id();

        $prefs = array(
            'transactional' => true, // Always true — can't opt out
            'marketing'     => (bool) $request->get_param('marketing'),
            'digest'        => (bool) $request->get_param('digest'),
        );

        update_user_meta($user_id, '_apollo_email_prefs', $prefs);

        return new WP_REST_Response(
            array(
                'success'     => true,
                'preferences' => $prefs,
            )
        );
    }

    /**
     * GET /email/track/open/{token}
     * Returns a 1x1 transparent GIF and records the open event.
     */
    public function track_open(\WP_REST_Request $request): void
    {
        $token = sanitize_text_field($request->get_param('token'));
        $entry = $this->plugin->logger()->get_by_token($token);

        if ($entry) {
            $this->plugin->logger()->logOpened((int) $entry->id);
        }

        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xFF\xFF\xFF\x00\x00\x00\x21\xF9\x04\x00\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * GET /email/track/click/{token}?url={target}
     * Records click event then redirects to target URL.
     */
    public function track_click(\WP_REST_Request $request): void
    {
        $token  = sanitize_text_field($request->get_param('token'));
        $target = esc_url_raw($request->get_param('url') ?? '');
        $entry  = $this->plugin->logger()->get_by_token($token);

        if ($entry) {
            $this->plugin->logger()->logClicked((int) $entry->id);
        }

        $redirect = ($target && wp_http_validate_url($target)) ? $target : home_url();
        wp_redirect($redirect, 302);
        exit;
    }
}
