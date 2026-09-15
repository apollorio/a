<?php
/**
 * Shortcode: [apollo_classifieds]
 *
 * Displays classified listings with search/filter.
 * Adapted from WPAdverts shortcode_adverts_list() in shortcodes.php.
 *
 * Attributes:
 *   limit   - posts per page (default APOLLO_ADVERTS_POSTS_PER_PAGE)
 *   domain  - filter by classified_domain slug
 *   intent  - filter by classified_intent slug
 *   orderby - WP_Query orderby (default 'date')
 *   order   - ASC|DESC (default 'DESC')
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [apollo_classifieds] shortcode handler
 * Adapted from WPAdverts shortcode_adverts_list()
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function apollo_adverts_shortcode_list( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'limit'    => APOLLO_ADVERTS_POSTS_PER_PAGE,
			'domain'   => '',
			'intent'   => '',
			'orderby'  => 'date',
			'order'    => 'DESC',
			'featured' => '',
		),
		$atts,
		'apollo_classifieds'
	);

	// Enqueue assets
	wp_enqueue_style( 'apollo-adverts' );
	wp_enqueue_script( 'apollo-adverts' );
	if ( function_exists( 'apollo_adverts_enqueue_rt_card' ) ) {
		apollo_adverts_enqueue_rt_card();
	}

	// Current page
	$paged = max( 1, get_query_var( 'paged', 1 ) );

	// Build WP_Query args — adapted from WPAdverts shortcode_adverts_list()
	$args = array(
		'post_type'      => APOLLO_CPT_CLASSIFIED,
		'post_status'    => 'publish',
		'posts_per_page' => absint( $atts['limit'] ),
		'paged'          => $paged,
		'orderby'        => sanitize_key( $atts['orderby'] ),
		'order'          => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
	);

	// Tax queries
	$tax_query = array();

	if ( ! empty( $atts['domain'] ) ) {
		$tax_query[] = array(
			'taxonomy' => APOLLO_TAX_CLASSIFIED_DOMAIN,
			'field'    => 'slug',
			'terms'    => sanitize_text_field( $atts['domain'] ),
		);
	}

	if ( ! empty( $atts['intent'] ) ) {
		$tax_query[] = array(
			'taxonomy' => APOLLO_TAX_CLASSIFIED_INTENT,
			'field'    => 'slug',
			'terms'    => sanitize_text_field( $atts['intent'] ),
		);
	}

	// Search filters from request
	$search_domain = apollo_adverts_request( APOLLO_TAX_CLASSIFIED_DOMAIN );
	if ( $search_domain && empty( $atts['domain'] ) ) {
		$tax_query[] = array(
			'taxonomy' => APOLLO_TAX_CLASSIFIED_DOMAIN,
			'field'    => 'slug',
			'terms'    => $search_domain,
		);
	}

	$search_intent = apollo_adverts_request( APOLLO_TAX_CLASSIFIED_INTENT );
	if ( $search_intent && empty( $atts['intent'] ) ) {
		$tax_query[] = array(
			'taxonomy' => APOLLO_TAX_CLASSIFIED_INTENT,
			'field'    => 'slug',
			'terms'    => $search_intent,
		);
	}

	if ( ! empty( $tax_query ) ) {
		$args['tax_query'] = $tax_query;
	}

	// Featured filter
	if ( $atts['featured'] === '1' ) {
		$args['meta_query'][] = array(
			'key'   => '_classified_featured',
			'value' => '1',
		);
	}

	// Text search
	$query_text = apollo_adverts_request( 'query' );
	if ( $query_text ) {
		$args['s'] = $query_text;
	}

	// Location filter
	$location = apollo_adverts_request( '_classified_loc' );
	if ( $location ) {
		$args['meta_query'][] = array(
			'key'     => '_classified_loc',
			'value'   => $location,
			'compare' => 'LIKE',
		);
	}

	// Price range filter
	$price_min = apollo_adverts_request( 'price_min' );
	$price_max = apollo_adverts_request( 'price_max' );
	if ( $price_min !== '' && is_numeric( $price_min ) ) {
		$args['meta_query'][] = array(
			'key'     => '_classified_price',
			'value'   => (float) $price_min,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	}
	if ( $price_max !== '' && is_numeric( $price_max ) ) {
		$args['meta_query'][] = array(
			'key'     => '_classified_price',
			'value'   => (float) $price_max,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}

	/**
	 * Filter the list query args
	 * Adapted from adverts_sh_query_args filter
	 */
	$args = apply_filters( 'apollo/classifieds/list_query_args', $args, $atts );

	$query = new \WP_Query( $args );

	// Load search form
	$search_form = \Apollo\Adverts\Form::load( 'search' );
	$search_form->bind(
		array(
			'query'                      => $query_text,
			APOLLO_TAX_CLASSIFIED_DOMAIN => $search_domain,
			APOLLO_TAX_CLASSIFIED_INTENT => $search_intent,
			'_classified_loc'            => $location,
			'price_min'                  => $price_min,
			'price_max'                  => $price_max,
		)
	);

	// Render — adapted from WPAdverts ob_start → template → ob_get_clean pattern
	ob_start();

	$template_data = array(
		'query'       => $query,
		'search_form' => $search_form,
		'atts'        => $atts,
		'paged'       => $paged,
	);

	apollo_adverts_load_template( 'list.php', $template_data );

	return ob_get_clean();
}
