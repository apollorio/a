<?php
/**
 * Template Part: loc-residents — DJs e artistas residentes do local
 *
 * Busca DJs vinculados a este local via meta _dj_local_id ou taxonomia.
 *
 * @var int $local_id
 *
 * @package Apollo\Local
 */

defined( 'ABSPATH' ) || exit;

// Só exibe se o CPT 'dj' existir (apollo-djs ativo)
if ( ! post_type_exists( 'dj' ) ) {
	return;
}

$residents = get_posts(
	array(
		'post_type'      => 'dj',
		'posts_per_page' => 8,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'   => '_dj_local_id',
				'value' => (string) $local_id,
			),
		),
	)
);

if ( empty( $residents ) ) {
	return;
}
?>
<section class="loc-section loc-residents-section" aria-labelledby="loc-residents-title">
	<h2 id="loc-residents-title" class="section-title">
		<i class="ri-headphone-line" aria-hidden="true"></i>
		<?php esc_html_e( 'DJs Residentes', 'apollo-local' ); ?>
	</h2>

	<div class="residents-grid">
		<?php foreach ( $residents as $dj ) : ?>
			<a href="<?php echo esc_url( get_permalink( $dj->ID ) ); ?>" class="resident-card">
				<?php
				$dj_image = get_the_post_thumbnail_url( $dj->ID, 'thumbnail' );
				if ( ! $dj_image ) {
					$dj_image = APOLLO_LOCAL_URL . 'assets/images/placeholder-dj.svg';
				}
				?>
				<div class="resident-card__avatar">
					<img
						src="<?php echo esc_url( $dj_image ); ?>"
						alt="<?php echo esc_attr( get_the_title( $dj->ID ) ); ?>"
						loading="lazy"
						decoding="async"
					>
				</div>
				<p class="resident-card__name"><?php echo esc_html( get_the_title( $dj->ID ) ); ?></p>
			</a>
		<?php endforeach; ?>
	</div>
</section>
