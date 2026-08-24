<?php

/**
 * Dashboard Panel — Favs (Favoritos Grid V2)
 *
 * Three sections: Eventos salvos, Revendas salvas, Acomodações salvas.
 * Uses fav-card grid with fav-tag color coding.
 *
 * Variables expected: $user_id
 *
 * @package Apollo\Dashboard
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Load favorites from Apollo favs system ──
$fav_events = array();
$fav_resale = array();
$fav_accom  = array();

global $wpdb;
$favs_table = $wpdb->prefix . 'apollo_favs';
$has_favs   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $favs_table ) ) === $favs_table;

if ( $has_favs ) {
	$all_favs = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT object_id, object_type, created_at FROM {$favs_table} WHERE user_id = %d ORDER BY created_at DESC",
			$user_id
		),
		ARRAY_A
	);

	foreach ( $all_favs as $fav ) {
		switch ( $fav['object_type'] ) {
			case 'event':
				$fav_events[] = $fav;
				break;
			case 'resale':
				$fav_resale[] = $fav;
				break;
			case 'accommodation':
			case 'accom':
				$fav_accom[] = $fav;
				break;
		}
	}
}
?>

<div class="tab-panel" id="panel-favs">
	<div class="panel-inner">

		<h1 class="d-title">Favoritos</h1>

		<!-- ── Eventos Salvos ── -->
		<h2 class="d-subtitle">Eventos salvos</h2>
		<div class="favs-grid" id="favEventsGrid">
			<?php if ( ! empty( $fav_events ) ) : ?>
				<?php
				foreach ( $fav_events as $fev ) :
					$ev_post = get_post( (int) $fev['object_id'] );
					if ( ! $ev_post ) {
						continue;
					}
					$ev_date  = get_post_meta( $ev_post->ID, '_event_start_date', true );
					$ev_loc_id = get_post_meta( $ev_post->ID, '_event_loc_id', true );
					$ev_loc    = $ev_loc_id ? get_the_title( (int) $ev_loc_id ) : '';
					$ev_thumb = get_the_post_thumbnail_url( $ev_post->ID, 'medium' ) ?: 'https://images.unsplash.com/photo-1574391884720-bbc3740c59d1?w=400&q=75';
					?>
					<div class="fav-card">
						<span class="fav-tag event">Evento</span>
						<div class="fav-thumb" style="background-image:url('<?php echo esc_url( $ev_thumb ); ?>')"></div>
						<div class="fav-body">
							<div class="fav-name"><?php echo esc_html( $ev_post->post_title ); ?></div>
							<?php
							if ( $ev_date ) :
								?>
								<div class="fav-meta"><?php echo esc_html( date_i18n( 'd M · H:i', strtotime( $ev_date ) ) ); ?></div><?php endif; ?>
							<?php
							if ( $ev_loc ) :
								?>
								<div class="fav-meta"><i class="ri-map-pin-line"></i> <?php echo esc_html( $ev_loc ); ?></div><?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<!-- Sample data for fresh install -->
				<div class="fav-card">
					<span class="fav-tag event">Evento</span>
					<div class="fav-thumb" style="background-image:url('https://images.unsplash.com/photo-1574391884720-bbc3740c59d1?w=400&q=75')"></div>
					<div class="fav-body">
						<div class="fav-name">Sunset Theory Vol.04</div>
						<div class="fav-meta">24 Fev · 23:00 · Fabrika</div>
					</div>
				</div>
				<div class="fav-card">
					<span class="fav-tag event">Evento</span>
					<div class="fav-thumb" style="background-image:url('https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=400&q=75')"></div>
					<div class="fav-body">
						<div class="fav-name">Dismantle Mainstage 2025</div>
						<div class="fav-meta">30 Mar · 22:00 · Fosfobox</div>
					</div>
				</div>
				<div class="fav-card">
					<span class="fav-tag event">Evento</span>
					<div class="fav-thumb" style="background-image:url('https://images.unsplash.com/photo-1504680177321-2e6a879aac86?w=400&q=75')"></div>
					<div class="fav-body">
						<div class="fav-name">Festa Rara #47</div>
						<div class="fav-meta">02 Mar · 23:30</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- ── Revendas Salvas ── -->
		<h2 class="d-subtitle" style="margin-top:32px;">Revendas salvas</h2>
		<div class="favs-grid" id="favResaleGrid">
			<?php if ( ! empty( $fav_resale ) ) : ?>
				<?php
				foreach ( $fav_resale as $fr ) :
					$resale_data = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}apollo_resale WHERE id = %d",
							(int) $fr['object_id']
						),
						ARRAY_A
					);
					if ( ! $resale_data ) {
						continue;
					}
					?>
					<div class="fav-card">
						<span class="fav-tag resale">Revenda</span>
						<div class="fav-thumb" style="background-image:url('<?php echo esc_url( $resale_data['image_url'] ?? 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=75' ); ?>')"></div>
						<div class="fav-body">
							<div class="fav-name"><?php echo esc_html( $resale_data['title'] ?? 'Ingresso' ); ?></div>
							<div class="fav-meta"><?php echo esc_html( 'R$ ' . ( $resale_data['price'] ?? '0' ) ); ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="fav-card">
					<span class="fav-tag resale">Revenda</span>
					<div class="fav-thumb" style="background-image:url('https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=75')"></div>
					<div class="fav-body">
						<div class="fav-name">Ingresso Sunset Theory</div>
						<div class="fav-meta">R$80 · Ingresso inteira</div>
					</div>
				</div>
				<div class="fav-card">
					<span class="fav-tag resale">Revenda</span>
					<div class="fav-thumb" style="background-image:url('https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400&q=75')"></div>
					<div class="fav-body">
						<div class="fav-name">2x Dismantle Pista</div>
						<div class="fav-meta">R$60 cada · Meia</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- ── Acomodações Salvas ── -->
		<h2 class="d-subtitle" style="margin-top:32px;">Acomodações salvas</h2>
		<div class="favs-grid" id="favAccomGrid">
			<?php if ( ! empty( $fav_accom ) ) : ?>
				<?php
				foreach ( $fav_accom as $fa ) :
					$accom_data = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}apollo_accommodations WHERE id = %d",
							(int) $fa['object_id']
						),
						ARRAY_A
					);
					if ( ! $accom_data ) {
						continue;
					}
					?>
					<div class="fav-card">
						<span class="fav-tag accom">Acomodação</span>
						<div class="fav-thumb" style="background-image:url('<?php echo esc_url( $accom_data['image_url'] ?? 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&q=75' ); ?>')"></div>
						<div class="fav-body">
							<div class="fav-name"><?php echo esc_html( $accom_data['title'] ?? 'Hospedagem' ); ?></div>
							<div class="fav-meta"><?php echo esc_html( 'R$ ' . ( $accom_data['price_per_night'] ?? '0' ) . '/noite' ); ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="fav-card">
					<span class="fav-tag accom">Acomodação</span>
					<div class="fav-thumb" style="background-image:url('https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&q=75')"></div>
					<div class="fav-body">
						<div class="fav-name">Quarto em Copacabana</div>
						<div class="fav-meta">R$120/noite · Vista mar</div>
					</div>
				</div>
				<div class="fav-card">
					<span class="fav-tag accom">Acomodação</span>
					<div class="fav-thumb" style="background-image:url('https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=400&q=75')"></div>
					<div class="fav-body">
						<div class="fav-name">Sofá em Botafogo</div>
						<div class="fav-meta">R$70/noite · Centro</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

	</div>
</div>
