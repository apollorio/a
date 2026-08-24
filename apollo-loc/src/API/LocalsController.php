<?php

/**
 * LocalsController — REST endpoints para CPT "local"
 *
 * Routes:
 *   GET    /apollo/v1/local          — listar locais (público)
 *   POST   /apollo/v1/local          — criar local  (admin)
 *   GET    /apollo/v1/local/{id}     — detalhe       (público)
 *   PUT    /apollo/v1/local/{id}     — atualizar     (admin)
 *   DELETE /apollo/v1/local/{id}     — deletar       (admin)
 *   GET    /apollo/v1/local/proximos — busca por geo (público)
 *
 * @package Apollo\Local\API
 */

declare(strict_types=1);

namespace Apollo\Local\API;

use Apollo\Core\API\RestBase;
use Apollo\Local\Geocoder;

if (! defined('ABSPATH')) {
    exit;
}

class LocalsController extends RestBase
{

    /**
     * REST base for this controller.
     *
     * @var string
     */
    protected $rest_base = 'local';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Registra todas as rotas REST.
     */
    public function register_routes(): void
    {

        // ── Collection: /apollo/v1/local ────────────────────────────────
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    // Logged-in: event-form quick-add ("Cadastrar Local") must work
                    // for organizers, not only admins. Update/Delete stay admin-only.
                    'permission_callback' => array($this, 'is_logged_in'),
                    'args'                => $this->get_create_params(),
                ),
            )
        );

        // ── Single: /apollo/v1/local/{id} ───────────────────────────────
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'id' => array(
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => function ($val) {
                                return is_numeric($val) && (int) $val > 0;
                            },
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'is_admin'),
                    'args'                => $this->get_create_params(),
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => array($this, 'is_admin'),
                ),
            )
        );

        // ── Proximos: /apollo/v1/local/proximos ─────────────────────────────
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/proximos',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_nearby'),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'lat'      => array(
                            'type'     => 'number',
                            'required' => true,
                        ),
                        'lng'      => array(
                            'type'     => 'number',
                            'required' => true,
                        ),
                        'radius'   => array(
                            'type'              => 'number',
                            'default'           => 5,
                            'sanitize_callback' => 'absint',
                        ),
                        'per_page' => array(
                            'type'              => 'integer',
                            'default'           => 20,
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );
    }

	// =====================================================================
	// CALLBACKS
	// =====================================================================

    /**
     * GET /locals — lista paginada.
     */
    public function get_items($request): \WP_REST_Response
    {
        $args = array(
            'post_type'      => APOLLO_LOCAL_CPT,
            'post_status'    => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged'          => $request->get_param('page') ?: 1,
            'orderby'        => $request->get_param('orderby') ?: 'title',
            'order'          => $request->get_param('order') ?: 'ASC',
        );

        // Filtro por taxonomia
        $tax_query = array();
        if ($type = $request->get_param('type')) {
            $tax_query[] = array(
                'taxonomy' => APOLLO_LOCAL_TAX_TYPE,
                'field'    => 'slug',
                'terms'    => sanitize_text_field($type),
            );
        }
        if ($area = $request->get_param('area')) {
            $tax_query[] = array(
                'taxonomy' => APOLLO_LOCAL_TAX_AREA,
                'field'    => 'slug',
                'terms'    => sanitize_text_field($area),
            );
        }
        if (! empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Exact post_name (CPT local slug), e.g. ?slug=dedge
        if ($slug = $request->get_param('slug')) {
            $args['name']           = sanitize_title($slug);
            $args['posts_per_page'] = 1;
        } elseif ($search = $request->get_param('search')) {
            // Busca textual (título)
            $args['s'] = sanitize_text_field($search);
        }

        $query = new \WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            $items[] = $this->prepare_local($post);
        }

        $response = $this->prepare_response($items);
        $response->header('X-WP-Total', (string) $query->found_posts);
        $response->header('X-WP-TotalPages', (string) $query->max_num_pages);

        return $response;
    }

    /**
     * GET /locals/{id} — detalhe de um local.
     */
    public function get_item($request): \WP_REST_Response
    {
        $id   = absint($request->get_param('id'));
        $post = get_post($id);

        if (! $post || APOLLO_LOCAL_CPT !== $post->post_type) {
            return $this->prepare_response(
                array(
                    'code'    => 'not_found',
                    'message' => 'Local não encontrado.',
                ),
                404
            );
        }

        return $this->prepare_response($this->prepare_local($post));
    }

    /**
     * POST /locals — criar novo local.
     */
    public function create_item($request): \WP_REST_Response
    {
        $title = sanitize_text_field($request->get_param('title'));

        if (empty($title)) {
            return $this->prepare_response(
                array(
                    'code'    => 'missing_title',
                    'message' => 'Título é obrigatório.',
                ),
                400
            );
        }

        $post_id = wp_insert_post(
            array(
                'post_title'   => $title,
                'post_type'    => APOLLO_LOCAL_CPT,
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
                'post_content' => wp_kses_post($request->get_param('content') ?? ''),
            ),
            true
        );

        if (is_wp_error($post_id)) {
            return $this->prepare_response(
                array(
                    'code'    => 'create_failed',
                    'message' => $post_id->get_error_message(),
                ),
                500
            );
        }

        $this->save_meta($post_id, $request);
        $this->save_taxonomies($post_id, $request);
        Geocoder::maybe_geocode($post_id);

        do_action('apollo/loc/created', $post_id, $request);

        return $this->prepare_response($this->prepare_local(get_post($post_id)), 201);
    }

    /**
     * PUT /locals/{id} — atualizar local.
     */
    public function update_item($request): \WP_REST_Response
    {
        $id   = absint($request->get_param('id'));
        $post = get_post($id);

        if (! $post || APOLLO_LOCAL_CPT !== $post->post_type) {
            return $this->prepare_response(
                array(
                    'code'    => 'not_found',
                    'message' => 'Local não encontrado.',
                ),
                404
            );
        }

        $update = array('ID' => $id);
        if ($title = $request->get_param('title')) {
            $update['post_title'] = sanitize_text_field($title);
        }
        if ($request->has_param('content')) {
            $update['post_content'] = wp_kses_post($request->get_param('content'));
        }

        wp_update_post($update);
        $this->save_meta($id, $request);
        $this->save_taxonomies($id, $request);
        Geocoder::maybe_geocode($id);

        do_action('apollo/loc/updated', $id, $request);

        return $this->prepare_response($this->prepare_local(get_post($id)));
    }

    /**
     * DELETE /locals/{id}
     */
    public function delete_item($request): \WP_REST_Response
    {
        $id   = absint($request->get_param('id'));
        $post = get_post($id);

        if (! $post || APOLLO_LOCAL_CPT !== $post->post_type) {
            return $this->prepare_response(
                array(
                    'code'    => 'not_found',
                    'message' => 'Local não encontrado.',
                ),
                404
            );
        }

        do_action('apollo/loc/before_delete', $id);

        wp_delete_post($id, true);

        return $this->prepare_response(
            array(
                'deleted' => true,
                'id'      => $id,
            )
        );
    }

    /**
     * GET /local/proximos — busca geolocalizada.
     */
    public function get_nearby($request): \WP_REST_Response
    {
        $lat      = (float) $request->get_param('lat');
        $lng      = (float) $request->get_param('lng');
        $radius   = (float) ($request->get_param('radius') ?: 5);
        $per_page = absint($request->get_param('per_page') ?: 20);

        global $wpdb;

        // Haversine — raio em km
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID,
                        lat.meta_value AS lat,
                        lng.meta_value AS lng,
                        ( 6371 * acos(
                            cos( radians(%f) ) *
                            cos( radians( CAST(lat.meta_value AS DECIMAL(10,7)) ) ) *
                            cos( radians( CAST(lng.meta_value AS DECIMAL(10,7)) ) - radians(%f) ) +
                            sin( radians(%f) ) *
                            sin( radians( CAST(lat.meta_value AS DECIMAL(10,7)) ) )
                        )) AS distance
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} lat ON lat.post_id = p.ID AND lat.meta_key = '_local_lat'
                 INNER JOIN {$wpdb->postmeta} lng ON lng.post_id = p.ID AND lng.meta_key = '_local_lng'
                 WHERE p.post_type = %s
                   AND p.post_status = 'publish'
                   AND lat.meta_value != ''
                   AND lng.meta_value != ''
                 HAVING distance <= %f
                 ORDER BY distance ASC
                 LIMIT %d",
                $lat,
                $lng,
                $lat,
                APOLLO_LOCAL_CPT,
                $radius,
                $per_page
            )
        );

        $items = array();
        foreach ($results as $row) {
            $post             = get_post((int) $row->ID);
            $item             = $this->prepare_local($post);
            $item['distance'] = round((float) $row->distance, 2);
            $items[]          = $item;
        }

        return $this->prepare_response($items);
    }

	// =====================================================================
	// HELPERS
	// =====================================================================

    /**
     * Prepara um post "local" para resposta REST.
     */
    private function prepare_local(\WP_Post $post): array
    {
        $meta = array();
        foreach (APOLLO_LOCAL_META_KEYS as $key) {
            $clean_key          = ltrim($key, '_');
            $clean_key          = str_replace('local_', '', $clean_key);
            $meta[$clean_key] = get_post_meta($post->ID, $key, true);
        }

        $types = wp_get_post_terms($post->ID, APOLLO_LOCAL_TAX_TYPE, array('fields' => 'all'));
        $areas = wp_get_post_terms($post->ID, APOLLO_LOCAL_TAX_AREA, array('fields' => 'all'));

        $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium');

        return array(
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'slug'      => $post->post_name,
            'content'   => wp_kses_post($post->post_content),
            'excerpt'   => get_the_excerpt($post),
            'thumbnail' => $thumbnail ?: null,
            'link'      => get_permalink($post->ID),
            'meta'      => $meta,
            'types'     => array_map(
                fn($t) => array(
                    'id'   => $t->term_id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                ),
                is_array($types) ? $types : array()
            ),
            'areas'     => array_map(
                fn($a) => array(
                    'id'   => $a->term_id,
                    'name' => $a->name,
                    'slug' => $a->slug,
                ),
                is_array($areas) ? $areas : array()
            ),
            'author'    => (int) $post->post_author,
            'date'      => $post->post_date,
            'modified'  => $post->post_modified,
        );
    }

    /**
     * Salva meta keys do request.
     */
    private function save_meta(int $post_id, \WP_REST_Request $request): void
    {
        $map = array(
            'name'        => '_local_name',
            'address'     => '_local_address',
            'city'        => '_local_city',
            'state'       => '_local_state',
            'country'     => '_local_country',
            'postal'      => '_local_postal',
            'lat'         => '_local_lat',
            'lng'         => '_local_lng',
            'phone'       => '_local_phone',
            'website'     => '_local_website',
            'instagram'   => '_local_instagram',
            'capacity'    => '_local_capacity',
            'price_range' => '_local_price_range',
        );

        foreach ($map as $param => $meta_key) {
            if ($request->has_param($param)) {
                $value = $request->get_param($param);
                update_post_meta($post_id, $meta_key, sanitize_text_field((string) $value));
            }
        }
    }

    /**
     * Salva taxonomias do request.
     */
    private function save_taxonomies(int $post_id, \WP_REST_Request $request): void
    {
        if ($request->has_param('type')) {
            wp_set_object_terms($post_id, sanitize_text_field($request->get_param('type')), APOLLO_LOCAL_TAX_TYPE);
        }
        if ($request->has_param('area')) {
            wp_set_object_terms($post_id, sanitize_text_field($request->get_param('area')), APOLLO_LOCAL_TAX_AREA);
        }
    }

	// =====================================================================
	// PARAM SCHEMAS
	// =====================================================================

    /**
     * Parâmetros para collection (GET /locals).
     */
    public function get_collection_params(): array
    {
        return array(
            'page'     => array(
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'type'              => 'integer',
                'default'           => 20,
                'sanitize_callback' => 'absint',
            ),
            'search'   => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'slug'     => array(
                'type'              => 'string',
                'description'       => 'Exact CPT local post_name (e.g. dedge).',
                'sanitize_callback' => 'sanitize_title',
            ),
            'type'     => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'area'     => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'orderby'  => array(
                'type'    => 'string',
                'default' => 'title',
                'enum'    => array('title', 'date', 'modified', 'ID'),
            ),
            'order'    => array(
                'type'    => 'string',
                'default' => 'ASC',
                'enum'    => array('ASC', 'DESC'),
            ),
        );
    }

    /**
     * Parâmetros para criação/update.
     */
    private function get_create_params(): array
    {
        return array(
            'title'       => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'content'     => array(
                'type'              => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ),
            'name'        => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'address'     => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'city'        => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'state'       => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'country'     => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'postal'      => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'lat'         => array('type' => 'number'),
            'lng'         => array('type' => 'number'),
            'phone'       => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'website'     => array(
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
            ),
            'instagram'   => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'capacity'    => array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'price_range' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'type'        => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'area'        => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }
}
