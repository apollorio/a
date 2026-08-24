<?php
/**
 * REST API: Search Controller
 *
 * Endpoint: /classifieds/search (GET)
 * Adapted from WPAdverts search/filter patterns.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SearchController extends \WP_REST_Controller {

	protected $namespace = APOLLO_ADVERTS_REST_NAMESPACE;
	protected $rest_base = 'classifieds/search';

	/**
	 * Register routes
	 * Registry spec: /classifieds/search (GET)
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_items' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_search_params(),
				),
			)
		);
	}

	/**
	 * GET /classifieds/search — Search classifieds
	 * Adapted from WPAdverts search shortcode query logic
	 */
	public function search_items( $request ): \WP_REST_Response {
		$args = array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ) ?: APOLLO_ADVERTS_POSTS_PER_PAGE,
			'paged'          => $request->get_param( 'page' ) ?: 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Text search
		$query = $request->get_param( 'query' );
		if ( $query ) {
			$args['s'] = sanitize_text_field( $query );
		}

		// Taxonomy filters
		$tax_query = array();
		$domain    = $request->get_param( 'domain' );
		if ( $domain ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_TAX_CLASSIFIED_DOMAIN,
				'field'    => 'slug',
				'terms'    => $domain,
			);
		}
		$intent = $request->get_param( 'intent' );
		if ( $intent ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_TAX_CLASSIFIED_INTENT,
				'field'    => 'slug',
				'terms'    => $intent,
			);
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		// Meta filters
		$meta_query = array();

		// Location
		$location = $request->get_param( 'location' );
		if ( $location ) {
			$meta_query[] = array(
				'key'     => '_classified_loc',
				'value'   => sanitize_text_field( $location ),
				'compare' => 'LIKE',
			);
		}

		// Price range
		$price_min = $request->get_param( 'price_min' );
		if ( $price_min !== null && is_numeric( $price_min ) ) {
			$meta_query[] = array(
				'key'     => '_classified_price',
				'value'   => (float) $price_min,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		$price_max = $request->get_param( 'price_max' );
		if ( $price_max !== null && is_numeric( $price_max ) ) {
			$meta_query[] = array(
				'key'     => '_classified_price',
				'value'   => (float) $price_max,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}

		// Condition
		$condition = $request->get_param( 'condition' );
		if ( $condition ) {
			$meta_query[] = array(
				'key'   => '_classified_condition',
				'value' => sanitize_key( $condition ),
			);
		}

		// Featured only
		$featured = $request->get_param( 'featured' );
		if ( $featured ) {
			$meta_query[] = array(
				'key'   => '_classified_featured',
				'value' => '1',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		/**
		 * Filter search query args
		 */
		$args = apply_filters( 'apollo/classifieds/search_query_args', $args, $request );

		$wp_query = new \WP_Query( $args );
		$items    = array();

		foreach ( $wp_query->posts as $post ) {
			$items[] = $this->prepare_search_item( $post );
		}

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (string) $wp_query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $wp_query->max_num_pages );

		return $response;
	}

	/**
	 * Prepare item for search response (lighter than full response)
	 */
	protected function prepare_search_item( \WP_Post $post ): array {
		$image = apollo_adverts_get_main_image( $post->ID, 'classified-thumb' );

		return array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'excerpt'     => get_the_excerpt( $post ),
			'link'        => get_permalink( $post->ID ),
			'image'       => $image,
			'price'       => apollo_adverts_get_the_price( $post->ID ),
			'location'    => get_post_meta( $post->ID, '_classified_loc', true ),
			'condition'   => apollo_adverts_get_condition_label( $post->ID ),
			'is_featured' => apollo_adverts_is_featured( $post->ID ),
			'date'        => $post->post_date,
		);
	}

	/**
	 * Search params schema
	 */
	protected function get_search_params(): array {
		return array(
			'query'     => array(
				'type'              => 'string',
				'description'       => 'Text search query',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'domain'    => array(
				'type'              => 'string',
				'description'       => 'Filter by classified_domain slug',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'intent'    => array(
				'type'              => 'string',
				'description'       => 'Filter by classified_intent slug',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'location'  => array(
				'type'              => 'string',
				'description'       => 'Filter by location (partial match)',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'price_min' => array(
				'type'              => 'number',
				'description'       => 'Minimum price',
				'sanitize_callback' => fn( $v ) => (float) $v,
			),
			'price_max' => array(
				'type'              => 'number',
				'description'       => 'Maximum price',
				'sanitize_callback' => fn( $v ) => (float) $v,
			),
			'condition' => array(
				'type'              => 'string',
				'enum'              => array( 'novo', 'usado', 'recondicionado' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'featured'  => array(
				'type'              => 'boolean',
				'description'       => 'Only featured ads',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'page'      => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page'  => array(
				'type'              => 'integer',
				'default'           => APOLLO_ADVERTS_POSTS_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
		);
	}
}
