<?php
/**
 * Template Part: loc-reviews — Depoimentos e avaliações do local
 *
 * Suporta depoimentos salvos em JSON em _local_testimonials
 * e integra com apollo-comment se ativo.
 *
 * @var int   $local_id
 * @var array $testimonials  Array de {author, text, rating, date}
 *
 * @package Apollo\Local
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $testimonials ) ) {
	return;
}
?>
<section class="loc-section loc-reviews-section" aria-labelledby="loc-reviews-title">
	<h2 id="loc-reviews-title" class="section-title">
		<i class="ri-star-line" aria-hidden="true"></i>
		<?php esc_html_e( 'Depoimentos', 'apollo-local' ); ?>
	</h2>

	<div class="reviews-list">
		<?php foreach ( $testimonials as $review ) : ?>
			<?php
			$author = sanitize_text_field( $review['author'] ?? __( 'Anônimo', 'apollo-local' ) );
			$text   = wp_kses_post( $review['text'] ?? '' );
			$rating = (int) ( $review['rating'] ?? 5 );
			$date   = sanitize_text_field( $review['date'] ?? '' );

			if ( ! $text ) {
				continue;
			}
			?>
			<article class="review-card">
				<header class="review-card__header">
					<span class="review-card__author"><?php echo esc_html( $author ); ?></span>

					<?php if ( $rating > 0 ) : ?>
						<div class="review-card__stars" aria-label="<?php printf( esc_attr__( '%d de 5 estrelas', 'apollo-local' ), $rating ); ?>">
							<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
								<i class="<?php echo $s <= $rating ? 'ri-star-fill' : 'ri-star-line'; ?>" aria-hidden="true"></i>
							<?php endfor; ?>
						</div>
					<?php endif; ?>

					<?php if ( $date ) : ?>
						<time class="review-card__date" datetime="<?php echo esc_attr( $date ); ?>">
							<?php
							$ts = strtotime( $date );
							echo $ts ? esc_html( date_i18n( 'd/m/Y', $ts ) ) : esc_html( $date );
							?>
						</time>
					<?php endif; ?>
				</header>

				<p class="review-card__text"><?php echo $text; // Already escaped via wp_kses_post ?></p>
			</article>
		<?php endforeach; ?>
	</div>
</section>
