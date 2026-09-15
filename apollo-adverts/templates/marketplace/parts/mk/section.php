<?php
/**
 * Marketplace — one section (label + grid or ticket carousel).
 *
 * Expects: $mk_label, $mk_icon, $mk_type ('ticket'|'accommodation'|'other'), $mk_card
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$mk_args = array(
	'post_type'      => APOLLO_CPT_CLASSIFIED,
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

if ( 'other' === $mk_type ) {
	$mk_args['meta_query'] = array(
		'relation' => 'OR',
		array(
			'key'     => '_classified_type',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_classified_type',
			'value'   => array_merge( APOLLO_ADVERTS_TICKET_TYPES, APOLLO_ADVERTS_ACCOMMODATION_TYPES ),
			'compare' => 'NOT IN',
		),
	);
} else {
	$mk_values = 'ticket' === $mk_type ? APOLLO_ADVERTS_TICKET_TYPES : APOLLO_ADVERTS_ACCOMMODATION_TYPES;
	$mk_args['meta_query'] = array(
		array(
			'key'     => '_classified_type',
			'value'   => $mk_values,
			'compare' => 'IN',
		),
	);
}

$mk_q = new WP_Query( $mk_args );

if ( 'other' === $mk_type && ! $mk_q->have_posts() ) {
	return;
}

$mk_create = is_user_logged_in()
	? home_url( '/novo-anuncio/' )
	: home_url( '/acesso?redirect=' . rawurlencode( home_url( '/novo-anuncio/' ) ) );

$mk_is_carousel = false;
$mk_grid_mod    = 'ticket' === $mk_type ? 'tickets' : ( 'accommodation' === $mk_type ? 'accom' : 'other' );
?>
<section class="mk-section" data-mk-section="<?php echo esc_attr( $mk_type ); ?>">
<div class="mk-sec-lbl">
	<i class="<?php echo esc_attr( $mk_icon ); ?>" aria-hidden="true"></i>
	<?php echo esc_html( $mk_label ); ?>
	<span class="mk-sec-count"><?php echo esc_html( (string) $mk_q->found_posts ); ?></span>
</div>

<?php if ( ! $mk_q->have_posts() ) : ?>
	<div class="mk-empty">
		<i class="<?php echo esc_attr( $mk_icon ); ?>" aria-hidden="true"></i>
		<p><?php esc_html_e( 'Nenhum anúncio publicado ainda.', 'apollo-adverts' ); ?></p>
		<a class="mk-cta mk-cta--ghost" href="<?php echo esc_url( $mk_create ); ?>">
			<?php echo 'accommodation' === $mk_type
				? esc_html__( 'Anunciar hospedagem', 'apollo-adverts' )
				: esc_html__( 'Publicar anúncio', 'apollo-adverts' ); ?>
		</a>
	</div>
<?php else : ?>
	<div
		class="<?php echo $mk_is_carousel ? 'carousel' : 'mk-grid mk-grid--' . esc_attr( $mk_grid_mod ); ?>"
		<?php echo $mk_is_carousel ? ' id="ticketCarousel"' : ''; ?>
	>
		<?php
		$mk_kind = ( 'accommodation' === $mk_type ) ? 'accommodation' : 'ticket';
		while ( $mk_q->have_posts() ) {
			$mk_q->the_post();
			if ( function_exists( 'apollo_adverts_render_rt_card' ) ) {
				apollo_adverts_render_rt_card( (int) get_the_ID(), array( 'kind' => $mk_kind, 'variant' => 'grid' ) );
			} else {
				$mk_card_file = dirname( __DIR__ ) . '/' . $mk_card;
				if ( is_readable( $mk_card_file ) ) {
					include $mk_card_file;
				}
			}
		}
		wp_reset_postdata();
		?>
	</div>
<?php endif; ?>
</section>
