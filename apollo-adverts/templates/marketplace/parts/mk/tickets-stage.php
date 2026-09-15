<?php
/**
 * Marketplace — Repasses section (mockup classificados-stage + carousel).
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk_args = array(
	'post_type'      => APOLLO_CPT_CLASSIFIED,
	'post_status'    => 'publish',
	'posts_per_page' => 24,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'meta_query'     => array(
		array(
			'key'     => '_classified_type',
			'value'   => APOLLO_ADVERTS_TICKET_TYPES,
			'compare' => 'IN',
		),
	),
);

$mk_q = new WP_Query( $mk_args );
$mk_create = is_user_logged_in()
	? home_url( '/novo-anuncio/' )
	: home_url( '/acesso?redirect=' . rawurlencode( home_url( '/novo-anuncio/' ) ) );
?>
<div class="section-header reveal-up">
	<h2 class="section-title">
		<i class="ri-ticket-2-fill" aria-hidden="true"></i>
		<?php esc_html_e( 'Repasses', 'apollo-adverts' ); ?>
		<span class="market-view-toggle" data-target="ticketCarousel">
			<i
				class="ri-gallery-view-2"
				data-view="grid"
				role="button"
				tabindex="0"
				title="<?php esc_attr_e( 'Ver em grade', 'apollo-adverts' ); ?>"
				aria-label="<?php esc_attr_e( 'Ver em grade', 'apollo-adverts' ); ?>"
			></i>
		</span>
	</h2>
	<span class="section-count" id="marketTicketCount">
		<?php
		printf(
			/* translators: %d: number of ticket listings */
			esc_html( _n( '%d disponível', '%d disponíveis', $mk_q->found_posts, 'apollo-adverts' ) ),
			(int) $mk_q->found_posts
		);
		?>
	</span>
</div>

<?php require __DIR__ . '/pills.php'; ?>

<section class="mk-section" data-mk-section="ticket">
<?php if ( ! $mk_q->have_posts() ) : ?>
	<div class="classificados-stage is-grid reveal-up" id="classificadosStage">
		<div class="mk-empty">
			<i class="ri-ticket-2-fill" aria-hidden="true"></i>
			<p><?php esc_html_e( 'Nenhum anúncio publicado ainda.', 'apollo-adverts' ); ?></p>
			<a class="btn btn-secondary" href="<?php echo esc_url( $mk_create ); ?>">
				<?php esc_html_e( 'Publicar anúncio', 'apollo-adverts' ); ?>
			</a>
		</div>
	</div>
<?php else : ?>
	<div class="classificados-stage reveal-up" id="classificadosStage">
		<div class="carousel" id="ticketCarousel">
			<?php
			while ( $mk_q->have_posts() ) {
				$mk_q->the_post();
				if ( function_exists( 'apollo_adverts_render_rt_card' ) ) {
					apollo_adverts_render_rt_card(
						(int) get_the_ID(),
						array(
							'kind'    => 'ticket',
							'variant' => 'market',
						)
					);
				}
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
<?php endif; ?>
</section>
