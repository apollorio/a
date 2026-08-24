<?php

/**
 * Cron Tasks
 *
 * Scheduled tasks: expiration checks, expiring-soon notifications, temp cleanup.
 * Adapted from WPAdverts includes/events.php cron patterns.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule cron events on init
 * Adapted from WPAdverts wp_schedule_event pattern
 */
function apollo_adverts_schedule_cron(): void {

	// Check expired ads (daily)
	if ( ! wp_next_scheduled( 'apollo_classifieds_check_expired' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'apollo_classifieds_check_expired' );
	}

	// Notify expiring soon (daily)
	if ( ! wp_next_scheduled( 'apollo_classifieds_notify_expiring' ) ) {
		wp_schedule_event( time(), 'daily', 'apollo_classifieds_notify_expiring' );
	}

	// Garbage collect temp posts (daily)
	if ( ! wp_next_scheduled( 'apollo_classifieds_gc_temp' ) ) {
		wp_schedule_event( time(), 'daily', 'apollo_classifieds_gc_temp' );
	}
}
add_action( 'init', 'apollo_adverts_schedule_cron', 20 );

/**
 * Cron: Check and expire ads past their expiration date
 * Adapted from WPAdverts adverts_event_expire
 */
function apollo_adverts_cron_check_expired(): void {
	$today = current_time( 'Y-m-d' );

	$expired_posts = get_posts(
		array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'meta_query'     => array(
				array(
					'key'     => '_classified_expires_at',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
			),
			'fields'         => 'ids',
		)
	);

	foreach ( $expired_posts as $post_id ) {
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'expired',
			)
		);

		/**
		 * Fires when a classified expires
		 * Registry hook: apollo/classifieds/expired
		 */
		do_action( 'apollo/classifieds/expired', $post_id );

		apollo_adverts_log(
			'classified_expired',
			array(
				'post_id' => $post_id,
			)
		);
	}
}
add_action( 'apollo_classifieds_check_expired', 'apollo_adverts_cron_check_expired' );

/**
 * Cron: Notify users about ads expiring soon
 * Adapted from WPAdverts notification pattern
 */
function apollo_adverts_cron_notify_expiring(): void {
	$days_before = apollo_adverts_config( 'expiring_days_before', 3 );
	$target_date = gmdate( 'Y-m-d', strtotime( '+' . $days_before . ' days' ) );

	$expiring_posts = get_posts(
		array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'meta_query'     => array(
				array(
					'key'     => '_classified_expires_at',
					'value'   => $target_date,
					'compare' => '=',
					'type'    => 'DATE',
				),
			),
			'fields'         => 'ids',
		)
	);

	foreach ( $expiring_posts as $post_id ) {
		// Check if already notified
		$notified = get_post_meta( $post_id, '_classified_expiring_notified', true );
		if ( $notified ) {
			continue;
		}

		/**
		 * Fires when a classified is about to expire
		 */
		do_action( 'apollo/classifieds/expiring_soon', $post_id, $days_before );

		// Use apollo-notif if available
		if ( function_exists( 'apollo_adverts_notif_expiring_soon' ) ) {
			apollo_adverts_notif_expiring_soon( $post_id, $days_before );
		}

		// Send email notification
		apollo_adverts_send_expiring_email( $post_id, $days_before );

		// Mark as notified
		update_post_meta( $post_id, '_classified_expiring_notified', '1' );
	}
}
add_action( 'apollo_classifieds_notify_expiring', 'apollo_adverts_cron_notify_expiring' );

/**
 * Send expiring-soon email
 * Adapted from WPAdverts email notification pattern
 */
function apollo_adverts_send_expiring_email( int $post_id, int $days_left ): void {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return;
	}

	$author = get_userdata( $post->post_author );
	if ( ! $author || ! $author->user_email ) {
		return;
	}

	$subject = sprintf(
		/* translators: %s: ad title */
		__( 'Seu anúncio "%s" está prestes a expirar', 'apollo-adverts' ),
		$post->post_title
	);

	$message = sprintf(
		/* translators: 1: user name, 2: ad title, 3: days remaining, 4: ad URL */
		__( "Olá %1\$s,\n\nSeu anúncio \"%2\$s\" expira em %3\$d dia(s).\n\nRenove-o para mantê-lo ativo: %4\$s\n\nAtenciosamente,\n%5\$s", 'apollo-adverts' ),
		$author->display_name,
		$post->post_title,
		$days_left,
		get_permalink( $post_id ),
		get_bloginfo( 'name' )
	);

	// Use apollo-email if available, otherwise wp_mail
	if ( function_exists( 'apollo_send_email' ) ) {
		apollo_send_email(
			$author->user_email,
			$subject,
			'notification',
			array(
				'user_name'   => $author->display_name,
				'title'       => $subject,
				'message'     => $message,
				'action_url'  => get_permalink( $post_id ),
				'action_text' => __( 'Renovar Anúncio', 'apollo-adverts' ),
				'site_name'   => get_bloginfo( 'name' ),
			)
		);
	} else {
		wp_mail( $author->user_email, $subject, $message );
	}
}

/**
 * Cron: Garbage collect temporary classified posts
 * Adapted from WPAdverts adverts_event_tmp_delete
 */
function apollo_adverts_cron_gc_temp(): void {
	$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

	$tmp_posts = get_posts(
		array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'classified_tmp',
			'posts_per_page' => 50,
			'date_query'     => array(
				array(
					'before' => $cutoff,
				),
			),
			'fields'         => 'ids',
		)
	);

	foreach ( $tmp_posts as $post_id ) {
		// Delete attachments
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_parent'    => $post_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $attachments as $att_id ) {
			wp_delete_attachment( $att_id, true );
		}

		wp_delete_post( $post_id, true );
	}
}
add_action( 'apollo_classifieds_gc_temp', 'apollo_adverts_cron_gc_temp' );

/**
 * Clear the "expiring notified" flag when a classified is renewed
 */
function apollo_adverts_clear_expiring_flag( int $post_id ): void {
	delete_post_meta( $post_id, '_classified_expiring_notified' );
}
add_action( 'apollo/classifieds/renewed', 'apollo_adverts_clear_expiring_flag' );
