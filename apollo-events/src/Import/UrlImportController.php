<?php

/**
 * URL importer — REST surface.
 *
 * Two routes, deliberately separate so the operator always sees what will be
 * written BEFORE anything is written:
 *
 *   POST apollo/v1/eventos/importar-url/preview   → fetch + normalise only
 *   POST apollo/v1/eventos/importar-url           → preview + create/update the event
 *
 * Both run entirely server-side (PHP → provider API). The browser only ever
 * talks to apollo.rio.br, so there is no cross-origin request anywhere in the
 * chain and CORS cannot break the importer — which is exactly what used to.
 *
 * Strict CPT contract on import():
 *   cover → CoverSideloader → `_event_banner` === `_thumbnail_id`
 *   venue → match_loc() → `_event_loc_id` (hard-fail if missing)
 *   coupon → `_event_coupon_code`
 *   dates  → start + end (overnight derived when needed)
 *
 * @package Apollo\Event
 * @since   1.7.0
 */

declare(strict_types=1);

namespace Apollo\Event\Import;

use Apollo\Event\Import\Pipeline\ImportPipeline;

if (! defined('ABSPATH')) {
    exit;
}

final class UrlImportController
{
    private const NS = 'apollo/v1';

    private ImportPipeline $pipeline;

    public function __construct()
    {
        $this->pipeline = new ImportPipeline();
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes(): void
    {
        $args = array(
            'url'    => array(
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'esc_url_raw',
                'validate_callback' => static function ($value): bool {
                    return is_string($value) && (bool) filter_var($value, FILTER_VALIDATE_URL);
                },
            ),
            'coupon' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );

        register_rest_route(
            self::NS,
            '/eventos/importar-url/preview',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'preview'),
                'permission_callback' => array($this, 'can_import'),
                'args'                => $args,
            )
        );

        register_rest_route(
            self::NS,
            '/eventos/importar-url',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'import'),
                'permission_callback' => array($this, 'can_import'),
                'args'                => $args + array(
                    'status'   => array(
                        'type'              => 'string',
                        'default'           => 'draft',
                        'enum'              => array('draft', 'publish', 'pending'),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'link_loc' => array(
                        'type'    => 'boolean',
                        'default' => true,
                    ),
                ),
            )
        );
    }

    /**
     * Importing creates real content, so it needs the same capability creating
     * an event by hand does — never __return_true.
     */
    public function can_import(): bool
    {
        return current_user_can('edit_posts');
    }

    public function preview(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $url    = (string) $request->get_param('url');
        $coupon = (string) $request->get_param('coupon');

        $result = $this->pipeline->preview($url, $coupon);
        if (is_wp_error($result)) {
            return $result;
        }

        return new \WP_REST_Response($result->preview_response(), 200);
    }

    public function import(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $url    = (string) $request->get_param('url');
        $coupon = (string) $request->get_param('coupon');
        $status = (string) $request->get_param('status');

        $result = $this->pipeline->import($url, $coupon, $status);
        if (is_wp_error($result)) {
            return $result;
        }

        $body     = $result->import_response();
        $code     = $result->created ? 201 : 200;

        return new \WP_REST_Response($body, $code);
    }
}
