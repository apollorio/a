<?php
/**
 * DJ Card Template — Base
 *
 * @var int    $dj_id
 * @var string $dj_name
 * @var string $dj_image
 * @var array  $dj_sounds
 * @var bool   $dj_verified
 * @var string $dj_bio
 * @var array  $dj_links
 * @var string $dj_url
 * @var int    $dj_upcoming_count
 *
 * @package Apollo\DJs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="a-dj-card" data-dj-id="<?php echo esc_attr( $dj_id ); ?>">
	<a href="<?php echo esc_url( $dj_url ); ?>">
		<img class="a-dj-card__image"
			src="<?php echo esc_url( $dj_image ); ?>"
			alt="<?php echo esc_attr( $dj_name ); ?>"
			loading="lazy">
	</a>

	<div class="a-dj-card__body">
		<div class="a-dj-card__header">
			<h3 class="a-dj-card__name">
				<a href="<?php echo esc_url( $dj_url ); ?>"><?php echo esc_html( $dj_name ); ?></a>
			</h3>
			<?php if ( $dj_verified ) : ?>
				<span class="a-dj-card__verified" title="<?php esc_attr_e( 'Verificado', 'apollo-djs' ); ?>">
					<i class="ri-verified-badge-fill"></i>
				</span>
			<?php endif; ?>
		</div>

		<?php if ( $dj_bio ) : ?>
			<p class="a-dj-card__bio"><?php echo esc_html( $dj_bio ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $dj_sounds ) ) : ?>
			<div class="a-dj-card__sounds">
				<?php foreach ( array_slice( $dj_sounds, 0, 4 ) as $sound ) : ?>
					<span class="a-dj-card__sound-tag"><?php echo esc_html( $sound ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="a-dj-card__footer">
			<span class="a-dj-card__events-count">
				<?php
				if ( $dj_upcoming_count > 0 ) {
					printf(
						esc_html( _n( '%d evento', '%d eventos', $dj_upcoming_count, 'apollo-djs' ) ),
						$dj_upcoming_count
					);
				} else {
					esc_html_e( 'Sem eventos', 'apollo-djs' );
				}
				?>
			</span>

			<?php if ( ! empty( $dj_links ) ) : ?>
				<div class="a-dj-card__links">
					<?php
					$all_links = array_merge(
						$dj_links['social'] ?? array(),
						$dj_links['music'] ?? array(),
						$dj_links['platforms'] ?? array()
					);
					foreach ( array_slice( $all_links, 0, 4 ) as $link ) :
						?>
						<a href="<?php echo esc_url( $link['url'] ); ?>"
							target="_blank" rel="noopener"
							title="<?php echo esc_attr( $link['label'] ); ?>">
							<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</article>
