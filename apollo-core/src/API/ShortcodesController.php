<?php

/**
 * Shortcodes REST Controller
 *
 * Provides REST API endpoints for Apollo shortcode discovery and rendering.
 * Migrated from apollo-shortcodes/includes/class-plugin.php (FASE 2).
 *
 * Routes:
 *   GET  /apollo/v1/shortcodes             — list all registered Apollo shortcodes
 *   GET  /apollo/v1/shortcodes/{tag}       — get metadata for a specific shortcode
 *   POST /apollo/v1/shortcodes/render      — render a whitelisted shortcode (auth required)
 *
 * @package Apollo\Core\API
 * @since   6.4.1
 */

namespace Apollo\Core\API;

if (! defined('ABSPATH')) {
    exit;
}

class ShortcodesController extends RestBase
{

    /** @var string REST resource. */
    protected $rest_base = 'shortcodes';

    /**
     * Shortcodes that may be rendered via REST.
     * Maintainers: grow this list carefully — each entry IS a potential XSS surface.
     *
     * @var string[]
     */
    private const ALLOWED_RENDER = array(
        'apollo_newsletter',
        'apollo_events',
        'apollo_event_single',
        'apollo_event_calendar',
        'apollo_social_feed',
        'apollo_social_share',
        'apollo_fav_dashboard',
        'apollo_user_stats',
        'apollo_wow_chart',
        'apollo_classifieds',
        'apollo_classified_form',
        'apollo_classifieds_sell_ticket',
        'apollo_classifieds_rent_space',
        'apollo_user_profile',
        'apollo_user_dashboard',
        'apollo_profile_card',
        'apollo_follow_button',
        'apollo_membership_button',
    );

    public function __construct()
    {
        parent::__construct();
        $this->register_routes();
    }

    /**
     * Register REST routes.
     */
    public function register_routes(): void
    {

        // GET /shortcodes — list all Apollo shortcodes
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => '__return_true',
            )
        );

        // GET /shortcodes/{tag} — get one shortcode's metadata
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<tag>[a-z_]+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'tag' => array(
                        'description'       => __('Shortcode tag (e.g. apollo_events).', 'apollo-core'),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // POST /shortcodes/render — render a whitelisted shortcode
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/render',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'render_item'),
                'permission_callback' => array($this, 'can_render'),
                'args'                => array(
                    'tag'        => array(
                        'description'       => __('Shortcode tag.', 'apollo-core'),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'attributes' => array(
                        'description'       => __('Shortcode attributes (key-value pairs).', 'apollo-core'),
                        'type'              => 'object',
                        'default'           => array(),
                    ),
                    'content'    => array(
                        'description'       => __('Inner content for enclosing shortcodes.', 'apollo-core'),
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'wp_kses_post',
                    ),
                ),
            )
        );
    }

	// ─────────────────────────────────────────────────────────────────────────
	// HANDLERS
	// ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /shortcodes — returns all Apollo shortcodes from WordPress registry + metadata.
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response
     */
    public function get_items($request): \WP_REST_Response
    {
        global $shortcode_tags;

        // Gather live WordPress-registered Apollo shortcodes.
        $active = array();
        foreach (array_keys($shortcode_tags) as $tag) {
            if (str_starts_with($tag, 'apollo_')) {
                $active[] = $tag;
            }
        }

        // Merge with ShortcodeRegistry metadata (if available).
        $meta = array();
        if (class_exists('\Apollo\Core\ShortcodeRegistry')) {
            $meta = \Apollo\Core\ShortcodeRegistry::get_instance()->get_all();
        }

        $result = array();
        foreach ($active as $tag) {
            $result[$tag] = array_merge(
                array('tag' => $tag, 'active' => true),
                $meta[$tag] ?? array()
            );
        }

        // Also include documented but not yet active shortcodes (with active:false).
        foreach ($meta as $tag => $data) {
            if (! isset($result[$tag])) {
                $result[$tag] = array_merge(array('active' => false), $data);
            }
        }

        return $this->prepare_response(array(
            'total'      => count($result),
            'shortcodes' => array_values($result),
        ));
    }

    /**
     * GET /shortcodes/{tag} — returns metadata for one shortcode.
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_item($request)
    {
        global $shortcode_tags;

        $tag = sanitize_key($request->get_param('tag'));

        if (! $tag) {
            return $this->prepare_error('invalid_tag', __('Invalid shortcode tag.', 'apollo-core'), 400);
        }

        // Check live registry.
        $is_active = isset($shortcode_tags[$tag]);

        // Fetch documentation from ShortcodeRegistry.
        $meta = array();
        if (class_exists('\Apollo\Core\ShortcodeRegistry')) {
            $meta = \Apollo\Core\ShortcodeRegistry::get_instance()->get($tag) ?? array();
        }

        if (! $is_active && empty($meta)) {
            return $this->prepare_error('not_found', __('Shortcode not found.', 'apollo-core'), 404);
        }

        return $this->prepare_response(array_merge(
            array('tag' => $tag, 'active' => $is_active),
            $meta
        ));
    }

    /**
     * POST /shortcodes/render — executes a whitelisted shortcode and returns HTML.
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function render_item($request)
    {
        $tag     = sanitize_key($request->get_param('tag'));
        $atts    = (array) $request->get_param('attributes') ?: array();
        $content = wp_kses_post($request->get_param('content') ?: '');

        if (empty($tag)) {
            return $this->prepare_error('missing_tag', __('Shortcode tag is required.', 'apollo-core'), 400);
        }

        $allowed = apply_filters('apollo/shortcodes/allowed_render', self::ALLOWED_RENDER);

        if (! in_array($tag, $allowed, true)) {
            return $this->prepare_error(
                'not_allowed',
                __('Shortcode not allowed for remote rendering.', 'apollo-core'),
                403
            );
        }

        // Build shortcode string.
        $atts_str = '';
        foreach ($atts as $key => $value) {
            $key      = sanitize_key($key);
            $value    = esc_attr((string) $value);
            $atts_str .= " {$key}=\"{$value}\"";
        }

        $shortcode_str = empty($content)
            ? "[{$tag}{$atts_str}]"
            : "[{$tag}{$atts_str}]{$content}[/{$tag}]";

        $html = do_shortcode($shortcode_str);

        return $this->prepare_response(array(
            'tag'  => $tag,
            'html' => $html,
        ));
    }

	// ─────────────────────────────────────────────────────────────────────────
	// PERMISSIONS
	// ─────────────────────────────────────────────────────────────────────────

    /**
     * Only users with edit_posts can render shortcodes via REST.
     *
     * @return bool|\WP_Error
     */
    public function can_render()
    {
        if (! current_user_can('edit_posts')) {
            return $this->prepare_error(
                'rest_forbidden',
                __('You need to be logged in with edit capability.', 'apollo-core'),
                401
            );
        }
        return true;
    }
}
