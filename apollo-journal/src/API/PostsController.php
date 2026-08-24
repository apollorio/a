<?php

/**
 * REST Controller — Journal Posts
 *
 * Endpoint: GET /apollo/v1/journal/posts
 * Used by the load-more JS and external consumers.
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Posts REST controller.
 */
class PostsController {


	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Legacy: GET /journal/posts (post type).
		register_rest_route(
			APOLLO_JOURNAL_REST_NAMESPACE,
			'/journal/posts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_posts' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
			)
		);

		// GET /journal/news (journal_news CPT).
		register_rest_route(
			APOLLO_JOURNAL_REST_NAMESPACE,
			'/journal/news',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_news' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
			)
		);

		// GET /journal/notas (journal_nota CPT).
		register_rest_route(
			APOLLO_JOURNAL_REST_NAMESPACE,
			'/journal/notas',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_notas' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_nota_params(),
				),
			)
		);
	}

	/**
	 * Get paginated journal posts.
	 *
	 * @param \WP_REST_Request $request Full request data.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_posts( \WP_REST_Request $request ) {
		$page     = $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1;
		$per_page = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 6;
		$category = $request->get_param( 'category' ) ? sanitize_text_field( $request->get_param( 'category' ) ) : '';
		$taxonomy = $request->get_param( 'taxonomy' ) ? sanitize_key( $request->get_param( 'taxonomy' ) ) : '';
		$term     = $request->get_param( 'term' ) ? sanitize_text_field( $request->get_param( 'term' ) ) : '';

		$per_page = min( $per_page, 24 );

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $category ) ) {
			$args['category_name'] = $category;
		}

		$allowed_tax = array( 'category', 'post_tag', 'music', 'culture', 'rio', 'formato' );
		if ( ! empty( $taxonomy ) && in_array( $taxonomy, $allowed_tax, true ) && ! empty( $term ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $term,
				),
			);
		}

		$query = new \WP_Query( $args );
		$data  = array();

		foreach ( $query->posts as $post ) {
			$cats     = get_the_category( $post->ID );
			$cat_name = ! empty( $cats ) ? $cats[0]->name : 'News';
			$nrep     = get_post_meta( $post->ID, '_nrep_code', true );

			$time_ago = function_exists( 'apollo_time_ago' )
				? apollo_time_ago( get_post_time( 'Y-m-d H:i:s', false, $post ) )
				: human_time_diff( get_post_time( 'U', false, $post ), time() );

			$data[] = array(
				'id'          => $post->ID,
				'title'       => array( 'rendered' => get_the_title( $post ) ),
				'link'        => get_permalink( $post ),
				'excerpt'     => wp_trim_words( get_the_excerpt( $post ), 20 ),
				'thumbnail'   => get_the_post_thumbnail_url( $post, 'medium' ) ?: '',
				'badge'       => $nrep ?: strtoupper( $cat_name ),
				'is_nrep'     => (bool) $nrep,
				'author_name' => get_the_author_meta( 'display_name', $post->post_author ),
				'time_ago'    => $time_ago,
				'date'        => get_the_date( 'c', $post ),
			);
		}

		$response = new \WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * Define REST params schema.
	 *
	 * @return array<string, array>
	 */
	private function get_collection_params(): array {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => 6,
				'minimum'           => 1,
				'maximum'           => 24,
				'sanitize_callback' => 'absint',
			),
			'category' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'taxonomy' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			),
			'term'     => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get paginated journal_news articles.
	 *
	 * @param \WP_REST_Request $request Full request data.
	 * @return \WP_REST_Response
	 */
	public function get_news( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = absint( $request->get_param( 'page' ) ) ?: 1;
		$per_page = min( absint( $request->get_param( 'per_page' ) ) ?: 6, 24 );
		$category = sanitize_text_field( (string) $request->get_param( 'category' ) );
		$taxonomy = sanitize_key( (string) $request->get_param( 'taxonomy' ) );
		$term     = sanitize_text_field( (string) $request->get_param( 'term' ) );

		$args = array(
			'post_type'      => 'journal_news',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $category ) {
			$args['category_name'] = $category;
		}

		$allowed_tax = array( 'category', 'post_tag', 'music', 'culture', 'rio', 'formato' );
		if ( $taxonomy && in_array( $taxonomy, $allowed_tax, true ) && $term ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $term,
				),
			);
		}

		$query = new \WP_Query( $args );
		$data  = array();

		if ( $query->have_posts() ) {
			$ids = wp_list_pluck( $query->posts, 'ID' );
			update_post_meta_cache( $ids );
			update_object_term_cache( $ids, 'journal_news' );
		}

		foreach ( $query->posts as $post ) {
			$cats     = get_the_category( $post->ID );
			$cat_name = ! empty( $cats ) ? $cats[0]->name : 'News';
			$nrep     = get_post_meta( $post->ID, '_nrep_code', true );
			$headline = get_post_meta( $post->ID, '_apollo_headline', true );
			$subtitle = get_post_meta( $post->ID, '_apollo_subtitle', true );

			$time_ago = function_exists( 'apollo_time_ago' )
				? apollo_time_ago( get_post_time( 'Y-m-d H:i:s', false, $post ) )
				: human_time_diff( get_post_time( 'U', false, $post ), time() );

			$data[] = array(
				'id'          => $post->ID,
				'title'       => array( 'rendered' => get_the_title( $post ) ),
				'headline'    => $headline ?: get_the_title( $post ),
				'subtitle'    => $subtitle ?: '',
				'link'        => get_permalink( $post ),
				'excerpt'     => wp_trim_words( get_the_excerpt( $post ), 20 ),
				'thumbnail'   => get_the_post_thumbnail_url( $post, 'medium_large' ) ?: '',
				'badge'       => $nrep ?: strtoupper( $cat_name ),
				'is_nrep'     => (bool) $nrep,
				'author_name' => get_the_author_meta( 'display_name', (int) $post->post_author ),
				'time_ago'    => $time_ago,
				'date'        => get_the_date( 'c', $post ),
			);
		}

		$response = new \WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * Get paginated journal_nota (notas de repúdio).
	 *
	 * @param \WP_REST_Request $request Full request data.
	 * @return \WP_REST_Response
	 */
	public function get_notas( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = absint( $request->get_param( 'page' ) ) ?: 1;
		$per_page = min( absint( $request->get_param( 'per_page' ) ) ?: 8, 24 );
		$type     = sanitize_text_field( (string) $request->get_param( 'type' ) );

		$args = array(
			'post_type'      => 'journal_nota',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $type ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_apollo_note_type',
					'value' => $type,
				),
			);
		}

		$query = new \WP_Query( $args );
		$data  = array();

		if ( $query->have_posts() ) {
			$ids = wp_list_pluck( $query->posts, 'ID' );
			update_post_meta_cache( $ids );
		}

		foreach ( $query->posts as $post ) {
			$nrep_code = get_post_meta( $post->ID, '_nrep_code', true );
			$note_type = get_post_meta( $post->ID, '_apollo_note_type', true );
			$source    = get_post_meta( $post->ID, '_apollo_source', true );

			$time_ago = function_exists( 'apollo_time_ago' )
				? apollo_time_ago( get_post_time( 'Y-m-d H:i:s', false, $post ) )
				: human_time_diff( get_post_time( 'U', false, $post ), time() );

			$data[] = array(
				'id'          => $post->ID,
				'title'       => array( 'rendered' => get_the_title( $post ) ),
				'link'        => get_permalink( $post ),
				'excerpt'     => wp_trim_words( get_the_excerpt( $post ), 20 ),
				'nrep_code'   => $nrep_code ?: '',
				'note_type'   => $note_type ?: '',
				'source'      => $source ?: '',
				'author_name' => get_the_author_meta( 'display_name', (int) $post->post_author ),
				'time_ago'    => $time_ago,
				'date'        => get_the_date( 'c', $post ),
			);
		}

		$response = new \WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * Define REST params for nota endpoint.
	 *
	 * @return array<string, array>
	 */
	private function get_nota_params(): array {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => 8,
				'minimum'           => 1,
				'maximum'           => 24,
				'sanitize_callback' => 'absint',
			),
			'type'     => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => 'Filter by note type (e.g., repudio, manifesto).',
			),
		);
	}
}
