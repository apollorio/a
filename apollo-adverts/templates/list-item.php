<?php

/**
 * Template: Resale Ticket Card
 *
 * Rendered inside list.php for each classified.
 * Ticket-shaped card with perforation, barcode, and chat CTA.
 * Override in theme: theme/apollo-adverts/list-item.php
 *
 * Available variables:
 *   $post_id — int, the classified post ID
 *
 * @package Apollo\Adverts
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post           = get_post( $post_id );
$price          = apollo_adverts_get_the_price( $post_id );
$original_price = get_post_meta( $post_id, '_classified_original_price', true );
$location       = get_post_meta( $post_id, '_classified_loc', true );
$image_url      = apollo_adverts_get_main_image( $post_id, 'classified-medium' );
$featured       = apollo_adverts_is_featured( $post_id );
$intent         = apollo_adverts_get_intent_label( $post_id );

// Seller info — identity (avatar/name/@handle) is member-only. Never built
// or printed for guests, not merely hidden, per site-wide privacy contract.
$is_logged_in_viewer = is_user_logged_in();
$author        = get_userdata( $post->post_author );
$initials      = '';
$seller_name   = '';
$seller_handle = '';
if ( $is_logged_in_viewer && $author ) {
	$seller_name   = $author->display_name;
	$seller_handle = '@' . $author->user_login;
	$parts         = explode( ' ', $seller_name );
	$initials      = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) );
	if ( count( $parts ) > 1 ) {
		$initials .= mb_strtoupper( mb_substr( end( $parts ), 0, 1 ) );
	}
}

// Date — compact Apollo standard (tempo-v icon + number + unit)
$date_dt  = get_the_time( 'Y-m-d H:i:s', $post_id );
$bar_seed = (int) $post_id;

$classes = array( 'resale-ticket' );
if ( $featured ) {
	$classes[] = 'is-featured';
}
?>

<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-id="<?php echo esc_attr( (string) $post_id ); ?>">

	<!-- ▲ TOPO DO INGRESSO -->
	<div class="resale-ticket__top">

		<div class="resale-ticket__badge">
			⟁ <?php echo esc_html( $intent ? $intent : __( 'Revenda · Resale', 'apollo-adverts' ) ); ?>
		</div>

		<?php if ( $image_url ) : ?>
			<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
				<img class="resale-ticket__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" loading="lazy" />
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="resale-ticket__image" style="display:flex;align-items:center;justify-content:center;background:var(--av2-surface,#f4f4f5);height:140px;">
				<i class="ri-ticket-line" style="font-size:32px;color:var(--av2-border,#e4e4e7);"></i>
			</a>
		<?php endif; ?>

		<div class="resale-ticket__body">

			<!-- Vendedor — avatar/nome/@handle são member-only, nunca renderizados
			     para visitantes (não é CSS escondido, não existe no HTML). -->
			<?php if ( $is_logged_in_viewer && $author ) : ?>
				<div class="resale-ticket__seller">
					<div class="resale-ticket__avatar">
						<?php
						$avatar_url = get_avatar_url( $author->ID, array( 'size' => 64 ) );
						if ( $avatar_url ) :
							?>
							<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $seller_name ); ?>" />
						<?php else : ?>
							<?php echo esc_html( $initials ); ?>
						<?php endif; ?>
					</div>
					<div>
						<div class="resale-ticket__seller-name"><?php echo esc_html( $seller_name ); ?></div>
						<div class="resale-ticket__seller-handle"><?php echo esc_html( $seller_handle ); ?></div>
					</div>
				</div>
			<?php elseif ( $author ) : ?>
				<div class="resale-ticket__seller resale-ticket__seller--locked">
					<div class="resale-ticket__avatar resale-ticket__avatar--locked"><i class="ri-lock-2-line"></i></div>
					<div class="resale-ticket__seller-locked-note"><?php esc_html_e( 'Vendedor visível para membros', 'apollo-adverts' ); ?></div>
				</div>
			<?php endif; ?>

			<!-- Título -->
			<h3 class="resale-ticket__title">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
					<?php echo esc_html( get_the_title( $post_id ) ); ?>
				</a>
			</h3>

			<!-- Meta -->
			<div class="resale-ticket__meta">
				<span><?php echo function_exists( 'apollo_time_ago_html' ) ? wp_kses_post( apollo_time_ago_html( $date_dt ) ) : esc_html( apollo_time_ago( $date_dt ) ); ?></span>
				<?php if ( $location ) : ?>
					<span><i class="ri-map-pin-line"></i><?php echo esc_html( $location ); ?></span>
				<?php endif; ?>
			</div>

			<!-- Preços -->
			<div class="resale-ticket__prices">
				<div>
					<?php if ( $price ) : ?>
						<div class="resale-ticket__price"><?php echo esc_html( $price ); ?></div>
						<div class="resale-ticket__price-label"><?php esc_html_e( 'Preço pedido', 'apollo-adverts' ); ?></div>
					<?php else : ?>
						<div class="resale-ticket__price">—</div>
						<div class="resale-ticket__price-label"><?php esc_html_e( 'A combinar', 'apollo-adverts' ); ?></div>
					<?php endif; ?>
				</div>
				<?php if ( $original_price ) : ?>
					<div style="text-align:right;">
						<div class="resale-ticket__original"><?php echo esc_html( apollo_adverts_format_price( (float) $original_price ) ); ?></div>
						<div class="resale-ticket__price-label"><?php esc_html_e( 'Valor original', 'apollo-adverts' ); ?></div>
					</div>
				<?php endif; ?>
			</div>

			<!-- Ações rápidas -->
			<div class="resale-ticket__actions">
				<?php echo apollo_adverts_fav_button( $post_id ); ?>
			</div>
		</div>
	</div>

	<!-- ✂ PERFURAÇÃO -->
	<div class="resale-ticket__rip">
		<div class="resale-ticket__rip-left"></div>
		<div class="resale-ticket__rip-right"></div>
	</div>

	<!-- ▼ RODAPÉ DO INGRESSO -->
	<div class="resale-ticket__bottom">
		<div class="resale-ticket__barcode">
			<?php
			// Gera barras pseudo-aleatórias baseadas no ID
			$widths = array( 2, 1, 3, 1, 2, 1, 1, 3, 2, 1, 2, 1, 3, 1, 1, 2 );
			foreach ( $widths as $i => $w ) :
				$seed_w = ( ( $bar_seed * ( $i + 1 ) ) % 3 ) + 1;
				?>
				<span style="width:<?php echo esc_attr( (string) $seed_w ); ?>px;"></span>
			<?php endforeach; ?>
		</div>
		<?php if ( $is_logged_in_viewer ) : ?>
			<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="resale-ticket__chat-btn">
				<i class="ri-chat-3-line"></i> <?php esc_html_e( 'CHAT', 'apollo-adverts' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( home_url( '/acesso?redirect=' . rawurlencode( get_permalink( $post_id ) ) ) ); ?>" class="resale-ticket__chat-btn is-locked">
				<i class="ri-lock-2-line"></i> <?php esc_html_e( 'CHAT', 'apollo-adverts' ); ?>
			</a>
		<?php endif; ?>
	</div>

</article>