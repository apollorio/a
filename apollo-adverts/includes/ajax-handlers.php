<?php
/**
 * AJAX Handlers
 *
 * Non-gallery AJAX operations (form operations, admin actions).
 * Gallery AJAX handlers are in gallery.php.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX: Quick status change (admin)
 * Adapted from WPAdverts admin AJAX pattern
 */
function apollo_adverts_ajax_change_status(): void {
	check_ajax_referer( 'apollo_adverts_admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-adverts' ) ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$status  = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';

	if ( ! $post_id || ! $status ) {
		wp_send_json_error( array( 'message' => __( 'Dados inválidos.', 'apollo-adverts' ) ) );
	}

	$allowed = array( 'publish', 'pending', 'draft', 'expired' );
	if ( ! in_array( $status, $allowed, true ) ) {
		wp_send_json_error( array( 'message' => __( 'Status inválido.', 'apollo-adverts' ) ) );
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
		wp_send_json_error( array( 'message' => __( 'Anúncio não encontrado.', 'apollo-adverts' ) ) );
	}

	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => $status,
		)
	);

	// If publishing, set expiration
	if ( $status === 'publish' && ! get_post_meta( $post_id, '_classified_expires_at', true ) ) {
		apollo_adverts_set_expiration( $post_id );
	}

	wp_send_json_success(
		array(
			'post_id' => $post_id,
			'status'  => $status,
		)
	);
}
add_action( 'wp_ajax_apollo_adverts_change_status', 'apollo_adverts_ajax_change_status' );

/**
 * AJAX: Toggle featured (admin)
 */
function apollo_adverts_ajax_toggle_featured(): void {
	check_ajax_referer( 'apollo_adverts_admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-adverts' ) ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	if ( ! $post_id ) {
		wp_send_json_error( array( 'message' => __( 'ID inválido.', 'apollo-adverts' ) ) );
	}

	$current = get_post_meta( $post_id, '_classified_featured', true );
	$new_val = $current === '1' ? '' : '1';
	update_post_meta( $post_id, '_classified_featured', $new_val );

	do_action( 'apollo/classifieds/featured', $post_id, $new_val === '1' );

	wp_send_json_success(
		array(
			'post_id'  => $post_id,
			'featured' => $new_val === '1',
		)
	);
}
add_action( 'wp_ajax_apollo_adverts_toggle_featured', 'apollo_adverts_ajax_toggle_featured' );

/**
 * AJAX: Get dashboard stats (admin)
 */
function apollo_adverts_ajax_dashboard_stats(): void {
	check_ajax_referer( 'apollo_adverts_admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-adverts' ) ) );
	}

	$counts = wp_count_posts( APOLLO_CPT_CLASSIFIED );

	// Recent classifieds
	$recent = get_posts(
		array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => array( 'publish', 'pending', 'expired' ),
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$recent_data = array();
	foreach ( $recent as $post ) {
		$recent_data[] = array(
			'id'     => $post->ID,
			'title'  => $post->post_title,
			'status' => $post->post_status,
			'date'   => $post->post_date,
			'author' => get_the_author_meta( 'display_name', $post->post_author ),
			'price'  => apollo_adverts_get_the_price( $post->ID ),
		);
	}

	wp_send_json_success(
		array(
			'counts' => array(
				'publish' => $counts->publish ?? 0,
				'pending' => $counts->pending ?? 0,
				'expired' => $counts->expired ?? 0,
				'draft'   => $counts->draft ?? 0,
				'trash'   => $counts->trash ?? 0,
			),
			'recent' => $recent_data,
		)
	);
}
add_action( 'wp_ajax_apollo_adverts_dashboard_stats', 'apollo_adverts_ajax_dashboard_stats' );
