<?php

/**
 * Template Part: Accommodation Card
 *
 * Primary CTA "Ver" opens this advert's own permalink. Hostel bookings stay
 * outbound. Peer stays go through the single page (+ safety gate on contact).
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = get_the_ID();
$permalink = (string) get_permalink( $post_id );
$mk_single_mode = ! empty( $mk_single_mode );

// ── Lock rule ──────────────────────────────────────────────────────────────
// A hostel is a public, worldwide-listed business: nothing about it is
// private, so its card stays open to everyone and its CTA goes to the
// hostel's own site instead of Apollo's peer-to-peer chat. Every other
// accommodation is somebody's home — host identity stays member-only, same as
// resale tickets. Guests therefore see the padlock on all non-hostel stays.
$is_hostel = function_exists( 'apollo_adverts_is_hostel' )
	? apollo_adverts_is_hostel( $post_id )
	: '1' === (string) get_post_meta( $post_id, '_classified_hostel', true );
$hostel_url = $is_hostel
	? (string) get_post_meta( $post_id, '_classified_hostel_url', true )
	: '';

$is_logged_in_viewer = is_user_logged_in();
$is_locked = ! $is_logged_in_viewer && ! $is_hostel;
$title     = get_the_title();
$location  = get_post_meta( $post_id, '_classified_loc', true );
$price     = get_post_meta( $post_id, '_classified_price', true );
$rating    = get_post_meta( $post_id, '_classified_rating', true ) ?: '4.5';
$badge     = get_post_meta( $post_id, '_classified_badge', true );
$image     = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600';

$mk_domains = function_exists( 'get_the_terms' ) ? get_the_terms( $post_id, defined( 'APOLLO_TAX_CLASSIFIED_DOMAIN' ) ? APOLLO_TAX_CLASSIFIED_DOMAIN : 'classified_domain' ) : array();
$mk_domains = is_wp_error( $mk_domains ) || empty( $mk_domains ) ? '' : implode( ' ', wp_list_pluck( $mk_domains, 'slug' ) );
?>

<div
	class="accom-card reveal-up"
	data-mk-domains="<?php echo esc_attr( $mk_domains ); ?>"
	data-classified-id="<?php echo esc_attr( (string) $post_id ); ?>"
	<?php if ( ! $mk_single_mode ) : ?>
		data-mk-permalink="<?php echo esc_url( $permalink ); ?>"
	<?php endif; ?>
>
	<div class="accom-img-wrap">
		<?php if ( $badge ) : ?>
			<span class="accom-badge"><?php echo esc_html( $badge ); ?></span>
		<?php endif; ?>
		<img src="<?php echo esc_url( $image ); ?>" class="accom-img" alt="<?php echo esc_attr( $title ); ?>">
	</div>
	<div class="accom-content">
		<div class="accom-header">
			<h3 class="accom-title"><?php echo esc_html( $title ); ?></h3>
			<div class="accom-rating">
				<i class="ri-star-fill"></i> <?php echo esc_html( $rating ); ?>
			</div>
		</div>
		<?php if ( $location ) : ?>
			<div class="accom-loc">
				<i class="ri-map-pin-line"></i> <?php echo esc_html( $location ); ?>
			</div>
		<?php endif; ?>
		<div class="accom-footer">
			<?php if ( $price ) : ?>
				<div class="accom-price">R$ <?php echo esc_html( number_format( (float) $price, 0, ',', '.' ) ); ?><span>/noite</span></div>
			<?php endif; ?>
			<?php if ( $mk_single_mode ) : ?>
				<?php /* CTA lives in #contato on the single. */ ?>
			<?php elseif ( $is_hostel && $hostel_url ) : ?>
				<a href="<?php echo esc_url( $hostel_url ); ?>" class="btn-accom btn-accom--hostel"
					target="_blank" rel="noopener noreferrer">
					<i class="ri-external-link-line" aria-hidden="true"></i> <?php esc_html_e( 'Reservar', 'apollo-adverts' ); ?>
				</a>
			<?php elseif ( $is_locked ) : ?>
				<a href="<?php echo esc_url( home_url( '/acesso?redirect=' . rawurlencode( $permalink ) ) ); ?>"
					class="btn-accom is-locked" aria-label="<?php esc_attr_e( 'Entre para ver esta hospedagem', 'apollo-adverts' ); ?>">
					<i class="ri-lock-2-line" aria-hidden="true"></i> Ver
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $permalink ); ?>" class="btn-accom">
					<?php esc_html_e( 'Ver', 'apollo-adverts' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
