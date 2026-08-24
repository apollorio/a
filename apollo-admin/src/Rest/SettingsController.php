<?php

/**
 * Apollo Admin REST Controller — CRUD for settings via REST API.
 *
 * Endpoints:
 *   GET    /apollo/v1/settings              → all settings
 *   GET    /apollo/v1/settings/<slug>       → settings for one plugin
 *   POST   /apollo/v1/settings/<slug>       → update settings for one plugin
 *   GET    /apollo/v1/registry              → all registered plugins
 *   POST   /apollo/v1/registry/refresh      → clear registry cache
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin\Rest;

use Apollo\Admin\Settings;
use Apollo\Admin\Registry;

if (! defined('ABSPATH')) {
    exit;
}

final class SettingsController
{

    private string $namespace;

    public function __construct()
    {
        $this->namespace = APOLLO_ADMIN_REST_NAMESPACE;
    }

    /**
     * Register routes — called on rest_api_init
     */
    public function register_routes(): void
    {
        // ── Settings ────────────────────────────────────────────────

        // GET all settings
        register_rest_route(
            $this->namespace,
            '/settings',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_all_settings'),
                'permission_callback' => array($this, 'admin_permission'),
            )
        );

        // GET / POST single plugin settings
        register_rest_route(
            $this->namespace,
            '/settings/(?P<slug>[a-z0-9_-]+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_plugin_settings'),
                    'permission_callback' => array($this, 'admin_permission'),
                    'args'                => array(
                        'slug' => array(
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_key',
                            'validate_callback' => function ($param) {
                                return is_string($param) && strlen($param) > 0;
                            },
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'update_plugin_settings'),
                    'permission_callback' => array($this, 'admin_permission'),
                    'args'                => array(
                        'slug' => array(
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_key',
                        ),
                    ),
                ),
            )
        );

        // ── Schema ──────────────────────────────────────────────────

        // GET schema for a plugin tab
        register_rest_route(
            $this->namespace,
            '/settings/(?P<slug>[a-z0-9_-]+)/schema',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_schema'),
                'permission_callback' => array($this, 'admin_permission'),
                'args'                => array(
                    'slug' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // ── Registry ────────────────────────────────────────────────

        // GET all registered plugins (namespaced under /admin/ to avoid collision with apollo-core /registry)
        register_rest_route(
            $this->namespace,
            '/admin/registry',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_registry'),
                'permission_callback' => array($this, 'admin_permission'),
            )
        );

        // POST refresh registry cache
        register_rest_route(
            $this->namespace,
            '/admin/registry/refresh',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'refresh_registry'),
                'permission_callback' => array($this, 'admin_permission'),
            )
        );

        // ── Export / Import ─────────────────────────────────────────

        register_rest_route(
            $this->namespace,
            '/settings/export',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'export_settings'),
                'permission_callback' => array($this, 'admin_permission'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/settings/import',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'import_settings'),
                'permission_callback' => array($this, 'admin_permission'),
            )
        );
    }

    /* ─────────────────────────── Callbacks ─────────────────────── */

    public function get_all_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response(Settings::get_instance()->all(), 200);
    }

    public function get_plugin_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        $slug = $request->get_param('slug');
        return new \WP_REST_Response(
            array(
                'slug'     => $slug,
                'settings' => Settings::get_instance()->for_plugin($slug),
            ),
            200
        );
    }

    public function update_plugin_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        $slug   = $request->get_param('slug');
        $body   = $request->get_json_params();
        $schema = Settings::get_schema($slug);

        if (empty($body) || ! is_array($body)) {
            return new \WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Request body must be a JSON object.',
                ),
                400
            );
        }

        // Sanitize against schema
        $sanitized = array();
        foreach ($body as $key => $value) {
            if (! isset($schema[$key])) {
                // Allow free-form keys if no schema (future-proof)
                $sanitized[$key] = sanitize_text_field((string) $value);
                continue;
            }
            $field = $schema[$key];
            switch ($field['type']) {
                case 'toggle':
                    $sanitized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'number':
                    $sanitized[$key] = (int) $value;
                    break;
                case 'email':
                    $sanitized[$key] = sanitize_email((string) $value);
                    break;
                case 'color':
                    $sanitized[$key] = sanitize_hex_color((string) $value) ?: $field['default'];
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field((string) $value);
            }
        }

        $ok = Settings::get_instance()->update_plugin($slug, $sanitized);

        return new \WP_REST_Response(
            array(
                'success'  => $ok,
                'slug'     => $slug,
                'settings' => Settings::get_instance()->for_plugin($slug),
            ),
            $ok ? 200 : 500
        );
    }

    public function get_schema(\WP_REST_Request $request): \WP_REST_Response
    {
        $slug = $request->get_param('slug');
        return new \WP_REST_Response(
            array(
                'slug'   => $slug,
                'schema' => Settings::get_schema($slug),
            ),
            200
        );
    }

    public function get_registry(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response(Registry::get_instance()->all(), 200);
    }

    public function refresh_registry(\WP_REST_Request $request): \WP_REST_Response
    {
        Registry::get_instance()->clear_cache();
        return new \WP_REST_Response(
            array(
                'success' => true,
                'plugins' => Registry::get_instance()->all(),
            ),
            200
        );
    }

    public function export_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response(
            array(
                'success' => true,
                'data'    => Settings::get_instance()->all(),
            ),
            200
        );
    }

    public function import_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        $json = $request->get_body();
        $ok   = Settings::get_instance()->import($json);

        return new \WP_REST_Response(
            array(
                'success' => $ok,
                'message' => $ok ? 'Import successful.' : 'Invalid JSON payload.',
            ),
            $ok ? 200 : 400
        );
    }

    /* ─────────────────────────── Permission ────────────────────── */

    public function admin_permission(\WP_REST_Request $request): bool
    {
        return current_user_can('manage_options');
    }
}
