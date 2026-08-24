<?php

/**
 * Template Part: Accommodation Card
 *
 * Registry-compliant meta keys with _classified_ prefix.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = get_the_ID();

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

// Host identity is member-only — never built/printed for guests, unless the
// listing is a hostel (in which case there is no personal identity involved).
$is_logged_in_viewer = is_user_logged_in();
$is_locked = ! $is_logged_in_viewer && ! $is_hostel;
$author_id = $is_logged_in_viewer ? get_the_author_meta( 'ID' ) : 0;
$title     = get_the_title();
$location  = get_post_meta( $post_id, '_classified_loc', true );
$price     = get_post_meta( $post_id, '_classified_price', true );
$rating    = get_post_meta( $post_id, '_classified_rating', true ) ?: '4.5';
$badge     = get_post_meta( $post_id, '_classified_badge', true );
$image     = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600';
$username  = $is_logged_in_viewer ? get_the_author_meta( 'user_login' ) : '';

// Domain terms drive the marketplace filter pills. Rendered as a data
// attribute so filtering is client-side over already-rendered cards and can
// never re-reveal anything the server chose to withhold.
$mk_domains = function_exists( 'get_the_terms' ) ? get_the_terms( $post_id, defined( 'APOLLO_TAX_CLASSIFIED_DOMAIN' ) ? APOLLO_TAX_CLASSIFIED_DOMAIN : 'classified_domain' ) : array();
$mk_domains = is_wp_error( $mk_domains ) || empty( $mk_domains ) ? '' : implode( ' ', wp_list_pluck( $mk_domains, 'slug' ) );
?>

<div class="accom-card reveal-up" data-mk-domains="<?php echo esc_attr( $mk_domains ); ?>" data-classified-id="<?php echo esc_attr( $post_id ); ?>">
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
			<?php if ( $is_hostel && $hostel_url ) : ?>
				<?php /* Hostel: public business — straight out to its own site, no chat, no lock. */ ?>
				<a href="<?php echo esc_url( $hostel_url ); ?>" class="btn-accom btn-accom--hostel"
					target="_blank" rel="noopener">
					<i class="ri-external-link-line" aria-hidden="true"></i> <?php esc_html_e( 'Reservar', 'apollo-adverts' ); ?>
				</a>
			<?php elseif ( $is_logged_in_viewer ) : ?>
				<button
					class="btn-accom btn-open-modal"
					data-a-user="<?php echo esc_attr( (string) $author_id ); ?>"
					data-classified-id="<?php echo esc_attr( $post_id ); ?>"
					data-username="<?php echo esc_attr( $username ); ?>"
				>Ver</button>
			<?php elseif ( $is_hostel ) : ?>
				<?php /* Hostel flagged but no URL saved yet — still unlocked (nothing private
				         to protect), just nowhere to send them, so fall back to the listing. */ ?>
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="btn-accom">Ver</a>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/acesso?redirect=' . rawurlencode( get_permalink( $post_id ) ) ) ); ?>"
					class="btn-accom is-locked" aria-label="<?php esc_attr_e( 'Entre para ver esta hospedagem', 'apollo-adverts' ); ?>">
					<i class="ri-lock-2-line" aria-hidden="true"></i> Ver
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
