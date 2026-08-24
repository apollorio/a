<?php
/**
 * Template: Event Card — Base Style
 *
 * Variáveis disponíveis (via extract):
 *
 * @var int    $id
 * @var string $title
 * @var string $permalink
 * @var string $excerpt
 * @var string $banner
 * @var string $start_date
 * @var string $end_date
 * @var string $start_time
 * @var string $end_time
 * @var array  $parsed_date
 * @var array  $djs
 * @var string $dj_names
 * @var array  $loc
 * @var string $loc_name
 * @var string $ticket_url
 * @var string $ticket_price
 * @var string $privacy
 * @var string $status
 * @var bool   $is_gone
 * @var array  $sounds
 * @var array  $categories
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gone_class = $is_gone ? ' a-eve-gone' : '';
?>

<article class="a-eve-card<?php echo esc_attr( $gone_class ); ?>" data-event-id="<?php echo esc_attr( $id ); ?>">

	<!-- Banner -->
	<div class="a-eve-card__banner">
		<a href="<?php echo esc_url( $permalink ); ?>">
			<img src="<?php echo esc_url( $banner ); ?>"
				alt="<?php echo esc_attr( $title ); ?>"
				loading="lazy">
		</a>
		<?php if ( 'scheduled' !== $status ) : ?>
			<span class="a-eve-card__status a-eve-card__status--<?php echo esc_attr( $status ); ?>">
				<?php echo esc_html( ucfirst( $status ) ); ?>
			</span>
		<?php endif; ?>
		<?php if ( 'private' === $privacy ) : ?>
			<span class="a-eve-card__status" style="left:8px;right:auto;">🔒</span>
		<?php endif; ?>
	</div>

	<!-- Body -->
	<div class="a-eve-card__body">
		<!-- Date -->
		<div class="a-eve-card__date">
			<span>📅</span>
			<span>
				<?php
				if ( ! empty( $parsed_date ) ) {
					echo esc_html( $parsed_date['dia_semana'] . ', ' . $parsed_date['dia'] . ' de ' . $parsed_date['mes'] );
				} else {
					echo esc_html( $start_date );
				}
				?>
			</span>
			<?php if ( $start_time ) : ?>
				<span>· <?php echo esc_html( $start_time ); ?></span>
			<?php endif; ?>
		</div>

		<!-- Title -->
		<h3 class="a-eve-card__title">
			<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
		</h3>

		<!-- Meta -->
		<div class="a-eve-card__meta">
			<?php if ( $loc_name ) : ?>
				<span class="a-eve-card__meta-item">
					<span>📍</span>
					<span><?php echo esc_html( $loc_name ); ?></span>
				</span>
			<?php endif; ?>

			<?php if ( $dj_names ) : ?>
				<span class="a-eve-card__meta-item">
					<span>🎧</span>
					<span><?php echo esc_html( $dj_names ); ?></span>
				</span>
			<?php endif; ?>

			<?php if ( $start_time && $end_time ) : ?>
				<span class="a-eve-card__meta-item">
					<span>🕐</span>
					<span><?php echo esc_html( $start_time . ' – ' . $end_time ); ?></span>
				</span>
			<?php endif; ?>
		</div>

		<!-- Sound Tags -->
		<?php if ( ! empty( $sounds ) ) : ?>
			<div class="a-eve-card__sounds">
				<?php foreach ( $sounds as $sound ) : ?>
					<span class="a-eve-card__sound-tag"><?php echo esc_html( $sound ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- Footer -->
	<?php if ( $ticket_url || $ticket_price ) : ?>
		<div class="a-eve-card__footer">
			<?php if ( $ticket_price ) : ?>
				<span class="a-eve-card__price"><?php echo esc_html( $ticket_price ); ?></span>
			<?php endif; ?>

			<?php if ( $ticket_url ) : ?>
				<a href="<?php echo esc_url( $ticket_url ); ?>" class="a-eve-card__ticket" target="_blank" rel="noopener">
					🎟 <?php esc_html_e( 'Ingressos', 'apollo-events' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

</article>
