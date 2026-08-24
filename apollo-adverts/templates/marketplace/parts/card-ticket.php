<?php

/**
 * Template Part: Ticket Card (Repasse)
 *
 * Carousel-ready ticket card with header block, rip perforation, and chat modal trigger.
 * Meta keys: registry-compliant _classified_* prefix.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id         = get_the_ID();
// Seller identity is member-only — never built/printed for guests.
$is_logged_in_viewer = is_user_logged_in();
$author_id       = $is_logged_in_viewer ? get_the_author_meta( 'ID' ) : 0;
$author_name     = $is_logged_in_viewer ? get_the_author_meta( 'display_name' ) : '';
$author_username = $is_logged_in_viewer ? get_the_author_meta( 'user_login' ) : '';
$author_avatar   = $is_logged_in_viewer ? get_avatar_url( $author_id, array( 'size' => 100 ) ) : '';

$event_title    = get_post_meta( $post_id, '_classified_event_title', true ) ?: get_the_title();
$event_date     = get_post_meta( $post_id, '_classified_event_date', true );
$event_location = get_post_meta( $post_id, '_classified_event_loc', true );
$ticket_price   = get_post_meta( $post_id, '_classified_price', true );
$ticket_qty     = (int) get_post_meta( $post_id, '_classified_quantity', true );
$ticket_image   = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=600';

$intent_terms = wp_get_post_terms( $post_id, 'classified_intent', array( 'fields' => 'names' ) );
$intent_label = ! is_wp_error( $intent_terms ) && ! empty( $intent_terms ) ? $intent_terms[0] : 'REPASSE';

// Domain terms drive the marketplace filter pills. Rendered as a data
// attribute so filtering is client-side over already-rendered cards and can
// never re-reveal anything the server chose to withhold.
$mk_domains = function_exists( 'get_the_terms' ) ? get_the_terms( $post_id, defined( 'APOLLO_TAX_CLASSIFIED_DOMAIN' ) ? APOLLO_TAX_CLASSIFIED_DOMAIN : 'classified_domain' ) : array();
$mk_domains = is_wp_error( $mk_domains ) || empty( $mk_domains ) ? '' : implode( ' ', wp_list_pluck( $mk_domains, 'slug' ) );
?>

<article type="ticket" class="carousel-item reveal-up" data-mk-domains="<?php echo esc_attr( $mk_domains ); ?>" data-classified-id="<?php echo esc_attr( $post_id ); ?>">
	<div class="ticket-header-block">
		<?php echo esc_html( mb_strtoupper( $intent_label ) ); ?> &#9642; RE-SELL &#9642; REVENTA
	</div>

	<div class="top">
		<?php if ( $is_logged_in_viewer ) : ?>
			<div class="ticket-user-info">
				<img src="<?php echo esc_url( $author_avatar ); ?>" class="ticket-avatar" alt="<?php echo esc_attr( $author_name ); ?>">
				<div class="ticket-user-meta">
					<span class="ticket-bandname"><?php echo esc_html( $author_name ); ?></span>
					<span class="ticket-tourname">@<?php echo esc_html( $author_username ); ?></span>
				</div>
			</div>
		<?php else : ?>
			<div class="ticket-user-info ticket-user-info--locked">
				<div class="ticket-avatar ticket-avatar--locked"><i class="ri-lock-2-line"></i></div>
				<div class="ticket-user-meta">
					<span class="ticket-locked-note"><?php esc_html_e( 'Vendedor visível para membros', 'apollo-adverts' ); ?></span>
				</div>
			</div>
		<?php endif; ?>
		<img src="<?php echo esc_url( $ticket_image ); ?>" class="ticket-img" alt="<?php echo esc_attr( $event_title ); ?>">
		<div class="ticket-deetz">
			<div class="ticket-meta-row">
				<div class="ticket-event-title"><?php echo esc_html( $event_title ); ?></div>
				<?php if ( $event_date ) : ?>
					<div class="ticket-date"><?php echo esc_html( $event_date ); ?></div>
				<?php endif; ?>
				<?php if ( $event_location ) : ?>
					<div class="ticket-location"><?php echo esc_html( $event_location ); ?></div>
				<?php endif; ?>
			</div>
			<?php if ( $ticket_qty > 1 ) : ?>
				<?php /* Quantity was collected by the sell form from day one but
				         never stored, so it never reached this card. */ ?>
				<div class="ticket-qty">
					<?php
					printf(
						esc_html(
							/* translators: %d: number of tickets on offer. */
							_n( '%d ingresso', '%d ingressos', $ticket_qty, 'apollo-adverts' )
						),
						(int) $ticket_qty
					);
					?>
				</div>
			<?php endif; ?>
			<?php if ( $ticket_price ) : ?>
				<div class="ticket-price-tag">R$ <?php echo esc_html( number_format( (float) $ticket_price, 0, ',', '.' ) ); ?></div>
			<?php endif; ?>
		</div>
	</div>

	<div class="rip"><div class="rip-line"></div></div>

	<div class="bottom">
		<div class="barcode"></div>
		<?php if ( $is_logged_in_viewer ) : ?>
			<button
				class="btn-chat-ticket btn-open-modal"
				data-a-user="<?php echo esc_attr( (string) $author_id ); ?>"
				data-classified-id="<?php echo esc_attr( $post_id ); ?>"
				data-username="<?php echo esc_attr( $author_username ); ?>"
			>
				<i class="ri-message-3-line"></i>
			</button>
		<?php else : ?>
			<a
				href="<?php echo esc_url( home_url( '/acesso?redirect=' . rawurlencode( get_permalink( $post_id ) ) ) ); ?>"
				class="btn-chat-ticket is-locked"
				aria-label="<?php esc_attr_e( 'Entre para conversar', 'apollo-adverts' ); ?>"
			>
				<i class="ri-lock-2-line"></i>
			</a>
		<?php endif; ?>
	</div>
</article>
