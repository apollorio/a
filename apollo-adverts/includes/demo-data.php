<?php

/**
 * Sample Data Generator
 *
 * Run this once to populate demo classifieds.
 * Access via: /wp-admin/admin.php?page=apollo_classifieds_demo
 *
 * @package Apollo\Classifieds
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add admin menu
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=classified',
			'Generate Demo Data',
			'Demo Data',
			'manage_options',
			'apollo_classifieds_demo',
			'apollo_classifieds_generate_demo'
		);
	}
);

function apollo_classifieds_generate_demo() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized' );
	}

	echo '<div class="wrap"><h1>Apollo Classifieds — Demo Data Generator</h1>';

	if ( isset( $_POST['generate'] ) ) {
		check_admin_referer( 'apollo_demo_nonce' );

		$count = 0;

		// Create 6 ticket classifieds
		$tickets = array(
			array(
				'title'          => 'Sunset Theory — Pista Premium',
				'event_title'    => 'Sunset Theory',
				'event_date'     => '24 Fev • 23:00',
				'event_location' => 'Pista Premium',
				'price'          => 149,
				'image'          => 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=600',
			),
			array(
				'title'          => 'Industrial C. — Backstage',
				'event_title'    => 'Industrial C.',
				'event_date'     => '12 Mar • 22:00',
				'event_location' => 'Backstage',
				'price'          => 320,
				'image'          => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=600',
			),
			array(
				'title'          => 'Sunset Theory — Area VIP',
				'event_title'    => 'Sunset Theory',
				'event_date'     => '24 Fev • 23:00',
				'event_location' => 'Area VIP',
				'price'          => 250,
				'image'          => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600',
			),
		);

		foreach ( $tickets as $ticket ) {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $ticket['title'],
					'post_type'   => 'classified',
					'post_status' => 'publish',
					'post_author' => 1,
				)
			);

			if ( $post_id ) {
				update_post_meta( $post_id, '_classified_type', 'ticket' );
				update_post_meta( $post_id, '_event_title', $ticket['event_title'] );
				update_post_meta( $post_id, '_event_date', $ticket['event_date'] );
				update_post_meta( $post_id, '_classified_event_loc', $ticket['event_location'] );
				update_post_meta( $post_id, '_price', $ticket['price'] );

				// Set featured image from URL
				$image_id = media_sideload_image( $ticket['image'], $post_id, '', 'id' );
				if ( ! is_wp_error( $image_id ) ) {
					set_post_thumbnail( $post_id, $image_id );
				}

				++$count;
			}
		}

		// Create 4 accommodation classifieds
		$accommodations = array(
			array(
				'title'    => 'Selina Lapa',
				'location' => 'Lapa, Rio de Janeiro',
				'price'    => 280,
				'rating'   => 4.8,
				'badge'    => 'Superhost',
				'image'    => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600',
			),
			array(
				'title'    => 'Vila Santa Teresa',
				'location' => 'Santa Teresa, RJ',
				'price'    => 450,
				'rating'   => 4.9,
				'badge'    => '',
				'image'    => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600',
			),
			array(
				'title'    => 'Ibis Centro',
				'location' => 'Centro, Rio',
				'price'    => 180,
				'rating'   => 4.2,
				'badge'    => '',
				'image'    => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600',
			),
			array(
				'title'    => 'Loft Industrial',
				'location' => 'Gamboa, RJ',
				'price'    => 200,
				'rating'   => 5.0,
				'badge'    => 'Novo',
				'image'    => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=600',
			),
		);

		foreach ( $accommodations as $accom ) {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $accom['title'],
					'post_type'   => 'classified',
					'post_status' => 'publish',
					'post_author' => 1,
				)
			);

			if ( $post_id ) {
				update_post_meta( $post_id, '_classified_type', 'accommodation' );
				update_post_meta( $post_id, '_classified_loc', $accom['location'] );
				update_post_meta( $post_id, '_price', $accom['price'] );
				update_post_meta( $post_id, '_rating', $accom['rating'] );
				if ( $accom['badge'] ) {
					update_post_meta( $post_id, '_badge', $accom['badge'] );
				}

				$image_id = media_sideload_image( $accom['image'], $post_id, '', 'id' );
				if ( ! is_wp_error( $image_id ) ) {
					set_post_thumbnail( $post_id, $image_id );
				}

				++$count;
			}
		}

		echo '<div class="notice notice-success"><p><strong>Sucesso!</strong> ' . $count . ' classificados criados.</p></div>';
	}

	?>
	<form method="post">
		<?php wp_nonce_field( 'apollo_demo_nonce' ); ?>
		<p>This will create sample classifieds (3 tickets + 4 accommodations) for testing.</p>
		<p><button type="submit" name="generate" class="button button-primary">Generate Demo Classifieds</button></p>
	</form>
	</div>
	<?php
}
