<?php
/**
 * Marketplace — Hospedagem section (mockup accom-section + grid).
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk_args = array(
	'post_type'      => APOLLO_CPT_CLASSIFIED,
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'meta_query'     => array(
		array(
			'key'     => '_classified_type',
			'value'   => APOLLO_ADVERTS_ACCOMMODATION_TYPES,
			'compare' => 'IN',
		),
	),
);

$mk_q = new WP_Query( $mk_args );
$mk_create = is_user_logged_in()
	? home_url( '/novo-anuncio/' )
	: home_url( '/acesso?redirect=' . rawurlencode( home_url( '/novo-anuncio/' ) ) );
?>
<div class="accom-section" data-mk-section="accommodation">
	<div class="section-header reveal-up">
		<h2 class="section-title">
			<i class="ri-hotel-bed-fill" aria-hidden="true"></i>
			<?php esc_html_e( 'Acomodações', 'apollo-adverts' ); ?>
		</h2>
		<span class="section-count" id="marketAccomCount">
			<?php
			printf(
				/* translators: %d: number of accommodation listings */
				esc_html( _n( '%d spot', '%d spots', $mk_q->found_posts, 'apollo-adverts' ) ),
				(int) $mk_q->found_posts
			);
			?>
		</span>
	</div>

	<div class="accom-body">
		<?php if ( ! $mk_q->have_posts() ) : ?>
			<div class="mk-empty reveal-up">
				<i class="ri-home-heart-line" aria-hidden="true"></i>
				<p><?php esc_html_e( 'Nenhuma hospedagem publicada ainda.', 'apollo-adverts' ); ?></p>
				<a class="btn btn-secondary" href="<?php echo esc_url( $mk_create ); ?>">
					<?php esc_html_e( 'Anunciar hospedagem', 'apollo-adverts' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="grid-layout reveal-up" id="accomGrid">
				<?php
				while ( $mk_q->have_posts() ) {
					$mk_q->the_post();
					if ( function_exists( 'apollo_adverts_render_rt_card' ) ) {
						apollo_adverts_render_rt_card(
							(int) get_the_ID(),
							array(
								'kind'    => 'accommodation',
								'variant' => 'grid',
							)
						);
					}
				}
				wp_reset_postdata();
				?>
			</div>
		<?php endif; ?>
	</div>
</div>
