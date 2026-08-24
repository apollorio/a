<?php

/**
 * Dashboard Panel — Events (Meus Eventos V2)
 *
 * stat-strip (4 metrics) + ev-grid (event cards) + "DJs que você segue" list.
 *
 * Variables expected: $user_id, $display_name
 *
 * @package Apollo\Dashboard
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Load user's events stats ──
global $wpdb;

$events_count = 0;
$next_event   = null;
$my_events    = array();
$past_events  = 0;
$followed_djs = array();

// Count user events
if ( post_type_exists( 'event' ) ) {
	$args         = array(
		'post_type'      => 'event',
		'post_status'    => 'publish',
		'author'         => $user_id,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);
	$events_count = count( get_posts( $args ) );

	// Upcoming events (for grid)
	$my_events = get_posts(
		array(
			'post_type'      => 'event',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		)
	);

	// Next event
	if ( ! empty( $my_events ) ) {
		$next_event = $my_events[0];
	}

	// Past events count
	$past_events = count(
		get_posts(
			array(
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_event_start_date',
						'value'   => current_time( 'Y-m-d' ),
						'compare' => '<',
						'type'    => 'DATE',
					),
				),
			)
		)
	);
}

// ── Followed DJs ──
$follows_table = $wpdb->prefix . 'apollo_connections';
$has_follows   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $follows_table ) ) === $follows_table;

if ( $has_follows ) {
	$followed_djs = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.ID, u.display_name, u.user_login,
                (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = '_apollo_avatar_url' LIMIT 1) as dj_avatar,
                (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = '_apollo_genre' LIMIT 1) as genre
         FROM {$follows_table} f
         INNER JOIN {$wpdb->users} u ON u.ID = f.followed_id
         WHERE f.follower_id = %d
         ORDER BY f.created_at DESC LIMIT 5",
			$user_id
		),
		ARRAY_A
	);
}

// Next event name
$next_event_name = '--';
if ( $next_event ) {
	$next_date       = get_post_meta( $next_event->ID, '_event_start_date', true );
	$next_event_name = $next_date ? date_i18n( 'd M', strtotime( $next_date ) ) : $next_event->post_title;
}
?>

<div class="tab-panel" id="panel-events">
	<div class="panel-inner">

		<h1 class="d-title">Eventos</h1>

		<!-- ── Stat Strip ── -->
		<div class="stat-strip">
			<div class="stat-item"><span class="stat-val"><?php echo esc_html( count( $my_events ) ); ?></span><span class="stat-label">Próximos</span></div>
			<div class="stat-item"><span class="stat-val"><?php echo esc_html( $past_events ); ?></span><span class="stat-label">Já foi</span></div>
			<div class="stat-item"><span class="stat-val"><?php echo esc_html( count( $followed_djs ) ); ?></span><span class="stat-label">DJs seguidos</span></div>
			<div class="stat-item"><span class="stat-val"><?php echo esc_html( $next_event_name ); ?></span><span class="stat-label">Próximo</span></div>
		</div>

		<!-- ── Events Grid ── -->
		<h2 class="d-subtitle">Próximos eventos</h2>
		<div class="ev-grid">
			<?php if ( ! empty( $my_events ) ) : ?>
				<?php
				foreach ( $my_events as $ev ) :
					$ev_date  = get_post_meta( $ev->ID, '_event_start_date', true );
					$ev_loc_id = get_post_meta( $ev->ID, '_event_loc_id', true );
					$ev_loc    = $ev_loc_id ? get_the_title( (int) $ev_loc_id ) : '';
					$ev_terms  = get_the_terms( $ev->ID, 'event_category' );
					$ev_cat    = ( $ev_terms && ! is_wp_error( $ev_terms ) ) ? $ev_terms[0]->name : '';
					$ev_thumb = get_the_post_thumbnail_url( $ev->ID, 'medium' ) ?: 'https://images.unsplash.com/photo-1574391884720-bbc3740c59d1?w=400&q=75';
					$ev_fmt   = $ev_date ? date_i18n( 'D, d M · H:i', strtotime( $ev_date ) ) : '';
					?>
					<a href="<?php echo esc_url( get_permalink( $ev->ID ) ); ?>" class="ev-card">
						<div class="ev-card-thumb" style="background-image:url('<?php echo esc_url( $ev_thumb ); ?>')"></div>
						<div class="ev-card-body">
							<div class="ev-card-name"><?php echo esc_html( $ev->post_title ); ?></div>
							<?php
							if ( $ev_fmt ) :
								?>
								<div class="ev-card-meta"><?php echo esc_html( $ev_fmt ); ?></div><?php endif; ?>
							<?php
							if ( $ev_loc ) :
								?>
								<div class="ev-card-meta"><i class="ri-map-pin-line"></i> <?php echo esc_html( $ev_loc ); ?></div><?php endif; ?>
							<?php
							if ( $ev_cat ) :
								?>
								<div class="ev-card-tag"><?php echo esc_html( $ev_cat ); ?></div><?php endif; ?>
						</div>
					</a>
				<?php endforeach; ?>
			<?php else : ?>
				<!-- Sample for fresh install -->
				<a class="ev-card">
					<div class="ev-card-thumb" style="background-image:url('https://images.unsplash.com/photo-1574391884720-bbc3740c59d1?w=400&q=75')"></div>
					<div class="ev-card-body">
						<div class="ev-card-name">Sunset Theory Vol.04</div>
						<div class="ev-card-meta">Sáb, 24 Fev · 23:00</div>
						<div class="ev-card-meta"><i class="ri-map-pin-line"></i> Fabrika</div>
						<div class="ev-card-tag">Techno</div>
					</div>
				</a>
				<a class="ev-card">
					<div class="ev-card-thumb" style="background-image:url('https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=400&q=75')"></div>
					<div class="ev-card-body">
						<div class="ev-card-name">Dismantle Mainstage</div>
						<div class="ev-card-meta">Sáb, 30 Mar · 22:00</div>
						<div class="ev-card-meta"><i class="ri-map-pin-line"></i> Fosfobox</div>
						<div class="ev-card-tag">House</div>
					</div>
				</a>
				<a class="ev-card">
					<div class="ev-card-thumb" style="background-image:url('https://images.unsplash.com/photo-1504680177321-2e6a879aac86?w=400&q=75')"></div>
					<div class="ev-card-body">
						<div class="ev-card-name">Festa Rara #47</div>
						<div class="ev-card-meta">Sex, 02 Mar · 23:30</div>
						<div class="ev-card-tag">Underground</div>
					</div>
				</a>
				<a class="ev-card">
					<div class="ev-card-thumb" style="background-image:url('https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400&q=75')"></div>
					<div class="ev-card-body">
						<div class="ev-card-name">Bass Culture</div>
						<div class="ev-card-meta">Dom, 10 Mar · 16:00</div>
						<div class="ev-card-meta"><i class="ri-map-pin-line"></i> Lapa</div>
						<div class="ev-card-tag">D&B</div>
					</div>
				</a>
			<?php endif; ?>
		</div>

		<!-- ── DJs que você segue ── -->
		<h2 class="d-subtitle" style="margin-top:32px;">DJs que você segue</h2>

		<?php if ( ! empty( $followed_djs ) ) : ?>
			<?php
			foreach ( $followed_djs as $dj ) :
				$dj_avatar = ! empty( $dj['dj_avatar'] )
					? $dj['dj_avatar']
					: get_avatar_url( (int) $dj['ID'], array( 'size' => 80 ) );
				$dj_genre  = ! empty( $dj['genre'] ) ? $dj['genre'] : 'DJ Apollo';
				?>
				<div class="li-item">
					<img src="<?php echo esc_url( $dj_avatar ); ?>" class="li-av" style="object-fit:cover;" alt="<?php echo esc_attr( $dj['display_name'] ); ?>">
					<div class="li-info">
						<div class="li-name"><?php echo esc_html( $dj['display_name'] ); ?></div>
						<div class="li-meta"><?php echo esc_html( $dj_genre ); ?></div>
					</div>
					<a href="<?php echo esc_url( home_url( '/id/' . $dj['user_login'] ) ); ?>" class="li-btn">Perfil</a>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="li-item">
				<div class="li-av" style="background:#8b5cf6;font-size:11px;">DJ</div>
				<div class="li-info">
					<div class="li-name">Leo Janeiro</div>
					<div class="li-meta">Techno · Industrial</div>
				</div>
				<a class="li-btn">Perfil</a>
			</div>
			<div class="li-item">
				<div class="li-av" style="background:#3b82f6;font-size:11px;">DJ</div>
				<div class="li-info">
					<div class="li-name">kaka.tech</div>
					<div class="li-meta">Minimal · Microhouse</div>
				</div>
				<a class="li-btn">Perfil</a>
			</div>
			<div class="li-item">
				<div class="li-av" style="background:#ec4899;font-size:11px;">DJ</div>
				<div class="li-info">
					<div class="li-name">Marta Supernova</div>
					<div class="li-meta">Techno · Acid</div>
				</div>
				<a class="li-btn">Perfil</a>
			</div>
		<?php endif; ?>

	</div>
</div>
