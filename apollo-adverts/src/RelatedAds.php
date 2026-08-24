<?php

/**
 * Apollo Adverts — Related Ads.
 *
 * Displays related classified ads on the single ad page.
 * Adapted from WPAdverts snippet related-ads.
 *
 * @package Apollo\Adverts
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Adverts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Related Ads display component.
 *
 * @since 1.1.0
 */
class RelatedAds {


	/**
	 * Default number of related ads to show.
	 */
	private const DEFAULT_COUNT = 4;

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// Hook into single classified template.
		add_action( 'apollo/classifieds/single/after_content', array( $this, 'display_related' ), 20 );

		// Shortcode.
		add_shortcode( 'apollo_related_classifieds', array( $this, 'shortcode' ) );

		// REST endpoint.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Display related ads in single classified template.
	 *
	 * Adapted from WPAdverts related_ads_tpl_single_bottom().
	 *
	 * @param int $post_id Current classified post ID.
	 */
	public function display_related( int $post_id ): void {
		$related = $this->get_related( $post_id );

		if ( empty( $related ) ) {
			return;
		}

		// Load the related ads template part.
		$template = APOLLO_ADVERTS_DIR . 'templates/parts/related-ads.php';
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			$this->render_default( $related );
		}
	}

	/**
	 * Shortcode [apollo_related_classifieds].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
				'count'   => self::DEFAULT_COUNT,
			),
			$atts,
			'apollo_related_classifieds'
		);

		$related = $this->get_related( (int) $atts['post_id'], (int) $atts['count'] );

		if ( empty( $related ) ) {
			return '';
		}

		ob_start();
		$this->render_default( $related );
		return ob_get_clean();
	}

	/**
	 * Register REST endpoint.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			APOLLO_ADVERTS_REST_NAMESPACE,
			'/classifieds/(?P<id>[\d]+)/related',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_related' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'    => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'count' => array(
						'type'              => 'integer',
						'default'           => self::DEFAULT_COUNT,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * REST callback — get related classifieds.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_related( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$count   = (int) $request->get_param( 'count' );

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
			return new \WP_REST_Response(
				array( 'message' => __( 'Anúncio não encontrado.', 'apollo-adverts' ) ),
				404
			);
		}

		$related = $this->get_related( $post_id, $count );

		return new \WP_REST_Response( $related, 200 );
	}

	/**
	 * Get related classifieds.
	 *
	 * Adapted from WPAdverts related_ads_tpl_single_bottom() query logic.
	 * Matches by domain taxonomy, intent, and location.
	 *
	 * @param int $post_id Current post ID.
	 * @param int $count   Number of related ads.
	 * @return array
	 */
	public function get_related( int $post_id, int $count = self::DEFAULT_COUNT ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		// Get terms for matching.
		$domain_terms = wp_get_post_terms( $post_id, APOLLO_TAX_CLASSIFIED_DOMAIN, array( 'fields' => 'ids' ) );
		$intent_terms = wp_get_post_terms( $post_id, APOLLO_TAX_CLASSIFIED_INTENT, array( 'fields' => 'ids' ) );

		$tax_query = array( 'relation' => 'OR' );

		if ( ! empty( $domain_terms ) && ! is_wp_error( $domain_terms ) ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_TAX_CLASSIFIED_DOMAIN,
				'field'    => 'term_id',
				'terms'    => $domain_terms,
			);
		}

		if ( ! empty( $intent_terms ) && ! is_wp_error( $intent_terms ) ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_TAX_CLASSIFIED_INTENT,
				'field'    => 'term_id',
				'terms'    => $intent_terms,
			);
		}

		$args = array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'rand',
			'fields'         => 'ids',
		);

		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query;
		}

		$query   = new \WP_Query( $args );
		$results = array();

		foreach ( $query->posts as $related_id ) {
			$results[] = array(
				'id'       => $related_id,
				'title'    => get_the_title( $related_id ),
				'link'     => get_permalink( $related_id ),
				'image'    => function_exists( 'apollo_adverts_get_main_image' )
					? apollo_adverts_get_main_image( $related_id, 'classified-thumb' )
					: get_the_post_thumbnail_url( $related_id, 'medium' ),
				'price'    => function_exists( 'apollo_adverts_get_the_price' )
					? apollo_adverts_get_the_price( $related_id )
					: '',
				'location' => get_post_meta( $related_id, '_classified_loc', true ),
			);
		}

		return $results;
	}

	/**
	 * Default HTML render for related ads.
	 *
	 * @param array $related Array of related ad data.
	 */
	private function render_default( array $related ): void {
		echo '<div class="apollo-related-ads">';
		echo '<h3 class="apollo-related-title">' . esc_html__( 'Anúncios Relacionados', 'apollo-adverts' ) . '</h3>';
		echo '<div class="apollo-related-grid">';

		foreach ( $related as $item ) {
			echo '<a href="' . esc_url( $item['link'] ) . '" class="apollo-related-card">';
			if ( ! empty( $item['image'] ) ) {
				echo '<img src="' . esc_url( $item['image'] ) . '" alt="' . esc_attr( $item['title'] ) . '" loading="lazy" />';
			}
			echo '<div class="apollo-related-info">';
			echo '<h4>' . esc_html( $item['title'] ) . '</h4>';
			if ( ! empty( $item['price'] ) ) {
				echo '<span class="apollo-price">' . esc_html( $item['price'] ) . '</span>';
			}
			if ( ! empty( $item['location'] ) ) {
				echo '<span class="apollo-location"><i class="ri-map-pin-line"></i> ' . esc_html( $item['location'] ) . '</span>';
			}
			echo '</div>';
			echo '</a>';
		}

		echo '</div>';
		echo '</div>';
	}
}
