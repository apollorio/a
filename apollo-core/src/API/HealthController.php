<?php

/**
 * Health Check REST Controller
 *
 * Endpoints per apollo-registry.json:
 * - GET /apollo/v1/health - Health check
 * - GET /apollo/v1/registry - Get registry (admin only)
 *
 * @package Apollo\Core\API
 * @since 6.0.0
 */

namespace Apollo\Core\API;

use Apollo\Core\Registry;

if (! defined('ABSPATH')) {
    exit;
}

class HealthController extends RestBase
{

    public function __construct()
    {
        parent::__construct();
        $this->register_routes();
    }

    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/health',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_health'),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        // /registry route is handled exclusively by RegistryController — removed from here to avoid conflict.
    }

    public function get_health(\WP_REST_Request $request): \WP_REST_Response
    {
        $health = array(
            'status'    => 'ok',
            'version'   => defined('APOLLO_VERSION') ? APOLLO_VERSION : '6.0.0',
            'timestamp' => current_time('mysql'),
            'checks'    => array(
                'database' => $this->check_database(),
                'cdn'      => $this->check_cdn(),
                'registry' => $this->check_registry(),
                'bridges'  => $this->check_bridges(),
            ),
        );

        $overall = true;
        foreach ($health['checks'] as $check) {
            if ($check['status'] !== 'ok') {
                $overall = false;
                break;
            }
        }
        $health['status'] = $overall ? 'ok' : 'error';

        return $this->prepare_response($health);
    }

    public function get_registry(\WP_REST_Request $request): \WP_REST_Response
    {
        $registry = Registry::get_registry();

        return $this->prepare_response(
            array(
                'registry'      => $registry,
                'version'       => $registry['$version'] ?? 'unknown',
                'plugins_count' => count($registry['plugins'] ?? array()),
            )
        );
    }

    private function check_database(): array
    {
        global $wpdb;
        $table  = $wpdb->prefix . 'apollo_audit_log';
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

        return array(
            'status'  => $exists ? 'ok' : 'error',
            'message' => $exists ? 'apollo_audit_log exists' : 'apollo_audit_log missing',
        );
    }

    private function check_cdn(): array
    {
        $registered = get_option('apollo_cdn_registered', false);

        return array(
            'status'  => $registered ? 'ok' : 'warning',
            'message' => $registered ? 'CDN registered' : 'CDN not registered',
            'url'     => defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : null,
        );
    }

    private function check_registry(): array
    {
        $registry = Registry::get_registry();
        $loaded   = ! empty($registry);

        return array(
            'status'  => $loaded ? 'ok' : 'error',
            'message' => $loaded ? 'Registry loaded' : 'Registry not loaded',
            'version' => $registry['$version'] ?? null,
        );
    }

    private function check_bridges(): array
    {
        return array(
            'status'  => 'ok',
            'message' => 'Apollo native plugins',
            'active'  => array(),
        );
    }
}
