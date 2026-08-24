<?php
/**
 * DJ v3 Partial: Depoimentos
 *
 * Testimonial cards loaded from apollo-comment CPT (Apollo term: depoimento, NEVER comment/review).
 * Falls back to hook-injected content if apollo-comment not active.
 * Variables: $dj_id, $dj_name.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;

// Load depoimentos linked to this DJ
$depoimentos = array();

if ( post_type_exists( 'depoimento' ) ) {
	$depo_query = new WP_Query(
		array(
			'post_type'      => 'depoimento',
			'posts_per_page' => 9,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => '_depoimento_target_id',
					'value' => $dj_id,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( $depo_query->have_posts() ) {
		while ( $depo_query->have_posts() ) {
			$depo_query->the_post();
			$depo_id = get_the_ID();
			$stars   = (int) get_post_meta( $depo_id, '_depoimento_rating', true );

			$depoimentos[] = array(
				'text'   => get_the_content(),
				'name'   => get_post_meta( $depo_id, '_depoimento_author_name', true ) ?: get_the_title(),
				'role'   => get_post_meta( $depo_id, '_depoimento_author_role', true ),
				'source' => get_post_meta( $depo_id, '_depoimento_source', true ),
				'stars'  => $stars > 0 && $stars <= 5 ? $stars : 5,
			);
		}
		wp_reset_postdata();
	}
}

/**
 * Filter: apollo/djs/depoimentos
 * Allows injecting depoimentos from other sources.
 *
 * @param array $depoimentos
 * @param int   $dj_id
 */
$depoimentos = apply_filters( 'apollo/djs/depoimentos', $depoimentos, $dj_id );

if ( empty( $depoimentos ) ) {
	return;
}
?>

<section class="dj-section" id="depoimentos">
	<div class="dj-section-head">
		<div>
			<span class="dj-section-label"><?php esc_html_e( 'O que dizem', 'apollo-djs' ); ?></span>
			<h2><?php esc_html_e( 'Depoimentos', 'apollo-djs' ); ?></h2>
		</div>
	</div>

	<div class="dj-depoimentos-grid">
		<?php foreach ( $depoimentos as $d ) : ?>
			<div class="dj-depoimento">
				<?php if ( ! empty( $d['source'] ) ) : ?>
					<span class="dj-depoimento-source"><?php echo esc_html( $d['source'] ); ?></span>
				<?php endif; ?>

				<div class="dj-stars" aria-label="<?php echo esc_attr( $d['stars'] . '/5' ); ?>">
					<?php
					for ( $i = 1; $i <= 5; $i++ ) {
						echo $i <= $d['stars'] ? '&#9733;' : '&#9734;';
					}
					?>
				</div>

				<p>&ldquo;<?php echo wp_kses_post( $d['text'] ); ?>&rdquo;</p>

				<div class="dj-depoimento-author">
					<div class="dj-depoimento-author-info">
						<span class="dj-depoimento-author-name"><?php echo esc_html( $d['name'] ); ?></span>
						<?php if ( ! empty( $d['role'] ) ) : ?>
							<span class="dj-depoimento-author-role"><?php echo esc_html( $d['role'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( is_user_logged_in() ) : ?>
		<div class="dj-depoimento-cta">
			<p><?php
				/* translators: %s: DJ name */
				printf( esc_html__( 'Já esteve num evento de %s?', 'apollo-djs' ), esc_html( $dj_name ) );
			?></p>
			<a href="#booking" class="btn btn-ghost">
				<i class="ri-quill-pen-line"></i> <?php esc_html_e( 'Deixe um depoimento', 'apollo-djs' ); ?>
			</a>
		</div>
	<?php endif; ?>
</section>
